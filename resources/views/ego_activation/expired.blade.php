@extends('layouts.app')
@section('title', 'التفعيل منتهٍ')

@section('content')
<section class="content" style="min-height:72vh;display:flex;align-items:center;justify-content:center">
    <div class="box box-solid" style="max-width:540px;width:100%">
        <div class="box-body" style="padding:34px;text-align:center">
            <i class="fas fa-lock" style="font-size:52px;color:#dc2626"></i>
            <h2 style="color:#dc2626;font-weight:800;margin-top:12px">انتهى تفعيل النظام</h2>
            @if($current)
                <p style="color:#64748b">انتهى الاشتراك بتاريخ <b>{{ \Carbon\Carbon::parse($current->end_date)->format('d-m-Y') }}</b></p>
            @endif
            <p style="color:#334155">يرجى إرسال طلب تجديد ليعتمده المدير ويُستأنف العمل.</p>

            <form method="POST" action="{{ route('ego-activation.request') }}" style="margin-top:18px;display:flex;gap:10px;flex-wrap:wrap;justify-content:center;align-items:flex-end">
                @csrf
                <div class="form-group" style="margin:0"><label>المدة</label><input type="number" name="duration_value" min="1" value="1" class="form-control" style="width:80px" required></div>
                <div class="form-group" style="margin:0"><label>الوحدة</label>
                    <select name="duration_unit" class="form-control"><option value="day">يوم</option><option value="month">شهر</option><option value="year">سنة</option></select>
                </div>
                <div class="form-group" style="margin:0;flex:1;min-width:170px"><label>ملاحظة / طلب خاص</label><input type="text" name="note" class="form-control"></div>
                <button type="submit" class="btn" style="background:#0d9488;color:#fff;font-weight:700"><i class="fas fa-sync-alt"></i> إرسال طلب تجديد</button>
            </form>

            <a href="{{ url('logout') }}" class="btn btn-default" style="margin-top:18px"><i class="fas fa-sign-out-alt"></i> تسجيل الخروج</a>
        </div>
    </div>
</section>
@endsection
