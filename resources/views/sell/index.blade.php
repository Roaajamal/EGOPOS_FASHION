@extends('layouts.app')
@section('title', __('lang_v1.all_sales'))

@section('content')
    <section class="content-header no-print">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <h1 class="tw-text-xl md:tw-text-3xl tw-font-bold tw-text-black">@lang('sale.sells')</h1>
    </section>

    <section class="content no-print">
        @component('components.filters', ['title' => __('report.filters')])
            @include('sell.partials.sell_list_filters')
            @if ($payment_types)
                <div class="col-md-3">
                    <div class="form-group">
                        {!! Form::label('payment_method', __('lang_v1.payment_method') . ':') !!}
                        {!! Form::select('payment_method', $payment_types, null, ['class' => 'form-control select2', 'style' => 'width:100%', 'placeholder' => __('lang_v1.all')]) !!}
                    </div>
                </div>
            @endif
        @endcomponent

        @component('components.widget', ['class' => 'box-primary', 'title' => __('lang_v1.all_sales')])
            @can('direct_sell.access')
                @slot('tool')
                    <div class="box-tools">
                        <a class="tw-dw-btn tw-bg-gradient-to-r tw-from-indigo-600 tw-to-blue-500 tw-font-bold tw-text-white tw-border-none tw-rounded-full pull-right" href="{{ action([\App\Http\Controllers\SellController::class, 'create']) }}">
                            <i class="fa fa-plus"></i> @lang('messages.add')
                        </a>
                    </div>
                @endslot
            @endcan

            @if (auth()->user()->can('direct_sell.view') || auth()->user()->can('view_own_sell_only'))
                @php $custom_labels = json_decode(session('business.custom_labels'), true); @endphp
                <table class="table table-bordered table-striped ajax_view" id="sell_table">
                    <thead>
                        <tr>
                            <th>@lang('messages.action')</th>
                            <th>@lang('messages.date')</th>
                            @if(!empty($pos_settings['enable_fatora'])) <!-- اضافة شرط حسب تفعيل الفوترة --> 
                            <th>@lang('lang_v1.fatora_status') </th>
                            <th>@lang('lang_v1.fatora_action') </th> 
                             @endif                                     <!-- اضافة شرط حسب تفعيل الفوترة -->
                            <th>@lang('sale.invoice_no')</th>
                            <th>@lang('sale.customer_name')</th>
                            <th>@lang('lang_v1.contact_no')</th>
                            <th>@lang('sale.location')</th>
                            <th>@lang('sale.payment_status')</th>
                            <th>@lang('lang_v1.payment_method')</th>
                            <th>@lang('sale.total_amount')</th>
                            <th>@lang('sale.total_paid')</th>
                            <th>@lang('lang_v1.sell_due')</th>
                            <th>@lang('lang_v1.sell_return_due')</th>
                            <th>@lang('lang_v1.shipping_status')</th>
                            <th>@lang('lang_v1.total_items')</th>
                            <th>@lang('lang_v1.types_of_service')</th>
                            <th>@lang('lang_v1.service_custom_field_1')</th>
                            <th>{{ $custom_labels['sell']['custom_field_1'] ?? '' }}</th>
                            <th>{{ $custom_labels['sell']['custom_field_2'] ?? '' }}</th>
                            <th>{{ $custom_labels['sell']['custom_field_3'] ?? '' }}</th>
                            <th>{{ $custom_labels['sell']['custom_field_4'] ?? '' }}</th>
                            <th>@lang('lang_v1.added_by')</th>
                            <th>@lang('sale.sell_note')</th>
                            <th>@lang('sale.staff_note')</th>
                            <th>@lang('sale.shipping_details')</th>
                            <th>@lang('restaurant.table')</th>
                            <th>@lang('restaurant.service_staff')</th>
                            <th>@lang('lang_v1.gift_invoice')</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                   <tfoot>
    <tr class="bg-gray font-17 footer-total text-center">
        {{-- 1. الأعمدة التي تسبق حالة الدفع --}}
        @php
            // الحسبة الأساسية: الأكشن(1) + التاريخ(1) + الفاتورة(1) + الاسم(1) + الهاتف(1) + الموقع(1) = 6
            $initial_cols = 6;
            if(!empty($pos_settings['enable_fatora'])) {
                $initial_cols += 2; // إضافة عمودي الفوترة
            }
        @endphp
        <td colspan="{{ $initial_cols }}">
            <strong>@lang('sale.total'):</strong>
        </td>

        {{-- 2. أعمدة الحسابات (ثابتة المحاذاة) --}}
        <td class="footer_payment_status_count"></td>
        <td class="payment_method_count"></td>
        <td class="footer_sale_total"></td>
        <td class="footer_total_paid"></td>
        <td class="footer_total_remaining"></td>
        <td class="footer_total_sell_return_due"></td>

        {{-- 3. أعمدة الشحن وإجمالي القطع (2 أعمدة) --}}
        <td colspan="2"></td>

        {{-- 4. عمود نوع الخدمة (ديناميكي) --}}
        <td class="service_type_count"></td>

        {{-- 5. حساب الـ colspan الأخير لتغطية كل الحقول المخصصة والملاحظات --}}
        @php
            $last_cols = 5; // (Added By, Sell Note, Staff Note, Shipping Details, Table/Waiter)
            
            // نزيد الـ colspan بناءً على الحقول المخصصة المفعّلة
            if(!empty($is_types_service_enabled)) { $last_cols += 1; } // لحقل مخصص الخدمة 1
            if(!empty($custom_labels['sell']['custom_field_1'])) { $last_cols += 1; }
            if(!empty($custom_labels['sell']['custom_field_2'])) { $last_cols += 1; }
            if(!empty($custom_labels['sell']['custom_field_3'])) { $last_cols += 1; }
            if(!empty($custom_labels['sell']['custom_field_4'])) { $last_cols += 1; }
        @endphp
        
        <td colspan="{{ $last_cols }}"></td>
    </tr>
