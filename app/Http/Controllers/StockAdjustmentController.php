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
                        DB::raw("CONCAT(COALESCE(u.surname, ''),' ',COALESCE(u.first_name, ''),' ',COALESCE(u.last_name,'')) as added_by")
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
                ->editColumn('transaction_date', '{{@format_datetime($transaction_date)}}')
                ->editColumn('adjustment_type', function ($row) {
                    return __('stock_adjustment.'.$row->adjustment_type);
                })
                ->setRowAttr([
                    'data-href' => function ($row) {
                        return  action([\App\Http\Controllers\StockAdjustmentController::class, 'show'], [$row->id]);
                    }, ])
                ->rawColumns(['final_total', 'action', 'total_amount_recovered'])
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
        if (! auth()->user()->can('stock_adjustment.create')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = request()->session()->get('user.business_id');

        //Check if subscribed or not
        if (! $this->moduleUtil->isSubscribed($business_id)) {
            return $this->moduleUtil->expiredResponse(action([\App\Http\Controllers\StockAdjustmentController::class, 'index']));
        }

        $business_locations = BusinessLocation::forDropdown($business_id);

        return view('stock_adjustment.create')
                ->with(compact('business_locations'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
 public function store(Request $request)
{
    if (! auth()->user()->can('stock_adjustment.create')) {
        abort(403, 'Unauthorized action.');
    }

    try {
        $business_id = $request->session()->get('user.business_id');
        $location_id = $request->input('location_id');
        $products = $request->input('products');

        if (empty($products)) {
            $output = ['success' => 0, 'msg' => __('messages.something_went_wrong')];
            return $request->ajax() ? response()->json($output) : redirect()->back()->with('status', $output);
        }

        // --- 1. الفحص المسبق الصارم جداً ---
        foreach ($products as $product) {
            $v_id = $product['variation_id'];
            
            // تنظيف الكمية المدخلة من أي رموز أو مسافات وتحويلها لرقم عشري
            $qty_requested = (float)str_replace(',', '', $this->productUtil->num_uf($product['quantity']));

            $product_check = \App\Variation::where('variations.id', $v_id)
                ->join('products as p', 'p.id', '=', 'variations.product_id')
                ->leftJoin('variation_location_details as vld', function ($join) use ($location_id) {
                    $join->on('variations.id', '=', 'vld.variation_id')
                         ->where('vld.location_id', '=', $location_id);
                })
                ->where('p.business_id', $business_id)
                ->select([
                    'p.enable_stock',
                    'p.name as product_name',
                    \DB::raw('COALESCE(vld.qty_available, 0) as qty_available')
                ])->first();

            if ($product_check && $product_check->enable_stock == 1) {
                $qty_available = (float)$product_check->qty_available;

                // هذا السطر للدييباج (Debug) - سيطبع القيم في ملف storage/logs/laravel.log
                \Log::info("فحص المخزن: المنتج: {$product_check->product_name}, المطلوب: $qty_requested, المتوفر: $qty_available");

                // المقارنة باستخدام معامل أكبر من الصريح
                if ($qty_requested > $qty_available) {
                    $output = [
                        'success' => 0, 
                        'msg' => "توقف! الكمية المطلوبة للمنتج (" . $product_check->product_name . ") هي ($qty_requested) ولكن المتوفر حالياً هو ($qty_available) فقط."
                    ];
                    
                    if ($request->ajax()) {
                        return response()->json($output);
                    }
                    return redirect()->back()->with('status', $output)->withInput();
                }
            }
        }

        // --- 2. إذا نجح الفحص، نبدأ المعاملة ---
        DB::beginTransaction();

        $input_data = $request->only(['location_id', 'transaction_date', 'adjustment_type', 'additional_notes', 'total_amount_recovered', 'final_total', 'ref_no']);
        $user_id = $request->session()->get('user.id');

        $input_data['type'] = 'stock_adjustment';
        $input_data['business_id'] = $business_id;
        $input_data['created_by'] = $user_id;
        $input_data['transaction_date'] = $this->productUtil->uf_date($input_data['transaction_date'], true);
        $input_data['total_amount_recovered'] = $this->productUtil->num_uf($input_data['total_amount_recovered']);

        $ref_count = $this->productUtil->setAndGetReferenceCount('stock_adjustment');
        if (empty($input_data['ref_no'])) {
            $input_data['ref_no'] = $this->productUtil->generateReferenceNumber('stock_adjustment', $ref_count);
        }

        $product_data = [];
        foreach ($products as $product) {
            $qty = $this->productUtil->num_uf($product['quantity']);
            
            $adjustment_line = [
                'product_id' => $product['product_id'],
                'variation_id' => $product['variation_id'],
                'quantity' => $qty,
                'unit_price' => $this->productUtil->num_uf($product['unit_price']),
            ];

            if (! empty($product['lot_no_line_id'])) {
                $adjustment_line['lot_no_line_id'] = $product['lot_no_line_id'];
            }
            $product_data[] = $adjustment_line;

            $this->productUtil->decreaseProductQuantity(
                $product['product_id'],
                $product['variation_id'],
                $location_id,
                $qty
            );
        }

        $stock_adjustment = Transaction::create($input_data);
        $stock_adjustment->stock_adjustment_lines()->createMany($product_data);

        $business = [
            'id' => $business_id,
            'accounting_method' => $request->session()->get('business.accounting_method'),
            'location_id' => $location_id,
        ];
        $this->transactionUtil->mapPurchaseSell($business, $stock_adjustment->stock_adjustment_lines, 'stock_adjustment');

        event(new StockAdjustmentCreatedOrModified($stock_adjustment, 'added'));
        $this->transactionUtil->activityLog($stock_adjustment, 'added', null, [], false);

        DB::commit();
        $output = ['success' => 1, 'msg' => __('stock_adjustment.stock_adjustment_added_successfully')];

    } catch (\Exception $e) {
        DB::rollBack();
        \Log::emergency('خطأ في الحفظ: '.$e->getMessage());
        $output = ['success' => 0, 'msg' => "حدث خطأ غير متوقع"];
    }

    if ($request->ajax()) {
        return response()->json($output);
    }

    return redirect('stock-adjustments')->with('status', $output);
}
    //////////////// export from excel 001
public function import(Request $request)
{
    try {
        $request->validate([
            'file' => 'required|max:2048', 
            'location_id' => 'required'
        ]);

        $file = $request->file('file');
        $parsed_array = \Excel::toArray([], $file);

        if (empty($parsed_array) || empty($parsed_array[0])) {
             throw new \Exception("الملف المرفوع فارغ.");
        }

        $imported_data = array_splice($parsed_array[0], 1);
        $business_id = auth()->user()->business_id;
        $location_id = $request->input('location_id');
        $row_index = (int)$request->input('row_count', 0);

        $html = '';
        $imported_count = 0;
        $skipped_products = [];
        $temp_stock_tracker = [];

        foreach ($imported_data as $value) {
            if (empty($value[0])) continue;

            $sku = trim(strval($value[0]));
            if (strpos($sku, '.') !== false) { $sku = explode('.', $sku)[0]; }

            // 1. جلب بيانات المنتج
            $variation = \App\Variation::where('variations.sub_sku', $sku)
                ->join('products as p', 'p.id', '=', 'variations.product_id')
                ->join('units as u', 'u.id', '=', 'p.unit_id')
                ->where('p.business_id', $business_id)
                ->select([
                    'variations.id as variation_id',
                    'p.id as product_id',
                    'p.name as product_name',
                    'p.enable_stock',
                    'p.image as image',
                    'u.short_name as unit',
                    'p.product_custom_field2',
                    'variations.default_purchase_price as last_purchased_price'
                ])->first();

            if (!$variation) {
                $skipped_products[] = ['SKU' => $sku, 'Quantity' => $value[1] ?? 0, 'Reason' => 'المنتج غير موجود'];
                continue;
            }

            // 2. التعديل الجوهري: استخدام SUM لجمع كافة السجلات (1 + 0 + -1...) 
            // هذا سيضمن الحصول على المخزن الصافي الذي تراه في شاشات النظام
            $qty_available = \DB::table('variation_location_details')
                ->where('variation_id', $variation->variation_id)
                ->where('location_id', $location_id)
                ->sum('qty_available'); 

            $qty_requested = isset($value[1]) && is_numeric($value[1]) ? (float)$value[1] : 0;

            // 3. الفحص التراكمي داخل ملف الإكسل
            $already_taken = $temp_stock_tracker[$variation->variation_id] ?? 0;
            $effective_stock = $qty_available - $already_taken;

            // تسجيل البيانات للمراقبة (Log)
            \Log::info("فحص استيراد (منطق SUM): SKU: $sku, المنتج: {$variation->product_name}, إجمالي المخزن في DB: $qty_available, المتاح بعد خصم الأسطر السابقة: $effective_stock, المطلوب: $qty_requested");

            if ($variation->enable_stock == 1) {
                // إذا كان الإجمالي الصافي 0 أو أقل من المطلوب، نرفض السطر
                if ($qty_requested <= 0 || $qty_requested > $effective_stock) {
                    $skipped_products[] = [
                        'SKU' => $sku,
                        'Quantity' => $qty_requested,
                        'Reason' => "رصيد غير كافٍ. المخزن الصافي المتوفر: ($effective_stock)"
                    ];
                    continue;
                }
            }

            $temp_stock_tracker[$variation->variation_id] = ($temp_stock_tracker[$variation->variation_id] ?? 0) + $qty_requested;

            // 4. بناء السطر للجدول
            $price = isset($value[2]) && is_numeric($value[2]) ? (float)$value[2] : $variation->last_purchased_price;
            $html .= view('stock_adjustment.partials.product_table_row', [
                'product' => $variation,
                'row_index' => $row_index,
                'quantity' => $qty_requested,
                'purchase_price' => $price
            ])->render();

            $row_index++;
            $imported_count++;
        }

        // إرجاع النتيجة كما هي في منطقك السابق
        $response = ['success' => true, 'html' => $html, 'imported_count' => $imported_count, 'skipped_count' => count($skipped_products), 'download_url' => null];
        if (count($skipped_products) > 0) {
            $file_name = 'skipped_products_' . time() . '.xlsx';
            $folder_path = 'temp_excel/';
            if (!\Storage::disk('public')->exists($folder_path)) { \Storage::disk('public')->makeDirectory($folder_path); }
            \Excel::store(new \App\Exports\DataExport($skipped_products), $folder_path . $file_name, 'public');
            $response['download_url'] = asset('storage/' . $folder_path . $file_name);
        }
        return response()->json($response);

    } catch (\Exception $e) {
        \Log::error("خطأ في الاستيراد: " . $e->getMessage());
        return response()->json(['success' => false, 'msg' => $e->getMessage()]);
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
                          'v.default_purchase_price as last_purchased_price'
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
                'variations.sub_sku as sku',
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
