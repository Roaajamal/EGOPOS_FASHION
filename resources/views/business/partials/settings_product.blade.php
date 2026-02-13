<div class="pos-tab-content">
    <div class="row">
        <div class="col-sm-4">
            <div class="form-group">
                {!! Form::label('sku_prefix', __('business.sku_prefix') . ':') !!}
                 {!! Form::text('sku_prefix', $business->sku_prefix, ['class' => 'form-control text-uppercase']); !!}
            </div>
        </div>
        
        <div class="col-sm-4">
            {!! Form::label('enable_product_expiry', __( 'product.enable_product_expiry' ) . ':') !!}
            @show_tooltip(__('lang_v1.tooltip_enable_expiry'))

            <div class="input-group">
                <span class="input-group-addon">
                    {!! Form::checkbox('enable_product_expiry', 1, $business->enable_product_expiry ); !!} 
                </span>

                <select class="form-control" id="expiry_type"
                    name="expiry_type" 
                    @if(!$business->enable_product_expiry) disabled @endif>
                    <option value="add_expiry" @if($business->expiry_type == 'add_expiry') selected @endif>
                        {{__('lang_v1.add_expiry')}}
                    </option>
                  <option value="add_manufacturing" @if($business->expiry_type == 'add_manufacturing') selected @endif>{{__('lang_v1.add_manufacturing_auto_expiry')}}</option>
                </select>
            </div>
        </div>

        <div class="col-sm-4 @if(!$business->enable_product_expiry) hide @endif" id="on_expiry_div">
            <div class="form-group">
                <div class="multi-input">
                    {!! Form::label('on_product_expiry', __('lang_v1.on_product_expiry') . ':') !!}
                    @show_tooltip(__('lang_v1.tooltip_on_product_expiry'))
                    <br>

                    {!! Form::select('on_product_expiry',     ['keep_selling'=>__('lang_v1.keep_selling'), 'stop_selling'=>__('lang_v1.stop_selling') ], $business->on_product_expiry, ['class' => 'form-control pull-left', 'style' => 'width:60%;']); !!}

                    @php
                        $disabled = '';
                        if($business->on_product_expiry == 'keep_selling'){
                            $disabled = 'disabled';
                        }
                    @endphp

                    {!! Form::number('stop_selling_before', $business->stop_selling_before, ['class' => 'form-control pull-left', 'placeholder' => 'stop n days before', 'style' => 'width:40%;', $disabled, 'required', 'id' => 'stop_selling_before']); !!}
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-sm-4">
            <div class="form-group">
                <div class="checkbox">
                  <label>
                    {!! Form::checkbox('enable_brand', 1, $business->enable_brand, 
                    [ 'class' => 'input-icheck']); !!} {{ __( 'lang_v1.enable_brand' ) }}
                  </label>
                </div>
            </div>
        </div>

        <div class="col-sm-4">
            <div class="form-group">
                <div class="checkbox">
                  <label>
                    {!! Form::checkbox('enable_category', 1, $business->enable_category, [ 'class' => 'input-icheck', 'id' => 'enable_category']); !!} {{ __( 'lang_v1.enable_category' ) }}
                  </label>
                </div>
            </div>
        </div>

        <div class="col-sm-4 enable_sub_category @if($business->enable_category != 1) hide @endif">
            <div class="form-group">
                <div class="checkbox">
                  <label>
                    {!! Form::checkbox('enable_sub_category', 1, $business->enable_sub_category, [ 'class' => 'input-icheck', 'id' => 'enable_sub_category']); !!} {{ __( 'lang_v1.enable_sub_category' ) }}
                  </label>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-sm-4">
            <div class="form-group">
                <div class="checkbox">
                  <label>
                    {!! Form::checkbox('enable_price_tax', 1, $business->enable_price_tax, [ 'class' => 'input-icheck']); !!} {{ __( 'lang_v1.enable_price_tax' ) }}
                  </label>
                </div>
            </div>
        </div>

        <div class="col-sm-4">
            <div class="form-group">
                {!! Form::label('default_unit', __('lang_v1.default_unit') . ':') !!}
                <div class="input-group">
                    <span class="input-group-addon">
                        <i class="fa fa-balance-scale"></i>
                    </span>
                    {!! Form::select('default_unit', $units_dropdown, $business->default_unit, ['class' => 'form-control select2', 'style' => 'width: 100%;' ]); !!}
                </div>
            </div>
        </div>

        <div class="col-sm-4">
            <div class="form-group">
                <div class="checkbox">
                  <label>
                    {!! Form::checkbox('enable_sub_units', 1, $business->enable_sub_units, [ 'class' => 'input-icheck']); !!} {{ __( 'lang_v1.enable_sub_units' ) }}
                  </label>
                  @show_tooltip(__('lang_v1.sub_units_tooltip'))
                </div>
            </div>
        </div>

        <div class="clearfix"></div>

        <div class="col-sm-4">
            <div class="form-group">
                <div class="checkbox">
                  <label>
                    {!! Form::checkbox('enable_racks', 1, $business->enable_racks, [ 'class' => 'input-icheck']); !!} {{ __( 'lang_v1.enable_racks' ) }}
                  </label>
                  @show_tooltip(__('lang_v1.tooltip_enable_racks'))
                </div>
            </div>
        </div>

        <div class="col-sm-4">
            <div class="form-group">
                <div class="checkbox">
                  <label>
                    {!! Form::checkbox('enable_row', 1, $business->enable_row, [ 'class' => 'input-icheck']); !!} {{ __( 'lang_v1.enable_row' ) }}
                  </label>
                </div>
            </div>
        </div>

        <div class="col-sm-4">
            <div class="form-group">
                <div class="checkbox">
                  <label>
                    {!! Form::checkbox('enable_position', 1, $business->enable_position, [ 'class' => 'input-icheck']); !!} {{ __( 'lang_v1.enable_position' ) }}
                  </label>
                </div>
            </div>
        </div>
        <div class="clearfix"></div>
        <div class="col-sm-4">
            <div class="form-group">
                <div class="checkbox">
                  <label>
                    {!! Form::checkbox('common_settings[enable_product_warranty]', 1, !empty($common_settings['enable_product_warranty']) ? true : false, 
                    [ 'class' => 'input-icheck']); !!} {{ __( 'lang_v1.enable_product_warranty' ) }}
                  </label>
                </div>
            </div>
        </div>

        <div class="col-sm-4 @if(config('constants.enable_secondary_unit') == false) hide @endif">
            <div class="form-group">
                <div class="checkbox">
                  <label>
                    {!! Form::checkbox('common_settings[enable_secondary_unit]', 1, !empty($common_settings['enable_secondary_unit']) ? true : false, 
                    [ 'class' => 'input-icheck']); !!} {{ __( 'lang_v1.enable_secondary_unit' ) }}
                  </label>
                </div>
            </div>
        </div>

        <div class="col-sm-4">
            <div class="form-group">
                <div class="checkbox">
                  <label>
                    {!! Form::checkbox('common_settings[is_product_image_required]', 1, 
                        !empty($common_settings['is_product_image_required']) ? true : false, 
                    [ 'class' => 'input-icheck']); !!} {{ __( 'lang_v1.is_product_image_required' ) }}
                  </label>
                </div>
            </div>
        </div>

        <div class="col-sm-4">
            <div class="form-group">
                <div class="checkbox">
                  <label>
                    <input type="hidden" name="common_settings[enable_product_size_color]" value="0">
                    {!! Form::checkbox('common_settings[enable_product_size_color]', 1, !isset($common_settings['enable_product_size_color']) || !empty($common_settings['enable_product_size_color']), [ 'class' => 'input-icheck']); !!} تفعيل الأحجام والألوان
                  </label>
                  <small class="help-block text-muted">عند التفعيل تظهر في إضافة منتج (فردي) إمكانية إضافة ألوان وأحجام وكميات ورصيد افتتاحي</small>
                </div>
            </div>
        </div>

    </div>

    {{-- إظهار/إخفاء حقول صفحة إضافة المنتج --}}
    <div class="row" style="margin-top: 24px;">
        <div class="col-sm-12">
            <h4 style="margin-bottom: 16px;">{{ __('business.product') }} — إظهار حقول صفحة إضافة المنتج</h4>
           
        </div>
    </div>
    <div class="row">
        @php
            $show_fields = ['show_product_sku', 'show_product_barcode_type', 'show_product_locations', 'show_product_description', 'show_product_brochure', 'show_product_weight', 'show_preparation_time', 'show_enable_sr_no', 'show_not_for_selling', 'show_alert_quantity'];
            $show_labels = ['عرض حقل SKU', 'عرض نوع الباركود', 'عرض فروع المنتج', 'عرض وصف المنتج', 'عرض كتيب المنتج (ملف)', 'عرض الوزن', 'عرض وقت التحضير', 'عرض تفعيل الرقم التسلسلي', 'عرض «غير معروض للبيع»', 'عرض حد التنبيه (كمية التنبيه)'];
        @endphp
        @foreach($show_fields as $idx => $key)
        <div class="col-sm-4">
            <div class="form-group">
                <div class="checkbox">
                    <label>
                        <input type="hidden" name="common_settings[{{ $key }}]" value="0">
                        {!! Form::checkbox('common_settings[' . $key . ']', 1, !isset($common_settings[$key]) || $common_settings[$key], ['class' => 'input-icheck']); !!} {{ $show_labels[$idx] }}
                    </label>
                </div>
            </div>
        </div>
        @endforeach
    </div>

    {{-- قيم افتراضية عند إضافة منتج جديد --}}
    <div class="row" style="margin-top: 24px;">
        <div class="col-sm-12">
            <h4 style="margin-bottom: 16px;">قيم افتراضية لصفحة إضافة المنتج</h4>
           
        </div>
    </div>
    <div class="row">
        <div class="col-sm-4">
            <div class="form-group">
                {!! Form::label('common_settings[default_product_type]', 'نوع المنتج الافتراضي:') !!}
                {!! Form::select('common_settings[default_product_type]', ['single' => __('lang_v1.single'), 'variable' => __('lang_v1.variable'), 'combo' => __('lang_v1.combo')], $common_settings['default_product_type'] ?? 'single', ['class' => 'form-control']); !!}
            </div>
        </div>
        <div class="col-sm-4">
            <div class="form-group">
                {!! Form::label('common_settings[default_tax_id]', __('product.applicable_tax') . ' (افتراضي):') !!}
                {!! Form::select('common_settings[default_tax_id]', $tax_rates ?? [], $common_settings['default_tax_id'] ?? null, ['class' => 'form-control select2', 'placeholder' => __('messages.please_select')]); !!}
            </div>
        </div>
        <div class="col-sm-4">
            <div class="form-group">
                {!! Form::label('common_settings[default_tax_type]', __('product.selling_price_tax_type') . ' (افتراضي):') !!}
                {!! Form::select('common_settings[default_tax_type]', ['inclusive' => __('product.inclusive'), 'exclusive' => __('product.exclusive')], $common_settings['default_tax_type'] ?? 'exclusive', ['class' => 'form-control']); !!}
            </div>
        </div>
        <div class="col-sm-4">
            <div class="form-group">
                {!! Form::label('common_settings[default_category_id]', __('product.category') . ' (افتراضي):') !!}
                {!! Form::select('common_settings[default_category_id]', $categories ?? [], $common_settings['default_category_id'] ?? null, ['class' => 'form-control select2', 'placeholder' => __('messages.please_select')]); !!}
            </div>
        </div>
        <div class="col-sm-4">
            <div class="form-group">
                {!! Form::label('common_settings[default_brand_id]', __('product.brand') . ' (افتراضي):') !!}
                {!! Form::select('common_settings[default_brand_id]', $brands ?? [], $common_settings['default_brand_id'] ?? null, ['class' => 'form-control select2', 'placeholder' => __('messages.please_select')]); !!}
            </div>
        </div>
        <div class="col-sm-4">
            <div class="form-group">
                {!! Form::label('common_settings[default_barcode_type]', __('product.barcode_type') . ' (افتراضي):') !!}
                {!! Form::select('common_settings[default_barcode_type]', $barcode_types ?? [], $common_settings['default_barcode_type'] ?? ($barcode_default ?? null), ['class' => 'form-control select2', 'placeholder' => __('messages.please_select')]); !!}
            </div>
        </div>
        <div class="col-sm-4">
            <div class="form-group">
                {!! Form::label('common_settings[default_alert_quantity]', __('product.alert_quantity') . ' (افتراضي):') !!}
                {!! Form::text('common_settings[default_alert_quantity]', $common_settings['default_alert_quantity'] ?? '0', ['class' => 'form-control input_number', 'placeholder' => '0']); !!}
            </div>
        </div>
        <div class="col-sm-4">
            <div class="form-group">
                {!! Form::label('common_settings[default_purchase_price]', 'سعر الشراء الافتراضي:') !!}
                @show_tooltip('يُستخدم كقيمة أولية لحقل سعر الشراء عند إضافة منتج جديد. حدد أدناه هل القيمة شاملة أم قبل الضريبة.')
                {!! Form::text('common_settings[default_purchase_price]', $common_settings['default_purchase_price'] ?? '', ['class' => 'form-control input_number', 'placeholder' => '0.00', 'step' => '0.01']); !!}
            </div>
        </div>
        <div class="col-sm-4">
            <div class="form-group">
                {!! Form::label('common_settings[default_purchase_price_tax_type]', 'نوع سعر الشراء الافتراضي:') !!}
                {!! Form::select('common_settings[default_purchase_price_tax_type]', ['exclusive' => __('product.exc_of_tax') . ' (قبل الضريبة)', 'inclusive' => __('product.inc_of_tax') . ' (شامل الضريبة)'], $common_settings['default_purchase_price_tax_type'] ?? 'exclusive', ['class' => 'form-control']); !!}
                <small class="help-block">إن اخترت «شامل الضريبة» يُحسب سعر ما قبل الضريبة تلقائياً حسب ضريبة المنتج الافتراضية.</small>
            </div>
        </div>
        <div class="col-sm-4">
            <div class="form-group">
                {!! Form::label('common_settings[default_enable_stock]', 'إدارة المخزون (افتراضي):') !!}
                {!! Form::select('common_settings[default_enable_stock]', ['1' => __('lang_v1.yes'), '0' => __('lang_v1.no')], $common_settings['default_enable_stock'] ?? '1', ['class' => 'form-control']); !!}
            </div>
        </div>
        <div class="col-sm-4">
            <div class="form-group">
                {!! Form::label('common_settings[default_product_locations]', __('business.business_locations') . ' (افتراضي):') !!}
                @show_tooltip('يُختار تلقائياً عند إضافة منتج جديد إن لم يُحدد غيره.')
                {!! Form::select('common_settings[default_product_locations][]', $business_locations ?? [], $common_settings['default_product_locations'] ?? [], ['class' => 'form-control select2', 'multiple', 'placeholder' => __('messages.please_select')]); !!}
                <small class="help-block">فروع النشاط الافتراضية للمنتج</small>
            </div>
        </div>
    </div>
</div>