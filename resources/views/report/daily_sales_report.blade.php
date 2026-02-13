@extends('layouts.app')
@section('title',  __('sales_detailed.daily_sales_report'))

@section('content')
<section class="content-header">
    <h1 class="tw-text-xl md:tw-text-3xl tw-font-bold tw-text-black">{{__('sales_detailed.daily_sales_report')}}  </h1>
</section>

<section class="content">
    <div class="row">
        <div class="col-md-12">
            @component('components.filters', ['title' => __('report.filters')])
                <div class="col-md-4">
                    <div class="form-group">
                        {!! Form::label('location_id', __('purchase.business_location').':') !!}
                        {!! Form::select('location_id', $business_locations, null, ['class' => 'form-control select2', 'style' => 'width:100%', 'placeholder' => __('messages.all')]); !!}
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        {!! Form::label('qer_date_filter', __('report.date_range') . ':') !!}
                        {!! Form::text('date_range', null, ['placeholder' => __('lang_v1.select_a_date_range'), 'class' => 'form-control', 'id' => 'qer_date_filter', 'readonly']); !!}
                    </div>
                </div>
                <div class="col-md-4">
                  <div class="form-group">
                   {!! Form::label('view_type', __('sales_detailed.view_type') ) !!}
                   {!! Form::select('view_type', [
                    'grouped' => __('sales_detailed.summary'), 
                    'detailed' => __('sales_detailed.detailed')
                    ], 'grouped', ['class' => 'form-control select2', 'id' => 'view_type', 'style' => 'width:100%']); !!}
                </div>
                </div>
            @endcomponent
        </div>
    </div>

    {{-- الجدول المجمل --}}
    <div class="row" id="grouped_report_div">
        <div class="col-md-12">
            @component('components.widget', ['class' => 'box-primary', 'title' => __('sales_detailed.sales_report_summary')  ])
                <div class="table-responsive">
                    <table class="table table-bordered table-striped" id="daily_sales_grouped_table" style="width: 100%;">
                        <thead>
                            <tr>
                                <th>@lang('messages.date')</th>
                                <th>{{__('sales_detailed.location')}}</th>
                                <th>{{__('sales_detailed.number_of_sales')}} </th>
                                <th> {{__('sales_detailed.total_sales')}}</th>
                                <th>{{__('sales_detailed.number_of_returns')}} </th>
                                <th> {{__('sales_detailed.total_of_returns')}}</th>
                                <th>{{__('sales_detailed.net_sales')}} </th>
                                <th> {{__('sales_detailed.total_before_tax')}}</th>
                                <th> {{__('sales_detailed.tax')}}</th>
                                <th> {{__('sales_detailed.tax_type')}}</th>
                                <th>  {{__('sales_detailed.return_not_paid')}}</th>
                                <th>{{__('sales_detailed.action')}}</th>
                            </tr>
                        </thead>
                        <tfoot>
                            <tr class="bg-gray font-17 footer-total text-center">
                                <td colspan="2"><strong>@lang('sale.total'):</strong></td>
                                <td id="g_footer_total_invoices"></td>
                                <td id="g_footer_total_sales"></td>
                                <td id="g_footer_total_returns_cnt"></td>
                                <td id="g_footer_total_returns_amt"></td>
                                <td id="g_footer_net_sales"></td>
                                <td id="g_footer_total_before_tax"></td>
                                <td id="g_footer_total_tax"></td>
                                <td></td>
                                <td id="g_footer_total_return_due"></td>
                                <td></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            @endcomponent
        </div>
    </div>

    {{-- الجدول التفصيلي --}}
    <div class="row hide" id="detailed_report_div">
        <div class="col-md-12">
            @component('components.widget', ['class' => 'box-success', 'title' => __('sales_detailed.sales_report_detailed') ])
                <div class="table-responsive">
                    <table class="table table-bordered table-striped" id="sales_detailed_table" style="width: 100%;">
                        <thead>
                            <tr>
                                <th> {{__('sales_detailed.date')}}</th>
                                <th>{{__('sales_detailed.ref_no')}} </th>
                                <th>{{__('sales_detailed.location')}}</th>
                                <th>{{__('sales_detailed.customer_name')}}</th>
                                <th>  {{__('sales_detailed.total_before_tax')}} </th>
                                <th> {{__('sales_detailed.tax')}}  </th>
                                <th>{{__('sales_detailed.total')}} </th>
                                <th> {{__('sales_detailed.tax_type')}} </th>
                                <th> {{__('sales_detailed.paid_type')}} </th>
                                <th> {{__('sales_detailed.transaction_method')}}</th> 
                                <th>{{__('sales_detailed.action')}}</th> 
                            </tr>
                        </thead>
                        <tfoot>
                            <tr class="bg-gray font-17 footer-total text-center">
                                <td colspan="4"><strong>@lang('sale.total'):</strong></td>
                                <td id="d_footer_before_tax"></td>
                                <td id="d_footer_tax"></td>
                                <td id="d_footer_final_total"></td>
                                <td colspan="4"></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            @endcomponent
        </div>
    </div>
