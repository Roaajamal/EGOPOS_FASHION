@extends('layouts.app')
@section('title', __('report.payment_method_report') )

@section('content')

<section class="content-header">
    <h1>{{__('report.payment_method_report')}}</h1>
</section>

<section class="content">
    <div class="row">
        <div class="col-md-12">
            @component('components.filters', ['title' => __('report.filters')])
                <div class="col-md-4">
                    <div class="form-group">
                        {!! Form::label('location_id_filter',  __('purchase.business_location') . ':') !!}
                        {!! Form::select('location_id_filter', $business_locations, null, ['class' => 'form-control select2', 'style' => 'width:100%', 'placeholder' => __('lang_v1.all')]); !!}
                    </div>
                </div>
                
                {{-- الفلتر الموحد للوقت والتاريخ --}}
                <div class="col-md-6">
                    <div class="form-group">
                        {!! Form::label('full_datetime_range', __('report.date_range') . ' (تاريخ ووقت):') !!}
                        <div class="input-group">
                            <span class="input-group-addon"><i class="fa fa-calendar"></i></span>
                            {!! Form::text('full_datetime_range', null, ['class' => 'form-control', 'id' => 'full_datetime_range', 'readonly', 'placeholder' => __('lang_v1.select_a_date_range')]); !!}
                        </div>
                    </div>
                </div>
            @endcomponent
        </div>
    </div>

    <div class="row">
        <div class="col-md-12">
            @component('components.widget', ['class' => 'box-primary', 'title' => __('report.summary_of_financial_activity') ])
                <div class="table-responsive">
                    <table class="table table-bordered table-striped" id="sales_report_table" style="width: 100%;">
                        <thead>
                            <tr class="bg-blue">
                                <th>{{ __('report.financial_activity')}}</th>
                                <th>{{__('report.number_of_transaction')}} </th>
                                        <th> {{__('report.amount')}}</th>
                            </tr>
                        </thead>
                        <tfoot>
                            <tr class="bg-gray font-17">
                                <td colspan="2"><b>{{__('report.total_sales')}}</b></td>
                                <td id="footer_total_combined"></td>
                            </tr>
                            <tr style="background-color: #eee8e8ff !important; color: #373737ff;" class="font-17">
                                <td colspan="2"><b>{{__('report.total_return_paid')}}</b></td>
                                <td id="footer_return_paid"></td>
                            </tr>  
                            <tr style="background-color: #eee8e8ff !important; color: #373737ff;" class="font-17">
                                <td colspan="2"><b>{{__('report.total_expense_paid')}} </b></td>
                                <td id="footer_total_expenses"></td>
                            </tr>
                            <tr style="background-color: #dff0d8 !important; color: #3c763d;" class="font-18">
                                <td colspan="2"><b>{{__('report.total_net_cash')}}</b></td>
                                <td id="footer_final_net_cash" style="font-weight: bold; border: 2px solid #3c763d;"></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            @endcomponent
        </div>
    </div>

    <div class="row" style="margin-top: 20px;">
        <div class="col-md-12">
            @component('components.widget', ['class' => 'box-danger', 'title' => __('report.returns') ])
                <div class="table-responsive">
                    <table class="table table-bordered table-striped" id="returns_details_table">
                        <thead>
                            <tr class="bg-red">
                                <th>{{__('report.return_type')}} </th>
                                <th>{{__('report.number_of_transaction')}} </th>
                                <th>{{__('report.amount')}} </th>
                            </tr>
                        </thead>
                        <tbody id="returns_details_body">
                        </tbody>
                    </table>
                </div>
            @endcomponent
        </div>
    </div>
</section>
@endsection

@section('javascript')
<script type="text/javascript">
    $(document).ready(function() {
        
        // 1. إعداد تاريخ اليوم الافتراضي
        var start = moment().startOf('day');
        var end = moment().endOf('day');

        // 2. تفعيل DateRangePicker مع الاختصارات والوقت
        if ($('#full_datetime_range').length) {
            $('#full_datetime_range').daterangepicker(
                _.extend({}, dateRangeSettings, {
                    timePicker: true,
                    timePicker24Hour: false,
                    startDate: start,
                    endDate: end,
                    ranges: {
                        'اليوم': [moment().startOf('day'), moment().endOf('day')],
                        'أمس': [moment().subtract(1, 'days').startOf('day'), moment().subtract(1, 'days').endOf('day')],
                        'آخر 7 أيام': [moment().subtract(6, 'days').startOf('day'), moment().endOf('day')],
                        'هذا الشهر': [moment().startOf('month'), moment().endOf('month')],
                    },
                    locale: {
                        format: moment_date_format + ' hh:mm A'
                    }
                }),
                function (start, end) {
                    $('#full_datetime_range').val(start.format(moment_date_format + ' hh:mm A') + ' ~ ' + end.format(moment_date_format + ' hh:mm A'));
                    sales_report_table.ajax.reload();
                }
            );
            
            $('#full_datetime_range').val(start.format(moment_date_format + ' hh:mm A') + ' ~ ' + end.format(moment_date_format + ' hh:mm A'));
        }

        // 3. تعريف DataTable
        sales_report_table = $('#sales_report_table').DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: '/reports/payment-method-report',
                data: function(d) {
                    d.location_id = $('#location_id_filter').val();
                    
                    if ($('#full_datetime_range').val()) {
                        var picker = $('#full_datetime_range').data('daterangepicker');
                        let startDate = picker.startDate.clone().format('YYYY-MM-DD HH:mm:ss');
                        let endDate = picker.endDate.clone().format('YYYY-MM-DD HH:mm:ss');

                        // معالجة اختيار اليوم الواحد بدون وقت محدد
                        if (picker.startDate.isSame(picker.endDate, 'day') && 
                            picker.startDate.format('HH:mm:ss') === picker.endDate.format('HH:mm:ss')) {
                            startDate = picker.startDate.clone().startOf('day').format('YYYY-MM-DD HH:mm:ss');
                            endDate = picker.endDate.clone().endOf('day').format('YYYY-MM-DD HH:mm:ss');
                        }

                        d.start_date = startDate;
                        d.end_date = endDate;
                    }
                },
                dataSrc: function (json) {
                    if(json.error) {
                        toastr.error(json.error);
                        return [];
                    }
                    $('#footer_total_combined').html(__currency_trans_from_en(json.grand_total_combined, true));
                    $('#footer_return_paid').html(__currency_trans_from_en(json.total_return_paid, true));
                    $('#footer_total_expenses').html(__currency_trans_from_en(json.total_cash_expenses, true));
                    $('#footer_final_net_cash').html(__currency_trans_from_en(json.final_net_cash, true));
                    
                    var returns_html = '';
                    $.each(json.returns_details, function(i, item) {
                        returns_html += '<tr>' +
                            '<td>' + item.method_label + '</td>' +
                            '<td class="text-center">' + item.total_count + '</td>' +
                            '<td>' + __currency_trans_from_en(item.total_amount, true) + '</td>' +
                            '</tr>';
                    });
                    $('#returns_details_body').html(returns_html);
                    return json.data;
                }
            },
            columns: [
                { data: 'method_label', name: 'method_label' },
                { data: 'total_count', name: 'total_count', className: 'text-center' },
                { 
                    data: 'total_amount', 
                    name: 'total_amount',
                    render: function(data) {
                        return __currency_trans_from_en(data, true);
                    }
                }
            ]
        });

        $(document).on('change', '#location_id_filter', function() {
            sales_report_table.ajax.reload();
        });
    });
</script>
@endsection