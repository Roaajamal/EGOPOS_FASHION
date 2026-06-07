@extends('layouts.app')

@section('title', __('accounting::lang.trial_balance'))

@section('content')

@include('accounting::layouts.nav')

<section class="content">
    {{-- الفلاتر --}}
    {!! Form::open(['url' => action([\Modules\Accounting\Http\Controllers\ReportController::class, 'trialBalance']), 'method' => 'get', 'id' => 'trial_balance_filter_form']) !!}
    <div class="row">
        <div class="col-md-3">
            <div class="form-group">
                {!! Form::label('date_range_filter', __('report.date_range') . ':') !!}
                {!! Form::text('date_range_filter', null, 
                    ['placeholder' => __('lang_v1.select_a_date_range'), 
                    'class' => 'form-control', 'readonly', 'id' => 'date_range_filter']); !!}
                <input type="hidden" name="start_date" id="start_date" value="{{$start_date}}">
                <input type="hidden" name="end_date" id="end_date" value="{{$end_date}}">
            </div>
        </div>

        <div class="col-md-3">
    <div class="form-group">
        {!! Form::label('location_id', 'الفرع:') !!}
        {!! Form::select('location_id', $business_locations, request()->get('location_id'), ['class' => 'form-control select2', 'placeholder' => 'كافة الفروع', 'style' => 'width:100%']); !!}
    </div>
</div>

        <div class="col-md-3">
            <div class="form-group">
                {!! Form::label('currency_id', __('business.currency') . ':') !!}
                {!! Form::select('currency_id', $currencies, request()->input('currency_id'), ['class' => 'form-control select2', 'placeholder' => __('messages.all'), 'style' => 'width:100%', 'id' => 'currency_id']); !!}
            </div>
        </div>

        <div class="col-md-3">
            <div class="form-group">
                {!! Form::label('exchange_rate', 'سعر الصرف الحالي:') !!}
                {!! Form::number('exchange_rate', request()->input('exchange_rate', 1), ['class' => 'form-control', 'step' => '0.001', 'id' => 'exchange_rate']); !!}
            </div>
        </div>
        
        <div class="col-md-3">
            <button type="submit" class="btn btn-primary" style="margin-top: 25px;">@lang('report.apply_filters')</button>
        </div>
    </div>
    {!! Form::close() !!}

    <div class="row">
        <div class="col-md-8 col-md-offset-2">
            <div class="box box-warning">
                <div class="box-header with-border text-center">
                    <h2 class="box-title">@lang( 'accounting::lang.trial_balance')</h2>
                    <p>{{@format_date($start_date)}} ~ {{@format_date($end_date)}}</p>
                </div>

                <div class="box-body">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>@lang('user.name')</th>
                                <th>@lang('accounting::lang.debit')</th>
                                <th>@lang('accounting::lang.credit')</th>
                            </tr>
                        </thead>

                        @php
                            $total_debit = 0;
                            $total_credit = 0;
                        @endphp

                       <tbody>
    @foreach($accounts as $account)
        @php
            $total_debit += $account->debit_balance;
            $total_credit += $account->credit_balance;
        @endphp
        <tr>
            <td>{{$account->name}}</td>
            <td>
                @if($account->debit_balance != 0)
                    {{@num_format($account->debit_balance)}}
                    {{-- تم تغيير $account->currency_code إلى $currency_code --}}
                    <small class="text-muted">{{ $currency_code }}</small>
                @endif    
            </td>
            <td>
                @if($account->credit_balance != 0)
                    {{@num_format($account->credit_balance)}}
                    {{-- تم تغيير $account->currency_code إلى $currency_code --}}
                    <small class="text-muted">{{ $currency_code }}</small>
                @endif
            </td>
        </tr>
    @endforeach
</tbody>

<tfoot>
    <tr class="bg-gray font-17 text-center footer-total">
        <th>@lang('sale.total')</th>
        <td>
            {{@num_format($total_debit)}} 
            <small>{{ $currency_code }}</small>
        </td>
        <td>
            {{@num_format($total_credit)}} 
            <small>{{ $currency_code }}</small>
        </td>
    </tr>
</tfoot>
                    </table>
                </div>
            </div>
        </div>
    </div>
</section>

@stop

@section('javascript')
<script type="text/javascript">
    $(document).ready(function(){
        
        // دالة التحكم في ظهور حقل سعر الصرف
        function toggleExchangeRate() {
            let currencySelect = $('#currency_id');
            let selectedText = currencySelect.find('option:selected').text();
            let exchangeRateGroup = $('#exchange_rate').closest('.col-md-3');

            // إذا كانت العملة المختارة هي الأردني (JOD) أو لم يتم اختيار شيء (All)
            if (selectedText.indexOf('JOD') >= 0 || currencySelect.val() === "" || currencySelect.val() === null) {
                $('#exchange_rate').val(1); // إرجاع القيمة لـ 1 تلقائياً
                exchangeRateGroup.hide();    // إخفاء الحقل
            } else {
                exchangeRateGroup.show();    // إظهار الحقل للعملات الأخرى
            }
        }

        // تشغيل الدالة عند تحميل الصفحة أول مرة
        toggleExchangeRate();

        // 1. تحديد العملة الافتراضية JOD إذا كان التقرير يفتح لأول مرة
        let currencySelect = $('#currency_id');
        if (currencySelect.val() === "" || currencySelect.val() === null) {
            currencySelect.find('option').each(function() {
                if ($(this).text().indexOf('JOD') >= 0) {
                    currencySelect.val($(this).val()).trigger('change');
                }
            });
        }

        // 2. معالجة نصوص العملة الظاهرة في الجدول
        $('.text-muted, .footer-total small').each(function() {
            let text = $(this).text().trim();
            if (text.toLowerCase() === 'all' || text === '') {
                $(this).text('JOD');
            }
        });

        // إعدادات التاريخ
        if($('#date_range_filter').length) {
            $('#date_range_filter').daterangepicker(
                dateRangeSettings,
                function (start, end) {
                    $('#date_range_filter').val(start.format(moment_date_format) + ' ~ ' + end.format(moment_date_format));
                    $('#start_date').val(start.format('YYYY-MM-DD'));
                    $('#end_date').val(end.format('YYYY-MM-DD'));
                    $('#trial_balance_filter_form').submit();
                }
            );
        }

        // عند تغيير العملة
        $(document).on('change', '#currency_id, #location_id', function() {
            toggleExchangeRate(); // تحديث ظهور الحقل
            $('#trial_balance_filter_form').submit();
        });

        // عند تغيير سعر الصرف يدوياً
        $(document).on('change', '#exchange_rate', function() {
            $('#trial_balance_filter_form').submit();
        });
    });
</script>
@stop