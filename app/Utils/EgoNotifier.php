<?php

namespace App\Utils;

use Illuminate\Support\Facades\DB;

// 🆕 مولّد الإشعارات الذكية (تفعيل/مخزون/توصيل) — إشعار واحد يومياً لكل نوع
class EgoNotifier
{
    public static function generate($user, $business_id)
    {
        if (empty($user) || empty($business_id)) {
            return;
        }
        // 1) قرب/انتهاء التفعيل
        try {
            $days = \App\EgoActivation::daysLeft($business_id);
            if ($days !== null && $days <= 3) {
                $msg = $days < 0 ? 'انتهى تفعيل النظام — يرجى التجديد' : ('تنبيه: تفعيل النظام ينتهي خلال ' . $days . ' يوم');
                self::make($user, 'ego_activation', $msg, $days < 0 ? 'fas fa-times-circle bg-red' : 'fas fa-exclamation-triangle bg-yellow', url('ego-activation'));
            }
        } catch (\Throwable $e) {}
        // 2) مخزون منخفض
        try {
            $low = DB::table('variation_location_details as vld')
                ->join('products as p', 'vld.product_id', '=', 'p.id')
                ->where('p.business_id', $business_id)->where('p.enable_stock', 1)
                ->whereNotNull('p.alert_quantity')->where('p.alert_quantity', '>', 0)
                ->whereRaw('vld.qty_available <= p.alert_quantity')
                ->distinct('p.id')->count('p.id');
            if ($low > 0) {
                self::make($user, 'ego_low_stock', $low . ' منتج قارب على النفاد', 'fas fa-cubes bg-yellow', url('products'));
            }
        } catch (\Throwable $e) {}
        // 3) طلبات توصيل
        try {
            $del = DB::table('transactions')->where('business_id', $business_id)->where('type', 'sell')
                ->whereIn('shipping_status', ['ordered', 'pending', 'packed', 'shipped'])->count();
            if ($del > 0) {
                self::make($user, 'ego_delivery', $del . ' طلب توصيل قيد التنفيذ', 'fas fa-truck bg-light-blue', url('sells'));
            }
        } catch (\Throwable $e) {}
    }

    private static function make($user, $key, $msg, $icon_class, $link)
    {
        try {
            $already = $user->notifications()->whereDate('created_at', \Carbon\Carbon::today())->get()
                ->contains(function ($n) use ($key) { return ! empty($n->data[$key]); });
            if ($already) {
                return;
            }
            DB::table('notifications')->insert([
                'id' => (string) \Illuminate\Support\Str::uuid(),
                'type' => 'App\\Notifications\\EgoGeneric',
                'notifiable_type' => 'App\\User',
                'notifiable_id' => $user->id,
                'data' => json_encode(['ego_generic' => true, $key => true, 'msg' => $msg, 'icon_class' => $icon_class, 'link' => $link]),
                'read_at' => null,
                'created_at' => \Carbon\Carbon::now(),
                'updated_at' => \Carbon\Carbon::now(),
            ]);
        } catch (\Throwable $e) {
            \Log::error('EgoNotifier: ' . $e->getMessage());
        }
    }
}
