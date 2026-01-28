@extends('layouts.app')
@section('title', __('report.sales_representative'))

@section('content')

<section class="content-header">
    <h1 class="tw-text-xl md:tw-text-3xl tw-font-bold tw-text-black">{{ __('report.sales_representative')}}</h1>
</section>

<style>
    #sales_rep_summary_table { width: 100% !important; }
    
    #custom_buttons_div { 
        display: block !important; 
        margin-bottom: 20px;
        text-align: center;
        min-height: 40px;
    }
#sales_rep_summary_table_wrapper .dt-buttons {
        visibility: hidden;
    }
    
    #custom_buttons_div .btn {
        background-color: transparent !important;
        border: 1px solid #c1c1c1ff !important;
        color: #959292ff !important;
        margin:  4px;
        padding: 5px 12px;
        font-size: 10px;
        border-radius: 8px;
        box-shadow: none !important;
    }

    #custom_buttons_div .btn:hover {
        background-color: #f4f4f4 !important;
        border-color: #adadad !important;
        color: #333 !important;
    }
</style>

<section class="content">
    <div class="row">
        <div class="col-md-12">
            @component('components.filters', ['title' => __('report.filters')])
              {!! Form::open(['url' => action([\App\Http\Controllers\ReportController::class, 'getStockReport']), 'method' => 'get', 'id' => 'sales_representative_filter_form' ]) !!}
                <div class="col-md-4">
                    <div class="form-group">
                        {!! Form::label('sr_id',  __('report.user') . ':') !!}
                        {!! Form::select('sr_id', $users, null, ['class' => 'form-control select2', 'style' => 'width:100%', 'placeholder' => __('report.all_users')]); !!}
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        {!! Form::label('sr_business_id',  __('business.business_location') . ':') !!}
                        {!! Form::select('sr_business_id', $business_locations, null, ['class' => 'form-control select2', 'style' => 'width:100%']); !!}
                    </div>
                </div>
                <div class="col-md-3">
              <div class="form-group">
              {!! Form::label('full_datetime_range', __('report.date_range') . ':') !!}
              {!! Form::text('full_datetime_range', null, ['placeholder' => __('lang_v1.select_a_date_range'), 'class' => 'form-control', 'id' => 'full_datetime_range', 'readonly']); !!}             </div>
             </div>
                {!! Form::close() !!}
            @endcomponent
        </div>
    </div>
    <!-- summary table -->

    <div class="row">
        <div class="col-sm-12">
            @component('components.widget', ['title' => __('report.summary')])
                <div id="custom_buttons_div"></div>
                <div class="table-responsive">
                    <table class="table table-bordered table-striped" id="sales_rep_summary_table">
                        <thead>
                            <tr class="bg-gray text-black">
                                <th>{{ __('report.user') }}</th>
                                <th>{{ __('report.total_sell') }}</th>
                                <th>{{ __('lang_v1.total_sales_return') }}</th>
                                <th>{{ __('report.net_sales') }} </th>
                                <th>{{ __('report.total_expense') }}</th>
                                <th>{{ __('report.commission_rate') }} </th> 
                                <th>{{ __('lang_v1.total_sale_commission') }}</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                        <tfoot>
                            <tr class="bg-gray font-17 text-bold">
                                <td>{{ __('report.total') }}</td>
                                <td id="footer_total_sell"></td>
                                <td id="footer_total_return"></td>
                                <td id="footer_net_sales"></td>
                                <td id="footer_total_expense"></td>
                                <td></td>
                                <td id="footer_total_commission"></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            @endcomponent 
        </div>
    </div>
       <!-- summary table -->
    {{-- Tabs Section --}}
    <div class="row">
        <div class="col-md-12">
            <div class="nav-tabs-custom">
                <ul class="nav nav-tabs">
                    <li class="active">
                        <a href="#sr_sales_tab" data-toggle="tab"><i class="fa fa-cog"></i> @lang('lang_v1.sales_added')</a>
                    </li>
                    <li>
                        <a href="#sr_commission_tab" data-toggle="tab"><i class="fa fa-cog"></i> @lang('lang_v1.sales_with_commission')</a>
                    </li>
                    <li>
                        <a href="#sr_expenses_tab" data-toggle="tab"><i class="fa fa-cog"></i> @lang('expense.expenses')</a>
                    </li>
                </ul>
                <div class="tab-content">
                    <div class="tab-pane active" id="sr_sales_tab">
                        @include('report.partials.sales_representative_sales')
                    </div>
                    <div class="tab-pane" id="sr_commission_tab">
                        @include('report.partials.sales_representative_commission')
                    </div>
                    <div class="tab-pane" id="sr_expenses_tab">
                        @include('report.partials.sales_representative_expenses')
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

