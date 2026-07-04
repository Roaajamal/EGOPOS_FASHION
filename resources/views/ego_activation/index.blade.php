@extends('layouts.app')
@section('title', 'تفعيل النظام')

@section('css')
<style>
    .ego-act-wrap{max-width:1000px;margin:0 auto}
    .ego-card{background:#fff;border:1px solid #eef0f4;border-radius:16px;box-shadow:0 2px 14px rgba(15,23,42,.05);margin-bottom:20px;overflow:hidden}
    .ego-card-h{padding:14px 20px;border-bottom:1px solid #f1f3f7;font-weight:800;color:#0f172a;display:flex;align-items:center;gap:8px}
    .ego-card-h i{color:#0d9488}
    .ego-card-b{padding:20px}
    /* بطاقة الحالة */
    .ego-status{display:flex;align-items:center;gap:22px;flex-wrap:wrap;background:linear-gradient(135deg,#0d9488,#0f766e);color:#fff;padding:22px 24px;border-radius:16px;margin-bottom:20px}
    .ego-status .days{display:flex;flex-direction:column;align-items:center;justify-content:center;background:rgba(255,255,255,.16);border-radius:14px;padding:12px 20px;min-width:110px}
    .ego-status .days b{font-size:34px;line-height:1;font-weight:900}
    .ego-status .days span{font-size:12px;opacity:.9;margin-top:4px}
    .ego-status .meta{display:flex;flex-direction:column;gap:6px}
    .ego-status .meta .row1{font-size:15px;font-weight:800}
    .ego-status .chip{display:inline-block;background:rgba(255,255,255,.18);border-radius:20px;padding:3px 12px;font-size:13px;font-weight:700;margin-inline-end:6px}
    .ego-badge{padding:5px 14px;border-radius:20px;font-weight:800;font-size:13px}
    .ego-badge.ok{background:#dcfce7;color:#166534}.ego-badge.soon{background:#fef3c7;color:#92400e}.ego-badge.exp{background:#fee2e2;color:#991b1b}
    .ego-field label{font-size:12px;font-weight:700;color:#475569;display:block;margin-bottom:4px}
    .ego-field .form-control{border-radius:10px}
    .ego-btn{background:#0d9488;color:#fff;font-weight:700;border:none;border-radius:10px;padding:9px 20px}
    .ego-btn:hover{filter:brightness(1.06);color:#fff}
    .ego-tbl{width:100%}
    .ego-tbl th{background:#f8fafc;color:#334155;font-weight:800;font-size:13px;padding:10px 12px;border-bottom:2px solid #e2e8f0}
    .ego-tbl td{padding:10px 12px;border-bottom:1px solid #f1f5f9;font-size:13px}
</style>
@endsection

@section('content')
<section class="content-header">
    <h1><i class="fas fa-key" style="color:#0d9488"></i> تفعيل النظام</h1>
</section>

<section class="content">
    <div class="ego-act-wrap">
        @php
            $expired = $days_left !== null && $days_left < 0;
            $soon = $days_left !== null && $days_left >= 0 && $days_left <= 3;
            $unitLbl = function ($u) { return $u == 'year' ? 'سنة' : ($u == 'day' ? 'يوم' : 'شهر'); };
        @endphp

        {{-- بطاقة الحالة --}}
        @if($current)
        <div class="ego-status">
            <div class="days">
                <b>{{ $expired ? abs($days_left) : $days_left }}</b>
                <span>{{ $expired ? 'يوم منذ الانتهاء' : 'يوم متبقٍّ' }}</span>
            </div>
            <div class="meta">
                <div class="row1">
                    <span class="ego-badge {{ $expired ? 'exp' : ($soon ? 'soon' : 'ok') }}">{{ $expired ? 'منتهٍ' : ($soon ? 'قارب الانتهاء' : 'مُفعّل') }}</span>
                </div>
                <div>
                    <span class="chip"><i class="fas fa-play"></i> يبدأ: {{ $current->start_date->format('d-m-Y') }}</span>
                    <span class="chip"><i class="fas fa-flag-checkered"></i> ينتهي: {{ $current->end_date->format('d-m-Y') }}</span>
                </div>
            </div>
        </div>
        @else
        <div class="ego-card"><div class="ego-card-b" style="text-align:center;color:#94a3b8">لا يوجد تفعيل بعد.</div></div>
        @endif

        @if($is_admin)
        {{-- تعيين / تمديد (أدمن) --}}
        <div class="ego-card">
            <div class="ego-card-h"><i class="fas fa-sliders-h"></i> تعيين / تمديد التفعيل</div>
            <div class="ego-card-b">
                <form method="POST" action="{{ route('ego-activation.store') }}" style="display:flex;gap:14px;flex-wrap:wrap;align-items:flex-end">
                    @csrf
                    <div class="ego-field"><label>المدة</label><input type="number" name="duration_value" min="1" value="1" class="form-control" style="width:90px" required></div>
                    <div class="ego-field"><label>الوحدة</label>
                        <select name="duration_unit" class="form-control"><option value="day">يوم</option><option value="month">شهر</option><option value="year">سنة</option></select>
                    </div>
                    <div class="ego-field"><label>تاريخ البداية (اختياري)</label><input type="date" name="start_date" class="form-control"></div>
                    <div class="ego-field" style="flex:1;min-width:160px"><label>ملاحظة</label><input type="text" name="note" class="form-control"></div>
                    <button type="submit" class="ego-btn"><i class="fas fa-check"></i> حفظ</button>
                </form>
                <p class="help-block" style="margin-top:10px">إن تركت تاريخ البداية فارغاً يبدأ من اليوم.</p>
            </div>
        </div>

        {{-- طلبات التجديد --}}
        <div class="ego-card">
            <div class="ego-card-h"><i class="fas fa-sync-alt"></i> طلبات التجديد</div>
            <div class="ego-card-b table-responsive">
                <table class="ego-tbl">
                    <thead><tr><th>المدة</th><th>ملاحظة</th><th>الحالة</th><th>التاريخ</th><th>إجراء</th></tr></thead>
                    <tbody>
                        @forelse($pending_requests as $r)
                            <tr>
                                <td>{{ $r->duration_value }} {{ $unitLbl($r->duration_unit) }}</td>
                                <td>{{ $r->note }}</td>
                                <td>
                                    @if($r->status == 'pending')<span class="ego-badge soon">بانتظار</span>
                                    @elseif($r->status == 'approved')<span class="ego-badge ok">معتمد</span>
                                    @else<span class="ego-badge exp">مرفوض</span>@endif
                                </td>
                                <td>{{ $r->created_at->format('d-m-Y h:i A') }}</td>
                                <td>
                                    @if($r->status == 'pending')
                                        <form method="POST" action="{{ route('ego-activation.approve', $r->id) }}" style="display:inline">@csrf
                                            <button class="btn btn-xs" style="background:#16a34a;color:#fff;border-radius:6px">اعتماد وتمديد</button>
                                        </form>
                                        <form method="POST" action="{{ route('ego-activation.reject', $r->id) }}" style="display:inline">@csrf
                                            <button class="btn btn-xs btn-danger" style="border-radius:6px">رفض</button>
                                        </form>
                                    @else <span class="text-muted">—</span> @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="text-center text-muted">لا توجد طلبات</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @else
        {{-- طلب تجديد (مستخدم بالصلاحية) --}}
        <div class="ego-card">
            <div class="ego-card-h"><i class="fas fa-sync-alt"></i> طلب تجديد الاشتراك</div>
            <div class="ego-card-b">
                <form method="POST" action="{{ route('ego-activation.request') }}" style="display:flex;gap:14px;flex-wrap:wrap;align-items:flex-end">
                    @csrf
                    <div class="ego-field"><label>المدة</label><input type="number" name="duration_value" min="1" value="1" class="form-control" style="width:90px" required></div>
                    <div class="ego-field"><label>الوحدة</label>
                        <select name="duration_unit" class="form-control"><option value="day">يوم</option><option value="month">شهر</option><option value="year">سنة</option></select>
                    </div>
                    <div class="ego-field" style="flex:1;min-width:200px"><label>ملاحظة / طلب خاص</label><input type="text" name="note" class="form-control"></div>
                    <button type="submit" class="ego-btn"><i class="fas fa-paper-plane"></i> إرسال الطلب</button>
                </form>
                <p class="help-block" style="margin-top:10px">يُرسَل الطلب للمدير لاعتماده.</p>
            </div>
        </div>
        @endif

        {{-- السجل --}}
        <div class="ego-card">
            <div class="ego-card-h"><i class="fas fa-history"></i> سجل التفعيلات</div>
            <div class="ego-card-b table-responsive">
                <table class="ego-tbl">
                    <thead><tr><th>البداية</th><th>الانتهاء</th><th>المدة</th><th>ملاحظة</th><th>أُنشئ في</th></tr></thead>
                    <tbody>
                        @forelse($history as $h)
                            <tr>
                                <td>{{ $h->start_date->format('d-m-Y') }}</td>
                                <td>{{ $h->end_date->format('d-m-Y') }}</td>
                                <td>{{ $h->duration_value }} {{ $unitLbl($h->duration_unit) }}</td>
                                <td>{{ $h->note }}</td>
                                <td>{{ $h->created_at->format('d-m-Y h:i A') }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="text-center text-muted">لا يوجد سجل</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</section>
@endsection
