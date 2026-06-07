<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Transaction;
use App\Utils\ProductUtil;
use App\Utils\TransactionUtil;
use App\Utils\Util;
use Datatables;
use App\VariationLocationDetails;
use App\Product;
use App\Variation;
use App\BusinessLocation;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Facades\Excel;

class InventoryController extends Controller
{      
    public function __construct(TransactionUtil $transactionUtil, ProductUtil $productUtil, Util $util)
    {
        $this->middleware('auth');
        $this->transactionUtil = $transactionUtil;
        $this->productUtil = $productUtil;
        $this->util = $util;
    }
   
   public function index(Request $request)
{
    if (!auth()->user()->can('inventory.view')) {
        abort(403, 'Unauthorized action.');
    } 
    $business_id = request()->session()->get('user.business_id');

    // 1. إذا كان الطلب Ajax (تحديث الجدول أو الفلترة)
    if ($request->ajax()) {
        $query = \App\Transaction::where('transactions.business_id', $business_id)
            ->whereIn('transactions.type', ['add_quantity', 'stock_adjustment'])
            ->where('transactions.ref_no', 'like', 'INV-%')
            ->with(['location', 'createdBy'])
            ->select(
                'transactions.id',
                'transactions.transaction_date',
                'transactions.ref_no',
                'transactions.location_id',
                'transactions.type',
                'transactions.final_total',
                'transactions.created_by'
            );

        // ✅ فلتر الفرع
        if ($request->filled('location_id')) {
            $query->where('transactions.location_id', $request->input('location_id'));
        }

        // ✅ فلتر التاريخ (سيرسل من الـ JS بصيغة Y-m-d H:i:s)
        if (!empty($request->input('start_date')) && !empty($request->input('end_date'))) {
            $query->whereBetween('transactions.transaction_date', [
                $request->input('start_date'),
                $request->input('end_date'),
            ]);
        }

        return Datatables::of($query)
            ->addColumn('action', function ($row) {
                $html = '<button type="button" data-href="' . route('inventory.show', [$row->id]) . '" 
                          class="btn btn-primary btn-xs btn-modal" data-container=".view_modal">
                          <i class="fa fa-eye"></i> ' . __("messages.view") . '</button>';

                $html .= '&nbsp;<button type="button" data-href="' . action([\App\Http\Controllers\InventoryController::class, 'printInventory'], [$row->id]) . '" 
                          class="btn btn-success btn-xs btn-print-now">
                          <i class="fa fa-print"></i> ' . __("messages.print") . '</button>';

                return $html;
            })
            ->editColumn('transaction_date', '{{@format_datetime($transaction_date)}}')
            ->editColumn('type', function ($row) {
                if ($row->type == 'add_quantity') {
                    return '<span class="label label-success">' . __("inventory.quantity_entry") . '</span>';
                }
                return '<span class="label label-danger">' . __("inventory.stock_adjustment") . '</span>';
            })
            ->editColumn('final_total', function ($row) {
                return '<span class="display_currency" data-currency_symbol="true">' . $row->final_total . '</span>';
            })
            // إضافة اسم المستخدم واسم الموقع ليعرضهم الـ Datatable
            ->addColumn('location_name', function($row){
                return $row->location->name ?? '';
            })
            ->addColumn('added_by', function($row){
                return $row->createdBy->user_full_name ?? '';
            })
            ->rawColumns(['action', 'type', 'final_total'])
            ->make(true);
    }

    $business_locations = \App\BusinessLocation::where('business_id', $business_id)
        ->pluck('name', 'id');

    return view('inventory.index', compact('business_locations'));
}

