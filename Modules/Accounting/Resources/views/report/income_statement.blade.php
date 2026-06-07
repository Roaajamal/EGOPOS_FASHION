@extends('layouts.app')
@section('title', 'تقرير قائمة الدخل')

@section('content')
@include('accounting::layouts.nav')

<section class="content-header">
    <h1 class="tw-text-xl md:tw-text-3xl tw-font-bold tw-text-black">تقرير قائمة الدخل (الأرباح والخسائر)</h1>
</section>

<section class="content">
    {!! Form::open(['url' => route('accounting.incomeStatement'), 'method' => 'get', 'id' => 'income_statement_filter_form']) !!}
    <div class="row">
        <div class="col-md-3">
            <div class="form-group">
                {!! Form::label('location_id', __('purchase.business_location') . ':') !!}
                {!! Form::select('location_id', $business_locations, $location_id, ['class' => 'form-control select2', 'id' => 'location_id', 'placeholder' => __('messages.all')]); !!}
            </div>
        </div>
        <div class="col-md-3">
            <div class="form-group">
                {!! Form::label('date_range_filter', __('report.date_range') . ':') !!}
                {!! Form::text('date_range_filter', null, ['class' => 'form-control', 'readonly', 'id' => 'date_range_filter']); !!}
                <input type="hidden" name="start_date" id="start_date" value="{{$start_date}}">
                <input type="hidden" name="end_date" id="end_date" value="{{$end_date}}">
            </div>
        </div>
        <div class="col-md-3">
            <div class="form-group">
                {!! Form::label('currency_id', __('business.currency') . ':') !!}
                {!! Form::select('currency_id', $currencies, request()->input('currency_id'), ['class' => 'form-control select2', 'id' => 'currency_id', 'placeholder' => __('messages.all')]); !!}
            </div>
        </div>
        <div class="col-md-3" id="exchange_rate_col">
            <div class="form-group">
                {!! Form::label('exchange_rate', 'سعر الصرف:') !!}
                {!! Form::number('exchange_rate', $exchange_rate, ['class' => 'form-control', 'step' => '0.001', 'id' => 'exchange_rate']); !!}
            </div>
        </div>
    </div>
    {!! Form::close() !!}

    <div class="row">
        <div class="col-md-10 col-md-offset-1">
            <div class="box box-warning">
                <div class="box-header with-border text-center">
                    <h2 class="box-title">قائمة الدخل التفصيلية</h2>
                    <p>{{@format_date($start_date)}} ~ {{@format_date($end_date)}}</p>
                </div>

                <div class="box-body">
                    <table class="table table-bordered table-striped" id="income_statement_table">
                        <thead>
                            <tr class="bg-gray">
                                <th>البند</th>
                                <th class="text-right">المبلغ</th>
                            </tr>
                        </thead>
                        <tbody>
                            {{-- الإيرادات --}}
                            <tr class="tw-font-bold" style="background-color: #f9f9f9;">
                                <td>صافي إيرادات المبيعات</td>
                                <td class="text-right">
                                    {{@num_format($total_revenue)}} 
                                    <small class="currency-symbol">{{$currency_code}}</small>
                                </td>
                            </tr>

                            {{-- تفصيل تكلفة البضاعة --}}
                            <tr>
                                <td colspan="2" class="bg-gray-light"><strong>تكلفة البضاعة المباعة (COGS)</strong></td>
                            </tr>
                            <tr>
                                <td style="padding-right: 30px;">مخزون أول المدة (+)</td>
                                <td class="text-right">{{@num_format($opening_inventory)}}</td>
                            </tr>
                            <tr>
                                <td style="padding-right: 30px;">المشتريات (+)</td>
                                <td class="text-right">{{@num_format($total_purchase)}}</td>
                            </tr>
                            <tr>
                                <td style="padding-right: 30px;">مخزون آخر المدة (-)</td>
                                <td class="text-right">({{@num_format($closing_inventory)}})</td>
                            </tr>
                            <tr class="text-danger tw-font-bold">
                                <td>إجمالي تكلفة المبيعات</td>
                                <td class="text-right">(-) {{@num_format($total_cogs)}}</td>
                            </tr>

                            {{-- مجمل الربح --}}
                            <tr class="bg-gray tw-font-bold">
                                <td>مجمل الربح (Gross Profit)</td>
                                <td class="text-right">
                                    {{@num_format($gross_profit)}} 
                                    <small class="currency-symbol">{{$currency_code}}</small>
                                </td>
                            </tr>

                            {{-- المصاريف الأخرى --}}
                            <tr>
                                <td class="tw-font-semibold">المصاريف الإدارية والعمومية والرواتب</td>
                                <td class="text-right text-danger">
                                    (-) {{@num_format($total_admin_expenses)}} 
                                    <small class="currency-symbol">{{$currency_code}}</small>
                                </td>
                            </tr>
                        </tbody>
                        <tfoot>
                            @php
                                $bg_color = $net_profit >= 0 ? '#e6fffa' : '#fff5f5';
                                $text_color = $net_profit >= 0 ? '#2c7a7b' : '#c53030';
                            @endphp
                            <tr style="background-color: {{ $bg_color }} !important; color: {{ $text_color }} !important; font-weight: bold; font-size: 1.3em;">
                                <td>صافي الربح / الخسارة</td>
                                <td class="text-right">
                                    {{@num_format($net_profit)}} 
                                    <small class="currency-symbol" style="color: inherit;">{{$currency_code}}</small>
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
    function fixCurrencySymbols() {
        $('.currency-symbol').each(function() {
            let text = $(this).text().trim().toLowerCase();
            if (text === 'all' || text === 'الكل' || text === '') { $(this).text('JOD'); }
        });
    }

    function toggleExchangeRate() {
        let selectedText = $('#currency_id option:selected').text();
        if (selectedText.includes('JOD') || $('#currency_id').val() == "") {
            $('#exchange_rate_col').hide();
            $('#exchange_rate').val(1);
        } else {
            $('#exchange_rate_col').show();
        }
    }

    toggleExchangeRate();
    fixCurrencySymbols();

    $('#currency_id, #exchange_rate, #location_id').change(function() {
        if($(this).attr('id') == 'currency_id') toggleExchangeRate();
        $('#income_statement_filter_form').submit();
    });

    $('#date_range_filter').daterangepicker(dateRangeSettings, function (start, end) {
        $('#date_range_filter').val(start.format(moment_date_format) + ' ~ ' + end.format(moment_date_format));
        $('#start_date').val(start.format('YYYY-MM-DD'));
        $('#end_date').val(end.format('YYYY-MM-DD'));
        $('#income_statement_filter_form').submit();
    });
    
    $('#date_range_filter').val('{{@format_date($start_date)}} ~ {{@format_date($end_date)}}');
});
</script>
@stop