<?php

namespace App\Http\Controllers;

use App\BusinessLocation;
use App\PurchaseLine;
use App\Transaction;
use App\TransactionSellLinesPurchaseLines;
use App\Utils\ModuleUtil;
use App\Utils\ProductUtil;
use App\Utils\TransactionUtil;
use Datatables;
use DB;
use Illuminate\Http\Request;
use Spatie\Activitylog\Models\Activity;
use App\Events\StockTransferCreatedOrModified;
use Maatwebsite\Excel\Facades\Excel;

class StockTransferController extends Controller
{
    /**
     * All Utils instance.
     */
    protected $productUtil;

    protected $transactionUtil;

    protected $moduleUtil;

    /**
     * Constructor
     *
     * @param  ProductUtils  $product
     * @return void
     */
    public function __construct(ProductUtil $productUtil, TransactionUtil $transactionUtil, ModuleUtil $moduleUtil)
    {
        $this->productUtil = $productUtil;
        $this->transactionUtil = $transactionUtil;
        $this->moduleUtil = $moduleUtil;
        $this->status_colors = [
            'in_transit' => 'bg-yellow',
            'completed' => 'bg-green',
            'pending' => 'bg-red',
        ];
    }

    /**
     * Check if current user can access stock transfer (permission or Admin role).
     */
    private function userCanAccessStockTransfer(): bool
    {
        $user = auth()->user();
        if ($user->can('stock_transfer.view') || $user->can('stock_transfer.create') || $user->can('stock_transfer.view_own')) {
            return true;
        }
        $business_id = request()->session()->get('user.business_id');
        if ($business_id && $user->hasRole('Admin#' . $business_id)) {
            return true;
        }
        return false;
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        if (! $this->userCanAccessStockTransfer()) {
            abort(403, 'Unauthorized action.');
        }
        $business_id = request()->session()->get('user.business_id');
 
        $business_locations = \App\BusinessLocation::where('business_id', $business_id)
        ->pluck('name', 'id');

        $statuses = $this->stockTransferStatuses();

        if (request()->ajax()) {
            $business_id = request()->session()->get('user.business_id');
            $edit_days = request()->session()->get('business.transaction_edit_days');

            $stock_transfers = Transaction::join(
                'business_locations AS l1',
                'transactions.location_id',
                '=',
                'l1.id'
            )
                    ->join('transactions as t2', 't2.transfer_parent_id', '=', 'transactions.id')
                    ->join(
                        'business_locations AS l2',
                        't2.location_id',
                        '=',
                        'l2.id'
                    )
                    ->leftJoin('transaction_sell_lines as tsl', 'transactions.id', '=', 'tsl.transaction_id')
                    ->where('transactions.business_id', $business_id)
                    ->where('transactions.type', 'sell_transfer');

                    if (! auth()->user()->can('stock_transfer.view') && auth()->user()->can('stock_transfer.view_own')) {
                        $stock_transfers->where('t2.created_by', request()->session()->get('user.id'));
                    }

                    if (!empty(request()->location_id)) {
                     // نستخدم transactions.location_id لضمان الفلترة على فرع المصدر
                      $stock_transfers->where('transactions.location_id', request()->location_id);
                     }
                      // ✅ فلتر التاريخ
                     if (!empty($request->input('start_date')) && !empty($request->input('end_date'))) {
                    $stock_transfers->whereBetween(
                        'transactions.transaction_date',
                        [
                           $request->input('start_date'),
                         $request->input('end_date'),
                       ]
                      );
                            } 

                    $stock_transfers->select(
                        'transactions.id',
                        'transactions.transaction_date',
                        'transactions.ref_no',
                        'l1.name as location_from',
                        'l2.name as location_to',
                         DB::raw('SUM(tsl.quantity) as added_qty'),
                        'transactions.final_total',
                        'transactions.shipping_charges',
                        'transactions.additional_notes',
                        'transactions.id as DT_RowId',
                        'transactions.status'
                    )->groupBy('transactions.id');

        

            return Datatables::of($stock_transfers)
                ->addColumn('action', function ($row) use ($edit_days) {
                    $html = '<button type="button" title="'.__('stock_adjustment.view_details').'" class="tw-dw-btn tw-dw-btn-xs tw-dw-btn-outline  tw-dw-btn-accent btn-modal" data-container=".view_modal" data-href="'.action([\App\Http\Controllers\StockTransferController::class, 'show'], [$row->id]).'"><i class="fa fa-eye" aria-hidden="true"></i> '.__('messages.view').'</button>';

                    $html .= ' <a href="#" class="print-invoice tw-dw-btn tw-dw-btn-xs tw-dw-btn-outline  tw-dw-btn-info" data-href="'.action([\App\Http\Controllers\StockTransferController::class, 'printInvoice'], [$row->id]).'"><i class="fa fa-print" aria-hidden="true"></i> '.__('messages.print').'</a>';

                    $date = \Carbon::parse($row->transaction_date)
                        ->addDays($edit_days);
                    $today = today();

                    if ($date->gte($today) && auth()->user()->can('stock_transfer.delete')) {
                        $html .= '&nbsp;
                        <button type="button" data-href="'.action([\App\Http\Controllers\StockTransferController::class, 'destroy'], [$row->id]).'" class="tw-dw-btn tw-dw-btn-xs tw-dw-btn-outline  tw-dw-btn-error delete_stock_transfer"><i class="fa fa-trash" aria-hidden="true"></i> '.__('messages.delete').'</button>';
                    }

                    if ($row->status != 'final' && auth()->user()->can('stock_transfer.update')) {
                        $html .= '&nbsp;
                        <a href="'.action([\App\Http\Controllers\StockTransferController::class, 'edit'], [$row->id]).'" class="tw-dw-btn tw-dw-btn-xs tw-dw-btn-outline  tw-dw-btn-primary"><i class="fa fa-edit" aria-hidden="true"></i> '.__('messages.edit').'</a>';
                    }

                    return $html;
                })
                 ->editColumn('added_qty', function ($row) {
                 return $this->productUtil->num_f($row->added_qty, false);
                })
                ->editColumn(
                    'final_total',
                    function($row) {
                        if (auth()->user()->can('view_purchase_price')) {
                            return '<span class="display_currency" data-currency_symbol="true">' . $row->final_total . '</span>';
                        } else {
                            return '<span>-</span>';
                        }
                    }
                )
                ->editColumn(
                    'shipping_charges',
                    '<span class="display_currency" data-currency_symbol="true">{{$shipping_charges}}</span>'
                )
                ->editColumn('status', function ($row) use ($statuses) {
                    $row->status = $row->status == 'final' ? 'completed' : $row->status;
                    $status = $statuses[$row->status];
                    $status_color = ! empty($this->status_colors[$row->status]) ? $this->status_colors[$row->status] : 'bg-gray';
                    $status = $row->status != 'completed' ? '<a href="#" class="stock_transfer_status" data-status="'.$row->status.'" data-href="'.action([\App\Http\Controllers\StockTransferController::class, 'updateStatus'], [$row->id]).'"><span class="label '.$status_color.'">'.$statuses[$row->status].'</span></a>' : '<span class="label '.$status_color.'">'.$statuses[$row->status].'</span>';

                    return $status;
                })
                ->editColumn('transaction_date', '{{@format_datetime($transaction_date)}}')
                ->rawColumns(['final_total', 'action', 'shipping_charges', 'status'])
                ->setRowAttr([
                    'data-href' => function ($row) {
                        return  action([\App\Http\Controllers\StockTransferController::class, 'show'], [$row->id]);
                    }, ])
                ->make(true);
        }

        return view('stock_transfer.index')->with(compact('statuses', 'business_locations'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
public function create()
{
    // التحقق من الصلاحية
    if (!auth()->user()->can('stock_transfer.create') && !$this->userCanAccessStockTransfer()) {
        abort(403, 'Unauthorized action.');
    }

    $business_id = request()->session()->get('user.business_id');

    // التحقق من الاشتراك
    if (!empty($this->moduleUtil) && !$this->moduleUtil->isSubscribed($business_id)) {
        return $this->moduleUtil->expiredResponse(
            action([\App\Http\Controllers\StockTransferController::class, 'index'])
        );
    }

    // جلب المواقع
    $business_locations = BusinessLocation::forDropdown($business_id);

    // جلب الحالات
    $statuses = $this->stockTransferStatuses();

    // تمرير البيانات للـ view
    return view('stock_transfer.create')
        ->with(compact('business_locations', 'statuses'));
}
    private function stockTransferStatuses()
    {
        return [
            'pending' => __('lang_v1.pending'),
            'in_transit' => __('lang_v1.in_transit'),
            'completed' => __('restaurant.completed'),
        ];
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
public function store(Request $request)
{
    if (! auth()->user()->can('stock_transfer.create')) {
        abort(403, 'Unauthorized action.');
    }
        ini_set('max_execution_time', 600); // 10 دقائق
    ini_set('memory_limit', '512M');
    ini_set('post_max_size', '100M');
    ini_set('upload_max_filesize', '100M');

    try {
        $business_id   = $request->session()->get('user.business_id');
        $user_id       = $request->session()->get('user.id');
        $status        = $request->input('status');
        $existing_ref  = trim((string) $request->input('ref_no', ''));
        $is_last_chunk = filter_var($request->input('is_last_chunk'), FILTER_VALIDATE_BOOLEAN);

        if (! $this->moduleUtil->isSubscribed($business_id)) {
            return $this->moduleUtil->expiredResponse(
                action([\App\Http\Controllers\StockTransferController::class, 'index'])
            );
        }

        DB::beginTransaction();

        // ============================================================
        // 1. جلب أو إنشاء الـ Transactions
        // ============================================================
        $sell_transfer     = null;
        $purchase_transfer = null;

        if (!empty($existing_ref)) {
            $sell_transfer = Transaction::where('business_id', $business_id)
                ->where('type', 'sell_transfer')
                ->where('ref_no', $existing_ref)
                ->first();

            if ($sell_transfer) {
                $purchase_transfer = Transaction::where('transfer_parent_id', $sell_transfer->id)
                    ->where('type', 'purchase_transfer')
                    ->first();
            }
        }

        if (!$sell_transfer) {
            // إنشاء transactions جديدين
            $input_data = $request->only([
                'location_id', 'ref_no', 'transaction_date',
                'additional_notes', 'shipping_charges', 'final_total'
            ]);

            $input_data['total_before_tax'] = $input_data['final_total'] ?? 0;
            $input_data['type']             = 'sell_transfer';
            $input_data['business_id']      = $business_id;
            $input_data['created_by']       = $user_id;
            $input_data['transaction_date'] = $this->productUtil->uf_date($input_data['transaction_date'], true);
            $input_data['shipping_charges'] = $this->productUtil->num_uf($input_data['shipping_charges'] ?? 0);
            $input_data['payment_status']   = 'paid';
            $input_data['status']           = $status == 'completed' ? 'final' : $status;

            $ref_count = $this->productUtil->setAndGetReferenceCount('stock_transfer');
            if (empty($input_data['ref_no'])) {
                $input_data['ref_no'] = $this->productUtil->generateReferenceNumber('stock_transfer', $ref_count);
            }

            $sell_transfer = Transaction::create($input_data);

            $purchase_input                       = $input_data;
            $purchase_input['type']               = 'purchase_transfer';
            $purchase_input['location_id']        = $request->input('transfer_location_id');
            $purchase_input['transfer_parent_id'] = $sell_transfer->id;
            $purchase_input['status']             = $status == 'completed' ? 'received' : $status;

            $purchase_transfer = Transaction::create($purchase_input);
        }

        // ============================================================
        // 2. معالجة المنتجات - إضافة جديدة فقط بدون حذف القديم
        // ============================================================
        $products = $request->input('products', []);

        $sell_lines     = [];
        $purchase_lines = [];

        foreach ($products as $product) {
            // حماية من القيم الفارغة أو المفاتيح المختلفة
            $qty        = isset($product['quantity']) && $product['quantity'] !== '' && $product['quantity'] !== null
                            ? $product['quantity']
                            : ($product['qty'] ?? 1);

            $unit_price = isset($product['unit_price']) && $product['unit_price'] !== '' && $product['unit_price'] !== null
                            ? $product['unit_price']
                            : ($product['price'] ?? 0);

            $sell_line_arr = [
                'product_id'     => $product['product_id'],
                'variation_id'   => $product['variation_id'],
                'quantity'       => $this->productUtil->num_uf($qty),
                'item_tax'       => 0,
                'line_total_tax' => 0,
                'tax_id'         => null,
            ];

            if (!empty($product['product_unit_id'])) {
                $sell_line_arr['product_unit_id'] = $product['product_unit_id'];
            }
            if (!empty($product['sub_unit_id'])) {
                $sell_line_arr['sub_unit_id'] = $product['sub_unit_id'];
            }

            $purchase_line_arr = $sell_line_arr;

            if (!empty($product['base_unit_multiplier'])) {
                $sell_line_arr['base_unit_multiplier'] = $product['base_unit_multiplier'];
            }

            $sell_line_arr['unit_price']         = $this->productUtil->num_uf($unit_price);
            $sell_line_arr['unit_price_inc_tax']  = $sell_line_arr['unit_price'];
            $purchase_line_arr['purchase_price']         = $sell_line_arr['unit_price'];
            $purchase_line_arr['purchase_price_inc_tax'] = $sell_line_arr['unit_price'];

            if (!empty($product['lot_no_line_id'])) {
                $sell_line_arr['lot_no_line_id'] = $product['lot_no_line_id'];
                $lot_details = PurchaseLine::find($product['lot_no_line_id']);
                if ($lot_details) {
                    $purchase_line_arr['lot_number'] = $lot_details->lot_number;
                    $purchase_line_arr['mfg_date']   = $lot_details->mfg_date;
                    $purchase_line_arr['exp_date']   = $lot_details->exp_date;
                }
            }

            if (!empty($product['base_unit_multiplier'])) {
                $multiplier = (float) $product['base_unit_multiplier'];
                $purchase_line_arr['quantity']               = $purchase_line_arr['quantity'] * $multiplier;
                $purchase_line_arr['purchase_price']         = $purchase_line_arr['purchase_price'] / $multiplier;
                $purchase_line_arr['purchase_price_inc_tax'] = $purchase_line_arr['purchase_price_inc_tax'] / $multiplier;
            }

            if (
                isset($purchase_line_arr['sub_unit_id'], $purchase_line_arr['product_unit_id']) &&
                $purchase_line_arr['sub_unit_id'] == $purchase_line_arr['product_unit_id']
            ) {
                unset($purchase_line_arr['sub_unit_id']);
            }
            unset($purchase_line_arr['product_unit_id']);

            $sell_lines[]     = $sell_line_arr;
            $purchase_lines[] = $purchase_line_arr;
        }

        // ✅ إضافة السطور الجديدة فقط - بدون استخدام createOrUpdateSellLines
        if (!empty($sell_lines)) {
            $formatted = [];
            foreach ($sell_lines as $line) {
                $formatted[] = new \App\TransactionSellLine($line);
            }
            $sell_transfer->sell_lines()->saveMany($formatted);
        }

        if (!empty($purchase_lines)) {
            $purchase_transfer->purchase_lines()->createMany($purchase_lines);
        }

        // ============================================================
        // 3. تحديث المخزون — فقط في آخر دفعة + completed
        // ============================================================
        if ($is_last_chunk && $status == 'completed') {
            $sell_transfer->load('sell_lines.product');

            foreach ($sell_transfer->sell_lines as $sell_line) {
                if ($sell_line->product && $sell_line->product->enable_stock) {
                    $decrease_qty = $sell_line->quantity;

                    $this->productUtil->decreaseProductQuantity(
                        $sell_line->product_id,
                        $sell_line->variation_id,
                        $sell_transfer->location_id,
                        $decrease_qty
                    );

                    $this->productUtil->updateProductQuantity(
                        $purchase_transfer->location_id,
                        $sell_line->product_id,
                        $sell_line->variation_id,
                        $decrease_qty,
                        0, null, false
                    );
                }
            }

            $this->productUtil->adjustStockOverSelling($purchase_transfer);

            $business = [
                'id'                => $business_id,
                'accounting_method' => $request->session()->get('business.accounting_method'),
                'location_id'       => $sell_transfer->location_id,
            ];
           $this->transactionUtil->mapPurchaseSell($business, $sell_transfer->sell_lines, 'purchase');
        }

        // ============================================================
        // 4. في آخر دفعة — سجّل النشاط وأطلق الحدث
        // ============================================================
        if ($is_last_chunk) {
            $this->transactionUtil->activityLog($sell_transfer, 'added');
            event(new StockTransferCreatedOrModified($sell_transfer, 'added'));
            session()->forget('next_stock_transfer_ref');
        }

        DB::commit();

        // حساب إجمالي الأصناف للتأكيد
        $total_lines = $sell_transfer->sell_lines()->count();
        
        return response()->json([
            'success' => 1,
            'msg'     => $is_last_chunk
                ? __('lang_v1.stock_transfer_added_successfully') . " (إجمالي $total_lines صنف)"
                : "تم حفظ الدفعة بنجاح (إجمالي $total_lines صنف)",
            'ref_no'  => $sell_transfer->ref_no,
        ]);

    } catch (\Exception $e) {
        DB::rollBack();
        \Log::emergency(
            'StockTransfer store | File:' . $e->getFile() .
            ' Line:' . $e->getLine() .
            ' Msg:' . $e->getMessage()
        );

        return response()->json([
            'success' => 0,
            'msg'     => 'حدث خطأ: ' . $e->getMessage(),
        ]);
    }
}
/**
 * =====================================================================
 * دالة importProducts - StockTransferController
 * 
 * ترجع:
 *  - products_raw         : كل المنتجات
 *  - products_sufficient  : المنتجات الكافية فقط (للإضافة للجدول)
 *  - products_insufficient: المنتجات الغير كافية (للتصدير إكسل في JS)
 *  - html_sufficient      : HTML صفوف المنتجات الكافية فقط
 * =====================================================================
 */
public function importProducts(Request $request)
{
    try {
        $business_id = $request->session()->get('user.business_id');
        $location_id = $request->input('location_id');

        $request->validate([
            'file'        => 'required|max:10240',
            'location_id' => 'required',
        ]);

        $file         = $request->file('file');
        $parsed_array = \Excel::toArray([], $file);

        if (empty($parsed_array) || empty($parsed_array[0])) {
            return response()->json(['success' => false, 'msg' => 'الملف فارغ']);
        }

        $rows      = array_slice($parsed_array[0], 1);
        $row_index = (int) $request->input('row_index', 0);
        $skipped   = 0;
        
        $custom_labels = json_decode(session('business.custom_labels'), true);
        $p_labels      = $custom_labels['product'] ?? []; 

        $products_sufficient   = [];
        $products_insufficient = [];
        $html_sufficient       = '';

        foreach ($rows as $row) {
            if (!isset($row[0]) || trim((string) $row[0]) === '') continue;

            $sku = trim((string) $row[0]);
            $qty = (isset($row[1]) && is_numeric($row[1]) && (float)$row[1] > 0)
                   ? (float) $row[1] : 1;

            // جلب كل البيانات في query واحد — بدون findOrFail
            $variation = \App\Variation::where('variations.sub_sku', $sku)
                ->join('products as p', 'p.id', '=', 'variations.product_id')
                ->join('units as u', 'u.id', '=', 'p.unit_id')
                ->join('product_variations as pv', 'pv.id', '=', 'variations.product_variation_id')
                ->leftJoin('variation_location_details as vld', function ($join) use ($location_id) {
                    $join->on('variations.id', '=', 'vld.variation_id')
                         ->where('vld.location_id', '=', $location_id);
                })
                ->where('p.business_id', $business_id)
                ->where('p.is_inactive', 0)
                ->select([
                    'variations.id as variation_id',
                    'p.id as product_id',
                    'p.name as product_name',
                    'p.type as product_type',
                    'p.enable_stock',
                    'p.unit_id',
                    'p.product_custom_field1',  // ✅ أضف
                    'p.product_custom_field2',  // ✅ أضف
                    'p.product_custom_field3',  // ✅ أضف
                    'u.allow_decimal as unit_allow_decimal',
                    'variations.sub_sku',
                    'variations.name as variation_name',
                    'pv.name as product_variation',
                    'variations.default_purchase_price',
                    'variations.dpp_inc_tax',
                    \DB::raw('COALESCE(vld.qty_available, 0) as qty_available'),
                ])
                ->first();

            if (!$variation) {
                $skipped++;
                continue;
            }

             $unit_price    = (float) ($variation->dpp_inc_tax );
            $qty_available = (float) $variation->qty_available;

            $product_data = [
                'product_id'          => (int) $variation->product_id,
                'variation_id'        => (int) $variation->variation_id,
                'sub_sku'             => $variation->sub_sku,
                'product_name'        => $variation->product_name,
                'variation_name'      => $variation->variation_name,
                'product_type'        => $variation->product_type,
                'quantity'            => $qty,
                'unit_price'          => $unit_price,
                'enable_stock'        => (bool) $variation->enable_stock,
                'qty_available'       => $qty_available,
                'product_unit_id'     => (int) $variation->unit_id,
                'unit_allow_decimal'  => (bool) $variation->unit_allow_decimal,
                'sub_unit_id'         => null,
                'base_unit_multiplier'=> null,
                'lot_no_line_id'      => null,
            ];

            // -------------------------------------------------------
            // فصل: كافية أم لا؟
            // -------------------------------------------------------
            $is_sufficient = !$variation->enable_stock || ($qty <= $qty_available);

            if ($is_sufficient) {
                $products_sufficient[] = $product_data;

                // بناء HTML لهذا الصف
                $product_label = htmlspecialchars($variation->product_name);
                if ($variation->product_type == 'variable') {
                    $product_label .= ' - ' . htmlspecialchars($variation->variation_name);
                }
                $max_qty  = $variation->enable_stock ? $qty_available : '';
                $max_rule = $variation->enable_stock ? 'data-rule-max-value="' . $max_qty . '"' : '';
                $line_total = round($qty * $unit_price, 4);

          
// بناء أعمدة الحقول المخصصة
$cf_html = '';
if (!empty($p_labels['custom_field_3'])) {
    $cf_html .= '<td class="text-center custom-field-3">' . htmlspecialchars($variation->product_custom_field3 ?? '-') . '</td>';
}
if (!empty($p_labels['custom_field_1'])) {
    $cf_html .= '<td class="text-center custom-field-1">' . htmlspecialchars($variation->product_custom_field1 ?? '-') . '</td>';
}
if (!empty($p_labels['custom_field_2'])) {
    $cf_html .= '<td class="text-center custom-field-2">' . htmlspecialchars($variation->product_custom_field2 ?? '-') . '</td>';
}

$html_sufficient .= '
<tr class="product_row" data-imported="1">
    <td>
        ' . $product_label . '
        <br><small class="text-primary"><strong>' . htmlspecialchars($variation->sub_sku) . '</strong></small>
        <input type="hidden" name="products[' . $row_index . '][product_id]"   class="product_id"   value="' . $variation->product_id . '">
        <input type="hidden" name="products[' . $row_index . '][variation_id]" class="variation_id" value="' . $variation->variation_id . '">
        <input type="hidden" name="products[' . $row_index . '][enable_stock]" class="enable_stock" value="' . ($variation->enable_stock ? '1' : '0') . '">
        <input type="hidden" name="products[' . $row_index . '][product_unit_id]" value="' . $variation->unit_id . '">
        <input type="hidden" class="hidden_base_unit_price" value="' . $unit_price . '">
        <input type="hidden" class="base_unit_multiplier" name="products[' . $row_index . '][base_unit_multiplier]" value="1">
    </td>
    ' . $cf_html . '
    <td>
        <input type="text"
            class="form-control product_quantity input_number"
            name="products[' . $row_index . '][quantity]"
            value="' . $qty . '"
            ' . $max_rule . '
            data-qty_available="' . $max_qty . '"
            data-rule-required="true">
    </td>
    <td class="show_price_with_permission">
        <input type="text"
            name="products[' . $row_index . '][unit_price]"
            class="form-control product_unit_price input_number"
            value="' . $unit_price . '">
    </td>
    <td class="show_price_with_permission">
        <input type="text" readonly
            name="products[' . $row_index . '][price]"
            class="form-control product_line_total"
            value="' . $line_total . '">
    </td>
    <td class="text-center">
        <i class="fa fa-trash remove_product_row cursor-pointer" aria-hidden="true"></i>
    </td>
</tr>';
                $row_index++;

            } else {
                // غير كافية — للتصدير إكسل في الـ JS فقط
                $products_insufficient[] = $product_data;
            }
        }

        if (empty($products_sufficient) && empty($products_insufficient)) {
            return response()->json([
                'success' => false,
                'msg'     => 'لم يتم العثور على منتجات مطابقة. تأكد من صحة الباركود (SKU)',
            ]);
        }

        return response()->json([
            'success'               => true,
            'html_sufficient'       => $html_sufficient,          // HTML الكافية فقط — للجدول
            'products_sufficient'   => $products_sufficient,      // بيانات الكافية — للحفظ
            'products_insufficient' => $products_insufficient,    // بيانات الغير كافية — للإكسل
            'new_row_index'         => $row_index,
            'skipped'               => $skipped,
        ]);

    } catch (\Exception $e) {
        \Log::error('importProducts: ' . $e->getMessage() . ' | ' . $e->getFile() . ':' . $e->getLine());
        return response()->json([
            'success' => false,
            'msg'     => 'حدث خطأ: ' . $e->getMessage(),
        ]);
    }
}
/////             $sku = trim(strval($value[0]));

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        if (! auth()->user()->can('stock_transfer.view') && ! $this->userCanAccessStockTransfer()) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = request()->session()->get('user.business_id');

        $sell_transfer = Transaction::where('business_id', $business_id)
                            ->where('id', $id)
                            ->where('type', 'sell_transfer')
                            ->with(
                                'contact',
                                'sell_lines',
                                'sell_lines.product',
                                'sell_lines.variations',
                                'sell_lines.variations.product_variation',
                                'sell_lines.lot_details',
                                'sell_lines.sub_unit',
                                'location',
                                'sell_lines.product.unit'
                            )
                            ->first();

        foreach ($sell_transfer->sell_lines as $key => $value) {
            if (! empty($value->sub_unit_id)) {
                $formated_sell_line = $this->transactionUtil->recalculateSellLineTotals($business_id, $value);

                $sell_transfer->sell_lines[$key] = $formated_sell_line;
            }
        }

        $purchase_transfer = Transaction::where('business_id', $business_id)
                    ->where('transfer_parent_id', $sell_transfer->id)
                    ->where('type', 'purchase_transfer')
                    ->first();

        $location_details = ['sell' => $sell_transfer->location, 'purchase' => $purchase_transfer->location];

        $lot_n_exp_enabled = false;
        if (request()->session()->get('business.enable_lot_number') == 1 || request()->session()->get('business.enable_product_expiry') == 1) {
            $lot_n_exp_enabled = true;
        }

        $statuses = $this->stockTransferStatuses();

        $statuses['final'] = __('restaurant.completed');

        $activities = Activity::forSubject($sell_transfer)
           ->with(['causer', 'subject'])
           ->latest()
           ->get();

         $total_transfer_qty = $sell_transfer->sell_lines->sum('quantity');

        return view('stock_transfer.show')
                ->with(compact('sell_transfer', 'location_details', 'lot_n_exp_enabled', 'statuses', 'activities','total_transfer_qty'));
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        if (! auth()->user()->can('stock_transfer.delete') && ! $this->userCanAccessStockTransfer()) {
            abort(403, 'Unauthorized action.');
        }
        try {
            if (request()->ajax()) {
                $edit_days = request()->session()->get('business.transaction_edit_days');
                if (! $this->transactionUtil->canBeEdited($id, $edit_days)) {
                    return ['success' => 0,
                        'msg' => __('messages.transaction_edit_not_allowed', ['days' => $edit_days]), ];
                }

                //Get sell transfer transaction
                $sell_transfer = Transaction::where('id', $id)
                                    ->where('type', 'sell_transfer')
                                    ->with(['sell_lines'])
                                    ->first();

                //Get purchase transfer transaction
                $purchase_transfer = Transaction::where('transfer_parent_id', $sell_transfer->id)
                                    ->where('type', 'purchase_transfer')
                                    ->with(['purchase_lines'])
                                    ->first();

                //Check if any transfer stock is deleted and delete purchase lines
                $purchase_lines = $purchase_transfer->purchase_lines;
                foreach ($purchase_lines as $purchase_line) {
                    if ($purchase_line->quantity_sold > 0) {
                        return ['success' => 0,
                            'msg' => __('lang_v1.stock_transfer_cannot_be_deleted'),
                        ];
                    }
                }

                event( new StockTransferCreatedOrModified($sell_transfer, 'deleted'));

                DB::beginTransaction();
                //Get purchase lines from transaction_sell_lines_purchase_lines and decrease quantity_sold
                $sell_lines = $sell_transfer->sell_lines;
                $deleted_sell_purchase_ids = [];
                $products = []; //variation_id as array

                foreach ($sell_lines as $sell_line) {
                    $purchase_sell_line = TransactionSellLinesPurchaseLines::where('sell_line_id', $sell_line->id)->first();

                    if (! empty($purchase_sell_line)) {
                        //Decrease quntity sold from purchase line
                        PurchaseLine::where('id', $purchase_sell_line->purchase_line_id)
                                ->decrement('quantity_sold', $sell_line->quantity);

                        $deleted_sell_purchase_ids[] = $purchase_sell_line->id;

                        //variation details
                        if (isset($products[$sell_line->variation_id])) {
                            $products[$sell_line->variation_id]['quantity'] += $sell_line->quantity;
                            $products[$sell_line->variation_id]['product_id'] = $sell_line->product_id;
                        } else {
                            $products[$sell_line->variation_id]['quantity'] = $sell_line->quantity;
                            $products[$sell_line->variation_id]['product_id'] = $sell_line->product_id;
                        }
                    }
                }

                //Update quantity available in both location
                if (! empty($products)) {
                    foreach ($products as $key => $value) {
                        //Decrease from location 2
                        $this->productUtil->decreaseProductQuantity(
                            $products[$key]['product_id'],
                            $key,
                            $purchase_transfer->location_id,
                            $products[$key]['quantity']
                        );

                        //Increase in location 1
                        $this->productUtil->updateProductQuantity(
                            $sell_transfer->location_id,
                            $products[$key]['product_id'],
                            $key,
                            $products[$key]['quantity']
                        );
                    }
                }

                //Delete sale line purchase line
                if (! empty($deleted_sell_purchase_ids)) {
                    TransactionSellLinesPurchaseLines::whereIn('id', $deleted_sell_purchase_ids)
                        ->delete();
                }

                //Delete both transactions
                $sell_transfer->delete();
                $purchase_transfer->delete();
                event( new StockTransferCreatedOrModified($sell_transfer, 'deleted'));
                $output = ['success' => 1,
                    'msg' => __('lang_v1.stock_transfer_delete_success'),
                ];
                DB::commit();
            }
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::emergency('File:'.$e->getFile().'Line:'.$e->getLine().'Message:'.$e->getMessage());

            $output = ['success' => 0,
                'msg' => __('messages.something_went_wrong'),
            ];
        }

        return $output;
    }

    /**
     * Checks if ref_number and supplier combination already exists.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function printInvoice($id)
    {
        try {
            $business_id = request()->session()->get('user.business_id');

            $sell_transfer = Transaction::where('business_id', $business_id)
                                ->where('id', $id)
                                ->where('type', 'sell_transfer')
                                ->with(
                                    'contact',
                                    'sell_lines',
                                    'sell_lines.product',
                                    'sell_lines.variations',
                                    'sell_lines.variations.product_variation',
                                    'sell_lines.lot_details',
                                    'location',
                                    'sell_lines.product.unit'
                                )
                                ->first();

            $purchase_transfer = Transaction::where('business_id', $business_id)
                        ->where('transfer_parent_id', $sell_transfer->id)
                        ->where('type', 'purchase_transfer')
                        ->first();

            $location_details = ['sell' => $sell_transfer->location, 'purchase' => $purchase_transfer->location];

            $lot_n_exp_enabled = false;
            if (request()->session()->get('business.enable_lot_number') == 1 || request()->session()->get('business.enable_product_expiry') == 1) {
                $lot_n_exp_enabled = true;
            }

            $output = ['success' => 1, 'receipt' => [], 'print_title' => $sell_transfer->ref_no];
            $output['receipt']['html_content'] = view('stock_transfer.print', compact('sell_transfer', 'location_details', 'lot_n_exp_enabled'))->render();
        } catch (\Exception $e) {
            \Log::emergency('File:'.$e->getFile().'Line:'.$e->getLine().'Message:'.$e->getMessage());

            $output = ['success' => 0,
                'msg' => __('messages.something_went_wrong'),
            ];
        }

        return $output;
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        if (! auth()->user()->can('stock_transfer.update') && ! $this->userCanAccessStockTransfer()) {
            abort(403, 'Unauthorized action.');
        }
        $business_id = request()->session()->get('user.business_id');

        $business_locations = BusinessLocation::forDropdown($business_id);

        $statuses = $this->stockTransferStatuses();

        $sell_transfer = Transaction::where('business_id', $business_id)
                ->where('type', 'sell_transfer')
                ->where('status', '!=', 'final')
                ->with(['sell_lines'])
                ->findOrFail($id);

        $purchase_transfer = Transaction::where('business_id',
                $business_id)
                ->where('transfer_parent_id', $id)
                ->where('status', '!=', 'received')
                ->where('type', 'purchase_transfer')
                ->first();

        $products = [];
        foreach ($sell_transfer->sell_lines as $sell_line) {
            $product = $this->productUtil->getDetailsFromVariation($sell_line->variation_id, $business_id, $sell_transfer->location_id, false);
            $product->formatted_qty_available = $this->productUtil->num_f($product->qty_available);
            $product->sub_unit_id = $sell_line->sub_unit_id;
            $product->quantity_ordered = $sell_line->quantity;
            $product->transaction_sell_lines_id = $sell_line->id;
            $product->lot_no_line_id = $sell_line->lot_no_line_id;

            $product->unit_details = $this->productUtil->getSubUnits($business_id, $product->unit_id);

            //Get lot number dropdown if enabled
            $lot_numbers = [];
            if (request()->session()->get('business.enable_lot_number') == 1 || request()->session()->get('business.enable_product_expiry') == 1) {
                $lot_number_obj = $this->transactionUtil->getLotNumbersFromVariation($sell_line->variation_id, $business_id, $sell_transfer->location_id, true);
                foreach ($lot_number_obj as $lot_number) {
                    $lot_number->qty_formated = $this->productUtil->num_f($lot_number->qty_available);
                    $lot_numbers[] = $lot_number;
                }
            }
            $product->lot_numbers = $lot_numbers;

            $products[] = $product;
        }

        return view('stock_transfer.edit')
                ->with(compact('sell_transfer', 'purchase_transfer', 'business_locations', 'statuses', 'products'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        if (! auth()->user()->can('stock_transfer.update') && ! $this->userCanAccessStockTransfer()) {
            abort(403, 'Unauthorized action.');
        }

        try {
            $business_id = $request->session()->get('user.business_id');

            //Check if subscribed or not
            if (! $this->moduleUtil->isSubscribed($business_id)) {
                return $this->moduleUtil->expiredResponse(action([\App\Http\Controllers\StockTransferController::class, 'index']));
            }

            $business_id = request()->session()->get('user.business_id');

            $sell_transfer = Transaction::where('business_id', $business_id)
                    ->where('type', 'sell_transfer')
                    ->findOrFail($id);

            $sell_transfer_before = $sell_transfer->replicate();

            $purchase_transfer = Transaction::where('business_id',
                    $business_id)
                    ->where('transfer_parent_id', $id)
                    ->where('type', 'purchase_transfer')
                    ->with(['purchase_lines'])
                    ->first();

            $status = $request->input('status');

            DB::beginTransaction();

            $input_data = $request->only(['transaction_date', 'additional_notes', 'shipping_charges', 'final_total']);
            $status = $request->input('status');

            $input_data['total_before_tax'] = $input_data['final_total'];

            $input_data['transaction_date'] = $this->productUtil->uf_date($input_data['transaction_date'], true);
            $input_data['shipping_charges'] = $this->productUtil->num_uf($input_data['shipping_charges']);
            $input_data['status'] = $status == 'completed' ? 'final' : $status;

            $products = $request->input('products');
            $sell_lines = [];
            $purchase_lines = [];
            $edited_purchase_lines = [];
            if (! empty($products)) {
                foreach ($products as $product) {
                    $sell_line_arr = [
                        'product_id' => $product['product_id'],
                        'variation_id' => $product['variation_id'],
                        'quantity' => $this->productUtil->num_uf($product['quantity']),
                        'item_tax' => 0,
                        'line_total_tax' => 0,
                        'tax_id' => null, ];

                    if (! empty($product['product_unit_id'])) {
                        $sell_line_arr['product_unit_id'] = $product['product_unit_id'];
                    }
                    if (! empty($product['sub_unit_id'])) {
                        $sell_line_arr['sub_unit_id'] = $product['sub_unit_id'];
                    }

                    $purchase_line_arr = $sell_line_arr;

                    if (! empty($product['base_unit_multiplier'])) {
                        $sell_line_arr['base_unit_multiplier'] = $product['base_unit_multiplier'];
                    }

                    $sell_line_arr['unit_price'] = $this->productUtil->num_uf($product['unit_price']);
                    $sell_line_arr['unit_price_inc_tax'] = $sell_line_arr['unit_price'];

                    $purchase_line_arr['purchase_price'] = $sell_line_arr['unit_price'];
                    $purchase_line_arr['purchase_price_inc_tax'] = $sell_line_arr['unit_price'];
                    if (isset($product['transaction_sell_lines_id'])) {
                        $sell_line_arr['transaction_sell_lines_id'] = $product['transaction_sell_lines_id'];
                    }

                    if (! empty($product['lot_no_line_id'])) {
                        //Add lot_no_line_id to sell line
                        $sell_line_arr['lot_no_line_id'] = $product['lot_no_line_id'];

                        //Copy lot number and expiry date to purchase line
                        $lot_details = PurchaseLine::find($product['lot_no_line_id']);
                        $purchase_line_arr['lot_number'] = $lot_details->lot_number;
                        $purchase_line_arr['mfg_date'] = $lot_details->mfg_date;
                        $purchase_line_arr['exp_date'] = $lot_details->exp_date;
                    }

                    if (! empty($product['base_unit_multiplier'])) {
                        $purchase_line_arr['quantity'] = $purchase_line_arr['quantity'] * $product['base_unit_multiplier'];
                        $purchase_line_arr['purchase_price'] = $purchase_line_arr['purchase_price'] / $product['base_unit_multiplier'];
                        $purchase_line_arr['purchase_price_inc_tax'] = $purchase_line_arr['purchase_price_inc_tax'] / $product['base_unit_multiplier'];
                    }

                    if (isset($purchase_line_arr['sub_unit_id']) && $purchase_line_arr['sub_unit_id'] == $purchase_line_arr['product_unit_id']) {
                        unset($purchase_line_arr['sub_unit_id']);
                    }
                    unset($purchase_line_arr['product_unit_id']);

                    $sell_lines[] = $sell_line_arr;

                    $purchase_line = [];
                    //check if purchase_line for the variation exists else create new
                    foreach ($purchase_transfer->purchase_lines as $pl) {
                        if ($pl->variation_id == $purchase_line_arr['variation_id']) {
                            $pl->update($purchase_line_arr);
                            $edited_purchase_lines[] = $pl->id;
                            $purchase_line = $pl;
                            break;
                        }
                    }
                    if (empty($purchase_line)) {
                        $purchase_line = new PurchaseLine($purchase_line_arr);
                    }

                    $purchase_lines[] = $purchase_line;
                }
            }

            //Create Sell Transfer transaction
            $sell_transfer->update($input_data);
            $sell_transfer->save();

            event( new StockTransferCreatedOrModified($sell_transfer, 'updated'));

            //Create Purchase Transfer at transfer location
            $input_data['status'] = $status == 'completed' ? 'received' : $status;

            $purchase_transfer->update($input_data);
            $purchase_transfer->save();

            //Sell Product from first location
            if (! empty($sell_lines)) {
                $this->transactionUtil->createOrUpdateSellLines($sell_transfer, $sell_lines, $sell_transfer->location_id, false, 'draft', [], false);
            }

            //Purchase product in second location
            if (! empty($purchase_lines)) {
                if (! empty($edited_purchase_lines)) {
                    PurchaseLine::where('transaction_id', $purchase_transfer->id)
                    ->whereNotIn('id', $edited_purchase_lines)
                    ->delete();
                }
                $purchase_transfer->purchase_lines()->saveMany($purchase_lines);
            }

            //Decrease product stock from sell location
            //And increase product stock at purchase location
            if ($status == 'completed') {
                foreach ($products as $product) {
                    if ($product['enable_stock']) {
                        $decrease_qty = $this->productUtil
                                    ->num_uf($product['quantity']);
                        if (! empty($product['base_unit_multiplier'])) {
                            $decrease_qty = $decrease_qty * $product['base_unit_multiplier'];
                        }

                        $this->productUtil->decreaseProductQuantity(
                            $product['product_id'],
                            $product['variation_id'],
                            $sell_transfer->location_id,
                            $decrease_qty
                        );

                        $this->productUtil->updateProductQuantity(
                            $purchase_transfer->location_id,
                            $product['product_id'],
                            $product['variation_id'],
                            $decrease_qty,
                            0,
                            null,
                            false
                        );
                    }
                }

                //Adjust stock over selling if found
                $this->productUtil->adjustStockOverSelling($purchase_transfer);

                //Map sell lines with purchase lines
                $business = ['id' => $business_id,
                    'accounting_method' => $request->session()->get('business.accounting_method'),
                    'location_id' => $sell_transfer->location_id,
                ];
                $this->transactionUtil->mapPurchaseSell($business, $sell_transfer->sell_lines, 'purchase');
            }

            $this->transactionUtil->activityLog($sell_transfer, 'edited', $sell_transfer_before);

            $output = ['success' => 1,
                'msg' => __('lang_v1.updated_succesfully'),
            ];

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::emergency('File:'.$e->getFile().'Line:'.$e->getLine().'Message:'.$e->getMessage());

            $output = ['success' => 0,
                'msg' => $e->getMessage(),
            ];
        }

      //// old  return redirect('stock-transfers')->with('status', $output);
      return response()->json($output);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function updateStatus(Request $request, $id)
    {
        if (! auth()->user()->can('stock_transfer.update') && ! $this->userCanAccessStockTransfer()) {
            abort(403, 'Unauthorized action.');
        }

        try {
            $business_id = request()->session()->get('user.business_id');

            $sell_transfer = Transaction::where('business_id', $business_id)
                    ->where('type', 'sell_transfer')
                    ->with(['sell_lines', 'sell_lines.product'])
                    ->findOrFail($id);

            $purchase_transfer = Transaction::where('business_id',
                    $business_id)
                    ->where('transfer_parent_id', $id)
                    ->where('type', 'purchase_transfer')
                    ->with(['purchase_lines'])
                    ->first();

            $status = $request->input('status');

            DB::beginTransaction();
            if ($status == 'completed' && $sell_transfer->status != 'completed') {
                foreach ($sell_transfer->sell_lines as $sell_line) {
                    if ($sell_line->product->enable_stock) {
                        $this->productUtil->decreaseProductQuantity(
                            $sell_line->product_id,
                            $sell_line->variation_id,
                            $sell_transfer->location_id,
                            $sell_line->quantity
                        );

                        $this->productUtil->updateProductQuantity(
                            $purchase_transfer->location_id,
                            $sell_line->product_id,
                            $sell_line->variation_id,
                            $sell_line->quantity,
                            0,
                            null,
                            false
                        );
                    }
                }

                //Adjust stock over selling if found
                $this->productUtil->adjustStockOverSelling($purchase_transfer);

                //Map sell lines with purchase lines
                $business = ['id' => $business_id,
                    'accounting_method' => $request->session()->get('business.accounting_method'),
                    'location_id' => $sell_transfer->location_id,
                ];
                $this->transactionUtil->mapPurchaseSell($business, $sell_transfer->sell_lines, 'purchase');
            }
            $purchase_transfer->status = $status == 'completed' ? 'received' : $status;
            $purchase_transfer->save();
            $sell_transfer->status = $status == 'completed' ? 'final' : $status;
            $sell_transfer->save();

            DB::commit();

            $output = ['success' => 1,
                'msg' => __('lang_v1.updated_succesfully'),
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::emergency('File:'.$e->getFile().'Line:'.$e->getLine().'Message:'.$e->getMessage());

            $output = ['success' => 0,
                'msg' => 'File:'.$e->getFile().'Line:'.$e->getLine().'Message:'.$e->getMessage(),
            ];
        }

        return $output;
    }
}