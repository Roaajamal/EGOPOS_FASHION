 

@extends('layouts.app')
@section('title', __('product.import_products'))

@section('content')

<section class="content-header">
    <h1 class="tw-text-xl md:tw-text-3xl tw-font-bold tw-text-black">
        @lang('product.import_products')
    </h1>
</section>

<section class="content">

    <div class="row">
        <div class="col-md-12">
            @component('components.filters', ['title' => __('report.filters')])

                <div class="col-md-3" id="location_filter">
                    <div class="form-group">
                        {!! Form::label('location_id', __('purchase.business_location') . ':') !!}
                        {!! Form::select('location_id', $business_locations, null, [
                            'class'       => 'form-control select2',
                            'style'       => 'width:100%',
                            'placeholder' => __('lang_v1.all'),
                        ]) !!}
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="form-group">
                        {!! Form::label('qer_date_filter', __('report.date_range') . ':') !!}
                        {!! Form::text('date_range', null, [
                            'placeholder' => __('lang_v1.select_a_date_range'),
                            'class'       => 'form-control',
                            'id'          => 'qer_date_filter',
                            'readonly'
                        ]); !!}
                    </div>
                </div>

            @endcomponent
        </div>
    </div>

    @if(session('status'))
        <div class="row">
            <div class="col-sm-12">
                <div class="alert {{ session('status.success') ? 'alert-success' : 'alert-danger' }} alert-dismissible">
                    <button type="button" class="close" data-dismiss="alert">×</button>
                    {{ session('status.msg') }}
                </div>
            </div>
        </div>
    @endif

    @if(session('notification') || !empty($notification))
        <div class="row">
            <div class="col-sm-12">
                <div class="alert alert-danger alert-dismissible">
                    <button type="button" class="close" data-dismiss="alert">×</button>
                    {{ session('notification.msg') ?? $notification['msg'] ?? '' }}
                </div>
            </div>
        </div>
    @endif

    <div class="row">
        <div class="col-sm-12">
            @component('components.widget', ['class' => 'box-primary'])

               @if(auth()->user()->can('add_product.create'))
                <div class="row" style="margin-bottom:15px;">
                    <div class="col-sm-12">
                        <a href="{{ route('add-products.create') }}"
                           class="tw-dw-btn tw-dw-btn-primary tw-text-white">
                            <i class="fa fa-plus"></i> @lang('product.import_products')
                        </a>
                    </div>
                </div>
                @endif

                <table class="table table-bordered table-striped" id="imports_table">
                    <thead>
                        <tr>
                            
                            <th>@lang('messages.date')</th>
                            <th>@lang('business.location')</th>
                            <th>@lang('product.total_products')</th>
                            <th>@lang('product.total_quantity')</th>
                            <th>@lang('product.location_quantity')</th>
                            <th>@lang('product.created_by')</th>
                            <th>@lang('product.notes')</th>
                            <th>@lang('messages.action')</th>
                        </tr>
                    </thead>
                </table>

            @endcomponent
        </div>
    </div>

</section>

{{-- Modal التفاصيل --}}
<div class="modal fade" id="import_details_modal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal">×</button>
                
                <h4 class="modal-title">تفاصيل الاستيراد</h4>

                
            </div>

            <div class="modal-body">
                <div id="import_details_content">
                    <i class="fa fa-spinner fa-spin"></i> Loading...
                </div>
            </div>
        </div>
    </div>
</div>

@endsection

