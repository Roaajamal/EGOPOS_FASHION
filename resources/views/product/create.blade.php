@extends('layouts.app')
@section('title', __('product.add_new_product'))

@section('content')

<!-- Content Header (Page header) -->
<section class="content-header">
    <h1 class="tw-text-xl md:tw-text-3xl tw-font-bold tw-text-black">@lang('product.add_new_product')</h1>
    <!-- <ol class="breadcrumb">
        <li><a href="#"><i class="fa fa-dashboard"></i> Level</a></li>
        <li class="active">Here</li>
    </ol> -->
</section>

<!-- Main content -->
<section class="content">
    @php
    $form_class = empty($duplicate_product) ? 'create' : '';
    $is_image_required = !empty($common_settings['is_product_image_required']);
    @endphp
    {!! Form::open(['url' => action([\App\Http\Controllers\ProductController::class, 'store']), 'method' => 'post',
    'id' => 'product_add_form','class' => 'product_form ' . $form_class, 'files' => true ]) !!}
    @component('components.widget', ['class' => 'box-primary'])
    <div class="row">
        <div class="col-sm-4">
            <div class="form-group">
                {!! Form::label('name', __('product.product_name') . ':*') !!}
                {!! Form::text('name', !empty($duplicate_product->name) ? $duplicate_product->name : null, ['class' => 'form-control', 'required',
                'placeholder' => __('product.product_name')]); !!}
            </div>
        </div>

        <div class="col-sm-4 @if(isset($common_settings['show_product_sku']) && !$common_settings['show_product_sku']) hide @endif">
            <div class="form-group">
                {!! Form::label('sku', __('product.sku') . ':') !!} @show_tooltip(__('tooltip.sku'))
                {!! Form::text('sku', !empty($duplicate_product->sku) ? $duplicate_product->sku : null, ['class' => 'form-control',
                'placeholder' => __('product.sku')]); !!}
            </div>
        </div>
        <div class="col-sm-4 @if(isset($common_settings['show_product_barcode_type']) && !$common_settings['show_product_barcode_type']) hide @endif">
            <div class="form-group">
                {!! Form::label('barcode_type', __('product.barcode_type') . ':*') !!}
                {!! Form::select('barcode_type', $barcode_types, !empty($duplicate_product->barcode_type) ? $duplicate_product->barcode_type : ($common_settings['default_barcode_type'] ?? $barcode_default), ['class' => 'form-control select2', 'required']); !!}
            </div>
        </div>

        <div class="clearfix"></div>
        <div class="col-sm-4">
            <div class="form-group">
                {!! Form::label('unit_id', __('product.unit') . ':*') !!}
                <div class="input-group">
                    {!! Form::select('unit_id', $units, !empty($duplicate_product->unit_id) ? $duplicate_product->unit_id : session('business.default_unit'), ['class' => 'form-control select2', 'required']); !!}
                    <span class="input-group-btn">
                        <button type="button" @if(!auth()->user()->can('unit.create')) disabled @endif class="btn btn-default bg-white btn-flat btn-modal" data-href="{{action([\App\Http\Controllers\UnitController::class, 'create'], ['quick_add' => true])}}" title="@lang('unit.add_unit')" data-container=".view_modal"><i class="fa fa-plus-circle text-primary fa-lg"></i></button>
                    </span>
                </div>
            </div>
        </div>

        <div class="col-sm-4 @if(!session('business.enable_sub_units')) hide @endif">
            <div class="form-group">
                {!! Form::label('sub_unit_ids', __('lang_v1.related_sub_units') . ':') !!} @show_tooltip(__('lang_v1.sub_units_tooltip'))

                {!! Form::select('sub_unit_ids[]', [], !empty($duplicate_product->sub_unit_ids) ? $duplicate_product->sub_unit_ids : null, ['class' => 'form-control select2', 'multiple', 'id' => 'sub_unit_ids']); !!}
            </div>
        </div>
        @if(!empty($common_settings['enable_secondary_unit']))
        <div class="col-sm-4">
            <div class="form-group">
                {!! Form::label('secondary_unit_id', __('lang_v1.secondary_unit') . ':') !!} @show_tooltip(__('lang_v1.secondary_unit_help'))
                {!! Form::select('secondary_unit_id', $units, !empty($duplicate_product->secondary_unit_id) ? $duplicate_product->secondary_unit_id : null, ['class' => 'form-control select2']); !!}
            </div>
        </div>
        @endif

        <div class="col-sm-4 @if(!session('business.enable_brand')) hide @endif">
            <div class="form-group">
                {!! Form::label('brand_id', __('product.brand') . ':') !!}
                <div class="input-group">
                    {!! Form::select('brand_id', $brands, !empty($duplicate_product->brand_id) ? $duplicate_product->brand_id : ($common_settings['default_brand_id'] ?? null), ['placeholder' => __('messages.please_select'), 'class' => 'form-control select2']); !!}
                    <span class="input-group-btn">
                        <button type="button" @if(!auth()->user()->can('brand.create')) disabled @endif class="btn btn-default bg-white btn-flat btn-modal" data-href="{{action([\App\Http\Controllers\BrandController::class, 'create'], ['quick_add' => true])}}" title="@lang('brand.add_brand')" data-container=".view_modal"><i class="fa fa-plus-circle text-primary fa-lg"></i></button>
                    </span>
                </div>
            </div>
        </div>
        <div class="col-sm-4 @if(!session('business.enable_category')) hide @endif">
            <div class="form-group">
                {!! Form::label('category_id', __('product.category') . ' / نوع المنتج:') !!}
                {!! Form::select('category_id', $categories, !empty($duplicate_product->category_id) ? $duplicate_product->category_id : ($common_settings['default_category_id'] ?? null), ['placeholder' => __('messages.please_select'), 'class' => 'form-control select2', 'id' => 'category_id']); !!}
                <small class="help-block">إذا اخترت تصنيفاً (نوع منتج)، يمكن جعل المنتج متغيراً تلقائياً (مقاسات وألوان).</small>
            </div>
        </div>

        <div class="col-sm-4 @if(!(session('business.enable_category') && session('business.enable_sub_category'))) hide @endif">
            <div class="form-group">
                {!! Form::label('sub_category_id', __('product.sub_category') . ':') !!}
                {!! Form::select('sub_category_id', $sub_categories, !empty($duplicate_product->sub_category_id) ? $duplicate_product->sub_category_id : null, ['placeholder' => __('messages.please_select'), 'class' => 'form-control select2']); !!}
            </div>
        </div>

        @php
        $default_location = null;
        if (!empty($duplicate_product) && isset($duplicate_product->product_locations)) {
            $pl = $duplicate_product->product_locations;
            if (is_object($pl) && method_exists($pl, 'pluck')) {
                $default_location = $pl->pluck('id')->toArray();
            } else {
                $default_location = is_array($pl) ? $pl : (array) $pl;
            }
        }
        if ($default_location === null && !empty($common_settings['default_product_locations']) && is_array($common_settings['default_product_locations'])) {
            $default_location = $common_settings['default_product_locations'];
        }
        if ($default_location === null && count($business_locations) == 1) {
            $default_location = [array_key_first($business_locations->toArray())];
        }
        @endphp
        <div class="col-sm-4 @if(isset($common_settings['show_product_locations']) && !$common_settings['show_product_locations']) hide @endif">
            <div class="form-group">
                {!! Form::label('product_locations', __('business.business_locations') . ':') !!} @show_tooltip(__('lang_v1.product_location_help'))
                {!! Form::select('product_locations[]', $business_locations, $default_location, ['class' => 'form-control select2', 'multiple', 'id' => 'product_locations']); !!}
            </div>
        </div>


        <div class="clearfix"></div>

        <div class="col-sm-4">
            <div class="form-group">
                <br>
                <label>
                    {!! Form::checkbox('enable_stock', 1, !empty($duplicate_product) ? $duplicate_product->enable_stock : (isset($common_settings['default_enable_stock']) ? (int)$common_settings['default_enable_stock'] : true), ['class' => 'input-icheck', 'id' => 'enable_stock']); !!} <strong>@lang('product.manage_stock')</strong>
                </label>@show_tooltip(__('tooltip.enable_stock')) <p class="help-block"><i>@lang('product.enable_stock_help')</i></p>
            </div>
        </div>
        <div class="col-sm-4 @if(!empty($duplicate_product) && $duplicate_product->enable_stock == 0) hide @endif @if(isset($common_settings['show_alert_quantity']) && !$common_settings['show_alert_quantity']) hide @endif" id="alert_quantity_div">
            <div class="form-group">
                {!! Form::label('alert_quantity', __('product.alert_quantity') . ':') !!} @show_tooltip(__('tooltip.alert_quantity'))
                {!! Form::text('alert_quantity', !empty($duplicate_product->alert_quantity) ? @format_quantity($duplicate_product->alert_quantity) : ($common_settings['default_alert_quantity'] ?? null) , ['class' => 'form-control input_number',
                'placeholder' => __('product.alert_quantity'), 'min' => '0']); !!}
            </div>
        </div>
        @if(!empty($common_settings['enable_product_warranty']))
        <div class="col-sm-4">
            <div class="form-group">
                {!! Form::label('warranty_id', __('lang_v1.warranty') . ':') !!}
                {!! Form::select('warranty_id', $warranties, null, ['class' => 'form-control select2', 'placeholder' => __('messages.please_select')]); !!}
            </div>
        </div>
        @endif
        <!-- include module fields -->
        @if(!empty($pos_module_data))
        @foreach($pos_module_data as $key => $value)
        @if(!empty($value['view_path']))
        @includeIf($value['view_path'], ['view_data' => $value['view_data']])
        @endif
        @endforeach
        @endif
        <div class="clearfix"></div>
        <div class="col-sm-8 mb-5 @if(isset($common_settings['show_product_description']) && !$common_settings['show_product_description']) hide @endif">
            <div class="form-group">
                <div class="row">
                    <div class="col-sm-8 product-description-label">
                        {!! Form::label('product_description', __('lang_v1.product_description') . ':') !!}
                    </div> 
                </div>
                {!! Form::textarea('product_description', !empty($duplicate_product->product_description) ? $duplicate_product->product_description : null, ['class' => 'form-control']); !!}
            </div>
        </div>
        <div class="col-sm-4">
            <div class="form-group">
               
                <div class="row">
                    <div class="col-sm-6 image-label">
                    {!! Form::label('image', __('lang_v1.product_image') . ':') !!}
                    </div> 
                </div>
                {!! Form::file('image', ['id' => 'upload_image', 'accept' => 'image/*',
                'required' => $is_image_required, 'class' => 'upload-element']); !!}
                <small>
                    <p class="help-block">@lang('purchase.max_file_size', ['size' => (config('constants.document_size_limit') / 1000000)]) <br> @lang('lang_v1.aspect_ratio_should_be_1_1')</p>
                </small>
            </div>
        </div>
    </div>
    <div class="col-sm-4 @if(isset($common_settings['show_product_brochure']) && !$common_settings['show_product_brochure']) hide @endif">
        <div class="form-group">
            {!! Form::label('product_brochure', __('lang_v1.product_brochure') . ':') !!}
            {!! Form::file('product_brochure', ['id' => 'product_brochure', 'accept' => implode(',', array_keys(config('constants.document_upload_mimes_types')))]); !!}
            <small>
                <p class="help-block">
                    @lang('purchase.max_file_size', ['size' => (config('constants.document_size_limit') / 1000000)])
                    @includeIf('components.document_help_text')
                </p>
            </small>
        </div>
    </div>
    @endcomponent

    @component('components.widget', ['class' => 'box-primary'])
    <div class="row">
        @if(session('business.enable_product_expiry'))

        @if(session('business.expiry_type') == 'add_expiry')
        @php
        $expiry_period = 12;
        $hide = true;
        @endphp
        @else
        @php
        $expiry_period = null;
        $hide = false;
        @endphp
        @endif
        <div class="col-sm-4 @if($hide) hide @endif">
            <div class="form-group">
                <div class="multi-input">
                    {!! Form::label('expiry_period', __('product.expires_in') . ':') !!}<br>
                    {!! Form::text('expiry_period', !empty($duplicate_product->expiry_period) ? @num_format($duplicate_product->expiry_period) : $expiry_period, ['class' => 'form-control pull-left input_number',
                    'placeholder' => __('product.expiry_period'), 'style' => 'width:60%;']); !!}
                    {!! Form::select('expiry_period_type', ['months'=>__('product.months'), 'days'=>__('product.days'), '' =>__('product.not_applicable') ], !empty($duplicate_product->expiry_period_type) ? $duplicate_product->expiry_period_type : 'months', ['class' => 'form-control select2 pull-left', 'style' => 'width:40%;', 'id' => 'expiry_period_type']); !!}
                </div>
            </div>
        </div>
        @endif

        <div class="col-sm-4 @if(isset($common_settings['show_enable_sr_no']) && !$common_settings['show_enable_sr_no']) hide @endif">
            <div class="form-group">
                <br>
                <label>
                    {!! Form::checkbox('enable_sr_no', 1, !(empty($duplicate_product)) ? $duplicate_product->enable_sr_no : false, ['class' => 'input-icheck']); !!} <strong>@lang('lang_v1.enable_imei_or_sr_no')</strong>
                </label> @show_tooltip(__('lang_v1.tooltip_sr_no'))
            </div>
        </div>

        <div class="col-sm-4 @if(isset($common_settings['show_not_for_selling']) && !$common_settings['show_not_for_selling']) hide @endif">
            <div class="form-group">
                <br>
                <label>
                    {!! Form::checkbox('not_for_selling', 1, !(empty($duplicate_product)) ? $duplicate_product->not_for_selling : false, ['class' => 'input-icheck']); !!} <strong>@lang('lang_v1.not_for_selling')</strong>
                </label> @show_tooltip(__('lang_v1.tooltip_not_for_selling'))
            </div>
        </div>

        <div class="clearfix"></div>

        <!-- Rack, Row & position number -->
        @if(session('business.enable_racks') || session('business.enable_row') || session('business.enable_position'))
        <div class="col-md-12">
            <h4>@lang('lang_v1.rack_details'):
                @show_tooltip(__('lang_v1.tooltip_rack_details'))
            </h4>
        </div>
        @foreach($business_locations as $id => $location)
        <div class="col-sm-3">
            <div class="form-group">
                {!! Form::label('rack_' . $id, $location . ':') !!}

                @if(session('business.enable_racks'))
                {!! Form::text('product_racks[' . $id . '][rack]', !empty($rack_details[$id]['rack']) ? $rack_details[$id]['rack'] : null, ['class' => 'form-control', 'id' => 'rack_' . $id,
                'placeholder' => __('lang_v1.rack')]); !!}
                @endif

                @if(session('business.enable_row'))
                {!! Form::text('product_racks[' . $id . '][row]', !empty($rack_details[$id]['row']) ? $rack_details[$id]['row'] : null, ['class' => 'form-control', 'placeholder' => __('lang_v1.row')]); !!}
                @endif

                @if(session('business.enable_position'))
                {!! Form::text('product_racks[' . $id . '][position]', !empty($rack_details[$id]['position']) ? $rack_details[$id]['position'] : null, ['class' => 'form-control', 'placeholder' => __('lang_v1.position')]); !!}
                @endif
            </div>
        </div>
        @endforeach
        @endif

        <div class="col-sm-4 @if(isset($common_settings['show_product_weight']) && !$common_settings['show_product_weight']) hide @endif">
            <div class="form-group">
                {!! Form::label('weight', __('lang_v1.weight') . ':') !!}
                {!! Form::text('weight', !empty($duplicate_product->weight) ? $duplicate_product->weight : null, ['class' => 'form-control', 'placeholder' => __('lang_v1.weight')]); !!}
            </div>
        </div>
        @php
        $custom_labels = json_decode(session('business.custom_labels'), true);
        $product_custom_fields = !empty($custom_labels['product']) ? $custom_labels['product'] : [];
        $product_cf_details = !empty($custom_labels['product_cf_details']) ? $custom_labels['product_cf_details'] : [];

        @endphp
        <!--custom fields-->
        <div class="clearfix"></div>

        @foreach($product_custom_fields as $index => $cf)
            @if(!empty($cf))
                @php
                    $db_field_name = 'product_custom_field' . $loop->iteration;
                    $cf_type = !empty($product_cf_details[$loop->iteration]['type']) ? $product_cf_details[$loop->iteration]['type'] : 'text';
                    $dropdown = !empty($product_cf_details[$loop->iteration]['dropdown_options']) ? explode(PHP_EOL, $product_cf_details[$loop->iteration]['dropdown_options']) : [];
                @endphp

                <div class="col-sm-3">
                    <div class="form-group">
                        {!! Form::label($db_field_name, $cf . ':') !!}

                        @if(in_array($cf_type, ['text', 'date']))
                        
                            <input type="{{$cf_type}}" name="{{$db_field_name}}" id="{{$db_field_name}}" value="{{!empty($duplicate_product->$db_field_name) ? $duplicate_product->$db_field_name : null}}" class="form-control" placeholder="{{$cf}}">

                        @elseif($cf_type == 'dropdown')
                            <!-- {!! Form::select($db_field_name, $dropdown, !empty($duplicate_product->$db_field_name) ? $duplicate_product->$db_field_name : null, ['placeholder' => $cf, 'class' => 'form-control select2']); !!} -->
                            <select name="{{ $db_field_name }}" id="{{ $db_field_name }}" class="form-control select2">
                                <option value="">{{ $cf }}</option>
                                @foreach($dropdown as $option)
                                    <option value="{{ $option }}" @if(!empty($duplicate_product->$db_field_name) && $option == $duplicate_product->$db_field_name) selected @endif>
                                        {{ $option }}
                                    </option>
                                @endforeach
                            </select>
                        @endif
                    </div>
                </div>
            @endif
        @endforeach

        <div class="col-sm-3 @if(isset($common_settings['show_preparation_time']) && !$common_settings['show_preparation_time']) hide @endif">
            <div class="form-group">
                {!! Form::label('preparation_time_in_minutes', __('lang_v1.preparation_time_in_minutes') . ':') !!}
                {!! Form::number('preparation_time_in_minutes', !empty($duplicate_product->preparation_time_in_minutes) ? $duplicate_product->preparation_time_in_minutes : null, ['class' => 'form-control', 'placeholder' => __('lang_v1.preparation_time_in_minutes')]); !!}
            </div>
        </div>
        <!--custom fields-->
        <div class="clearfix"></div>
        @include('layouts.partials.module_form_part')
    </div>
    @endcomponent

    @component('components.widget', ['class' => 'box-primary'])
    <div class="row">

        <div class="col-sm-4 @if(!session('business.enable_price_tax')) hide @endif">
            <div class="form-group">
                {!! Form::label('tax', __('product.applicable_tax') . ':') !!}
                {!! Form::select('tax', $taxes, !empty($duplicate_product->tax) ? $duplicate_product->tax : ($common_settings['default_tax_id'] ?? null), ['placeholder' => __('messages.please_select'), 'class' => 'form-control select2'], $tax_attributes); !!}
            </div>
        </div>

        <div class="col-sm-4 @if(!session('business.enable_price_tax')) hide @endif">
            <div class="form-group">
                {!! Form::label('tax_type', __('product.selling_price_tax_type') . ':*') !!}
                {!! Form::select('tax_type', ['inclusive' => __('product.inclusive'), 'exclusive' => __('product.exclusive')], !empty($duplicate_product->tax_type) ? $duplicate_product->tax_type : ($common_settings['default_tax_type'] ?? 'exclusive'),
                ['class' => 'form-control select2', 'required']); !!}
            </div>
        </div>

        <div class="clearfix"></div>

        <div class="col-sm-4">
            <div class="form-group">
                {!! Form::label('type', __('product.product_type') . ':*') !!} @show_tooltip(__('tooltip.product_type'))
                {!! Form::select('type', $product_types, !empty($duplicate_product->type) ? $duplicate_product->type : ($common_settings['default_product_type'] ?? 'single'), ['class' => 'form-control select2', 'id' => 'type',
                'required', 'data-action' => !empty($duplicate_product) ? 'duplicate' : 'add', 'data-product_id' => !empty($duplicate_product) ? $duplicate_product->id : '0']); !!}
           
            </div>
        </div>

        <div class="form-group col-sm-12" id="product_form_part">
            @include('product.partials.single_product_form_part', [
                'profit_percent' => $default_profit_percent,
                'default_dpp' => $default_dpp ?? null,
                'default_dpp_inc_tax' => $default_dpp_inc_tax ?? null,
                'duplicate_product' => $duplicate_product ?? null
            ])
        </div>
        
