@php
$custom_labels = json_decode(session('business.custom_labels'), true);
@endphp

@section('css')
<style>
    .table-controls {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 15px;
    }

    .dt-buttons {
        flex-grow: 1;
        text-align: center;
        margin-left: 180px;
    }

    .dataTables_filter {
        text-align: left;
    }

    .dataTables_filter input {
        margin-right: 10px;
        display: inline-block;
        width: auto;
    }
</style>
@endsection

@extends('layouts.app')
@section('title', __('missing_product.missing_product_with_sales'))

@section('content')
<section class="content-header">
    <h1 class="tw-text-xl md:tw-text-3xl tw-font-bold tw-text-black">
        {{ __('missing_product.missing_product_with_sales') }}
    </h1>
</section>

<section class="content">
    <div class="row">
        <div class="col-md-12">
           @component('components.filters', ['title' => __('report.filters')])
    {!! Form::open(['url' => action([\App\Http\Controllers\MissingProductController::class, 'getMissingProducts']), 'method' => 'get', 'id' => 'missing_products_filter_form']) !!}
    
    <style>
        /* تنسيق محسن للفلاتر */
        .filters-section {
            background: linear-gradient(145deg, #ffffff, #f8fafc);
            border-radius: 20px;
            padding: 25px 20px 15px 20px;
            margin-bottom: 25px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
            border: 1px solid #e9ecef;
        }
        
        .filter-group {
            margin-bottom: 20px;
            transition: all 0.3s ease;
        }
        
        .filter-group label {
            font-weight: 700;
            color: #2c3e50;
            margin-bottom: 10px;
            display: block;
            font-size: 14px;
            letter-spacing: 0.3px;
            position: relative;
            padding-right: 12px;
        }
        
        .filter-group label::before {
            content: '';
            position: absolute;
            right: 0;
            top: 50%;
            transform: translateY(-50%);
            width: 3px;
            height: 15px;
            background: linear-gradient(135deg, #3498db, #2980b9);
            border-radius: 2px;
        }
        
        .filter-group select,
        .filter-group .select2-container {
            width: 100% !important;
        }
        
        .filter-group select {
            border: 2px solid #e2e8f0;
            border-radius: 12px;
            padding: 8px 12px;
            font-size: 14px;
            transition: all 0.3s ease;
            background-color: white;
        }
        
        .filter-group select:focus {
            border-color: #3498db;
            box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.1);
            outline: none;
        }
        
        /* تحسين مظهر select2 */
        .select2-container--default .select2-selection--single {
            border: 2px solid #e2e8f0 !important;
            border-radius: 12px !important;
            height: 42px !important;
            padding: 5px 10px !important;
            transition: all 0.3s ease;
        }
        
        .select2-container--default .select2-selection--single:hover {
            border-color: #3498db !important;
        }
        
        .select2-container--default.select2-container--open .select2-selection--single {
            border-color: #3498db !important;
            box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.1);
        }
        
        .select2-container--default .select2-selection--single .select2-selection__rendered {
            line-height: 28px !important;
            color: #4a5568;
        }
        
        /* أزرار التحكم */
        .filter-actions {
            display: flex;
            gap: 10px;
            align-items: flex-end;
            justify-content: flex-start;
            margin-top: 10px;
        }
        
        .filter-actions .btn {
            border-radius: 12px;
            padding: 8px 20px;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .filter-actions .btn-primary {
            background: linear-gradient(135deg, #3498db, #2980b9);
            border: none;
        }
        
        .filter-actions .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(52, 152, 219, 0.3);
        }
        
        .filter-actions .btn-danger {
            background: linear-gradient(135deg, #e74c3c, #c0392b);
            border: none;
        }
        
        .filter-actions .btn-danger:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(231, 76, 60, 0.3);
        }
        
        /* تنسيق الصفوف */
        .filters-row {
            margin-bottom: 10px;
        }
        
        /* تنسيق المسافات بين الأعمدة */
        .filters-row [class*="col-"] {
            padding: 0 10px;
        }
        
        /* تنسيق العناوين */
        .filters-title {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid #e9ecef;
        }
        
        .filters-title i {
            font-size: 24px;
            color: #3498db;
        }
        
        .filters-title h4 {
            margin: 0;
            font-size: 18px;
            font-weight: 700;
            color: #2c3e50;
        }
        
        /* تحسين للشاشات الصغيرة */
        @media (max-width: 768px) {
            .filters-section {
                padding: 20px 15px;
            }
            
            .filter-group {
                margin-bottom: 15px;
            }
            
            .filter-actions {
                margin-top: 20px;
                justify-content: center;
            }
            
            .filters-row [class*="col-"] {
                padding: 0 8px;
            }
        }
        
        /* تأثيرات حركية */
        .filter-group select,
        .select2-container--default .select2-selection--single {
            transition: all 0.2s ease-in-out;
        }
        
        /* تنسيق البادج لعدد الفلاتر النشطة */
        .active-filters-badge {
            background: #3498db;
            color: white;
            border-radius: 20px;
            padding: 2px 10px;
            font-size: 12px;
            margin-right: 10px;
        }
    </style>
    
    <div class="filters-section">
        {{-- عنوان الفلاتر --}}
        <div class="filters-title">
           
            @php
                $activeFilters = 0;
                if(request()->get('location_id_1')) $activeFilters++;
                if(request()->get('location_id_2')) $activeFilters++;
                if(request()->get('brand_id')) $activeFilters++;
                if(request()->get('status')) $activeFilters++;
                if(request()->get('category_id')) $activeFilters++;
                if(request()->get('sub_category_id')) $activeFilters++;
                if(request()->get('unit_id')) $activeFilters++;
                if(request()->get('tax_type')) $activeFilters++;
                if(request()->get('custom_field1')) $activeFilters++;
                if(request()->get('custom_field2')) $activeFilters++;
                if(request()->get('custom_field3')) $activeFilters++;
                 if(request()->get('date_filter')) $activeFilters++;  
            @endphp
            @if($activeFilters > 0)
                <span class="active-filters-badge">{{ $activeFilters }} {{ __('active_filters') }}</span>
            @endif
        </div>
        
        {{-- الصف الأول: الفروع والحالة --}}
        <div class="row filters-row">
            <div class="col-md-3 col-sm-6">
                <div class="filter-group">
                    {!! Form::label('location_id_1', __('missing_product.source_branch') . ' <span class="text-danger">*</span>', [], false) !!}
                    {!! Form::select('location_id_1', $business_locations, request()->get('location_id_1'), ['class' => 'form-control select2', 'placeholder' => __('messages.please_select'), 'id' => 'location_id_1', 'required']); !!}
                </div>
            </div>
            
            <div class="col-md-3 col-sm-6">
                <div class="filter-group">
                    {!! Form::label('location_id_2', __('missing_product.target_branch') . ' <span class="text-danger">*</span>', [], false) !!}
                    {!! Form::select('location_id_2', $business_locations, request()->get('location_id_2'), ['class' => 'form-control select2', 'placeholder' => __('messages.please_select'), 'id' => 'location_id_2', 'required']); !!}
                </div>
            </div>
            
            <div class="col-md-3 col-sm-6">
                <div class="filter-group">
                    {!! Form::label('brand_id', __('missing_product.brand')) !!}
                    {!! Form::select('brand_id', $brands, request()->get('brand_id'), ['class' => 'form-control select2 submit_on_change', 'placeholder' => __('missing_product.all'), 'id' => 'brand_id']); !!}
                </div>
            </div>
            
            <div class="col-md-3 col-sm-6">
                <div class="filter-group">
                    {!! Form::label('status', __('missing_product.status')) !!}
                    {!! Form::select('status', ['active' => __('missing_product.active'), 'inactive' => __('missing_product.disactive')], request()->get('status'), ['class' => 'form-control select2 submit_on_change', 'placeholder' => __('missing_product.all'), 'id' => 'status']); !!}
                </div>
            </div>
        </div>
        
        {{-- الصف الثاني: الأصناف والضريبة --}}
        <div class="row filters-row">
            <div class="col-md-3 col-sm-6">
                <div class="filter-group">
                    {!! Form::label('category_id', __('product.category')) !!}
                    {!! Form::select('category_id', $categories, request()->get('category_id'), ['class' => 'form-control select2 submit_on_change', 'placeholder' => __('missing_product.all'), 'id' => 'category_id']); !!}
                </div>
            </div>
            
            <div class="col-md-3 col-sm-6">
                <div class="filter-group">
                    {!! Form::label('sub_category_id', __('missing_product.sub_category')) !!}
                    {!! Form::select('sub_category_id', $sub_categories, request()->get('sub_category_id'), ['class' => 'form-control select2 submit_on_change', 'placeholder' => __('missing_product.all'), 'id' => 'sub_category_id']); !!}
                </div>
            </div>
            
            <div class="col-md-3 col-sm-6">
                <div class="filter-group">
                    {!! Form::label('unit_id', __('product.unit')) !!}
                    {!! Form::select('unit_id', $units, request()->get('unit_id'), ['class' => 'form-control select2 submit_on_change', 'placeholder' => __('missing_product.all'), 'id' => 'unit_id']); !!}
                </div>
            </div>
            
            <div class="col-md-3 col-sm-6">
                <div class="filter-group">
                    {!! Form::label('tax_type', __('missing_product.tax_type')) !!}
                    {!! Form::select('tax_type', ['inclusive' => __('missing_product.inclusive'), 'exclusive' => __('missing_product.exclusive')], request()->get('tax_type'), ['class' => 'form-control select2 submit_on_change', 'placeholder' => __('messages.all'), 'id' => 'tax_type']); !!}
                </div>
            </div>
        </div>
       <div class="col-md-3 col-sm-6">
    <div class="filter-group">
        {!! Form::label('date_filter', 'الكميات المباعة في الفترة') !!}
        {!! Form::text('date_filter', request()->get('date_filter', $default_date), [
            'class'        => 'form-control',
            'id'           => 'date_filter',
            'placeholder'  => 'اختر فترة زمنية',
            'autocomplete' => 'off',
            'readonly'     => true,
        ]) !!}
    </div>
</div>


        {{-- الصف الثالث: الحقول المخصصة --}}
         
        @if(!empty($custom_labels['product']['custom_field_1']) || !empty($custom_labels['product']['custom_field_2']) || !empty($custom_labels['product']['custom_field_3']))
        <div class="row filters-row">
            @if(!empty($custom_labels['product']['custom_field_1']))
            <div class="col-md-3 col-sm-6">
                <div class="filter-group">
                    {!! Form::label('custom_field1', $custom_labels['product']['custom_field_1']) !!}
                    {!! Form::select('custom_field1', $custom_field1_values, request()->get('custom_field1'), ['class' => 'form-control select2 submit_on_change', 'placeholder' => __('missing_product.all'), 'id' => 'custom_field1']); !!}
                </div>
            </div>
            @endif

            @if(!empty($custom_labels['product']['custom_field_2']))
            <div class="col-md-3 col-sm-6">
                <div class="filter-group">
                    {!! Form::label('custom_field2', $custom_labels['product']['custom_field_2']) !!}
                    {!! Form::select('custom_field2', $custom_field2_values, request()->get('custom_field2'), ['class' => 'form-control select2 submit_on_change', 'placeholder' => __('missing_product.all'), 'id' => 'custom_field2']); !!}
                </div>
            </div>
            @endif

            @if(!empty($custom_labels['product']['custom_field_3']))
            <div class="col-md-3 col-sm-6">
                <div class="filter-group">
                    {!! Form::label('custom_field3', $custom_labels['product']['custom_field_3']) !!}
                    {!! Form::select('custom_field3', $custom_field3_values, request()->get('custom_field3'), ['class' => 'form-control select2 submit_on_change', 'placeholder' => __('missing_product.all'), 'id' => 'custom_field3']); !!}
                </div>
            </div>
            @endif
        </div>
        @endif
          
    
    {!! Form::close() !!}
@endcomponent

<script>
$(document).ready(function() {
    // زر مسح الفلاتر
    $('#reset_filters_btn').click(function(e) {
        e.preventDefault();
        
        // تفريغ جميع حقول select
        $('#missing_products_filter_form select').each(function() {
            $(this).val('').trigger('change');
        });
        
        // إعادة تعيين select2
        $('#location_id_1, #location_id_2, #brand_id, #status, #category_id, #sub_category_id, #unit_id, #tax_type').val('').trigger('change');
        
        // تقديم النموذج لعرض الكل
        $('#missing_products_filter_form').submit();
    });
    
    // تحسين مظهر select2
    $('.select2').select2({
        dropdownAutoWidth: true,
        width: '100%',
        placeholder: function() {
            return $(this).data('placeholder') || '{{ __('messages.please_select') }}';
        }
    });
    
    // إضافة كلاس للـ select2 المحمل
    $('.select2').on('select2:open', function() {
        $('.select2-dropdown').css('z-index', '9999');
    });
    
    // تأثير التحميل عند الضغط على زر البحث
    $('#filter_btn').click(function() {
        $(this).html('<i class="fas fa-spinner fa-spin"></i> {{ __('messages.loading') }}').attr('disabled', true);
        $('#missing_products_filter_form').submit();
    });
    
    // التحقق من صحة النموذج قبل الإرسال
    $('#missing_products_filter_form').submit(function(e) {
        let location1 = $('#location_id_1').val();
        let location2 = $('#location_id_2').val();
        
        if (!location1 || !location2) {
            e.preventDefault();
            Swal.fire({
                icon: 'warning',
                title: '{{ __("messages.warning") }}',
                text: '{{ __("missing_product.both_branches_required") }}',
                confirmButtonColor: '#3498db'
            });
            $('#filter_btn').html('<i class="fas fa-search"></i> {{ __("messages.search") }}').attr('disabled', false);
            return false;
        }
        
        return true;
    });
});
</script>
        </div>
    </div>

    <div class="row">
        <div class="col-md-12">
            @component('components.widget', ['class' => 'box-primary'])
                <div class="table-responsive">
                    <table class="table table-bordered table-striped"
                           id="missing_products_table"
                           style="width: 100%;">
                        <thead>
                            <tr>
                                <th>{{ __('product.image') }}</th>
                                <th>{{ __('missing_product.product_name') }}</th>
                                <th>SKU</th>
                                <th>{{ __('product.product_type') }}</th>
                                <th>{{ __('product.brand') }}</th>
                                <th>{{ __('product.unit') }}</th>
                                <th>{{ __('product.category') }}</th>
                                <th>{{ __('product.sub_category') }}</th>
                                <th>{{ __('missing_product.tax_type') }}</th>
                                <th>{{ __('missing_product.status') }}</th>

                                @if(!empty($custom_labels['product']['custom_field_1']))
                                    <th>{{ $custom_labels['product']['custom_field_1'] }}</th>
                                @endif
                                @if(!empty($custom_labels['product']['custom_field_2']))
                                    <th>{{ $custom_labels['product']['custom_field_2'] }}</th>
                                @endif
                                @if(!empty($custom_labels['product']['custom_field_3']))
                                    <th>{{ $custom_labels['product']['custom_field_3'] }}</th>
                                @endif
                                @if(!empty($custom_labels['product']['custom_field_4']))
                                    <th>{{ $custom_labels['product']['custom_field_4'] }}</th>
                                @endif
                                @if(!empty($custom_labels['product']['custom_field_5']))
                                    <th>{{ $custom_labels['product']['custom_field_5'] }}</th>
                                @endif
                                @if(!empty($custom_labels['product']['custom_field_6']))
                                    <th>{{ $custom_labels['product']['custom_field_6'] }}</th>
                                @endif
                                @if(!empty($custom_labels['product']['custom_field_7']))
                                    <th>{{ $custom_labels['product']['custom_field_7'] }}</th>
                                @endif

                                <th>{{ __('missing_product.solded_quantity', ['location' => $loc1_name]) }}</th>
                                <th>{{ __('missing_product.quantity_in_location', ['location' => $loc1_name]) }}</th>
                                <th>{{ __('missing_product.quantity_in_location', ['location' => $loc2_name]) }}</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            @endcomponent
        </div>
    </div>
</section>
@endsection

@section('javascript')
<script type="text/javascript">
    var missing_products_table;

   $(document).ready(function () {

    // 1. تعريف الجدول
    missing_products_table = $('#missing_products_table').DataTable({
        processing: true,
        serverSide: true,
        stateSave:  true,
        aaSorting:  [[1, 'asc']],
        ajax: {
            url: "{{ action([\App\Http\Controllers\MissingProductController::class, 'getMissingProductsWithSales']) }}",
            data: function (d) {
                d.location_id_1   = $('#location_id_1').val();
                d.location_id_2   = $('#location_id_2').val();
                d.date_filter     = $('#date_filter').val();
                d.brand_id        = $('#brand_id').val();
                d.status          = $('#status').val();
                d.category_id     = $('#category_id').val();
                d.sub_category_id = $('#sub_category_id').val();
                d.unit_id         = $('#unit_id').val();
                d.tax_type        = $('#tax_type').val();
                d.custom_field1 = $('#custom_field1').val();
d.custom_field2 = $('#custom_field2').val();
d.custom_field3 = $('#custom_field3').val();
            }
        },
        columns: [
            { data: 'image', name: 'image', orderable: false, searchable: false },
            { data: 'name',  name: 'p.name' },
            { data: 'sku',   name: 'p.sku' },
            { data: 'type',  name: 'p.type' },
            { data: 'brand_name', name: 'b.name' },
            { data: 'unit_name',  name: 'u.actual_name' },
            { data: 'category_name', name: 'cat.name' },
            { data: 'sub_category_name', name: 'sub_cat.name' },
            { data: 'tax_type', name: 'p.tax_type' },
            { data: 'is_inactive', name: 'p.is_inactive' },

            @if(!empty($custom_labels['product']['custom_field_1']))
                { data: 'product_custom_field1', name: 'p.product_custom_field1' },
            @endif
            @if(!empty($custom_labels['product']['custom_field_2']))
                { data: 'product_custom_field2', name: 'p.product_custom_field2' },
            @endif
            @if(!empty($custom_labels['product']['custom_field_3']))
                { data: 'product_custom_field3', name: 'p.product_custom_field3' },
            @endif
            @if(!empty($custom_labels['product']['custom_field_4']))
                { data: 'product_custom_field4', name: 'p.product_custom_field4' },
            @endif
            @if(!empty($custom_labels['product']['custom_field_5']))
                { data: 'product_custom_field5', name: 'p.product_custom_field5' },
            @endif
            @if(!empty($custom_labels['product']['custom_field_6']))
                { data: 'product_custom_field6', name: 'p.product_custom_field6' },
            @endif
            @if(!empty($custom_labels['product']['custom_field_7']))
                { data: 'product_custom_field7', name: 'p.product_custom_field7' },
            @endif

            { data: 'qty_sold_loc1', name: 'sold.total_sold', orderable: true, searchable: false },
             { data: 'qty_in_loc1', name: 'vld1.qty_available' },
            { data: 'qty_in_loc2',   name: 'vld2.qty_available' }
        ],
        dom: '<"row"<"col-md-12"<"table-controls"lBf>>>rtip',
    });

   // 1. تحديد تاريخ اليوم بصيغة YYYY-MM-DD
    var today = new Date();
    var dd = String(today.getDate()).padStart(2, '0');
    var mm = String(today.getMonth() + 1).padStart(2, '0'); 
    var yyyy = today.getFullYear();
    var todayFormatted = yyyy + '-' + mm + '-' + dd;

    // 2. وضع تاريخ اليوم كقيمة افتراضية في الحقل عند تحميل الصفحة
    if ($('#date_filter').val() == '') {
        $('#date_filter').val(todayFormatted);
    }

    // 3. تهيئة الـ Datepicker مع القيود المطلوبة
   // 2. تهيئة الـ Datepicker المتوافق مع الصورة (Bootstrap style)
   $('#date_filter').daterangepicker({
    maxSpan:   { days: 6 },
    maxDate:   moment(),
    minDate:   moment().subtract(6, 'days'),
    startDate: moment().subtract(6, 'days'),
    endDate:   moment(),
    singleDatePicker: false,
    linkedCalendars: false,
    showDropdowns: false,
    ranges: false,
    opens: 'right',
    locale: {
        format:      'YYYY-MM-DD',
        separator:   ' - ',
        applyLabel:  'تطبيق',
        cancelLabel: 'إلغاء',
    },
}, function(start, end) {
    if ($('#location_id_1').val() && $('#location_id_2').val()) {
        missing_products_table.ajax.reload();
    }
});
// بعد تهيئة الـ daterangepicker أضف
$('#date_filter').on('show.daterangepicker', function(ev, picker) {
    picker.container.find('.drp-calendar.right').hide();  // ← اخفي الأيمن
    picker.container.find('.drp-calendar.left').addClass('single');  // ← خلي الأيسر single
});


    // 2. تحديث الجدول عند تغيير التاريخ يدوياً (كتابةً)
    $('#date_filter').on('change', function () {
        missing_products_table.ajax.reload();
    });
    // 4. مراقبة باقي الفلاتر (قمت بإزالة شرط الفروع لتحديث الجدول بمجرد التغيير)
    $(document).on('change', 
        '#location_id_1, #location_id_2, #brand_id, #unit_id, #status, #category_id, #sub_category_id, #tax_type, #custom_field1, #custom_field2, #custom_field3',
        function () {
           if ($('#location_id_1').val() && $('#location_id_2').val()) {
            missing_products_table.ajax.reload();
        }
        }
    );
});
</script>
@endsection