</section>
@endsection

@section('javascript')
<script type="text/javascript">
    var daily_sales_grouped_table;
    var sales_detailed_table;

    $(document).ready(function() {
        // 1. إعداد الوقت الافتراضي ليكون اليوم كاملاً
        var start = moment().startOf('day'); 
        var end = moment().endOf('day');

        // 2. ضبط القيمة النصية في الحقل فوراً
        $('#qer_date_filter').val(start.format(moment_date_format + ' hh:mm A') + ' ~ ' + end.format(moment_date_format + ' hh:mm A'));

        // 3. إعداد المكتبة
        $('#qer_date_filter').daterangepicker(_.extend({}, dateRangeSettings, {
            timePicker: true,
            timePicker24Hour: false,
            autoUpdateInput: false, // لمنع الكتابة التلقائية بالوقت الحالي
            startDate: start,
            endDate: end,
            locale: { format: moment_date_format + ' hh:mm A' }
        }));

        // 4. معالجة الضغط على Apply لضمان اليوم الكامل
        $('#qer_date_filter').on('apply.daterangepicker', function(ev, picker) {
            var s = picker.startDate.clone();
            var e = picker.endDate.clone();

            // إذا اختار يوماً واحداً، نجبر الوقت على البداية والنهاية
            if (s.format('YYYY-MM-DD') == e.format('YYYY-MM-DD')) {
                s.startOf('day');
                e.endOf('day');
            }

            $(this).val(s.format(moment_date_format + ' hh:mm A') + ' ~ ' + e.format(moment_date_format + ' hh:mm A'));
            
            // تحديث القيم داخل المكتبة ليتم استخدامها في Ajax
            picker.setStartDate(s);
            picker.setEndDate(e);
            
            reload_tables();
        });

        // دالة تنظيف الأرقام
        var get_raw_value = function (i) {
            if (typeof i === 'string') { return parseFloat(i.replace(/[^\d.-]/g, '')) || 0; }
            return typeof i === 'number' ? i : 0;
        };

        // دالة جلب التواريخ لـ Ajax
        var get_filter_dates = function() {
            var picker = $('#qer_date_filter').data('daterangepicker');
            return {
                start: picker.startDate.format('YYYY-MM-DD HH:mm:ss'),
                end: picker.endDate.format('YYYY-MM-DD HH:mm:ss')
            };
        };

        // تعريف الجداول
        daily_sales_grouped_table = $('#daily_sales_grouped_table').DataTable({
            processing: true,
            serverSide: true,
            aaSorting: [[0, 'desc']],
            ajax: {
                url: "{{ action([\App\Http\Controllers\ReportController::class, 'dailySalesReport']) }}",
                data: function(d) {
                    var dates = get_filter_dates();
                    d.start_date = dates.start;
                    d.end_date = dates.end;
                    d.location_id = $('#location_id').val();
                }
            },
            columns: [
                { data: 'date', name: 'date' },
                { data: 'location_name', name: 'bl.name' },
                { data: 'total_invoices', name: 'total_invoices' },
                { data: 'total_sales', name: 'total_sales' },
                { data: 'total_returns_count', name: 'total_returns_count' },
                { data: 'total_return_amount', name: 'total_return_amount' },
                { data: 'net_sales', name: 'net_sales' },
                { data: 'total_before_tax', name: 'total_before_tax' },
                { data: 'total_tax', name: 'total_tax' },
                { data: 'tax_type', name: 'tax_type' },
                { data: 'return_due', name: 'return_due' },
                { data: 'action', name: 'action', orderable: false, searchable: false }
            ],
            footerCallback: function (row, data, start, end, display) {
                var api = this.api();
                $(api.column(2).footer()).html(__number_f(api.column(2).data().reduce((a, b) => get_raw_value(a) + get_raw_value(b), 0)));
                $(api.column(3).footer()).html(__currency_trans_from_en(api.column(3).data().reduce((a, b) => get_raw_value(a) + get_raw_value(b), 0), true));
                $(api.column(4).footer()).html(__number_f(api.column(4).data().reduce((a, b) => get_raw_value(a) + get_raw_value(b), 0)));
                $(api.column(5).footer()).html(__currency_trans_from_en(api.column(5).data().reduce((a, b) => get_raw_value(a) + get_raw_value(b), 0), true));
                $(api.column(6).footer()).html(__currency_trans_from_en(api.column(6).data().reduce((a, b) => get_raw_value(a) + get_raw_value(b), 0), true));
                $(api.column(7).footer()).html(__currency_trans_from_en(api.column(7).data().reduce((a, b) => get_raw_value(a) + get_raw_value(b), 0), true));
                $(api.column(8).footer()).html(__currency_trans_from_en(api.column(8).data().reduce((a, b) => get_raw_value(a) + get_raw_value(b), 0), true));
                $(api.column(10).footer()).html(__currency_trans_from_en(api.column(10).data().reduce((a, b) => get_raw_value(a) + get_raw_value(b), 0), true));
            }
        });

        sales_detailed_table = $('#sales_detailed_table').DataTable({
            processing: true,
            serverSide: true,
            aaSorting: [[0, 'desc']],
            ajax: {
                url: "{{ action([\App\Http\Controllers\ReportController::class, 'detailedSalesReport']) }}",
                data: function(d) {
                    var dates = get_filter_dates();
                    d.start_date = dates.start;
                    d.end_date = dates.end;
                    d.location_id = $('#location_id').val();
                }
            },
            columns: [
                { data: 'transaction_date', name: 'transaction_date' },
                { data: 'invoice_no', name: 'invoice_no' },
                { data: 'location_name', name: 'bl.name' },
                { data: 'customer_name', name: 'c.name' },
                { data: 'line_total_before_tax', name: 'line_total_before_tax' },
                { data: 'line_total_tax', name: 'line_total_tax' },
                { data: 'final_total', name: 'final_total' },
                { data: 'tax_type', name: 'tr.name' },
                { data: 'payment_status', name: 'payment_status' },
                { data: 'payment_methods', name: 'payment_methods', orderable: false },
                { data: 'action', name: 'action', orderable: false, searchable: false }
            ],
            footerCallback: function (row, data, start, end, display) {
                var api = this.api();
                $(api.column(4).footer()).html(__currency_trans_from_en(api.column(4).data().reduce((a, b) => get_raw_value(a) + get_raw_value(b), 0), true));
                $(api.column(5).footer()).html(__currency_trans_from_en(api.column(5).data().reduce((a, b) => get_raw_value(a) + get_raw_value(b), 0), true));
                $(api.column(6).footer()).html(__currency_trans_from_en(api.column(6).data().reduce((a, b) => get_raw_value(a) + get_raw_value(b), 0), true));
            }
        });

        // التبديل وتحديث الفلاتر
        $('#view_type').change(function() {
            var val = $(this).val();
            if(val == 'detailed') {
                $('#grouped_report_div').addClass('hide');
                $('#detailed_report_div').removeClass('hide');
                sales_detailed_table.ajax.reload();
            } else {
                $('#detailed_report_div').addClass('hide');
                $('#grouped_report_div').removeClass('hide');
                daily_sales_grouped_table.ajax.reload();
            }
        });

        $(document).on('change', '#location_id', function() { reload_tables(); });

        // فتح المودال
        $(document).on('click', '.view-daily-details-modal', function(e) {
            e.preventDefault();
            var date = $(this).data('date');
            var location_id = $('#location_id').val();
            var url = "{{ action([\App\Http\Controllers\ReportController::class, 'getDailySalesDetailsModal']) }}";
            $('.view_modal').html('<div class="modal-dialog"><div class="modal-content"><div class="modal-body text-center"><i class="fa fa-spinner fa-spin fa-3x"></i></div></div></div>').modal('show');
            $.ajax({
                method: 'GET',
                url: url,
                dataType: 'html',
                data: { date: date, location_id: location_id },
                success: function(result) { $('.view_modal').html(result); }
            });
        });
    });

    function reload_tables() {
        if (daily_sales_grouped_table) daily_sales_grouped_table.ajax.reload();
        if (sales_detailed_table) sales_detailed_table.ajax.reload();
    }
</script>
@endsection