{{-- ==================================================== --}}
{{-- قسم الأحجام والألوان – تصميم حديث وسهل الاستخدام --}}
{{-- ==================================================== --}}
<div class="col-sm-12" id="size_color_combo_section" style="display: none;">
    <div class="panel panel-default" style="border-radius: 14px; box-shadow: 0 10px 25px rgba(0,0,0,0.06); border: 0;">
        <div class="panel-heading" style="border-radius: 14px 14px 0 0; background: linear-gradient(135deg,#4f46e5,#7c3aed); color: #fff; padding: 14px 18px;">
            <div style="display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:8px;">
                <div>
                    <h3 class="panel-title" style="margin:0; font-size:17px; font-weight:700;">
                        <i class="fa fa-tags"></i>
                        <span style="margin-right:6px;">@lang('product.size_color_combinations')</span>
                    </h3>
                    <p style="margin:4px 0 0; font-size:12px; opacity:0.9;">
                        أضف الألوان مرة واحدة، ثم الأحجام، ودع النظام ينشئ لك كل التركيبات مع كمياتها.
                    </p>
                </div>
                <div id="size_color_require_locations_msg" style="display:none; margin:0;">
                    <span class="label label-warning" style="font-size:12px; padding:6px 10px;">
                        <i class="fa fa-exclamation-triangle"></i>
                        اختر فروع النشاط أولاً لتفعيل الألوان والمقاسات
                    </span>
                </div>
            </div>
        </div>

        <div class="panel-body" style="background:#f9fafb; border-radius: 0 0 14px 14px;">
            <div class="row size_color_content_block">

                {{-- سعر واحد لجميع المقاسات (للمتباين فقط) --}}
                <div class="col-sm-12 variable-only-block" style="margin-bottom: 16px; display: none;">
                    <div class="well" style="background:#eef2ff; border:1px solid #e0e7ff; border-radius:10px; padding:10px 14px; max-width:360px;">
                        <label for="variable_single_price" style="font-weight:600; margin-bottom:4px;">
                            <i class="fa fa-dollar text-primary"></i>
                            سعر واحد لجميع المقاسات
                        </label>
                        <input type="text" id="variable_single_price" name="variable_single_price"
                               class="form-control input_number"
                               placeholder="مثال: 10.00"
                               step="0.01"
                               value="{{ isset($duplicate_product->variable_single_price) ? $duplicate_product->variable_single_price : '' }}" />
                        <small class="help-block" style="margin-top:4px;">يمكن تعديل السعر لكل منتج لاحقاً من شاشة الكميات إن لزم.</small>
                    </div>
                </div>

                {{-- بطاقة الألوان --}}
                <div class="col-sm-6">
                    <div class="well" style="background:#ffffff; border-radius:10px; border:1px solid #e5e7eb;">
                        <h4 style="margin-top:0; font-size:15px; font-weight:700;">
                            <i class="fa fa-palette text-primary"></i>
                            <span style="margin-right:6px;">الألوان</span>
                        </h4>
                        <p style="font-size:12px; color:#6b7280; margin-bottom:8px;">أدخل لوناً واحداً في كل مرة، ثم اضغط «إضافة لون».</p>
                        <div class="form-group" style="margin-bottom:8px;">
                            <label for="new_color" class="sr-only">أدخل اللون</label>
                            <div class="input-group">
                                <input type="text" id="new_color" class="form-control" placeholder="مثال: أسود، أبيض، أزرق">
                                <span class="input-group-btn">
                                    <button type="button" id="add_color_btn" class="btn btn-primary">
                                        <i class="fa fa-plus"></i> إضافة لون
                                    </button>
                                </span>
                            </div>
                        </div>
                        <p style="font-size:12px; color:#6b7280; margin-bottom:6px;">أو اختر من الخيارات:</p>
                        <div class="preset-colors" style="display:flex; flex-wrap:wrap; gap:6px;">
                            @php
                                $preset_colors = ['أسود', 'أبيض', 'أزرق', 'أحمر', 'أخضر', 'رمادي', 'بني', 'وردي', 'أصفر', 'بيج', 'كحلي', 'أخضر زيتي', 'برتقالي', 'بنفسجي'];
                            @endphp
                            @foreach($preset_colors as $c)
                                <button type="button" class="btn btn-default btn-sm preset-color-option" data-color="{{ $c }}" style="border-radius:20px; padding:4px 12px;">{{ $c }}</button>
                            @endforeach
                        </div>
                        <small class="help-block" style="margin-top:8px;">يمكنك إضافة عدد غير محدود من الألوان، وسيتم تطبيق الأحجام عليها تلقائياً.</small>
                    </div>
                </div>

                {{-- بطاقة الأحجام --}}
                <div class="col-sm-6">
                    <div class="well" style="background:#ffffff; border-radius:10px; border:1px solid #e5e7eb;">
                        <h4 style="margin-top:0; font-size:15px; font-weight:700;">
                            <i class="fa fa-ruler-combined text-info"></i>
                            <span style="margin-right:6px;">الأحجام</span>
                        </h4>
                        <p style="font-size:12px; color:#6b7280; margin-bottom:8px;">اكتب كل الأحجام مفصولة بفاصلة ثم اضغط «إضافة أحجام».</p>
                        <div class="form-group" style="margin-bottom:8px;">
                            <label for="new_size" class="sr-only">إدارة الأحجام</label>
                            <div class="input-group">
                                <input type="text" id="new_size" class="form-control" placeholder="مثال: S, M, L أو 38, 40, 42">
                                <span class="input-group-btn">
                                    <button type="button" id="add_size_btn" class="btn btn-info">
                                        <i class="fa fa-plus"></i> إضافة أحجام
                                    </button>
                                </span>
                            </div>
                        </div>
                        <p style="font-size:12px; color:#6b7280; margin-bottom:6px;">أو اختر من الخيارات:</p>
                        <div class="preset-sizes" style="display:flex; flex-wrap:wrap; gap:6px;">
                            {{-- أحجام حروف --}}
                            @foreach(['XS', 'S', 'M', 'L', 'XL', 'XXL'] as $s)
                                <button type="button" class="btn btn-default btn-sm preset-size-option" data-size="{{ $s }}" style="border-radius:20px; padding:4px 12px;">{{ $s }}</button>
                            @endforeach
                            <span style="width:1px; background:#e5e7eb; margin:0 4px;"></span>
                            {{-- أحجام أرقام --}}
                            @foreach(['34', '36', '38', '40', '42', '44', '46', '48', '50'] as $s)
                                <button type="button" class="btn btn-default btn-sm preset-size-option" data-size="{{ $s }}" style="border-radius:20px; padding:4px 12px;">{{ $s }}</button>
                            @endforeach
                        </div>
                        <small class="help-block" style="margin-top:8px;">الأحجام التي تضيفها هنا ستظهر كصفوف لكل لون، ويمكنك إدخال كمية لكل تركيبة.</small>
                    </div>
                </div>

                {{-- جداول الألوان والمقاسات --}}
                <div class="col-sm-12" id="color_tables_container" style="margin-top: 10px;">
                    <!-- Color tables will be added here -->
                </div>

                {{-- ملخص سريع --}}
                <div class="col-sm-12" style="margin-top: 10px;">
                    <div class="alert alert-info" id="summary_box" style="display: none; border-radius:10px;">
                        <i class="fa fa-info-circle"></i>
                        <strong style="margin-left:4px;">الملخص:</strong>
                        <span id="summary_text"></span>
                    </div>
                </div>

            </div>{{-- /.size_color_content_block --}}
        </div>
    </div>
