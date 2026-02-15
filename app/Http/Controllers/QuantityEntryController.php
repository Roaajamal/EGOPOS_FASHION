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
use Datatables;
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
    if (!auth()->user()->can('quantity_entry.view') && !auth()->user()->can('quantity_entry.create')) {
        abort(403, 'Unauthorized action.');
    }

    if (request()->ajax()) {
        $business_id = request()->session()->get('user.business_id');

        $quantity_entry = Transaction::join(
            'business_locations AS BL',
            'transactions.location_id', '=', 'BL.id'
        )
        ->leftJoin('users as u', 'transactions.created_by', '=', 'u.id')
        ->leftJoin('purchase_lines as pl', 'transactions.id', '=', 'pl.transaction_id')
        ->where('transactions.business_id', $business_id)
        ->where('transactions.type', 'add_quantity')
        ->select(
            'transactions.id',
            'transaction_date',
            'ref_no',
            'BL.name as location_name',
            'final_total',
            DB::raw('SUM(pl.quantity) as added_qty'),
            'additional_notes',
            'transactions.id as DT_RowId',
            DB::raw("CONCAT(COALESCE(u.surname, ''),' ',COALESCE(u.first_name, ''),' ',COALESCE(u.last_name,'')) as added_by")
        )
        ->groupBy('transactions.id');

        // فلترة المواقع المسموحة
        $permitted_locations = auth()->user()->permitted_locations();
        if ($permitted_locations != 'all') {
            $quantity_entry->whereIn('transactions.location_id', $permitted_locations);
        }

        return Datatables::of($quantity_entry)
    ->addColumn('action', function ($row) {
        $show_url = action([\App\Http\Controllers\QuantityEntryController::class, 'show'], [$row->id]);
        
        // زر العرض (المعاينة)
        $html = '<button type="button" data-href="' . $show_url . '" class="tw-dw-btn tw-dw-btn-xs tw-dw-btn-outline tw-dw-btn-primary btn-modal" data-container=".view_modal"><i class="fa fa-eye"></i> ' . __("messages.view") . '</button>';

        // زر الطباعة (استخدام الكلاس الجديد والمضمون)
        $print_url = route('quantity_entry.printInvoice', [$row->id]);
        $html .= '&nbsp;<button type="button" data-href="' . $print_url . '" class="tw-dw-btn tw-dw-btn-xs tw-dw-btn-outline tw-dw-btn-success btn-print-now"><i class="fa fa-print"></i> ' . __("messages.print") . '</button>';
          return $html; // تأكدي من وجود هذا السطر
          })
       ->editColumn('final_total', function ($row) {
        if (auth()->user()->can('view_purchase_price')) {
            return $this->transactionUtil->num_f($row->final_total, true);
        }
        return '<span>-</span>';
    })
    ->editColumn('transaction_date', '{{@format_datetime($transaction_date)}}')
    ->editColumn('added_qty', function ($row) {
        return $this->productUtil->num_f($row->added_qty, false);
    })
    ->setRowAttr([
        'data-href' => function ($row) {
            return action([\App\Http\Controllers\QuantityEntryController::class, 'show'], [$row->id]);
        },
        'class' => 'row-clickable'
    ])
    ->rawColumns(['final_total', 'action', 'added_qty'])
    ->make(true);
    }

    return view('quantity_entry.index');
}

    
    public function create()
{
    // التأكد من الصلاحية
    if (! auth()->user()->can('purchase.create')) {
        abort(403, 'Unauthorized action.');
    }

     $business_id = request()->session()->get('user.business_id');

        $business_locations = BusinessLocation::forDropdown($business_id);
        $user = Transaction::with('createdBy')
        ->where('business_id', $business_id)
        ->latest()
        ->get();
    return view('quantity_entry.create', compact('business_locations', 'user'));
}
 
      public function show($id)
{
    // تغيير الصلاحية لتناسب إدخال الكميات
    if (! auth()->user()->can('quantity_entry.view')) {
        abort(403, 'Unauthorized action.');
    }

    $business_id = request()->session()->get('user.business_id');

    $quantity_entry = Transaction::where('transactions.business_id', $business_id)
                ->where('transactions.id', $id)
                ->where('transactions.type', 'add_quantity') // النوع الجديد
                ->with([
                    'purchase_lines', // العلاقة الصحيحة لعمليات الإدخال والشراء
                    'location', 
                    'business', 
                    'purchase_lines.product', 
                    'purchase_lines.variations', 
                    'purchase_lines.variations.product_variation'
                ])
                ->firstOrFail();

    

          return view('quantity_entry.show') // مسار الملف الجديد
            ->with(compact('quantity_entry',));
}


