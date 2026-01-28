<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Facades\Excel;
use App\Product;
use App\Variation;
use App\BusinessLocation;
use App\Currency;
use App\brands;
use Carbon\Carbon;
use App\Utils\TransactionUtil;
use App\Transaction;
use App\Utils\ProductUtil;
use App\VariationLocationDetails;


class QuantityEntryController extends Controller
{
    protected $transactionUtil;
    protected $productUtil;


    public function __construct(TransactionUtil $transactionUtil, ProductUtil $productUtil)
    {
        $this->middleware('auth');
        $this->transactionUtil = $transactionUtil;
        $this->productUtil = $productUtil;
    }

    /**
     * Display a listing of the resource.
     */
   public function index()
    {
        $business_id = request()->session()->get('user.business_id');

        $business_locations = BusinessLocation::forDropdown($business_id);
        $user = Transaction::with('createdBy')
        ->where('business_id', $business_id)
        ->latest()
        ->get();


        return view('quantity_entry.index', compact('business_locations','user'));
    }

    
     public function create()
    {
        if (! auth()->user()->can('purchase.create')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = request()->session()->get('user.business_id');

        // التحقق من الاشتراك
        if (! $this->moduleUtil->isSubscribed($business_id)) {
            return $this->moduleUtil->expiredResponse();
        }

        // جلب المواقع
        $business_locations = BusinessLocation::forDropdown($business_id, false, true);
        $business_locations = $business_locations['locations'];

        return view('quantity_entry.index', compact('business_locations'));
    }

 


   public function store(Request $request)
{
    DB::beginTransaction();

    try {
        // 0️⃣ Validation (أضف سعر الشراء للتحقق)
        $request->validate([
            'transaction_date' => 'required',
            'location_id'      => 'required',
            'products'         => 'required|array',
            'products.*.quantity' => 'required|numeric|min:1',
            'products.*.purchase_price' => 'required|numeric', // تأكد من إرسال السعر
            'document' => 'nullable|file|max:' . (config('constants.document_size_limit') / 1000),
            'ref_no'           => 'nullable|string'
        ]);

        $business_id = auth()->user()->business_id;
        $user_id     = auth()->id();
        $total_before_tax = 0; // متغير لتجميع الإجمالي

        // 1️⃣ جلب مورد (كما هو)
        // $supplier = DB::table('contacts')->where('business_id', $business_id)->where('type', 'supplier')->first();
        // if (!$supplier) { throw new \Exception('لا يوجد مورد مرتبط بهذا النشاط'); }

        // 2️⃣ توليد الرقم المرجعي (كما هو)
        $year = date('Y'); 
        $ref_count = $this->transactionUtil->setAndGetReferenceCount('purchase');
        $formatted_count = sprintf("%04d", $ref_count);
        $ref_no = "QE" . $year . "/" . $formatted_count;

        // 3️⃣ رفع الملف (كما هو)
        $document = null;
        if ($request->hasFile('document')) {
            $document = $this->transactionUtil->uploadFile($request, 'document', 'documents');
        }

        // 4️⃣ إنشاء Transaction (القيمة مبدئياً 0)
        $transaction = Transaction::create([
            'business_id'      => $business_id,
            'location_id'      => $request->location_id,
            'type'             => 'add_quantity', 
            'status'           => 'received',
           // 'contact_id'       => $supplier->id,
            'ref_no'           => $ref_no,
            'transaction_date' => Carbon::createFromFormat('m/d/Y H:i', $request->transaction_date),
            'final_total'      => 0, // سيتم تحديثه لاحقاً
            'created_by'       => $user_id,
            'document'         => $document,
        ]);

        // 5️⃣ معالجة المنتجات
        foreach ($request->products as $product) {
            $unit_price = $product['purchase_price'] ?? 0;
            $quantity = $product['quantity'];
            $line_total = $unit_price * $quantity;
            
            $total_before_tax += $line_total; // إضافة إجمالي السطر إلى الإجمالي الكلي

            // جلب الكمية السابقة (كما هو)
            $current_stock = DB::table('variation_location_details')
                ->where('variation_id', $product['variation_id'])
                ->where('location_id', $request->location_id)
                ->value('qty_available');

            $prev_qty = $current_stock ?? 0;

            // 5.1️⃣ حفظ في purchase_lines
            DB::table('purchase_lines')->insert([
                'transaction_id'         => $transaction->id,
                'product_id'             => $product['product_id'],
                'variation_id'           => $product['variation_id'],
                'quantity'               => $quantity,
                'previous_quantity'      => $prev_qty,
                'purchase_price'         => $unit_price,
                'purchase_price_inc_tax' => $unit_price,
                'item_tax'               => 0,
                'created_at'             => now(),
                'updated_at'             => now(),
            ]);

            // تحديث المخزون (كما هو)
            $this->productUtil->updateProductQuantity($request->location_id, $product['product_id'], $product['variation_id'], $quantity);
        }

        // ✅ 6️⃣ تحديث الـ Transaction بالإجمالي النهائي
        $transaction->final_total = $total_before_tax;
        $transaction->save();

        // 7️⃣ تسجيل النشاط
        $this->transactionUtil->activityLog($transaction, 'added');

        DB::commit();
        return redirect()->route('quantity_entry.index')->with('status', ['success' => 1, 'msg' => 'تمت العملية بنجاح']);

    } catch (\Exception $e) {
        DB::rollBack();
        return back()->withErrors('حدث خطأ: ' . $e->getMessage());
    }
}

    

   public function getProducts(Request $request)
    {
      if ($request->ajax()) {

        $term = $request->term;
        $business_id = $request->session()->get('user.business_id');

        if (empty($term)) {
            return response()->json([]);
        }

        $products = Product::leftJoin('variations', 'products.id', '=', 'variations.product_id')
            ->where('products.business_id', $business_id)
            ->where(function ($q) use ($term) {
                $q->where('products.name', 'like', "%{$term}%")
                  ->orWhere('variations.sub_sku', 'like', "%{$term}%");
            })
            ->select(
                'products.id as product_id',
                'products.name',
                'variations.id as variation_id',
                'variations.sub_sku'
            )
            ->limit(20)
            ->get();

        $result = [];

        foreach ($products as $product) {
            $result[] = [
                'label' => $product->name . ' - ' . $product->sub_sku,
                'value' => $product->name,
                'product_id' => $product->product_id,
                'variation_id' => $product->variation_id,
            ];
        }

        return response()->json($result);
       }
    }

    public function getPurchaseEntryRow(Request $request)
    {
        if ($request->ajax()) {

        $product_id   = $request->product_id;
        $variation_id = $request->variation_id;
        $location_id  = $request->location_id;
        $row_count    = $request->row_count;

        $business_id = $request->session()->get('user.business_id');

        if (empty($product_id)) {
            return '';
        }

        // المنتج
        $product = Product::where('id', $product_id)
            ->with('unit')
            ->first();

        // الـ Variation
        $variation = Variation::where('product_id', $product_id)
            ->where('id', $variation_id)
            ->with(['variation_location_details' => function ($q) use ($location_id) {
                $q->where('location_id', $location_id);
            }])
            ->first();

        if (!$variation) {
            return response()->json(['error' => 'Variation not found'], 404);
        }

        // سعر الشراء الافتراضي
        $purchase_price = $variation->default_purchase_price ?? 0;

        return view('quantity_entry.partials.simple_purchase_entry_row', compact(
            'product',
            'variation',
            'row_count',
            'purchase_price'
        ));
      }
    }

   public function import(Request $request)
{
    try {
        $request->validate([
            'file' => 'required|max:2048', 
            'location_id' => 'required'
        ]);

        $file = $request->file('file');
        $extension = strtolower($file->getClientOriginalExtension());
        
        if (!in_array($extension, ['xlsx', 'xls', 'csv'])) {
            return response()->json([
                'success' => false,
                'msg' => 'نوع الملف غير مدعوم.'
            ]);
        }

        $parsed_array = Excel::toArray([], $file);

        if (empty($parsed_array) || empty($parsed_array[0])) {
             throw new \Exception("الملف المرفوع فارغ.");
        }

        // تخطي سطر العنوان
        $imported_data = array_splice($parsed_array[0], 1);

        $business_id = auth()->user()->business_id;
        $row_count  = $request->input('row_count', 0);

        $rows = [];
        foreach ($imported_data as $key => $value) {
            $row_index = $key + 1;

            // التحقق من أن السطر ليس فارغاً تماماً
            if (empty($value[0]) && empty($value[1])) {
                continue;
            }

            // تنظيف الـ SKU
            $sku = trim(strval($value[0]));
            if (strpos($sku, '.') !== false) {
                $sku = explode('.', $sku)[0]; 
            }

            // البحث عن الـ Variation
            $variation = Variation::where('sub_sku', $sku)
                ->join('products', 'products.id', '=', 'variations.product_id')
                ->where('products.business_id', $business_id)
                ->select('variations.*')
                ->first();

            if (!$variation) {
                $variation = Variation::where('sub_sku', 'LIKE', '%' . $sku . '%')
                    ->join('products', 'products.id', '=', 'variations.product_id')
                    ->where('products.business_id', $business_id)
                    ->select('variations.*')
                    ->first();
            }

            if (!$variation) {
                throw new \Exception("المنتج SKU: {$sku} غير موجود في السطر {$row_index}");
            }

            $product = Product::where('id', $variation->product_id)
                ->where('business_id', $business_id)
                ->first();

            // --- معالجة القيم الرقمية بشكل آمن ---
            $quantity = isset($value[1]) && is_numeric($value[1]) ? (float)$value[1] : 0;
            $price = isset($value[2]) && is_numeric($value[2]) ? (float)$value[2] : 0;

            $rows[] = [
                'product'    => $product,
                'variation'  => $variation,
                'quantity'   => $quantity,
                'price'      => $price,
                'row_count'  => $row_count++
            ];
        }

        $html = '';
        foreach ($rows as $row) {
            $html .= view('quantity_entry.partials.quantity_entry_row', [
                'product'    => $row['product'],
                'variation'  => $row['variation'],
                'row_count'  => $row['row_count'],
                'quantity'   => $row['quantity'],
                'purchase_price' => $row['price']
            ])->render();
        }

        return response()->json(['success' => true, 'html' => $html]);

    } catch (\Exception $e) {
        return response()->json(['success' => false, 'msg' => $e->getMessage()]);
    }
}

    public function updateProductStock(Request $request)
    {
     $request->validate([
        'product_id' => 'required|integer|exists:products,id',
        'variation_id' => 'required|integer|exists:variations,id',
        'location_id' => 'required|integer|exists:business_locations,id',
        'quantity' => 'required|numeric',
      ]);

     // البحث عن المخزون الحالي
     $variationStock = VariationLocationDetails::firstOrCreate([
        'variation_id' => $request->variation_id,
        'location_id' => $request->location_id
      ]);

     // تحديث المخزون
     $variationStock->qty_available += $request->quantity;
     $variationStock->save();

     return response()->json([
        'success' => true,
        'new_stock' => $variationStock->qty_available
     ]);
    }

   
}



