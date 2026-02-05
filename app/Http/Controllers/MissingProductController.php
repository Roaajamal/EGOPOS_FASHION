<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MissingProductController extends Controller
{
    public function getMissingProducts(Request $request)
{
    $business_id = request()->session()->get('user.business_id');

    // جلب الفروع للقوائم المنسدلة
    $business_locations = DB::table('business_locations')
                            ->where('business_id', $business_id)
                            ->pluck('name', 'id');

    $loc1 = $request->location_id_1;
    $loc2 = $request->location_id_2;

    $loc1_name = !empty($loc1) ? $business_locations[$loc1] : 'المصدر';
    $loc2_name = !empty($loc2) ? $business_locations[$loc2] : 'المستهدف';

    $missingProducts = collect();

    if (!empty($loc1) && !empty($loc2)) {
        // الاستعلام: جلب المنتجات الموجودة في فرع 1 ورصيدها 0 في فرع 2
        $missingProducts = DB::table('variation_location_details as vld1')
            ->join('products as p', 'vld1.product_id', '=', 'p.id')
            // ربط مع نفس الجدول للفرع الثاني (Left Join) للتأكد من الكمية هناك
            ->leftJoin('variation_location_details as vld2', function($join) use ($loc2) {
                $join->on('vld1.variation_id', '=', 'vld2.variation_id')
                     ->where('vld2.location_id', '=', $loc2);
            })
            ->select(
                'p.name', 
                'p.sku', 
                'vld1.qty_available as qty_in_loc1',
                DB::raw('COALESCE(vld2.qty_available, 0) as qty_in_loc2')
            )
            ->where('vld1.location_id', $loc1)
            ->where('vld1.qty_available', '>', 0)
            // موجود في فرع 1
            ->where(function($query) {
                $query->where('vld2.qty_available', '<=', 0) // كميته 0 في فرع 2
                      ->orWhereNull('vld2.qty_available');   // أو ليس له سجل أصلاً في فرع 2
            })
            ->get();
    }

    return view('missing_products.index', compact('missingProducts', 'business_locations', 'loc1_name' , 'loc2_name'));
}
}