</div>

<input type="hidden" id="variation_counter" value="0">
<input type="hidden" id="default_profit_percent" value="{{ $default_profit_percent }}">

     

    </div>
    @endcomponent
    <div class="row" style="margin-bottom: 16px; display: none;" id="print_settings_row">
        <div class="col-sm-12">
            <div class="panel panel-default">
                <div class="panel-heading">
                    <strong>🖨️ إعدادات الطباعة (عند استخدام «حفظ وطباعة»)</strong>
                </div>
                <div class="panel-body">
                    <div class="row">
                        <div class="col-sm-4">
                            <div class="form-group">
                                <label for="print_copies">عدد النسخ للطباعة</label>
                                <input type="number" name="print_copies" id="print_copies" class="form-control input_number" value="1" min="1" max="999" placeholder="1">
                                <small class="help-block">كم نسخة من كل ملصق (أو كل مقاس/لون) تُطبع.</small>
                            </div>
                        </div>
                        <div class="col-sm-4">
                            <div class="form-group">
                                <label for="print_send_mode">طريقة الإرسال للطابعة</label>
                                <select name="print_send_mode" id="print_send_mode" class="form-control">
                                    <option value="all_at_once">طباعة مباشرة للكل</option>
                                    <option value="one_by_one">وحدة وحدة</option>
                                </select>
                                <small class="help-block">«مباشرة للكل»: إرسال واحد؛ «وحدة وحدة»: كل ملصق على حدة.</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-sm-12">
            <input type="hidden" name="submit_type" id="submit_type">
            <div class="text-center">
                <div class="btn-group">
                    @if($selling_price_group_count)
                    <button type="submit" value="submit_n_add_selling_prices" class="tw-dw-btn tw-dw-btn-warning tw-dw-btn-lg tw-text-white submit_product_form btn-for-single">@lang('lang_v1.save_n_add_selling_price_group_prices')</button>
                    @endif

                    @can('product.opening_stock')
                    <button id="opening_stock_button" type="submit" value="submit_n_add_opening_stock" class="tw-dw-btn tw-dw-btn-lg tw-text-white bg-purple submit_product_form btn-for-single" @if(!empty($duplicate_product) && $duplicate_product->enable_stock == 0) disabled @endif>@lang('lang_v1.save_n_add_opening_stock')</button>
                    @endcan

                    <button id="save_and_print_button" type="submit" value="submit_n_print" class="tw-dw-btn tw-dw-btn-lg tw-text-white bg-purple submit_product_form btn-for-variable btn-print-both" style="display: none;">🖨️ حفظ وطباعة</button>

                    <a href="{{ action([\App\Http\Controllers\ProductController::class, 'create']) }}" id="clear_form_button" class="tw-dw-btn tw-dw-btn-lg tw-dw-btn-default btn-for-variable" style="display: none;">حذف المدخلات</a>

                    @if(!empty($form_restored_from_session))
                    <a href="{{ action([\App\Http\Controllers\ProductController::class, 'create'], ['clear_form' => 1]) }}" class="tw-dw-btn tw-dw-btn-lg tw-dw-btn-default" id="clear_restored_form_btn">حذف المدخلات</a>
                    @endif

                    <button type="submit" value="save_n_add_another" class="tw-dw-btn tw-dw-btn-lg bg-maroon submit_product_form btn-for-single">@lang('lang_v1.save_n_add_another')</button>

                    <button type="submit" value="submit" class="tw-dw-btn tw-dw-btn-primary tw-dw-btn-lg tw-text-white submit_product_form btn-for-single">@lang('messages.save')</button>
                </div>

            </div>
        </div>
    </div>
    {!! Form::close() !!}