@section('javascript')
<script>
$(document).ready(function() {

    // ── daterangepicker ───────────────────────────────────
    var start = moment().startOf('day');
    var end   = moment().endOf('day');

    var p_labels = @json(array_filter($p_labels ?? [])); 

    if ($('#qer_date_filter').length == 1) {
        $('#qer_date_filter').daterangepicker(
            _.extend({}, dateRangeSettings, {
                timePicker: true,
                timePicker24Hour: false,
                timePickerIncrement: 1,
                startDate: start,
                endDate: end,
                locale: { format: moment_date_format + ' hh:mm A' }
            }),
            function(start, end) {
                start = start.startOf('day');
                end   = end.endOf('day');
                $('#qer_date_filter').val(
                    start.format(moment_date_format + ' hh:mm A') + ' ~ ' +
                    end.format(moment_date_format + ' hh:mm A')
                );
                $('#qer_date_filter').data('daterangepicker').setStartDate(start);
                $('#qer_date_filter').data('daterangepicker').setEndDate(end);
                imports_table.ajax.reload();
            }
        );

        $('#qer_date_filter').val(
            start.format(moment_date_format + ' hh:mm A') + ' ~ ' +
            end.format(moment_date_format + ' hh:mm A')
        );

        $('#qer_date_filter').on('cancel.daterangepicker', function(ev, picker) {
            $(this).val('');
            imports_table.ajax.reload();
        });

        $('#qer_date_filter').on('apply.daterangepicker', function(ev, picker) {
            var startTime = picker.startDate.format('HH:mm');
            var endTime   = picker.endDate.format('HH:mm');
            if (startTime === '00:00' && endTime === '00:00') {
                picker.endDate = picker.endDate.endOf('day');
                $(this).val(
                    picker.startDate.format(moment_date_format + ' hh:mm A') + ' ~ ' +
                    picker.endDate.format(moment_date_format + ' hh:mm A')
                );
            }
            imports_table.ajax.reload();
        });
        $('#qer_date_filter').on('hide.daterangepicker', function(ev, picker) {
    if ($('#qer_date_filter').val() !== '') {
        imports_table.ajax.reload();
    }
});
    }

    // ── DataTable ─────────────────────────────────────────
    var imports_table = $('#imports_table').DataTable({
        processing: true,
        serverSide: true,
        aaSorting: [[1, 'desc']],
        ajax: {
            url: "{{ route('add-products.index') }}",
            data: function(d) {
                var picker = $('#qer_date_filter').data('daterangepicker');
                if (picker && $('#qer_date_filter').val() !== '') {
                    d.start_date = picker.startDate.format('YYYY-MM-DD HH:mm:ss');
                    d.end_date   = picker.endDate.format('YYYY-MM-DD HH:mm:ss');
                }
                d.location_id = $('#location_id').val();
            }
        },
        columns: [
           
            { data: 'date', name: 'created_at' },
            { data: 'locations_html', name: 'locations_html', orderable: false, },
            { data: 'product_count', name: 'product_count' },
            { data: 'total_quantity', name: 'total_quantity' },
            { data: 'selected_location', name: 'selected_location', orderable: false, searchable: false },
            { data: 'created_by_name', name: 'created_by_name', orderable: false, },
            { data: 'notes', name: 'notes', orderable: false },
            { data: 'action', name: 'action', orderable: false, searchable: false },
        ]
    });

    // ── فلتر الفرع ────────────────────────────────────────
    $(document).on('change', '#location_id', function() {
        imports_table.ajax.reload();
    });

    // ── modal التفاصيل ────────────────────────────────────
    $(document).on('click', '.view_import_details', function() {
        var id = $(this).data('id');
         $('#print_import_details_link').attr('href', '{{ url("add-products") }}/' + id + '/show');

        document.getElementById('import_details_content').innerHTML =
            '<i class="fa fa-spinner fa-spin"></i> Loading...';

        $('#import_details_modal').modal('show');

        fetch('{{ url("add-products") }}/' + id + '/details', {
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        })
        .then(function(res) {
        
        return res.json(); })
        .then(function(response) {
            
            if (!response.success) {
                document.getElementById('import_details_content').innerHTML = 'Error loading data.';
                return;
            }

            var products = response.products;

            
// بناء headers ديناميكياً
           var html = '<table class="table table-bordered table-striped">' +
           '<thead><tr>' +
        '<th>#</th>' +
        '<th>SKU</th>' +
        '<th>{{ __("product.product") }}</th>' +
        '<th>{{ __("product.unit") }}</th>' +
        '<th>{{ __("product.category") }}</th>' +
        '<th>{{ __("product.tax") }}</th>' +
        '<th>{{ __("product.tax_type") }}</th>' +
        '<th>{{ __("product.purchase_price_inc_tax") }}</th>' +
        '<th>{{ __("product.selling_price_inc_tax") }}</th>' +
        '<th>{{ __("product.opening_stock") }}</th>';

       for (var i = 1; i <= 20; i++) {
      var label = p_labels['custom_field_' + i];
    if (label) {
        html += '<th>' + label + '</th>';
    }
}

html += '<th>{{ __("messages.status") }}</th>' +
        '</tr></thead><tbody>';

if (products.length === 0) {
    html += '<tr><td colspan="99" class="text-center">{{ __("messages.no_records_found") }}</td></tr>';
} else {
    products.forEach(function(p, i) {
        var statusBadge = p.is_add_qty
            ? '<span class="label label-warning">زيادة كمية</span>'
            : '<span class="label label-success">منتج جديد</span>';

        var rowStyle = p.is_add_qty ? 'background-color:#fff3cd;' : '';

       html += '<tr style="' + rowStyle + '">' +
    '<td>' + (i + 1) + '</td>' +
    '<td>' + (p.sku || '-') + '</td>' +
    '<td>' + (p.name || '-') + '</td>' +
    '<td>' + (p.unit || '-') + '</td>' +
    '<td>' + (p.category || '-') + '</td>' +
    '<td>' + (p.tax || '-') + '</td>' +
    '<td>' + (p.tax_type || 'inclusive') + '</td>' +
    '<td>' + (p.purchase_price || '0') + '</td>' +
    '<td>' + (p.selling_price || '-') + '</td>' +
    '<td>' + (p.opening_stock || '-') + '</td>';
        for (var j = 1; j <= 20; j++) {
            if (p_labels['custom_field_' + j]) {
                html += '<td>' + (p['custom_field_' + j] || '-') + '</td>';
            }
        }

        html += '<td>' + statusBadge + '</td>' +
                '</tr>';
    });
}

html += '</tbody></table>';
document.getElementById('import_details_content').innerHTML = html;
})
.catch(function() {
    document.getElementById('import_details_content').innerHTML = 'Something went wrong.';
});
    });

// 1. وظيفة الطباعة: تأخذ محتوى المودال وتفتحه في نافذة جديدة للطباعة
$(document).on('click', '#print_import_details', function() {
    var content = $('#import_details_content').html();
    
    // التحقق إذا كان المحتوى لا يزال قيد التحميل
    if (content.includes('fa-spinner') || content === '') {
        toastr.error("الرجاء الانتظار حتى يتم تحميل البيانات");
        return;
    }

    var win = window.open('', '_blank');
    win.document.write('<html><head><title>طباعة تفاصيل الاستيراد</title>');
    
    // إضافة تنسيقات لتظهر الطباعة بشكل مرتب (Bootstrap + Custom CSS)
    win.document.write('<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css">');
    win.document.write('<style>' +
        'body { padding: 20px; font-family: sans-serif; }' +
        'table { width: 100%; border-collapse: collapse; margin-top: 20px; }' +
        'th, td { border: 1px solid #ccc !important; padding: 8px; text-align: center; }' +
        'th { background-color: #f5f5f5 !important; }' +
        '.label { border: 1px solid #000; color: #000; padding: 2px 5px; }' +
        '@media print { .no-print { display: none; } }' +
        '</style>');
    
    win.document.write('</head><body dir="rtl">'); // دعم الاتجاه العربي
    win.document.write('<h2 class="text-center">تقرير تفاصيل استيراد المنتجات</h2>');
    win.document.write('<hr>');
    win.document.write(content); // وضع محتوى الجدول هنا
    win.document.write('</body></html>');

    win.document.close();
    
    // تأخير بسيط لضمان تحميل التنسيقات قبل فتح نافذة الطباعة
    setTimeout(function() {
        win.focus();
        win.print();
        win.close();
    }, 1000);
});

// 2. ربط زر الطباعة الموجود في "جدول البيانات" (DataTable)
// عند الضغط عليه، سيقوم بفتح المودال أولاً ثم تشغيل الطباعة تلقائياً
// $(document).on('click', '.print_import_details', function(e) {
//     e.preventDefault();
//     var id = $(this).data('id');
    
//     // محاكاة الضغط على زر "فحص" لفتح المودال وتحميل البيانات
//     $('.view_import_details[data-id="' + id + '"]').click();

//     // فحص متكرر كل نصف ثانية: إذا اختفت علامة التحميل (Spinner) قم بالطباعة
//     var checkLoad = setInterval(function() {
//         var modalContent = $('#import_details_content').html();
//         if (modalContent && !modalContent.includes('fa-spinner')) {
//             $('#print_import_details').click(); // تنفيذ وظيفة الطباعة أعلاه
//             clearInterval(checkLoad); // إيقاف الفحص
//         }
//     }, 500);
// });
 



});
</script>
@endsection