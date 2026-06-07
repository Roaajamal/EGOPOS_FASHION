<div class="modal-dialog modal-lg" role="document">
  <div class="modal-content">
  @php
    $form_id = 'contact_add_form';
    if(isset($quick_add)){
      $form_id = 'quick_add_contact';
    }

    if(isset($store_action)) {
      $url = $store_action;
      $type = 'lead';
      $customer_groups = [];
    } else {
      $url = action([\App\Http\Controllers\ContactController::class, 'store']);
      $type = isset($selected_type) ? $selected_type : 'customer';
      $sources = [];
      $life_stages = [];
    }
  @endphp
    {!! Form::open(['url' => $url, 'method' => 'post', 'id' => $form_id ]) !!}

    <div class="modal-header">
      <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
      <h4 class="modal-title">@lang('contact.add_contact')</h4>
    </div>

    <div class="modal-body">
       
        <div class="hide">
            <!-- نوع جهة الاتصال: عميل -->
            <input type="hidden" name="type" value="customer">
            
            <!-- تحديد نوع العميل كـ "فردي" وهو الخيار المتوافق مع first_name -->
            <input type="radio" name="contact_type_radio" value="individual" checked="checked">
            <input type="hidden" name="contact_type" value="individual">
            
          
            <input type="hidden" name="last_name" value="">

            <!-- حقول النظام الإضافية المخفية -->
            {!! Form::text('contact_id', null, ['class' => 'form-control']); !!}
            {!! Form::select('customer_group_id', $customer_groups, '', ['class' => 'form-control']); !!}
            {!! Form::text('supplier_business_name', null, ['class' => 'form-control']); !!}
            {!! Form::text('prefix', null, ['class' => 'form-control']); !!}
            {!! Form::text('middle_name', null, ['class' => 'form-control']); !!}
            {!! Form::text('alternate_number', null, ['class' => 'form-control']); !!}
            {!! Form::text('landline', null, ['class' => 'form-control']); !!}
            {!! Form::email('email', null, ['class' => 'form-control']); !!}
        </div>

        <!-- 2. الحقول الظاهرة للكاشير فقط -->
        <div class="row">            
            <!-- حقل الاسم الأول والأخير مدمج ظاهرياً -->
            <div class="col-md-6">
                <div class="form-group">
                    {!! Form::label('first_name', __( 'business.first_name' ) . ':*') !!}
                    <div class="input-group">
                        <span class="input-group-addon">
                            <i class="fa fa-user"></i>
                        </span>
                        {!! Form::text('first_name', null, ['class' => 'form-control', 'required', 'placeholder' => __( 'business.first_name' ), 'id' => 'first_name' ]); !!}
                    </div>
                </div>
            </div>

            <!-- حقل رقم الهاتف -->
            <div class="col-md-6">
                <div class="form-group">
                    {!! Form::label('mobile', __('contact.mobile') . ':*') !!}
                    <div class="input-group">
                        <span class="input-group-addon">
                            <i class="fa fa-mobile"></i>
                        </span>
                        {!! Form::text('mobile', null, ['class' => 'form-control', 'required', 'placeholder' => __('contact.mobile'), 'id' => 'mobile']); !!}
                    </div>
                </div>
            </div>
        </div>

        
        <div class="hide">
            <button type="button" class="more_btn" data-target="#more_div"></button>
            <div id="more_div"></div>
            @include('layouts.partials.module_form_part')
        </div>
    </div>
    
    <div class="modal-footer">
      <button type="submit" class="tw-dw-btn tw-dw-btn-primary tw-text-white">@lang( 'messages.save' )</button>
      <button type="button" class="tw-dw-btn tw-dw-btn-neutral tw-text-white" data-dismiss="modal">@lang( 'messages.close' )</button>
    </div>

    {!! Form::close() !!}
  
  </div>
</div>