</section>

<!-- /.content -->
@endsection

@section('javascript')

<script src="{{ asset('js/product.js?v=' . $asset_v) }}"></script>

@php
    $restore_size_color_qty = (isset($duplicate_product->size_color_qty) && is_array($duplicate_product->size_color_qty)) ? $duplicate_product->size_color_qty : [];
@endphp
<script type="text/javascript">
    window.__restoreSizeColorQty = @json($restore_size_color_qty);
    window.__printProductId = {{ !empty($print_product_id) ? (int)$print_product_id : 0 }};
    window.__printProductUrl = @json(isset($print_product_url) ? $print_product_url : '');
    window.__enableProductSizeColor = @json(!isset($common_settings['enable_product_size_color']) || !empty($common_settings['enable_product_size_color']));
</script>
<script type="text/javascript">
    $(document).ready(function() {
        __page_leave_confirmation('#product_add_form');

        // إذا وُجد سعر شراء افتراضي: تحديث سعر البيع ونسبة الربح تلقائياً
        if ($('#type').val() === 'single') {
            var dppInc = $('input#single_dpp_inc_tax').val();
            if (dppInc && parseFloat(String(dppInc).replace(/,/g, '')) > 0) {
                $('input#single_dpp_inc_tax').trigger('change');
            }
        }

        // بعد العودة من «أضف الكميات» (فردي): طباعة مخفية مثل المتباين — iframe مخفي يحمّل صفحة الطباعة
        if (window.__printProductUrl && window.__printProductUrl.length > 0) {
            setTimeout(function() {
                var iframe = document.createElement('iframe');
                iframe.setAttribute('style', 'position:absolute;left:-9999px;top:0;width:1px;height:1px;visibility:hidden;border:0');
                document.body.appendChild(iframe);
                iframe.src = window.__printProductUrl;
                setTimeout(function() {
                    try { if (iframe.parentNode) iframe.parentNode.removeChild(iframe); } catch (e) {}
                }, 10000);
            }, 500);
        }

        // Barcode scanner
        onScan.attachTo(document, {
            suffixKeyCodes: [13],
            reactToPaste: true,
            onScan: function(sCode, iQty) {
                $('input#sku').val(sCode);
            },
            onScanError: function(oDebug) {
                console.log(oDebug);
            },
            minLength: 2,
            ignoreIfFocusOn: ['input', '.form-control']
        });
        
        // ============================================
        // نظام الألوان والمقاسات (Excel Style)
        // ============================================
        
        // Global arrays
        var allColors = [];     // جميع الألوان المضافة
        var allSizes = [];      // جميع الأحجام المضافة
        var colorTables = {};   // تخزين جداول الألوان
        
        // إظهار أزرار الحفظ: فردي = كل الأزرار (حفظ، أضف كمية، حفظ وطباعة عند تفعيل الأحجام/الألوان) | كومبو = بدون حفظ وطباعة
        function toggleSubmitButtonsByType() {
            var isSingle = ($('#type').val() === 'single');
            $('.btn-for-single').show();
            $('.btn-for-variable').hide();
            if (isSingle && window.__enableProductSizeColor) {
                $('.btn-print-both').show();
                $('#print_settings_row').show();
            } else {
                $('.btn-print-both').hide();
                $('#print_settings_row').hide();
            }
        }

        // إذا لم يُختر أي فرع نشاط: لا يمكن تعبئة الأحجام/المقاسات؛ عند اختيار فروع النشاط تفتح
        function toggleSizeColorByLocations() {
            var locVal = $('#product_locations').val();
            var hasLocations = (Array.isArray(locVal) && locVal.length > 0) || (locVal && String(locVal).length > 0);
            var sectionVisible = $('#size_color_combo_section').is(':visible');
            if (!sectionVisible) return;
            if (!hasLocations) {
                $('#size_color_require_locations_msg').show();
                $('.size_color_content_block').css({'pointer-events': 'none', 'opacity': '0.6'});
                $('#add_color_btn').prop('disabled', true);
                $('#add_size_btn').prop('disabled', true);
                $('#new_color').prop('disabled', true);
                $('#new_size').prop('disabled', true);
                $('#variable_single_price').prop('disabled', true);
                $('#color_tables_container .size-qty-input').prop('disabled', true);
            } else {
                $('#size_color_require_locations_msg').hide();
                $('.size_color_content_block').css({'pointer-events': '', 'opacity': ''});
                $('#add_color_btn').prop('disabled', false);
                $('#add_size_btn').prop('disabled', false);
                $('#new_color').prop('disabled', false);
                $('#new_size').prop('disabled', false);
                $('#variable_single_price').prop('disabled', false);
                $('#color_tables_container .size-qty-input').prop('disabled', false);
            }
        }
        $(document).on('change', '#product_locations', function() {
            toggleSizeColorByLocations();
        });

        // فردي: جدول السعر + قسم الأحجام/الألوان (إن مُفعّل في الإعدادات). كومبو: إخفاء قسم الأحجام
        function toggleSizeColorSection() {
            if (!window.__enableProductSizeColor) {
                $('#size_color_combo_section').hide();
                $('#product_form_part').show();
                toggleSubmitButtonsByType();
                return;
            }
            var typeVal = $('#type').val();
            $('#product_form_part').show();
            $('.variable-only-block').hide();
            if (typeVal === 'single') {
                $('#size_color_combo_section').show();
            } else {
                $('#size_color_combo_section').hide();
                if (typeof clearSizeColorForm === 'function') clearSizeColorForm();
            }
            toggleSizeColorByLocations();
            toggleSubmitButtonsByType();
        }
        $('#type').change(function() {
            $('#product_form_part').show();
            if (window.__enableProductSizeColor && $(this).val() === 'single') {
                $('#size_color_combo_section').show();
            } else {
                $('#size_color_combo_section').hide();
                if (typeof clearSizeColorForm === 'function') clearSizeColorForm();
            }
            toggleSizeColorByLocations();
            toggleSubmitButtonsByType();
        });
        toggleSizeColorSection();
        toggleSubmitButtonsByType();

        // استعادة بيانات الألوان والمقاسات بعد تحديث الصفحة (حفظ وطباعة)
        if ($('#type').val() === 'single' && window.__restoreSizeColorQty && Object.keys(window.__restoreSizeColorQty).length > 0) {
            var data = window.__restoreSizeColorQty;
            for (var colorName in data) {
                if (data.hasOwnProperty(colorName) && allColors.indexOf(colorName) === -1) {
                    allColors.push(colorName);
                    if (typeof createColorTable === 'function') createColorTable(colorName);
                }
                var sizesObj = data[colorName];
                if (sizesObj && typeof sizesObj === 'object') {
                    var sizesList = Object.keys(sizesObj);
                    sizesList.forEach(function(s) {
                        if (allSizes.indexOf(s) === -1) allSizes.push(s);
                    });
                    if (typeof addSizesToColorTable === 'function') addSizesToColorTable(colorName, sizesList);
                    for (var sizeName in sizesObj) {
                        if (sizesObj.hasOwnProperty(sizeName)) {
                            var qtyVal = sizesObj[sizeName];
                            $('input[name="size_color_qty[' + colorName + '][' + sizeName + ']"]').val(qtyVal).trigger('input');
                        }
                    }
                }
            }
            if (typeof updateSummary === 'function') updateSummary();
            if (typeof syncVariablePriceToForm === 'function') syncVariablePriceToForm();
        }

        // نسخ السعر من مربع «السعر لجميع المقاسات» إلى الحقول المرسلة (رقم بصيغة نقطة عشرية)
        function syncVariablePriceToForm() {
            var raw = $('#variable_single_price').val();
            if (raw == null || raw === '') return;
            var num = parseFloat(String(raw).replace(/\s/g, '').replace(',', '.')) || 0;
            $('#single_dsp').val(num);
            $('#single_dsp_inc_tax').val(num);
            $('#variable_single_price').val(num); // يبقى الـ name يرسل نفس القيمة للـ backend
            if ($('#single_dpp').length && (!$('#single_dpp').val() || $('#single_dpp').val() === '0')) {
                $('#single_dpp').val(0);
                $('#single_dpp_inc_tax').val(0);
            }
        }
        $('#variable_single_price').on('input change blur', function() {
            syncVariablePriceToForm();
        });

        // عند اختيار التصنيف (نوع المنتج): إبقاء النوع فردي وإظهار قسم الأحجام/الألوان
        $('#category_id').change(function() {
            var catVal = $(this).val();
            if (catVal && catVal !== '') {
                $('#type').val('single').trigger('change');
                $('#size_color_combo_section').show();
            }
        });
        
        // زر إضافة اللون
        $('#add_color_btn').click(function() {
            var colorName = $('#new_color').val().trim();
            
            if (!colorName) {
                toastr.error('الرجاء إدخال اسم اللون');
                return;
            }
            
            // التحقق إذا كان اللون موجوداً بالفعل
            if (allColors.includes(colorName)) {
                toastr.warning('اللون "' + colorName + '" موجود بالفعل');
                return;
            }
            
            // إضافة اللون للمصفوفة
            allColors.push(colorName);
            
            // إنشاء جدول للون
            createColorTable(colorName);
            
            // مسح الحقل
            $('#new_color').val('');
            
            // تحديث الملخص
            updateSummary();
            
            toastr.success('تم إضافة اللون: ' + colorName);
        });
        
        // زر إضافة المقاسات
        $('#add_size_btn').click(function() {
            var sizesInput = $('#new_size').val().trim();
            
            if (!sizesInput) {
                toastr.error('الرجاء إدخال الأحجام');
                return;
            }
            
            // تحليل المقاسات المدخلة
            var newSizes = sizesInput.split(',').map(s => s.trim()).filter(s => s !== '');
            
            // إضافة للمقاسات العامة (فريدة)
            newSizes.forEach(function(size) {
                if (!allSizes.includes(size)) {
                    allSizes.push(size);
                }
            });
            
            // إضافة المقاسات لجميع جداول الألوان
            allColors.forEach(function(color) {
                addSizesToColorTable(color, newSizes);
            });
            
            // الإبقاء على النص في الحقل لاستخدامه مع ألوان أخرى
            updateSummary();
            
            toastr.success('تم إضافة ' + newSizes.length + ' حجم');
        });
        
        // دعم زر Enter لحقل اللون
        $('#new_color').keypress(function(e) {
            if (e.which == 13) {
                $('#add_color_btn').click();
                e.preventDefault();
            }
        });
        
        // دعم زر Enter لحقل المقاسات
        $('#new_size').keypress(function(e) {
            if (e.which == 13) {
                $('#add_size_btn').click();
                e.preventDefault();
            }
        });
        
        // خيارات الألوان الجاهزة: النقر يملأ الحقل ويضيف اللون
        $(document).on('click', '.preset-color-option', function() {
            var color = $(this).data('color');
            if (color) {
                $('#new_color').val(color);
                $('#add_color_btn').click();
            }
        });
        
        // خيارات المقاسات الجاهزة: النقر يملأ الحقل ويضيف المقاس
        $(document).on('click', '.preset-size-option', function() {
            var size = $(this).data('size');
            if (size) {
                $('#new_size').val(size);
                $('#add_size_btn').click();
            }
        });
        
        // دالة إنشاء جدول اللون
        function createColorTable(colorName) {
            var tableId = 'color_table_' + colorName.replace(/\s+/g, '_');
            
            var tableHTML = `
            <div class="color-table-container panel panel-default mt-3" id="${tableId}">
                <div class="panel-heading">
                    <h4 class="panel-title">
                        <i class="fa fa-tag"></i> ${colorName}
                        <button type="button" class="btn btn-xs btn-danger pull-right remove-color-btn" data-color="${colorName}">
                            <i class="fa fa-times"></i> حذف اللون
                        </button>
                    </h4>
                </div>
                <div class="panel-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-condensed color-size-table">
                            <thead>
                                <tr>
                                    <th style="width: 100px;">الحجم</th>
                                    <th>الكمية</th>
                                    <th style="width: 50px;"></th>
                                </tr>
                            </thead>
                            <tbody id="table_body_${colorName.replace(/\s+/g, '_')}">
                                <!-- سيتم إضافة الصفوف هنا -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            `;
            
            $('#color_tables_container').append(tableHTML);
            
            // تخزين المرجع
            colorTables[colorName] = {
                sizes: [],
                quantities: {}
            };
        }
        
        // دالة إضافة المقاسات لجدول اللون
        function addSizesToColorTable(colorName, sizes) {
            var tableBodyId = '#table_body_' + colorName.replace(/\s+/g, '_');
            
            sizes.forEach(function(size) {
                // التحقق إذا كان المقاس موجوداً بالفعل في جدول هذا اللون
                if (colorTables[colorName] && colorTables[colorName].sizes.includes(size)) {
                    return;
                }
                
                // إضافة لصفوف المقاسات
                if (!colorTables[colorName]) {
                    colorTables[colorName] = { sizes: [], quantities: {} };
                }
                colorTables[colorName].sizes.push(size);
                
                // إضافة صف للجدول
                var rowHTML = `
                <tr data-size="${size}">
                    <td style="vertical-align: middle;">
                        <strong>${size}</strong>
                    </td>
                    <td>
                        <input type="number" 
                               class="form-control input-sm size-qty-input" 
                               name="size_color_qty[${colorName}][${size}]"
                               data-color="${colorName}"
                               data-size="${size}"
                               placeholder="0"
                               value=""
                               min="0"
                               style="min-width: 100px;">
                    </td>
                    <td style="vertical-align: middle;">
                        <button type="button" class="btn btn-xs btn-danger remove-size-btn" 
                                data-color="${colorName}" 
                                data-size="${size}">
                            <i class="fa fa-times"></i>
                        </button>
                    </td>
                </tr>
                `;
                
                $(tableBodyId).append(rowHTML);
            });
        }
        
        // حذف المقاس من جدول اللون
        $(document).on('click', '.remove-size-btn', function() {
            var colorName = $(this).data('color');
            var sizeName = $(this).data('size');
            
            if (confirm(`هل تريد حذف الحجم ${sizeName} من اللون ${colorName}؟`)) {
                // حذف من جدول اللون
                if (colorTables[colorName]) {
                    colorTables[colorName].sizes = colorTables[colorName].sizes.filter(s => s !== sizeName);
                    delete colorTables[colorName].quantities[sizeName];
                }
                
                // حذف الصف
                $(this).closest('tr').remove();
                
                toastr.info('تم حذف الحجم ' + sizeName);
            }
        });
        
        // حذف جدول اللون
        $(document).on('click', '.remove-color-btn', function() {
            var colorName = $(this).data('color');
            
            if (confirm(`هل تريد حذف اللون ${colorName} وجميع أحجامه؟`)) {
                // حذف من المصفوفات
                allColors = allColors.filter(c => c !== colorName);
                delete colorTables[colorName];
                
                // حذف الجدول
                $(this).closest('.color-table-container').remove();
                
                // تحديث الملخص
                updateSummary();
                
                toastr.info('تم حذف اللون ' + colorName);
            }
        });
        
        // تغيير الكمية
        $(document).on('input', '.size-qty-input', function() {
            var colorName = $(this).data('color');
            var sizeName = $(this).data('size');
            var quantity = $(this).val();
            
            // تخزين الكمية
            if (!colorTables[colorName]) {
                colorTables[colorName] = { sizes: [], quantities: {} };
            }
            colorTables[colorName].quantities[sizeName] = quantity;
            
            // تحديث الملخص
            updateSummary();
        });
        
        // Tab: الانتقال لحقل الكمية التالي / السابق في قسم الألوان والمقاسات
        $(document).on('keydown', '#color_tables_container .size-qty-input', function(e) {
            if (e.which !== 9) return;
            var inputs = $('#color_tables_container .size-qty-input').toArray();
            var idx = inputs.indexOf(this);
            if (idx === -1) return;
            if (e.shiftKey) {
                if (idx > 0) {
                    e.preventDefault();
                    inputs[idx - 1].focus();
                }
            } else {
                if (idx < inputs.length - 1) {
                    e.preventDefault();
                    inputs[idx + 1].focus();
                }
            }
        });
        
        // دالة تحديث الملخص
        function updateSummary() {
            if (allColors.length === 0) {
                $('#summary_box').hide();
                return;
            }
            
            var totalCombinations = 0;
            var totalQuantity = 0;
            
            allColors.forEach(function(color) {
                if (colorTables[color] && colorTables[color].sizes) {
                    totalCombinations += colorTables[color].sizes.length;
                    
                    // حساب إجمالي الكمية
                    colorTables[color].sizes.forEach(function(size) {
                        var qty = parseInt(colorTables[color].quantities[size]) || 0;
                        totalQuantity += qty;
                    });
                }
            });
            
            var summaryText = `
            <span class="badge bg-primary">${allColors.length} ألوان</span>
            <span class="badge bg-success">${allSizes.length} أحجام</span>
            <span class="badge bg-warning">${totalCombinations} تركيبة</span>
            <span class="badge bg-info">${totalQuantity} قطعة</span>
            `;
            
            $('#summary_text').html(summaryText);
            $('#summary_box').show();
        }
        
        // دالة مسح النموذج
        function clearSizeColorForm() {
            allColors = [];
            allSizes = [];
            colorTables = {};
            $('#color_tables_container').html('');
            $('#summary_box').hide();
        }
        
        // قبل إرسال النموذج: نسخ سعر التباين إلى الحقول المرسلة (ليصل للـ Backend)
        $('form#product_add_form').on('submit', function(e) {
            if ($('#type').val() === 'variable' && $('#variable_single_price').val()) {
                syncVariablePriceToForm();
            }
            // عند «حفظ وطباعة»: إرسال عبر AJAX ثم فتح صفحة الطباعة في iframe حتى يُرسل الأمر للطابعة
            if ($('#submit_type').val() === 'submit_n_print') {
                e.preventDefault();
                if (!$('form#product_add_form').valid()) return false;
                var form = $('form#product_add_form');
                var btn = form.find('button[type="submit"][value="submit_n_print"]');
                if (btn.length) btn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> جاري الحفظ...');
                $.ajax({
                    url: form.attr('action'),
                    type: form.attr('method'),
                    data: new FormData(form[0]),
                    processData: false,
                    contentType: false,
                    success: function(data) {
                        if (data.success && data.print_url) {
                            if (typeof window.onbeforeunload === 'function') window.onbeforeunload = null;
                            var iframe = document.createElement('iframe');
                            iframe.setAttribute('style', 'position:absolute;left:-9999px;top:0;width:1px;height:1px;visibility:hidden;border:0');
                            document.body.appendChild(iframe);
                            iframe.src = data.print_url;
                            setTimeout(function() {
                                try { if (iframe.parentNode) iframe.parentNode.removeChild(iframe); } catch (err) {}
                                if (data.redirect_url) window.location.href = data.redirect_url;
                            }, 8000);
                        } else if (data.redirect_url) {
                            window.location.href = data.redirect_url;
                        }
                        if (btn.length) btn.prop('disabled', false).html('🖨️ حفظ وطباعة');
                    },
                    error: function(xhr) {
                        if (btn.length) btn.prop('disabled', false).html('🖨️ حفظ وطباعة');
                        var msg = (xhr.responseJSON && xhr.responseJSON.msg) ? xhr.responseJSON.msg : 'حدث خطأ أثناء الحفظ';
                        toastr.error(msg);
                    }
                });
                return false;
            }
        });

        // التحقق من البيانات قبل الحفظ
        $(document).on('click', '.submit_product_form', function(e) {
            var submit_type = $(this).attr('value');
            $('#submit_type').val(submit_type);
            
            // فردي مع أحجام/ألوان أو متباين: التحقق من الفروع والكميات
            var isSingleWithSizes = ($('#type').val() === 'single' && allColors.length > 0);
            if ($('#type').val() === 'variable' || isSingleWithSizes) {
                var locVal = $('#product_locations').val();
                var hasLocations = (Array.isArray(locVal) && locVal.length > 0) || (locVal && String(locVal).length > 0);
                if (!hasLocations) {
                    toastr.error('الرجاء اختيار فرع نشاط واحد على الأقل (فروع النشاط)');
                    e.preventDefault();
                    return false;
                }
                // التحقق من وجود بيانات
                var hasData = false;
                var totalQty = 0;
                
                $('.size-qty-input').each(function() {
                    var qty = parseInt($(this).val()) || 0;
                    if (qty > 0) {
                        hasData = true;
                        totalQty += qty;
                    }
                });
                
                if (!hasData) {
                    toastr.error('الرجاء إدخال الكميات للألوان والمقاسات');
                    e.preventDefault();
                    return false;
                }
                
                if (totalQty === 0) {
                    toastr.error('الرجاء إدخال كمية أكبر من الصفر لواحدة على الأقل من التركيبات');
                    e.preventDefault();
                    return false;
                }
                
                // التحقق من وجود ألوان ومقاسات
                if (allColors.length === 0) {
                    toastr.error('الرجاء إضافة لون واحد على الأقل');
                    e.preventDefault();
                    return false;
                }
                
                if (allSizes.length === 0) {
                    toastr.error('الرجاء إضافة مقاس واحد على الأقل');
                    e.preventDefault();
                    return false;
                }
                // سعر الفردي مع أحجام من جدول السعر (single_dsp)
                var priceVal = $('#single_dsp').val() || $('#single_dsp_inc_tax').val();
                if (!priceVal || priceVal.toString().trim() === '') {
                    toastr.error('الرجاء إدخال السعر (جدول السعر أعلاه)');
                    e.preventDefault();
                    return false;
                }
            }
            
            // إذا كان النموذج صالحاً، الاستمرار
            if ($('form#product_add_form').valid()) {
                return true;
            }
            
            e.preventDefault();
            return false;
        });
        
    });
</script>
@endsection