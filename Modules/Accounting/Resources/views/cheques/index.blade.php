@extends('layouts.app')
@section('title', 'إدارة سجل الشيكات')

@section('content')
@include('accounting::layouts.nav')

<section class="content no-print">
    <div class="row">
        <div class="col-md-4">
            <div class="info-box shadow-sm border-left-danger">
                <span class="info-box-icon bg-white text-danger"><i class="fa fa-exclamation-circle"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text text-muted">متأخرات معلقة</span>
                    <span class="info-box-number text-dark" id="overdue_count">0</span>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="info-box shadow-sm border-left-warning">
                <span class="info-box-icon bg-white text-warning"><i class="fa fa-clock-o"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text text-muted">استحقاق اليوم</span>
                    <span class="info-box-number text-dark" id="today_count">0</span>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="info-box shadow-sm border-left-info">
                <span class="info-box-icon bg-white text-info"><i class="fa fa-calendar-check-o"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text text-muted">قريباً (3 أيام)</span>
                    <span class="info-box-number text-dark" id="upcoming_count">0</span>
                </div>
            </div>
        </div>
    </div>

    <div class="box box-solid shadow-sm">
        <div class="box-header with-border">
            <h3 class="box-title"><i class="fa fa-search text-primary"></i> الفلاتر</h3>
            <div class="box-tools pull-right">
                <button type="button" class="btn btn-box-tool" data-widget="collapse"><i class="fa fa-minus"></i></button>
            </div>
        </div>
        <div class="box-body bg-light">
            <div class="row">
                <div class="col-md-3">
                    <div class="form-group">
                        <label>نوع العملية:</label>
                        {!! Form::select('cheque_type', ['' => 'الكل', 'mine' => 'شيكات برسم التحصيل (مقبوضة )', 'on_me' => 'شيكات صادرة'], null, ['class' => 'form-control select2', 'id' => 'cheque_type_filter', 'style' => 'width:100%']) !!}
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label>حالة الشيك:</label>
                        {!! Form::select('status', ['' => 'الكل', 'pending' => 'قيد الانتظار', 'cleared' => 'تم التحصيل', 'returned' => 'مرتجع'], null, ['class' => 'form-control select2', 'id' => 'status_filter', 'style' => 'width:100%']) !!}
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label>العميل / المورد:</label>
                        {!! Form::select('contact_id', $contacts, null, ['class' => 'form-control select2', 'placeholder' => 'اختر الاسم', 'id' => 'contact_id_filter', 'style' => 'width:100%']) !!}
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label>اسم البنك:</label>
                        <input type="text" id="bank_filter" class="form-control" placeholder="بحث باسم البنك...">
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-md-3">
                    <div class="form-group">
                        <label>استحقاق سريع:</label>
                        <select id="due_date_filter" class="form-control select2">
                            <option value="all">كل المواعيد</option>
                            <option value="today">اليوم</option>
                            <option value="yesterday">أمس</option>
                            <option value="overdue">المتأخرات المعلقة</option>
                            <option value="upcoming">خلال 3 أيام</option>
                        </select>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label>تاريخ استحقاق مخصص:</label>
                        <div class="input-group">
                            <input type="date" id="start_date" class="form-control" title="من تاريخ">
                            <span class="input-group-addon"><i class="fa fa-arrow-left"></i></span>
                            <input type="date" id="end_date" class="form-control" title="إلى تاريخ">
                        </div>
                    </div>
                </div>
                <div class="col-md-3 text-center">
                    <label>&nbsp;</label><br>
                    <button type="button" id="refresh_table" class="btn btn-primary btn-flat btn-block"><i class="fa fa-refresh"></i> تحديث الجدول</button>
                </div>
            </div>
        </div>
    </div>

    <div class="box box-primary shadow-sm">
        <div class="box-body">
            <div class="table-responsive">
                <table class="table table-bordered table-hover" id="cheques_table" style="width: 100%;">
                    <thead>
                        <tr class="bg-navy">
                            <th>رقم الشيك</th>
                            <th>البنك</th>
                            <th>العميل/المورد</th>
                            <th>المبلغ</th>
                            <th>تاريخ الاستحقاق</th>
                            <th>الحالة</th>
                            <th>رقم الفاتورة</th>
                            <th>الإجراءات</th>
                        </tr>
                    </thead>
                </table>
            </div>
        </div>
    </div>
