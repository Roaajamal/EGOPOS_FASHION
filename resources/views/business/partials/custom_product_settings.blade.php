<div class="pos-tab-content" id="custom_product_settings_tab">
    {{-- المجموعة الأولى: إعدادات الهوية والنشاط --}}
    <div class="row">
        <div class="col-md-12">
            <h4 class="text-primary"><i class="fa fa-id-card"></i> 1. إعدادات الهوية والتصنيف</h4>
            <hr>
        </div>
        <div class="col-sm-4">
            <div class="form-group">
                {!! Form::label('custom_product_settings[sku_prefix]', __('business.sku_prefix') . ':') !!}
                {!! Form::text('custom_product_settings[sku_prefix]', $business->custom_product_settings['sku_prefix'] ?? $business->sku_prefix, ['class' => 'form-control text-uppercase', 'placeholder' => 'ABC-']); !!}
            </div>
        </div>
        <div class="col-sm-4">
            <div class="form-group">
                <div class="checkbox">
                  <label>
                    {!! Form::hidden('custom_product_settings[enable_brand]', 0); !!}
                    {!! Form::checkbox('custom_product_settings[enable_brand]', 1, !empty($business->custom_product_settings['enable_brand']), [ 'class' => 'input-icheck']); !!} {{ __( 'lang_v1.enable_brand' ) }}
                  </label>
                </div>
            </div>
        </div>
        <div class="col-sm-4">
            <div class="form-group">
                <div class="checkbox">
                  <label>
                    {!! Form::hidden('custom_product_settings[enable_category]', 0); !!}
                    {!! Form::checkbox('custom_product_settings[enable_category]', 1, !empty($business->custom_product_settings['enable_category']), [ 'class' => 'input-icheck']); !!} {{ __( 'lang_v1.enable_category' ) }}
                  </label>
                </div>
            </div>
        </div>
        <div class="col-sm-4">
            <div class="form-group">
                <div class="checkbox">
                  <label>
                    {!! Form::hidden('custom_product_settings[enable_sub_category]', 0); !!}
                    {!! Form::checkbox('custom_product_settings[enable_sub_category]', 1, !empty($business->custom_product_settings['enable_sub_category']), [ 'class' => 'input-icheck']); !!} {{ __( 'lang_v1.enable_sub_category' ) }}
                  </label>
                </div>
            </div>
        </div>
        <div class="col-sm-4">
            <div class="form-group">
                <div class="checkbox">
                  <label>
                    {!! Form::hidden('custom_product_settings[enable_price_tax]', 0); !!}
                    {!! Form::checkbox('custom_product_settings[enable_price_tax]', 1, !empty($business->custom_product_settings['enable_price_tax']), [ 'class' => 'input-icheck']); !!} {{ __( 'lang_v1.enable_price_tax' ) }}
                  </label>
                </div>
            </div>
        </div>
    </div>

    {{-- المجموعة الثانية: التحكم في ظهور الحقول (إظهار/إخفاء) --}}
    <div class="row" style="margin-top: 30px;">
        <div class="col-md-12">
            <h4 class="text-primary"><i class="fa fa-eye"></i> 2. التحكم في ظهور الحقول بصفحة إضافة المنتج</h4>
            <hr>
        </div>
        @php
            $show_fields = [
                'show_product_sku' => 'عرض حقل SKU',
                'show_product_barcode_type' => 'عرض نوع الباركود',
                'show_product_description' => 'عرض وصف المنتج',
                'show_product_barcode_types'=> 'تفعيل الرقم التسلسلي',
                'show_not_for_selling' => 'عرض خيار «غير معروض للبيع»',
                'show_alert_quantity' => 'عرض حد التنبيه (كمية التنبيه)',
                'enable_product_size_color' => 'تفعيل خيارات الأحجام والألوان',
                'show_product_weights' => 'عرض الوزن'
            ];
        @endphp
        @foreach($show_fields as $key => $label)
        <div class="col-sm-4">
            <div class="form-group">
                <div class="checkbox">
                    <label>
                        <input type="hidden" name="custom_product_settings[{{ $key }}]" value="0">
                        {!! Form::checkbox('custom_product_settings[' . $key . ']', 1, !empty($business->custom_product_settings[$key]), ['class' => 'input-icheck']); !!} {{ $label }}
                    </label>
                </div>
            </div>
        </div>
        @endforeach

        <div class="col-sm-4">
            <div class="form-group">
                <div class="checkbox">
                  <label>
                    {!! Form::hidden('custom_product_settings[enable_racks]', 0); !!}
                    {!! Form::checkbox('custom_product_settings[enable_racks]', 1, !empty($business->custom_product_settings['enable_racks']), [ 'class' => 'input-icheck']); !!} 
                    {{ __( 'lang_v1.enable_racks' ) }}
                  </label>
                  @show_tooltip(__('lang_v1.tooltip_enable_racks'))
                </div>
            </div>
        </div>
        <div class="col-sm-4">
            <div class="form-group">
                <div class="checkbox">
                  <label>
                    {!! Form::hidden('custom_product_settings[show_product_image]', 0); !!}
                    {!! Form::checkbox('custom_product_settings[show_product_image]', 1, !empty($business->custom_product_settings['show_product_image']), [ 'class' => 'input-icheck']); !!} 
                    إظهار حقل صورة المنتج
                  </label>
                </div>
            </div>
        </div>
        <div class="col-sm-4">
            <div class="form-group">
                <div class="checkbox">
                  <label>
                    {!! Form::hidden('custom_product_settings[enable_row]', 0); !!}
                    {!! Form::checkbox('custom_product_settings[enable_row]', 1, !empty($business->custom_product_settings['enable_row']), [ 'class' => 'input-icheck']); !!} 
                    {{ __( 'lang_v1.enable_row' ) }}
                  </label>
                </div>
            </div>
        </div>
    </div>

    {{-- المجموعة الثالثة: إدارة الصلاحية والقيم الافتراضية --}}
    <div class="row" style="margin-top: 30px;">
        <div class="col-md-12">
            <h4 class="text-primary"><i class="fa fa-sliders"></i> 3. القيم الافتراضية وتاريخ الانتهاء</h4>
            <hr>
        </div>
        <div class="col-sm-4">
            <div class="form-group">
                {!! Form::label('custom_product_settings[enable_product_expiry]', __( 'product.enable_product_expiry' ) . ':') !!}
                <div class="input-group">
                    <span class="input-group-addon">
                        {!! Form::hidden('custom_product_settings[enable_product_expiry]', 0); !!}
                        {!! Form::checkbox('custom_product_settings[enable_product_expiry]', 1, !empty($business->custom_product_settings['enable_product_expiry']) ); !!} 
                    </span>
                    {!! Form::select('custom_product_settings[expiry_type]', ['add_expiry' => __('lang_v1.add_expiry'), 'add_manufacturing' => __('lang_v1.add_manufacturing_auto_expiry')], $business->custom_product_settings['expiry_type'] ?? $business->expiry_type, ['class' => 'form-control']); !!}
                </div>
            </div>
        </div>

        <div class="col-sm-4">
            <div class="form-group">
                {!! Form::label('custom_product_settings[on_product_expiry]', __('lang_v1.on_product_expiry') . ':') !!}
                <div class="input-group">
                    {!! Form::select('custom_product_settings[on_product_expiry]', ['keep_selling'=>__('lang_v1.keep_selling'), 'stop_selling'=>__('lang_v1.stop_selling') ], $business->custom_product_settings['on_product_expiry'] ?? $business->on_product_expiry, ['class' => 'form-control']); !!}
                    <span class="input-group-addon">قبل:</span>
                    {!! Form::number('custom_product_settings[stop_selling_before]', $business->custom_product_settings['stop_selling_before'] ?? $business->stop_selling_before, ['class' => 'form-control', 'placeholder' => 'يوم']); !!}
                </div>
            </div>
        </div>

        <div class="col-sm-4">
            <div class="form-group">
                {!! Form::label('custom_product_settings[default_unit]', __('lang_v1.default_unit') . ':') !!}
                {!! Form::select('custom_product_settings[default_unit]', $units_dropdown, $business->custom_product_settings['default_unit'] ?? $business->default_unit, ['class' => 'form-control select2', 'style' => 'width: 100%;' ]); !!}
            </div>
        </div>

        <div class="col-sm-4">
            <div class="form-group">
                {!! Form::label('custom_product_settings[default_barcode_type]', __('product.barcode_type') . ' (الافتراضي):') !!}
                {!! Form::select('custom_product_settings[default_barcode_type]', 
                    $barcode_types, 
                    $business->custom_product_settings['default_barcode_type'] ?? ($barcode_default ?? null), 
                    ['class' => 'form-control select2', 'placeholder' => __('messages.please_select'), 'style' => 'width:100%']
                ); !!}
            </div>
        </div>

        <div class="col-sm-4">
            <div class="form-group">
                {!! Form::label('custom_product_settings[default_location_id]', __('business.business_locations') . ' (الافتراضي):') !!}
                {!! Form::select('custom_product_settings[default_location_id]', $business_locations, $business->custom_product_settings['default_location_id'] ?? null, ['class' => 'form-control select2', 'placeholder' => __('messages.please_select'), 'style' => 'width: 100%;']); !!}
                <small class="help-block">سيتم اختيار هذا الفرع تلقائياً عند إضافة منتج جديد</small>
            </div>
        </div>

        <div class="col-sm-4">
            <div class="form-group">
                {!! Form::label('custom_product_settings[default_product_type]', 'نوع المنتج الافتراضي:') !!}
                {!! Form::select('custom_product_settings[default_product_type]', ['single' => __('lang_v1.single'), 'variable' => __('lang_v1.variable'), 'combo' => __('lang_v1.combo')], $business->custom_product_settings['default_product_type'] ?? 'single', ['class' => 'form-control']); !!}
            </div>
        </div>

