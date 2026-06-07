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
     public function index(Request $request)
{
    if (!auth()->user()->can('quantity_entry.view')) {
        abort(403, 'Unauthorized action.');
    }

    $business_id = request()->session()->get('user.business_id');
        $business_locations = BusinessLocation::where('business_id', $business_id)
        ->pluck('name', 'id'); 
         
    if (request()->ajax()) {


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
        ->groupBy('transactions.id')
        ->orderBy('transactions.transaction_date', 'desc');

        // ✅ فلتر الفرع
    if ($request->filled('location_id')) {
        $quantity_entry->where('transactions.location_id', $request->input('location_id'));
    }

    // ✅ فلتر التاريخ
    if (!empty($request->input('start_date')) && !empty($request->input('end_date'))) {
    $quantity_entry->whereBetween(
        'transactions.transaction_date',
        [
            $request->input('start_date'),
            $request->input('end_date'),
        ]
        );
       }

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

    return view('quantity_entry.index', compact('business_locations')); 
}

    
    public function create()
{
    // التأكد من الصلاحية
    if (! auth()->user()->can('quantity_entry.create')) {
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
    if (! auth()->user()->can('quantity_entry.view')) {
        abort(403, 'Unauthorized action.');
    }

    $business_id = request()->session()->get('user.business_id');

    $quantity_entry = Transaction::where('transactions.business_id', $business_id)
        ->where('transactions.id', $id)
        ->where('transactions.type', 'add_quantity')
        ->with([
            'location',
            'business',
            'purchase_lines',
            'purchase_lines.product',
            'purchase_lines.variations',
            'purchase_lines.variations.product_variation'
        ])
        ->firstOrFail();

    $total_quantity = $quantity_entry->purchase_lines->sum('quantity');

    return view('quantity_entry.show')
        ->with(compact('quantity_entry', 'total_quantity'));
}


public function store(Request $request)
{
    // معالجة البيانات القادمة (سواء كانت JSON أو Array)
    $products_input = $request->input('products');
    $products_data = is_string($products_input) ? json_decode($products_input, true) : $products_input;

    if (empty($products_data)) {
        return response()->json(['success' => false, 'msg' => 'قائمة المنتجات فارغة']);
    }

    try {
        $business_id = auth()->user()->business_id;
        
        // تجهيز مصفوفة البيانات لإرسالها للـ Util
        $data = [
            'ref_no' => $request->ref_no,
            'location_id' => $request->location_id,
            'transaction_date' => \Carbon\Carbon::createFromFormat('m/d/Y H:i', $request->transaction_date),
            'products' => $products_data,
            'is_last_chunk' => $request->input('is_last_chunk') == 1,
        ];

        $this->productUtil->createAddQuantityTransaction($business_id, $data);

        return response()->json(['success' => true]);

    } catch (\Exception $e) {
        \Log::emergency("File:" . $e->getFile() . "Line:" . $e->getLine() . "Message:" . $e->getMessage());
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
                'variations.dpp_inc_tax',
                'products.product_custom_field1',
                'products.product_custom_field2',
                'products.product_custom_field3'
                
                
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
                'sub_sku'      => $product->sub_sku, // ✅ أضف هذا
                'name'         => $product->name
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
            'file'        => 'required',
            'location_id' => 'required'
        ]);

        $file      = $request->file('file');
        $extension = strtolower($file->getClientOriginalExtension());

        if (!in_array($extension, ['xlsx', 'xls', 'csv'])) {
            return response()->json(['success' => false, 'msg' => 'نوع الملف غير مدعوم.']);
        }

        $parsed_array = Excel::toArray([], $file);

        if (empty($parsed_array) || empty($parsed_array[0])) {
            throw new \Exception("الملف المرفوع فارغ.");
        }

        $imported_data = array_splice($parsed_array[0], 1);
        $business_id   = auth()->user()->business_id;
        $row_count     = (int) $request->input('row_count', 0);

        // جمع كل الـ SKUs دفعة واحدة
        $all_skus = [];
        foreach ($imported_data as $value) {
            if (!empty($value[0])) {
                $all_skus[] = trim(strval($value[0]));
            }
        }

        // جلب كل المنتجات دفعة واحدة
        $variations = Variation::whereIn('variations.sub_sku', $all_skus)
            ->join('products', 'products.id', '=', 'variations.product_id')
            ->where('products.business_id', $business_id)
            ->select(
                'variations.*',
                'products.name as product_name',
                'products.product_custom_field1',
                'products.product_custom_field2',
                'products.product_custom_field3',
                'products.unit_id'
            )
            ->get()
            ->keyBy('sub_sku');

        $rows                  = [];
        $products_insufficient = [];

        foreach ($imported_data as $key => $value) {
            if (empty($value[0]) && empty($value[1])) continue;

            $sku      = trim(strval($value[0]));
            $variation = $variations->get($sku);

            if (!$variation) {
                $products_insufficient[] = [
                    'sub_sku'      => $sku,
                    'product_name' => null,
                    'qty'          => $value[1] ?? 0,
                    'reason'       => 'المنتج غير موجود في النظام'
                ];
                continue;
            }

            $quantity = isset($value[1]) && is_numeric($value[1]) && (float)$value[1] > 0
                ? (float)$value[1] : 0;

            if ($quantity <= 0) {
                $products_insufficient[] = [
                    'sub_sku'      => $variation->sub_sku,
                    'product_name' => $variation->product_name,
                    'qty'          => $value[1] ?? 0,
                    'reason'       => 'الكمية غير صالحة'
                ];
                continue;
            }

            $price = isset($value[2]) && is_numeric($value[2]) && (float)$value[2] > 0
                ? (float)$value[2]
                : $variation->dpp_inc_tax;

            $rows[] = [
                'variation' => $variation,
                'quantity'  => $quantity,
                'price'     => $price,
                'row_count' => $row_count++
            ];
        }

        // بناء الـ HTML
        $html = '';
        foreach ($rows as $row) {
            // بناء كائن product من بيانات الـ variation المدمجة
            $product = (object)[
                'id'                    => $row['variation']->product_id,
                'name'                  => $row['variation']->product_name,
                'product_custom_field1' => $row['variation']->product_custom_field1,
                'product_custom_field2' => $row['variation']->product_custom_field2,
                'product_custom_field3' => $row['variation']->product_custom_field3,
            ];

            $html .= view('quantity_entry.partials.quantity_entry_row', [
                'product'        => $product,
                'variation'      => $row['variation'],
                'row_count'      => $row['row_count'],
                'quantity'       => $row['quantity'],
                'purchase_price' => $row['price'],
            ])->render();
        }

        return response()->json([
            'success'               => true,
            'html'                  => $html,
            'imported_count'        => count($rows),
            'skipped_count'         => count($products_insufficient),
            'products_insufficient' => $products_insufficient,
        ]);

    } catch (\Exception $e) {
        \Log::error('Import Error: ' . $e->getMessage());
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
         $total_quantity = $quantity_entry->purchase_lines->sum('quantity');

        $output = [
            'success' => 1,
            'receipt' => [],
            'print_title' => $print_title
        ];

        // هنا نقوم بعمل رندر لملف عرض مخصص للطباعة (أو نفس ملف show)
        $output['receipt']['html_content'] = view('quantity_entry.partials.print', compact('quantity_entry','total_quantity'))->render();

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