</tfoot>
                </table>
            @endif
        @endcomponent
    </section>
@stop

@section('javascript')
<script type="text/javascript">
$(document).ready(function() {
    // 1. إعداد فلتر التاريخ
    $('#sell_list_filter_date_range').daterangepicker(dateRangeSettings, function(start, end) {
        $('#sell_list_filter_date_range').val(start.format(moment_date_format) + ' ~ ' + end.format(moment_date_format));
        sell_table.ajax.reload();
    });

    // 2. بناء مصفوفة الأعمدة ديناميكياً لضمان الترتيب الصحيح
    var sell_columns = [
        { data: 'action', name: 'action', orderable: false, searchable: false },
        { data: 'transaction_date', name: 'transaction_date' }
    ];

    // أضف أعمدة الفوترة فقط إذا كانت مفعلة في الإعدادات
    @if(!empty($pos_settings['enable_fatora']))
        sell_columns.push({ data: 'fatora_status', name: 'fatora_status', orderable: false, searchable: false });
        sell_columns.push({ data: 'fatora_action', name: 'fatora_action', orderable: false, searchable: false });
    @endif

    // إضافة باقي الأعمدة بالترتيب
    sell_columns.push(
        { data: 'invoice_no', name: 'invoice_no' },
        { data: 'conatct_name', name: 'conatct_name' },
        { data: 'mobile', name: 'contacts.mobile' },
        { data: 'business_location', name: 'bl.name' },
        { data: 'payment_status', name: 'payment_status' },
        { data: 'payment_methods', orderable: false, searchable: false },
        { data: 'final_total', name: 'final_total' },
        { data: 'total_paid', name: 'total_paid', searchable: false },
        { data: 'total_remaining', name: 'total_remaining' },
        { data: 'return_due', orderable: false, searchable: false },
        { data: 'shipping_status', name: 'shipping_status' },
        { data: 'total_items', name: 'total_items', searchable: false },
        { data: 'types_of_service_name', name: 'tos.name', @if(empty($is_types_service_enabled)) visible: false @endif },
        { data: 'service_custom_field_1', name: 'service_custom_field_1', @if(empty($is_types_service_enabled)) visible: false @endif },
        { data: 'custom_field_1', name: 'transactions.custom_field_1', @if(empty($custom_labels['sell']['custom_field_1'])) visible: false @endif },
        { data: 'custom_field_2', name: 'transactions.custom_field_2', @if(empty($custom_labels['sell']['custom_field_2'])) visible: false @endif },
        { data: 'custom_field_3', name: 'transactions.custom_field_3', @if(empty($custom_labels['sell']['custom_field_3'])) visible: false @endif },
        { data: 'custom_field_4', name: 'transactions.custom_field_4', @if(empty($custom_labels['sell']['custom_field_4'])) visible: false @endif },
        { data: 'added_by', name: 'u.first_name' },
        { data: 'additional_notes', name: 'additional_notes' },
        { data: 'staff_note', name: 'staff_note' },
        { data: 'shipping_details', name: 'shipping_details' },
        { data: 'table_name', name: 'tables.name', @if(empty($is_tables_enabled)) visible: false @endif },
        { data: 'waiter', name: 'ss.first_name', @if(empty($is_service_staff_enabled)) visible: false @endif },
        { data: 'gift_invoice', name: 'gift_invoice', orderable: false, searchable: false },
    );

    // 3. تشغيل DataTable
    sell_table = $('#sell_table').DataTable({
        processing: true,
        serverSide: true,
        fixedHeader: false,
        aaSorting: [[1, 'desc']],
        "ajax": {
            "url": "/sells",
            "data": function(d) {
                if ($('#sell_list_filter_date_range').val()) {
                    d.start_date = $('#sell_list_filter_date_range').data('daterangepicker').startDate.format('YYYY-MM-DD');
                    d.end_date = $('#sell_list_filter_date_range').data('daterangepicker').endDate.format('YYYY-MM-DD');
                }
                d.is_direct_sale = 1;
                d.fatora_status = $('#fatora_status_filter').val();
                d.location_id = $('#sell_list_filter_location_id').val();
                d.customer_id = $('#sell_list_filter_customer_id').val();
                d.payment_status = $('#sell_list_filter_payment_status').val();
                d.payment_method = $('#payment_method').val();
                d = __datatable_ajax_callback(d);
            }
        },
        scrollY: "75vh",
        scrollX: true,
        scrollCollapse: true,
        columns: sell_columns, // استخدام المصفوفة الديناميكية
        "fnDrawCallback": function(oSettings) {
            __currency_convert_recursively($('#sell_table'));
        },
        "footerCallback": function(row, data, start, end, display) {
            var footer_sale_total = 0, footer_total_paid = 0, footer_total_remaining = 0, footer_total_sell_return_due = 0;
            for (var r in data) {
                footer_sale_total += $(data[r].final_total).data('orig-value') ? parseFloat($(data[r].final_total).data('orig-value')) : 0;
                footer_total_paid += $(data[r].total_paid).data('orig-value') ? parseFloat($(data[r].total_paid).data('orig-value')) : 0;
                footer_total_remaining += $(data[r].total_remaining).data('orig-value') ? parseFloat($(data[r].total_remaining).data('orig-value')) : 0;
                footer_total_sell_return_due += $(data[r].return_due).find('.sell_return_due').data('orig-value') ? parseFloat($(data[r].return_due).find('.sell_return_due').data('orig-value')) : 0;
            }
            $('.footer_total_sell_return_due').html(__currency_trans_from_en(footer_total_sell_return_due));
            $('.footer_total_remaining').html(__currency_trans_from_en(footer_total_remaining));
            $('.footer_total_paid').html(__currency_trans_from_en(footer_total_paid));
            $('.footer_sale_total').html(__currency_trans_from_en(footer_sale_total));
            $('.footer_payment_status_count').html(__count_status(data, 'payment_status'));
            $('.service_type_count').html(__count_status(data, 'types_of_service_name'));
            $('.payment_method_count').html(__count_status(data, 'payment_methods'));
        },
        createdRow: function(row, data, dataIndex) {
            // تحديد عمود اسم العميل ديناميكياً لجعل الخلايا قابلة للنقر
            var customer_col_idx = {{ !empty($pos_settings['enable_fatora']) ? 5 : 3 }};
            $(row).find('td:eq('+customer_col_idx+')').attr('class', 'clickable_td');
        }
    });

    $(document).on('change', '#sell_list_filter_location_id, #sell_list_filter_customer_id, #sell_list_filter_payment_status, #payment_method, #fatora_status_filter', function() {
        sell_table.ajax.reload();
    });
});
</script>
<script src="{{ asset('js/payment.js?v=' . $asset_v) }}"></script>
@endsection