</section>
@endsection

@section('javascript')
<script>
    $(document).ready(function() {
        var cheques_table = $('#cheques_table').DataTable({
            processing: true,
            serverSide: true,
            aaSorting: [[4, 'asc']],
            ajax: {
                url: '/accounting/cheques',
                data: function(d) {
                    d.cheque_type = $('#cheque_type_filter').val();
                    d.status = $('#status_filter').val();
                    d.contact_id = $('#contact_id_filter').val();
                    d.bank_name = $('#bank_filter').val();
                    d.due_date_filter = $('#due_date_filter').val();
                    d.start_date = $('#start_date').val();
                    d.end_date = $('#end_date').val();
                }
            },
            columns: [
                { data: 'cheque_number', name: 'cheque_number' },
                { data: 'bank_name', name: 'bank_name' },
                { data: 'contact_name', name: 'contact_name' },
                { data: 'amount', name: 'amount' },
                { data: 'cheque_return_date', name: 'cheque_return_date' },
                { data: 'cheque_status', name: 'cheque_status' },
                { data: 'invoice_no', name: 'invoice_no' },
                { data: 'action', name: 'action', orderable: false, searchable: false }
            ],
            createdRow: function(row, data, dataIndex) {
                var dueDate = moment(data.cheque_return_date);
                var today = moment().startOf('day');
                if (data.cheque_status === 'pending') {
                    if (dueDate.isBefore(today)) {
                        $(row).addClass('danger'); 
                    } else if (dueDate.isSame(today, 'd')) {
                        $(row).addClass('warning');
                    }
                }
            },
            fnDrawCallback: function(oSettings) {
                __currency_convert_recursively($('#cheques_table'));
                var json = oSettings.json;
                if (json.counts) {
                    $('#overdue_count').text(json.counts.overdue);
                    $('#today_count').text(json.counts.today);
                    $('#upcoming_count').text(json.counts.upcoming);
                }
            }
        });

        // تشغيل الفلترة عند أي تغيير
        $('select, #bank_filter, #start_date, #end_date').on('change keyup', function() {
            cheques_table.ajax.reload();
        });

        $('#refresh_table').click(function() { cheques_table.ajax.reload(); });

        // الطباعة وتغيير الحالة (نفس الكود السابق يعمل هنا)
        $(document).on('click', '.change-status', function(e) {
            e.preventDefault();
            var id = $(this).data('id');
            var status = $(this).data('status');
            if (confirm("هل تريد تحديث حالة الشيك؟")) {
                $.ajax({
                    method: "POST",
                    url: "{{ route('accounting.cheques.updateStatus') }}",
                    data: { id: id, status: status, _token: "{{ csrf_token() }}" },
                    success: function(result) {
                        if (result.success) {
                            toastr.success(result.msg);
                            cheques_table.ajax.reload();
                        }
                    }
                });
            }
        });

        $(document).on('click', '.print-cheque', function(e) {
            e.preventDefault();
            window.open("{{ route('accounting.cheques.print', ':id') }}".replace(':id', $(this).data('id')), '_blank');
        });
    });
</script>

<style>
    .info-box.shadow-sm { box-shadow: 0 1px 1px rgba(0,0,0,0.1); border-radius: 4px; }
    .border-left-danger { border-left: 5px solid #dd4b39; }
    .border-left-warning { border-left: 5px solid #f39c12; }
    .border-left-info { border-left: 5px solid #00c0ef; }
    .bg-light { background-color: #f9f9f9 !important; }
    .bg-navy { background-color: #001f3f !important; color: #fff; }
    .table-hover tbody tr:hover { background-color: #f1f1f1 !important; }
</style>
@endsection