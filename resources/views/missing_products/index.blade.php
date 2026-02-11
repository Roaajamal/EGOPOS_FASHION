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
    
    {{-- الصف الأول: الفروع والحالة --}}
    <div class="row">
        <div class="col-md-3">
            <div class="form-group">
                {!! Form::label('location_id_1', __('missing_product.source_branch')) !!}
                {!! Form::select('location_id_1', $business_locations, request()->get('location_id_1'), ['class' => 'form-control select2', 'placeholder' => __('messages.please_select'), 'id' => 'location_id_1', 'required']); !!}
            </div>
        </div>
        <div class="col-md-3">
            <div class="form-group">
                {!! Form::label('location_id_2',__('missing_product.target_branch')) !!}
                {!! Form::select('location_id_2', $business_locations, request()->get('location_id_2'), ['class' => 'form-control select2', 'placeholder' => __('messages.please_select'), 'id' => 'location_id_2', 'required']); !!}
            </div>
        </div>
        <div class="col-md-3">
            <div class="form-group">
                {!! Form::label('brand_id', __('missing_product.brand')) !!}
                {!! Form::select('brand_id', $brands, request()->get('brand_id'), ['class' => 'form-control select2 submit_on_change', 'placeholder' => __('missing_product.all'), 'id' => 'brand_id']); !!}
            </div>
        </div>
        <div class="col-md-3">
            <div class="form-group">
                {!! Form::label('status', __('missing_product.status')) !!}
                {!! Form::select('status', ['active' =>  __('missing_product.active'), 'inactive' =>  __('missing_product.disactive')], request()->get('status'), ['class' => 'form-control select2 submit_on_change', 'placeholder' => __('missing_product.all'), 'id' => 'status']); !!}
            </div>
        </div>
    </div>

    {{-- الصف الثاني: الأصناف والضريبة --}}
    <div class="row">
        <div class="col-md-3">
            <div class="form-group">
                {!! Form::label('category_id',__('product.category')) !!}
                {!! Form::select('category_id', $categories, request()->get('category_id'), ['class' => 'form-control select2 submit_on_change', 'placeholder' =>  __('missing_product.all'), 'id' => 'category_id']); !!}
            </div>
        </div>
        <div class="col-md-3">
            <div class="form-group">
                {!! Form::label('sub_category_id', __('missing_product.sub_category')) !!}
                {!! Form::select('sub_category_id', $sub_categories, request()->get('sub_category_id'), ['class' => 'form-control select2 submit_on_change', 'placeholder' =>  __('missing_product.all'), 'id' => 'sub_category_id']); !!}
            </div>
        </div>
        <div class="col-md-3">
            <div class="form-group">
                {!! Form::label('unit_id', __('product.unit')) !!}
                {!! Form::select('unit_id', $units, request()->get('unit_id'), ['class' => 'form-control select2 submit_on_change', 'placeholder' => __('missing_product.all'), 'id' => 'unit_id']); !!}
            </div>
        </div>
        <div class="col-md-3">
            <div class="form-group">
                {!! Form::label('tax_type', __('missing_product.tax_type')) !!} 
                {!! Form::select('tax_type', ['inclusive' => __('missing_product.inclusive'), 'exclusive' => __('missing_product.exclusive')], request()->get('tax_type'), ['class' => 'form-control select2 submit_on_change', 'placeholder' => 'الكل', 'id' => 'tax_type']); !!}
            </div>
        </div>
    </div>

   
    {!! Form::close() !!}
