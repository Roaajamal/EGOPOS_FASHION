@extends('layouts.app')
@section('title', __('accounting::lang.balance_sheet'))

@section('content')
@include('accounting::layouts.nav')

<section class="content-header">
    <h1 class="tw-text-xl md:tw-text-3xl tw-font-bold tw-text-black">@lang( 'accounting::lang.balance_sheet' )</h1>
</section>

<section class="content">
    {!! Form::open(['url' => action([\Modules\Accounting\Http\Controllers\ReportController::class, 'balanceSheet']), 'method' => 'get', 'id' => 'balance_sheet_filter_form']) !!}
    <div class="row">
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
        {!! Form::label('location_id', 'الفرع:') !!}
        {!! Form::select('location_id', $business_locations, request()->get('location_id'), ['class' => 'form-control select2', 'placeholder' => 'كافة الفروع', 'style' => 'width:100%']); !!}
    </div>
</div>
        <div class="col-md-3">
            <div class="form-group">
                {!! Form::label('currency_id', __('business.currency') . ':') !!}
                {!! Form::select('currency_id', $currencies, request()->input('currency_id'), ['class' => 'form-control select2', 'style' => 'width:100%', 'id' => 'currency_id', 'placeholder' => __('messages.all')]); !!}
            </div>
        </div>
        <div class="col-md-3" id="exchange_rate_col">
            <div class="form-group">
                {!! Form::label('exchange_rate', 'سعر الصرف الحالي:') !!}
                {!! Form::number('exchange_rate', $exchange_rate, ['class' => 'form-control', 'step' => '0.001', 'id' => 'exchange_rate']); !!}
            </div>
        </div>
    </div>
    {!! Form::close() !!}

    <div class="row">
        <div class="col-md-10 col-md-offset-1">
            <div class="box box-warning">
                <div class="box-header with-border text-center">
                    <h2 class="box-title">@lang( 'accounting::lang.balance_sheet')</h2>
                    <p>{{@format_date($start_date)}} ~ {{@format_date($end_date)}}</p>
                </div>
                <div class="box-body" id="balance_sheet_table_container">
                    @php $total_assets = 0; $total_liab_owners = 0; @endphp
                    <table class="table table-bordered" id="balance_sheet_table">
                        <thead>
                            <tr class="bg-gray">
                                <th>@lang( 'accounting::lang.assets')</th>
                                <th>@lang( 'accounting::lang.liab_owners_capital')</th>
                            </tr>
                        </thead>
                        <tr>
                            <td>
                                <table class="table table-condensed">
                                    @foreach($assets as $asset)
                                        @php $total_assets += $asset->balance @endphp
                                        <tr>
                                            <td>{{$asset->name}}</td>
                                            <td class="text-right">
                                                {{@num_format($asset->balance)}} 
                                                <small class="currency-symbol">{{$currency_code}}</small>
                                            </td>
                                        </tr>
                                    @endforeach
                                </table>
                            </td>
                            <td>
                                <table class="table table-condensed">
                                    @foreach($liabilities as $liability)
                                        @php $total_liab_owners += $liability->balance @endphp
                                        <tr>
                                            <td>{{$liability->name}}</td>
                                            <td class="text-right">
                                                {{@num_format($liability->balance)}} 
                                                <small class="currency-symbol">{{$currency_code}}</small>
                                            </td>
                                        </tr>
                                    @endforeach
                                    @foreach($equities as $equity)
                                        @php $total_liab_owners += $equity->balance @endphp
                                        <tr>
                                            <td>{{$equity->name}}</td>
                                            <td class="text-right">
                                                {{@num_format($equity->balance)}} 
                                                <small class="currency-symbol">{{$currency_code}}</small>
                                            </td>
                                        </tr>
                                    @endforeach
                                </table>
                            </td>
                        </tr>
                        <tr class="bg-gray" style="font-weight: bold">
                            <td>Total: {{@num_format($total_assets)}} <small class="currency-symbol">{{$currency_code}}</small></td>
                            <td>Total: {{@num_format($total_liab_owners)}} <small class="currency-symbol">{{$currency_code}}</small></td>
                        </tr>
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
        
        // وظيفة لتنظيف رموز العملة من كلمة all
        function cleanCurrencySymbols() {
            $('.currency-symbol').each(function() {
                let text = $(this).text().trim().toLowerCase();
                // إذا كان النص "all" أو فارغ أو يحتوي على ترجمة كلمة "الكل"
                if (text === 'all' || text === '' || text === 'الكل') {
                    $(this).text('JOD');
                }
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

        // تشغيل الوظائف عند التحميل
        toggleExchangeRate();
        cleanCurrencySymbols();

        $('#date_range_filter').daterangepicker(dateRangeSettings, function (start, end) {
            $('#date_range_filter').val(start.format(moment_date_format) + ' ~ ' + end.format(moment_date_format));
            $('#start_date').val(start.format('YYYY-MM-DD'));
            $('#end_date').val(end.format('YYYY-MM-DD'));
            $('#balance_sheet_filter_form').submit();
        });

        $(document).on('change', '#currency_id, #exchange_rate, #location_id', function() {
            if($(this).attr('id') == 'currency_id') toggleExchangeRate();
            $('#balance_sheet_filter_form').submit();
        });

        // التأكد من التشغيل حتى لو حدث تحديث جزئي
        $(document).ajaxComplete(function() {
            cleanCurrencySymbols();
        });
    });
</script>
@stop