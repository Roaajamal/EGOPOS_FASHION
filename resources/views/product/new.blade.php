@extends('layouts.app')
@section('title', 'إضافة منتج')

@section('content')
{{-- QZ Tray --}}
<script src="{{ asset('js/qz/qz-tray.js') }}"></script>
<script src="{{ asset('js/qz/rsvp.min.js') }}"></script>
<script src="{{ asset('js/qz/sha256.min.js') }}"></script>
<style>

    :root {
--primary-dark: #074f32;
        --primary-medium: #085d3a;
        --primary-light: #0e7a4c;
        --primary-soft: #e8f3ef;
        --primary-bg-light: #f4faf7;
        --gray-light: #fbfbfc;
        --gray-border: #dce1e6; 
        --text-dark: #2c3e50;
        --text-muted: #7f8c8d;
        --success-green: #28a745;
        --warning-orange: #fd7e14;
        --danger-red: #dc3545;
        --info-blue: #17a2b8;
    }

    

    /* Card Styles - NEW but using original classes */
    .box.box-primary {
        border-radius: 20px;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
        margin-bottom: 28px;
        border: none;
        overflow: hidden;
    }

    .box.box-primary > .box-header {
        background: linear-gradient(135deg, var(--primary-dark) 0%, var(--primary-medium) 100%);
        padding: 18px 25px;
        border-radius: 20px 20px 0 0;
        border-bottom: none;
    }

    .box.box-primary > .box-header .box-title {
        color: white;
        font-weight: 600;
        font-size: 1.2rem;
    }

    .box.box-primary > .box-header .box-title i {
        color: white;
        margin-left: 10px;
    }

    .box.box-primary > .box-body {
        padding: 28px;
    }

    .box.box-primary > .box-footer {
        padding: 16px 24px;
        background: var(--gray-light);
        border-top: 1px solid var(--gray-border);
        border-radius: 0 0 20px 20px;
    }

    /* Highlight Basic Info Section */
    .basic-info-highlight .box-body {
        background: linear-gradient(135deg, #ffffff 0%, var(--primary-bg-light) 100%);
        border-left: 4px solid var(--primary-medium);
    }

    /* Form Groups - Enhanced */
    .form-group {
        margin-bottom: 22px;
    }

    .form-group label {
        font-weight: 600;
        font-size: 0.85rem;
        margin-bottom: 8px;
        color: var(--text-dark);
        display: block;
    }

    .form-group label .text-danger {
        color: var(--danger-red);
        font-size: 1rem;
    }

    .form-control, select.form-control {
        border-radius: 12px;
        border: 1px solid var(--gray-border);
        padding: 10px 16px;
        transition: all 0.2s;
        font-size: 0.9rem;
    }

    .form-control:focus, select.form-control:focus {
        border-color: var(--primary-medium);
        box-shadow: 0 0 0 3px rgba(8, 93, 58, 0.1);
        outline: none;
    }

    /* Input Group for + buttons */
   .input-group {
    display: flex !important;
    align-items: stretch;
    width: 100% !important;
    border: 1px solid var(--gray-border);
    border-radius: 12px;
    background-color: #fff;
    overflow: hidden;
    transition: all 0.3s ease;
    box-shadow: none;
}
.input-group .form-control, 
.input-group .select2-container--default .select2-selection--single {
    flex: 1; 
    border: none !important;
    height: 42px !important;
    box-shadow: none !important;
    border-radius: 12px 0 0 12px !important; 
}
.input-group-btn {
    display: flex !important;
    width: auto !important;
    border: none !important;
}

    .input-group .form-control {
    border: none !important;
    box-shadow: none !important;
    height: 42px;
}

  .input-group .input-group-btn .btn {
    background-color: transparent !important;
    border: none !important;
    height: 100%;
    margin: 0 !important;
    padding: 0 12px !important;
    color: var(--primary-medium);
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.2s ease;
    font-size: 1.2rem;
    border-radius: 0 12px 12px 0 !important;
}
.input-group .input-group-btn .btn {
    border-right: 1px solid #f0f0f0 !important;
}
.input-group .btn:hover i {
    transform: scale(1.2);
    color: var(--primary-light);
}

    .input-group .input-group-btn .btn:hover i {
    transform: scale(1.2); 
    color: var(--primary-light);
}

   .input-group .input-group-btn .btn i {
    color: var(--primary-medium);
    font-size: 1.2rem;
    transition: all 0.2s;
}



    /* Section Divider */
    .section-divider {
        border-top: 2px dashed var(--gray-border);
        margin: 28px 0;
        position: relative;
    }

    .section-divider span {
        position: absolute;
        top: -12px;
        right: 20px;
        background: white;
        padding: 0 15px;
        color: var(--primary-medium);
        font-size: 0.85rem;
        font-weight: 600;
    }

    /* Buttons */
    .btn-primary {
        background: var(--primary-medium);
        border: none;
        border-radius: 40px;
        padding: 8px 25px;
        font-weight: 500;
        transition: all 0.3s;
    }

    .btn-primary:hover {
        background: var(--primary-dark);
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(7, 79, 50, 0.3);
    }

    .btn-success {
        background: var(--primary-medium);
        border: none;
        border-radius: 40px;
        padding: 8px 25px;
    }

    .btn-success:hover {
        background: var(--primary-dark);
        transform: translateY(-2px);
    }

    .btn-default {
        border-radius: 40px;
        transition: all 0.3s;
    }

    /* Table Styles */
    .table {
        width: 100%;
        border-collapse: separate;
        border-spacing: 0;
    }

    .table thead tr {
        background: var(--primary-bg-light);
    }

    .table thead th {
        padding: 14px 12px;
        font-weight: 600;
        font-size: 0.85rem;
        color: var(--primary-dark);
        text-align: right;
        border-bottom: 2px solid var(--primary-light);
    }

    .table tbody td {
        padding: 12px;
        border-bottom: 1px solid var(--gray-border);
        vertical-align: middle;
    }

    .table tbody tr:hover {
        background: var(--primary-bg-light);
    }

    /* Collapsible Box */
    .collapsible-modern {
        background: white;
        border-radius: 20px;
        margin-bottom: 28px;
        border: 1px solid var(--gray-border);
        overflow: hidden;
    }

    .collapsible-modern-header {
        background: linear-gradient(135deg, #f8f9fa 0%, #ffffff 100%);
        padding: 18px 25px;
        cursor: pointer;
        display: flex;
        justify-content: space-between;
        align-items: center;
        transition: all 0.3s;
    }

    .collapsible-modern-header:hover {
        background: var(--primary-bg-light);
    }

    .collapsible-modern-header h3 {
        margin: 0;
        font-size: 1.1rem;
        font-weight: 600;
        color: var(--primary-dark);
    }

    .collapsible-modern-header i.fa-chevron-down {
        transition: transform 0.3s;
        color: var(--primary-medium);
    }

    .collapsible-modern-body {
        padding: 25px;
        display: none;
        border-top: 1px solid var(--gray-border);
    }

    /* Alert */
    .alert {
        border-radius: 12px;
        border-right: 4px solid;
    }

    /* Checkbox */
    .checkbox {
        margin-top: 8px;
    }

    .checkbox label {
        font-weight: normal;
        font-size: 0.85rem;
        color: var(--text-muted);
    }

    input[type="checkbox"] {
        accent-color: var(--primary-medium);
        width: 16px;
        height: 16px;
        margin-left: 8px;
    }

    /* Modal Redesign */
    .modal-content {
        border-radius: 20px;
        border: none;
        box-shadow: 0 20px 60px rgba(0, 0, 0, 0.2);
    }

    .modal-header {
        background: linear-gradient(135deg, var(--primary-dark) 0%, var(--primary-medium) 100%);
        color: white;
        border-radius: 20px 20px 0 0;
        padding: 18px 25px;
        border: none;
    }

    .modal-header .close {
        color: white;
        opacity: 0.8;
    }

    .modal-header .close:hover {
        opacity: 1;
    }

    .modal-header .modal-title {
        font-weight: 600;
        font-size: 1.2rem;
    }

    .modal-body {
        padding: 28px;
        max-height: 70vh;
        overflow-y: auto;
    }

    .modal-footer {
        border-top: 1px solid var(--gray-border);
        padding: 18px 25px;
    }

    /* Nav Tabs */
    .nav-tabs-custom .nav-tabs {
        border-bottom: 2px solid var(--primary-soft);
    }

    .nav-tabs-custom .nav-tabs li a {
        color: var(--text-dark);
        font-weight: 500;
        border-radius: 8px 8px 0 0;
    }

    .nav-tabs-custom .nav-tabs li.active a {
        color: var(--primary-medium);
        border-bottom: 2px solid var(--primary-medium);
        font-weight: 600;
    }

    /* Select2 */
    .select2-container--default .select2-selection--single {
        border-radius: 12px !important;
        border-color: var(--gray-border) !important;
        height: 42px !important;
        padding: 5px !important;
    }

    .select2-container--default.select2-container--focus .select2-selection--single {
        border-color: var(--primary-medium) !important;
    }

    /* Hide class */
    .hide {
        display: none !important;
    }

    /* Responsive */
    @media (max-width: 768px) {
        .box.box-primary > .box-body {
            padding: 20px;
        }
        .modal-body {
            padding: 20px;
        }
    }
</style>

<section class="content-header" style="background: linear-gradient(135deg, #074f32 0%, #085d3a 100%); padding: 30px 35px; margin:0px 0 35px 0px; box-shadow: 0 4px 15px rgba(0,0,0,0.1);">
    <h1 style="color: white; margin: 0; font-weight: 700; display: flex; align-items: center; gap: 15px;">
        <i class="fa fa-cube" style="font-size: 2rem;"></i>
        إضافة منتج جديد
    </h1>
</section>

<section class="content">
    {!! Form::open(['url' => action([\App\Http\Controllers\NewProductController::class, 'store']), 'method' => 'post', 'id' => 'smart_product_add_form']) !!}
    
    <!-- Basic Information Card - Highlighted -->
    <div class="box box-primary basic-info-highlight">
        <div class="box-header">
            <h3 class="box-title">
                <i class="fa fa-info-circle"></i>
                البيانات الأساسية
            </h3>
        </div>
        <div class="box-body">
            <div class="row">
                <div class="col-sm-4">
                    <div class="form-group">
                        {!! Form::label('name', __('product.product_name') . ' <span class="text-danger">*</span>', ['class' => 'control-label'], false) !!}
                        {!! Form::text('name', null, ['class' => 'form-control', 'id' => 'product_name_input', 'required', 'placeholder' => __('product.product_name')]); !!}
                        <div id="name_duplicate_msg" style="display: none;">
                            <div class="alert alert-warning" style="margin-top: 8px; padding: 8px 12px; font-size: 12px;">
                                <i class="fa fa-exclamation-triangle"></i> هذا الاسم مستخدم مسبقاً، يمكنك الإكمال إذا أردت.
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-sm-4">
                    <div class="form-group">
                        {!! Form::label('barcode_type', __('product.barcode_type') . ' <span class="text-danger">*</span>', ['class' => 'control-label'], false) !!}
                        {!! Form::select('barcode_type', 
                            $barcode_types, 
                            !empty($duplicate_product->barcode_type) 
                                ? $duplicate_product->barcode_type 
                                : ($custom_settings['default_barcode_type'] ?? $barcode_default), 
                            ['class' => 'form-control select2', 'required', 'style' => 'width:100%']
                        ); !!}
                    </div>
                </div>

                <div class="col-sm-4 @if(empty($custom_settings['show_product_sku'])) hide @endif">
                    <div class="form-group">
                        {!! Form::label('sku', __('product.sku'), ['class' => 'control-label']) !!}
                        {!! Form::text('sku', null, ['class' => 'form-control', 'placeholder' => __('product.sku')]); !!}
                    </div>
                </div>

                <div class="col-sm-4">
                    <div class="form-group">
                        {!! Form::label('main_location_id', 'الفرع الحالي <span class="text-danger">*</span>', ['class' => 'control-label'], false) !!}
                        {!! Form::select('main_location_id', 
                            $business_locations, 
                            $custom_settings['default_location_id'] ?? null, 
                            ['class' => 'form-control select2', 'required', 'style' => 'width:100%']
                        ); !!}
                        <div class="checkbox" style="margin-top: 8px;">
                            {!! Form::checkbox('define_in_all_locations', 1, false, ['class' => 'input-icheck', 'id' => 'define_all_locations']); !!}
                            <label for="define_all_locations" style="font-weight: normal; font-size: 0.85rem; color: #6c757d;">
                                تعريف في كل الفروع
                            </label>
                        </div>
                    </div>
                </div>
            </div>

            <div class="section-divider"><span>الأسعار والمخزون</span></div>

            <div class="row">
                <div class="col-sm-3">
                    <div class="form-group">
                        {!! Form::label('single_dpp_inc_tax', 'سعر الشراء (شامل) <span class="text-danger">*</span>', ['class' => 'control-label'], false) !!}
                        {!! Form::text('single_dpp_inc_tax', null, ['class' => 'form-control input_number', 'id' => 'single_dpp_inc_tax', 'required', 'placeholder' => '0.00']); !!}
                        {!! Form::hidden('single_dpp', null, ['id' => 'single_dpp']); !!}
                    </div>
                </div>

                <div class="col-sm-3">
                    <div class="form-group">
                        {!! Form::label('profit_percent', 'الربح %', ['class' => 'control-label']) !!}
                        {!! Form::text('profit_percent', @num_format($default_profit_percent), ['class' => 'form-control input_number', 'id' => 'profit_percent']); !!}
                    </div>
                </div>

                <div class="col-sm-3">
                    <div class="form-group">
                        {!! Form::label('single_dsp', 'سعر البيع (صافي) <span class="text-danger">*</span>', ['class' => 'control-label'], false) !!}
                        {!! Form::text('single_dsp', null, ['class' => 'form-control input_number dsp', 'id' => 'single_dsp', 'required', 'placeholder' => '0.00']); !!}
                    </div>
                </div>

                <div class="col-sm-3">
                    <div class="form-group">
                        {!! Form::label('single_dsp_inc_tax', 'سعر البيع (شامل) <span class="text-danger">*</span>', ['class' => 'control-label'], false) !!}
                        {!! Form::text('single_dsp_inc_tax', null, ['class' => 'form-control input_number', 'id' => 'single_dsp_inc_tax', 'required', 'placeholder' => '0.00']); !!}
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-sm-4">
                    <div class="form-group">
                        {!! Form::label('opening_stock', 'الكمية المتوفرة <span class="text-danger">*</span>', ['class' => 'control-label'], false) !!}
                        {!! Form::text('opening_stock', 0, ['class' => 'form-control input_number']); !!}
                    </div>
                </div>

                <div class="col-sm-4 @if(empty($custom_settings['enable_category'])) hide @endif">
                    <div class="form-group">
                        {!! Form::label('category_id', __('product.category'), ['class' => 'control-label']) !!}
                        <div class="input-group">
                            {!! Form::select('category_id', $categories, null, ['placeholder' => __('messages.please_select'), 'class' => 'form-control select2', 'style' => 'width:100%', 'id' => 'category_id']); !!}
                            <span class="input-group-btn">
                                <button type="button" 
                                    @if(!auth()->user()->can('category.create')) disabled @endif 
                                    class="btn btn-default bg-white btn-flat btn-modal" 
                                    data-href="{{action([\App\Http\Controllers\TaxonomyController::class, 'create'], ['type' => 'product', 'quick_add' => true])}}" 
                                    title="@lang('category.add_category')" 
                                    data-container=".category_modal">
                                    <i class="fa fa-plus-circle text-primary fa-lg"></i>
                                </button>
                            </span>
                        </div>
                    </div>
                </div>

                <div class="col-sm-4 @if(empty($custom_settings['enable_sub_category'])) hide @endif">
                    <div class="form-group">
                        {!! Form::label('sub_category_id', __('product.sub_category'), ['class' => 'control-label']) !!}
                        {!! Form::select('sub_category_id', $sub_categories, null, ['placeholder' => __('messages.please_select'), 'class' => 'form-control select2', 'style' => 'width:100%', 'id' => 'sub_category_id']); !!}
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-sm-4 @if(empty($custom_settings['enable_brand'])) hide @endif">
                    <div class="form-group">
                        {!! Form::label('brand_id', __('product.brand'), ['class' => 'control-label']) !!}
                        <div class="input-group">
                            {!! Form::select('brand_id', $brands, null, ['placeholder' => __('messages.please_select'), 'class' => 'form-control select2', 'style' => 'width:100%', 'id' => 'brand_id']); !!}
                            <span class="input-group-btn">
                                <button type="button" @if(!auth()->user()->can('brand.create')) disabled @endif class="btn btn-default bg-white btn-flat btn-modal" data-href="{{action([\App\Http\Controllers\BrandController::class, 'create'], ['quick_add' => true])}}" title="@lang('brand.add_brand')" data-container=".view_modal"><i class="fa fa-plus-circle text-primary fa-lg"></i></button>
                            </span>
                        </div>
                    </div>
                </div>

                <div class="col-sm-4">
                    <div class="form-group">
                        {!! Form::label('unit_id', __('product.unit'), ['class' => 'control-label']) !!}
                        <div class="input-group">
                            {!! Form::select('unit_id', $units, $custom_settings['default_unit'] ?? null, ['class' => 'form-control select2', 'style' => 'width:100%', 'id' => 'unit_id']); !!}
                            <span class="input-group-btn">
                                <button type="button" @if(!auth()->user()->can('unit.create')) disabled @endif class="btn btn-default bg-white btn-flat btn-modal" data-href="{{action([\App\Http\Controllers\UnitController::class, 'create'], ['quick_add' => true])}}" title="@lang('unit.add_unit')" data-container=".view_modal"><i class="fa fa-plus-circle text-primary fa-lg"></i></button>
                            </span>
                        </div>
                    </div>
                </div>

                <div class="col-sm-4 @if(!session('business.enable_price_tax')) hide @endif">
                    <div class="form-group">
                        {!! Form::label('tax', __('product.applicable_tax'), ['class' => 'control-label']) !!}
                        {!! Form::select('tax', 
                            $taxes, 
                            !empty($duplicate_product->tax) 
                                ? $duplicate_product->tax 
                                : ($custom_settings['default_tax_id'] ?? null), 
                            [
                                'placeholder' => __('messages.please_select'), 
                                'class' => 'form-control select2', 
                                'id' => 'tax_id', 
                                'style' => 'width:100%'
                            ], 
                            $tax_attributes
                        ); !!}
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-sm-4 @if(empty($custom_settings['enable_price_tax'])) hide @endif">
                    <div class="form-group">
                        {!! Form::label('tax_type', __('product.selling_price_tax_type') . ' <span class="text-danger">*</span>', ['class' => 'control-label'], false) !!}
                        {!! Form::select('tax_type', ['inclusive' => __('product.inclusive'), 'exclusive' => __('product.exclusive')], $custom_settings['default_tax_type'] ?? 'exclusive', ['class' => 'form-control select2', 'required', 'style' => 'width:100%']); !!}
                    </div>
                </div>

                <div class="col-sm-4">
                    <div class="form-group">
                        {!! Form::label('type', __('product.product_type') . ' <span class="text-danger">*</span>', ['class' => 'control-label'], false) !!}
                        {!! Form::select('type', $product_types, $custom_settings['default_product_type'] ?? 'single', ['class' => 'form-control select2', 'id' => 'type', 'required', 'style' => 'width:100%']); !!}
                    </div>
                </div>

                <div class="col-sm-4 @if(empty($custom_settings['show_alert_quantity'])) hide @endif">
                    <div class="form-group">
                        {!! Form::label('alert_quantity', __('product.alert_quantity'), ['class' => 'control-label']) !!}
                        {!! Form::text('alert_quantity', 
                            $custom_settings['default_alert_quantity'] ?? 0, 
                            [
                                'class' => 'form-control input_number', 
                                'placeholder' => __('product.alert_quantity'),
                                'min' => '0',
                                'readonly' => (isset($custom_settings['default_enable_stock']) && $custom_settings['default_enable_stock'] == '0') ? true : false
                            ]) 
                        !!}
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-sm-4 @if(empty($custom_settings['enable_product_expiry'])) hide @endif">
                    <div class="form-group">
                        {!! Form::label('expiry_period', __('product.expires_in'), ['class' => 'control-label']) !!}
                        <div class="input-group">
                            {!! Form::text('expiry_period', 
                                !empty($duplicate_product->expiry_period) 
                                ? @num_format($duplicate_product->expiry_period) 
                                : ($custom_settings['stop_selling_before'] ?? null), 
                                ['class' => 'form-control input_number', 'placeholder' => __('product.expiry_period')]
                            ); !!}
                            {!! Form::select('expiry_period_type', 
                                ['months'=>__('product.months'), 'days'=>__('product.days'), '' =>__('product.not_applicable') ], 
                                !empty($duplicate_product->expiry_period_type) 
                                ? $duplicate_product->expiry_period_type 
                                : ($custom_settings['expiry_type'] == 'add_manufacturing' ? 'months' : 'days'), 
                                ['class' => 'form-control select2', 'id' => 'expiry_period_type']
                            ); !!}
                        </div>
                    </div>
                </div>

                <div class="col-sm-4 @if(empty($custom_settings['show_not_for_selling'])) hide @endif">
                    <div class="form-group">
                        <div class="checkbox" style="margin-top: 28px;">
                            {!! Form::checkbox('not_for_selling', 1, !(empty($duplicate_product)) ? $duplicate_product->not_for_selling : false, ['class' => 'input-icheck', 'id' => 'not_for_selling']); !!}
                            <label for="not_for_selling" style="font-weight: 600;">@lang('lang_v1.not_for_selling')</label>
                        </div>
                    </div>
                </div>

                @if(!empty($custom_settings['enable_racks']))
                <div class="col-sm-4">
                    <div class="form-group">
                        {!! Form::label('rack', __('lang_v1.rack'), ['class' => 'control-label']) !!}
                        {!! Form::text('product_racks', null, ['class' => 'form-control', 'placeholder' => __('lang_v1.rack')]); !!}
                    </div>
                </div>
                @endif
            </div>

            <div class="row">
                @if(!empty($custom_settings['enable_row']))
                <div class="col-sm-4">
                    <div class="form-group">
                        {!! Form::label('row', __('lang_v1.row'), ['class' => 'control-label']) !!}
                        {!! Form::text('product_row', null, ['class' => 'form-control', 'placeholder' => __('lang_v1.row')]); !!}
                    </div>
                </div>
                @endif

                @if(!empty($custom_settings['show_product_image']))
                <div class="col-sm-4">
                    <div class="form-group">
                        {!! Form::label('image', __('lang_v1.product_image'), ['class' => 'control-label']) !!}
                        {!! Form::file('image', ['id' => 'upload_image', 'accept' => 'image/*', 'class' => 'form-control']); !!}
                        <small class="text-muted">@lang('purchase.max_file_size', ['size' => (config('constants.document_size_limit') / 1000000)]) MB</small>
                    </div>
                </div>
                @endif

                <div class="col-sm-4 @if(empty($custom_settings['show_product_description'])) hide @endif">
                    <div class="form-group">
                        {!! Form::label('product_description', __('lang_v1.product_description'), ['class' => 'control-label']) !!}
                        {!! Form::textarea('product_description', !empty($duplicate_product->product_description) ? $duplicate_product->product_description : null, ['class' => 'form-control', 'rows' => 2]); !!}
                    </div>
                </div>
            </div>
        </div>
    </div>

   <!-- Custom Fields -->
<div class="box box-primary">
    <div class="box-header">
        <h3 class="box-title">
            <i class="fa fa-cog"></i>
            @lang('product.additional_fields')
        </h3>
    </div>
    <div class="box-body">
        <div class="row">
            @for($i = 3; $i <= 20; $i++)
                @php
                    $label = $custom_labels['product']['custom_field_' . $i] 
                             ?? session('business.custom_labels.product.custom_field_' . $i)
                             ?? $common_settings['product_custom_field' . $i] 
                             ?? null;

                    $field_type = $custom_labels['product_cf_details'][$i]['type'] 
                                  ?? session('business.custom_labels.product_cf_details.' . $i . '.type') 
                                  ?? 'text';
                @endphp

                @if(!empty($label))
                    <div class="col-sm-3">
                        <div class="form-group">
                            {!! Form::label('product_custom_field' . $i, $label . ':') !!}

                            @if($field_type == 'date')
                                {!! Form::text('product_custom_field' . $i, null, ['class' => 'form-control date-picker', 'placeholder' => $label, 'readonly']); !!}

                            @elseif($field_type == 'dropdown')
                                @php
                                    $raw_options = $custom_labels['product_cf_details'][$i]['dropdown_options'] 
                                                   ?? session('business.custom_labels.product_cf_details.' . $i . '.dropdown_options') 
                                                   ?? '';
                                    $options = [];
                                    foreach (explode("\n", str_replace("\r", "", $raw_options)) as $opt) {
                                        if(!empty(trim($opt))) $options[trim($opt)] = trim($opt);
                                    }
                                @endphp
                                {!! Form::select('product_custom_field' . $i, $options, null, ['class' => 'form-control select2', 'placeholder' => $label, 'style' => 'width:100%']); !!}

                            @else
                                {{-- حقل نص عادي مبسط - القائمة تظهر عبر الـ Body كعنصر عائم --}}
                                <div>
                                    {!! Form::text('product_custom_field' . $i, null, [
                                        'class'        => 'form-control cf-autocomplete',
                                        'placeholder'  => $label,
                                        'autocomplete' => 'off',
                                        'data-field'   => 'product_custom_field' . $i,
                                    ]); !!}
                                </div>
                            @endif

                        </div>
                    </div>
                @endif
            @endfor
        </div>
    </div>
</div>
    <!-- Size & Color Management Section -->
    <div class="collapsible-modern @if(empty($custom_settings['enable_product_size_color'])) hide @endif">
        <div class="collapsible-modern-header" onclick="toggleCollapse(this)">
            <h3><i class="fa fa-tags"></i> إدارة الألوان والمقاسات والمخزون</h3>
            <i class="fa fa-chevron-down"></i>
        </div>
        <div class="collapsible-modern-body">
            <div class="row">
                <div class="col-sm-6">
                    <div class="form-group">
                        {!! Form::label('selected_colors', '1. اختر الألوان المتاحة:', ['class' => 'control-label']) !!}
                        <div class="input-group">
                            <select name="selected_colors[]" id="selected_colors" class="form-control select2" multiple style="width:100%">
                                <option value="أسود">أسود</option>
                                <option value="أبيض">أبيض</option>
                                <option value="كحلي">كحلي</option>
                                <option value="رمادي">رمادي</option>
                                <option value="احمر">احمر</option>
                                <option value="اخضر">اخضر</option>
                                <option value="اصفر">اصفر</option>
                                <option value="بني">بني</option>
                                <option value="ازرق">ازرق</option>
                            </select>
                            <span class="input-group-btn">
                                <button type="button" class="btn btn-default" id="add_quick_color"><i class="fa fa-plus"></i></button>
                            </span>
                        </div>
                    </div>
                </div>
                <div class="col-sm-6">
                    <div class="form-group">
                        {!! Form::label('target_color', '2. إضافة المقاسات لـ:', ['class' => 'control-label']) !!}
                        <select id="target_color" class="form-control select2" style="width:100%">
                            <option value="all">كل الألوان المختارة</option>
                        </select>
                    </div>
                </div>
            </div>

            <div class="nav-tabs-custom" style="margin-top: 20px;">
                <ul class="nav nav-tabs">
                    <li class="active"><a href="#t_single" data-toggle="tab">مقاس واحد</a></li>
                    <li><a href="#t_range" data-toggle="tab">من - إلى</a></li>
                    <li><a href="#t_manual" data-toggle="tab">جدول حر</a></li>
                </ul>
                <div class="tab-content" style="padding: 20px 0;">
                    <div class="tab-pane active" id="t_single">
                        <div class="row">
                            <div class="col-sm-4"><input type="text" id="s_size" class="form-control" placeholder="المقاس"></div>
                            <div class="col-sm-4"><input type="number" id="s_qty" class="form-control" placeholder="الكمية"></div>
                            <div class="col-sm-4"><button type="button" class="btn btn-primary add_v_btn" data-type="single"><i class="fa fa-plus"></i> إضافة</button></div>
                        </div>
                    </div>
                    <div class="tab-pane" id="t_range">
                        <div class="row">
                            <div class="col-sm-3"><input type="number" id="r_from" class="form-control" placeholder="من"></div>
                            <div class="col-sm-3"><input type="number" id="r_to" class="form-control" placeholder="إلى"></div>
                            <div class="col-sm-3"><input type="number" id="r_step" class="form-control" value="2" placeholder="الفرق"></div>
                            <div class="col-sm-3"><input type="number" id="r_qty" class="form-control" placeholder="كمية كل مقاس"></div>
                            <div class="col-sm-12" style="margin-top: 15px;"><button type="button" class="btn btn-primary add_v_btn" data-type="range"><i class="fa fa-cogs"></i> توليد</button></div>
                        </div>
                    </div>
                    <div class="tab-pane" id="t_manual">
                        <table class="table table-condensed" id="m_table" style="margin-bottom: 15px;">
                            <thead>
                                <tr>
                                    <th>المقاس</th>
                                    <th>الكمية</th>
                                    <th><button type="button" class="btn btn-default btn-sm" id="add_m_row"><i class="fa fa-plus"></i> إضافة صف</button></th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                        <button type="button" class="btn btn-primary btn-sm add_v_btn" data-type="manual">تأكيد الجدول</button>
                    </div>
                </div>
            </div>

            <table class="table table-bordered" id="f_table" style="margin-top: 20px;">
                <thead>
                    <tr>
                        <th>اللون</th><th>المقاس</th><th>الكمية</th><th>الشراء</th><th>البيع</th><th>حذف</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>

    <!-- Action Buttons -->
    <div class="box box-primary">
        <div class="box-body text-center">
            <div style="display: flex; justify-content: center; gap: 20px; flex-wrap: wrap;">
                <div class="checkbox" style="margin-top: 0;">
                    {!! Form::checkbox('print_barcode', 1, false, ['id' => 'print_barcode_check']) !!}
                    <label for="print_barcode_check" style="font-weight: 600;">طباعة باركود عند الإضافة للجدول</label>
                </div>
                
                <button type="button" id="add_to_main_table" class="btn btn-primary btn-lg">
                    <i class="fa fa-plus"></i> حفظ وإضافة للمعاينة
                </button>
                
                <button type="button" id="clear_page_data" class="btn btn-default btn-lg">
                    <i class="fa fa-eraser"></i> مسح بيانات الصفحة
                </button>
            </div>
        </div>
    </div>

    <!-- Final Products Table -->
    <div class="box box-primary">
        <div class="box-header">
            <h3 class="box-title">
                <i class="fa fa-list"></i>
                قائمة المنتجات الجاهزة للحفظ
            </h3>
        </div>
        <div class="box-body">
            <div class="row mb-10">
                <div class="col-md-12">
                    <button type="button" id="print_selected_products" class="btn btn-success">
                        <i class="fa fa-print"></i> طباعة الملصقات للمحدد
                    </button>
                </div>
            </div>
            <div class="table-responsive">
                <table class="table table-bordered" id="final_products_table">
                    <thead>
                        <tr>
                            <th style="width: 30px; text-align: center;">
                                <input type="checkbox" id="select_all_variants">
                            </th>
                            <th>اسم المنتج</th>
                            <th>SKU</th>
                            <th>اللون / المقاس</th>
                            <th>سعر الشراء</th>
                            <th>سعر البيع</th>
                            <th>الكمية</th>
                            <th>العلامة التجارية</th>
                            <th>الصنف</th>
                            <th>الفرع</th>
                            <th>الصورة</th>
                            <th>العمليات</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                    <tfoot>
                        <tr style="background: #f8f9fa; font-weight: bold;">
                            <td colspan="4">إجمالي عدد الصفوف: <span id="total_rows_count">0</span></td>
                            <td colspan="8">إجمالي الكمية الكلية: <span id="total_qty_sum">0</span></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
        <div class="box-footer text-center">
            <button type="button" id="save_all_products" class="btn btn-primary btn-lg" style="display: none; background: #28a745;">
                <i class="fa fa-save"></i> إنهاء المعاينة
            </button>
        </div>
    </div>

    <!-- Edit Modal - Redesigned -->
    <div class="modal fade" id="edit_prod_modal" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                    <h4 class="modal-title">
                        <i class="fa fa-edit"></i> تعديل بيانات المنتج
                    </h4>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="edit_row_id">
                    
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>اسم المنتج: <span class="text-danger">*</span></label>
                                <input type="text" id="modal_edit_name" class="form-control">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label>SKU:</label>
                                <input type="text" id="modal_edit_sku" class="form-control">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label>اللون:</label>
                                <input type="text" id="modal_edit_color" class="form-control" placeholder="مثال: أحمر">
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="form-group">
                                <label>المقاس:</label>
                                <input type="text" id="modal_edit_size" class="form-control" placeholder="مثال: XL">
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-3">
                            <div class="form-group">
                                <label>سعر الشراء: <span class="text-danger">*</span></label>
                                <input type="number" id="modal_edit_purchase" class="form-control" step="any">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label>سعر البيع: <span class="text-danger">*</span></label>
                                <input type="number" id="modal_edit_selling" class="form-control" step="any">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label>الكمية: <span class="text-danger">*</span></label>
                                <input type="number" id="modal_edit_qty" class="form-control">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label>تنبيه الكمية:</label>
                                <input type="number" id="modal_edit_alert" class="form-control" value="0">
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>العلامة التجارية:</label>
                                {!! Form::select('modal_brand_id', $brands, null, [
                                    'placeholder' => 'اختر',
                                    'class' => 'form-control select2',
                                    'id' => 'modal_edit_brand',
                                    'style' => 'width:100%'
                                ]) !!}
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>الوحدة:</label>
                                {!! Form::select('modal_unit_id', $units, null, [
                                    'placeholder' => 'اختر',
                                    'class' => 'form-control select2',
                                    'id' => 'modal_edit_unit',
                                    'style' => 'width:100%'
                                ]) !!}
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>الفرع:</label>
                                {!! Form::select('modal_location_id', $business_locations, null, [
                                    'placeholder' => 'اختر الفرع',
                                    'class' => 'form-control select2',
                                    'id' => 'modal_edit_location',
                                    'style' => 'width:100%'
                                ]) !!}
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>الصنف الرئيسي:</label>
                                {!! Form::select('modal_category_id', $categories, null, [
                                    'placeholder' => 'اختر',
                                    'class' => 'form-control select2',
                                    'id' => 'modal_edit_category',
                                    'style' => 'width:100%'
                                ]) !!}
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>الصنف الفرعي:</label>
                                <select id="modal_edit_sub_category" class="form-control select2" name="modal_sub_category_id" style="width:100%">
                                    <option value="">-- اختر صنف فرعي --</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>نوع الباركود:</label>
                                {!! Form::select('modal_barcode_type', $barcode_types, null, [
                                    'class' => 'form-control select2',
                                    'id' => 'modal_edit_barcode_type',
                                    'style' => 'width:100%'
                                ]) !!}
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>الضريبة:</label>
                                {!! Form::select('modal_tax_id', $taxes, null,
                                    ['placeholder' => 'اختر', 'class' => 'form-control select2',
                                     'id' => 'modal_edit_tax', 'style' => 'width:100%'],
                                    $tax_attributes
                                ) !!}
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>نوع الضريبة:</label>
                                {!! Form::select('modal_tax_type',
                                    ['inclusive' => __('product.inclusive'), 'exclusive' => __('product.exclusive')],
                                    null,
                                    ['class' => 'form-control select2', 'id' => 'modal_edit_tax_type', 'style' => 'width:100%']
                                ) !!}
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>تحديث صورة المنتج:</label>
                        <input type="file" id="modal_edit_image" class="form-control" accept="image/*">
                    </div>
                    
                    <div class="form-group">
                        <label>وصف المنتج:</label>
                        <textarea id="modal_edit_description" class="form-control" rows="2"></textarea>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-primary" id="save_modal_changes">
                        <i class="fa fa-save"></i> حفظ التعديلات
                    </button>
                    <button type="button" class="btn btn-default" data-dismiss="modal">
                        <i class="fa fa-times"></i> إغلاق
                    </button>
                </div>
            </div>
        </div>
    </div>

    {!! Form::close() !!}
</section>

<script type="text/javascript">
    // Toggle Collapse Function
    function toggleCollapse(element) {
        var body = $(element).next('.collapsible-modern-body');
        var icon = $(element).find('.fa-chevron-down');
        body.slideToggle(300, function() {
            if (body.is(':visible')) {
                icon.css('transform', 'rotate(180deg)');
            } else {
                icon.css('transform', 'rotate(0deg)');
            }
        });
    }
    

</script>


@endsection

<div class="modal fade view_modal" tabindex="-1" role="dialog" aria-labelledby="gridSystemModalLabel"></div>
    <div class="modal fade category_modal" tabindex="-1" role="dialog" aria-labelledby="gridSystemModalLabel"></div>
    <div class="modal fade brand_modal" tabindex="-1" role="dialog" aria-labelledby="gridSystemModalLabel"></div>

@section('javascript')

<script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.5/dist/JsBarcode.all.min.js"></script>
<script type="text/javascript">

   $(document).ready(function(){
       
        $('.select2').select2();

        // 1. حساب سعر البيع تلقائياً عند تغيير سعر الشراء أو نسبة الربح
        $(document).on('keyup change', '#profit_percent, .dpp', function(){
            var dpp = __read_number($('.dpp')); 
            var profit_percent = __read_number($('#profit_percent'));

            if(dpp > 0){
                // حساب قيمة الربح (مبلغ) من النسبة المئوية
                var profit_amount = __calculate_amount('percentage', profit_percent, dpp);
                var dsp = dpp + profit_amount;

                __write_number($('.dsp'), dsp);
            }
        });

        // 2. حساب نسبة الربح تلقائياً عند تغيير سعر البيع
        $(document).on('keyup change', '.dsp', function(){
            var dpp = __read_number($('.dpp'));
            var dsp = __read_number($('.dsp'));

            if(dsp > 0 && dpp > 0){
                // حساب النسبة المئوية للفرق بين السعرين
                var profit_percent = __get_rate(dpp, dsp);
                
                // تحديث حقل النسبة
                __write_number($('#profit_percent'), profit_percent);
            }
        });

        // كود الفتح والإغلاق
        $('#toggle_extra_details').click(function() {
            $('#extra_details_body').slideToggle(300, function() {
                if ($(this).is(':visible')) {
                    $('#toggle_icon').removeClass('fa-plus-circle').addClass('fa-minus-circle');
                } else {
                    $('#toggle_icon').removeClass('fa-minus-circle').addClass('fa-plus-circle');
                }
            });
        });


    });

// فحص التكرار
$('#product_name_input').on('blur', function() {
    var name = $(this).val();
    var msg_element = $('#name_duplicate_msg');
    var input_element = $(this);

    if (name.length > 1) { 
        $.ajax({
            method: 'GET',
            url: '/products/check-name', 
            data: { name: name },
            success: function(result) {
                if (result.exists) {
                    msg_element.slideDown(200);
                    input_element.css('border', '2px solid #f39c12');
                } else {
                    msg_element.slideUp(200);
                    input_element.css('border', '1px solid #d2d6de');
                }
            },
            error: function(err) {
                console.log("Error details:", err);
            }
        });
    }
});

$(document).on('click', '[data-widget="collapse"]', function() {
    var box = $(this).closest('.box');
    var body = box.find('.box-body');
    var icon = $(this).find('i');

    body.slideToggle(300, function() {
        if (body.is(':visible')) {
            box.removeClass('collapsed-box');
            icon.removeClass('fa-plus').addClass('fa-minus');
        } else {
            box.addClass('collapsed-box');
            icon.removeClass('fa-minus').addClass('fa-plus');
        }
    });
});

$(document).ready(function() {
    
    const defaultColors = [];


    function setDefaultColors() {
        $('#selected_colors').val(defaultColors).trigger('change');
    }

    // 1. توليد 4 صفوف تلقائياً في الجدول الحر
    function addManualRow() {
        $('#m_table tbody').append('<tr><td><input type="text" class="form-control m_size"></td><td><input type="number" class="form-control m_qty"></td><td><button class="btn btn-danger btn-xs rem_m"><i class="fa fa-times"></i></button></td></tr>');
    }

    // التشغيل عند تحميل الصفحة لأول مرة
    for(let i=0; i<4; i++) { addManualRow(); }
    setDefaultColors();

    $('#add_m_row').click(addManualRow);
    $(document).on('click', '.rem_m', function() { $(this).closest('tr').remove(); });

    // تحديث قائمة "إضافة المقاسات لـ" بناءً على الألوان المختارة
    $('#selected_colors').on('change', function() {
        let selected = $(this).val();
        let targetSelect = $('#target_color');
        targetSelect.empty().append('<option value="all">كل الألوان المختارة</option>');
        if (selected) {
            selected.forEach(color => {
                targetSelect.append(`<option value="${color}">${color}</option>`);
            });
        }
    });

    // 2. تحديث الأسعار تلقائياً في جدول المعاينة
    $(document).on('change', '#single_dpp_inc_tax, #single_dsp_inc_tax', function() {
        let p = $('#single_dpp_inc_tax').val() || 0;
        let s = $('#single_dsp_inc_tax').val() || 0;
        $('#f_table tbody tr').each(function() {
            $(this).find('.v_purc').val(p);
            $(this).find('.v_sell').val(s);
        });
    });

    // 3. منطق الإضافة للجدول النهائي (المعاينة)
    $(document).on('click', '.add_v_btn', function() {
        let type = $(this).data('type');
        let sel_colors = $('#selected_colors').val();
        let target = $('#target_color').val();
        
        if (!sel_colors || sel_colors.length === 0) { 
            toastr.error('اختر لوناً أولاً'); return; 
        }
        
        let colors = (target === 'all') ? sel_colors : [target];
        let vars = [];

        if(type=='single') {
            vars.push({s: $('#s_size').val(), q: $('#s_qty').val()});
        } else if(type=='range') {
            let f=parseInt($('#r_from').val()), t=parseInt($('#r_to').val()), st=parseInt($('#r_step').val()), q=$('#r_qty').val();
            for(let i=f; i<=t; i+=st) vars.push({s: i, q: q});
        } else if(type=='manual') {
            $('#m_table tbody tr').each(function() {
                let s=$(this).find('.m_size').val(), q=$(this).find('.m_qty').val();
                if(s) vars.push({s: s, q: q});
            });
        }

        let p = $('#single_dpp_inc_tax').val() || 0;
        let s = $('#single_dsp_inc_tax').val() || 0;

        colors.forEach(c => {
            vars.forEach(v => {
                let exists = false;
                $('#f_table tbody tr').each(function() {
                    if($(this).find('.v_c_val').val() == c && $(this).find('.v_s_val').val() == v.s) exists = true;
                });

                if(!exists) {
                    $('#f_table tbody').append(`<tr>
                        <td><input type="hidden" name="v_color[]" class="v_c_val" value="${c}">${c}</td>
                        <td><input type="hidden" name="v_size[]" class="v_s_val" value="${v.s}">${v.s}</td>
                        <td><input type="number" name="v_qty[]" class="form-control" value="${v.q}"></td>
                        <td><input type="number" name="v_purchase[]" class="form-control v_purc" value="${p}" step="any"></td>
                        <td><input type="number" name="v_selling[]" class="form-control v_sell" value="${s}" step="any"></td>
                        <td><button type="button" class="btn btn-danger btn-xs rem_f"><i class="fa fa-trash"></i></button></td>
                    </tr>`);
                }
            });
        });

        // تصفير حقول المنتج فقط لترك المجال لإضافة لون/منتج جديد
        $('#name').val('').focus(); 
        // $('#sku').val(''); 
        $('input[name="opening_stock"]').val(0).prop('disabled', true);

        toastr.success('تمت الإضافة للمعاينة');
    });

    $(document).on('click', '.rem_f', function() { $(this).closest('tr').remove(); });

    // 4. زر مسح بيانات الصفحة 
    $(document).on('click', '#clear_page_data', function() {
    
        $('input[type="text"], input[type="number"], textarea').val('');
        $('#category_id, #sub_category_id, #brand_id').val(null).trigger('change');
    
        setDefaultColors();
        
        $('#m_table tbody').empty();
        for(let i=0; i<4; i++) { addManualRow(); } 
        $('#f_table tbody').empty(); 
        
        $('input[name="opening_stock"]').prop('disabled', false).val(0);
        
        // الصورة
        $('#upload_image').val('');
        $('.image-preview').attr('src', '');

        toastr.info('تمت إعادة تعيين الصفحة وتثبيت الألوان الأساسية');
    });
});
$(document).ready(function() {

    $('#selected_colors').select2({
        tags: true, 
        tokenSeparators: [','], 
        placeholder: "اختر لوناً أو اكتب لوناً جديداً واضغط Enter"
    });

    $('#add_quick_color').click(function() {
        $('#selected_colors').select2('open');
    });

    $('#selected_colors').on('change', function() {
        var selected = $(this).val();
        var target = $('#target_color');
        target.empty().append('<option value="all">كل الألوان المختارة</option>');
        
        if (selected) {
            selected.forEach(function(color) {
                target.append(`<option value="${color}">${color}</option>`);
            });
        }
    });
});


$(document).ready(function() {
    
    function resetProductFields() {
        $('input[name="name"]').val('');
        $('#f_table tbody').empty(); 
        $('input[name="name"]').focus();
    }

    $('#add_to_main_table').click(function() {
        var name = $('input[name="name"]').val();
        var loc_id = $('#main_location_id').val();

        if(!name) { toastr.error("يرجى إدخال اسم المنتج"); return; }
        if(!loc_id) { toastr.error("يرجى اختيار الفرع أولاً"); return; }

        var btn = $(this);
        btn.prop('disabled', true).html('<i class="fa fa-refresh fa-spin"></i> جاري الحفظ...');

        var formData = new FormData($('#smart_product_add_form')[0]);

        $.ajax({
            method: 'POST',
            url: $('#smart_product_add_form').attr('action'),
            data: formData,
            processData: false,
            contentType: false,
            success: function(res) {
    btn.prop('disabled', false).html('حفظ وإضافة للمعاينة ');
    if(res.success == 1) {
        var products = res.products; 
        products.forEach(function(prod) {
            var row_html = buildLiveRow(prod);
            $('#final_products_table tbody').append(row_html);
        });


        if ($('#print_barcode_check').is(':checked')) {
            printAfterSave(products);
        }



         function calculateTableTotals() {
        let rows = $('#final_products_table tbody tr'); 
        let rowCount = rows.length;
        let totalQty = 0;


        rows.each(function() {
            let qty = parseFloat($(this).find('.v_qty_input').val()) || 0;
            totalQty += qty;
        });

  
        $('#total_rows_count').text(rowCount);
        $('#total_qty_sum').text(totalQty);

        // إظهار أو إخفاء زر "إنهاء المعاينة" حسب وجود بيانات
        if(rowCount > 0) {
            $('#save_all_products').show();
        } else {
            $('#save_all_products').hide();
        }
    }
        // تشغيل الحساب عند تغيير الكمية في أي صف
    $(document).on('input change', '.v_qty_input', function() {
        calculateTableTotals();
    });

    // تشغيل الحساب عند حذف صف من الجدول
    $(document).on('click', '.remove_live_row', function() {
        $(this).closest('tr').remove();
        calculateTableTotals();
    });

    calculateTableTotals();


        resetProductFields();
        $('#save_all_products').show(); 
        toastr.success("تم الحفظ بنجاح");
    } else {
        toastr.error(res.msg);
    }
},
            error: function() {
                btn.prop('disabled', false).html('حفظ و إضافة للمعاينة');
                toastr.error("حدث خطأ في الاتصال بالسيرفر");
            }
        });
    });

    // دالة بناء السطر مع تخزين كافة البيانات في data attributes
function buildLiveRow(prod) {
    return `<tr id="prod_${prod.id}" 
                class="product_row"
                data-id="${prod.id}"
                data-name="${prod.name}"
                data-sku="${prod.sku}"
                data-variant="${prod.variant_info || ''}"
                data-purchase="${prod.purchase_price}"
                data-selling="${prod.selling_price}"
                data-qty="${prod.qty}"
                data-brand-id="${prod.brand_id || ''}" 
                data-cat-id="${prod.category_id || ''}" 
                data-loc-id="${prod.location_id || ''}"
                data-barcode-type="${prod.barcode_type || ''}"
                data-alert-qty="${prod.alert_quantity || 0}"
                data-image="${prod.image_url || ''}"
                data-desc="${prod.product_description || ''}"
                data-model="${prod.product_custom_field3 || ''}">
            <td><input type="checkbox" class="row_check" value="${prod.id}"></td>
            <td class="p_name">${prod.name}</td>
            <td class="p_sku text-bold text-success">${prod.sku}</td>
            <td class="p_variant"><small><b>${prod.variant_info || ''}</b></small></td>
            <td class="p_purchase">${prod.purchase_price}</td>
            <td class="p_selling">${prod.selling_price}</td>
            <td class="p_qty">
    ${prod.qty}
    <input type="hidden" class="v_qty_input" value="${prod.qty}">
</td>
            <td class="p_brand_name"><span class="badge label-info">${prod.brand_name || '--'}</span></td>
            <td class="p_cat_name"><span class="badge label-info">${prod.category_name || '--'}</span></td>
            <td class="p_loc_name"><span class="badge label-info">${prod.location_name || '--'}</span></td>
            <td class="text-center p_image">
                ${prod.image_url ? '<i class="fa fa-image text-success" title="توجد صورة"></i>' : '--'}
            </td>
<td class="text-center" style="white-space: nowrap;">
    <div class="btn-group" role="group" aria-label="Product Actions" style="display: flex; gap: 3px; justify-content: center;">
        
        <button type="button" class="btn btn-outline-primary btn-xs print_single_row" title="طباعة">
            <i class="fa fa-print"></i> طباعة
        </button>
        

        <button type="button" class="btn btn-outline-info btn-xs edit_live_row" data-id="${prod.id}">
            <i class="fa fa-edit"></i> تعديل
        </button>

        <button type="button" class="btn btn-outline-danger btn-xs remove_live_row" data-id="${prod.id}">
            <i class="fa fa-trash"></i> حذف
        </button>
        
    </div>
</td>
        </tr>`;
}

$(document).ready(function () {

    //جلب الاصناف الفرعية 
    $(document).on('change', '#modal_edit_category', function () {
        var cat_id     = $(this).val();
        var sub_select = $('#modal_edit_sub_category');
        var next_val   = sub_select.data('next-selected');

        sub_select.empty().append('<option value="">-- اختر صنف فرعي --</option>');

        if (!cat_id) return;

        $.ajax({
            url:  '/new-products/sub-categories/' + cat_id,
            type: 'GET',
            success: function (data) {
                if (data && data.length > 0) {
                    $.each(data, function (i, v) {
                        sub_select.append($('<option>', { value: v.id, text: v.name }));
                    });
                }
                if (next_val) {
                    sub_select.val(next_val);
                    sub_select.data('next-selected', null);
                }
            },
            error: function () {
                console.warn('فشل جلب الأصناف الفرعية');
            }
        });
    });

   // مودال التعديل
    $(document).on('click', '.edit_live_row', function () {
        var id  = $(this).data('id');
        var btn = $(this);

        btn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i>');

        $.get('/products/get-product-details/' + id)
            .done(function (res) {
               btn.prop('disabled', false).html('<i class="fa fa-edit"></i> تعديل');

                if (res.success != 1) {
                    toastr.error(res.msg || 'فشل جلب البيانات');
                    return;
                }

                var p = res.data;

                // تعبئة الحقول
                $('#edit_row_id').val(p.id);
                $('#modal_edit_name').val(p.name);
                $('#modal_edit_sku').val(p.sku);
                $('#modal_edit_purchase').val(p.purchase_price);
                $('#modal_edit_selling').val(p.selling_price);
                $('#modal_edit_qty').val(p.qty);
                $('#modal_edit_alert').val(p.alert_quantity || 0);
                $('#modal_edit_description').val(p.product_description || '');

                var variantParts = (p.variant_info || '').split(' / ');
                $('#modal_edit_color').val(variantParts[0] || '');
                $('#modal_edit_size').val(variantParts[1] || '');

                $('#modal_edit_brand').val(p.brand_id || '').trigger('change');
                $('#modal_edit_unit').val(p.unit_id || '').trigger('change');
                $('#modal_edit_location').val(p.location_id || '').trigger('change');
                $('#modal_edit_barcode_type').val(p.barcode_type || '').trigger('change');
                $('#modal_edit_tax').val(p.tax_id || '').trigger('change');
                $('#modal_edit_tax_type').val(p.tax_type || 'exclusive').trigger('change');

                $('#modal_edit_sub_category')
                    .empty()
                    .append('<option value="">جاري التحميل...</option>')
                    .data('next-selected', p.sub_category_id || '');

                $('#modal_edit_category').val(p.category_id || '').trigger('change');
                $('#modal_edit_image').val('');

                $('#edit_prod_modal').modal('show');
            })
            .fail(function () {
                btn.prop('disabled', false).html('<i class="fa fa-edit"></i>');
                toastr.error('تعذر الاتصال بالسيرفر');
            });
    });

   
    // حفظ التعديلات الكاملة وتحديث المعاينة
    $(document).on('click', '#save_modal_changes', function () {
        var id = $('#edit_row_id').val();

        if (!$('#modal_edit_name').val().trim()) { toastr.error('اسم المنتج مطلوب'); return; }
        if (!$('#modal_edit_selling').val())      { toastr.error('سعر البيع مطلوب'); return; }
        if ($('#modal_edit_qty').val() === '')    { toastr.error('الكمية مطلوبة');    return; }

        var formData = new FormData();
        formData.append('_token',               $('input[name="_token"]').val());
        formData.append('name',                 $('#modal_edit_name').val());
        formData.append('sku',                  $('#modal_edit_sku').val());
        formData.append('purchase_price',       $('#modal_edit_purchase').val() || 0);
        formData.append('selling_price',        $('#modal_edit_selling').val());
        formData.append('qty',                  $('#modal_edit_qty').val());
        formData.append('alert_quantity',       $('#modal_edit_alert').val() || 0);
        formData.append('brand_id',             $('#modal_edit_brand').val() || '');
        formData.append('unit_id',              $('#modal_edit_unit').val() || '');
        formData.append('category_id',          $('#modal_edit_category').val() || '');
        formData.append('sub_category_id',      $('#modal_edit_sub_category').val() || '');
        formData.append('location_id',          $('#modal_edit_location').val() || '');
        formData.append('barcode_type',         $('#modal_edit_barcode_type').val() || '');
        formData.append('tax',                  $('#modal_edit_tax').val() || '');
        formData.append('tax_type',             $('#modal_edit_tax_type').val() || 'exclusive');
        formData.append('product_description',  $('#modal_edit_description').val());
        formData.append('product_custom_field1', $('#modal_edit_color').val() || '');
        formData.append('product_custom_field2', $('#modal_edit_size').val() || '');

        if ($('#modal_edit_image')[0].files[0]) {
            formData.append('image', $('#modal_edit_image')[0].files[0]);
        }

        var saveBtn = $(this);
        saveBtn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> جاري الحفظ...');

        $.ajax({
            method:      'POST',
            url:         '/products/quick-update/' + id,
            data:        formData,
            processData: false,
            contentType: false,
            success: function (res) {
                saveBtn.prop('disabled', false).html('<i class="fa fa-save"></i> حفظ التعديلات');

                if (res.success == 1) {
                    var row = $('#prod_' + id);

                    // جلب النصوص والأسماء الجديدة للتحديث 
                    var newName     = $('#modal_edit_name').val();
                    var newSku      = $('#modal_edit_sku').val();
                    var newPurchase = $('#modal_edit_purchase').val();
                    var newSelling  = $('#modal_edit_selling').val();
                    var newQty      = $('#modal_edit_qty').val();
                    var newLocId    = $('#modal_edit_location').val();
                    var newLocName  = $('#modal_edit_location option:selected').text();
                    var variantText = [$('#modal_edit_color').val(), $('#modal_edit_size').val()].filter(Boolean).join(' / ');

                    //  تحديث الخلايا في الجدول )
                    row.find('.p_name').text(newName);
                    row.find('.p_sku').text(newSku);
                    row.find('.p_purchase').text(newPurchase);
                    row.find('.p_selling').text(newSelling);
                    row.find('.p_qty').html(newQty + '<input type="hidden" class="v_qty_input" value="' + newQty + '">');
                    row.find('.p_variant').html('<small><b>' + variantText + '</b></small>');
                    row.find('.p_loc_name').html('<span class="badge label-info">' + newLocName + '</span>');

               
                    row.attr('data-name',     newName);
                    row.attr('data-sku',      newSku);
                    row.attr('data-qty',      newQty);
                    row.attr('data-selling',  newSelling);
                    row.attr('data-purchase', newPurchase);
                    row.attr('data-loc-id',   newLocId);
                    row.attr('data-variant',  variantText);

                    
                    calculateTableTotals();
                    $('#edit_prod_modal').modal('hide');
                    $('.modal-backdrop').remove();

                    toastr.success('تم التحديث بنجاح ✅');
                } else {
                    toastr.error(res.msg || 'فشل التحديث');
                }
            },
            error: function () {
                saveBtn.prop('disabled', false).html('<i class="fa fa-save"></i> حفظ التعديلات');
                toastr.error('تعذر الاتصال بالسيرفر');
            }
        });
    });

    // ===============================================
    // د - حذف المنتج من الجدول والسيرفر
    // ===============================================
    $(document).on('click', '.remove_live_row', function () {
        var btn = $(this);
        var id  = btn.data('id');
        var row = btn.closest('tr');

        if (confirm('هل تريد حذف هذا المنتج نهائياً؟')) {
            $.ajax({
                method: 'DELETE',
                url:     '/products/destroy/' + id,
                data:   { _token: $('input[name="_token"]').val() },
                success: function (res) {
                    if (res.success == 1) {
                        row.fadeOut(400, function () {
                            $(this).remove();
                            calculateTableTotals();
                        });
                        toastr.warning('تم حذف المنتج');
                    } else {
                        toastr.error(res.msg || 'فشل الحذف');
                    }
                },
                error: function () {
                    toastr.error('تعذر الاتصال بالسيرفر');
                }
            });
        }
    });

    // ===============================================
    // هـ - دوال الحسابات العامة
    // ===============================================
    function calculateTableTotals() {
        var rows = $('#final_products_table tbody tr');
        var totalQty = 0;
        rows.each(function() {
            var qty = parseFloat($(this).find('.v_qty_input').val()) || 0;
            totalQty += qty;
        });
        $('#total_rows_count').text(rows.length);
        $('#total_qty_sum').text(totalQty.toFixed(2));

        if(rows.length > 0) {
            $('#save_all_products').show();
        } else {
            $('#save_all_products').hide();
        }
    }

    // زر إنهاء المعاينة (تصفير الجدول)
    $('#save_all_products').on('click', function () {
        if(confirm('هل أنت متأكد من إنهاء المعاينة وتصفير الجدول؟')) {
            $('#final_products_table tbody').empty();
            $(this).hide();
            calculateTableTotals();
            toastr.info('تم إنهاء المعاينة');
        }
    });

});
});


$(document).ready(function() {
    // 1. عند تغيير سعر الشراء (الشامل)
    $(document).on('change', 'input#single_dpp_inc_tax', function() {
        var purchase_inc_tax = __read_number($(this));
        var tax_rate = $('select#tax_id').find(':selected').data('rate') || 0;

        // حساب السعر قبل الضريبة وتخزينه في النظام)
        var purchase_exc_tax = __get_principle(purchase_inc_tax, tax_rate);
        __write_number($('input#single_dpp'), purchase_exc_tax);

        // تحديث سعر البيع بناءً على نسبة الربح
        update_selling_price();
    });

    // 2. عند تغيير سعر البيع (الصافي)
    $(document).on('change', 'input#single_dsp', function() {
        var selling_price = __read_number($(this));
        var tax_rate = $('select#tax_id').find(':selected').data('rate') || 0;

        // حساب السعر الشامل
        var selling_price_inc_tax = __add_percent(selling_price, tax_rate);
        __write_number($('input#single_dsp_inc_tax'), selling_price_inc_tax);

        // تحديث نسبة الربح تلقائياً
        update_profit_percent();
    });

    // 3. عند تغيير سعر البيع (الشامل)
    $(document).on('change', 'input#single_dsp_inc_tax', function() {
        var selling_price_inc_tax = __read_number($(this));
        var tax_rate = $('select#tax_id').find(':selected').data('rate') || 0;

        // حساب السعر الصافي
        var selling_price = __get_principle(selling_price_inc_tax, tax_rate);
        __write_number($('input#single_dsp'), selling_price);

        // تحديث نسبة الربح تلقائياً
        update_profit_percent();
    });

    // 4. عند تغيير نسبة الربح
    $(document).on('change', 'input#profit_percent', function() {
        update_selling_price();
    });

    // 5. عند تغيير الضريبة المختارة
    $(document).on('change', 'select#tax_id', function() {
        var selling_price = __read_number($('input#single_dsp'));
        var tax_rate = $(this).find(':selected').data('rate') || 0;
        
        var selling_price_inc_tax = __add_percent(selling_price, tax_rate);
        __write_number($('input#single_dsp_inc_tax'), selling_price_inc_tax);
    });

    // دالة تحديث سعر البيع بناء على الربح والشراء
    function update_selling_price() {
        var purchase_exc_tax = __read_number($('input#single_dpp'));
        var profit_percent = __read_number($('#profit_percent'));
        var tax_rate = $('select#tax_id').find(':selected').data('rate') || 0;

        var selling_price = __add_percent(purchase_exc_tax, profit_percent);
        __write_number($('input#single_dsp'), selling_price);

        var selling_price_inc_tax = __add_percent(selling_price, tax_rate);
        __write_number($('input#single_dsp_inc_tax'), selling_price_inc_tax);
    }

    // دالة تحديث نسبة الربح بناء على الأسعار الحالية
    function update_profit_percent() {
        var purchase_exc_tax = __read_number($('input#single_dpp'));
        var selling_price = __read_number($('input#single_dsp'));

        if (purchase_exc_tax != 0) {
            var profit_percent = __get_rate(purchase_exc_tax, selling_price);
            __write_number($('input#profit_percent'), profit_percent);
        }
    }
});

// 6. عند تغيير نوع الضريبة (شامل أو باستثناء)
$(document).on('change', 'select#tax_type', function() {
    var tax_type = $(this).val();
    var tax_rate = $('select#tax_id').find(':selected').data('rate') || 0;
    
    var selling_price = __read_number($('input#single_dsp')); // السعر الصافي
    var selling_price_inc_tax = __read_number($('input#single_dsp_inc_tax')); // السعر الشامل

    if (tax_type == 'inclusive') {
        // إذا اختار "شامل": نعتبر السعر الشامل هو "القائد" ونعيد حساب الصافي
        var new_selling_price = __get_principle(selling_price_inc_tax, tax_rate);
        __write_number($('input#single_dsp'), new_selling_price);
    } else {
        // إذا اختار "باستثناء": نعتبر السعر الصافي هو "القائد" ونعيد حساب الشامل
        var new_selling_price_inc_tax = __add_percent(selling_price, tax_rate);
        __write_number($('input#single_dsp_inc_tax'), new_selling_price_inc_tax);
    }
    
    // تحديث الربح بناءً على القيم الجديدة
    update_profit_percent();
});


// =====================================================
// QZ TRAY - إعداد الاتصال والطباعة
// =====================================================

// اعادة التوقيع والشهادة 
qz.security.setSignatureAlgorithm("SHA256");
const _qzCertificate = `{!! \App\Services\PrintService::getQzCertificate() !!}`;
qz.security.setCertificatePromise(function(resolve, reject) {
    resolve(_qzCertificate);
});
qz.security.setSignaturePromise(function(toSign) {
    return function(resolve, reject) {
        $.ajax({
            url: "{{ route('qz.sign') }}?request=" + toSign,
            type: 'GET',
            success: resolve,
            error: reject
        });
    };
});

//جلب التصميم من الـ API
let _barcodeDesignData = null;
async function getBarcodeDesign() {
    if (_barcodeDesignData) return _barcodeDesignData;
    try {
        const res = await $.get("{{ url('/print-barcode/design') }}");
        if (res.success) {
            _barcodeDesignData = res.design;
            return _barcodeDesignData;
        }
    } catch(e) {
        console.error('فشل جلب تصميم الباركود:', e);
    }
    return null;
}

//  تهيئة QZ عند تحميل الصفحة
let _qzConnected = false;
async function initQZForNewProduct() {
    try {
        qz.api.setPromiseType(function(resolver) { return new Promise(resolver); });
        await qz.websocket.connect();
        _qzConnected = true;
        console.log('✅ QZ Tray متصل');
    } catch(e) {
        console.warn('⚠️ QZ Tray غير متصل:', e);
    }
}
initQZForNewProduct();

// اسم الطابعة الثابت 
const BARCODE_PRINTER_NAME = @json($default_barcode_printer ?? '');



var currentProduct = {}; 

function mmToPx(mm) {
    return Math.round(mm * 3.7795275591);
}

function parsePosition(value) {
    if (!value) return '0px';
    if (value.toString().endsWith('mm')) return value;
    if (value.toString().endsWith('px')) return value;
    if (!isNaN(parseFloat(value))) return value + 'px';
    return value;
}

function pxToPt(px) {
    const n = parseInt(px, 10) || 12;
    return Math.max(9, Math.round(n * 72 / 96)) + 'pt';
}

function substituteElementText(key, el) {
    let txt = (el.text || '').toString().trim();
    
    const p = {
        name:  (currentProduct.name_main || currentProduct.name || '').toString(),
        price: (currentProduct.price || '0').toString(),
        brand: (currentProduct.brand || '').toString(),
        sku:   (currentProduct.barcode || currentProduct.sku || '').toString(),
        cf1: (currentProduct.custom_field_1 || '').toString().trim(),
        cf2: (currentProduct.custom_field_2 || '').toString().trim(),
        cf3: (currentProduct.custom_field_3 || '').toString().trim()
    };

    // ✅ إذا كان العنصر مخصصاً للموديل، نرجع القيمة مباشرة
    if (key === 'cf3' || key === 'custom_field_3' || key === 'model' || key === 'موديل') {
        return p.cf3 !== "" ? p.cf3 : "";
    }

    let result = txt;

    // ✅ استبدال المتغيرات العامة
    result = result
        .replace(/\{\{\s*product_name\s*\}\}/gi,       p.name)
        .replace(/\{\{\s*name_main\s*\}\}/gi,           p.name)
        .replace(/\{\{\s*name\s*\}\}/gi,               p.name)
        .replace(/\{\{\s*price\s*\}\}/gi,               p.price)
        .replace(/\{\{\s*brand\s*\}\}/gi,               p.brand)
        .replace(/\{\{\s*sku\s*\}\}/gi,                 p.sku)
        .replace(/\{\{\s*custom_field_1\s*\}\}/gi,      p.cf1)
        .replace(/\{\{\s*custom_field_2\s*\}\}/gi,      p.cf2)
        .replace(/\{\{\s*custom_field_3\s*\}\}/gi,      p.cf3)
        .replace(/\{\{\s*product_custom_field3\s*\}\}/gi, p.cf3)
        .replace(/\{\{\s*model\s*\}\}/gi,               p.cf3)
        .replace(/\{\{\s*MODEL\s*\}\}/gi,               p.cf3);

    // ✅ استبدال الكلمات الثابتة
    result = result.replace(/اسم المنتج/gi, p.name);
    result = result.replace(/0\.00/gi, p.price);
    result = result.replace(/Brand/gi, p.brand);

    // ✅ معالجة حقل الموديل بشكل خاص (إذا كان النص يحتوي على "موديل" أو "Model")
    if (p.cf3 !== "") {
        result = result.replace(/موديل/gi, p.cf3);
        result = result.replace(/Model/gi, p.cf3);
    } else {
        // إذا كان الموديل فارغاً، قم بإخفاء كلمة "موديل" تماماً
        result = result.replace(/موديل/gi, '');
        result = result.replace(/Model/gi, '');
    }

    // ✅ معالجة اللون والمقاس
    if (/لون|color/i.test(key) || /لون|color/i.test(txt)) {
        return p.cf1 !== "" ? p.cf1 : "";
    }
    if (/مقاس|size/i.test(key) || /مقاس|size/i.test(txt)) {
        return p.cf2 !== "" ? p.cf2 : "";
    }

    if (key === 'extra')                               return result;
    if (key === 'barcode-container' || /barcode/i.test(key)) return p.sku;
    if (key === 'lblName' || /name/i.test(key))        return p.name;

    return result;
}

function generateBarcode(code, barcodeSettings) {
    if (!code || typeof JsBarcode !== "function") return null;
    const widthMm = parseFloat(barcodeSettings?.width) || 40;
    const heightMm = parseFloat(barcodeSettings?.height) || 20;
    const color = barcodeSettings?.color || '#000000';
    const fontSize = parseInt(barcodeSettings?.font_size) || 12;
    const showText = barcodeSettings?.show_text !== false;
    const type = barcodeSettings?.type || 'CODE128';
    const heightPx = mmToPx(heightMm);
    const svg = document.createElementNS("http://www.w3.org/2000/svg", "svg");
    try {
        JsBarcode(svg, code, {
            format: type, lineColor: color, width: (widthMm / 40) * 1.2, height: heightPx,
            displayValue: showText, font: "Arial", fontSize: fontSize, textMargin: 2, margin: 0,
            textAlign: 'right', textPosition: 'bottom'
        });
        svg.style.width = mmToPx(widthMm) + 'px';
        svg.style.height = heightPx + 'px';
        return svg;
    } catch (error) {
        console.error('Barcode Error:', error);
        return null;
    }
}


function generateLabelHTMLForProduct(prod, designData) {
    
      // ✅ تأكد من قراءة الموديل من جميع المصادر الممكنة
    const modelValue = prod.product_custom_field3 || prod.custom_field3 || prod.custom_field_3 || prod.model || '';
    
    currentProduct = {
        name: prod.product_name || prod.name || '',
        name_main: prod.product_name || prod.name || '',
        price: Math.round(parseFloat(prod.selling_price || prod.price || 0)).toString()+ ' JD',
        barcode: (prod.barcode || prod.sku || '').toString(),
        brand: prod.brand_name || prod.brand || '',
        // ✅ الإصلاح: v_color للون، v_size للمقاس (كانا معكوسين)
        custom_field_1: (prod.v_color || prod.color || prod.custom_field1 || '').toString().trim(),
        custom_field_2: (prod.v_size  || prod.size  || prod.custom_field2 || '').toString().trim(),
         custom_field_3: (prod.product_custom_field3 || prod.custom_field3 || prod.custom_field_3 || prod.model || '').toString().trim(),
        custom_field_4: prod.custom_field4 || prod.product_custom_field4 || ''
    };
    


    const tempDiv = $('<div/>').addClass('label-content').css({
        width: '100%', height: '100%', position: 'relative'
    });

    const processElement = (key, el, isExtra) => {
        if (!el || el.visible === false) return;

        const left = parsePosition(el.left);
        const top  = parsePosition(el.top);

        // معالجة الباركود
        if (key === 'barcode-container' || /barcode/i.test(key)) {
            const barcodeSvg = generateBarcode(currentProduct.barcode, designData.barcode_settings);
            if (barcodeSvg) {
                const svgHtml = $('<div>').append($(barcodeSvg).clone()).html();
                tempDiv.append($('<div/>').css({ position:'absolute', left: left, top: top }).html(svgHtml));
            }
            return;
        }

        let text = substituteElementText(isExtra ? 'extra' : key, el);

        // تأكيد إضافي للحقول cf
        if (key.startsWith('cf')) {
            const num = key.replace('cf', '');
            if (currentProduct['custom_field_' + num]) {
                text = currentProduct['custom_field_' + num];
            }
        }

        // حجب الألوان الفارغة
        if (currentProduct.custom_field_1 === '' && (/لون|color/i.test(key) || /لون|color/i.test(text))) {
            return;
        }
        // حجب المقاسات الفارغة
        if (currentProduct.custom_field_2 === '' && (/مقاس|size/i.test(key) || /مقاس|size/i.test(text))) {
            return;
        }

        if (text === '') return;

        // ✅ تحديد نوع العنصر لتطبيق الستايل المناسب
        const isNameField    = (key === 'lblName' || /name/i.test(key));
        const isExtraField   = isExtra; // الحقول الإضافية (extra_elements)
        const isPriceField   = /price/i.test(key);

        // ✅ حجم الخط: الاسم يصغر، الحقول الإضافية تكبر قليلاً
        let fontSizePx;
        if (isNameField) {
            // الاسم: خذ حجم المصمم لكن اجعله صغيراً (لا يتجاوز 14px)
            fontSizePx = Math.min(parseInt(el.fontSize) || 12, 14);
        } else if (isExtraField) {
            // الحقول الإضافية: كبّر بمقدار 2-3 بكسل عن الحجم المصمم
            fontSizePx = (parseInt(el.fontSize) || 9) + 5;
        } else {
            fontSizePx = parseInt(el.fontSize) || 9;
        }

        // ✅ الوزن: الاسم عادي، الحقول الإضافية bold
        let fontWeight;
        if (isExtraField || isPriceField) {
            fontWeight = 'bold';
        } else {
            fontWeight = 'normal';
        }

        tempDiv.append($('<div/>').addClass('element').css({
            position:    'absolute',
            left:        left,
            top:         top,
            'font-size':   pxToPt(fontSizePx),
            'font-weight': fontWeight,
            'font-family': (el.fontFamily || 'Arial').split(',')[0].trim(),
            color:         el.color || '#000000',
            'white-space': 'nowrap'
        }).text(text));
    };

    // معالجة كافة العناصر
    Object.keys(designData.elements       || {}).forEach(key => processElement(key, designData.elements[key],       false));
    Object.keys(designData.extra_elements || {}).forEach(key => processElement(key, designData.extra_elements[key], true));

    const wMm = designData.label_size?.width  || 50;
    const hMm = designData.label_size?.height || 25;

    return `<!DOCTYPE html><html><head><meta charset="utf-8"><style>
        * { -webkit-print-color-adjust: exact; box-sizing: border-box; }
        body { margin: 0; padding: 0; width: ${wMm}mm; height: ${hMm}mm; overflow: hidden; font-family: 'Tahoma', 'Arial', sans-serif; }
        .label-content { width: 100%; height: 100%; position: relative; }
        .element { position: absolute; white-space: nowrap; }
        svg { display: block !important; }
    </style></head><body><div class="label-content">${tempDiv.html()}</div></body></html>`;
}
// الدالة الرئيسية للطباعة بعد الحفظ
async function printAfterSave(products) {
    if (!products || products.length === 0) return;
    

    const design = await getBarcodeDesign();
    if (!design) {
        console.warn('⚠️ لم يتم جلب تصميم الباركود');
        return;
    }

    if (typeof _qzConnected === 'undefined' || !_qzConnected || !qz.websocket.isActive()) {
        try {
            await qz.websocket.connect();
            _qzConnected = true;
        } catch(e) {
            console.error('❌ QZ Tray غير متصل');
            toastr.error('تعذرت الطباعة: QZ Tray غير متصل');
            return;
        }
    }

    let printerName = (typeof BARCODE_PRINTER_NAME !== 'undefined') ? BARCODE_PRINTER_NAME : null;
    if (!printerName) {
        try {
            const printers = await qz.printers.find();
            printerName = printers[0] || '';
        } catch(e) {}
    }

    if (!printerName) {
        toastr.error('لم يتم تحديد طابعة الباركود');
        return;
    }

    const printData = [];
    products.forEach(function(prod) {
        
        const price = prod.selling_price || prod.selling || prod.price || 0;
        const qty   = parseInt(prod.print_qty || prod.qty || 1);
        

        const normalizedProd = {
            ...prod,
            selling_price: price,
            barcode: prod.barcode || prod.sku
        };

        const copies = Math.max(1, qty);
        const html = generateLabelHTMLForProduct(normalizedProd, design);
        
        for (let i = 0; i < copies; i++) {
            printData.push({ type: 'html', format: 'plain', data: html });
        }
    });

    if (printData.length === 0) return;

    try {
        const cfg = qz.configs.create(printerName, { 
            margins: 0, 
            colorType: 'color' 
        });
        await qz.print(cfg, printData);
        toastr.success(`✅ تم إرسال ${printData.length} ملصق للطابعة`);
    } catch(e) {
        console.error('❌ خطأ في الطباعة:', e);
        toastr.error('فشل الطباعة: ' + (e?.toString?.() || e));
    }
}

// =====================================================
// ✅ الربط مع زر "إضافة للمعاينة"
// =====================================================
$(document).on('ajaxSuccess_newProduct', function(e, products) {
    if ($('#print_barcode_check').is(':checked')) {
        printAfterSave(products);
    }
});


// --- 1. زر طباعة المحدد (الكل) ---
$(document).on('click', '#print_selected_products', function() {
    var selected_products = [];
    
    $('.row_check:checked').each(function() {
        var row = $(this).closest('tr');
        
        var variant_text = row.find('.p_variant').text() || '';
        var parts = variant_text.split('/');
        var custom_field1 = parts[1] ? parts[1].trim() : '';
        var custom_field2 = parts[0] ? parts[0].trim() : '';
        
        var model_val = row.data('model') || '';

        selected_products.push({
            id: row.data('id'),
            name: row.data('name'),
            sku: row.data('sku'),
            barcode: row.data('sku'),
            selling_price: row.data('selling'), 
            qty: row.data('qty'), 
            print_qty: row.data('qty'),                
            variant_info: row.data('variant'),
            custom_field1: custom_field1,
            custom_field2: custom_field2,
            custom_field3: model_val,
            custom_field_3: model_val,
            product_custom_field3: model_val  // ✅ هذا السطر مهم جداً!
        });
    });

    if (selected_products.length === 0) {
        toastr.error("يرجى اختيار منتج واحد على الأقل");
        return;
    }

    printAfterSave(selected_products);
});
// --- 2. زر طباعة لسطر واحد فقط ---
$(document).on('click', '.print_single_row', function() {
    var row = $(this).closest('tr');
    
    var variant_text = row.find('.p_variant').text() || '';
    var parts = variant_text.split('/');
    var custom_field2 = parts[0] ? parts[0].trim() : '';  // مقاس
    var custom_field1 = parts[1] ? parts[1].trim() : '';  // لون
    
    var model_val = row.data('model') || '';
    

    var prodData = [{
        id: row.data('id'),
        name: row.data('name'),
        sku: row.data('sku'),
        barcode: row.data('sku'),
        selling_price: row.data('selling'),
        print_qty: row.data('qty'),
        qty: row.data('qty'),
        variant_info: row.data('variant'),
        custom_field1: custom_field1,
        custom_field2: custom_field2,
        custom_field3: model_val,
        custom_field_3: model_val,
        product_custom_field3: model_val  // ✅ هذا السطر مهم جداً!
    }];
    

    printAfterSave(prodData);
});
$(document).ready(function() {
    
   
    $(document).on('change', '#select_all_variants', function() {
       
        let isChecked = $(this).is(':checked');
        
        $('#final_products_table tbody .row_check').prop('checked', isChecked);
    });

    $(document).on('change', '.row_check', function() {
        if ($('.row_check:checked').length == $('.row_check').length) {
            $('#select_all_variants').prop('checked', true);
        } else {
            $('#select_all_variants').prop('checked', false);
        }
    });

});

$(document).ready(function() {

    // دالة الحساب 
    function calculateTableTotals() {
        let rows = $('#final_products_table tbody tr');
        let rowCount = rows.length; 
        let totalQty = 0;

        
        rows.each(function() {
            let qty = parseFloat($(this).find('.v_qty_input').val()) || 0;
            totalQty += qty;
        });

        $('#total_rows_count').text(rowCount);
        $('#total_qty_sum').text(totalQty);

        if(rowCount > 0) {
            $('#save_all_products').show();
        } else {
            $('#save_all_products').hide();
        }
    }

    $(document).on('input change', '.v_qty_input', function() {
        calculateTableTotals();
    });

    $(document).on('click', '.rem_f', function() {
        $(this).closest('tr').remove();
        calculateTableTotals();
    });



});



$(document).ready(function() {

    // 1. كود إضافة الوحدة (Unit)
    $(document).on('submit', 'form#quick_add_unit_form', function(e) {
        e.preventDefault();
        var form = $(this);
        var data = form.serialize();

        $.ajax({
            method: 'POST',
            url: $(this).attr('action'),
            dataType: 'json',
            data: data,
            success: function(result) {
                if (result.success == true) {
                    var newOption = new Option(result.data.short_name, result.data.id, true, true);
                    $('#unit_id').append(newOption).trigger('change');
                    $(form).closest('.modal').modal('hide');
                    toastr.success(result.msg);
                } else {
                    toastr.error(result.msg);
                }
            },
        });
    });

    // 2. كود إضافة الماركة (Brand)
    $(document).on('submit', 'form#quick_add_brand_form', function(e) {
        e.preventDefault();
        e.stopImmediatePropagation();
        var form = $(this);
        var data = form.serialize();

        $.ajax({
            method: 'POST',
            url: $(this).attr('action'),
            dataType: 'json',
            data: data,
            success: function(result) {
                if (result.success == true) {
                    var newOption = new Option(result.data.name, result.data.id, true, true);
                    $('#brand_id').append(newOption).trigger('change');
                    $(form).closest('.modal').modal('hide');
                    toastr.success(result.msg);
                } else {
                    toastr.error(result.msg);
                }
            },
        });
    });

    // 3. كود إضافة التصنيف   
$(document).on('submit', 'form#category_add_form', function(e) {
    e.preventDefault();
    var form = $(this);
    var data = form.serialize();

    $.ajax({
        method: 'POST',
        url: $(this).attr('action'),
        dataType: 'json',
        data: data,
        success: function(result) {
            if (result.success == true) {
 
                $(form).closest('.modal').modal('hide');
                toastr.success(result.msg);

                var newOption = new Option(result.data.name, result.data.id, true, true);
                
                var is_sub_category = form.find('input[name="add_as_sub_cat"]').is(':checked');
                var parent_id = form.find('select[name="parent_id"]').val();

                if (is_sub_category && parent_id) {
                    // --- حالة إضافة صنف فرعي ---
                    
                    if ($('#category_id').length > 0) {
                        $('#category_id').val(parent_id).trigger('change');
                    }
                    if ($('#sub_category_id').length > 0) {

                        $('#sub_category_id').append(newOption).val(result.data.id).trigger('change');
                    }
                } else {
                    // ------------------------------
                    
                    if ($('#category_id').length > 0) {
                        $('#category_id').append(newOption).val(result.data.id).trigger('change');
                        

                        if ($('#sub_category_id').length > 0) {
                            $('#sub_category_id').val('').trigger('change');
                        }
                    }
                }
            } else {
                toastr.error(result.msg);
            }
        },
        error: function(xhr) {
            toastr.error("حدث خطأ أثناء الحفظ");
        }
    });
});
});

// =====================================================
// Autocomplete للحقول المخصصة
// =====================================================
$(document).ready(function () {
    var cfTimers = {};

    // إنشاء حاوية عائمة موحدة في الـ Body إذا لم تكن موجودة مسبقاً
    if ($('#cf-global-dropdown').length === 0) {
        $('body').append('<div id="cf-global-dropdown" style="display:none; position:absolute; background:#fff; border:1px solid #ccc; border-radius:8px; box-shadow:0 4px 15px rgba(0,0,0,0.15); z-index:999999; max-height:220px; overflow-y:auto;"></div>');
    }

    var globalDropdown = $('#cf-global-dropdown');

    // الاستماع لأي حقل يحمل كلاس cf-autocomplete
    $(document).on('input', '.cf-autocomplete', function () {
        var input = $(this);
        var field = input.data('field');
        var q     = input.val();

        clearTimeout(cfTimers[field]);

        if (q.length < 1) {
            globalDropdown.hide().empty();
            return;
        }

        cfTimers[field] = setTimeout(function () {
            $.get('/new-products/cf-suggestions', { field: field, q: q })
                .done(function (results) {
                    globalDropdown.empty();

                    if (!results || results.length === 0) {
                        globalDropdown.hide();
                        return;
                    }

                    // حساب الأبعاد والموقع الحالي للحقل بدقة على الشاشة
                    var offset = input.offset();
                    var inputHeight = input.outerHeight();
                    var inputWidth = input.outerWidth();

                    results.forEach(function (val) {
                        var item = $('<div>').text(val).css({
                            padding      : '9px 14px',
                            cursor       : 'pointer',
                            fontSize     : '0.88rem',
                            borderBottom : '1px solid #f0f0f0',
                            transition   : 'background 0.15s',
                            color        : '#333',
                        });

                        item.on('mouseenter', function () {
                            $(this).css('background', '#f1f5f9'); // لون خلفية ناعم عند التمرير
                        }).on('mouseleave', function () {
                            $(this).css('background', '#fff');
                        });

                        // عند اختيار عنصر من القائمة المنسدلة العائمة
                        item.on('mousedown', function (e) {
                            e.preventDefault();
                            input.val(val).trigger('change');
                            globalDropdown.hide().empty();
                        });

                        globalDropdown.append(item);
                    });

                    // تثبيت القائمة العائمة أسفل الحقل النشط مباشرة وضبط عرضها
                    globalDropdown.css({
                        top: (offset.top + inputHeight) + 'px',
                        left: offset.left + 'px',
                        width: inputWidth + 'px'
                    }).show();
                });
        }, 300);
    });

    // إغلاق القائمة العائمة عند الخروج من الحقل
    $(document).on('blur', '.cf-autocomplete', function () {
        setTimeout(function () { 
            globalDropdown.hide(); 
        }, 200);
    });

    // إغلاق القائمة عند الضغط في أي مكان خارج الحقل أو خارج القائمة المنسدلة
    $(document).on('click', function (e) {
        if (!$(e.target).closest('.cf-autocomplete, #cf-global-dropdown').length) {
            globalDropdown.hide();
        }
    });

    // إعادة ضبط موقع القائمة العائمة في حال قام المستخدم بعمل Scroll للصفحة أثناء فتحها
    $(window).on('scroll resize', function() {
        if (globalDropdown.is(':visible')) {
            var activeInput = $('.cf-autocomplete:focus');
            if(activeInput.length) {
                var offset = activeInput.offset();
                globalDropdown.css({
                    top: (offset.top + activeInput.outerHeight()) + 'px',
                    left: offset.left + 'px'
                });
            }
        }
    });
});


</script>
@endsection
