<?php

namespace App\Http\Controllers;

use App\EgoActivation;
use App\EgoRenewalRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

// 🆕 إدارة تفعيل النظام + طلبات التجديد
class EgoActivationController extends Controller
{
    // 🆕 إضافة مدة (يوم/شهر/سنة) لتاريخ
    public static function addDuration($date, $unit, $value)
    {
        if ($unit === 'year') { return $date->addYears($value); }
        if ($unit === 'day') { return $date->addDays($value); }
        return $date->addMonths($value);
    }

    private function isAdmin()
    {
        try {
            return app(\App\Utils\BusinessUtil::class)->is_admin(auth()->user());
        } catch (\Throwable $e) {
            return false;
        }
    }

    public function index(Request $request)
    {
        $business_id = $request->session()->get('user.business_id');
        $current = EgoActivation::current($business_id);
        $history = EgoActivation::where('business_id', $business_id)->orderByDesc('id')->limit(30)->get();
        $days_left = EgoActivation::daysLeft($business_id);
        $is_admin = $this->isAdmin();
        $pending_requests = $is_admin
            ? EgoRenewalRequest::where('business_id', $business_id)->orderByDesc('id')->limit(30)->get()
            : collect();

        return view('ego_activation.index', compact('current', 'history', 'days_left', 'is_admin', 'pending_requests'));
    }

    // تعيين/تمديد مباشر (أدمن فقط)
    public function store(Request $request)
    {
        if (! $this->isAdmin()) { abort(403); }
        $request->validate([
            'duration_value' => 'required|integer|min:1',
            'duration_unit' => 'required|in:day,month,year',
            'start_date' => 'nullable|date',
        ]);

        $business_id = $request->session()->get('user.business_id');
        $start = ! empty($request->start_date) ? \Carbon\Carbon::parse($request->start_date) : \Carbon\Carbon::now();
        $end = self::addDuration($start->copy(), $request->duration_unit, (int) $request->duration_value);

        EgoActivation::create([
            'business_id' => $business_id,
            'start_date' => $start->format('Y-m-d'),
            'end_date' => $end->format('Y-m-d'),
            'duration_value' => (int) $request->duration_value,
            'duration_unit' => $request->duration_unit,
            'note' => $request->note,
            'created_by' => auth()->id(),
        ]);

        return redirect()->route('ego-activation.index')->with('status', ['success' => 1, 'msg' => 'تم تعيين/تمديد التفعيل بنجاح']);
    }

    // إنشاء طلب تجديد (الأدمن أو مستخدم يملك صلاحية ego.notification_bell)
    public function requestRenewal(Request $request)
    {
        if (! $this->isAdmin() && ! auth()->user()->can('ego.notification_bell')) { abort(403); }
        $request->validate([
            'duration_value' => 'required|integer|min:1',
            'duration_unit' => 'required|in:day,month,year',
        ]);
        $business_id = $request->session()->get('user.business_id');
        $req = EgoRenewalRequest::create([
            'business_id' => $business_id,
            'requested_by' => auth()->id(),
            'duration_value' => (int) $request->duration_value,
            'duration_unit' => $request->duration_unit,
            'note' => $request->note,
            'status' => 'pending',
        ]);

        // إشعار لمالك البزنس (الأدمن)
        try {
            $ownerId = \App\Business::where('id', $business_id)->value('owner_id');
            if ($ownerId) {
                DB::table('notifications')->insert([
                    'id' => (string) \Illuminate\Support\Str::uuid(),
                    'type' => 'App\\Notifications\\EgoGeneric',
                    'notifiable_type' => 'App\\User',
                    'notifiable_id' => $ownerId,
                    'data' => json_encode([
                        'ego_generic' => true,
                        'msg' => 'طلب تجديد جديد (' . $req->duration_value . ' ' . ($req->duration_unit == 'year' ? 'سنة' : ($req->duration_unit == 'day' ? 'يوم' : 'شهر')) . ') — بانتظار الموافقة',
                        'icon_class' => 'fas fa-sync-alt bg-green',
                        'link' => url('ego-activation'),
                    ]),
                    'read_at' => null,
                    'created_at' => \Carbon\Carbon::now(),
                    'updated_at' => \Carbon\Carbon::now(),
                ]);
            }
        } catch (\Throwable $e) {}

        return redirect()->back()->with('status', ['success' => 1, 'msg' => 'تم إرسال طلب التجديد — بانتظار موافقة المدير']);
    }

    // اعتماد طلب تجديد → تمديد الاشتراك (أدمن فقط)
    public function approveRenewal(Request $request, $id)
    {
        if (! $this->isAdmin()) { abort(403); }
        $business_id = $request->session()->get('user.business_id');
        $req = EgoRenewalRequest::where('business_id', $business_id)->where('id', $id)->where('status', 'pending')->firstOrFail();

        $cur = EgoActivation::current($business_id);
        $base = ($cur && \Carbon\Carbon::parse($cur->end_date)->isFuture()) ? \Carbon\Carbon::parse($cur->end_date) : \Carbon\Carbon::now();
        $end = self::addDuration($base->copy(), $req->duration_unit, $req->duration_value);

        EgoActivation::create([
            'business_id' => $business_id,
            'start_date' => $base->format('Y-m-d'),
            'end_date' => $end->format('Y-m-d'),
            'duration_value' => $req->duration_value,
            'duration_unit' => $req->duration_unit,
            'note' => 'تجديد معتمد لطلب #' . $req->id,
            'created_by' => auth()->id(),
        ]);
        $req->update(['status' => 'approved', 'reviewed_by' => auth()->id(), 'reviewed_at' => \Carbon\Carbon::now()]);

        return redirect()->route('ego-activation.index')->with('status', ['success' => 1, 'msg' => 'تم اعتماد التجديد وتمديد الاشتراك حتى ' . $end->format('Y-m-d')]);
    }

    // رفض طلب (أدمن فقط)
    public function rejectRenewal(Request $request, $id)
    {
        if (! $this->isAdmin()) { abort(403); }
        $business_id = $request->session()->get('user.business_id');
        $req = EgoRenewalRequest::where('business_id', $business_id)->where('id', $id)->where('status', 'pending')->firstOrFail();
        $req->update(['status' => 'rejected', 'reviewed_by' => auth()->id(), 'reviewed_at' => \Carbon\Carbon::now()]);

        return redirect()->route('ego-activation.index')->with('status', ['success' => 1, 'msg' => 'تم رفض الطلب']);
    }

    // صفحة "التفعيل منتهٍ" (تظهر للمستخدمين المحظورين)
    public function expired()
    {
        $business_id = session('user.business_id');
        $current = EgoActivation::current($business_id);
        return view('ego_activation.expired', compact('current'));
    }
}
