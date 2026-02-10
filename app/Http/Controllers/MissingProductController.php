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
        // 1) منتجات لها رصيد في فرع 1 ورصيدها 0 أو غير مسجّل في فرع 2
        $missingWithStock = DB::table('variation_location_details as vld1')
            ->join('products as p', 'vld1.product_id', '=', 'p.id')
            ->join('variations as v', 'vld1.variation_id', '=', 'v.id')
            ->leftJoin('variation_location_details as vld2', function($join) use ($loc2) {
                $join->on('vld1.variation_id', '=', 'vld2.variation_id')
                     ->where('vld2.location_id', '=', $loc2);
            })
            ->select(
                'p.name',
                'p.sku',
                'p.product_custom_field1',
                'p.product_custom_field2',
                'v.name as variation_name',
                'vld1.qty_available as qty_in_loc1',
                DB::raw('COALESCE(vld2.qty_available, 0) as qty_in_loc2')
            )
            ->where('p.business_id', $business_id)
            ->where('vld1.location_id', $loc1)
            ->where('vld1.qty_available', '>', 0)
            ->whereNull('v.deleted_at')
            ->where(function($query) {
                $query->where('vld2.qty_available', '<=', 0)
                      ->orWhereNull('vld2.qty_available');
            })
            ->get();

        // 2) منتجات معرّفة للفرعين لكن لا رصيد لها في المصدر ولا في المستهدف
        $missingNewBoth = DB::table('products as p')
            ->join('variations as v', 'v.product_id', '=', 'p.id')
            ->whereNull('v.deleted_at')
            ->join('product_locations as pl1', function($join) use ($loc1) {
                $join->on('p.id', '=', 'pl1.product_id')->where('pl1.location_id', '=', $loc1);
            })
            ->join('product_locations as pl2', function($join) use ($loc2) {
                $join->on('p.id', '=', 'pl2.product_id')->where('pl2.location_id', '=', $loc2);
            })
            ->leftJoin('variation_location_details as vld1', function($join) use ($loc1) {
                $join->on('v.id', '=', 'vld1.variation_id')->where('vld1.location_id', '=', $loc1);
            })
            ->leftJoin('variation_location_details as vld2', function($join) use ($loc2) {
                $join->on('v.id', '=', 'vld2.variation_id')->where('vld2.location_id', '=', $loc2);
            })
            ->where('p.business_id', $business_id)
            ->where(function($query) {
                $query->whereNull('vld2.qty_available')->orWhere('vld2.qty_available', '<=', 0);
            })
            ->where(function($query) {
                $query->whereNull('vld1.qty_available')->orWhere('vld1.qty_available', '<=', 0);
            })
            ->select(
                'p.name',
                'p.sku',
                'p.product_custom_field1',
                'p.product_custom_field2',
                'v.name as variation_name',
                DB::raw('COALESCE(vld1.qty_available, 0) as qty_in_loc1'),
                DB::raw('COALESCE(vld2.qty_available, 0) as qty_in_loc2')
            )
            ->get();

        // 3) منتجات معرّفة للفرع المستهدف فقط (أو ضمن فروع فيها المستهدف) ولا يوجد لها رصيد في المستهدف — المنتجات التي ضفتها للفرع ولم تدخل رصيدها بعد
        $missingInTargetOnly = DB::table('products as p')
            ->join('variations as v', 'v.product_id', '=', 'p.id')
            ->whereNull('v.deleted_at')
            ->join('product_locations as pl2', function($join) use ($loc2) {
                $join->on('p.id', '=', 'pl2.product_id')->where('pl2.location_id', '=', $loc2);
            })
            ->leftJoin('variation_location_details as vld1', function($join) use ($loc1) {
                $join->on('v.id', '=', 'vld1.variation_id')->where('vld1.location_id', '=', $loc1);
            })
            ->leftJoin('variation_location_details as vld2', function($join) use ($loc2) {
                $join->on('v.id', '=', 'vld2.variation_id')->where('vld2.location_id', '=', $loc2);
            })
            ->where('p.business_id', $business_id)
            ->where(function($query) {
                $query->whereNull('vld2.qty_available')->orWhere('vld2.qty_available', '<=', 0);
            })
            ->select(
                'p.name',
                'p.sku',
                'p.product_custom_field1',
                'p.product_custom_field2',
                'v.name as variation_name',
                DB::raw('COALESCE(vld1.qty_available, 0) as qty_in_loc1'),
                DB::raw('COALESCE(vld2.qty_available, 0) as qty_in_loc2')
            )
            ->get();

        // دمج النتائج وإزالة التكرار (نفس المنتج + نفس الكميات)
        $seen = [];
        $missingProducts = $missingWithStock->concat($missingNewBoth)->concat($missingInTargetOnly)->filter(function ($row) use (&$seen) {
            $key = $row->sku . '|' . (string) $row->qty_in_loc1 . '|' . (string) $row->qty_in_loc2;
            if (isset($seen[$key])) {
                return false;
            }
            $seen[$key] = true;
            return true;
        })->values();

        // اسم العرض: اسم المنتج - اللون - المقاس (إن وُجد)
        $missingProducts = $missingProducts->map(function ($row) {
            $name = $row->name ?? '';
            if (isset($row->variation_name) && (string) $row->variation_name === 'DUMMY') {
                if (!empty(trim((string) ($row->product_custom_field1 ?? '')))) {
                    $name .= ' - ' . trim($row->product_custom_field1);
                }
                if (!empty(trim((string) ($row->product_custom_field2 ?? '')))) {
                    $name .= ' - ' . trim($row->product_custom_field2);
                }
            } elseif (!empty(trim((string) ($row->variation_name ?? '')))) {
                $name .= ' - ' . trim($row->variation_name);
            }
            $row->display_name = $name;
            return $row;
        });
    }

    return view('missing_products.index', compact('missingProducts', 'business_locations', 'loc1_name' , 'loc2_name'));
}
}
