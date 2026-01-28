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
                $html = '<div class="btn-group">';
                $html .= '<button class="btn btn-xs btn-primary edit-btn" data-id="'.$row->id.'">
                         <i class="fa fa-edit"></i></button>';
                $html .= '<button class="btn btn-xs btn-danger delete-btn" data-href="'.action([\App\Http\Controllers\ProductOfferController::class, 'destroy'], [$row->id]).'">
                         <i class="fa fa-trash"></i></button>';
                $html .= '</div>';
                return $html;
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
                $types = [
                    'fixed' => __('lang_v1.fixed'),
                    'percentage' => __('lang_v1.percentage'),
                    'override' => __('lang_v1.override_price')
                ];
                return $types[$row->price_type] ?? $row->price_type;
            })
            ->editColumn('is_active', function ($row) {
                if ($row->is_active) {
                    return '<span class="label label-success">'.__('lang_v1.active').'</span>';
                } else {
                    return '<span class="label label-danger">'.__('lang_v1.inactive').'</span>';
                }
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
            $this->createExcelTemplate();
        }
        
        return response()->download($file_path, 'product_offers_template_' . date('Y-m-d') . '.xlsx');
    }
    
    private function createExcelTemplate()
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
}