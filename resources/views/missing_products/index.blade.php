@php
$custom_labels = json_decode(session('business.custom_labels'), true);
@endphp

@section('css')
<style>
    /* حاوية التحكم بالجدول */
    .table-controls {
        display: flex;
        justify-content: space-between; /* توزيع العناصر على الأطراف */
        align-items: center;
        margin-bottom: 15px;
    }

    /* جعل الأزرار في المنتصف */
    .dt-buttons {
        flex-grow: 1;
        text-align: center;
        margin-left: 180px; /* موازنة لإزاحة خانة البحث */
    }

    /* جعل خانة البحث على اليسار */
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
@section('title', __('missing_product.missing_product_report') )

@section('content')
<section class="content-header">
    <h1 class="tw-text-xl md:tw-text-3xl tw-font-bold tw-text-black"> {{__('missing_product.missing_product_report')}} </h1>
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
            
            {{-- فلتر العلامة التجارية - يظهر فقط إذا كان عمود البراند مفعل --}}
            @if(is_col_visible('missing_products', 'brand'))
            <div class="col-md-3 col-sm-6">
                <div class="filter-group">
                    {!! Form::label('brand_id', __('missing_product.brand')) !!}
                    {!! Form::select('brand_id', $brands, request()->get('brand_id'), ['class' => 'form-control select2 submit_on_change', 'placeholder' => __('missing_product.all'), 'id' => 'brand_id']); !!}
                </div>
            </div>
            @endif
            
            {{-- فلتر الحالة - يظهر فقط إذا كان عمود الحالة مفعل --}}
            @if(is_col_visible('missing_products', 'status'))
            <div class="col-md-3 col-sm-6">
                <div class="filter-group">
                    {!! Form::label('status', __('missing_product.status')) !!}
                    {!! Form::select('status', ['active' => __('missing_product.active'), 'inactive' => __('missing_product.disactive')], request()->get('status'), ['class' => 'form-control select2 submit_on_change', 'placeholder' => __('missing_product.all'), 'id' => 'status']); !!}
                </div>
            </div>
            @endif
        </div>
        
        {{-- الصف الثاني: الأصناف والضريبة --}}
        <div class="row filters-row">
            {{-- فلتر التصنيف الرئيسي --}}
            @if(is_col_visible('missing_products', 'category'))
            <div class="col-md-3 col-sm-6">
                <div class="filter-group">
                    {!! Form::label('category_id', __('product.category')) !!}
                    {!! Form::select('category_id', $categories, request()->get('category_id'), ['class' => 'form-control select2 submit_on_change', 'placeholder' => __('missing_product.all'), 'id' => 'category_id']); !!}
                </div>
            </div>
            @endif
            
            {{-- فلتر التصنيف الفرعي --}}
            @if(is_col_visible('missing_products', 'sub_category'))
            <div class="col-md-3 col-sm-6">
                <div class="filter-group">
                    {!! Form::label('sub_category_id', __('missing_product.sub_category')) !!}
                    {!! Form::select('sub_category_id', $sub_categories, request()->get('sub_category_id'), ['class' => 'form-control select2 submit_on_change', 'placeholder' => __('missing_product.all'), 'id' => 'sub_category_id']); !!}
                </div>
            </div>
            @endif
            
            {{-- فلتر الوحدة --}}
            @if(is_col_visible('missing_products', 'unit'))
            <div class="col-md-3 col-sm-6">
                <div class="filter-group">
                    {!! Form::label('unit_id', __('product.unit')) !!}
                    {!! Form::select('unit_id', $units, request()->get('unit_id'), ['class' => 'form-control select2 submit_on_change', 'placeholder' => __('missing_product.all'), 'id' => 'unit_id']); !!}
                </div>
            </div>
            @endif
            
            {{-- فلتر نوع الضريبة --}}
            @if(is_col_visible('missing_products', 'tax'))
            <div class="col-md-3 col-sm-6">
                <div class="filter-group">
                    {!! Form::label('tax_type', __('missing_product.tax_type')) !!}
                    {!! Form::select('tax_type', ['inclusive' => __('missing_product.inclusive'), 'exclusive' => __('missing_product.exclusive')], request()->get('tax_type'), ['class' => 'form-control select2 submit_on_change', 'placeholder' => __('messages.all'), 'id' => 'tax_type']); !!}
                </div>
            </div>
            @endif

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
                    <table class="table table-bordered table-striped" id="missing_products_table" style="width: 100%;">
                        <thead>
                            <tr>
                                @if(is_col_visible('missing_products', 'image'))
        <th>{{ __('product.image')}}</th>
    @endif

    @if(is_col_visible('missing_products', 'product_name'))
        <th>{{ __('missing_product.product_name')}}</th>
    @endif

    @if(is_col_visible('missing_products', 'sku'))
        <th> (SKU)</th>
    @endif

    @if(is_col_visible('missing_products', 'product_type'))
        <th>{{ __('product.product_type')}}</th>
    @endif

    @if(is_col_visible('missing_products', 'brand'))
        <th>{{ __('product.brand')}}</th>
    @endif

    @if(is_col_visible('missing_products', 'unit'))
        <th>{{ __('product.unit')}}</th>
    @endif

    @if(is_col_visible('missing_products', 'category'))
        <th>{{ __('product.category')}}</th>
    @endif

    @if(is_col_visible('missing_products', 'sub_category'))
        <th>{{ __('product.sub_category')}}</th> 
    @endif

    @if(is_col_visible('missing_products', 'tax'))
        <th>{{ __('missing_product.tax_type')}}</th>
    @endif

    @if(is_col_visible('missing_products', 'status'))
        <th>{{ __('missing_product.status')}}</th>
    @endif
                               
                                @if(!empty($custom_labels['product']['custom_field_1']) && is_col_visible('missing_products', 'custom_field1')) <th>{{ $custom_labels['product']['custom_field_1'] }}</th> @endif
