<div class="modal-dialog modal-lg" role="document">
  <div class="modal-content">

    {!! Form::open(['url' => action([\Modules\Accounting\Http\Controllers\CoaController::class, 'store']), 'method' => 'post', 'id' => 'create_client_form' ]) !!}

    <div class="modal-header">
      <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
      <h4 class="modal-title">@lang( 'accounting::lang.add_account' )</h4>
    </div>

    <div class="modal-body">
        <div class="row">
            <div class="col-md-12">
                <div class="form-group">
                    {!! Form::label('account_primary_type', __( 'accounting::lang.account_type' ) . ':*') !!}
                    <select class="form-control" name="account_primary_type" id="account_primary_type" required>
                        <option value="">@lang('messages.please_select')</option>
                        @foreach($account_types as $account_type => $account_details)
                            <option value="{{$account_type}}">{{__('accounting::lang.' .$account_type)}}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group">
                    {!! Form::label('account_sub_type', __( 'accounting::lang.account_sub_type' ) . ':*') !!}
                    <select class="form-control" name="account_sub_type_id" id="account_sub_type" required>
                        <option value="">@lang('messages.please_select')</option>
                    </select>
                </div>
                <div class="form-group">
                    {!! Form::label('detail_type', __( 'accounting::lang.detail_type' ) . ':*') !!}
                    {!! Form::select('detail_type_id', [], null,  ['class' => 'form-control', 
                        'required', 'placeholder' => __('messages.please_select'), 'id' => 'detail_type' ]); !!}
                    <p class="help-block" id="detail_type_desc"></p>
                </div>
                <div class="form-group">
                    {!! Form::label('name', __( 'user.name' ) . ':*') !!}
                    {!! Form::text('name', null, ['class' => 'form-control', 'required', 'placeholder' => __( 'user.name' ) ]); !!}
                </div>
                <div class="form-group">
                    {!! Form::label('gl_code', __( 'accounting::lang.gl_code' ) . ':') !!}
                    {!! Form::text('gl_code', null, ['class' => 'form-control', 'placeholder' => __( 'accounting::lang.gl_code' ) ]); !!}
                </div>
            </div>
        </div>

        <div id="opening_balance_section" style="display: none; border: 1px solid #ddd; padding: 15px; margin-top: 15px; background: #f9f9f9;">
            <h4 style="margin-top: 0;">الرصيد الافتتاحي</h4>
            <hr>
            <div class="row" id="balance_option_div" style="display: none;">
                <div class="col-md-12">
                    <label class="radio-inline">
                        <input type="radio" name="balance_type" value="general" checked id="radio_general"> حساب عام
                    </label>
                    <label class="radio-inline">
                        <input type="radio" name="balance_type" value="distributed" id="radio_distributed"> توزيع على الفروع
                    </label>
                </div>
            </div>

            <div class="row" id="single_balance_div">
                <div class="col-md-6">
                    <div class="form-group">
                        {!! Form::label('balance', __( 'lang_v1.balance' ) . ':') !!}
                        {!! Form::text('balance', null, ['class' => 'form-control input_number', 'id' => 'main_balance_field']); !!}
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        {!! Form::label('as_of', __( 'accounting::lang.as_of' ) . ':') !!}
                        <input type="text" name="balance_as_of" id="balance_as_of" class="form-control date-picker-new">
                    </div>
                </div>
            </div>

            <div class="row" id="distributed_balance_div" style="display: none;">
                <div class="col-md-12">
                    <table class="table table-condensed">
                        <thead>
                            <tr class="bg-gray">
                                <th>الفرع</th>
                                <th>الرصيد الافتتاحي</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($business_locations as $id => $name)
                            <tr>
                                <td>{{$name}}</td>
                                <td>
                                    <input type="text" name="location_balance[{{$id}}]" class="form-control input_number" placeholder="0.00">
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-12">
                <div class="form-group">
                    {!! Form::label('description', __( 'lang_v1.description' ) . ':') !!}
                    {!! Form::textarea('description', null, ['class' => 'form-control', 'rows' => 3 ]); !!}
                </div>
            </div>
        </div> 
    </div>

    <div class="modal-footer">
      <button type="submit" class="btn btn-primary">@lang( 'messages.save' )</button>
      <button type="button" class="btn btn-default" data-dismiss="modal">@lang( 'messages.close' )</button>
    </div>

    {!! Form::close() !!}
  </div>
</div>

<script>
$(document).ready(function(){
    // تحويل مصفوفة الـ PHP إلى مصفوفة جافاسكريبت
    var allowedBalanceIds = @json($allowed_balances ?? []);

    // دالة فحص الرصيد وإظهاره
    function checkBalanceVisibility() {
        // نأخذ قيمة الـ Sub Type (الذي يمثل معرف الـ account_type في جدول القاعدة)
        var subTypeId = $('#account_sub_type').val();
        var primaryType = $('#account_primary_type').val();

        // الشرط: إذا كان المعرف المختار موجود ضمن المصفوفة التي تسمح بالرصيد
        if(allowedBalanceIds.includes(parseInt(subTypeId))) {
            $('#opening_balance_section').fadeIn();
            applyInternalLogic(primaryType);
        } else {
            $('#opening_balance_section').hide();
        }
    }

    function applyInternalLogic(primaryType) {
        // الأصول والخصوم -> توزيع إجباري
        if(['asset', 'liabilities'].includes(primaryType)) {
            $('#balance_option_div').hide();
            $('#single_balance_div').hide();
            $('#distributed_balance_div').show();
            $('#radio_distributed').prop('checked', true);
        } 
        // حقوق الملكية -> اختيار (عام أو توزيع)
        else if(primaryType == 'equity') {
            $('#balance_option_div').show();
            if($('#radio_distributed').is(':checked')) {
                $('#single_balance_div').hide();
                $('#distributed_balance_div').show();
            } else {
                $('#single_balance_div').show();
                $('#distributed_balance_div').hide();
            }
        } else {
            $('#balance_option_div').hide();
            $('#single_balance_div').show();
            $('#distributed_balance_div').hide();
        }
    }

    // التنفيذ عند تغيير النوع الفرعي (لأنه هو المرتبط بـ show_balance)
    $(document).on('change', '#account_sub_type', function(){
        // نضع مهلة بسيطة للتأكد أن القيمة تم اختيارها
        setTimeout(checkBalanceVisibility, 100);
    });

    $(document).on('change', 'input[name="balance_type"]', function(){
        applyInternalLogic($('#account_primary_type').val());
    });
});
</script>