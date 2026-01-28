<?php

namespace App\Http\Controllers;

use App\ProductOffer;
use App\Variation;
use App\BusinessLocation;
use App\Utils\ProductUtil;
use Excel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;

class SpecialOfferController extends Controller
{
    protected $productUtil;
    
    public function __construct(ProductUtil $productUtil)
    {
        $this->productUtil = $productUtil;
    }
    
    /**
     * عرض صفحة العروض
     */
    public function index()
    {
        if (!auth()->user()->can('offer.view')) {
            abort(403, 'Unauthorized action.');
        }
        
        $business_id = request()->session()->get('user.business_id');
        
        if (request()->ajax()) {
            $query = ProductOffer::with([
                    'variation', 
                    'variation.product:id,name,sku,type',
                    'location:id,name',
                    'created_by_user:id,username'
                ])
                ->where('product_offers.business_id', $business_id);
            
            // فلترة حسب الموقع
            $location_id = request()->get('location_id', null);
            if (!empty($location_id) && $location_id != 'all') {
                $query->where('product_offers.location_id', $location_id);
            }
            
            // فلترة حسب النشاط
            $active_state = request()->get('active_state', null);
            if ($active_state == 'active') {
                $query->where('is_active', 1);
            } elseif ($active_state == 'inactive') {
                $query->where('is_active', 0);
            }
            
            // فلترة حسب التاريخ
            $start_date = request()->get('start_date', null);
            $end_date = request()->get('end_date', null);
            if (!empty($start_date) && !empty($end_date)) {
                $query->where(function($q) use ($start_date, $end_date) {
                    $q->whereNull('start_date')
                      ->orWhere('start_date', '<=', $end_date);
                })
                ->where(function($q) use ($start_date, $end_date) {
                    $q->whereNull('end_date')
                      ->orWhere('end_date', '>=', $start_date);
                });
            }
            
            return DataTables::of($query)
                ->addColumn('product_name', function($row) {
                    $name = $row->variation->product->name ?? __('lang_v1.product_not_found');
                    $sku = $row->variation->sub_sku ?? $row->variation->product->sku ?? 'N/A';
                    return $name . ' <br><small class="text-muted">' . __('product.sku') . ': ' . $sku . '</small>';
                })
                ->addColumn('location_name', function($row) {
                    return $row->location->name ?? __('lang_v1.all_locations');
                })
                ->addColumn('price_type_text', function($row) {
                    $types = [
                        'fixed' => __('lang_v1.fixed'),
                        'percentage' => __('lang_v1.percentage'),
                        'override' => __('lang_v1.override')
                    ];
                    return $types[$row->price_type] ?? $row->price_type;
                })
                ->addColumn('calculated_price', function($row) {
                    if ($row->price_type == 'percentage') {
                        return $row->offer_price . '%';
                    }
                    return $this->productUtil->num_f($row->offer_price, true);
                })
                ->addColumn('date_range', function($row) {
                    if (empty($row->start_date) && empty($row->end_date)) {
                        return __('lang_v1.no_date_limit');
                    }
                    
                    $start = !empty($row->start_date) ? $this->productUtil->format_date($row->start_date) : __('lang_v1.no_start_date');
                    $end = !empty($row->end_date) ? $this->productUtil->format_date($row->end_date) : __('lang_v1.no_end_date');
                    
                    return $start . ' - ' . $end;
                })
                ->addColumn('action', function($row) {
                    $html = '<div class="btn-group">
                            <button type="button" class="btn btn-info btn-sm dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                ' . __('messages.actions') . ' <span class="caret"></span>
                            </button>
                            <ul class="dropdown-menu dropdown-menu-right">';
                    
                    if (auth()->user()->can('offer.update')) {
                        $html .= '<li><a href="#" data-href="' . action([SpecialOfferController::class, 'edit'], [$row->id]) . '" class="edit-offer-btn"><i class="fa fa-edit"></i> ' . __('messages.edit') . '</a></li>';
                    }
                    
                    if (auth()->user()->can('offer.delete')) {
                        $html .= '<li><a href="#" data-href="' . action([SpecialOfferController::class, 'destroy'], [$row->id]) . '" class="delete-offer-btn"><i class="fa fa-trash"></i> ' . __('messages.delete') . '</a></li>';
                    }
                    
                    // زر التنشيط/الإلغاء
                    if (auth()->user()->can('offer.update')) {
                        if ($row->is_active) {
                            $html .= '<li><a href="#" data-href="' . action([SpecialOfferController::class, 'toggleStatus'], [$row->id]) . '" class="toggle-status-btn"><i class="fa fa-ban"></i> ' . __('lang_v1.deactivate') . '</a></li>';
                        } else {
                            $html .= '<li><a href="#" data-href="' . action([SpecialOfferController::class, 'toggleStatus'], [$row->id]) . '" class="toggle-status-btn"><i class="fa fa-check"></i> ' . __('lang_v1.activate') . '</a></li>';
                        }
                    }
                    
                    $html .= '</ul></div>';
                    return $html;
                })
                ->editColumn('min_quantity', function($row) {
                    return $this->productUtil->num_f($row->min_quantity);
                })
                ->editColumn('created_at', function($row) {
                    return $this->productUtil->format_date($row->created_at, true);
                })
                ->editColumn('is_active', function($row) {
                    if ($row->is_active) {
                        $status = '<span class="label label-success">' . __('lang_v1.active') . '</span>';
                        // التحقق إذا كان العرض منتهي الصلاحية
                        if (!empty($row->end_date) && date('Y-m-d') > $row->end_date) {
                            $status .= ' <span class="label label-warning">' . __('lang_v1.expired') . '</span>';
                        }
                        return $status;
                    } else {
                        return '<span class="label label-danger">' . __('lang_v1.inactive') . '</span>';
                    }
                })
                ->filterColumn('product_name', function($query, $keyword) {
                    $query->whereHas('variation.product', function($q) use ($keyword) {
                        $q->where('name', 'like', "%{$keyword}%")
                          ->orWhere('sku', 'like', "%{$keyword}%");
                    })->orWhereHas('variation', function($q) use ($keyword) {
                        $q->where('sub_sku', 'like', "%{$keyword}%");
                    });
                })
                ->rawColumns(['product_name', 'action', 'is_active'])
                ->removeColumn('id')
                ->make(true);
        }
        
        $business_locations = BusinessLocation::forDropdown($business_id, false, true);
        $business_locations->prepend(__('lang_v1.all_locations'), 'all');
        
        return view('special_offers.index')
            ->with(compact('business_locations'));
    }
    
