@extends('layouts.app')
@section('title', __('purchase.purchases'))

@section('content')

    <style>
        .hide { display: none !important; }
    </style>

    <section class="content-header no-print">
        <h1 class="tw-text-xl md:tw-text-3xl tw-font-bold tw-text-black">@lang('purchase.purchases')
            <small></small>
        </h1>
    </section>

    <section class="content no-print">
        @component('components.filters', ['title' => __('report.filters')])
            <div class="col-md-3">
                <div class="form-group">
                    {!! Form::label('purchase_list_filter_location_id', __('purchase.business_location') . ':') !!}
                    {!! Form::select('purchase_list_filter_location_id', $business_locations, null, [
                        'class' => 'form-control select2',
                        'style' => 'width:100%',
                        'placeholder' => __('lang_v1.all'),
                    ]) !!}
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group">
                    {!! Form::label('purchase_list_filter_supplier_id', __('purchase.supplier') . ':') !!}
                    {!! Form::select('purchase_list_filter_supplier_id', $suppliers, null, [
                        'class' => 'form-control select2',
                        'style' => 'width:100%',
                        'placeholder' => __('lang_v1.all'),
                    ]) !!}
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group">
                    {!! Form::label('purchase_list_filter_status', __('purchase.purchase_status') . ':') !!}
                    {!! Form::select('purchase_list_filter_status', $orderStatuses, null, [
                        'class' => 'form-control select2',
                        'style' => 'width:100%',
                        'placeholder' => __('lang_v1.all'),
                    ]) !!}
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group">
                    {!! Form::label('purchase_list_filter_payment_status', __('purchase.payment_status') . ':') !!}
                    {!! Form::select(
                        'purchase_list_filter_payment_status',
                        [
                            'paid' => __('lang_v1.paid'),
                            'due' => __('lang_v1.due'),
                            'partial' => __('lang_v1.partial'),
                            'overdue' => __('lang_v1.overdue'),
                        ],
                        null,
                        ['class' => 'form-control select2', 'style' => 'width:100%', 'placeholder' => __('lang_v1.all')],
                    ) !!}
                </div>
            </div>
            
            <div class="col-md-3">
                <div class="form-group">
                    {!! Form::label('view_type', __('sales_detailed.view_type')) !!}
                    {!! Form::select('view_type', 
                        ['summary' => __('sales_detailed.summary'), 
                        'detailed' => __('sales_detailed.detailed')], 
                        'summary', ['class' => 'form-control select2', 
                        'id' => 'view_type', 'style' => 'width:100%']); !!}
                </div>
            </div>

            <div class="col-md-3">
               <div class="form-group">
               {!! Form::label('qer_date_filter', __('report.date_range') . ':') !!}
               <input type="text" id="qer_date_filter" name="date_range" class="form-control" readonly placeholder="{{__('lang_v1.select_a_date_range')}}">
               </div>
            </div>
        @endcomponent

        @component('components.widget', ['class' => 'box-primary', 'title' => __('purchase.all_purchases')])
            @can('purchase.create')
                @slot('tool')
                    <div class="box-tools">
                        <a class="tw-dw-btn tw-bg-gradient-to-r tw-from-indigo-600 tw-to-blue-500 tw-font-bold tw-text-white tw-border-none tw-rounded-full pull-right"
                            href="{{action([\App\Http\Controllers\PurchaseController::class, 'create'])}}">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"
                                stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                                class="icon icon-tabler icons-tabler-outline icon-tabler-plus">
                                <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                <path d="M12 5l0 14" />
                                <path d="M5 12l14 0" />
                            </svg> @lang('messages.add')
                        </a>
                    </div>
                @endslot
            @endcan

            <div id="summary_table_container">
                @include('purchase.partials.purchase_table')
            </div>

            <div id="detailed_table_container" class="hide">
              @include('purchase.partials.detailed_purchase_table')
            </div>
        @endcomponent

        <div class="modal fade product_modal" tabindex="-1" role="dialog" aria-labelledby="gridSystemModalLabel"></div>
        <div class="modal fade payment_modal" tabindex="-1" role="dialog" aria-labelledby="gridSystemModalLabel"></div>
        <div class="modal fade edit_payment_modal" tabindex="-1" role="dialog" aria-labelledby="gridSystemModalLabel"></div>
        @include('purchase.partials.update_purchase_status_modal')

    </section>

    <section id="receipt_section" class="print_section"></section>