<div class="col-sm-4">

    <div class="form-group">

        {!! Form::label('custom_product_settings[default_tax_id]', __('product.applicable_tax') . ' (الافتراضي):') !!}

        {!! Form::select('custom_product_settings[default_tax_id]',

            // تأكد من تجربة المتغيرين هنا لضمان التوافق

            isset($taxes) ? $taxes : (isset($tax_rates) ? $tax_rates : []),

            $business->custom_product_settings['default_tax_id'] ?? null,

            ['class' => 'form-control select2', 'placeholder' => __('messages.please_select'), 'style' => 'width: 100%;']

        ); !!}

        <small class="help-block">الضريبة التي سيتم اختيارها تلقائياً عند إضافة منتج جديد</small>

    </div>

</div>

        <div class="col-sm-4">
            <div class="form-group">
                {!! Form::label('custom_product_settings[default_tax_type]', 'ضريبة البيع الافتراضية:') !!}
                {!! Form::select('custom_product_settings[default_tax_type]', ['inclusive' => __('product.inclusive'), 'exclusive' => __('product.exclusive')], $business->custom_product_settings['default_tax_type'] ?? 'exclusive', ['class' => 'form-control']); !!}
            </div>
        </div>

        <div class="col-sm-4">
            <div class="form-group">
                {!! Form::label('custom_product_settings[default_purchase_price_tax_type]', 'سعر الشراء الافتراضي يكون:') !!}
                {!! Form::select('custom_product_settings[default_purchase_price_tax_type]', ['exclusive' => 'قبل الضريبة', 'inclusive' => 'شامل الضريبة'], $business->custom_product_settings['default_purchase_price_tax_type'] ?? 'exclusive', ['class' => 'form-control']); !!}
            </div>
        </div>

        <div class="col-sm-4">
            <div class="form-group">
                {!! Form::label('custom_product_settings[default_enable_stock]', 'إدارة المخزون (افتراضي):') !!}
                {!! Form::select('custom_product_settings[default_enable_stock]', ['1' => __('lang_v1.yes'), '0' => __('lang_v1.no')], $business->custom_product_settings['default_enable_stock'] ?? '1', ['class' => 'form-control']); !!}
            </div>
        </div>

        <div class="col-sm-4">
            <div class="form-group">
                {!! Form::label('custom_product_settings[default_alert_quantity]', 'حد التنبيه الافتراضي:') !!}
                {!! Form::text('custom_product_settings[default_alert_quantity]', $business->custom_product_settings['default_alert_quantity'] ?? '0', ['class' => 'form-control input_number']); !!}
            </div>
        </div>
    </div>
     {{--  المجموعة الثانية: التحكم في ظهور الحقول (إظهار/إخفاء) في الاكسل--}}
    <div class="row" style="margin-top: 30px;">
        <div class="col-md-12">
            <h4 class="text-primary"><i class="fa fa-eye"></i> 2. التحكم في ظهور الحقول بملف الاكسل</h4>
            <hr>
        </div>
        @php
            $show_fields = [
                'show_product_type' => __('business.show_product_type'),
                'show_barcode_type' =>  __('business.show_barcode_type'),
                'show_opening_stock' => __('business.show_opening_stock'),
                'show_manage_stock' =>  __('business.show_manage_stock'),
                'show_profit_margin' => __('business.show_profit_margin'),
                'show_purchase_price' =>  __('business.show_purchase_price'),
                'show_enable_racks' =>  __('business.show_enable_racks'),
                'show_enable_rows' =>  __('business.show_enable_rows'),
                'show_purchase_price_exc' => __('business.show_purchase_price_exc')
            ];
        @endphp
        @foreach($show_fields as $key => $label)
        <div class="col-sm-4">
            <div class="form-group">
                <div class="checkbox">
                    <label>
                        <input type="hidden" name="custom_product_settings[{{ $key }}]" value="0">
                        {!! Form::checkbox('custom_product_settings[' . $key . ']', 1, !empty($business->custom_product_settings[$key]), ['class' => 'input-icheck']); !!} {{ $label }}
                    </label>
                </div>
            </div>
        </div>
        @endforeach
          <div class="col-sm-4">
    <div class="form-group">
        <div class="checkbox">
            <label>
                {{-- ✅ أضف hidden input --}}
                <input type="hidden" name="custom_product_settings[enable_single_product]" value="0">
                {!! Form::checkbox('custom_product_settings[enable_single_product]', 1, 
                    !empty($business->custom_product_settings['enable_single_product']),
                    ['class' => 'input-icheck']) !!}
                {{ __('business.enable_single_product') }}
            </label>
        </div>
    </div>
</div>



    </div>

    </div>
</div>

       
