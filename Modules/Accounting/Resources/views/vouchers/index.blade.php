@extends('layouts.app')
@section('title', 'السندات المالية')

@section('content')
@include('accounting::layouts.nav')

<section class="content">
    <div class="box box-primary no-print">
        <div class="box-header">
            <h3 class="box-title">الفلاتر</h3>
        </div>
        <div class="box-body">
            <div class="row">
                <div class="col-md-3">
    <div class="form-group">
        {!! Form::label('filter_location_id', 'الفرع:') !!}
        {!! Form::select('filter_location_id', $business_locations, null, ['class' => 'form-control select2', 'style' => 'width:100%', 'placeholder' => 'الكل']); !!}
    </div>
</div>
                <div class="col-md-3">
                    <div class="form-group">
                        {!! Form::label('filter_type', 'نوع السند:') !!}
                        {!! Form::select('filter_type', ['receipt' => 'سند قبض', 'payment' => 'سند صرف', 'journal' => 'سند قيد'], null, ['class' => 'form-control select2', 'style' => 'width:100%', 'placeholder' => 'الكل']); !!}
                    </div>
                </div>

                <div class="col-md-3">
    <div class="form-group">
        {!! Form::label('filter_contact_type', 'فلترة حسب نوع الجهة:') !!}
        {!! Form::select('contact_type', [
            'customer' => 'عملاء',
            'supplier' => 'موردين',
            'general'  => 'حساب عام'
        ], null, ['class' => 'form-control select2', 'placeholder' => 'الكل', 'id' => 'filter_contact_type', 'style' => 'width:100%']); !!}
    </div>
</div>

                <div class="col-md-3">
                    <div class="form-group">
                        {!! Form::label('filter_contact_id', 'العميل / المورد:') !!}
                        {!! Form::select('filter_contact_id', $contacts, null, ['class' => 'form-control select2', 'style' => 'width:100%', 'placeholder' => 'الكل']); !!}
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="form-group">
                        {!! Form::label('filter_account_id', 'الحساب المالي:') !!}
                        {!! Form::select('filter_account_id', $accounts, null, ['class' => 'form-control select2', 'style' => 'width:100%', 'placeholder' => 'الكل']); !!}
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="form-group">
                        {!! Form::label('filter_date_range', 'الفترة الزمنية:') !!}
                        {!! Form::text('filter_date_range', null, ['placeholder' => 'اختر التاريخ', 'class' => 'form-control', 'readonly']); !!}
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="box box-primary">
        <div class="box-header">
            <h3 class="box-title">إدارة السندات المالية</h3>
            <div class="box-tools">
                <a class="btn btn-primary" href="{{action('\Modules\Accounting\Http\Controllers\VoucherController@create')}}">
                    <i class="fa fa-plus"></i> @lang('messages.add')
                </a>
            </div>
        </div>
        <div class="box-body">
            <table class="table table-bordered table-striped" id="vouchers_table" style="width: 100%;">
                <thead>
                    <tr>
                        <th>التاريخ</th>
                        <th>رقم السند</th>
                        <th>الفرع</th>
                        <th>النوع</th>
                        <th>الشخص</th>
                        <th>الحساب المالي</th>
                        <th>المبلغ</th>
                        <th>البيان</th>
                        <th>الإجراءات</th>
                    </tr>
                </thead>
            </table>
        </div>
    </div>
</section>
@endsection

@section('javascript')
<script>
    $(document).ready(function() {
        // 1. إعداد الـ DateRangePicker
        if ($('#filter_date_range').length) {
            $('#filter_date_range').daterangepicker(
                dateRangeSettings,
                function(start, end) {
                    $('#filter_date_range').val(start.format(moment_date_format) + ' ~ ' + end.format(moment_date_format));
                    vouchers_table.ajax.reload();
                }
            );
            $('#filter_date_range').on('cancel.daterangepicker', function(ev, picker) {
                $(this).val('');
                vouchers_table.ajax.reload();
            });
        }

        // 2. تعريف الجدول
        vouchers_table = $('#vouchers_table').DataTable({
            processing: true,
            serverSide: true,
            aaSorting: [[0, 'desc']], 
            ajax: {
                url: "{{ action('\Modules\Accounting\Http\Controllers\VoucherController@index') }}",
                data: function(d) {
                    d.type = $('#filter_type').val();
                    d.contact_id = $('#filter_contact_id').val();
                    d.account_id = $('#filter_account_id').val();
                    // --- إضافة فلتر نوع الجهة الجديد هنا ---
                    d.contact_type = $('#filter_contact_type').val(); 
                    d.location_id = $('#filter_location_id').val();

                    if ($('#filter_date_range').val()) {
                        var drp = $('#filter_date_range').data('daterangepicker');
                        d.start_date = drp.startDate.format('YYYY-MM-DD');
                        d.end_date = drp.endDate.format('YYYY-MM-DD');
                    }
                }
            },
            columns: [
                { data: 'operation_date', name: 'operation_date' },
                { data: 'voucher_no', name: 'voucher_no' },
                { data: 'location_name', name: 'bl.name' },
                { data: 'type', name: 'type' },
                // تعديل هنا ليقرأ من الاسم المعالج (يدوي أو من القائمة)
                { data: 'contact_display_name', name: 'contacts.name' }, 
                { data: 'account_name', name: 'accounting_accounts.name' },
                { data: 'amount', name: 'amount' },
                { data: 'note', name: 'note' },
                { data: 'action', name: 'action', orderable: false, searchable: false }
            ]
        });

        // 3. تحديث الجدول عند تغيير أي فلتر (أضفنا الفلتر الجديد للقائمة)
        $(document).on('change', '#filter_type, #filter_contact_id, #filter_account_id, #filter_contact_type ,#filter_location_id', function() {
            vouchers_table.ajax.reload();
        });
    });


    $(document).on('click', '.print-cheque', function(e) {
    e.preventDefault();
    var url = "{{ route('accounting.cheques.print', ':id') }}";
    url = url.replace(':id', $(this).data('id'));
    
    // فتح نافذة الطباعة
    var win = window.open(url, '_blank');
    win.focus();
});
</script>
@endsection