    /**
     * عرض نموذج إضافة عرض
     */
    public function create()
    {
        if (!auth()->user()->can('offer.create')) {
            abort(403, 'Unauthorized action.');
        }
        
        $business_id = request()->session()->get('user.business_id');
        $business_locations = BusinessLocation::forDropdown($business_id);
        
        return view('special_offers.create')
            ->with(compact('business_locations'));
    }
    
    /**
     * حفظ عرض جديد
     */
    public function store(Request $request)
    {
        if (!auth()->user()->can('offer.create')) {
            abort(403, 'Unauthorized action.');
        }
        
        try {
            $business_id = $request->session()->get('user.business_id');
            $user_id = $request->session()->get('user.id');
            
            $input = $request->only([
                'variation_id',
                'location_id',
                'min_quantity',
                'offer_price',
                'price_type',
                'start_date',
                'end_date',
                'notes'
            ]);
            
            // تحويل التواريخ
            $input['start_date'] = !empty($input['start_date']) ? 
                $this->productUtil->uf_date($input['start_date']) : null;
            $input['end_date'] = !empty($input['end_date']) ? 
                $this->productUtil->uf_date($input['end_date']) : null;
            
            $input['business_id'] = $business_id;
            $input['created_by'] = $user_id;
            $input['is_active'] = $request->has('is_active') ? 1 : 0;
            
            // التحقق من صحة التواريخ
            if (!empty($input['start_date']) && !empty($input['end_date']) && 
                $input['start_date'] > $input['end_date']) {
                return [
                    'success' => false,
                    'msg' => __('lang_v1.start_date_greater_than_end_date')
                ];
            }
            
            // التحقق من عدم تكرار العرض لنفس المنتج والكمية والموقع
            $existing = ProductOffer::where('business_id', $business_id)
                ->where('variation_id', $input['variation_id'])
                ->where('location_id', $input['location_id'])
                ->where('min_quantity', $input['min_quantity'])
                ->where('price_type', $input['price_type'])
                ->first();
            
            if ($existing) {
                return [
                    'success' => false,
                    'msg' => __('lang_v1.offer_already_exists')
                ];
            }
            
            DB::beginTransaction();
            
            $offer = ProductOffer::create($input);
            
            DB::commit();
            
            $output = [
                'success' => true,
                'msg' => __('lang_v1.added_success'),
                'data' => $offer
            ];
            
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::emergency('File:'.$e->getFile().'Line:'.$e->getLine().'Message:'.$e->getMessage());
            
            $output = [
                'success' => false,
                'msg' => __('messages.something_went_wrong')
            ];
        }
        
        return $output;
    }
    
