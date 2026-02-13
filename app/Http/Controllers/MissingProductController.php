<?php

namespace App\Http\Controllers;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MissingProductController extends Controller
{
public function getMissingProducts(Request $request)
{
    $business_id = request()->session()->get('user.business_id');
    
    // 1. تعريف المتغيرات الأساسية للفلاتر (يجب أن تكون متاحة للـ Ajax ولتحميل الصفحة)
    $loc1 = $request->location_id_1;
    $loc2 = $request->location_id_2;

    // 2. منطق الـ Ajax (DataTables)
    if ($request->ajax()) {
        $query = DB::table('variation_location_details as vld1')
            ->join('products as p', 'vld1.product_id', '=', 'p.id')
            ->leftJoin('brands as b', 'p.brand_id', '=', 'b.id')
            ->leftJoin('units as u', 'p.unit_id', '=', 'u.id')
            ->leftJoin('categories as cat', 'p.category_id', '=', 'cat.id')
            ->leftJoin('categories as sub_cat', 'p.sub_category_id', '=', 'sub_cat.id')
            ->leftJoin('variation_location_details as vld2', function($join) use ($loc2) {
                $join->on('vld1.variation_id', '=', 'vld2.variation_id')
                     ->where('vld2.location_id', '=', $loc2);
            })
            ->select(
                'p.image',
                'p.name', 'p.sku', 'p.type', 'p.image', 'p.is_inactive', 
                'p.tax_type',
                'cat.name as category_name', 
                'sub_cat.name as sub_category_name',
                'p.product_custom_field1', 'p.product_custom_field2', 'p.product_custom_field3', 
                'p.product_custom_field4', 'p.product_custom_field5', 'p.product_custom_field6', 'p.product_custom_field7',
                'b.name as brand_name',
                'u.short_name as unit_name',
                'vld1.qty_available as qty_in_loc1',
                'vld2.qty_available as qty_in_loc2'
            )
            ->where('p.business_id', $business_id)
            ->where('vld1.location_id', $loc1)
            ->where('vld1.qty_available', '>', 0);

        // تطبيق الفلاتر
        if (!empty($request->brand_id)) { $query->where('p.brand_id', $request->brand_id); }
        if (!empty($request->unit_id)) { $query->where('p.unit_id', $request->unit_id); }
        if ($request->status == 'active') { $query->where('p.is_inactive', 0); }
        elseif ($request->status == 'inactive') { $query->where('p.is_inactive', 1); }
        if (!empty($request->category_id)) { $query->where('p.category_id', $request->category_id); }
        if (!empty($request->sub_category_id)) { $query->where('p.sub_category_id', $request->sub_category_id); }
        if (!empty($request->tax_type)) { $query->where('p.tax_type', $request->tax_type); }

        $query->where(function($q) {
            $q->where('vld2.qty_available', '<=', 0)->orWhereNull('vld2.qty_available');
        });

        return Datatables::of($query)
            ->editColumn('image', function ($row) {
                // تحديد المسار الافتراضي للصور في نظام الـ POS
                $image_url = asset('uploads/img/' . (!empty($row->image) ? $row->image : 'default.png'));
                
                // إرجاع وسم الصورة HTML
                return '<img src="' . $image_url . '" 
                             class="img-thumbnail" 
                             style="width: 50px; height: 50px; object-fit: cover;" 
                             onerror="this.src=\'' . asset('img/default.png') . '\';">';
            })
            ->editColumn('type', function($row) { return __('lang_v1.' . $row->type); })
            ->editColumn('is_inactive', function($row) {
                return $row->is_inactive ? '<span class="label label-danger">غير نشط</span>' : '<span class="label label-success">نشط</span>';
            })
            ->editColumn('qty_in_loc2', function($row) {
                if (is_null($row->qty_in_loc2)) return 0;
                $class = $row->qty_in_loc2 < 0 ? 'text-danger' : '';
                return '<span class="' . $class . '">' . number_format($row->qty_in_loc2, 2) . '</span>';
            })
            ->editColumn('qty_in_loc1', '{{@num_format($qty_in_loc1)}}')
            ->editColumn('tax_type', function($row) {
                return $row->tax_type == 'inclusive' ? 'شامل' : 'غير شامل';
            })
            ->rawColumns(['image', 'is_inactive', 'qty_in_loc2'])
            ->make(true);
    }

    // 3. جلب البيانات للقوائم المنسدلة (عند تحميل الصفحة لأول مرة)
    $business_locations = DB::table('business_locations')->where('business_id', $business_id)->pluck('name', 'id');
    $brands = DB::table('brands')->where('business_id', $business_id)->pluck('name', 'id');
    $units = DB::table('units')->where('business_id', $business_id)->pluck('actual_name', 'id');
    
    // جلب الأصناف الرئيسية والفرعية بشكل صحيح
    $categories = DB::table('categories')
                ->where('business_id', $business_id)
                ->where(function($q) {
                    $q->whereNull('parent_id')->orWhere('parent_id', 0);
                })
                ->pluck('name', 'id');
    $sub_categories = DB::table('categories')
                    ->where('business_id', $business_id)
                    ->where('parent_id', '!=', 0)
                    ->whereNotNull('parent_id')
                    ->pluck('name', 'id');

     $loc1_name = !empty($loc1) ? $business_locations[$loc1] : 'المصدر';
    $loc2_name = !empty($loc2) ? $business_locations[$loc2] : 'المستهدف';

    return view('missing_products.index', compact(
        'business_locations', 
        'brands', 
        'units', 
        'categories', 
        'sub_categories',
        'loc1', 
        'loc2', 
        'loc1_name', 
        'loc2_name'
    ));
}
}
