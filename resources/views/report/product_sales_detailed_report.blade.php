@extends('layouts.app')
@section('title', __('sales_detailed.sales_returns_report'))

@section('content')
<section class="content-header">
    <h1 class="tw-text-xl md:tw-text-3xl tw-font-bold tw-text-black"> {{__('sales_detailed.sales_returns_report') }}  </h1>
</section>

<style>
    .report-div { margin-top: 20px; }

    /* تحسين حاوية الجدول للسماح بالتمرير */
    .dataTables_scrollBody {
        overflow-x: auto !important;
        overflow-y: auto !important;
        max-height: 70vh !important; /* تحديد ارتفاع مناسب للتمرير العمودي */
    }

    /* تثبيت الرأس (thead) */
    .table thead th { 
        position: sticky !important; 
        top: 0 !important; 
        background-color: #f4f4f4 !important; 
        z-index: 30 !important; 
        box-shadow: 0 2px 2px -1px rgba(0,0,0,0.4); 
        text-align: center; 
        vertical-align: middle; 
        white-space: nowrap;
    }

    


    /* تثبيت الفوتر (tfoot) */
    .table tfoot td { 
        position: sticky !important; 
        bottom: 0 !important; 
        background-color: #efefef !important; 
        z-index: 30 !important; 
        font-weight: bold; 
        text-align: center;
        box-shadow: 0 -2px 2px -1px rgba(0,0,0,0.2);
    }

    

    .table td { text-align: center; vertical-align: middle; white-space: nowrap !important; }
    .text-red { color: #dd4b39 !important; font-weight: bold; }
    .text-green { color: #00a65a !important; font-weight: bold; }
</style>

<section class="content">
    <div class="row">
        <div class="col-md-12">
            @component('components.filters', ['title' => __('report.filters')])
                <div class="col-md-3">
                    <div class="form-group">
                        {!! Form::label('location_id',__('sales_detailed.location')) !!}
                        {!! Form::select('location_id', $business_locations, null, ['class' => 'form-control select2', 'id' => 'location_id', 'style' => 'width:100%', 'placeholder' =>  __('sales_detailed.all')]); !!}
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        {!! Form::label('transaction_type', __('sales_detailed.transaction_type')) !!}
                        {!! Form::select('transaction_type',
                         ['all' => __('sales_detailed.All_sales_returns') ,   
                         'sell' => __('sales_detailed.sales'), 
                         'sell_return' => __('sales_detailed.returns')],
                         'all', ['class' => 'form-control select2',
                         'id' => 'transaction_type', 'style' => 'width:100%']); !!}
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        {!! Form::label('view_type', __('sales_detailed.view_type')) !!}
                        {!! Form::select('view_type', 
                        ['summary' => __('sales_detailed.summary'), 
                        'detailed' => __('sales_detailed.detailed')], 
                        'detailed', ['class' => 'form-control select2', 
                        'id' => 'view_type', 'style' => 'width:100%']); !!}
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        {!! Form::label('date_filter', __('sales_detailed.range_time')) !!}
                        {!! Form::text('date_range', null, ['class' => 'form-control', 'id' => 'qer_date_filter', 'readonly']); !!}
                    </div>
                </div>
            @endcomponent
        </div>
    </div>

    {{--- 1. جداول "الكل" (مدمج) ---}}
    <div class="row report-div hide" id="all_detailed_div">
        <div class="col-md-12">
            <!-- الكل تفصيلي -->
            @component('components.widget', 
            ['class' => 'box-info', 'title' =>  __('sales_detailed.all_datailed') ] )
                <table class="table table-bordered table-striped" id="all_detailed_table" style="width:100%">
                    <thead>
                        <tr>
                            <th>{{__('sales_detailed.date')}}</th>
                            <th>{{__('sales_detailed.transaction_type')}}</th>
                            <th> {{__('sales_detailed.ref_no')}}</th>
                            <th>{{__('sales_detailed.customer_name')}}</th>
                            <th>SKU</th>
                            <th>{{__('sales_detailed.product_name')}}</th>
                            <th>{{__('sales_detailed.quantity')}}</th>
                            <th>{{__('sales_detailed.total')}}</th>
                            <th>{{__('sales_detailed.cash')}}</th>
                            <th>{{__('sales_detailed.card')}}</th>
                            <th>{{__('sales_detailed.due')}}</th>
                            <th>{{__('sales_detailed.location')}}</th>
                        </tr>
                    </thead>
                    <tfoot>
                        <tr class="bg-gray">
                            <td colspan="6"> {{__('sales_detailed.total_net')}}:</td>
                            <td id="all_det_qty"></td>
                            <td id="all_det_total"></td>
                            <td id="all_det_cash"></td>
                            <td id="all_det_card"></td>
                            <td id="all_det_due"></td>
                            <td></td>
                        </tr>
                    </tfoot>
                </table>
            @endcomponent
        </div>
    </div>

    <div class="row report-div hide" id="all_summary_div">
        <div class="col-md-12">
            <!-- الكل مجمل -->
            @component('components.widget',
            ['class' => 'box-info', 'title' =>  __('sales_detailed.all_summary') ])
                <table class="table table-bordered table-striped" id="all_summary_table" style="width:100%">
                    <thead>
                        <tr>
                            <th>{{__('sales_detailed.date')}}</th>
                            <th>{{__('sales_detailed.transaction_type')}}</th>
                            <th>{{__('sales_detailed.ref_no')}} </th>
                            <th>{{__('sales_detailed.customer_name')}}</th>
                            <th>{{__('sales_detailed.total')}}</th>
                            <th>{{__('sales_detailed.transaction_type')}}</th>
                            <th>{{__('sales_detailed.location')}}</th>
                    </tr>
                </thead>
                    <tfoot>
                        <tr class="bg-gray">
                            <td>{{__('sales_detailed.total_net')}}:</td>
                            <td colspan="3"></td>
                            <td id="all_sum_total"></td>
                            <td colspan="2"></td>
                        </tr>
                        </tfoot>
                </table>
            @endcomponent
        </div>
    </div>

    {{--- 2. جداول "المبيعات" ---}}
    <div class="row report-div hide" id="sell_detailed_div">
        <div class="col-md-12">
            <!-- مبيعات تفصيلي --> 
            @component('components.widget', ['class' => 'box-success', 'title' =>   __('sales_detailed.sales_detailed') ])
                <table class="table table-bordered table-striped" id="sell_detailed_table" style="width:100%">
                    <thead>
                            <tr>
                                <th>{{__('sales_detailed.date')}}</th>
                                <th> {{__('sales_detailed.ref_no')}}</th>
                                <th>{{__('sales_detailed.customer_name')}}</th>
                                <th>SKU</th>
                                <th>{{__('sales_detailed.product_name')}}</th>
                                <th>{{__('sales_detailed.quantity')}}</th>
                                <th> {{__('sales_detailed.total_before_tax')}}</th>
                                <th> {{__('sales_detailed.total_after_tax')}}</th>
                                <th>{{__('sales_detailed.total')}}</th>
                                <th>{{__('sales_detailed.cash')}}</th>
                                <th>{{__('sales_detailed.card')}}</th>
                                <th>{{__('sales_detailed.due')}}</th>
                                <th>{{__('sales_detailed.location')}}</th>
                            </tr>
                        </thead>
                        <tfoot>
                            <tr class="bg-gray">
                                <td colspan="5">  {{__('sales_detailed.total_daily_sales') }}:</td>
                                <td id="sell_det_qty"></td>
                                <td></td>
                                <td></td>
                                <td id="sell_det_total"></td>
                                <td id="sell_det_cash"></td>
                                <td id="sell_det_card"></td>
                                <td id="sell_det_due"></td>
                                <td></td>
                        </tr>
                        </tfoot>
                </table>
            @endcomponent
        </div>
    </div>

    <div class="row report-div hide" id="sell_summary_div">
        <div class="col-md-12">
            <!--  مبيعات مجمل --> 
            @component('components.widget', ['class' => 'box-success', 'title' =>  __('sales_detailed.sales_summary') ])
                <table class="table table-bordered table-striped" id="sell_summary_table" style="width:100%">
                    <thead>
                        <tr>
                            <th>{{__('sales_detailed.date')}}</th>
                            <th> {{__('sales_detailed.ref_no')}}</th>
                            <th>{{__('sales_detailed.customer_name')}}</th>
                            <th> {{__('sales_detailed.total_before_tax')}}</th>
                            <th>{{__('sales_detailed.tax')}}</th>
                            <th>{{__('sales_detailed.discount')}}</th>
                            <th> {{__('sales_detailed.total')}}</th>
                            <th>{{__('sales_detailed.paid')}}</th>
                            <th>{{__('sales_detailed.location')}}</th>
                        </tr>
                    </thead>
                    <tfoot>
                        <tr class="bg-gray">
                            <td>{{__('sales_detailed.total_net')}}:</td>
                            <td colspan="2"></td>
                            <td id="sell_sum_orig"></td>
                            <td id="sell_sum_tax"></td>
                            <td id="sell_sum_discount"></td>
                            <td id="sell_sum_total"></td>
                            <td colspan="2"></td>
                        </tr>
                    </tfoot>
                </table>
            @endcomponent
        </div>
    </div>

    {{--- 3. جداول "المرتجعات" ---}}
    <div class="row report-div hide" id="return_detailed_div">
        <div class="col-md-12">
            <!-- مرتجعات تفصيلي --> 
            @component('components.widget', ['class' => 'box-danger', 'title' =>  __('sales_detailed.returns_deatiled') ])
                <table class="table table-bordered table-striped" id="return_detailed_table" style="width:100%">
                    <thead>
                        <tr>
                            <th>{{__('sales_detailed.date')}}</th>
                            <th> {{__('sales_detailed.ref_no')}}</th>
                            <th>{{__('sales_detailed.customer_name')}}</th>
                            <th>SKU</th>
                            <th>{{__('sales_detailed.product_name')}}</th>
                            <th>{{__('sales_detailed.quantity')}}</th>
                            <th>{{__('sales_detailed.total')}}</th>
                            <th>{{__('sales_detailed.paid_type')}}</th>
                            <th>{{__('sales_detailed.location')}}</th>
                        </tr>
                    </thead>
                    <tfoot>
                        <tr class="bg-gray">
                            <td colspan="4"> {{__('sales_detailed.total_of_returns')}}:</td>
                            <td id="ret_det_qty"></td>
                            <td id="ret_det_total"></td>
                            <td colspan="2"></td>
                        </tr>
                    </tfoot>
                </table>
            @endcomponent
        </div>
    </div>

    <div class="row report-div hide" id="return_summary_div">
        <div class="col-md-12">
            <!-- مرتجعات مجمل -->
            @component('components.widget', ['class' => 'box-danger', 'title' =>  __('sales_detailed.returns_summary') ])
                <table class="table table-bordered table-striped" id="return_summary_table" style="width:100%">
                    <thead>
                        <tr>
                            <th>{{__('sales_detailed.date')}}</th>
                            <th> {{__('sales_detailed.ref_no')}}</th>
                            <th>{{__('sales_detailed.customer_name')}}</th>
                            <th>{{__('sales_detailed.total')}}</th>
                            <th> {{__('sales_detailed.paid_type')}}</th>
                            <th> {{__('sales_detailed.transaction_type')}}</th>
                            <th>{{__('sales_detailed.location')}}</th>
                        </tr>
                    </thead>
                    <tfoot>
                        <tr class="bg-gray">
                            <td>{{__('sales_detailed.total_net')}}:</td>
                            <td colspan="2"></td>
                            <td id="ret_sum_total"></td>
                            <td colspan="3"></td>
                        </tr>
                    </tfoot>
                </table>
            @endcomponent
        </div>
    </div>
</section>
@endsection

@section('javascript')
<script type="text/javascript">
    $(document).ready(function() {
       // 1. إعداد تاريخ اليوم الافتراضي (بداية ونهاية اليوم الحالي)
        var start = moment().startOf('day');
        var end = moment().endOf('day');

        // 2. تفعيل DateRangePicker مع خاصية اختيار الوقت
        if ($('#qer_date_filter').length) {
            $('#qer_date_filter').daterangepicker(
                _.extend({}, dateRangeSettings, {
                    timePicker: true,          // تفعيل اختيار الوقت
                    timePicker24Hour: false,   // نظام 12 ساعة
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
                    $('#qer_date_filter').val(start.format(moment_date_format + ' hh:mm A') + ' ~ ' + end.format(moment_date_format + ' hh:mm A'));
                    // استدعاء دالة التحديث الخاصة بك
                    reload_active_table(); 
                }
            );
            
            // وضع القيمة الابتدائية في الحقل عند تحميل الصفحة
            $('#qer_date_filter').val(start.format(moment_date_format + ' hh:mm A') + ' ~ ' + end.format(moment_date_format + ' hh:mm A'));
        }

        // 3. دالة تجميع بيانات الفلاتر (المنطق الأساسي للوقت)
        window.get_filters = function() {
            var picker = $('#qer_date_filter').data('daterangepicker');
            var start_dt = picker.startDate.clone();
            var end_dt = picker.endDate.clone();

            /* المنطق: إذا كان وقت البداية والنهاية متطابقاً تماماً (اختيار يوم واحد بدون تعديل يدوي للوقت)
               نقوم برفع النطاق ليشمل اليوم من بدايته لنهايته.
            */
            if (start_dt.format('HH:mm:ss') === end_dt.format('HH:mm:ss')) {
                start_dt = start_dt.startOf('day');
                end_dt = end_dt.endOf('day');
            }

            return {
                location_id: $('#location_id').val(),
                // إرسال التواريخ بصيغة YYYY-MM-DD HH:mm:ss المتوافقة مع MySQL
                start_date: start_dt.format('YYYY-MM-DD HH:mm:ss'),
                end_date: end_dt.format('YYYY-MM-DD HH:mm:ss'),
                transaction_type: $('#transaction_type').val(),
                view_type: $('#view_type').val()
            };
        };

        var get_raw = function(v) { 
            return typeof v === 'string' ? v.replace(/[^\d.-]/g, '') * 1 : typeof v === 'number' ? v : 0; 
        };

        // الإعدادات المشتركة
        var common_settings = {
            processing: true,
            serverSide: true,
            aaSorting: [[0, 'desc']],
            scrollX: true,
            scrollY: '60vh',
            scrollCollapse: true,
            autoWidth: false,
            // تحديث الأعمدة عند كل عملية رسم لضمان ثبات العناوين
            fnDrawCallback: function (oSettings) {
                var api = this.api();
                setTimeout(function() {
                    api.columns.adjust();
                }, 200); 
            }
        };

        // دالة عامة لتحديث أو إنشاء الجدول
        function refresh_datatable(selector, init_function) {
            if ($.fn.DataTable.isDataTable(selector)) {
                $(selector).DataTable().ajax.reload();
            } else {
                init_function();
            }
        }

        // --- وظائف تهيئة الجداول ---

        function init_all_det() {
            window.all_det_table = $('#all_detailed_table').DataTable($.extend({}, common_settings, {
                ajax: { url: "{{ route('productAllTransactionsReport') }}", data: function(d) { Object.assign(d, get_filters()); } },
              columns: [
    { data: 'transaction_date', name: 't.transaction_date', searchable: false }, // عطلنا البحث هنا
    { data: 'type_label', name: 'type_label', searchable: false, orderable: false },
    { data: 'invoice_no', name: 't.invoice_no' },
    { data: 'customer_name', name: 'c.name' },
    { data: 'sku', name: 'p.sku' },
    { data: 'product_name', name: 'p.name' },
    { data: 'quantity', name: 'quantity' },
    { data: 'total_line_amount', name: 'total_line_amount', searchable: false }, // عطلنا البحث هنا
    { data: 'cash_val', name: 'cash_val', searchable: false, orderable: false },
    { data: 'card_val', name: 'card_val', searchable: false, orderable: false },
    { data: 'due_val', name: 'due_val', searchable: false, orderable: false },
    { data: 'location_name', name: 'bl.name' }
],
                footerCallback: function (row, data, start, end, display) {
                    var api = this.api();
                    $('#all_det_qty').html(__number_f(api.column(6, {page: 'current'}).data().reduce((a, b) => get_raw(a) + get_raw(b), 0)));
                    $('#all_det_total').html(__currency_trans_from_en(api.column(7, {page: 'current'}).data().reduce((a, b) => get_raw(a) + get_raw(b), 0), true));
                    $('#all_det_cash').html(__currency_trans_from_en(api.column(8, {page: 'current'}).data().reduce((a, b) => get_raw(a) + get_raw(b), 0), true));
                    $('#all_det_card').html(__currency_trans_from_en(api.column(9, {page: 'current'}).data().reduce((a, b) => get_raw(a) + get_raw(b), 0), true));
                    $('#all_det_due').html(__currency_trans_from_en(api.column(10, {page: 'current'}).data().reduce((a, b) => get_raw(a) + get_raw(b), 0), true));
                }
            }));
        }

        function init_all_sum() {
            window.all_sum_table = $('#all_summary_table').DataTable($.extend({}, common_settings, {
                ajax: { url: "{{ route('productAllSummaryReport') }}", data: function(d) { Object.assign(d, get_filters()); } },
                columns: [
            { data: 'transaction_date', name: 't.transaction_date', searchable: false }, // منع البحث في التاريخ
            { data: 'type_label', name: 'type_label', searchable: false },
            { data: 'invoice_no', name: 't.invoice_no' },
            { data: 'customer_name', name: 'c.name' },
            { data: 'final_total', name: 'final_total', searchable: false }, // حقل ناتج عن IF
            { data: 'payment_status', name: 't.payment_status' },
            { data: 'location_name', name: 'bl.name' }
        ],
        footerCallback: function (row, data, start, end, display) {
            var api = this.api();
            $('#all_sum_total').html(__currency_trans_from_en(api.column(4, {page: 'current'}).data().reduce((a, b) => get_raw(a) + get_raw(b), 0), true));
        }
            }));
        }

        function init_sell_det() {
            window.sell_det_table = $('#sell_detailed_table').DataTable($.extend({}, common_settings, {
                ajax: { url: "{{ route('productSalesDetailedReport') }}", data: function(d) { Object.assign(d, get_filters()); } },
                columns: [
            { data: 'transaction_date', name: 't.transaction_date', searchable: false },
            { data: 'invoice_no', name: 't.invoice_no' },
            { data: 'customer_name', name: 'c.name' },
            { data: 'sku', name: 'p.sku' },
            { data: 'product_name', name: 'p.name' },
            { data: 'quantity', name: 'transaction_sell_lines.quantity' },
            { data: 'price_before_tax', name: 'transaction_sell_lines.unit_price_before_discount', searchable: false },
            { data: 'price_after_tax', name: 'transaction_sell_lines.unit_price_inc_tax', searchable: false },
            { data: 'total_line_amount', name: 'total_line_amount', searchable: false },
            { data: 'cash_amount', name: 'cash_amount', searchable: false, orderable: false },
            { data: 'card_amount', name: 'card_amount', searchable: false, orderable: false },
            { data: 'due_amount', name: 'due_amount', searchable: false, orderable: false },
            { data: 'location_name', name: 'bl.name' }
        ],
        footerCallback: function (row, data, start, end, display) {
            var api = this.api();
            $('#sell_det_qty').html(__number_f(api.column(5, {page: 'current'}).data().reduce((a, b) => get_raw(a) + get_raw(b), 0)));
            $('#sell_det_total').html(__currency_trans_from_en(api.column(8, {page: 'current'}).data().reduce((a, b) => get_raw(a) + get_raw(b), 0), true));
            $('#sell_det_cash').html(__currency_trans_from_en(api.column(9, {page: 'current'}).data().reduce((a, b) => get_raw(a) + get_raw(b), 0), true));
            $('#sell_det_card').html(__currency_trans_from_en(api.column(10, {page: 'current'}).data().reduce((a, b) => get_raw(a) + get_raw(b), 0), true));
            $('#sell_det_due').html(__currency_trans_from_en(api.column(11, {page: 'current'}).data().reduce((a, b) => get_raw(a) + get_raw(b), 0), true));
        }
            }));
        }

        function init_sell_sum() {
            window.sell_sum_table = $('#sell_summary_table').DataTable($.extend({}, common_settings, {
                ajax: { url: "{{ route('productSalesSummaryReport') }}", data: function(d) { Object.assign(d, get_filters()); } },
               columns: [
            { data: 'transaction_date', name: 't.transaction_date', searchable: false },
            { data: 'invoice_no', name: 't.invoice_no' },
            { data: 'customer_name', name: 'c.name' },
            { data: 'original_price', name: 'original_price', searchable: false }, // حقل SUM
            { data: 'unit_tax', name: 'unit_tax', searchable: false },             // حقل SUM
            { data: 'discount_val', name: 't.discount_amount', searchable: false },
            { data: 'total_line_amount', name: 't.final_total', searchable: false },
            { data: 'payment_methods', name: 'payment_methods', searchable: false, orderable: false },
            { data: 'location_name', name: 'bl.name' }
        ],
        footerCallback: function (row, data, start, end, display) {
            var api = this.api();
            $('#sell_sum_orig').html(__currency_trans_from_en(api.column(3, {page: 'current'}).data().reduce((a, b) => get_raw(a) + get_raw(b), 0), true));
            $('#sell_sum_tax').html(__currency_trans_from_en(api.column(4, {page: 'current'}).data().reduce((a, b) => get_raw(a) + get_raw(b), 0), true));
            $('#sell_sum_discount').html(__currency_trans_from_en(api.column(5, {page: 'current'}).data().reduce((a, b) => get_raw(a) + get_raw(b), 0), true));
            $('#sell_sum_total').html(__currency_trans_from_en(api.column(6, {page: 'current'}).data().reduce((a, b) => get_raw(a) + get_raw(b), 0), true));
        }
            }));
        }

        function init_ret_det() {
    window.ret_det_table = $('#return_detailed_table').DataTable($.extend({}, common_settings, {
        ajax: { 
            url: "{{ route('productReturnsDetailedReport') }}", 
            data: function(d) { Object.assign(d, get_filters()); } 
        },
        columns: [
            { data: 'transaction_date', name: 't.transaction_date', searchable: false },
            { data: 'invoice_no', name: 't.invoice_no' },
            { data: 'customer_name', name: 'c.name' },
            { data: 'sku', name: 'p.sku' },
            { data: 'product_name', name: 'p.name' },
            { data: 'quantity', name: 'tsl.quantity' },
            { data: 'subtotal', name: 'subtotal', searchable: false, orderable: false }, 
            { data: 'payment_status', name: 't.payment_status' },
            { data: 'location_name', name: 'bl.name' }
        ],
        footerCallback: function (row, data, start, end, display) {
            var api = this.api();
            var total_qty = api.column(5, {page: 'current'}).data().reduce((a, b) => get_raw(a) + get_raw(b), 0);
            $('#ret_det_qty').html(__number_f(total_qty));
            
            var total_amount = api.column(6, {page: 'current'}).data().reduce((a, b) => get_raw(a) + get_raw(b), 0);
            $('#ret_det_total').html(__currency_trans_from_en(total_amount, true));
        }
    }));
}

        function init_ret_sum() {
            window.ret_sum_table = $('#return_summary_table').DataTable($.extend({}, common_settings, {
                ajax: { url: "{{ route('productReturnsSummaryReport') }}", data: function(d) { Object.assign(d, get_filters()); } },
                columns: [
            { data: 'transaction_date', name: 't.transaction_date', searchable: false },
            { data: 'invoice_no', name: 't.invoice_no' },
            { data: 'customer_name', name: 'c.name' }, 
            { data: 'total_line_amount', name: 't.final_total', searchable: false },
            { data: 'payment_status', name: 't.payment_status' },
            { data: 'payment_methods', name: 'payment_methods', searchable: false, orderable: false },
            { data: 'location_name', name: 'bl.name' }
        ],
        footerCallback: function (row, data, start, end, display) {
            var api = this.api();
            $('#ret_sum_total').html(__currency_trans_from_en(api.column(3, {page: 'current'}).data().reduce((a, b) => get_raw(a) + get_raw(b), 0), true));
        }
            }));
        }

        // دالة التبديل والتشغيل الرئيسية
        function reload_active_table() {
            var type = $('#transaction_type').val();
            var view = $('#view_type').val();
            
            // إخفاء جميع الحاويات أولاً
            $('.report-div').addClass('hide'); 

            if (type === 'all') {
                if (view === 'detailed') { 
                    $('#all_detailed_div').removeClass('hide'); 
                    refresh_datatable('#all_detailed_table', init_all_det);
                } else { 
                    $('#all_summary_div').removeClass('hide'); 
                    refresh_datatable('#all_summary_table', init_all_sum);
                }
            } else if (type === 'sell') {
                if (view === 'detailed') { 
                    $('#sell_detailed_div').removeClass('hide'); 
                    refresh_datatable('#sell_detailed_table', init_sell_det);
                } else { 
                    $('#sell_summary_div').removeClass('hide'); 
                    refresh_datatable('#sell_summary_table', init_sell_sum);
                }
            } else if (type === 'sell_return') {
                if (view === 'detailed') { 
                    $('#return_detailed_div').removeClass('hide'); 
                    refresh_datatable('#return_detailed_table', init_ret_det);
                } else { 
                    $('#return_summary_div').removeClass('hide'); 
                    refresh_datatable('#return_summary_table', init_ret_sum);
                }
            }
        }

        // الاستماع لتغييرات الفلاتر
        $(document).on('change', '#transaction_type, #location_id, #view_type', function() {
            reload_active_table();
        });
        
        // تشغيل الجدول الافتراضي عند تحميل الصفحة
        reload_active_table();
    });
</script>
@endsection