    /**
     * عرض نموذج تعديل عرض
     */
    public function edit($id)
    {
        if (!auth()->user()->can('offer.update')) {
            abort(403, 'Unauthorized action.');
        }
        
        $business_id = request()->session()->get('user.business_id');
        
        $offer = ProductOffer::with(['variation.product', 'location'])
            ->where('business_id', $business_id)
            ->findOrFail($id);
        
        $business_locations = BusinessLocation::forDropdown($business_id);
        
        return view('special_offers.edit')
            ->with(compact('offer', 'business_locations'));
    }
    
    /**
     * تحديث عرض
     */
    public function update(Request $request, $id)
    {
        if (!auth()->user()->can('offer.update')) {
            abort(403, 'Unauthorized action.');
        }
        
        try {
            $business_id = $request->session()->get('user.business_id');
            
            $offer = ProductOffer::where('business_id', $business_id)
                ->findOrFail($id);
            
            $input = $request->only([
                'variation_id',
                'location_id',
                'min_quantity',
                'offer_price',
                'price_type',
                'start_date',
                'end_date',
                'notes'
            ]);
            
            // تحويل التواريخ
            $input['start_date'] = !empty($input['start_date']) ? 
                $this->productUtil->uf_date($input['start_date']) : null;
            $input['end_date'] = !empty($input['end_date']) ? 
                $this->productUtil->uf_date($input['end_date']) : null;
            
            $input['is_active'] = $request->has('is_active') ? 1 : 0;
            
            // التحقق من صحة التواريخ
            if (!empty($input['start_date']) && !empty($input['end_date']) && 
                $input['start_date'] > $input['end_date']) {
                return [
                    'success' => false,
                    'msg' => __('lang_v1.start_date_greater_than_end_date')
                ];
            }
            
            // التحقق من عدم تكرار العرض (باستثناء السجل الحالي)
            $existing = ProductOffer::where('business_id', $business_id)
                ->where('variation_id', $input['variation_id'])
                ->where('location_id', $input['location_id'])
                ->where('min_quantity', $input['min_quantity'])
                ->where('price_type', $input['price_type'])
                ->where('id', '!=', $id)
                ->first();
            
            if ($existing) {
                return [
                    'success' => false,
                    'msg' => __('lang_v1.offer_already_exists')
                ];
            }
            
            DB::beginTransaction();
            
            $offer->update($input);
            
            DB::commit();
            
            $output = [
                'success' => true,
                'msg' => __('lang_v1.updated_success'),
                'data' => $offer
            ];
            
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::emergency('File:'.$e->getFile().'Line:'.$e->getLine().'Message:'.$e->getMessage());
            
            $output = [
                'success' => false,
                'msg' => __('messages.something_went_wrong')
            ];
        }
        
        return $output;
    }
    
