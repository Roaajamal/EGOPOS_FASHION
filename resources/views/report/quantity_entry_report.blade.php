@extends('layouts.app')
@section('title', __('quantity_entry.quantity_entry_report'))

@section('content')
<section class="content-header">
    <h1 class="tw-text-xl md:tw-text-3xl tw-font-bold tw-text-black">{{__('quantity_entry.quantity_entry_report')}}</h1>
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
                        {!! Form::label('view_type', __('sales_detailed.view_type')) !!}
                        {!! Form::select('view_type', 
                        ['summary' => __('sales_detailed.summary'), 
                        'detailed' => __('sales_detailed.detailed')], 
                        'detailed', ['class' => 'form-control select2', 
                        'id' => 'view_type', 'style' => 'width:100%']); !!}
                    </div>
                </div>
            @endcomponent
        </div>
    </div>
    <div class="row">
        <div class="col-md-12">
            @component('components.widget', ['class' => 'box-primary'])
                <div class="table-responsive">
                    <table class="table table-bordered table-striped" id="quantity_entry_report_table">
                        <thead>
    <tr>
        <th>{{__('quantity_entry.quantity_date')}}</th>
        <th>SKU</th>
        <th>{{__('quantity_entry.product_name')}}</th>
        <th>{{__('quantity_entry.previous_quantity')}} </th>
        <th>{{__('quantity_entry.new_quantity')}} </th>
        <th>{{__('quantity_entry.quantity_entry_total')}} </th>
        <th>{{__('quantity_entry.cost_quantity_entry')}} </th>
        <th>{{__('quantity_entry.ref_number')}} </th>
        <th>{{__('purchase.business_location')}}</th>
        <th>{{__('quantity_entry.user')}}</th>
    </tr>
</thead>
<tfoot>
        <tr class="bg-gray font-17 footer-total text-center">
            <td colspan="4"><strong>@lang('quantity_entry.total'):</strong></td>
            <td id="footer_total_added_qty"></td>
            <td id="footer_total_current_qty"></td>
            <td id="footer_total_price"></td>
            <td colspan="3"></td>
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
   $(document).ready(function() {
    // 1. تعريف التاريخ مع الوقت الافتراضي
    var start = moment().startOf('day');
    var end = moment().endOf('day');

    if ($('#qer_date_filter').length == 1) {
        $('#qer_date_filter').daterangepicker(
            _.extend({}, dateRangeSettings, {
                timePicker: true,
                timePicker24Hour: false,
                startDate: start,
                endDate: end,
                locale: {
                    format: moment_date_format + ' hh:mm A'
                }
            }), 
            function(start, end) {
                $('#qer_date_filter').val(start.format(moment_date_format + ' hh:mm A') + ' ~ ' + end.format(moment_date_format + ' hh:mm A'));
                quantity_entry_report_table.ajax.reload();
            }
        );

        $('#qer_date_filter').val(start.format(moment_date_format + ' hh:mm A') + ' ~ ' + end.format(moment_date_format + ' hh:mm A'));

        $('#qer_date_filter').on('cancel.daterangepicker', function(ev, picker) {
            $(this).val('');
            quantity_entry_report_table.ajax.reload();
        });
    }

    // 2. تعريف جدول DataTable
    quantity_entry_report_table = $('#quantity_entry_report_table').DataTable({
        processing: true,
        serverSide: true,
        searchDelay: 500,
        aaSorting: [[0, 'desc']],
        ajax: {
            url: "{{ action([\App\Http\Controllers\ReportController::class, 'quantityEntryReport']) }}",
            data: function(d) {
                var picker = $('#qer_date_filter').data('daterangepicker');
                if (picker) {
                    d.start_date = picker.startDate.format('YYYY-MM-DD HH:mm:ss');
                    d.end_date = picker.endDate.format('YYYY-MM-DD HH:mm:ss');
                }
                d.location_id = $('#location_id').val();
                d.view_type = $('#view_type').val(); // إرسال النوع (summary أو detailed)
            }
        },
        columns: [
            { data: 'transaction_date', name: 'transactions.transaction_date' },
            { data: 'sku', name: 'sku', defaultContent: '' },
            { data: 'product_name', name: 'product_name', defaultContent: '' },
            { data: 'prev_qty', name: 'pl.previous_quantity', defaultContent: '' }, 
            { data: 'added_qty', name: 'added_qty' },
            { data: 'current_qty', name: 'current_qty', defaultContent: '', searchable: false, orderable: false },
            { data: 'row_total_price', name: 'row_total_price', searchable: false },
            { data: 'ref_no', name: 'ref_no' },
            { data: 'location_name', name: 'location_name' },
            { data: 'added_by', name: 'added_by' }
        ],

        // 3. التحكم في ظهور الأعمدة بناءً على نوع العرض
        fnDrawCallback: function(oSettings) {
            var view_type = $('#view_type').val();
            if (view_type == 'summary') {
                // إخفاء الأعمدة: SKU (1), اسم المنتج (2), الكمية السابقة (3), الكمية الحالية (5)
                this.api().columns([1, 2, 3, 5]).visible(false);
            } else {
                // إظهارها في العرض التفصيلي
                this.api().columns([1, 2, 3, 5]).visible(true);
            }
        },

        // 4. حساب الإجماليات في الـ Footer
        footerCallback: function (row, data, start, end, display) {
            var api = this.api();
            var view_type = $('#view_type').val();

            var get_raw_value = function (i) {
                if (typeof i === 'string' && i.indexOf('data-orig-value') !== -1) {
                    return parseFloat($(i).data('orig-value')) || 0;
                }
                if (typeof i === 'string') {
                    return parseFloat(i.replace(/[^\d.-]/g, '')) || 0;
                }
                return typeof i === 'number' ? i : 0;
            };

            // جمع الكميات المضافة
            var total_added_qty = api.column(4, { page: 'current' }).data().reduce(function (a, b) {
                return get_raw_value(a) + get_raw_value(b);
            }, 0);

            // جمع المبالغ المالية
            var total_price = api.column(6, { page: 'current' }).data().reduce(function (a, b) {
                return get_raw_value(a) + get_raw_value(b);
            }, 0);

            $(api.column(4).footer()).html(__number_f(total_added_qty, false));
            $(api.column(6).footer()).html(__currency_trans_from_en(total_price, true));

            // ضبط دمج الخلايا (Colspan) في الفوتر بناءً على العرض
            if (view_type == 'summary') {
                // في المجمل، أول عمود هو التاريخ فقط
                $(row).find('td:first').attr('colspan', 1).html("<strong>@lang('quantity_entry.total'):</strong>");
            } else {
                // في التفصيلي، ندمج أول 4 أعمدة (تاريخ، SKU، اسم، سابقة)
                $(row).find('td:first').attr('colspan', 4).html("<strong>@lang('quantity_entry.total'):</strong>");
            }
        }
    });

    // 5. تحديث الجدول عند تغيير الفلاتر
    $(document).on('change', '#location_id, #view_type', function() {
        quantity_entry_report_table.ajax.reload();
    });
});
</script>
@endsection