     public function show($id)
     {
        if (!auth()->user()->can('inventory.view')) {
        abort(403, 'Unauthorized action.');
    } 
    $business_id = request()->session()->get('user.business_id');
    
    // جلب السند مع تفاصيل المنتجات (العلاقة تعتمد على النوع)
    $transaction = \App\Transaction::where('business_id', $business_id)
        ->with(['location', 'createdBy'])
        ->findOrFail($id);

    // تحديد أسطر البيانات بناءً على نوع السند
    if ($transaction->type == 'add_quantity') {
        $transaction->load('purchase_lines.product', 'purchase_lines.variations');
    } else {
        $transaction->load('stock_adjustment_lines.product', 'stock_adjustment_lines.variations');
    }

    return view('inventory.show', compact('transaction'));
    }

     public function create(Request $request) {
   
      // التأكد من الصلاحية
    if (!auth()->user()->can('inventory.create')) {
        abort(403, 'Unauthorized action.');
    }

     $business_id = request()->session()->get('user.business_id');

        $business_locations = BusinessLocation::forDropdown($business_id);
        $user = Transaction::with('createdBy')
        ->where('business_id', $business_id)
        ->latest()
        ->get();
    return view('inventory.create', compact('business_locations', 'user'));

    }
 public function store(Request $request)
{
    if (!auth()->user()->can('inventory.create')) {
        abort(403, 'Unauthorized action.');
    }

    DB::beginTransaction();

    try {
        $business_id      = auth()->user()->business_id;
        $location_id      = $request->input('location_id');
        $transaction_date = $request->input('transaction_date');
        $user_ref_no      = $request->input('ref_no');

        // ✅ تحقق صحيح من الـ checkbox
        $auto_zero = $request->input('final_zero_out') == 1;

        $to_add                  = [];
        $to_adjust               = [];
        $included_variation_ids  = [];

        // ✅ تحقق من products_data قبل استخدامها
        $products_data = json_decode($request->input('products'), true) ?? [];

        if (empty($products_data) && !$auto_zero) {
            return response()->json(['success' => false, 'msg' => 'قائمة المنتجات فارغة']);
        }

        // ── التحقق من القيم السالبة ───────────────────────
        foreach ($products_data as $product) {
            if ((float)$product['quantity'] < 0) {
                return response()->json([
                    'success' => false,
                    'msg'     => 'فشل الجرد: يوجد قيم سالبة للمنتج (SKU: ' . ($product['sku'] ?? $product['variation_id']) . '). يرجى تصحيح الكمية لتكون 0 أو أكثر.'
                ]);
            }
        }

        // ── فرز المنتجات إلى إدخال أو إخراج ────────────────
        foreach ($products_data as $product) {
            $included_variation_ids[] = $product['variation_id'];
            $diff = (float)$product['quantity'] - (float)$product['current_stock'];

            if ($diff > 0) {
                $to_add[] = [
                    'product_id'   => $product['product_id'],
                    'variation_id' => $product['variation_id'],
                    'quantity'     => $diff,
                    'purchase_price' => $product['purchase_price'],
                ];
            } elseif ($diff < 0) {
                $to_adjust[] = [
                    'product_id'   => $product['product_id'],
                    'variation_id' => $product['variation_id'],
                    'quantity'     => abs($diff),
                    'unit_price'   => $product['purchase_price'],
                ];
            }
        }

        // ── التصفير التلقائي للمنتجات غير المدرجة ───────────
        if ($auto_zero) {
            $other_products = \App\VariationLocationDetails::where('location_id', $location_id)
                ->whereNotIn('variation_id', $included_variation_ids)
                ->where('qty_available', '>', 0) // ✅ فقط الموجب
                ->with(['variation'])
                ->get();

            foreach ($other_products as $product) {
                $to_adjust[] = [
                    'product_id'   => $product->product_id,
                    'variation_id' => $product->variation_id,
                    'quantity'     => $product->qty_available,
                    'unit_price'   => $product->variation->dpp_inc_tax ?? 0,
                ];
            }
        }

        // ✅ لو ما في تغييرات
        if (empty($to_add) && empty($to_adjust)) {
            DB::commit();
            return response()->json([
                'success' => true,
                'msg'     => 'لا يوجد تغييرات في المخزون',
            ]);
        }

        // ── تنفيذ سندات الإدخال ───────────────────────────
        if (!empty($to_add)) {
            if (empty($user_ref_no)) {
                $ref_count = $this->util->setAndGetReferenceCount('add_quantity');
                $add_ref   = $this->util->generateReferenceNumber('add_quantity', $ref_count, $business_id, 'INV-QE-');
            } else {
                $add_ref = $user_ref_no . '-QE';
            }

            $this->productUtil->createAddQuantityTransaction($business_id, [
                'ref_no'           => $add_ref,
                'location_id'      => $location_id,
                'products'         => $to_add,
                'transaction_date' => $transaction_date,
                'is_last_chunk'    => true,
            ]);
        }

        // ── تنفيذ سندات الإخراج ───────────────────────────
        if (!empty($to_adjust)) {
            $total_adjustment_amount = 0;
            foreach ($to_adjust as $line) {
                $total_adjustment_amount += ($line['quantity'] * $line['unit_price']);
            }

            if (empty($user_ref_no)) {
                $ref_count = $this->util->setAndGetReferenceCount('stock_adjustment');
                $adj_ref   = $this->util->generateReferenceNumber('stock_adjustment', $ref_count, $business_id, 'INV-SA-');
            } else {
                $adj_ref = $user_ref_no . '-SA';
            }

            $this->productUtil->createStockAdjustment($business_id, $location_id, $to_adjust, [
                'ref_no'           => $adj_ref,
                'transaction_date' => $transaction_date,
                'adjustment_type'  => 'inventory',
                'location_id'      => $location_id,
                'final_total'      => $total_adjustment_amount,
                'is_last_chunk'    => true,
            ]);

            // تصحيح final_total إذا تغيّر
            $saved_transaction = \App\Transaction::where('ref_no', $adj_ref)
                ->where('business_id', $business_id)
                ->first();

            if ($saved_transaction && $saved_transaction->final_total != $total_adjustment_amount) {
                \Log::warning("final_total mismatch for {$adj_ref}: expected {$total_adjustment_amount}, got {$saved_transaction->final_total}");
                $saved_transaction->final_total = $total_adjustment_amount;
                $saved_transaction->save();
            }
        }

        DB::commit();

        return response()->json(['success' => true, 'msg' => 'تم حفظ الجرد بنجاح']);

    } catch (\Exception $e) {
        DB::rollBack();
        \Log::error("Inventory Store Error: " . $e->getMessage() . ' | Line: ' . $e->getLine() . ' | File: ' . $e->getFile());
        return response()->json(['success' => false, 'msg' => 'حدث خطأ: ' . $e->getMessage()]);
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
                'variations.sub_sku',
                'variations.dpp_inc_tax',
                'products.product_custom_field1 as model',
               'products.product_custom_field2 as size',
               'products.product_custom_field3 as color',
                
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
                'model'        => $product->model,
                'size'         => $product->size,
                'color'        => $product->color,
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
        $location_id  = $request->location_id; // الموقع المختار من الواجهة
        $row_count    = $request->row_count;

        $variation = Variation::where('id', $variation_id)
            ->with(['variation_location_details' => function ($q) use ($location_id) {
                $q->where('location_id', $location_id);
            }])
            ->first();

        // جلب الكمية المتاحة في هذا الموقع تحديداً
        $location_details = $variation->variation_location_details->first();
        $current_qty = $location_details ? $location_details->qty_available : 0;

        $product = Product::find($product_id);
        $purchase_price = $variation->dpp_inc_tax ?? 0;

        $model = $product->product_custom_field1;
        $size  = $product->product_custom_field2;
        $color = $product->product_custom_field3; 

        return view('inventory.partials.simple_purchase_entry_row', compact(
            'product',
            'variation',
            'row_count',
            'purchase_price',
            'current_qty', // نمرر القيمة الصافية للموقع المختار
            'model',
            'size',
            'color'
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
        $location_id = $request->input('location_id'); // 👈 نحتاجه لجلب كمية الفرع
        $extension = strtolower($file->getClientOriginalExtension());
        
        if (!in_array($extension, ['xlsx', 'xls', 'csv'])) {
            return response()->json(['success' => false, 'msg' => 'نوع الملف غير مدعوم.']);
        }

        $parsed_array = Excel::toArray([], $file);
        if (empty($parsed_array) || empty($parsed_array[0])) {
             throw new \Exception("الملف المرفوع فارغ.");
        }

        $imported_data = array_splice($parsed_array[0], 1);
        $business_id = auth()->user()->business_id;
        $row_count = $request->input('row_count', 0);

        $rows = [];
        foreach ($imported_data as $key => $value) {
            $row_index = $key + 1;
            if (empty($value[0]) && empty($value[1])) { continue; }

           $sku = trim(strval($value[0]));

            // البحث عن الـ Variation مع تحميل علاقة تفاصيل الموقع
            $variation = Variation::where('sub_sku', $sku)
                ->join('products', 'products.id', '=', 'variations.product_id')
                ->where('products.business_id', $business_id)
                ->with(['variation_location_details' => function($q) use ($location_id) {
                    $q->where('location_id', $location_id);
                }])
                ->select('variations.*')
                ->first();

            if (!$variation) {
                throw new \Exception("المنتج SKU: {$sku} غير موجود في السطر {$row_index}");
            }

            // 👈 استخراج كمية النظام الحالية للموقع المختار
            $current_qty = 0;
            if (!empty($variation->variation_location_details->first())) {
                $current_qty = $variation->variation_location_details->first()->qty_available;
            }

            $product = Product::find($variation->product_id);

            $quantity = isset($value[1]) && is_numeric($value[1]) ? (float)$value[1] : 0;
            $price = isset($value[2]) && is_numeric($value[2]) && (float)$value[2] > 0 
                     ? (float)$value[2] 
                     : $variation->dpp_inc_tax;

            $rows[] = [
                'product'     => $product,
                'variation'   => $variation,
                'quantity'    => $quantity,
                'price'       => $price,
                'current_qty' => $current_qty, // 👈 حفظ الكمية الحالية
                'row_count'   => $row_count++,
                'model'       => $product->product_custom_field1,
                'size'        => $product->product_custom_field2,
                'color'       => $product->product_custom_field3
            ];
        }

        $html = '';
        foreach ($rows as $row) {
            // مررنا current_qty هنا لكي تظهر في عمود "النظام" بالجدول
            $html .= view('inventory.partials.quantity_entry_row', [
                'product'        => $row['product'],
                'variation'      => $row['variation'],
                'row_count'      => $row['row_count'],
                'quantity'       => $row['quantity'],
                'purchase_price' => $row['price'],
                'current_qty'    => $row['current_qty'], // 👈 ضروري جداً
                'model'          => $row['model'],
                'size'           => $row['size'],
                'color'          => $row['color']
            ])->render();
        }

        return response()->json(['success' => true, 'html' => $html]);

    } catch (\Exception $e) {
        return response()->json(['success' => false, 'msg' => $e->getMessage()]);
    }
}

public function printInventory($id)
{
    try {
        $business_id = request()->session()->get('user.business_id');
        $transaction = \App\Transaction::where('business_id', $business_id)
            ->with(['location', 'createdBy'])
            ->findOrFail($id);

        // تحميل العلاقات حسب نوع السند
        if ($transaction->type == 'add_quantity') {
            $transaction->load('purchase_lines.product', 'purchase_lines.variations');
        } else {
            $transaction->load('stock_adjustment_lines.product', 'stock_adjustment_lines.variations');
        }

        // 👈 السر هنا: نقوم بعمل render لنفس ملف صفحة العرض
        $receipt_html = view('inventory.show', compact('transaction'))->render();

        return response()->json([
            'success' => 1,
            'receipt' => ['html_content' => $receipt_html]
        ]);
    } catch (\Exception $e) {
        return response()->json(['success' => 0, 'msg' => $e->getMessage()]);
    }
}

}