    /**
     * تبديل حالة العرض (نشط/غير نشط)
     */
    public function toggleStatus($id)
    {
        if (!auth()->user()->can('offer.update')) {
            abort(403, 'Unauthorized action.');
        }
        
        if (request()->ajax()) {
            try {
                $business_id = request()->session()->get('user.business_id');
                
                $offer = ProductOffer::where('business_id', $business_id)
                    ->findOrFail($id);
                
                $offer->is_active = !$offer->is_active;
                $offer->save();
                
                $output = [
                    'success' => true,
                    'msg' => __('lang_v1.updated_success'),
                    'new_status' => $offer->is_active
                ];
                
            } catch (\Exception $e) {
                \Log::emergency('File:'.$e->getFile().'Line:'.$e->getLine().'Message:'.$e->getMessage());
                
                $output = [
                    'success' => false,
                    'msg' => __('messages.something_went_wrong')
                ];
            }
            
            return $output;
        }
    }
    
    /**
     * حذف عرض
     */
    public function destroy($id)
    {
        if (!auth()->user()->can('offer.delete')) {
            abort(403, 'Unauthorized action.');
        }
        
        if (request()->ajax()) {
            try {
                $business_id = request()->session()->get('user.business_id');
                
                $offer = ProductOffer::where('business_id', $business_id)
                    ->findOrFail($id);
                
                DB::beginTransaction();
                
                $offer->delete();
                
                DB::commit();
                
                $output = [
                    'success' => true,
                    'msg' => __('lang_v1.deleted_success')
                ];
                
            } catch (\Exception $e) {
                DB::rollBack();
                \Log::emergency('File:'.$e->getFile().'Line:'.$e->getLine().'Message:'.$e->getMessage());
                
                $output = [
                    'success' => false,
                    'msg' => __('messages.something_went_wrong')
                ];
            }
            
            return $output;
        }
    }
    
    /**
     * البحث عن المنتجات لإضافتها للعروض
     */
    public function getProducts(Request $request)
    {
        if (request()->ajax()) {
            $search_term = $request->input('term', '');
            $location_id = $request->input('location_id', null);
            $business_id = $request->session()->get('user.business_id');
            
            // استخدام دالة البحث من ProductUtil
            $result = $this->productUtil->filterProduct($business_id, $search_term, $location_id);
            
            return $result;
        }
    }
    
    /**
     * الحصول على تفاصيل المنتج
     */
    public function getProductDetails($variation_id)
    {
        if (request()->ajax()) {
            $business_id = request()->session()->get('user.business_id');
            $location_id = request()->get('location_id', null);
            
            $product = $this->productUtil->getDetailsFromVariation($variation_id, $business_id, $location_id);
            
            if ($product) {
                return [
                    'success' => true,
                    'data' => $product
                ];
            }
            
            return [
                'success' => false,
                'msg' => __('lang_v1.product_not_found')
            ];
        }
    }
    
    /**
     * التحقق من صحة SKU
     */
    public function checkProductSku(Request $request)
    {
        $business_id = $request->session()->get('user.business_id');
        $sku = $request->input('sku');
        
        // البحث في جدول variations
        $variation = Variation::where('sub_sku', $sku)
            ->whereHas('product', function($q) use ($business_id) {
                $q->where('business_id', $business_id);
            })
            ->first();
        
        if ($variation) {
            return [
                'exists' => true,
                'variation_id' => $variation->id,
                'product_name' => $variation->product->name,
                'default_price' => $variation->sell_price_inc_tax
            ];
        }
        
        // البحث في جدول products
        $product = Product::where('sku', $sku)
            ->where('business_id', $business_id)
            ->where('type', 'single')
            ->first();
        
        if ($product) {
            $variation = $product->variations->first();
            if ($variation) {
                return [
                    'exists' => true,
                    'variation_id' => $variation->id,
                    'product_name' => $product->name,
                    'default_price' => $variation->sell_price_inc_tax
                ];
            }
        }
        
        return ['exists' => false];
    }
}