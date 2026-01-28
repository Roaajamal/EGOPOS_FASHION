@extends('layouts.app')

@section('title', 'تقرير كشف الفواتير')

@section('content')
<section class="content-header">
    <h1>تقرير كشف الفواتير</h1>
</section>

<section class="content">

    {{-- الفلاتر --}}
    <div class="box box-solid">
        <div class="box-body">
            <div class="row">
                <div class="col-md-3">
                    <label>من تاريخ</label>
                    <input type="date" id="start_date" class="form-control">
                </div>

                <div class="col-md-3">
                    <label>إلى تاريخ</label>
                    <input type="date" id="end_date" class="form-control">
                </div>

                <div class="col-md-3">
                    <label>الفرع</label>
                    <select id="location_id" class="form-control">
                        <option value="">كل الفروع</option>
                        @foreach($branches as $branch)
                            <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-3">
                    <label>&nbsp;</label>
                    <button id="filter_btn" class="btn btn-primary btn-block">
                        <i class="fa fa-search"></i> عرض التقرير
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- جدول الملخص --}}
    <div class="box box-primary">
        <div class="box-body table-responsive">
            <table class="table table-bordered table-striped" id="daily_sales_table">
                <thead>
                    <tr>
                        <th>التاريخ</th>
                        <th>الفرع</th>
                        <th>عدد الفواتير</th>
                        <th>إجمالي المبيعات</th>
                        <th>المرتجعات</th>
                        <th>صافي المبيعات</th>
                        <th>الضريبة</th>
                        <th>الخصم</th>
                        <th>مدفوع</th>
                        <th>مستحق</th>
                        <th>جزئي</th>
                        <th>تفاصيل</th>
                    </tr>
                </thead>
            </table>
        </div>
    </div>
</section>

{{-- مودال التفاصيل --}}
<div class="modal fade" id="details_modal">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">تفاصيل اليوم</h4>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body" id="details_body">
                <div class="text-center"><i class="fa fa-spinner fa-spin fa-2x"></i></div>
            </div>
        </div>
    </div>
</div>

@endsection

@section('javascript')
<script>
$(document).ready(function () {
    let table = $('#daily_sales_table').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: "{{ route('invoice.statement.day.details') }}",
            type: "POST",
            data: function(d) {
                d.start_date = $('#start_date').val();
                d.end_date = $('#end_date').val();
                d.location_id = $('#location_id').val();
                d._token = "{{ csrf_token() }}";
            }
        },
        columns: [
            { data: 'date', name: 'date' },
            { data: 'branch', name: 'branch' },
            { data: 'invoice_count', name: 'invoice_count' },
            { data: 'total_sales', name: 'total_sales' },
            { data: 'total_returns', name: 'total_returns' },
            { data: 'net_sales', name: 'net_sales' },
            { data: 'total_tax', name: 'total_tax' },
            { data: 'total_discount', name: 'total_discount' },
            { data: 'paid_amount', name: 'paid_amount' },
            { data: 'due_amount', name: 'due_amount' },
            { data: 'partial_amount', name: 'partial_amount' },
            { 
                data: 'details', 
                orderable: false, 
                searchable: false,
                render: function(data, type, row) {
                    return `<button class="btn btn-sm btn-info view-details" data-date="${row.date}" data-branch="${row.branch}"><i class="fa fa-eye"></i></button>`;
                }
            }
        ]
    });

    $('#filter_btn').click(function () {
        table.ajax.reload();
    });

    // فتح التفاصيل
    $(document).on('click', '.view-details', function() {
        let date = $(this).data('date');
        let branch = $(this).data('branch');

        $('#details_modal').modal('show');
        $('#details_body').html('<div class="text-center"><i class="fa fa-spinner fa-spin fa-2x"></i></div>');

        $.ajax({
            url: "{{ route('invoice.statement.day.details') }}",
            type: 'POST',
            data: { start_date: date, end_date: date, location_id: branch, _token: "{{ csrf_token() }}" },
            success: function(res) {
                $('#details_body').html(res);
            }
        });
    });
});
</script>
@endsection
