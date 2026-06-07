<?php

namespace App\Http\Controllers;
use App\Exports\DataExport;
use App\BusinessLocation;
use App\PurchaseLine;
use App\Transaction;
use App\Utils\ModuleUtil;
use App\Utils\ProductUtil;
use App\Utils\TransactionUtil;
use Datatables;
use DB;
use Illuminate\Http\Request;
use Spatie\Activitylog\Models\Activity;
use App\Events\StockAdjustmentCreatedOrModified;
use App\Variation;
use App\Product;
use Maatwebsite\Excel\Facades\Excel;
use App\Http\Requests\StockAdjustmentRequest;

class StockAdjustmentController extends Controller
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
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {

        if (! auth()->user()->can('stock_adjustment.view') && ! auth()->user()->can('stock_adjustment.create') && ! auth()->user()->can('view_own_stock_adjustment')) {
            abort(403, 'Unauthorized action.');
        }

        if (request()->ajax()) {
            $business_id = request()->session()->get('user.business_id');

            $stock_adjustments = Transaction::join(
                'business_locations AS BL',
                'transactions.location_id',
                '=',
                'BL.id'
            )
                ->leftJoin('users as u', 'transactions.created_by', '=', 'u.id')
                    ->where('transactions.business_id', $business_id)
                    ->where('transactions.type', 'stock_adjustment')
                    ->select(
                        'transactions.id',
                        'transaction_date',
                        'ref_no',
                        'BL.name as location_name',
                        'adjustment_type',
                        'final_total',
                        'total_amount_recovered',
                        'additional_notes',
                        'transactions.id as DT_RowId',
                        DB::raw("CONCAT(COALESCE(u.surname, ''),' ',COALESCE(u.first_name, ''),' ',COALESCE(u.last_name,'')) as added_by"),
                        // ---  جلب إجمالي الكميات ---
                        DB::raw('(SELECT SUM(quantity) FROM stock_adjustment_lines WHERE transaction_id = transactions.id) as total_qty'),
                    );

            $permitted_locations = auth()->user()->permitted_locations();
            if ($permitted_locations != 'all') {
                $stock_adjustments->whereIn('transactions.location_id', $permitted_locations);
            }

            $hide = '';
            $start_date = request()->get('start_date');
            $end_date = request()->get('end_date');
            if (! empty($start_date) && ! empty($end_date)) {
                $stock_adjustments->whereBetween(DB::raw('date(transaction_date)'), [$start_date, $end_date]);
                $hide = 'hide';
            }
            $location_id = request()->get('location_id');
            if (! empty($location_id)) {
                $stock_adjustments->where('transactions.location_id', $location_id);
            }

            if (! auth()->user()->can('stock_adjustment.view') && auth()->user()->can('view_own_stock_adjustment')) {
                $stock_adjustments->where('transactions.created_by', request()->session()->get('user.id'));
            }

            if(! auth()->user()->can('stock_adjustment.delete')){
                $hide = 'hide';
            }

            return Datatables::of($stock_adjustments)
                ->addColumn('action', function($row) use ($hide) {
            $show_url = action([\App\Http\Controllers\StockAdjustmentController::class, 'show'], [$row->id]);
            $edit_url = action([\App\Http\Controllers\StockAdjustmentController::class, 'edit'], [$row->id]);
            $delete_url = action([\App\Http\Controllers\StockAdjustmentController::class, 'destroy'], [$row->id]);

           $html = '<button type="button" data-href="' . $show_url . '" class="tw-dw-btn tw-dw-btn-xs tw-dw-btn-outline tw-dw-btn-primary btn-modal" data-container=".view_modal"><i class="fa fa-eye"></i> ' . __("messages.view") . '</button>';
    
           $html .= '&nbsp;<a href="' . $edit_url . '" class="tw-dw-btn tw-dw-btn-xs tw-dw-btn-outline tw-dw-btn-info"><i class="fa fa-edit"></i> ' . __("messages.edit") . '</a>';
    
           $html .= '&nbsp;<button type="button" data-href="' . $delete_url . '" class="tw-dw-btn tw-dw-btn-xs tw-dw-btn-outline tw-dw-btn-error delete_stock_adjustment ' . $hide . '"><i class="fa fa-trash"></i> ' . __("messages.delete") . '</button>';

          return $html;
           })
                ->removeColumn('id')
                ->editColumn(
                    'final_total',
                    function ($row) {
                        if (auth()->user()->can('view_purchase_price')) {
                            return $this->transactionUtil->num_f($row->final_total, true);                     
                         } else {
                            return '<span>-</span>';
                        }
                        
                    }
                )

                ->editColumn(
                    'total_amount_recovered',
                    function ($row) {
                        if (auth()->user()->can('view_purchase_price')) {
                            return $this->transactionUtil->num_f($row->total_amount_recovered, true);                    
                         } else {
                            return '<span>-</span>';
                        }
                    }
                )
                ->editColumn('total_qty', function($row) {
                    return $this->transactionUtil->num_f($row->total_qty, false);
                  })
                ->editColumn('transaction_date', '{{@format_datetime($transaction_date)}}')
                ->editColumn('adjustment_type', function ($row) {
                    return __('stock_adjustment.'.$row->adjustment_type);
                })
                ->setRowAttr([
                    'data-href' => function ($row) {
                        return  action([\App\Http\Controllers\StockAdjustmentController::class, 'show'], [$row->id]);
                    }, ])
                ->rawColumns(['final_total', 'action', 'total_amount_recovered', 'total_qty'])
                ->make(true);
        }

        return view('stock_adjustment.index');
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
  public function create()
    {
        if (!auth()->user()->can('stock_adjustment.create')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = request()->session()->get('user.business_id');

        // التحقق من الاشتراك النشط
        if (!$this->moduleUtil->isSubscribed($business_id)) {
            return $this->moduleUtil->expiredResponse(
                action([\App\Http\Controllers\StockAdjustmentController::class, 'index'])
            );
        }

        // إنشاء أو استرجاع الرقم المرجعي من الجلسة
        if (session()->has('next_stock_adjustment_ref')) {
            $next_ref_no = session('next_stock_adjustment_ref');
        } else {
            $ref_count = $this->productUtil->setAndGetReferenceCount('stock_adjustment', $business_id);
            $next_ref_no = $this->productUtil->generateReferenceNumber('stock_adjustment', $ref_count, $business_id);
            session(['next_stock_adjustment_ref' => $next_ref_no]);
        }

        $business_locations = BusinessLocation::forDropdown($business_id);

        return view('stock_adjustment.create')
                ->with(compact('business_locations', 'next_ref_no'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
 public function store(StockAdjustmentRequest $request)
{
    try {
        $business_id = $request->session()->get('user.business_id');
        $validated = $request->validated();
        
        $location_id = $validated['location_id'];
        $products = $validated['products'];
        $is_last_chunk = filter_var($request->input('is_last_chunk'), FILTER_VALIDATE_BOOLEAN);

        // تجهيز بيانات الإدخال
        $input_data = [
            'location_id' => $location_id,
            'transaction_date' => $validated['transaction_date'],
            'adjustment_type' => $validated['adjustment_type'],
            'additional_notes' => $request->input('additional_notes'),
            'total_amount_recovered' => $this->productUtil->num_uf($validated['total_amount_recovered'] ?? 0),
            'final_total' => $this->productUtil->num_uf($validated['final_total']),
            'ref_no' => $request->input('ref_no'),
            'is_last_chunk' => $is_last_chunk
        ];

        // بدء معاملة قاعدة البيانات
        DB::beginTransaction();

        // استدعاء منطق الحفظ
        $stock_adjustment = $this->productUtil->createStockAdjustment(
            $business_id, 
            $location_id, 
            $products, 
            $input_data
        );

        // إذا فشل الإنشاء، نرمي استثناء
        if (!$stock_adjustment) {
            throw new \Exception('فشل في إنشاء تسوية المخزون');
        }

        // إذا كانت هذه هي الدفعة الأخيرة، نقوم بتأكيد المعاملة ومسح الجلسة
        if ($is_last_chunk) {
            DB::commit();
            session()->forget('next_stock_adjustment_ref');
            
            $output = [
                'success' => 1,
                'msg' => __('stock_adjustment.stock_adjustment_added_successfully'),
                'ref_no' => $stock_adjustment->ref_no,
                'redirect' => route('stock-adjustments.index')
            ];
        } else {
            // للدفعات المتوسطة، نؤقتاً لا نؤكد المعاملة
            DB::commit(); // أو يمكنك استخدام savepoints إذا كنت تريد التراجع الجزئي
            $output = [
                'success' => 1,
                'msg' => 'تم حفظ الدفعة ' . $request->input('chunk_number') . ' بنجاح',
                'ref_no' => $stock_adjustment->ref_no
            ];
        }

    } catch (\Exception $e) {
        DB::rollBack();
        \Log::error('خطأ في حفظ تسوية المخزون: ' . $e->getMessage());
        \Log::error($e->getTraceAsString());
        
        $output = [
            'success' => 0,
            'msg' => 'حدث خطأ أثناء الحفظ: ' . $e->getMessage()
        ];
    }

    return response()->json($output);
}
// أضيفي Request $request قبل المتغير $type أو اجعلي المتغير اختيارياً
public function getNextReference($type = 'stock_adjustment')
{
    try {
        $business_id = session()->get('user.business_id');
        
        // التحقق من وجود رقم في الجلسة أولاً
        if (session()->has('next_' . $type . '_ref')) {
            $ref_no = session('next_' . $type . '_ref');
        } else {
            $ref_count = $this->productUtil->setAndGetReferenceCount($type, $business_id);
            $ref_no = $this->productUtil->generateReferenceNumber($type, $ref_count, $business_id);
            session(['next_' . $type . '_ref' => $ref_no]);
        }

        return response()->json([
            'success' => true,
            'ref_no' => $ref_no
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'msg' => 'خطأ في إنشاء الرقم المرجعي'
        ], 500);
    }
}
    //////////////// export from excel 001
public function import(Request $request)
{
    try {
        $request->validate([
            'file' => 'required|mimes:xlsx,xls,csv|max:5120',
            'location_id' => 'required|exists:business_locations,id'
        ]);

        $file = $request->file('file');
        $business_id = auth()->user()->business_id;
        $location_id = $request->input('location_id');

        $imported_data = \Excel::toArray([], $file);
        
        if (empty($imported_data) || empty($imported_data[0])) {
            throw new \Exception("الملف المرفوع فارغ أو غير صالح");
        }

        $headers = array_shift($imported_data[0]);
        $rows = $imported_data[0];

        $column_mapping = [
            'sku'      => 0,
            'quantity' => 1,
            'price'    => 2
        ];

        $all_skus = [];
        foreach ($rows as $row) {
            if (!empty($row[$column_mapping['sku']])) {
                $all_skus[] = trim(strval($row[$column_mapping['sku']]));
            }
        }

        $variations = \App\Variation::whereIn('variations.sub_sku', $all_skus)
            ->join('products as p', 'p.id', '=', 'variations.product_id')
            ->where('p.business_id', $business_id)
            ->select([
                'variations.id as variation_id',
                'p.id as product_id',
                'variations.sub_sku',
                'p.enable_stock',
                'variations.dpp_inc_tax as last_purchased_price',
                'p.name as product_name',
                'p.product_custom_field1 as custom_field_1',
                'p.product_custom_field2 as custom_field_2',
                'p.product_custom_field3 as custom_field_3'
            ])
            ->get()
            ->keyBy('sub_sku');

        $variation_ids = $variations->pluck('variation_id')->toArray();
        $stock_quantities = \DB::table('variation_location_details')
            ->whereIn('variation_id', $variation_ids)
            ->where('location_id', $location_id)
            ->select('variation_id', \DB::raw('SUM(qty_available) as total_qty'))
            ->groupBy('variation_id')
            ->get()
            ->keyBy('variation_id');

        $products_data       = [];
        $products_insufficient = [];
        $temp_stock_tracker  = [];

        foreach ($rows as $index => $row) {
            $sku = trim(strval($row[$column_mapping['sku']] ?? ''));
            
            if (empty($sku)) continue;

            $variation = $variations->get($sku);

            if (!$variation) {
                $products_insufficient[] = [
                    'sub_sku'       => $sku,
                    'product_name'  => null,
                    'qty'           => $row[$column_mapping['quantity']] ?? 0,
                    'qty_available' => 0,
                    'reason'        => 'المنتج غير موجود في النظام'
                ];
                continue;
            }

            $stock_info      = $stock_quantities->get($variation->variation_id);
            $qty_available   = $stock_info ? floatval($stock_info->total_qty) : 0;
            $qty_requested   = isset($row[$column_mapping['quantity']]) && is_numeric($row[$column_mapping['quantity']])
                ? (float)$row[$column_mapping['quantity']]
                : 0;

            $already_taken   = $temp_stock_tracker[$variation->variation_id] ?? 0;
            $effective_stock = $qty_available - $already_taken;

            if ($variation->enable_stock == 1) {
                if ($qty_requested <= 0 || $qty_requested > $effective_stock) {
                    $products_insufficient[] = [
                        'sub_sku'       => $variation->sub_sku,
                        'product_name'  => $variation->product_name,
                        'qty'           => $qty_requested,
                        'qty_available' => $effective_stock,
                        'reason'        => $qty_requested <= 0
                            ? 'الكمية المطلوبة غير صالحة'
                            : "رصيد غير كافٍ. المتوفر: $effective_stock"
                    ];
                    continue;
                }
            }

            $temp_stock_tracker[$variation->variation_id] = $already_taken + $qty_requested;

            $price = isset($row[$column_mapping['price']]) && is_numeric($row[$column_mapping['price']]) && (float)$row[$column_mapping['price']] > 0
                ? (float)$row[$column_mapping['price']]
                : $variation->last_purchased_price;

            $products_data[] = [
                'variation_id'   => $variation->variation_id,
                'product_id'     => $variation->product_id,
                'sub_sku'        => $variation->sub_sku,
                'qty'            => $qty_requested,
                'price'          => $price,
                'product_name'   => $variation->product_name,
                'custom_field_1' => $variation->custom_field_1,
                'custom_field_2' => $variation->custom_field_2,
                'custom_field_3' => $variation->custom_field_3
            ];
        }

        return response()->json([
            'success'               => true,
            'products'              => $products_data,
            'products_insufficient' => $products_insufficient,
            'imported_count'        => count($products_data),
            'skipped_count'         => count($products_insufficient),
            'msg'                   => count($products_data) . ' منتج تم استيرادهم بنجاح',
        ]);

    } catch (\Exception $e) {
        \Log::error('خطأ في استيراد ملف الإكسل: ' . $e->getMessage());
        \Log::error($e->getTraceAsString());
        
        return response()->json([
            'success' => false,
            'msg'     => 'حدث خطأ أثناء معالجة الملف: ' . $e->getMessage()
        ], 500);
    }
}
     /*
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        if (! auth()->user()->can('stock_adjustment.view')) {
            abort(403, 'Unauthorized action.');
        }
        $business_id = request()->session()->get('user.business_id');
        $stock_adjustment = Transaction::where('transactions.business_id', $business_id)
                    ->where('transactions.id', $id)
                    ->where('transactions.type', 'stock_adjustment')
                    ->with(['stock_adjustment_lines', 'location', 'business', 'stock_adjustment_lines.variation', 'stock_adjustment_lines.variation.product', 'stock_adjustment_lines.variation.product_variation', 'stock_adjustment_lines.lot_details'])
                    ->first();

        $lot_n_exp_enabled = false;
        if (request()->session()->get('business.enable_lot_number') == 1 || request()->session()->get('business.enable_product_expiry') == 1) {
            $lot_n_exp_enabled = true;
        }

        $activities = Activity::forSubject($stock_adjustment)
           ->with(['causer', 'subject'])
           ->latest()
           ->get();

        return view('stock_adjustment.show')
                ->with(compact('stock_adjustment', 'lot_n_exp_enabled', 'activities'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Transaction  $stockAdjustment
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
{
    if (! auth()->user()->can('stock_adjustment.create')) {
        abort(403, 'Unauthorized action.');
    }

    $business_id = request()->session()->get('user.business_id');

    $stock_adjustment = Transaction::where('business_id', $business_id)
        ->where('id', $id)
        ->with([
            'stock_adjustment_lines' => function($query) {
                // هنا نقوم بتهيئة البيانات لتشبه مخرجات دالة get_product_row
                $query->join('variations as v', 'v.id', '=', 'stock_adjustment_lines.variation_id')
                      ->join('products as p', 'p.id', '=', 'v.product_id')
                      ->select([
                          'stock_adjustment_lines.*',
                          'v.sub_sku as sku', // توحيد مسمى الـ SKU
                          'p.name as product_name',
                          'p.product_custom_field1',
                          'p.product_custom_field2',
                          'p.product_custom_field3',
                          'v.dpp_inc_tax as last_purchased_price'
                      ]);
            },
            'location'
        ])
        ->firstOrFail();

    $business_locations = BusinessLocation::forDropdown($business_id);

    return view('stock_adjustment.edit')
            ->with(compact('stock_adjustment', 'business_locations'));
}

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Transaction  $stockAdjustment
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        if (! auth()->user()->can('stock_adjustment.create')) {
            abort(403, 'Unauthorized action.');
        }

        try {
            $business_id = $request->session()->get('user.business_id');
            $products = $request->input('products');

            DB::beginTransaction();

            $stock_adjustment = Transaction::where('business_id', $business_id)
                                    ->where('type', 'stock_adjustment')
                                    ->with(['stock_adjustment_lines'])
                                    ->findOrFail($id);

            // 1. استرجاع الكميات القديمة إلى المخزن قبل التعديل
            foreach ($stock_adjustment->stock_adjustment_lines as $old_line) {
                $this->productUtil->updateProductQuantity(
                    $stock_adjustment->location_id,
                    $old_line->product_id,
                    $old_line->variation_id,
                    $this->productUtil->num_f($old_line->quantity)
                );
            }

            // 2. تحديث بيانات السند الأساسية
            $input_data = $request->only(['transaction_date', 'adjustment_type', 'additional_notes', 'total_amount_recovered', 'final_total']);
            $input_data['transaction_date'] = $this->productUtil->uf_date($input_data['transaction_date'], true);
            $input_data['total_amount_recovered'] = $this->productUtil->num_uf($input_data['total_amount_recovered']);
            $input_data['final_total'] = $this->productUtil->num_uf($input_data['final_total']);

            $stock_adjustment->update($input_data);

            // 3. مسح الأسطر القديمة وإضافة الجديدة مع خصم المخزن
            $stock_adjustment->stock_adjustment_lines()->delete();

            $product_data = [];
            foreach ($products as $product) {
                $qty = $this->productUtil->num_uf($product['quantity']);
                $product_data[] = [
                    'product_id' => $product['product_id'],
                    'variation_id' => $product['variation_id'],
                    'quantity' => $qty,
                    'unit_price' => $this->productUtil->num_uf($product['unit_price']),
                ];

                // خصم الكمية الجديدة من المخزن
                $this->productUtil->decreaseProductQuantity(
                    $product['product_id'],
                    $product['variation_id'],
                    $stock_adjustment->location_id,
                    $qty
                );
            }
            $stock_adjustment->stock_adjustment_lines()->createMany($product_data);

            // 4. إعادة ربط الحسابات (Mapping)
            $business = [
                'id' => $business_id,
                'accounting_method' => $request->session()->get('business.accounting_method'),
                'location_id' => $stock_adjustment->location_id,
            ];
            $this->transactionUtil->mapPurchaseSell($business, $stock_adjustment->stock_adjustment_lines, 'stock_adjustment');

            event(new StockAdjustmentCreatedOrModified($stock_adjustment, 'modified'));

            DB::commit();
            $output = ['success' => 1, 'msg' => __('stock_adjustment.stock_adjustment_added_successfully')];

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::emergency("File:" . $e->getFile(). "Line:" . $e->getLine(). "Message:" . $e->getMessage());
            $output = ['success' => 0, 'msg' => __('messages.something_went_wrong')];
        }

        return redirect('stock-adjustments')->with('status', $output);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        if (! auth()->user()->can('stock_adjustment.delete')) {
            abort(403, 'Unauthorized action.');
        }
        try {
            if (request()->ajax()) {
                DB::beginTransaction();

                $stock_adjustment = Transaction::where('id', $id)
                                    ->where('type', 'stock_adjustment')
                                    ->with(['stock_adjustment_lines'])
                                    ->first();

                //Add deleted product quantity to available quantity
                $stock_adjustment_lines = $stock_adjustment->stock_adjustment_lines;
                if (! empty($stock_adjustment_lines)) {
                    $line_ids = [];
                    foreach ($stock_adjustment_lines as $stock_adjustment_line) {
                        $this->productUtil->updateProductQuantity(
                            $stock_adjustment->location_id,
                            $stock_adjustment_line->product_id,
                            $stock_adjustment_line->variation_id,
                            $this->productUtil->num_f($stock_adjustment_line->quantity)
                        );
                        $line_ids[] = $stock_adjustment_line->id;
                    }

                    $this->transactionUtil->mapPurchaseQuantityForDeleteStockAdjustment($line_ids);
                }
                $stock_adjustment->delete();

                event( new StockAdjustmentCreatedOrModified($stock_adjustment, 'deleted'));


                //Remove Mapping between stock adjustment & purchase.

                $output = ['success' => 1,
                    'msg' => __('stock_adjustment.delete_success'),
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
     * Return product rows
     *
     * @param  Request  $request
     * @return \Illuminate\Http\Response
     */
    public function getProductRow(Request $request)
{
    if (request()->ajax()) {
        $row_index = $request->input('row_index');
        $variation_id = $request->input('variation_id');
        $location_id = $request->input('location_id');
        $business_id = $request->session()->get('user.business_id');

        // تعديل البحث لجلب كافة تفاصيل جدول products بالاشتراك مع الـ variation
       $product = \App\Variation::where('variations.id', $variation_id)
             ->with(['product'])
            ->join('products as p', 'p.id', '=', 'variations.product_id')
            ->join('units as u', 'u.id', '=', 'p.unit_id')
            ->leftJoin('variation_location_details as vld', function ($join) use ($location_id) {
                $join->on('variations.id', '=', 'vld.variation_id')
                     ->where('vld.location_id', '=', $location_id);
            })
            ->where('p.business_id', $business_id)
            ->select([
                'p.id as product_id',
                'p.name as product_name',
                'p.image',
                'vld.qty_available',
                // جلب الحقول المخصصة الثلاثة المطلوبة في نهاية الجدول
                'p.product_custom_field1',
                'p.product_custom_field2',
                'p.product_custom_field3',
                'variations.id as variation_id',
                'variations.sub_sku as sub_sku',
                'u.short_name as unit',
                'u.id as unit_id',
                'variations.dpp_inc_tax as last_purchased_price',
                \DB::raw('COALESCE(vld.qty_available, 0) as qty_available')
            ])->first();
        $product->formatted_qty_available = $this->productUtil->num_f($product->qty_available);
        $type = ! empty($request->input('type')) ? $request->input('type') : 'stock_adjustment';

        // جلب أرقام اللوت إذا كانت مفعلة
        $lot_numbers = [];
        if (request()->session()->get('business.enable_lot_number') == 1 || request()->session()->get('business.enable_product_expiry') == 1) {
            $lot_number_obj = $this->transactionUtil->getLotNumbersFromVariation($variation_id, $business_id, $location_id, true);
            foreach ($lot_number_obj as $lot_number) {
                $lot_number->qty_formated = $this->productUtil->num_f($lot_number->qty_available);
                $lot_numbers[] = $lot_number;
            }
        }
        $product->lot_numbers = $lot_numbers;

        $sub_units = $this->productUtil->getSubUnits($business_id, $product->unit_id, false, $product->product_id);

        $unit_price = $product->last_purchased_price;

        // إرجاع ملف الـ View مع كل البيانات الجديدة
        if ($type == 'stock_transfer') {
            return view('stock_transfer.partials.product_table_row')
                ->with(compact('product', 'row_index', 'sub_units'));
        } else {
            return view('stock_adjustment.partials.product_table_row')
                ->with(compact('product', 'row_index', 'sub_units','unit_price'));
        }
    }
}

    /**
     * Sets expired purchase line as stock adjustmnet
     *
     * @param  int  $purchase_line_id
     * @return json $output
     */
    public function removeExpiredStock($purchase_line_id)
    {
        if (! auth()->user()->can('stock_adjustment.delete')) {
            abort(403, 'Unauthorized action.');
        }

        try {
            $purchase_line = PurchaseLine::where('id', $purchase_line_id)
                                    ->with(['transaction'])
                                    ->first();

            if (! empty($purchase_line)) {
                DB::beginTransaction();

                $qty_unsold = $purchase_line->quantity - $purchase_line->quantity_sold - $purchase_line->quantity_adjusted - $purchase_line->quantity_returned;
                $final_total = $purchase_line->purchase_price_inc_tax * $qty_unsold;

                $user_id = request()->session()->get('user.id');
                $business_id = request()->session()->get('user.business_id');

                //Update reference count
                $ref_count = $this->productUtil->setAndGetReferenceCount('stock_adjustment');

                $stock_adjstmt_data = [
                    'type' => 'stock_adjustment',
                    'business_id' => $business_id,
                    'created_by' => $user_id,
                    'transaction_date' => \Carbon::now()->format('Y-m-d'),
                    'total_amount_recovered' => 0,
                    'location_id' => $purchase_line->transaction->location_id,
                    'adjustment_type' => 'normal',
                    'final_total' => $final_total,
                    'ref_no' => $this->productUtil->generateReferenceNumber('stock_adjustment', $ref_count),
                ];

                //Create stock adjustment transaction
                $stock_adjustment = Transaction::create($stock_adjstmt_data);

                $stock_adjustment_line = [
                    'product_id' => $purchase_line->product_id,
                    'variation_id' => $purchase_line->variation_id,
                    'quantity' => $qty_unsold,
                    'unit_price' => $purchase_line->purchase_price_inc_tax,
                    'removed_purchase_line' => $purchase_line->id,
                ];

                //Create stock adjustment line with the purchase line
                $stock_adjustment->stock_adjustment_lines()->create($stock_adjustment_line);

                //Decrease available quantity
                $this->productUtil->decreaseProductQuantity(
                    $purchase_line->product_id,
                    $purchase_line->variation_id,
                    $purchase_line->transaction->location_id,
                    $qty_unsold
                );

                //Map Stock adjustment & Purchase.
                $business = ['id' => $business_id,
                    'accounting_method' => request()->session()->get('business.accounting_method'),
                    'location_id' => $purchase_line->transaction->location_id,
                ];
                $this->transactionUtil->mapPurchaseSell($business, $stock_adjustment->stock_adjustment_lines, 'stock_adjustment', false, $purchase_line->id);

                DB::commit();

                $output = ['success' => 1,
                    'msg' => __('lang_v1.stock_removed_successfully'),
                ];
            }
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::emergency('File:'.$e->getFile().'Line:'.$e->getLine().'Message:'.$e->getMessage());
            $msg = trans('messages.something_went_wrong');

            if (get_class($e) == \App\Exceptions\PurchaseSellMismatch::class) {
                $msg = $e->getMessage();
            }

            $output = ['success' => 0,
                'msg' => $msg,
            ];
        }

        return $output;
    }
}
