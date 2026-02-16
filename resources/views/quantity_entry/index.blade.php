@extends('layouts.app')
@section('title',  __('quantity_entry.quantity_entry_list'))

@section('content')

<!-- Content Header (Page header) -->
<section class="content-header">
    <h1 class="tw-text-xl md:tw-text-3xl tw-font-bold tw-text-black">@lang('quantity_entry.quantity_entry_list')
        <small></small>
    </h1>
</section>

<!-- Main content -->
<section class="content">
    @component('components.widget', ['class' => 'box-primary', 'title' => __('quantity_entry.quantity_entry_list')])
        @slot('tool')
            <div class="box-tools">
                @if(auth()->user()->can('quantity_entry.create'))
                    <a class="tw-dw-btn tw-bg-gradient-to-r tw-from-indigo-600 tw-to-blue-500 tw-font-bold tw-text-white tw-border-none tw-rounded-full pull-right"
                        href="{{action([\App\Http\Controllers\QuantityEntryController::class, 'create'])}}">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                            stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                            class="icon icon-tabler icons-tabler-outline icon-tabler-plus">
                            <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                            <path d="M12 5l0 14" />
                            <path d="M5 12l14 0" />
                        </svg> @lang('messages.add')
                    </a>
                @endif
            </div>
        @endslot
        <div class="table-responsive">
            <table class="table table-bordered table-striped ajax_view" id="quantity_entry_table">
               <thead>
                  <tr>
                       <th>@lang('messages.action')</th>
                       <th>@lang('messages.date')</th>
                      <th>@lang('purchase.ref_no')</th>
                      <th>@lang('business.location')</th>
                      <th>{{__('quantity_entry.total_of_quantity')}}</th>
                      <th>@lang('stock_adjustment.total_amount')</th>
                      <th>@lang('purchase.additional_notes')</th> <th>@lang('lang_v1.added_by')</th>
    </tr>
</thead>
            </table>
        </div>
       <div id="receipt_section" style="display:none;"></div>
    @endcomponent

</section>
<section id="receipt_section" class="print_section hide"></section>
<!-- /.content -->
@stop
@section('javascript')
<script>
$(document).ready(function () {
    // 1. تعريف الجدول (DataTable)
    var quantity_entry_table = $('#quantity_entry_table').DataTable({
        processing: true,
        serverSide: true,
        ajax: '/quantity-entry',
        order: [[1, 'desc']],
        columns: [
            { data: 'action', name: 'action', orderable: false, searchable: false },
            { data: 'transaction_date', name: 'transaction_date' },
            { data: 'ref_no', name: 'ref_no' },
            { data: 'location_name', name: 'BL.name' },
            { data: 'added_qty', name: 'added_qty', searchable: false },
            { data: 'final_total', name: 'final_total' },
            { data: 'additional_notes', name: 'additional_notes' },
            { data: 'added_by', name: 'u.first_name' }
        ]
    });

    // 2. كود الطباعة المباشرة (حل مشكلة الصفحة الفاضية)
  $(document).on('click', '.btn-print-now', function(e) {
        e.preventDefault();
        e.stopPropagation();
        
        var href = $(this).data('href');
        toastr.info("جاري تحضير الفاتورة...");

        $.ajax({
            method: 'GET',
            url: href,
            dataType: 'json',
            success: function(result) {
                if (result.success == 1 && result.receipt.html_content) {
                    
                    // استخدام نافذة مخفية (Iframe) للطباعة لضمان عدم ظهور شاشات فاضية
                    var frame = $('<iframe id="print_frame">').hide().appendTo('body');
                    var doc = frame[0].contentWindow.document;
                    
                    doc.write('<html><head><title>Print</title>');
                    // سحب التنسيقات لضمان الحدود السوداء
                    $('link[rel="stylesheet"]').each(function() {
                        doc.write('<link rel="stylesheet" href="' + $(this).attr('href') + '">');
                    });
                    doc.write('</head><body>');
                    doc.write(result.receipt.html_content);
                    doc.write('</body></html>');
                    doc.close();

                    // إعطاء وقت قصير جداً للتحميل ثم الطباعة
                    setTimeout(function() {
                        frame[0].contentWindow.focus();
                        frame[0].contentWindow.print();
                        frame.remove(); // حذف الفريم بعد الطباعة
                    }, 500);
                    
                } else {
                    toastr.error("فشل في استلام بيانات الطباعة");
                }
            }
        });
    });
    // 3. كود فتح المودال عند الضغط على "عرض"
    $(document).on('click', '.btn-modal', function(e) {
        e.preventDefault();
        var container = $(this).data('container');
        $.ajax({
            url: $(this).data('href'),
            dataType: 'html',
            success: function(result) {
                $(container).html(result).modal('show');
            },
        });
    });
});
</script>
@endsection

<style>
    /* تصغير الخط في رأس الجدول وجعله غامقاً */
    #quantity_entry_table thead th {
        padding: 8px 4px !important;
        font-size: 16px;
        background-color: #f8f9fa;
        color: #333;
        text-align: center;
        vertical-align: middle;
    }

    /* تصغير الخط والمساحات داخل خلايا الجدول */
    #quantity_entry_table tbody td {
        padding: 4px 6px !important; /* تقليل الارتفاع */
        font-size: 14px;
        vertical-align: middle;
        text-align: center;
    }

    /* تصغير حجم الأزرار داخل الجدول */
    #quantity_entry_table .tw-dw-btn {
        padding: 2px 8px !important;
        min-height: 24px !important;
        height: 24px !important;
        font-size: 11px !important;
    }

    /* جعل الجدول يبدو مضغوطاً أكثر */
    .table-responsive {
        overflow-x: auto;
    }
</style>

@cannot('view_purchase_price')
    <style>
        .show_price_with_permission {
            display: none !important;
        }
    </style>
@endcannot