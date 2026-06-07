@extends('layouts.app')
@section('title', __('accounting::lang.add_voucher'))

@section('content')
@include('accounting::layouts.nav')
<section class="content">
    <div class="box box-primary">
        <div class="box-header">
            <h3 class="box-title">إضافة سند جديد</h3>
        </div>
        <div class="box-body">
            {!! Form::open(['url' => action('\Modules\Accounting\Http\Controllers\VoucherController@store'), 'method' => 'post', 'id' => 'add_voucher_form']) !!}
            
<div class="row">
    <div class="col-md-3">
        <div class="form-group">
            {!! Form::label('location_id', 'الفرع:*') !!}
            {!! Form::select('location_id', $business_locations, null, ['class' => 'form-control select2', 'required', 'placeholder' => 'اختر الفرع']); !!}
        </div>
    </div>
    
    <div class="col-md-3">
        <div class="form-group">
            {!! Form::label('type', 'نوع السند:*') !!}
            {!! Form::select('type', [
                'receipt' => 'سند قبض', 
                'payment' => 'سند صرف',
                'journal' => 'سند قيد'
            ], 'receipt', ['class' => 'form-control select2', 'required', 'id' => 'voucher_type']); !!}
        </div>
    </div>
    </div>

                <div class="col-md-3">
                    <div class="form-group">
                        {!! Form::label('voucher_no', 'رقم السند:') !!}
                        {!! Form::text('voucher_no', $next_voucher_no, ['class' => 'form-control', 'placeholder' => 'اتركه فارغاً للتوليد التلقائي']); !!}
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="form-group">
                        {!! Form::label('operation_date', 'التاريخ:*') !!}
                        {!! Form::date('operation_date', \Carbon\Carbon::now()->format('Y-m-d'), ['class' => 'form-control', 'required']); !!}
                    </div>
                </div>
            </div>

<div class="row">
    <div class="col-md-3">
        <div class="form-group">
            {!! Form::label('partner_type', 'نوع الطرف الآخر:') !!}
            {!! Form::select('partner_type', [
                'customer' => 'عميل (Customer)',
                'supplier' => 'مورد (Supplier)',
                'none' => 'حساب مباشر (Direct Account)'
            ], 'customer', ['class' => 'form-control select2', 'id' => 'partner_type']); !!}
        </div>
    </div>

    <div class="col-md-3" id="contact_div">
        <div class="form-group">
            {!! Form::label('contact_id', 'اختيار الاسم من النظام:') !!}
            {!! Form::select('contact_id', $contacts, null, ['class' => 'form-control select2', 'placeholder' => 'اختر من القائمة', 'id' => 'contact_id']); !!}
        </div>
    </div>

    <div class="col-md-3">
        <div class="form-group">
            {!! Form::label('payee_name', 'الاسم المكتوب في السند:*') !!}
            {!! Form::text('payee_name', null, ['class' => 'form-control', 'required', 'placeholder' => 'الاسم الذي سيظهر عند الطباعة', 'id' => 'payee_name']); !!}
        </div>
    </div>
</div>

<hr>

<div class="row">
    <div class="col-md-6">
        <div class="form-group">
            {!! Form::label('debit_account', 'من حساب (الطرف المدين):*') !!}
            {!! Form::select('debit_account', $accounts, null, ['class' => 'form-control select2', 'required', 'placeholder' => 'اختر الحساب']); !!}
            <small class="text-muted">الحساب الذي زادت قيمته (مثلاً الصندوق في حال القبض)</small>
        </div>
    </div>

    <div class="col-md-6">
        <div class="form-group">
            {!! Form::label('credit_account', 'إلى حساب (الطرف الدائن):*') !!}
            {!! Form::select('credit_account', $accounts, null, ['class' => 'form-control select2', 'required', 'placeholder' => 'اختر الحساب']); !!}
            <small class="text-muted">الحساب الذي نقصت قيمته (مثلاً العميل في حال القبض)</small>
        </div>
    </div>
</div>



            <div class="row">
                <div class="col-md-4">
                    <div class="form-group">
                        {!! Form::label('amount', 'المبلغ:*') !!}
                        {!! Form::number('amount', null, ['class' => 'form-control', 'required', 'step' => '0.01', 'placeholder' => '0.00']); !!}
                    </div>
                </div>
                <div class="col-md-8">
                    <div class="form-group">
                        {!! Form::label('note', 'البيان (السبب):') !!}
                        {!! Form::textarea('note', null, ['class' => 'form-control', 'rows' => 3, 'placeholder' => 'اكتب تفاصيل السند هنا...']); !!}
                    </div>
                </div>
            </div>

<div class="row">
    <div class="col-md-12 text-center">
        <button type="submit" name="submit_type" value="save" class="btn btn-primary btn-lg">
            <i class="fa fa-save"></i> حفظ 
        </button>

        <button type="submit" name="submit_type" value="save_and_print" class="btn btn-primary btn-lg">
            <i class="fa fa-print"></i> حفظ وطباعة السند
        </button>
    </div>
</div>

            {!! Form::close() !!}
        </div>
    </div>
</section>
@endsection

@section('javascript')
<script>
    $(document).ready(function(){
        // 1. عند تغيير نوع السند (قبض، صرف، قيد)
        $('#voucher_type').on('change', function(){
            var type = $(this).val();
            
            if(type == 'journal') {
                // في سند القيد: نثبت الخيار على "حساب مباشر" ونقفل الاسم
                $('#partner_type').val('none').trigger('change');
                $('#payee_name').val('سند قيد محاسبي').prop('readonly', true);
            } else {
                // في سند القبض أو الصرف:
                // نعيد خيار "عميل" كخيار افتراضي ليظهر حقل اختيار الأسماء مرة أخرى
                $('#partner_type').val('customer').trigger('change'); 
                
                // نفتح حقل الاسم ونفرغه
                $('#payee_name').val('').prop('readonly', false);
                $('#payee_name').attr('placeholder', 'الاسم سيظهر هنا تلقائياً أو اكتبه');
            }
        });

        // 2. عند اختيار جهة اتصال من القائمة
        $('#contact_id').on('change', function(){
            var selectedName = $("#contact_id option:selected").text();
            var contactId = $(this).val();
            
            if(contactId != "" && contactId != undefined) {
                // تنظيف الاسم من أي رموز زائدة
                var cleanName = selectedName.split(' - ')[0].trim(); 
                $('#payee_name').val(cleanName);
            }
        });

        // 3. التحكم في ظهور واختفاء قسم اختيار الأسماء
        $('#partner_type').on('change', function(){
            var partnerType = $(this).val();
            
            if(partnerType == 'none') {
                $('#contact_div').fadeOut();
                // لا نفرغ الاسم هنا إذا كان نوع السند "قيد" لأننا وضعنا له قيمة ثابتة فوق
                if($('#voucher_type').val() != 'journal') {
                    $('#payee_name').val('');
                }
                $('#contact_id').val('').trigger('change');
            } else {
                $('#contact_div').fadeIn();
            }
        });
    });
</script>
@endsection