@endcomponent
        </div>
    </div>

    <div class="row">
        <div class="col-md-12">
            @component('components.widget', ['class' => 'box-primary'])
                <div class="table-responsive">
                    <table class="table table-bordered table-striped" id="missing_products_table" style="width: 100%;">
                        <thead>
                            <tr>
                                <th>{{ __('product.image')}}</th>
                                <th> {{ __('missing_product.product_name')}}</th>
                                <th> (SKU)</th>
                                <th>{{ __('product.product_type')}}</th>
                                <th> {{ __('product.brand')}}</th>
                                <th>{{ __('product.unit')}}</th>
                                <th> {{ __('product.category')}}</th>
                                 <th> {{ __('product.sub_category')}}</th> 
                                  <th>{{ __('missing_product.tax_type')}} </th>
                                <th>{{  __('missing_product.status')}}</th>
                               
                                @if(!empty($custom_labels['product']['custom_field_1'])) <th>{{ $custom_labels['product']['custom_field_1'] }}</th> @endif
                                @if(!empty($custom_labels['product']['custom_field_2'])) <th>{{ $custom_labels['product']['custom_field_2'] }}</th> @endif
                                @if(!empty($custom_labels['product']['custom_field_3'])) <th>{{ $custom_labels['product']['custom_field_3'] }}</th> @endif
                                @if(!empty($custom_labels['product']['custom_field_4'])) <th>{{ $custom_labels['product']['custom_field_4'] }}</th> @endif
                                @if(!empty($custom_labels['product']['custom_field_5'])) <th>{{ $custom_labels['product']['custom_field_5'] }}</th> @endif
                                @if(!empty($custom_labels['product']['custom_field_6'])) <th>{{ $custom_labels['product']['custom_field_6'] }}</th> @endif
                                @if(!empty($custom_labels['product']['custom_field_7'])) <th>{{ $custom_labels['product']['custom_field_7'] }}</th> @endif

                                <th>  {{ __('missing_product.quantity_in_location', ['location' => $loc1_name]) }} </th>
                                <th>  {{ __('missing_product.quantity_in_location', ['location' => $loc2_name]) }} </th>
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
    d.brand_id = $('#brand_id').val();
    d.unit_id = $('#unit_id').val();
    d.status = $('#status').val();
    // الفلاتر الجديدة
    d.category_id = $('#category_id').val();
    d.sub_category_id = $('#sub_category_id').val();
    d.tax_type = $('#tax_type').val();
}
            },
            columns: [
                { data: 'image', name: 'p.image', orderable: false, searchable: false },
                { data: 'name', name: 'p.name' },
                { data: 'sku', name: 'p.sku' },
                { data: 'type', name: 'p.type' },
                { data: 'brand_name', name: 'b.name' },
                { data: 'unit_name', name: 'u.actual_name' },
                { data: 'category_name', name: 'cat.name' },
                { data: 'sub_category_name', name: 'sub_cat.name' },
                { data: 'tax_type', name: 'p.tax_type' },
                { data: 'is_inactive', name: 'p.is_inactive' },
                
                @if(!empty($custom_labels['product']['custom_field_1'])) { data: 'product_custom_field1', name: 'p.product_custom_field1' }, @endif
                @if(!empty($custom_labels['product']['custom_field_2'])) { data: 'product_custom_field2', name: 'p.product_custom_field2' }, @endif
                @if(!empty($custom_labels['product']['custom_field_3'])) { data: 'product_custom_field3', name: 'p.product_custom_field3' }, @endif
                @if(!empty($custom_labels['product']['custom_field_4'])) { data: 'product_custom_field4', name: 'p.product_custom_field4' }, @endif
                @if(!empty($custom_labels['product']['custom_field_5'])) { data: 'product_custom_field5', name: 'p.product_custom_field5' }, @endif
                @if(!empty($custom_labels['product']['custom_field_6'])) { data: 'product_custom_field6', name: 'p.product_custom_field6' }, @endif
                @if(!empty($custom_labels['product']['custom_field_7'])) { data: 'product_custom_field7', name: 'p.product_custom_field7' }, @endif

                { data: 'qty_in_loc1', name: 'vld1.qty_available' },
                { data: 'qty_in_loc2', name: 'vld2.qty_available' }
            ],
            dom: '<"row"<"col-md-12"<"table-controls"lBf>>>rtip',
            
        });

        // التحديث التلقائي الفوري عند تغيير الفلاتر
        $(document).on('change', '#location_id_1, #location_id_2, #brand_id, #unit_id, #status,#category_id, #sub_category_id, #tax_type', function() {
            if ($('#location_id_1').val() && $('#location_id_2').val()) {
                missing_products_table.ajax.reload();
            }
        });
    });
</script>
@endsection