public function store(Request $request)
{
    $products_input = $request->input('products');
    if (is_string($products_input)) {
        $products_data = json_decode($products_input, true);
    } else {
        $products_data = $products_input;
    }

    // التحقق من وجود بيانات
    if (empty($products_data)) {
        return response()->json(['success' => false, 'msg' => 'قائمة المنتجات فارغة']);
    }
    $is_last_chunk = $request->input('is_last_chunk') == 1;

    DB::beginTransaction();
    try {
        // 1. جلب أو إنشاء المعاملة بحالة 'draft'
        $transaction = Transaction::where('ref_no', $request->ref_no)->first();
        if (!$transaction) {
            $transaction = Transaction::create([
                'business_id' => auth()->user()->business_id,
                'location_id' => $request->location_id,
                'type' => 'add_quantity',
                'status' => 'draft', // الحالة مبدئياً مسودة
                'ref_no' => $request->ref_no,
                'transaction_date' => Carbon::createFromFormat('m/d/Y H:i', $request->transaction_date),
                'final_total' => 0,
                'created_by' => auth()->id(),
            ]);
        }

        // 2. إدخال الأسطر فقط في purchase_lines (بدون تحديث المخزون الآن)
        foreach ($products_data as $product) {
            DB::table('purchase_lines')->insert([
                'transaction_id' => $transaction->id,
                'product_id' => $product['product_id'],
                'variation_id' => $product['variation_id'],
                'quantity' => $product['quantity'],
                'purchase_price' => $product['purchase_price'],
                'created_at' => now(),
            ]);
            $transaction->final_total += ($product['quantity'] * $product['purchase_price']);
        }
        $transaction->save();

        // 3. إذا كانت هذه هي الدفعة الأخيرة.. نقوم بتحديث المخزون للجميع!
        if ($is_last_chunk) {
            $all_lines = DB::table('purchase_lines')->where('transaction_id', $transaction->id)->get();
            
            foreach ($all_lines as $line) {
                // تحديث المخزون الفعلي هنا
                $affected = DB::table('variation_location_details')
                    ->where('variation_id', $line->variation_id)
                    ->where('location_id', $transaction->location_id)
                    ->increment('qty_available', $line->quantity);

                if ($affected == 0) {
                    DB::table('variation_location_details')->insert([
                        'product_id' => $line->product_id,
                        'variation_id' => $line->variation_id,
                        'location_id' => $transaction->location_id,
                        'qty_available' => $line->quantity
                    ]);
                }
            }
            // تحويل الحالة إلى مستلمة (نهائية)
            $transaction->status = 'received';
            $transaction->save();
            
        }

        //  تسجيل النشاط
        $this->transactionUtil->activityLog($transaction, 'added');
        DB::commit();
        return response()->json(['success' => true]);

    } catch (\Exception $e) {
        DB::rollBack();
        return response()->json(['success' => false, 'msg' => $e->getMessage()]);
    }
}


    public function cleanupFailedTransaction(Request $request) {
    // مسح المعاملة التي لم تكتمل لكي لا يبقى لها أثر
    Transaction::where('ref_no', $request->ref_no)
               ->where('status', 'draft')
               ->delete();
    return response()->json(['success' => true]);
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
                'variations.sub_sku',
                'variations.dpp_inc_tax'
                
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
                'dpp_inc_tax' => $product->dpp_inc_tax,
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
        $purchase_price = $variation->dpp_inc_tax ?? 0;

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
            'file' => 'required', 
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
           $price = isset($value[2]) && is_numeric($value[2]) && (float)$value[2] > 0 
         ? (float)$value[2] 
         : $variation->dpp_inc_tax;

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

    public function printInvoice($id)
{
    try {
        $business_id = request()->session()->get('user.business_id');

        $quantity_entry = Transaction::where('business_id', $business_id)
            ->where('id', $id)
            ->where('type', 'add_quantity')
            ->with([
                'location',
                
                'purchase_lines',
                'purchase_lines.product',
                'purchase_lines.variations',
                'purchase_lines.variations.product_variation',
                'purchase_lines.product.unit'
            ])
            ->firstOrFail();

        // إعداد العناوين والمعلومات الإضافية إذا لزم الأمر
        $print_title = $quantity_entry->ref_no;

        $output = [
            'success' => 1,
            'receipt' => [],
            'print_title' => $print_title
        ];

        // هنا نقوم بعمل رندر لملف عرض مخصص للطباعة (أو نفس ملف show)
        $output['receipt']['html_content'] = view('quantity_entry.partials.print', compact('quantity_entry'))->render();

    } catch (\Exception $e) {
        \Log::emergency('File:'.$e->getFile().'Line:'.$e->getLine().'Message:'.$e->getMessage());

        $output = [
            'success' => 0,
            'msg' => __('messages.something_went_wrong'),
        ];
    }

    return $output;
}
   
}