@endsection

@section('javascript')
    <script src="{{ asset('js/report.js?v=' . $asset_v) }}"></script>
    <script type="text/javascript">

    $(document).ready(function() {

        var start = moment().startOf('day');
        var end = moment().endOf('day');
        // 1. تهيئة حقل التاريخ والوقت مع الخيارات الكاملة
       // 1. تهيئة حقل التاريخ والوقت لتكون القيمة الافتراضية هي "اليوم"
      if ($('#full_datetime_range').length) {
            $('#full_datetime_range').daterangepicker(
               _.extend({}, dateRangeSettings, {
        timePicker: true,
        timePicker24Hour: false, // تحويله إلى false لنظام 12 ساعة
        startDate: start,
        endDate: end,
        locale: {
            format: moment_date_format + ' hh:mm A' // hh للوقت الصغير و A لـ AM/PM
        }
    }),
               function (start, end) {
        // تحديث النص بالتنسيق الجديد
        $('#full_datetime_range').val(start.format(moment_date_format + ' hh:mm A') + ' ~ ' + end.format(moment_date_format + ' hh:mm A'));
        updateSummaryTable();
    }
            );
            
            // وضع القيمة الابتدائية (اليوم) في الحقل عند تحميل الصفحة مباشرة
            $('#full_datetime_range').val(start.format(moment_date_format + ' HH:mm') + ' ~ ' + end.format(moment_date_format + ' HH:mm'));
        }
        $(document).on('click', '#show_column', function(e) {
            e.preventDefault();
            e.stopPropagation();

            if ($.fn.DataTable.isDataTable('#sales_rep_summary_table')) {
                var table = $('#sales_rep_summary_table').DataTable();
                // التأكد من أن زر colvis موجود في تعريف DataTable
                table.button('.buttons-colvis').trigger();
                
                var btnOffset = $(this).offset();
                var btnHeight = $(this).outerHeight();
                
                setTimeout(function() {
                    $('.dt-button-collection').css({
                        'top': (btnOffset.top + btnHeight) + 'px',
                        'left': btnOffset.left + 'px',
                        'z-index': '9999' // تأكد من ظهور القائمة فوق العناصر الأخرى
                    });
                }, 10);
            }
        });

        // 2. دالة التحديث الرئيسية
       function updateSummaryTable() {
            var start_dt = '';
            var end_dt = '';
            var picker = $('#full_datetime_range').data('daterangepicker');
            
            if (picker) {
                start_dt = picker.startDate.format('YYYY-MM-DD HH:mm:ss');
                end_dt = picker.endDate.format('YYYY-MM-DD HH:mm:ss');
            }

            $.ajax({
                url: "{{ action([\App\Http\Controllers\ReportController::class, 'getSalesRepresentativeSummary']) }}",
                data: {
                    user_id: $('#sr_id').val(),
                    location_id: $('#sr_business_id').val(),
                    start_date: start_dt,
                    end_date: end_dt
                },
                dataType: 'json',
                success: function(result) {
                    // ... (بقية كود معالجة البيانات و drawTable كما هو لديك)
                    var total_sell = 0, total_return = 0, net_sales_total = 0, total_expense = 0, total_commission = 0;
                    var processed_data = $.map(result, function(row) {
                        var sell = parseFloat(row.total_sell || 0);
                        var s_return = parseFloat(row.total_sell_return || 0);
                        var net = sell - s_return;
                        var expense = parseFloat(row.total_expense || 0);
                        var comm = parseFloat(row.total_commission || 0);
                        total_sell += sell;
                        total_return += s_return;
                        net_sales_total += net;
                        total_expense += expense;
                        total_commission += comm;
                        return {
                            name: row.name,
                            total_sell: sell,
                            total_sell_return: s_return,
                            net_sales: net,
                            total_expense: expense,
                            commission_percentage: row.commission_percentage,
                            total_commission: comm
                        };
                    });
                    drawTable(processed_data);
                    $('#footer_total_sell').html(__currency_trans_from_en(total_sell, true));
                    $('#footer_total_return').html(__currency_trans_from_en(total_return, true));
                    $('#footer_net_sales').html(__currency_trans_from_en(net_sales_total, true));
                    $('#footer_total_expense').html(__currency_trans_from_en(total_expense, true));
                    $('#footer_total_commission').html(__currency_trans_from_en(total_commission, true));
                }
            });
        }

        // 3. دالة رسم الـ DataTable
        function drawTable(data) {
            if ($.fn.DataTable.isDataTable('#sales_rep_summary_table')) {
                $('#sales_rep_summary_table').DataTable().destroy();
            }

            var table = $('#sales_rep_summary_table').DataTable({
                data: data,
                columns: [
                    { data: 'name', render: function(data) { return '<b>' + data + '</b>'; } },
                    { data: 'total_sell', render: function(data) { return '<span class="display_currency" data-currency_symbol="true">' + data + '</span>'; } },
                    { data: 'total_sell_return', render: function(data) { return '<span class="display_currency" data-currency_symbol="true">' + data + '</span>'; } },
                    { data: 'net_sales', render: function(data) { return '<span class="display_currency" data-currency_symbol="true">' + data + '</span>'; } },
                    { data: 'total_expense', render: function(data) { return '<span class="display_currency" data-currency_symbol="true">' + data + '</span>'; } },
                    { data: 'commission_percentage', render: function(data) { return parseFloat(data || 0).toFixed(2) + '%'; } },
                    { data: 'total_commission', render: function(data) { return '<span class="display_currency" data-currency_symbol="true">' + data + '</span>'; } }
                ],
                dom: 'Bfrtip',
               buttons: [
                                { extend: 'csv', text: '<i class="fa fa-file-csv"></i> تصدير إلى CSV', className: 'btn btn-default btn-sm' },
                                { extend: 'excel', text: '<i class="fa fa-file-excel"></i> تصدير إلى Excel', className: 'btn btn-default btn-sm' },
                                { extend: 'print', text: '<i class="fa fa-print"></i> طباعة', className: 'btn btn-default btn-sm' },
                                { extend: 'collection', text: '<i class="fa fa-columns"></i> رؤية العمود', className: 'btn btn-default btn-sm', buttons: ['columnsToggle'] }
                            ],
                "fnDrawCallback": function (oSettings) {
                    __currency_convert_recursively($('#sales_rep_summary_table'));
                }
            });

            // نقل أزرار التصدير للمكان المخصص
            $('#custom_buttons_div').html('');
            table.buttons().container().appendTo('#custom_buttons_div');
        }

        // 4. الأحداث (Events)
        $(document).on('change', '#sr_id, #sr_business_id', function() {
            updateSummaryTable();
        });

      $('#full_datetime_range').on('apply.daterangepicker', function() {
            updateSummaryTable();
            if(typeof updateSalesRepresentativeReport === 'function'){
        updateSalesRepresentativeReport();
    }
        });

        // التحميل الأول
        updateSummaryTable();
        updateSalesRepresentativeReport();
    });
</script>
@endsection