<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Facades\Excel;
use Yajra\DataTables\Facades\DataTables;
use App\BusinessLocation;
use App\Product;
use App\Variation;
use App\ProductOffer;
use App\ProductOfferBundle;
use App\ProductOfferBundleItem;
use App\ProductAltBarcode;
use App\ProductSpecialOffer;
use App\ProductSpecialOfferItem;
use App\Utils\BusinessUtil;
use App\Utils\ProductUtil;
use App\Imports\ProductOffersImport;

class ProductOfferController extends Controller
{
    protected $businessUtil;
    protected $productUtil;
    
    public function __construct(BusinessUtil $businessUtil, ProductUtil $productUtil)
    {
        $this->businessUtil = $businessUtil;
        $this->productUtil = $productUtil;
    }
    
    // ============================================
    // 📋 1. الصفحة الرئيسية وعرض العروض
    // ============================================
    
    public function index()
    {
        if (!auth()->user()->can('product.create')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = request()->session()->get('user.business_id');
        $business_locations = BusinessLocation::forDropdown($business_id);

        return view('product_offers.index')->with(compact('business_locations'));
    }
    
    public function getOffersData()
    {
        if (!auth()->user()->can('product.view')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = request()->session()->get('user.business_id');

        $offers = ProductOffer::where('product_offers.business_id', $business_id)
            ->leftJoin('variations as v', function($join) {
                $join->on('product_offers.variation_id', '=', 'v.id');
            })
            ->leftJoin('products as p', function($join) {
                $join->on('v.product_id', '=', 'p.id');
            })
            ->leftJoin('business_locations as bl', function($join) {
                $join->on('product_offers.location_id', '=', 'bl.id');
            })
            ->select(
                'product_offers.*',
                'p.name as product_name',
                'v.name as variation_name',
                'v.sub_sku',
                'bl.name as location_name'
            );

        return DataTables::of($offers)
            ->addColumn('action', function ($row) {
                return '<div class="ego-act-wrap">'
                    . '<button class="btn ego-act-btn btn-info inspect-offer-btn" data-id="'.$row->id.'"><i class="fa fa-search"></i> فحص</button>'
                    . '<button class="btn ego-act-btn btn-primary edit-btn" data-id="'.$row->id.'"><i class="fa fa-edit"></i> تعديل</button>'
                    . '<button class="btn ego-act-btn btn-danger delete-btn" data-href="'.action([\App\Http\Controllers\ProductOfferController::class, 'destroy'], [$row->id]).'"><i class="fa fa-trash"></i> حذف</button>'
                    . '</div>';
            })
            ->editColumn('product', function ($row) {
                $name = $row->product_name;
                if (!empty($row->variation_name) && $row->variation_name != 'DUMMY') {
                    $name .= ' - ' . $row->variation_name;
                }
                return $name . '<br><small class="text-muted">' . $row->sub_sku . '</small>';
            })
            ->editColumn('min_quantity', function ($row) {
                return $this->businessUtil->num_f($row->min_quantity);
            })
            ->editColumn('offer_price', function ($row) {
                return '<span class="display_currency">' . 
                       $this->businessUtil->num_f($row->offer_price) . '</span>';
            })
            ->editColumn('price_type', function ($row) {
                // 🆕 تسميات عربية واضحة
                $types = [
                    'fixed'      => 'سعر ثابت للقطعة',
                    'percentage' => 'نسبة خصم %',
                    'override'   => 'سعر إجمالي للكمية',
                ];
                return $types[$row->price_type] ?? $row->price_type;
            })
            ->editColumn('start_date', function ($row) {
                return !empty($row->start_date)
                    ? \Carbon\Carbon::parse($row->start_date)->timezone('Asia/Amman')->format('Y-m-d')
                    : '';
            })
            ->editColumn('end_date', function ($row) {
                return !empty($row->end_date)
                    ? \Carbon\Carbon::parse($row->end_date)->timezone('Asia/Amman')->format('Y-m-d')
                    : '';
            })
            ->editColumn('is_active', function ($row) {
                $expired = !empty($row->end_date) && \Carbon\Carbon::parse($row->end_date)->endOfDay()->isPast();
                $notStarted = !empty($row->start_date) && \Carbon\Carbon::parse($row->start_date)->startOfDay()->isFuture();
                if ($expired) {
                    return '<span class="label label-danger">منتهٍ</span>';
                } elseif ($notStarted) {
                    return '<span class="label label-warning">لم يبدأ</span>';
                } elseif ($row->is_active) {
                    return '<span class="label label-success">فعّال</span>';
                }
                return '<span class="label label-danger">غير فعّال</span>';
            })
            ->rawColumns(['action', 'product', 'offer_price', 'is_active'])
            ->make(true);
    }
    
    // ============================================
    // ✏️ 2. إدارة العروض (CRUD)
    // ============================================
    
    public function store(Request $request)
    {
        if (!auth()->user()->can('product.create')) {
            abort(403, 'Unauthorized action.');
        }

        try {
            $business_id = request()->session()->get('user.business_id');
            
            $validator = Validator::make($request->all(), [
                'variation_id' => 'required',
                'min_quantity' => 'required|numeric|min:0.001',
                'offer_price' => 'required|numeric|min:0',
                'price_type' => 'required|in:fixed,percentage,override',
                'location_id' => 'required',
                'start_date' => 'nullable|date',
                'end_date' => 'nullable|date|after_or_equal:start_date'
            ]);
            
            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'msg' => implode('<br>', $validator->errors()->all())
                ]);
            }
            
            // التحقق من أن variation تتبع للـ business
            $variation = Variation::where('id', $request->variation_id)
                ->whereHas('product', function($q) use ($business_id) {
                    $q->where('business_id', $business_id);
                })
                ->first();
            
            if (!$variation) {
                return response()->json([
                    'success' => false,
                    'msg' => __('lang_v1.variation_not_found')
                ]);
            }
            
            // البحث عن عرض موجود لنفس variation والكمية والموقع
            $existingOffer = ProductOffer::where('business_id', $business_id)
                ->where('variation_id', $request->variation_id)
                ->where('min_quantity', $request->min_quantity)
                ->where('location_id', $request->location_id)
                ->first();
            
            if ($existingOffer) {
                // تحديث العرض الموجود
                $existingOffer->update([
                    'offer_price' => $request->offer_price,
                    'price_type' => $request->price_type,
                    'start_date' => $request->start_date,
                    'end_date' => $request->end_date,
                    'is_active' => $request->is_active ?? 1
                ]);
                
                $msg = __('lang_v1.offer_updated_successfully');
                $offer = $existingOffer;
            } else {
                // إنشاء عرض جديد
                $offer = ProductOffer::create([
                    'business_id' => $business_id,
                    'variation_id' => $request->variation_id,
                    'min_quantity' => $request->min_quantity,
                    'offer_price' => $request->offer_price,
                    'price_type' => $request->price_type,
                    'location_id' => $request->location_id,
                    'start_date' => $request->start_date,
                    'end_date' => $request->end_date,
                    'is_active' => $request->is_active ?? 1
                ]);
                
                $msg = __('lang_v1.offer_added_successfully');
            }
            
            return response()->json([
                'success' => true,
                'msg' => $msg,
                'offer' => $offer
            ]);
            
        } catch (\Exception $e) {
            \Log::emergency("File:" . $e->getFile() . "Line:" . $e->getLine() . "Message:" . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'msg' => __('messages.something_went_wrong')
            ]);
        }
    }
    
    public function edit($id)
    {
        if (!auth()->user()->can('product.update')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = request()->session()->get('user.business_id');
        
        $offer = ProductOffer::where('business_id', $business_id)
            ->with(['variation.product'])
            ->find($id);
        
        if (!$offer) {
            return response()->json([
                'success' => false,
                'msg' => __('lang_v1.offer_not_found')
            ]);
        }
        
        // جلب معلومات المنتج
        $product_info = [
            'product_name' => $offer->variation->product->name,
            'variation_name' => $offer->variation->name,
            'sub_sku' => $offer->variation->sub_sku
        ];
        
        return response()->json([
            'success' => true,
            'offer' => $offer,
            'product_info' => $product_info
        ]);
    }
    
    public function update(Request $request, $id)
    {
        if (!auth()->user()->can('product.update')) {
            abort(403, 'Unauthorized action.');
        }

        try {
            $business_id = request()->session()->get('user.business_id');
            
            $validator = Validator::make($request->all(), [
                'min_quantity' => 'required|numeric|min:0.001',
                'offer_price' => 'required|numeric|min:0',
                'price_type' => 'required|in:fixed,percentage,override',
                'start_date' => 'nullable|date',
                'end_date' => 'nullable|date|after_or_equal:start_date'
            ]);
            
            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'msg' => implode('<br>', $validator->errors()->all())
                ]);
            }
            
            $offer = ProductOffer::where('business_id', $business_id)
                ->find($id);
            
            if (!$offer) {
                return response()->json([
                    'success' => false,
                    'msg' => __('lang_v1.offer_not_found')
                ]);
            }
            
            // التحقق من عدم وجود عرض آخر لنفس المنتج والكمية (إلا إذا كان نفسه)
            $duplicate = ProductOffer::where('business_id', $business_id)
                ->where('id', '!=', $id)
                ->where('variation_id', $offer->variation_id)
                ->where('min_quantity', $request->min_quantity)
                ->where('location_id', $offer->location_id)
                ->first();
            
            if ($duplicate) {
                return response()->json([
                    'success' => false,
                    'msg' => __('lang_v1.offer_already_exists_for_quantity')
                ]);
            }
            
            $offer->update([
                'min_quantity' => $request->min_quantity,
                'offer_price' => $request->offer_price,
                'price_type' => $request->price_type,
                'start_date' => $request->start_date,
                'end_date' => $request->end_date,
                'is_active' => $request->is_active ?? $offer->is_active
            ]);
            
            return response()->json([
                'success' => true,
                'msg' => __('lang_v1.offer_updated_successfully')
            ]);
            
        } catch (\Exception $e) {
            \Log::emergency("File:" . $e->getFile() . "Line:" . $e->getLine() . "Message:" . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'msg' => __('messages.something_went_wrong')
            ]);
        }
    }
    
    public function destroy($id)
    {
        if (!auth()->user()->can('product.delete')) {
            abort(403, 'Unauthorized action.');
        }

        try {
            $business_id = request()->session()->get('user.business_id');
            
            $offer = ProductOffer::where('business_id', $business_id)
                ->find($id);
            
            if ($offer) {
                $offer->delete();
                
                $output = [
                    'success' => true,
                    'msg' => __('lang_v1.offer_deleted_successfully')
                ];
            } else {
                $output = [
                    'success' => false,
                    'msg' => __('lang_v1.offer_not_found')
                ];
            }
            
        } catch (\Exception $e) {
            \Log::emergency("File:" . $e->getFile() . "Line:" . $e->getLine() . "Message:" . $e->getMessage());
            
            $output = [
                'success' => false,
                'msg' => __('messages.something_went_wrong')
            ];
        }
        
        return $output;
    }
    
    // ============================================
    // 📤 3. قسم الاستيراد من Excel
    // ============================================
    
    public function importExcel(Request $request)
    {
        if (!auth()->user()->can('product.create')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = request()->session()->get('user.business_id');

        $validator = Validator::make($request->all(), [
            'excel_file' => 'required|mimes:xlsx,xls,csv|max:5120',
            'location_id' => 'required',
            'import_mode' => 'required|in:add,replace'
        ]);

        if ($validator->fails()) {
            return back()
                ->withErrors($validator)
                ->withInput();
        }

        try {
            DB::beginTransaction();
            
            // إذا كان الوضع "استبدال" - حذف العروض القديمة للموقع
            if ($request->import_mode == 'replace') {
                ProductOffer::where('business_id', $business_id)
                    ->where('location_id', $request->location_id)
                    ->delete();
            }
            
            // استيراد البيانات
            $import = new ProductOffersImport(
                $business_id, 
                $request->location_id,
                $request->import_mode
            );
            
            Excel::import($import, $request->file('excel_file'));
            
            DB::commit();
            
            // إحصائيات الاستيراد
            $stats = $import->getStats();
            
            $output = [
                'success' => true,
                'msg' => __('lang_v1.import_successful', [
                    'imported' => $stats['imported'],
                    'skipped' => $stats['skipped'],
                    'updated' => $stats['updated'],
                    'failed' => $stats['failed']
                ])
            ];
            
            return redirect()->action([\App\Http\Controllers\ProductOfferController::class, 'index'])
                ->with('status', $output);
                
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::emergency("File:" . $e->getFile() . "Line:" . $e->getLine() . "Message:" . $e->getMessage());
            
            $output = [
                'success' => false,
                'msg' => __('messages.something_went_wrong') . ': ' . $e->getMessage()
            ];
            
            return back()->with('status', $output)->withInput();
        }
    }
    
    public function downloadTemplate()
    {
        if (!auth()->user()->can('product.view')) {
            abort(403, 'Unauthorized action.');
        }

        $file_path = public_path('downloads/product_offers_template.xlsx');

        if (!file_exists($file_path)) {
            $this->createExcelTemplate($file_path); // 🆕 تمرير المسار (كان مفقوداً فيسبّب 500)
        }

        return response()->download($file_path, 'product_offers_template_' . date('Y-m-d') . '.xlsx');
    }

    private function createExcelTemplate($file_path)
    {
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        
        // العناوين
        $headers = [
            'SKU/Barcode',
            'Min Quantity',
            'Offer Price',
            'Price Type (fixed/percentage/override)',
            'Start Date (YYYY-MM-DD)',
            'End Date (YYYY-MM-DD)',
            'Active (1/0)'
        ];
        
        // كتابة العناوين
        foreach ($headers as $index => $header) {
            $sheet->setCellValue(chr(65 + $index) . '1', $header);
            $sheet->getStyle(chr(65 + $index) . '1')->getFont()->setBold(true);
        }
        
        // أمثلة للبيانات
        $examples = [
            ['PROD001', 3, 10, 'fixed', '2024-01-01', '2024-12-31', 1],
            ['PROD001', 5, 8, 'fixed', '2024-01-01', '2024-12-31', 1],
            ['PROD001', 10, 6, 'fixed', '2024-01-01', '2024-12-31', 1],
            ['PROD002', 5, 15, 'percentage', '', '', 1],
            ['PROD003', 1, 20, 'override', '2024-03-01', '2024-03-31', 1]
        ];
        
        // كتابة الأمثلة
        foreach ($examples as $rowIndex => $example) {
            foreach ($example as $colIndex => $value) {
                $sheet->setCellValue(chr(65 + $colIndex) . ($rowIndex + 2), $value);
            }
        }
        
        // تنسيق الأعمدة
        $sheet->getColumnDimension('A')->setWidth(20);
        $sheet->getColumnDimension('B')->setWidth(15);
        $sheet->getColumnDimension('C')->setWidth(15);
        $sheet->getColumnDimension('D')->setWidth(25);
        $sheet->getColumnDimension('E')->setWidth(20);
        $sheet->getColumnDimension('F')->setWidth(20);
        $sheet->getColumnDimension('G')->setWidth(15);
        
        // حفظ الملف
        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        
        if (!file_exists(public_path('downloads'))) {
            mkdir(public_path('downloads'), 0755, true);
        }
        
        $writer->save($file_path);
    }
    
    // ============================================
    // 🔍 4. خدمات البحث والمساعدة
    // ============================================
    
    public function searchProducts(Request $request)
    {
        $business_id = request()->session()->get('user.business_id');
        $search_term = $request->get('term', '');
        
        $products = Variation::join('products as p', 'variations.product_id', '=', 'p.id')
            ->leftJoin('units as u', 'p.unit_id', '=', 'u.id')
            ->where('p.business_id', $business_id)
            ->where(function($query) use ($search_term) {
                $query->where('p.name', 'like', '%' . $search_term . '%')
                      ->orWhere('variations.sub_sku', 'like', '%' . $search_term . '%')
                      ->orWhere('p.sku', 'like', '%' . $search_term . '%');
            })
            ->select(
                'variations.id',
                DB::raw('CONCAT(p.name, " - ", variations.name, " (", variations.sub_sku, ")") as text'),
                'variations.sub_sku',
                'p.name as product_name',
                'variations.name as variation_name',
                'u.short_name as unit'
            )
            ->limit(20)
            ->get();
        
        return response()->json($products);
    }
    
    public function getProductOffers(Request $request)
    {
        $business_id = request()->session()->get('user.business_id');
        $variation_id = $request->get('variation_id');
        $location_id = $request->get('location_id');
        
        $offers = ProductOffer::where('business_id', $business_id)
            ->where('variation_id', $variation_id)
            ->where('location_id', $location_id)
            ->where('is_active', 1)
            ->orderBy('min_quantity', 'asc')
            ->get();
        
        return response()->json([
            'success' => true,
            'offers' => $offers
        ]);
    }
    
    public function getActiveOffersForLocation(Request $request)
    {
        $business_id = request()->session()->get('user.business_id');
        $location_id = $request->get('location_id');
        
        $offers = ProductOffer::where('product_offers.business_id', $business_id)
            ->where('product_offers.location_id', $location_id)
            ->where('product_offers.is_active', 1)
            ->where(function($query) {
                $query->whereNull('start_date')
                      ->orWhere('start_date', '<=', date('Y-m-d'));
            })
            ->where(function($query) {
                $query->whereNull('end_date')
                      ->orWhere('end_date', '>=', date('Y-m-d'));
            })
            ->join('variations as v', 'product_offers.variation_id', '=', 'v.id')
            ->join('products as p', 'v.product_id', '=', 'p.id')
            ->select(
                'product_offers.*',
                'p.name as product_name',
                'v.name as variation_name',
                'v.sub_sku'
            )
            ->orderBy('p.name')
            ->orderBy('product_offers.min_quantity')
            ->get();
        
        return response()->json([
            'success' => true,
            'offers' => $offers
        ]);
    }
    
    // ============================================
    // 🎯 5. التكامل مع شاشة البيع (POS)
    // ============================================
    
    public function getOfferPrice(Request $request)
    {
        $business_id = request()->session()->get('user.business_id');
        $variation_id = $request->get('variation_id');
        $quantity = $request->get('quantity', 1);
        $location_id = $request->get('location_id');
        
        if (!$variation_id || !$location_id) {
            return response()->json([
                'success' => false,
                'msg' => 'Missing parameters'
            ]);
        }
        
        // البحث عن العرض المناسب للكمية
        $offer = ProductOffer::where('business_id', $business_id)
            ->where('variation_id', $variation_id)
            ->where('location_id', $location_id)
            ->where('min_quantity', '<=', $quantity)
            ->where('is_active', 1)
            ->where(function($query) {
                $query->whereNull('start_date')
                      ->orWhere('start_date', '<=', date('Y-m-d'));
            })
            ->where(function($query) {
                $query->whereNull('end_date')
                      ->orWhere('end_date', '>=', date('Y-m-d'));
            })
            ->orderBy('min_quantity', 'desc')
            ->first();
        
        if ($offer) {
            return response()->json([
                'success' => true,
                'has_offer' => true,
                'offer' => $offer,
                'quantity' => $quantity
            ]);
        }
        
        return response()->json([
            'success' => true,
            'has_offer' => false
        ]);
    }

    // ============================================
    // 📦 6. مجموعة عروض (حزم) - Bundles
    // ============================================

    public function getBundlesData()
    {
        if (!auth()->user()->can('product.view')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = request()->session()->get('user.business_id');

        $bundles = ProductOfferBundle::where('product_offer_bundles.business_id', $business_id)
            ->leftJoin('business_locations as bl', 'product_offer_bundles.location_id', '=', 'bl.id')
            ->with(['items.variation.product'])
            ->select('product_offer_bundles.*', 'bl.name as location_name');

        return DataTables::of($bundles)
            ->addColumn('products', function ($row) {
                $parts = [];
                foreach ($row->items as $item) {
                    if (empty($item->variation)) { continue; }
                    $pname = optional($item->variation->product)->name;
                    $vname = $item->variation->name;
                    $label = $pname;
                    if (!empty($vname) && $vname != 'DUMMY') {
                        $label .= ' - ' . $vname;
                    }
                    $parts[] = '<span class="label label-default" style="display:inline-block;margin:2px;font-size:12px;">'
                        . e($label) . ' × ' . $this->businessUtil->num_f($item->quantity) . '</span>';
                }
                return implode(' ', $parts);
            })
            ->editColumn('bundle_price', function ($row) {
                return '<span class="display_currency">' . $this->businessUtil->num_f($row->bundle_price) . '</span>';
            })
            ->editColumn('location_name', function ($row) {
                return $row->location_name ?: 'كل الفروع';
            })
            ->editColumn('is_active', function ($row) {
                $expired = !empty($row->end_date) && \Carbon\Carbon::parse($row->end_date)->endOfDay()->isPast();
                $notStarted = !empty($row->start_date) && \Carbon\Carbon::parse($row->start_date)->startOfDay()->isFuture();
                if ($expired) {
                    return '<span class="label label-danger">منتهٍ</span>';
                } elseif ($notStarted) {
                    return '<span class="label label-warning">لم يبدأ</span>';
                } elseif ($row->is_active) {
                    return '<span class="label label-success">فعّال</span>';
                }
                return '<span class="label label-danger">غير فعّال</span>';
            })
            ->addColumn('action', function ($row) {
                return '<div class="ego-act-wrap">'
                    . '<button class="btn ego-act-btn btn-info inspect-bundle-btn" data-id="'.$row->id.'"><i class="fa fa-search"></i> فحص</button>'
                    . '<button class="btn ego-act-btn btn-primary edit-bundle-btn" data-id="'.$row->id.'"><i class="fa fa-edit"></i> تعديل</button>'
                    . '<button class="btn ego-act-btn btn-danger delete-bundle-btn" data-id="'.$row->id.'"><i class="fa fa-trash"></i> حذف</button>'
                    . '</div>';
            })
            ->rawColumns(['products', 'bundle_price', 'location_name', 'is_active', 'action'])
            ->make(true);
    }

    public function editBundle($id)
    {
        if (!auth()->user()->can('product.update')) {
            abort(403, 'Unauthorized action.');
        }
        $business_id = request()->session()->get('user.business_id');
        $bundle = ProductOfferBundle::where('business_id', $business_id)
            ->with(['items.variation.product'])
            ->find($id);
        if (!$bundle) {
            return response()->json(['success' => false, 'msg' => 'الحزمة غير موجودة']);
        }
        $items = [];
        foreach ($bundle->items as $it) {
            $pname = optional(optional($it->variation)->product)->name;
            $vname = optional($it->variation)->name;
            $sku   = optional($it->variation)->sub_sku;
            $label = $pname . (!empty($vname) && $vname != 'DUMMY' ? ' - ' . $vname : '') . ($sku ? ' (' . $sku . ')' : '');
            $items[] = [
                'variation_id' => $it->variation_id,
                'quantity'     => $it->quantity,
                'label'        => $label,
            ];
        }
        return response()->json(['success' => true, 'bundle' => $bundle, 'items' => $items]);
    }

    public function updateBundle(Request $request, $id)
    {
        if (!auth()->user()->can('product.update')) {
            abort(403, 'Unauthorized action.');
        }
        try {
            $business_id = request()->session()->get('user.business_id');
            $bundle = ProductOfferBundle::where('business_id', $business_id)->find($id);
            if (!$bundle) {
                return response()->json(['success' => false, 'msg' => 'الحزمة غير موجودة']);
            }

            $validator = Validator::make($request->all(), [
                'bundle_price' => 'required|numeric|min:0',
                'items'        => 'required|array|min:2',
                'items.*.variation_id' => 'required',
                'items.*.quantity'     => 'required|numeric|min:0.001',
                'start_date'   => 'nullable|date',
                'end_date'     => 'nullable|date|after_or_equal:start_date',
            ], [], ['items' => 'المنتجات']);
            if ($validator->fails()) {
                return response()->json(['success' => false, 'msg' => implode('<br>', $validator->errors()->all())]);
            }

            DB::beginTransaction();
            $bundle->update([
                'location_id' => $request->filled('location_id') ? $request->location_id : null,
                'name'        => $request->name,
                'bundle_price'=> $request->bundle_price,
                'start_date'  => $request->start_date,
                'end_date'    => $request->end_date,
                'is_active'   => $request->is_active ?? 1,
            ]);
            // أعد بناء عناصر الحزمة
            $bundle->items()->delete();
            foreach ($request->items as $item) {
                $variation = Variation::where('id', $item['variation_id'])
                    ->whereHas('product', function ($q) use ($business_id) { $q->where('business_id', $business_id); })->first();
                if (!$variation) { continue; }
                ProductOfferBundleItem::create([
                    'bundle_id'    => $bundle->id,
                    'variation_id' => $item['variation_id'],
                    'quantity'     => $item['quantity'],
                ]);
            }
            if ($bundle->items()->count() < 2) {
                DB::rollBack();
                return response()->json(['success' => false, 'msg' => 'يجب اختيار منتجين مختلفين على الأقل للحزمة']);
            }
            DB::commit();
            return response()->json(['success' => true, 'msg' => 'تم تحديث مجموعة العروض بنجاح']);
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::emergency("File:" . $e->getFile() . "Line:" . $e->getLine() . "Message:" . $e->getMessage());
            return response()->json(['success' => false, 'msg' => __('messages.something_went_wrong')]);
        }
    }

    public function storeBundle(Request $request)
    {
        if (!auth()->user()->can('product.create')) {
            abort(403, 'Unauthorized action.');
        }

        try {
            $business_id = request()->session()->get('user.business_id');

            $validator = Validator::make($request->all(), [
                'bundle_price' => 'required|numeric|min:0',
                'items'        => 'required|array|min:2',
                'items.*.variation_id' => 'required',
                'items.*.quantity'     => 'required|numeric|min:0.001',
                'start_date'   => 'nullable|date',
                'end_date'     => 'nullable|date|after_or_equal:start_date',
            ], [], [
                'items' => 'المنتجات',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'msg' => implode('<br>', $validator->errors()->all())
                ]);
            }

            DB::beginTransaction();

            $bundle = ProductOfferBundle::create([
                'business_id' => $business_id,
                'location_id' => $request->filled('location_id') ? $request->location_id : null,
                'name'        => $request->name,
                'bundle_price'=> $request->bundle_price,
                'start_date'  => $request->start_date,
                'end_date'    => $request->end_date,
                'is_active'   => $request->is_active ?? 1,
                'notes'       => $request->notes,
                'created_by'  => auth()->id(),
            ]);

            foreach ($request->items as $item) {
                // التحقق أن المنتج يتبع للنشاط
                $variation = Variation::where('id', $item['variation_id'])
                    ->whereHas('product', function ($q) use ($business_id) {
                        $q->where('business_id', $business_id);
                    })->first();
                if (!$variation) { continue; }

                ProductOfferBundleItem::create([
                    'bundle_id'    => $bundle->id,
                    'variation_id' => $item['variation_id'],
                    'quantity'     => $item['quantity'],
                ]);
            }

            if ($bundle->items()->count() < 2) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'msg' => 'يجب اختيار منتجين مختلفين على الأقل للحزمة'
                ]);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'msg' => 'تمت إضافة مجموعة العروض بنجاح'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::emergency("File:" . $e->getFile() . "Line:" . $e->getLine() . "Message:" . $e->getMessage());
            return response()->json([
                'success' => false,
                'msg' => __('messages.something_went_wrong')
            ]);
        }
    }

    public function destroyBundle($id)
    {
        if (!auth()->user()->can('product.delete')) {
            abort(403, 'Unauthorized action.');
        }

        try {
            $business_id = request()->session()->get('user.business_id');
            $bundle = ProductOfferBundle::where('business_id', $business_id)->find($id);

            if ($bundle) {
                $bundle->items()->delete();
                $bundle->delete();
                return ['success' => true, 'msg' => 'تم حذف مجموعة العروض'];
            }
            return ['success' => false, 'msg' => __('messages.something_went_wrong')];
        } catch (\Exception $e) {
            \Log::emergency("File:" . $e->getFile() . "Line:" . $e->getLine() . "Message:" . $e->getMessage());
            return ['success' => false, 'msg' => __('messages.something_went_wrong')];
        }
    }

    // ============================================
    // ▮ 7. الباركود البديل - Alternative barcodes
    // ============================================

    public function getAltBarcodesData()
    {
        if (!auth()->user()->can('product.view')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = request()->session()->get('user.business_id');

        // 🆕 تجميع حسب المنتج: صف واحد لكل منتج مع عدد باركوداته (لا تُفرَّق الباركودات في الصفحة)
        $rows = ProductAltBarcode::where('product_alt_barcodes.business_id', $business_id)
            ->whereIn('product_alt_barcodes.id', function ($sub) use ($business_id) {
                $sub->from('product_alt_barcodes')->where('business_id', $business_id)
                    ->selectRaw('MIN(id)')->groupBy('variation_id');
            })
            ->leftJoin('variations as v', 'product_alt_barcodes.variation_id', '=', 'v.id')
            ->leftJoin('products as p', 'v.product_id', '=', 'p.id')
            ->select(
                'product_alt_barcodes.id',
                'product_alt_barcodes.variation_id',
                'p.name as product_name',
                'v.name as variation_name',
                'v.sub_sku',
                DB::raw('(SELECT COUNT(*) FROM product_alt_barcodes pab WHERE pab.variation_id = product_alt_barcodes.variation_id AND pab.business_id = ' . ((int) $business_id) . ') as codes_count')
            );

        return DataTables::of($rows)
            ->editColumn('product', function ($row) {
                $name = $row->product_name;
                if (!empty($row->variation_name) && $row->variation_name != 'DUMMY') {
                    $name .= ' - ' . $row->variation_name;
                }
                return e($name) . '<br><small class="text-muted">' . e($row->sub_sku) . '</small>';
            })
            ->addColumn('codes_count', function ($row) {
                return '<span class="label label-info" style="font-size:13px;">' . (int) $row->codes_count . ' باركود</span>';
            })
            ->addColumn('action', function ($row) {
                return '<div class="ego-act-wrap">'
                    . '<button class="btn ego-act-btn btn-info inspect-alt-btn" data-vid="'.$row->variation_id.'"><i class="fa fa-search"></i> فحص</button>'
                    . '<button class="btn ego-act-btn btn-danger delete-alt-group-btn" data-vid="'.$row->variation_id.'"><i class="fa fa-trash"></i> حذف الكل</button>'
                    . '</div>';
            })
            ->rawColumns(['product', 'codes_count', 'action'])
            ->make(true);
    }

    public function updateAltBarcode(Request $request, $id)
    {
        if (!auth()->user()->can('product.update')) {
            abort(403, 'Unauthorized action.');
        }
        try {
            $business_id = request()->session()->get('user.business_id');
            $row = ProductAltBarcode::where('business_id', $business_id)->find($id);
            if (!$row) {
                return response()->json(['success' => false, 'msg' => 'الباركود غير موجود']);
            }
            $code = trim($request->input('alt_barcode', ''));
            if ($code === '') {
                return response()->json(['success' => false, 'msg' => 'أدخل الباركود']);
            }
            // تأكد أن الباركود غير مستخدم لسطر آخر
            $exists = ProductAltBarcode::where('business_id', $business_id)
                ->where('alt_barcode', $code)->where('id', '!=', $id)->exists();
            if ($exists) {
                return response()->json(['success' => false, 'msg' => 'هذا الباركود مستخدم مسبقاً']);
            }
            $row->update(['alt_barcode' => $code]);
            return response()->json(['success' => true, 'msg' => 'تم تحديث الباركود البديل']);
        } catch (\Exception $e) {
            \Log::emergency("File:" . $e->getFile() . "Line:" . $e->getLine() . "Message:" . $e->getMessage());
            return response()->json(['success' => false, 'msg' => __('messages.something_went_wrong')]);
        }
    }

    public function storeAltBarcode(Request $request)
    {
        if (!auth()->user()->can('product.create')) {
            abort(403, 'Unauthorized action.');
        }

        try {
            $business_id = request()->session()->get('user.business_id');

            $validator = Validator::make($request->all(), [
                'variation_id' => 'required',
                'barcodes'     => 'required|array|min:1',
                'barcodes.*'   => 'required|string|max:191',
            ], [], [
                'barcodes' => 'الباركود',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'msg' => implode('<br>', $validator->errors()->all())
                ]);
            }

            $variation = Variation::where('id', $request->variation_id)
                ->whereHas('product', function ($q) use ($business_id) {
                    $q->where('business_id', $business_id);
                })->first();

            if (!$variation) {
                return response()->json(['success' => false, 'msg' => __('lang_v1.variation_not_found')]);
            }

            $added = 0; $skipped = 0;
            foreach ($request->barcodes as $code) {
                $code = trim($code);
                if ($code === '') { continue; }

                // تخطّي إن كان مستخدماً مسبقاً (بديل أو الـ SKU الأصلي)
                $exists = ProductAltBarcode::where('business_id', $business_id)
                    ->where('alt_barcode', $code)->exists();
                if ($exists) { $skipped++; continue; }

                ProductAltBarcode::create([
                    'business_id'  => $business_id,
                    'variation_id' => $request->variation_id,
                    'alt_barcode'  => $code,
                    'created_by'   => auth()->id(),
                ]);
                $added++;
            }

            return response()->json([
                'success' => true,
                'msg' => 'تم حفظ الباركود البديل (أُضيف: ' . $added . '، تم تخطّي مكرر: ' . $skipped . ')'
            ]);

        } catch (\Exception $e) {
            \Log::emergency("File:" . $e->getFile() . "Line:" . $e->getLine() . "Message:" . $e->getMessage());
            return response()->json([
                'success' => false,
                'msg' => __('messages.something_went_wrong')
            ]);
        }
    }

    public function destroyAltBarcode($id)
    {
        if (!auth()->user()->can('product.delete')) {
            abort(403, 'Unauthorized action.');
        }

        try {
            $business_id = request()->session()->get('user.business_id');
            $row = ProductAltBarcode::where('business_id', $business_id)->find($id);

            if ($row) {
                $row->delete();
                return ['success' => true, 'msg' => 'تم حذف الباركود البديل'];
            }
            return ['success' => false, 'msg' => __('messages.something_went_wrong')];
        } catch (\Exception $e) {
            \Log::emergency("File:" . $e->getFile() . "Line:" . $e->getLine() . "Message:" . $e->getMessage());
            return ['success' => false, 'msg' => __('messages.something_went_wrong')];
        }
    }

    // ============================================
    // 🌟 العروض الخاصة (BOGO / اشتري N والتالية % / خصم % على أصناف)
    // ============================================

    private function specialTypeLabel($type)
    {
        $map = [
            'bogo'          => 'اشتري واحصل مجاناً',
            'nth_percent'   => 'القطعة التالية بخصم %',
            'percent_items' => 'خصم % على أصناف',
        ];
        return $map[$type] ?? $type;
    }

    public function getSpecialOffersData()
    {
        if (!auth()->user()->can('product.view')) {
            abort(403, 'Unauthorized action.');
        }
        $business_id = request()->session()->get('user.business_id');

        $offers = ProductSpecialOffer::where('product_special_offers.business_id', $business_id)
            ->leftJoin('business_locations as bl', 'product_special_offers.location_id', '=', 'bl.id')
            ->with(['items.variation.product'])
            ->select('product_special_offers.*', 'bl.name as location_name');

        return DataTables::of($offers)
            ->editColumn('offer_type', function ($row) {
                return '<span class="label label-primary">' . $this->specialTypeLabel($row->offer_type) . '</span>';
            })
            ->addColumn('details', function ($row) {
                if ($row->offer_type == 'bogo') {
                    return 'اشترِ ' . $this->businessUtil->num_f($row->buy_qty) . ' واحصل على ' . $this->businessUtil->num_f($row->free_qty) . ' مجاناً';
                } elseif ($row->offer_type == 'nth_percent') {
                    return 'اشترِ ' . $this->businessUtil->num_f($row->buy_qty) . ' والـ ' . $this->businessUtil->num_f($row->free_qty) . ' التالية بخصم ' . rtrim(rtrim($row->percent, '0'), '.') . '%';
                }
                return 'خصم ' . rtrim(rtrim($row->percent, '0'), '.') . '% على الأصناف المحددة';
            })
            ->addColumn('products', function ($row) {
                $parts = [];
                foreach ($row->items as $item) {
                    if (empty($item->variation)) { continue; }
                    $pname = optional($item->variation->product)->name;
                    $vname = $item->variation->name;
                    $label = $pname . (!empty($vname) && $vname != 'DUMMY' ? ' - ' . $vname : '');
                    $parts[] = '<span class="label label-default" style="display:inline-block;margin:2px;font-size:12px;">' . e($label) . '</span>';
                }
                return implode(' ', $parts) ?: '<span class="text-muted">—</span>';
            })
            ->editColumn('location_name', function ($row) {
                return $row->location_name ?: 'كل الفروع';
            })
            ->editColumn('is_active', function ($row) {
                $expired = !empty($row->end_date) && \Carbon\Carbon::parse($row->end_date)->endOfDay()->isPast();
                $notStarted = !empty($row->start_date) && \Carbon\Carbon::parse($row->start_date)->startOfDay()->isFuture();
                if ($expired) {
                    return '<span class="label label-danger">منتهٍ</span>';
                } elseif ($notStarted) {
                    return '<span class="label label-warning">لم يبدأ</span>';
                } elseif ($row->is_active) {
                    return '<span class="label label-success">فعّال</span>';
                }
                return '<span class="label label-danger">غير فعّال</span>';
            })
            ->addColumn('action', function ($row) {
                return '<div class="ego-act-wrap">'
                    . '<button class="btn ego-act-btn btn-info inspect-special-btn" data-id="'.$row->id.'"><i class="fa fa-search"></i> فحص</button>'
                    . '<button class="btn ego-act-btn btn-primary edit-special-btn" data-id="'.$row->id.'"><i class="fa fa-edit"></i> تعديل</button>'
                    . '<button class="btn ego-act-btn btn-danger delete-special-btn" data-id="'.$row->id.'"><i class="fa fa-trash"></i> حذف</button>'
                    . '</div>';
            })
            ->rawColumns(['offer_type', 'products', 'location_name', 'is_active', 'action'])
            ->make(true);
    }

    public function storeSpecialOffer(Request $request)
    {
        if (!auth()->user()->can('product.create')) {
            abort(403, 'Unauthorized action.');
        }
        try {
            $business_id = request()->session()->get('user.business_id');

            $validator = Validator::make($request->all(), [
                'name'        => 'required|string|max:191',
                'offer_type'  => 'required|in:bogo,nth_percent,percent_items',
                'items'       => 'required|array|min:1',
                'items.*'     => 'required',
                'start_date'  => 'nullable|date',
                'end_date'    => 'nullable|date|after_or_equal:start_date',
            ], [], ['items' => 'الأصناف', 'name' => 'اسم العرض']);

            if ($validator->fails()) {
                return response()->json(['success' => false, 'msg' => implode('<br>', $validator->errors()->all())]);
            }

            // تحقّق منطقي حسب النوع
            $type = $request->offer_type;
            if ($type == 'percent_items' && !($request->percent > 0)) {
                return response()->json(['success' => false, 'msg' => 'أدخل نسبة الخصم']);
            }
            if ($type == 'nth_percent' && (!($request->buy_qty > 0) || !($request->percent > 0))) {
                return response()->json(['success' => false, 'msg' => 'أدخل عدد الشراء ونسبة الخصم']);
            }
            if ($type == 'bogo' && (!($request->buy_qty > 0) || !($request->free_qty > 0))) {
                return response()->json(['success' => false, 'msg' => 'أدخل عدد الشراء وعدد المجاني']);
            }

            DB::beginTransaction();
            $offer = ProductSpecialOffer::create([
                'business_id' => $business_id,
                'location_id' => $request->filled('location_id') ? $request->location_id : null,
                'name'        => $request->name,
                'offer_type'  => $type,
                'buy_qty'     => $request->buy_qty ?: 1,
                'free_qty'    => $request->free_qty ?: 1,
                'percent'     => $request->percent ?: 0,
                'start_date'  => $request->start_date,
                'end_date'    => $request->end_date,
                'is_active'   => $request->is_active ?? 1,
                'created_by'  => auth()->id(),
            ]);

            foreach ((array) $request->items as $vid) {
                $variation = Variation::where('id', $vid)
                    ->whereHas('product', function ($q) use ($business_id) { $q->where('business_id', $business_id); })->first();
                if (!$variation) { continue; }
                ProductSpecialOfferItem::create(['special_offer_id' => $offer->id, 'variation_id' => $vid]);
            }

            if ($offer->items()->count() < 1) {
                DB::rollBack();
                return response()->json(['success' => false, 'msg' => 'اختر صنفاً واحداً على الأقل']);
            }

            DB::commit();
            return response()->json(['success' => true, 'msg' => 'تمت إضافة العرض الخاص بنجاح']);
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::emergency("File:" . $e->getFile() . "Line:" . $e->getLine() . "Message:" . $e->getMessage());
            return response()->json(['success' => false, 'msg' => __('messages.something_went_wrong')]);
        }
    }

    // 🆕 تحويل قيمة تاريخ من Excel (نص YYYY-MM-DD أو رقم تسلسلي) إلى Y-m-d أو null
    private function egoParseDate($val)
    {
        if ($val === null || $val === '') { return null; }
        if (is_numeric($val)) {
            try { return \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($val)->format('Y-m-d'); }
            catch (\Exception $e) { return null; }
        }
        try { return \Carbon\Carbon::parse(trim((string) $val))->format('Y-m-d'); }
        catch (\Exception $e) { return null; }
    }

    // 🆕 تحويل قيمة "فعّال" (1/0/yes/no/نعم/لا) إلى 1/0
    private function egoParseActive($val)
    {
        $v = strtolower(trim((string) $val));
        if ($v === '' ) { return 1; }
        return in_array($v, ['0', 'no', 'false', 'لا', 'غير فعال', 'غير فعّال']) ? 0 : 1;
    }

    // 🆕 إيجاد variation عبر sub_sku ضمن النشاط
    private function egoFindVariationBySku($sku, $business_id)
    {
        $sku = trim((string) $sku);
        if ($sku === '') { return null; }
        return Variation::where('variations.sub_sku', $sku)
            ->join('products as p', 'variations.product_id', '=', 'p.id')
            ->where('p.business_id', $business_id)
            ->select('variations.id')
            ->first();
    }

    // 🆕 فحص: تفاصيل منتجات عرض خاص (تُعرض في نافذة الفحص)
    public function getSpecialOfferItems($id)
    {
        if (!auth()->user()->can('product.view')) { abort(403, 'Unauthorized action.'); }
        $business_id = request()->session()->get('user.business_id');
        $offer = ProductSpecialOffer::where('business_id', $business_id)
            ->with(['items.variation.product'])->find($id);
        if (!$offer) { return response()->json(['success' => false, 'msg' => 'العرض غير موجود']); }

        $rows = [];
        foreach ($offer->items as $it) {
            if (empty($it->variation)) { continue; }
            $pname = optional($it->variation->product)->name;
            $vname = $it->variation->name;
            $label = $pname . (!empty($vname) && $vname != 'DUMMY' ? ' - ' . $vname : '');
            $rows[] = [$label, $it->variation->sub_sku];
        }
        $details = $offer->offer_type == 'bogo'
            ? ('اشترِ ' . $this->businessUtil->num_f($offer->buy_qty) . ' واحصل على ' . $this->businessUtil->num_f($offer->free_qty) . ' مجاناً')
            : ($offer->offer_type == 'nth_percent'
                ? ('اشترِ ' . $this->businessUtil->num_f($offer->buy_qty) . ' والتالية بخصم ' . rtrim(rtrim($offer->percent, '0'), '.') . '%')
                : ('خصم ' . rtrim(rtrim($offer->percent, '0'), '.') . '% على الأصناف'));
        $header = [
            ['اسم العرض', $offer->name],
            ['النوع', $this->specialTypeLabel($offer->offer_type)],
            ['التفاصيل', $details],
            ['الفرع', $offer->location_id ? optional(BusinessLocation::find($offer->location_id))->name : 'كل الفروع'],
        ];
        return response()->json(['success' => true, 'title' => 'تفاصيل العرض الخاص', 'header' => $header, 'columns' => ['المنتج', 'SKU/الباركود'], 'rows' => $rows]);
    }

    // 🆕 فحص: تفاصيل منتجات حزمة عروض
    public function getBundleItems($id)
    {
        if (!auth()->user()->can('product.view')) { abort(403, 'Unauthorized action.'); }
        $business_id = request()->session()->get('user.business_id');
        $bundle = ProductOfferBundle::where('business_id', $business_id)
            ->with(['items.variation.product'])->find($id);
        if (!$bundle) { return response()->json(['success' => false, 'msg' => 'الحزمة غير موجودة']); }

        $rows = [];
        foreach ($bundle->items as $it) {
            if (empty($it->variation)) { continue; }
            $pname = optional($it->variation->product)->name;
            $vname = $it->variation->name;
            $label = $pname . (!empty($vname) && $vname != 'DUMMY' ? ' - ' . $vname : '');
            $rows[] = [$label, $it->variation->sub_sku, $this->businessUtil->num_f($it->quantity)];
        }
        $header = [
            ['اسم الحزمة', $bundle->name ?: '—'],
            ['سعر الحزمة', $this->businessUtil->num_f($bundle->bundle_price)],
            ['الفرع', $bundle->location_id ? optional(BusinessLocation::find($bundle->location_id))->name : 'كل الفروع'],
        ];
        return response()->json(['success' => true, 'title' => 'تفاصيل الحزمة', 'header' => $header, 'columns' => ['المنتج', 'SKU/الباركود', 'الكمية'], 'rows' => $rows]);
    }

    // 🆕 فحص عرض كمية: يعرض المنتج وكل شرائح الكمية/السعر له في نفس الفرع
    public function getOfferItems($id)
    {
        if (!auth()->user()->can('product.view')) { abort(403, 'Unauthorized action.'); }
        $business_id = request()->session()->get('user.business_id');
        $offer = ProductOffer::where('business_id', $business_id)->find($id);
        if (!$offer) { return response()->json(['success' => false, 'msg' => 'العرض غير موجود']); }
        $variation = Variation::with('product')->find($offer->variation_id);
        $pname = optional(optional($variation)->product)->name;
        $vname = optional($variation)->name;
        $label = $pname . (!empty($vname) && $vname != 'DUMMY' ? ' - ' . $vname : '');
        $tiers = ProductOffer::where('business_id', $business_id)
            ->where('variation_id', $offer->variation_id)
            ->where(function ($q) use ($offer) {
                if ($offer->location_id) { $q->where('location_id', $offer->location_id); } else { $q->whereNull('location_id'); }
            })
            ->orderBy('min_quantity')->get();
        $typeLabels = ['fixed' => 'سعر ثابت للقطعة', 'percentage' => 'نسبة خصم %', 'override' => 'سعر إجمالي للكمية'];
        $rows = [];
        foreach ($tiers as $t) {
            $period = ($t->start_date ? \Carbon\Carbon::parse($t->start_date)->format('Y-m-d') : '—')
                . ' ← ' . ($t->end_date ? \Carbon\Carbon::parse($t->end_date)->format('Y-m-d') : '—');
            $rows[] = [$this->businessUtil->num_f($t->min_quantity), $this->businessUtil->num_f($t->offer_price), ($typeLabels[$t->price_type] ?? $t->price_type), $period];
        }
        $header = [
            ['المنتج', $label],
            ['SKU/الباركود', optional($variation)->sub_sku],
            ['الفرع', $offer->location_id ? optional(BusinessLocation::find($offer->location_id))->name : 'كل الفروع'],
        ];
        return response()->json(['success' => true, 'title' => 'تفاصيل عرض الكمية', 'header' => $header, 'columns' => ['الكمية', 'السعر', 'النوع', 'الفترة'], 'rows' => $rows]);
    }

    // 🆕 فحص باركود بديل حسب المنتج: يعرض كل باركودات المنتج مع زر حذف لكلٍّ منها
    public function getAltItems($id) // $id = variation_id (المنتج)
    {
        if (!auth()->user()->can('product.view')) { abort(403, 'Unauthorized action.'); }
        $business_id = request()->session()->get('user.business_id');
        $variation = Variation::with('product')->whereHas('product', function ($q) use ($business_id) {
            $q->where('business_id', $business_id);
        })->find($id);
        if (!$variation) { return response()->json(['success' => false, 'msg' => 'المنتج غير موجود']); }
        $pname = optional($variation->product)->name;
        $vname = $variation->name;
        $label = $pname . (!empty($vname) && $vname != 'DUMMY' ? ' - ' . $vname : '');
        $codes = ProductAltBarcode::where('business_id', $business_id)->where('variation_id', $id)->orderBy('id')->get(['id', 'alt_barcode']);
        $rows = [];
        foreach ($codes as $c) {
            $rows[] = [
                e($c->alt_barcode),
                '<button class="btn btn-xs btn-primary ego-insp-edit-alt" data-id="' . $c->id . '" data-vid="' . $id . '" data-code="' . e($c->alt_barcode) . '"><i class="fa fa-edit"></i> تعديل</button> '
                    . '<button class="btn btn-xs btn-danger ego-insp-del-alt" data-id="' . $c->id . '" data-vid="' . $id . '"><i class="fa fa-trash"></i> حذف</button>',
            ];
        }
        $header = [
            ['المنتج', $label],
            ['SKU الأصلي', $variation->sub_sku],
        ];
        return response()->json(['success' => true, 'title' => 'الباركودات البديلة للمنتج', 'header' => $header, 'columns' => ['الباركود البديل', 'إجراء'], 'rows' => $rows, 'rawLast' => true]);
    }

    // 🆕 حذف كل باركودات منتج معيّن دفعةً واحدة
    public function destroyAltGroup($vid)
    {
        if (!auth()->user()->can('product.delete')) { abort(403, 'Unauthorized action.'); }
        try {
            $business_id = request()->session()->get('user.business_id');
            $n = ProductAltBarcode::where('business_id', $business_id)->where('variation_id', $vid)->delete();
            return ['success' => true, 'msg' => 'تم حذف ' . $n . ' باركود بديل'];
        } catch (\Exception $e) {
            \Log::emergency("File:" . $e->getFile() . "Line:" . $e->getLine() . "Message:" . $e->getMessage());
            return ['success' => false, 'msg' => __('messages.something_went_wrong')];
        }
    }

    // 🆕 استيراد العروض الخاصة من Excel — كل صف = عرض خاص واحد، أصنافه SKU مفصولة بفواصل
    public function importSpecialOffers(Request $request)
    {
        if (!auth()->user()->can('product.create')) { abort(403, 'Unauthorized action.'); }
        $business_id = request()->session()->get('user.business_id');
        $validator = Validator::make($request->all(), ['excel_file' => 'required|mimes:xlsx,xls,csv|max:5120']);
        if ($validator->fails()) {
            return back()->with('status', ['success' => false, 'msg' => implode('<br>', $validator->errors()->all())]);
        }
        try {
            $location_id = $request->filled('location_id') ? $request->location_id : null;
            $rows = \PhpOffice\PhpSpreadsheet\IOFactory::load($request->file('excel_file')->getRealPath())->getActiveSheet()->toArray();
            $added = 0; $skipped = 0;
            DB::beginTransaction();
            foreach ($rows as $i => $row) {
                if ($i === 0) { continue; } // العناوين
                $name = isset($row[0]) ? trim((string) $row[0]) : '';
                $type = isset($row[1]) ? strtolower(trim((string) $row[1])) : '';
                if ($name === '' || !in_array($type, ['bogo', 'nth_percent', 'percent_items'])) { $skipped++; continue; }
                $buy     = (isset($row[2]) && $row[2] !== '') ? (float) $row[2] : 1;
                $free    = (isset($row[3]) && $row[3] !== '') ? (float) $row[3] : 1;
                $percent = (isset($row[4]) && $row[4] !== '') ? (float) $row[4] : 0;
                $skus    = isset($row[5]) ? preg_split('/[,;|\n]+/', (string) $row[5]) : [];

                $vids = [];
                foreach ((array) $skus as $sku) {
                    $v = $this->egoFindVariationBySku($sku, $business_id);
                    if ($v) { $vids[] = $v->id; }
                }
                $vids = array_values(array_unique($vids));
                if (empty($vids)) { $skipped++; continue; }

                $offer = ProductSpecialOffer::create([
                    'business_id' => $business_id,
                    'location_id' => $location_id,
                    'name'        => $name,
                    'offer_type'  => $type,
                    'buy_qty'     => $buy ?: 1,
                    'free_qty'    => $free ?: 1,
                    'percent'     => $percent ?: 0,
                    'start_date'  => $this->egoParseDate($row[6] ?? null),
                    'end_date'    => $this->egoParseDate($row[7] ?? null),
                    'is_active'   => isset($row[8]) ? $this->egoParseActive($row[8]) : 1,
                    'created_by'  => auth()->id(),
                ]);
                foreach ($vids as $vid) {
                    ProductSpecialOfferItem::create(['special_offer_id' => $offer->id, 'variation_id' => $vid]);
                }
                $added++;
            }
            DB::commit();
            return back()->with('status', ['success' => true, 'msg' => "تم الاستيراد — عروض مُضافة: $added ، متخطّاة: $skipped"]);
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::emergency("File:" . $e->getFile() . "Line:" . $e->getLine() . "Message:" . $e->getMessage());
            return back()->with('status', ['success' => false, 'msg' => __('messages.something_went_wrong') . ': ' . $e->getMessage()]);
        }
    }

    public function downloadSpecialTemplate()
    {
        if (!auth()->user()->can('product.view')) { abort(403, 'Unauthorized action.'); }
        $file_path = public_path('downloads/special_offers_template.xlsx');
        if (!file_exists(public_path('downloads'))) { mkdir(public_path('downloads'), 0755, true); }
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $headers = ['Name', 'Type (bogo/nth_percent/percent_items)', 'Buy Qty', 'Free Qty', 'Percent %', 'SKUs (comma separated)', 'Start Date (YYYY-MM-DD)', 'End Date', 'Active (1/0)'];
        foreach ($headers as $idx => $h) { $sheet->setCellValue(chr(65 + $idx) . '1', $h); $sheet->getStyle(chr(65 + $idx) . '1')->getFont()->setBold(true); }
        $examples = [
            ['عرض العيد', 'bogo', 1, 1, '', 'PROD001,PROD002', '2026-01-01', '2026-12-31', 1],
            ['خصم القمصان', 'percent_items', '', '', 20, 'SHIRT01,SHIRT02,SHIRT03', '', '', 1],
        ];
        foreach ($examples as $r => $ex) { foreach ($ex as $c => $v) { $sheet->setCellValue(chr(65 + $c) . ($r + 2), $v); } }
        foreach (range('A', 'I') as $col) { $sheet->getColumnDimension($col)->setWidth(22); }
        (new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet))->save($file_path);
        return response()->download($file_path, 'special_offers_template_' . date('Y-m-d') . '.xlsx');
    }

    // 🆕 استيراد الحزم من Excel — صفوف بنفس "اسم الحزمة" تُجمَّع في حزمة واحدة
    public function importBundles(Request $request)
    {
        if (!auth()->user()->can('product.create')) { abort(403, 'Unauthorized action.'); }
        $business_id = request()->session()->get('user.business_id');
        $validator = Validator::make($request->all(), ['excel_file' => 'required|mimes:xlsx,xls,csv|max:5120']);
        if ($validator->fails()) {
            return back()->with('status', ['success' => false, 'msg' => implode('<br>', $validator->errors()->all())]);
        }
        try {
            $location_id = $request->filled('location_id') ? $request->location_id : null;
            $rows = \PhpOffice\PhpSpreadsheet\IOFactory::load($request->file('excel_file')->getRealPath())->getActiveSheet()->toArray();

            $groups = [];
            foreach ($rows as $i => $row) {
                if ($i === 0) { continue; }
                $bname = isset($row[0]) ? trim((string) $row[0]) : '';
                $sku   = isset($row[1]) ? trim((string) $row[1]) : '';
                if ($bname === '' || $sku === '') { continue; }
                $v = $this->egoFindVariationBySku($sku, $business_id);
                if (!$v) { continue; }
                if (!isset($groups[$bname])) {
                    $groups[$bname] = ['price' => null, 'start' => null, 'end' => null, 'active' => 1, 'items' => []];
                }
                if (isset($row[3]) && $row[3] !== '') { $groups[$bname]['price'] = (float) $row[3]; }
                if (!empty($row[4])) { $groups[$bname]['start'] = $this->egoParseDate($row[4]); }
                if (!empty($row[5])) { $groups[$bname]['end'] = $this->egoParseDate($row[5]); }
                if (isset($row[6]) && trim((string) $row[6]) !== '') { $groups[$bname]['active'] = $this->egoParseActive($row[6]); }
                $groups[$bname]['items'][] = ['vid' => $v->id, 'qty' => (isset($row[2]) && $row[2] !== '') ? (float) $row[2] : 1];
            }

            $added = 0; $skipped = 0;
            DB::beginTransaction();
            foreach ($groups as $bname => $g) {
                if (count($g['items']) < 2 || !($g['price'] > 0)) { $skipped++; continue; }
                $bundle = ProductOfferBundle::create([
                    'business_id' => $business_id,
                    'location_id' => $location_id,
                    'name'        => $bname,
                    'bundle_price'=> $g['price'],
                    'start_date'  => $g['start'],
                    'end_date'    => $g['end'],
                    'is_active'   => $g['active'],
                    'created_by'  => auth()->id(),
                ]);
                foreach ($g['items'] as $it) {
                    ProductOfferBundleItem::create(['bundle_id' => $bundle->id, 'variation_id' => $it['vid'], 'quantity' => $it['qty']]);
                }
                $added++;
            }
            DB::commit();
            return back()->with('status', ['success' => true, 'msg' => "تم الاستيراد — حزم مُضافة: $added ، متخطّاة: $skipped"]);
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::emergency("File:" . $e->getFile() . "Line:" . $e->getLine() . "Message:" . $e->getMessage());
            return back()->with('status', ['success' => false, 'msg' => __('messages.something_went_wrong') . ': ' . $e->getMessage()]);
        }
    }

    public function downloadBundleTemplate()
    {
        if (!auth()->user()->can('product.view')) { abort(403, 'Unauthorized action.'); }
        $file_path = public_path('downloads/bundles_template.xlsx');
        if (!file_exists(public_path('downloads'))) { mkdir(public_path('downloads'), 0755, true); }
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $headers = ['Bundle Name', 'SKU/Barcode', 'Quantity', 'Bundle Price', 'Start Date (YYYY-MM-DD)', 'End Date', 'Active (1/0)'];
        foreach ($headers as $idx => $h) { $sheet->setCellValue(chr(65 + $idx) . '1', $h); $sheet->getStyle(chr(65 + $idx) . '1')->getFont()->setBold(true); }
        $examples = [
            ['عرض الصيف', 'PROD001', 1, 100, '2026-06-01', '2026-08-31', 1],
            ['عرض الصيف', 'PROD002', 2, 100, '2026-06-01', '2026-08-31', 1],
            ['حزمة القرطاسية', 'PEN01', 3, 5, '', '', 1],
            ['حزمة القرطاسية', 'NOTE01', 1, 5, '', '', 1],
        ];
        foreach ($examples as $r => $ex) { foreach ($ex as $c => $v) { $sheet->setCellValue(chr(65 + $c) . ($r + 2), $v); } }
        foreach (range('A', 'G') as $col) { $sheet->getColumnDimension($col)->setWidth(20); }
        (new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet))->save($file_path);
        return response()->download($file_path, 'bundles_template_' . date('Y-m-d') . '.xlsx');
    }

    // 🆕 استيراد الباركود البديل من Excel (عمودان: SKU/الباركود الأصلي ، الباركود البديل)
    public function importAltBarcodes(Request $request)
    {
        if (!auth()->user()->can('product.create')) {
            abort(403, 'Unauthorized action.');
        }
        $business_id = request()->session()->get('user.business_id');

        $validator = Validator::make($request->all(), [
            'excel_file' => 'required|mimes:xlsx,xls,csv|max:5120',
        ]);
        if ($validator->fails()) {
            return back()->with('status', ['success' => false, 'msg' => implode('<br>', $validator->errors()->all())]);
        }

        try {
            $path = $request->file('excel_file')->getRealPath();
            $sheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($path)->getActiveSheet();
            $rows = $sheet->toArray();

            $added = 0; $skipped = 0; $notfound = 0;
            foreach ($rows as $i => $row) {
                if ($i === 0) { continue; } // تخطّي صف العناوين
                $sku  = isset($row[0]) ? trim((string) $row[0]) : '';
                $code = isset($row[1]) ? trim((string) $row[1]) : '';
                if ($sku === '' || $code === '') { continue; }

                // إيجاد المنتج عبر sub_sku ضمن نفس النشاط
                $variation = \App\Variation::where('variations.sub_sku', $sku)
                    ->join('products as p', 'variations.product_id', '=', 'p.id')
                    ->where('p.business_id', $business_id)
                    ->select('variations.id')
                    ->first();
                if (!$variation) { $notfound++; continue; }

                $exists = ProductAltBarcode::where('business_id', $business_id)->where('alt_barcode', $code)->exists();
                if ($exists) { $skipped++; continue; }

                ProductAltBarcode::create([
                    'business_id'  => $business_id,
                    'variation_id' => $variation->id,
                    'alt_barcode'  => $code,
                    'created_by'   => auth()->id(),
                ]);
                $added++;
            }

            return back()->with('status', [
                'success' => true,
                'msg' => "تم الاستيراد — مُضاف: $added ، متخطّى (مكرّر): $skipped ، SKU غير موجود: $notfound",
            ]);
        } catch (\Exception $e) {
            \Log::emergency("File:" . $e->getFile() . "Line:" . $e->getLine() . "Message:" . $e->getMessage());
            return back()->with('status', ['success' => false, 'msg' => __('messages.something_went_wrong') . ': ' . $e->getMessage()]);
        }
    }

    public function downloadAltBarcodeTemplate()
    {
        if (!auth()->user()->can('product.view')) {
            abort(403, 'Unauthorized action.');
        }
        $file_path = public_path('downloads/alt_barcodes_template.xlsx');
        if (!file_exists($file_path)) {
            if (!file_exists(public_path('downloads'))) { mkdir(public_path('downloads'), 0755, true); }
            $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();
            $sheet->setCellValue('A1', 'SKU/Original Barcode');
            $sheet->setCellValue('B1', 'Alternative Barcode');
            $sheet->getStyle('A1')->getFont()->setBold(true);
            $sheet->getStyle('B1')->getFont()->setBold(true);
            $sheet->setCellValue('A2', 'PROD001'); $sheet->setCellValue('B2', '6291234567890');
            $sheet->getColumnDimension('A')->setWidth(28);
            $sheet->getColumnDimension('B')->setWidth(28);
            (new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet))->save($file_path);
        }
        return response()->download($file_path, 'alt_barcodes_template_' . date('Y-m-d') . '.xlsx');
    }

    public function editSpecialOffer($id)
    {
        if (!auth()->user()->can('product.update')) {
            abort(403, 'Unauthorized action.');
        }
        $business_id = request()->session()->get('user.business_id');
        $offer = ProductSpecialOffer::where('business_id', $business_id)->with(['items.variation.product'])->find($id);
        if (!$offer) {
            return response()->json(['success' => false, 'msg' => 'العرض غير موجود']);
        }
        $items = [];
        foreach ($offer->items as $it) {
            if (empty($it->variation)) { continue; }
            $pname = optional($it->variation->product)->name;
            $vname = $it->variation->name;
            $sku   = $it->variation->sub_sku;
            $label = $pname . (!empty($vname) && $vname != 'DUMMY' ? ' - ' . $vname : '') . ($sku ? ' (' . $sku . ')' : '');
            $items[] = ['variation_id' => $it->variation_id, 'label' => $label];
        }
        return response()->json(['success' => true, 'offer' => $offer, 'items' => $items]);
    }

    public function updateSpecialOffer(Request $request, $id)
    {
        if (!auth()->user()->can('product.update')) {
            abort(403, 'Unauthorized action.');
        }
        try {
            $business_id = request()->session()->get('user.business_id');
            $offer = ProductSpecialOffer::where('business_id', $business_id)->find($id);
            if (!$offer) {
                return response()->json(['success' => false, 'msg' => 'العرض غير موجود']);
            }

            $validator = Validator::make($request->all(), [
                'name'        => 'required|string|max:191',
                'offer_type'  => 'required|in:bogo,nth_percent,percent_items',
                'items'       => 'required|array|min:1',
                'start_date'  => 'nullable|date',
                'end_date'    => 'nullable|date|after_or_equal:start_date',
            ], [], ['items' => 'الأصناف', 'name' => 'اسم العرض']);
            if ($validator->fails()) {
                return response()->json(['success' => false, 'msg' => implode('<br>', $validator->errors()->all())]);
            }

            DB::beginTransaction();
            $offer->update([
                'location_id' => $request->filled('location_id') ? $request->location_id : null,
                'name'        => $request->name,
                'offer_type'  => $request->offer_type,
                'buy_qty'     => $request->buy_qty ?: 1,
                'free_qty'    => $request->free_qty ?: 1,
                'percent'     => $request->percent ?: 0,
                'start_date'  => $request->start_date,
                'end_date'    => $request->end_date,
                'is_active'   => $request->is_active ?? 1,
            ]);
            $offer->items()->delete();
            foreach ((array) $request->items as $vid) {
                $variation = Variation::where('id', $vid)
                    ->whereHas('product', function ($q) use ($business_id) { $q->where('business_id', $business_id); })->first();
                if (!$variation) { continue; }
                ProductSpecialOfferItem::create(['special_offer_id' => $offer->id, 'variation_id' => $vid]);
            }
            if ($offer->items()->count() < 1) {
                DB::rollBack();
                return response()->json(['success' => false, 'msg' => 'اختر صنفاً واحداً على الأقل']);
            }
            DB::commit();
            return response()->json(['success' => true, 'msg' => 'تم تحديث العرض الخاص بنجاح']);
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::emergency("File:" . $e->getFile() . "Line:" . $e->getLine() . "Message:" . $e->getMessage());
            return response()->json(['success' => false, 'msg' => __('messages.something_went_wrong')]);
        }
    }

    public function destroySpecialOffer($id)
    {
        if (!auth()->user()->can('product.delete')) {
            abort(403, 'Unauthorized action.');
        }
        try {
            $business_id = request()->session()->get('user.business_id');
            $offer = ProductSpecialOffer::where('business_id', $business_id)->find($id);
            if ($offer) {
                $offer->items()->delete();
                $offer->delete();
                return ['success' => true, 'msg' => 'تم حذف العرض الخاص'];
            }
            return ['success' => false, 'msg' => __('messages.something_went_wrong')];
        } catch (\Exception $e) {
            \Log::emergency("File:" . $e->getFile() . "Line:" . $e->getLine() . "Message:" . $e->getMessage());
            return ['success' => false, 'msg' => __('messages.something_went_wrong')];
        }
    }
}