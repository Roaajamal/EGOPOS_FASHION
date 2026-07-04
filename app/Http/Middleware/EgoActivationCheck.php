<?php

namespace App\Http\Middleware;

use App\EgoActivation;
use Closure;

// 🆕 حظر الدخول عند انتهاء التفعيل (يُستثنى الأدمن + صفحة الانتهاء + الخروج)
class EgoActivationCheck
{
    public function handle($request, Closure $next)
    {
        try {
            if (! auth()->check()) {
                return $next($request);
            }
            // الأدمن مستثنى دائماً
            if (app(\App\Utils\BusinessUtil::class)->is_admin(auth()->user())) {
                return $next($request);
            }
            $business_id = $request->session()->get('user.business_id') ?? auth()->user()->business_id;
            if (empty($business_id)) {
                return $next($request);
            }
            $days = EgoActivation::daysLeft($business_id);
            // منتهٍ (days < 0) → حظر عدا صفحة الانتهاء والخروج وطلب/اعتماد التجديد
            if ($days !== null && $days < 0) {
                // 🆕 مسارات مسموحة أثناء الانتهاء: صفحة الانتهاء، طلب التجديد، اعتماد/رفض التجديد، الخروج، الدخول
                $allowed = $request->routeIs('ego-activation.expired')
                    || $request->routeIs('ego-activation.request')
                    || $request->routeIs('ego-activation.approve')
                    || $request->routeIs('ego-activation.reject')
                    || $request->routeIs('ego-activation.index')
                    || $request->routeIs('logout')
                    || $request->is('logout')
                    || $request->is('login');
                if ($allowed) {
                    return $next($request);
                }
                return redirect()->route('ego-activation.expired');
            }
        } catch (\Throwable $e) {
            // fail-open: أي خطأ لا يحظر
        }

        return $next($request);
    }
}
