@extends('layouts.app')

@section('title', __('accounting::lang.ledger'))

@section('content')

@include('accounting::layouts.nav')

<section class="content-header">
    <h1 class="tw-text-xl md:tw-text-3xl tw-font-bold tw-text-black">@lang( 'accounting::lang.ledger' ) - {{$account->name}}</h1>
</section>

<section class="content">
    <div class="row">
        <div class="col-md-5">
            <div class="box box-solid">
                <div class="box-body">
                    <table class="table table-condensed">
                        <tr>
                            <th>@lang( 'user.name' ):</th>
                            <td>
                                {{$account->name}}
                                @if(!empty($account->gl_code))
                                    ({{$account->gl_code}})
                                @endif
                            </td>
                        </tr>

                        <tr>
                            <th>@lang( 'accounting::lang.account_type' ):</th>
                            <td>
                                @if(!empty($account->account_primary_type))
                                    {{__('accounting::lang.' . $account->account_primary_type)}}
                                @endif
                            </td>
                        </tr>

                        <tr>
                            <th>@lang( 'accounting::lang.account_sub_type' ):</th>
                            <td>
                                @if(!empty($account->account_sub_type))
                                    {{__('accounting::lang.' . $account->account_sub_type->name)}}
                                @endif
                            </td>
                        </tr>

                        <tr>
                            <th>@lang( 'accounting::lang.detail_type' ):</th>
                            <td>
                                @if(!empty($account->detail_type))
                                    {{__('accounting::lang.' . $account->detail_type->name)}}
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <th>@lang( 'lang_v1.balance' ):</th>
                            <td>
                                <span id="current_balance_display">{{@num_format($current_bal)}}</span> 
                                <small class="currency-symbol">{{$currency_code}}</small>
                            </td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-md-7">
            <div class="box box-solid">
                <div class="box-header">
                    <h3 class="box-title"> <i class="fa fa-filter" aria-hidden="true"></i> @lang('report.filters'):</h3>
                </div>
                <div class="box-body">
                    <div class="row">
                        <div class="col-sm-6">
                            <div class="form-group">
                                {!! Form::label('transaction_date_range', __('report.date_range') . ':') !!}
                                <div class="input-group">
                                    <span class="input-group-addon"><i class="fa fa-calendar"></i></span>
                                    {!! Form::text('transaction_date_range', null, ['class' => 'form-control', 'readonly', 'placeholder' => __('report.date_range')]) !!}
                                </div>
                            </div>
                        </div>

                        <div class="col-sm-6">
                            <div class="form-group">
                                {!! Form::label('account_filter', __( 'accounting::lang.account' ) . ':') !!}
                                {!! Form::select('account_filter', [$account->id => $account->name], $account->id,
                                    ['class' => 'form-control accounts-dropdown', 'style' => 'width:100%', 
                                    'id' => 'account_filter', 'data-default' => $account->id]); !!}
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-sm-6">
                            <div class="form-group">
                                {!! Form::label('currency_id', __('business.currency') . ':') !!}
                                {!! Form::select('currency_id', $currencies, request()->input('currency_id'), ['class' => 'form-control select2', 'style' => 'width:100%', 'id' => 'currency_id', 'placeholder' => __('messages.all')]); !!}
                            </div>
                        </div>

                        <div class="col-sm-6" id="exchange_rate_col">
                            <div class="form-group">
                                {!! Form::label('exchange_rate', 'سعر الصرف:') !!}
                                {!! Form::number('exchange_rate', $exchange_rate, ['class' => 'form-control', 'step' => '0.001', 'id' => 'exchange_rate']); !!}
                            </div>
                        </div>
                    </div>

                                            <div class="col-md-3">
    <div class="form-group">
        {!! Form::label('location_id', 'الفرع:') !!}
        {!! Form::select('location_id', $business_locations, null, ['class' => 'form-control select2', 'placeholder' => 'كافة الفروع', 'id' => 'ledger_location_id']); !!}
    </div>
</div>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="content">
    <div class="row">
        <div class="col-sm-12">
            <div class="box">
                <div class="box-body">
                   @if(auth()->user()->can('superadmin') || auth()->user()->can('accounting.manage_accounts') || auth()->user()->can('accounting.view_reports'))
                        <div class="table-responsive">
                        <table class="table table-bordered table-striped" id="ledger">
                            <thead>
                                <tr>
                                    <th>@lang( 'messages.date' )</th>
                                    <th>@lang( 'lang_v1.description' )</th>
                                    <th>@lang( 'brand.note' )</th>
                                    <th>@lang( 'lang_v1.added_by' )</th>
                                    <th>@lang('account.debit')</th>
                                    <th>@lang('account.credit')</th>
                                    <th>@lang( 'messages.action' )</th>
                                </tr>
                            </thead>
                            <tfoot>
                                <tr class="bg-gray font-17 footer-total text-center">
                                    <td colspan="4"><strong>@lang('sale.total'):</strong></td>
                                    <td class="footer_total_debit"></td>
                                    <td class="footer_total_credit"></td>
                                    <td></td>
                                </tr>
                            </tfoot>
                        </table>
                        </div>
                    @endcan
                </div>
            </div>
        </div>
    </div>
</section>

@stop

