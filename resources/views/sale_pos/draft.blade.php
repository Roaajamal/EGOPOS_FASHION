@extends('layouts.app')
@section('title', __( 'sale.drafts'))
@section('content')

<!-- Content Header (Page header) -->
<section class="content-header no-print">
    <h1 class="tw-text-xl md:tw-text-3xl tw-font-bold tw-text-black">@lang('sale.drafts')
    </h1>
</section>

<!-- Main content -->
<section class="content no-print">
    @component('components.filters', ['title' => __('report.filters')])
        <div class="col-md-3">
            <div class="form-group">
                {!! Form::label('sell_list_filter_location_id',  __('purchase.business_location') . ':') !!}

                {!! Form::select('sell_list_filter_location_id', $business_locations, null, ['class' => 'form-control select2', 'style' => 'width:100%', 'placeholder' => __('lang_v1.all') ]); !!}
            </div>
        </div>
        <div class="col-md-3">
            <div class="form-group">
                {!! Form::label('sell_list_filter_customer_id',  __('contact.customer') . ':') !!}
                {!! Form::select('sell_list_filter_customer_id', $customers, null, ['class' => 'form-control select2', 'style' => 'width:100%', 'placeholder' => __('lang_v1.all')]); !!}
            </div>
        </div>

        <div class="col-md-3">
            <div class="form-group">
                {!! Form::label('sell_list_filter_date_range', __('report.date_range') . ':') !!}
                {!! Form::text('sell_list_filter_date_range', null, ['placeholder' => __('lang_v1.select_a_date_range'), 'class' => 'form-control', 'readonly']); !!}
            </div>
        </div>
        <div class="col-md-3">
            <div class="form-group">
                {!! Form::label('created_by',  __('report.user') . ':') !!}
                {!! Form::select('created_by', $sales_representative, null, ['class' => 'form-control select2', 'style' => 'width:100%']); !!}
            </div>
        </div>
    @endcomponent
    @component('components.widget', ['class' => 'box-primary'])
        @slot('tool')
            <div class="box-tools">
                <a class="tw-dw-btn tw-bg-gradient-to-r tw-from-indigo-600 tw-to-blue-500 tw-font-bold tw-text-white tw-border-none tw-rounded-full pull-right"
                    href="{{action([\App\Http\Controllers\SellController::class, 'create'], ['status' => 'draft'])}}">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                        stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                        class="icon icon-tabler icons-tabler-outline icon-tabler-plus">
                        <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                        <path d="M12 5l0 14" />
                        <path d="M5 12l14 0" />
                    </svg>  @lang('lang_v1.add_draft')
                </a>
            </div>
        @endslot
        <div class="table-responsive">
            <table class="table table-bordered table-striped ajax_view" id="sell_table">
                <thead>
                    <tr>
                        <th>@lang('messages.date')</th>
                        <th>@lang('purchase.ref_no')</th>
                        <th>@lang('sale.customer_name')</th>
                        <th>@lang('lang_v1.contact_no')</th>
                        <th>@lang('sale.location')</th>
                        <th>@lang('lang_v1.total_items')</th>
                        <th>@lang('lang_v1.added_by')</th>
                        <th>الحالة</th>
                        <th>@lang('messages.action')</th>
                    </tr>
                </thead>
            </table>
        </div>
    @endcomponent
