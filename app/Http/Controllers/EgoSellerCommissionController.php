<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

// 🆕 تقرير عمولات البائعين لكل منتج (حسب البائع المُسنَد لكل سطر بيع)
class EgoSellerCommissionController extends Controller
{
    public function index(Request $request)
    {
        $business_id = $request->session()->get('user.business_id');
        $start = $request->input('start_date', date('Y-m-01'));
        $end   = $request->input('end_date', date('Y-m-d'));
        $seller_id = $request->input('seller_id');

        // قائمة البائعين للفلتر
        $sellers = \App\User::saleCommissionAgentsDropdown($business_id, false);

        // تقرير تفصيلي: سطر لكل منتج مُباع مع بائعه
        $rows = DB::table('transaction_sell_lines as tsl')
            ->join('transactions as t', 'tsl.transaction_id', '=', 't.id')
            ->join('users as u', 'tsl.ego_seller_id', '=', 'u.id')
            ->join('products as p', 'tsl.product_id', '=', 'p.id')
            ->leftJoin('variations as v', 'tsl.variation_id', '=', 'v.id')
            ->where('t.business_id', $business_id)
            ->where('t.type', 'sell')
            ->where('t.status', 'final')
            ->whereNotNull('tsl.ego_seller_id')
            ->whereDate('t.transaction_date', '>=', $start)
            ->whereDate('t.transaction_date', '<=', $end)
            ->when(! empty($seller_id), function ($q) use ($seller_id) {
                $q->where('tsl.ego_seller_id', $seller_id);
            })
            ->orderBy('t.transaction_date', 'desc')
            ->select(
                DB::raw("TRIM(CONCAT(COALESCE(u.first_name,''),' ',COALESCE(u.surname,''))) as seller_name"),
                'p.name as product_name',
                'v.sub_sku as barcode',
                't.invoice_no',
                't.transaction_date',
                'u.cmmsn_percent',
                'tsl.quantity as qty',
                DB::raw('(tsl.quantity * tsl.unit_price_inc_tax) as line_total')
            )
            ->get();

        // احتساب عمولة كل سطر = قيمة السطر × نسبة البائع + الإجماليات
        $grand_value = 0; $grand_qty = 0; $grand_commission = 0;
        foreach ($rows as $r) {
            $r->commission = round(((float) $r->line_total) * ((float) $r->cmmsn_percent) / 100, 2);
            $grand_value += (float) $r->line_total;
            $grand_qty += (float) $r->qty;
            $grand_commission += $r->commission;
        }

        return view('reports.ego_seller_commission', compact('rows', 'start', 'end', 'grand_value', 'grand_qty', 'grand_commission', 'sellers', 'seller_id'));
    }
}
