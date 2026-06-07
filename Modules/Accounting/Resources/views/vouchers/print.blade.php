@extends('layouts.app')
@section('title', 'طباعة سند')

@section('content')
<div class="container">
    <div class="row no-print" style="margin-bottom: 20px;">
        <div class="col-md-12 text-center">
            <button class="btn btn-primary btn-lg" onclick="window.print();">
                <i class="fa fa-print"></i> طـبـاعـة
            </button>
        </div>
    </div>

    @if($voucher->type == 'journal')
        {{-- ========================================================== --}}
        {{-- تصميم سند القيد (نفس المعلومات والترتيب في كودك السابق تماماً) --}}
        {{-- ========================================================== --}}
        <div class="voucher-box" style="border: 1px solid #000; padding: 20px; background: #fff; direction: rtl;">
            <div class="text-center">
                <h3 style="font-weight: bold;">سند قيد يومية</h3>
                <p>رقم السند: {{ $voucher->voucher_no }} | التاريخ: {{ @format_date($voucher->operation_date) }}</p>
            </div>

            <div class="row" style="margin-top: 20px; font-size: 16px; line-height: 2;">
                <div class="col-xs-12">
                    <p style="font-size: 18px;"><strong>البيان العام (الغرض من القيد):</strong></p>
                    <p style="border: 1px solid #eee; padding: 10px; min-height: 60px;">{{ $voucher->note }}</p>
                </div>
            </div>

            <div style="margin-top: 30px;">
                <p><strong>التوجيه المحاسبي (القيد المزدوج):</strong></p>
                <table class="table table-bordered">
                    <thead>
                        <tr style="background: #f2f2f2;">
                            <th class="text-center">الحساب المدين (منه)</th>
                            <th class="text-center">الحساب الدائن (إليه)</th>
                            <th class="text-center">القيمة</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td class="text-center">{{ $transactions->where('type', 'debit')->first()->account->name ?? '---' }}</td>
                            <td class="text-center">{{ $transactions->where('type', 'credit')->first()->account->name ?? '---' }}</td>
                            <td class="text-center" style="font-weight: bold;">{{ @num_format($voucher->amount) }} د.أ</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="row" style="margin-top: 60px; text-align: center;">
                <div class="col-xs-4">
                    <strong>توقيع المستفيد</strong><br><br>
                    <span>..........................</span>
                </div>
                <div class="col-xs-4">
                    <strong>المحاسب</strong><br><br>
                    <span>..........................</span>
                </div>
                <div class="col-xs-4">
                    <strong>المدير العام</strong><br><br>
                    <span>..........................</span>
                </div>
            </div>
        </div>

    @else
        {{-- ========================================================== --}}
        {{-- تصميم سند القبض والصرف (بناءً على طلبك بالدينار الأردني) --}}
        {{-- ========================================================== --}}
        <div class="voucher-box" style="border: 2px solid #000; padding: 25px; background: #fff; max-width: 850px; margin: 0 auto; direction: rtl;">
            
            <div class="row" style="margin-bottom: 20px;">
                <div class="col-xs-6">
                    <h3 style="margin: 0; font-weight: bold;">{{ request()->session()->get('business.name') }}</h3>
                </div>
                <div class="col-xs-6 text-left">
                    <h2 style="margin: 0; font-weight: bold;">{{ $voucher->type == 'receipt' ? 'سند قبض' : 'سند صرف' }}</h2>
                    <div style="margin-top: 5px;">№ {{ $voucher->voucher_no }}</div>
                </div>
            </div>

            <div class="row" style="margin-bottom: 20px;">
                <div class="col-xs-6">
                    <strong>التاريخ:</strong> {{ @format_date($voucher->operation_date) }}
                </div>
                <div class="col-xs-6 text-left">
                    <div style="border: 1px solid #000; padding: 5px 15px; display: inline-block;">
                        <strong>دينار J.D</strong>
                        <div style="font-size: 18px; font-weight: bold;">{{ @num_format($voucher->amount) }}</div>
                    </div>
                </div>
            </div>

            <div style="font-size: 17px; line-height: 2.5;">
                <div class="row">
                    <div class="col-xs-12">
                        <span>{{ $voucher->type == 'receipt' ? 'وصلنا من السيد/ة:' : 'يُصرف للسيد/ة:' }}</span>
                        <span style="border-bottom: 1px dotted #000; display: inline-block; width: 75%; font-weight: bold; padding-right: 10px;">{{ $voucher->received_from }}</span>
                    </div>
                </div>

                <div class="row" style="margin-top: 10px;">
                    <div class="col-xs-12">
                        <strong>مبلغ وقدره:</strong>
                        <span style="border-bottom: 1px dotted #000; display: inline-block; width: 85%; font-weight: bold; padding-right: 10px;">{{ $voucher->amount_in_words }} فقط لا غير.</span>
                    </div>
                </div>

                <div class="row" style="margin-top: 10px;">
                    <div class="col-xs-12">
                        <strong>وذلك عن:</strong>
                        <span style="border-bottom: 1px dotted #000; display: inline-block; width: 85%; padding-right: 10px;">{{ $voucher->note }}</span>
                    </div>
                </div>
            </div>

            <div class="row" style="margin-top: 50px; text-align: center;">
                <div class="col-xs-4">
                    <strong>توقيع المستلم</strong><br><br>
                    <span>..........................</span>
                </div>
                <div class="col-xs-4">
                    <strong>المحاسب</strong><br><br>
                    <span>..........................</span>
                </div>
                <div class="col-xs-4">
                    <strong>المدير العام</strong><br><br>
                    <span>..........................</span>
                </div>
            </div>
        </div>
    @endif
</div>

<style>
    @media print {
        .no-print { display: none; }
        .voucher-box { width: 100% !important; max-width: 100% !important; border: 2px solid #000 !important; }
    }
</style>

<script type="text/javascript">
    $(document).ready(function () {
        // بمجرد تحميل الصفحة تفتح نافذة الطباعة
        window.print();
        
        // بعد إغلاق نافذة الطباعة أو إلغائها، يمكن إعادة المستخدم لصفحة الإضافة أو الجدول
        window.onafterprint = function() {
            window.location.href = "{{ action('\Modules\Accounting\Http\Controllers\VoucherController@index') }}";
        };
    });
</script>
@endsection