</section>
<!-- /.content -->
@stop
@section('javascript')
<script type="text/javascript">
$(document).ready( function(){
    $('#sell_list_filter_date_range').daterangepicker(
        dateRangeSettings,
        function (start, end) {
            $('#sell_list_filter_date_range').val(start.format(moment_date_format) + ' ~ ' + end.format(moment_date_format));
            sell_table.ajax.reload();
        }
    );
    $('#sell_list_filter_date_range').on('cancel.daterangepicker', function(ev, picker) {
        $('#sell_list_filter_date_range').val('');
        sell_table.ajax.reload();
    });
    sell_table = $('#sell_table').DataTable({
        processing: true,
        serverSide: true,
        fixedHeader:false,
        aaSorting: [[0, 'desc']],
        "ajax": {
            "url": '/sells/draft-dt?is_quotation=0',
            "data": function ( d ) {
                if($('#sell_list_filter_date_range').val()) {
                    var start = $('#sell_list_filter_date_range').data('daterangepicker').startDate.format('YYYY-MM-DD');
                    var end = $('#sell_list_filter_date_range').data('daterangepicker').endDate.format('YYYY-MM-DD');
                    d.start_date = start;
                    d.end_date = end;
                }

                if($('#sell_list_filter_location_id').length) {
                    d.location_id = $('#sell_list_filter_location_id').val();
                }
                d.customer_id = $('#sell_list_filter_customer_id').val();

                if($('#created_by').length) {
                    d.created_by = $('#created_by').val();
                }
            }
        },
        columnDefs: [ {
            "targets": [7, 8],
            "orderable": false,
            "searchable": false
        } ],
        columns: [
            { data: 'transaction_date', name: 'transaction_date'  },
            { data: 'invoice_no', name: 'invoice_no'},
            { data: 'conatct_name', name: 'conatct_name'},
            { data: 'mobile', name: 'contacts.mobile'},
            { data: 'business_location', name: 'bl.name'},
            { data: 'total_items', name: 'total_items', "searchable": false},
            { data: 'added_by', name: 'added_by'},
            { data: 'draft_status', name: 'draft_status', "searchable": false},
            { data: 'action', name: 'action'}
        ],
        "fnDrawCallback": function (oSettings) {
            __currency_convert_recursively($('#purchase_table'));
        }
    });
    $(document).on('change', '#sell_list_filter_location_id, #sell_list_filter_customer_id, #created_by',  function() {
        sell_table.ajax.reload();
    });

    $(document).on('click', 'a.convert-to-proforma', function(e){
        e.preventDefault();
        swal({
            title: LANG.sure,
            icon: 'warning',
            buttons: true,
            dangerMode: true,
        }).then(confirm => {
            if (confirm) {
                var url = $(this).attr('href');
                $.ajax({
                    method: 'GET',
                    url: url,
                    dataType: 'json',
                    success: function(result) {
                        if (result.success == true) {
                            toastr.success(result.msg);
                            sell_table.ajax.reload();
                        } else {
                            toastr.error(result.msg);
                        }
                    },
                });
            }
        });
    });

    // Delete Draft - NEW
    $(document).on('click', 'a.delete-draft', function(e){
        e.preventDefault();
        
        swal({
            title: 'هل أنت متأكد؟',
            text: 'سيتم حذف هذه المسودة نهائياً!',
            icon: 'warning',
            buttons: {
                cancel: {
                    text: 'إلغاء',
                    value: null,
                    visible: true,
                    className: "",
                    closeModal: true,
                },
                confirm: {
                    text: 'نعم، احذف!',
                    value: true,
                    visible: true,
                    className: "bg-danger",
                    closeModal: true
                }
            },
            dangerMode: true,
        }).then(willDelete => {
            if (willDelete) {
                var url = $(this).attr('href');
                var draft_id = $(this).data('draft_id');
                
                $.ajax({
                    method: 'DELETE',
                    url: url,
                    data: {
                        _token: '{{ csrf_token() }}'
                    },
                    dataType: 'json',
                    success: function(result) {
                        if (result.success == 1) {
                            toastr.success(result.msg);
                            sell_table.ajax.reload();
                        } else {
                            toastr.error(result.msg);
                        }
                    },
                    error: function(xhr) {
                        toastr.error('حدث خطأ أثناء الحذف');
                    }
                });
            }
        });
    });

    // Convert Draft to Invoice - NEW
    $(document).on('click', 'a.convert-draft', function(e){
        e.preventDefault();
        
        swal({
            title: 'تحويل المسودة إلى فاتورة نهائية؟',
            text: 'سيتم إنشاء فاتورة نهائية وخصم المخزون',
            icon: 'info',
            buttons: {
                cancel: {
                    text: 'إلغاء',
                    value: null,
                    visible: true,
                },
                confirm: {
                    text: 'نعم، حوّل الآن!',
                    value: true,
                    visible: true,
                    className: "bg-success",
                }
            },
        }).then(willConvert => {
            if (willConvert) {
                swal({
                    title: 'جاري التحويل...',
                    text: 'يرجى الانتظار',
                    icon: 'info',
                    buttons: false,
                    closeOnClickOutside: false,
                    closeOnEsc: false,
                });
                
                var url = $(this).attr('href');
                window.location.href = url;
            }
        });
    });

    // View Draft Details - NEW (No btn-modal conflict)
    $(document).on('click', 'a.view-draft-details', function(e){
        e.preventDefault();
        var url = $(this).attr('href');
        
        $.ajax({
            url: url,
            method: 'GET',
            success: function(response) {
                // Create simple modal
                if ($('#draftDetailsModal').length) {
                    $('#draftDetailsModal').remove();
                }
                
                var modal = $('<div class="modal fade" id="draftDetailsModal" tabindex="-1" role="dialog">' + response + '</div>');
                $('body').append(modal);
                $('#draftDetailsModal').modal('show');
                
                // Remove modal from DOM when closed
                $('#draftDetailsModal').on('hidden.bs.modal', function() {
                    $(this).remove();
                });
            },
            error: function() {
                toastr.error('حدث خطأ أثناء تحميل التفاصيل');
            }
        });
    });
});
</script>
	
@endsection