@section('javascript')
@include('accounting::accounting.common_js')
<script>
    $(document).ready(function(){
        
        // وظيفة تنظيف واستبدال رموز العملة
        function cleanCurrencySymbols() {
            let selectedCurrencyText = $('#currency_id option:selected').text();
            let symbol = "JD"; 

            if ($('#currency_id').val() !== "" && $('#currency_id').val() !== null) {
                let match = selectedCurrencyText.match(/\(([^)]+)\)/);
                if (match && match[1] !== "JOD") {
                    symbol = match[1];
                } else {
                    symbol = "JD";
                }
            }

            $('.currency-symbol').text(symbol);

            $('#ledger tbody tr td, #ledger tfoot tr td, #current_balance_display, .footer_total_debit, .footer_total_credit').each(function() {
                let currentHtml = $(this).html();
                if (currentHtml && currentHtml.indexOf('<input') === -1) {
                    let updatedHtml = currentHtml.replace(/all|JOD|\$/gi, symbol);
                    if (symbol !== "JD") {
                        updatedHtml = updatedHtml.replace(/JD/gi, symbol);
                    }
                    if (currentHtml !== updatedHtml) {
                        $(this).html(updatedHtml);
                    }
                }
            });
        }

        function toggleExchangeRate() {
            let selectedText = $('#currency_id option:selected').text();
            if (selectedText.includes('JOD') || $('#currency_id').val() == "" || $('#currency_id').val() == null) {
                $('#exchange_rate_col').hide();
                $('#exchange_rate').val(1);
            } else {
                $('#exchange_rate_col').show();
            }
        }

        toggleExchangeRate();
        setTimeout(cleanCurrencySymbols, 300);

        $('#account_filter').change(function(){
            account_id = $(this).val();
            url = base_path + '/accounting/ledger/' + account_id;
            window.location = url;
        });

        // تحديث الفلتر عند تغيير الفرع أو العملة
        $(document).on('change', '#currency_id, #exchange_rate, #ledger_location_id', function() {
            // كشف الحساب يعمل بالـ Ajax، لذا سنقوم بإعادة تحميل الجدول بدلاً من الصفحة كاملة لتحسين الأداء
            // ولكن سنقوم بتحديث الـ URL في المتصفح للحفاظ على حالة الفلتر عند تحديث الصفحة
            let url = new URL(window.location.href);
            url.searchParams.set('currency_id', $('#currency_id').val());
            url.searchParams.set('exchange_rate', $('#exchange_rate').val());
            url.searchParams.set('location_id', $('#ledger_location_id').val());
            window.history.pushState({}, '', url);
            
            toggleExchangeRate();
            ledger.ajax.reload(); // تحديث الجدول فوراً دون ريفريش كامل
        });

        $('#transaction_date_range').daterangepicker(
            dateRangeSettings,
            function (start, end) {
                $('#transaction_date_range').val(start.format(moment_date_format) + ' ~ ' + end.format(moment_date_format));
                ledger.ajax.reload();
            }
        );
        
        // بناء الجدول مع التأكد من مطابقة عدد الأعمدة في الـ HTML
        ledger = $('#ledger').DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: '{{action([\Modules\Accounting\Http\Controllers\CoaController::class, 'ledger'],[$account->id])}}',
                data: function(d) {
                    if($('#transaction_date_range').val()){
                        d.start_date = $('input#transaction_date_range').data('daterangepicker').startDate.format('YYYY-MM-DD');
                        d.end_date = $('input#transaction_date_range').data('daterangepicker').endDate.format('YYYY-MM-DD');
                    }
                    d.location_id = $('#ledger_location_id').val();
                    d.exchange_rate = $('#exchange_rate').val();
                    d.currency_id = $('#currency_id').val();
                }
            },
            ordering: false,
            columns: [
                {data: 'operation_date', name: 'operation_date'},
                {data: 'ref_no', name: 'ATM.ref_no'},
                {data: 'note', name: 'ATM.note', defaultContent: ''}, // أضفنا defaultContent لمنع الخطأ
                {data: 'added_by', name: 'added_by'},
                {data: 'debit', name: 'debit', searchable: false, orderable: false}, // غيرنا الاسم ليتطابق مع addColumn
                {data: 'credit', name: 'credit', searchable: false, orderable: false}, // غيرنا الاسم ليتطابق مع addColumn
                {data: 'action', name: 'action', searchable: false, orderable: false}
            ],
            fnDrawCallback: function (oSettings) {
                __currency_convert_recursively($('#ledger'));
                setTimeout(cleanCurrencySymbols, 150);
            },
            footerCallback: function ( row, data, start, end, display ) {
                var footer_total_debit = 0;
                var footer_total_credit = 0;

                for (var r in data){
                    // جلب القيم الأصلية من الـ data-orig-value التي نرسلها من الكنترولر
                    var debit_val = $(data[r].debit).data('orig-value') || 0;
                    var credit_val = $(data[r].credit).data('orig-value') || 0;
                    footer_total_debit += parseFloat(debit_val);
                    footer_total_credit += parseFloat(credit_val);
                }

                $('.footer_total_debit').html(__currency_trans_from_en(footer_total_debit, true));
                $('.footer_total_credit').html(__currency_trans_from_en(footer_total_credit, true));
            }
        });

        $('#transaction_date_range').on('cancel.daterangepicker', function(ev, picker) {
            $(this).val('');
            ledger.ajax.reload();
        });
    });
</script>


<style>
@media print {
    /* إخفاء نص JD أينما وجد داخل الجدول */
    #ledger td, #ledger th, .display_currency {
        font-size: 0 !important; /* إخفاء النص */
    }
    /* إظهار الأرقام فقط مع الرمز الجديد */
    #ledger td::after, #ledger th::after, .display_currency::after {
        content: attr(data-orig-value) " $"; /* استبدل $ برمزك أو اجعلها متغيرة */
        font-size: 12px !important;
        color: black !important;
    }
}
</style>

@stop