@if(!empty($custom_labels['product']['custom_field_2']) && is_col_visible('missing_products', 'custom_field2')) <th>{{ $custom_labels['product']['custom_field_2'] }}</th> @endif
@if(!empty($custom_labels['product']['custom_field_3']) && is_col_visible('missing_products', 'custom_field3')) <th>{{ $custom_labels['product']['custom_field_3'] }}</th> @endif
@if(!empty($custom_labels['product']['custom_field_4']) && is_col_visible('missing_products', 'custom_field4')) <th>{{ $custom_labels['product']['custom_field_4'] }}</th> @endif
@if(!empty($custom_labels['product']['custom_field_5']) && is_col_visible('missing_products', 'custom_field5')) <th>{{ $custom_labels['product']['custom_field_5'] }}</th> @endif
@if(!empty($custom_labels['product']['custom_field_6']) && is_col_visible('missing_products', 'custom_field6')) <th>{{ $custom_labels['product']['custom_field_6'] }}</th> @endif
@if(!empty($custom_labels['product']['custom_field_7']) && is_col_visible('missing_products', 'custom_field7')) <th>{{ $custom_labels['product']['custom_field_7'] }}</th> @endif

                                @if(is_col_visible('missing_products', 'qty_source'))
                               <th>{{ __('missing_product.quantity_in_location', ['location' => $loc1_name]) }}</th>
                                @endif

                               {{-- الكمية في المستهدف --}}
                               @if(is_col_visible('missing_products', 'qty_target'))
                               <th>{{ __('missing_product.quantity_in_location', ['location' => $loc2_name]) }}</th>
                                 @endif
                            </tr>
                        </thead>
                        <tbody>
                            {{-- البيانات سيتم جلبها عبر AJAX --}}
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
    var missing_products_table;

    $(document).ready(function() {
        missing_products_table = $('#missing_products_table').DataTable({
            processing: true,
            serverSide: true,
            stateSave: true, ////// لحفظ حالة اخفاء\ اظهار الاعمدة 
            aaSorting: [[1, 'asc']],
            ajax: {
                url: "{{ action([\App\Http\Controllers\MissingProductController::class, 'getMissingProducts']) }}",
                data: function(d) {
    d.location_id_1 = $('#location_id_1').val();
    d.location_id_2 = $('#location_id_2').val();
   if($('#brand_id').length) d.brand_id = $('#brand_id').val();
    if($('#status').length) d.status = $('#status').val();
    if($('#category_id').length) d.category_id = $('#category_id').val();
    if($('#sub_category_id').length) d.sub_category_id = $('#sub_category_id').val();
    if($('#unit_id').length) d.unit_id = $('#unit_id').val();
    if($('#tax_type').length) d.tax_type = $('#tax_type').val();
    d.custom_field1 = $('#custom_field1').val();
d.custom_field2 = $('#custom_field2').val();
d.custom_field3 = $('#custom_field3').val();
}
            },
            columns: [
              @if(is_col_visible('missing_products', 'image'))
        { data: 'image', name: 'image', orderable: false, searchable: false },
    @endif

    @if(is_col_visible('missing_products', 'product_name'))
        { data: 'name', name: 'p.name' },
    @endif

    @if(is_col_visible('missing_products', 'sku'))
        { data: 'sku', name: 'p.sku' },
    @endif

    @if(is_col_visible('missing_products', 'product_type'))
        { data: 'type', name: 'p.type' },
    @endif

    @if(is_col_visible('missing_products', 'brand'))
        { data: 'brand_name', name: 'b.name' }, // تم تصحيح الـ name هنا
    @endif

    @if(is_col_visible('missing_products', 'unit'))
        { data: 'unit_name', name: 'u.actual_name' },
    @endif

    @if(is_col_visible('missing_products', 'category'))
        { data: 'category_name', name: 'cat.name' },
    @endif

    @if(is_col_visible('missing_products', 'sub_category'))
        { data: 'sub_category_name', name: 'sub_cat.name' },
    @endif

    @if(is_col_visible('missing_products', 'tax'))
        { data: 'tax_type', name: 'p.tax_type' }, //       
    @endif

    @if(is_col_visible('missing_products', 'status'))
        { data: 'is_inactive', name: 'p.is_inactive' },
    @endif
                
               @if(!empty($custom_labels['product']['custom_field_1']) && is_col_visible('missing_products', 'custom_field1')) { data: 'product_custom_field1', name: 'p.product_custom_field1' }, @endif
@if(!empty($custom_labels['product']['custom_field_2']) && is_col_visible('missing_products', 'custom_field2')) { data: 'product_custom_field2', name: 'p.product_custom_field2' }, @endif
@if(!empty($custom_labels['product']['custom_field_3']) && is_col_visible('missing_products', 'custom_field3')) { data: 'product_custom_field3', name: 'p.product_custom_field3' }, @endif
@if(!empty($custom_labels['product']['custom_field_4']) && is_col_visible('missing_products', 'custom_field4')) { data: 'product_custom_field4', name: 'p.product_custom_field4' }, @endif
@if(!empty($custom_labels['product']['custom_field_5']) && is_col_visible('missing_products', 'custom_field5')) { data: 'product_custom_field5', name: 'p.product_custom_field5' }, @endif
@if(!empty($custom_labels['product']['custom_field_6']) && is_col_visible('missing_products', 'custom_field6')) { data: 'product_custom_field6', name: 'p.product_custom_field6' }, @endif
@if(!empty($custom_labels['product']['custom_field_7']) && is_col_visible('missing_products', 'custom_field7')) { data: 'product_custom_field7', name: 'p.product_custom_field7' }, @endif
               @if(is_col_visible('missing_products', 'qty_source'))
    { data: 'qty_in_loc1', name: 'vld1.qty_available' },
@endif

@if(is_col_visible('missing_products', 'qty_target'))
    { data: 'qty_in_loc2', name: 'vld2.qty_available' }
@endif
            ],
            dom: '<"row"<"col-md-12"<"table-controls"lBf>>>rtip',
            
        });

        // التحديث التلقائي الفوري عند تغيير الفلاتر
        $(document).on('change', '#location_id_1, #location_id_2, #brand_id, #unit_id, #status,#category_id, #sub_category_id, #tax_type, #custom_field1, #custom_field2, #custom_field3', function() {
            if ($('#location_id_1').val() && $('#location_id_2').val()) {
                missing_products_table.ajax.reload();
            }
        });
    });
</script>
@endsection