@stop

@section('javascript')
    @php
        $custom_labels = json_decode(session('business.custom_labels'), true);
    @endphp
    <script>
        var customFieldVisibility = {
            custom_field_1: @json(!empty($custom_labels['purchase']['custom_field_1'])),
            custom_field_2: @json(!empty($custom_labels['purchase']['custom_field_2'])),
            custom_field_3: @json(!empty($custom_labels['purchase']['custom_field_3'])),
            custom_field_4: @json(!empty($custom_labels['purchase']['custom_field_4']))
        };
    </script>
    <script src="{{ asset('js/purchase.js?v=' . $asset_v) }}"></script>
    <script src="{{ asset('js/payment.js?v=' . $asset_v) }}"></script>
    
    <script>
        $(document).ready(function(){
            // تهيئة التاريخ والوقت لليوم الحالي
            var start = moment().startOf('day'); // الساعة 12:00 AM اليوم
            var end = moment().endOf('day');     // الساعة 11:59 PM اليوم

            // وظيفة تفعيل التقويم (DateRangePicker) مع الوقت ونظام 12 ساعة
            function init_date_filter() {
                $('#qer_date_filter').daterangepicker(
                    $.extend({}, dateRangeSettings, {
                        startDate: start,
                        endDate: end,
                        timePicker: true,
                        timePicker24Hour: false, // نظام 12 ساعة AM/PM
                        locale: {
                            format: moment_date_format + ' hh:mm A'
                        },
                        ranges: {
                            'اليوم': [moment().startOf('day'), moment().endOf('day')],
                            'أمس': [moment().subtract(1, 'days').startOf('day'), moment().subtract(1, 'days').endOf('day')],
                            'آخر 7 أيام': [moment().subtract(6, 'days').startOf('day'), moment().endOf('day')],
                            'هذا الشهر': [moment().startOf('month'), moment().endOf('month')],
                        }
                    }),
                    function(start, end) {
                        $('#qer_date_filter').val(start.format(moment_date_format + ' hh:mm A') + ' ~ ' + end.format(moment_date_format + ' hh:mm A'));
                        reload_tables();
                    }
                );

                // ضمان جلب اليوم كاملاً عند اختيار تاريخ من التقويم (تصفير الوقت تلقائياً)
                $('#qer_date_filter').on('apply.daterangepicker', function(ev, picker) {
                    // إذا كان الوقت المختار هو نفس وقت "الآن" (يعني المستخدم اختار تاريخ ولم يلمس الوقت)
                    if (picker.startDate.format('HH:mm') == moment().format('HH:mm')) {
                        picker.startDate.startOf('day');
                        picker.endDate.endOf('day');
                    }
                    $(this).val(picker.startDate.format(moment_date_format + ' hh:mm A') + ' ~ ' + picker.endDate.format(moment_date_format + ' hh:mm A'));
                    reload_tables();
                });

                // تعيين القيمة الافتراضية في الحقل عند التحميل
                $('#qer_date_filter').val(start.format(moment_date_format + ' hh:mm A') + ' ~ ' + end.format(moment_date_format + ' hh:mm A'));
            }

            init_date_filter();

            function reload_tables() {
                if ($('#view_type').val() == 'detailed') {
                    if (typeof detailed_purchase_table !== 'undefined') detailed_purchase_table.ajax.reload();
                } else {
                    if (typeof purchase_table !== 'undefined') purchase_table.ajax.reload();
                }
            }

            // منطق التبديل بين الجداول
            $('#view_type').change(function() {
                if ($(this).val() == 'detailed') {
                    $('#summary_table_container').addClass('hide');
                    $('#detailed_table_container').removeClass('hide');
                    load_detailed_purchase_table();
                } else {
                    $('#detailed_table_container').addClass('hide');
                    $('#summary_table_container').removeClass('hide');
                    if (typeof purchase_table !== 'undefined') {
                        purchase_table.ajax.reload();
                    }
                }
            });

            // تعريف الجدول التفصيلي
            function load_detailed_purchase_table() {
                if (!$.fn.DataTable.isDataTable('#detailed_purchase_table')) {
                    detailed_purchase_table = $('#detailed_purchase_table').DataTable({
                        processing: true,
                        serverSide: true,
                        order: [[1, 'desc']],
                        ajax: {
                            url: '/purchases/get-detailed-report',
                            data: function(d) {
                                d.location_id = $('#purchase_list_filter_location_id').val();
                                d.supplier_id = $('#purchase_list_filter_supplier_id').val();
                                d.status = $('#purchase_list_filter_status').val();
                                d.payment_status = $('#purchase_list_filter_payment_status').val();
                                
                                var picker = $('#qer_date_filter').data('daterangepicker');
                                if (picker) {
                                    // نرسل التنسيق الكامل 24 ساعة للسيرفر لضمان دقة البحث
                                    d.start_date = picker.startDate.format('YYYY-MM-DD HH:mm:00');
                                    d.end_date = picker.endDate.format('YYYY-MM-DD HH:mm:59');
                                }
                            }
                        },
                        columns: [
                            { data: 'action', name: 'action', orderable: false, searchable: false },
                            { data: 'transaction_date', name: 't.transaction_date' },
                            { data: 'ref_no', name: 't.ref_no' },
                            { data: 'location_name', name: 'bl.name' },
                            { data: 'supplier_name', name: 'c.name' },
                            { data: 'product_name', name: 'p.name' },
                            { data: 'sku', name: 'p.sku' },
                            { data: 'quantity', name: 'purchase_lines.quantity' },
                            { data: 'status', name: 't.status' },
                            { data: 'payment_status', name: 't.payment_status' },
                            { data: 'custom_field_1', name: 't.custom_field_1', visible: customFieldVisibility.custom_field_1 },
                            { data: 'custom_field_2', name: 't.custom_field_2', visible: customFieldVisibility.custom_field_2 },
                            { data: 'custom_field_3', name: 't.custom_field_3', visible: customFieldVisibility.custom_field_3 },
                            { data: 'custom_field_4', name: 't.custom_field_4', visible: customFieldVisibility.custom_field_4 },
                            { data: 'line_total', name: 'line_total', searchable: false },
                            { data: 'added_by', name: 'u.first_name' }
                        ],
                        footerCallback: function (row, data, start, end, display) {
                            var api = this.api();
                            var intVal = function (i) {
                                return typeof i === 'string' ? i.replace(/[\$,]|JD/g, '') * 1 : typeof i === 'number' ? i : 0;
                            };
                            var total_qty = api.column(7, { page: 'current' }).data().reduce(function (a, b) { return intVal(a) + intVal(b); }, 0);
                            var total_line_amount = api.column(14, { page: 'current' }).data().reduce(function (a, b) { return intVal(a) + intVal(b); }, 0);
                            $(api.column(7).footer()).html(__number_f(total_qty));
                            $(api.column(14).footer()).html(__currency_trans_from_en(total_line_amount, true));
                        }
                    });
                } else {
                    detailed_purchase_table.ajax.reload();
                }
            }

            // تحديث عند تغيير أي فلتر
            $(document).on('change', '#purchase_list_filter_location_id, #purchase_list_filter_supplier_id, #purchase_list_filter_status, #purchase_list_filter_payment_status', function() {
                reload_tables();
            });

            // منطق المودال (View, Edit, Delete, Update Status)
            $(document).on('click', '.update_status', function(e) {
                e.preventDefault();
                $('#update_purchase_status_form').find('#status').val($(this).data('status'));
                $('#update_purchase_status_form').find('#purchase_id').val($(this).data('purchase_id'));
                $('#update_purchase_status_modal').modal('show');
            });
        });
    </script>
@endsection