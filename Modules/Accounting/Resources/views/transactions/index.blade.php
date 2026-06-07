@extends('layouts.app')

@section('title', __('accounting::lang.transactions'))

@section('content')

    @include('accounting::layouts.nav')

    <!-- Content Header (Page header) -->
    <section class="content-header">
        <h1 class="tw-text-xl md:tw-text-3xl tw-font-bold tw-text-black">@lang('accounting::lang.transactions')</h1>
    </section>

    <!-- Main content -->
    <section class="content">
        
        <div class="row">
            <div class="col-xs-12">
                {{-- <div class="col-xs-12 pos-tab-container"> --}}
                @component('components.widget', ['class' => 'pos-tab-container'])
                    <div class="col-lg-2 col-md-2 col-sm-2 col-xs-2 pos-tab-menu tw-rounded-lg">
                        <div class="list-group">
                            <a href="#"
                                class="list-group-item text-center tw-font-bold tw-text-sm md:tw-text-base active">@lang('sale.sale')</a>
                            <a href="#"
                                class="list-group-item text-center tw-font-bold tw-text-sm md:tw-text-base">@lang('accounting::lang.sales_payments')</a>
                            <a href="#"
                                class="list-group-item text-center tw-font-bold tw-text-sm md:tw-text-base">@lang('purchase.purchases')</a>
                            <a href="#"
                                class="list-group-item text-center tw-font-bold tw-text-sm md:tw-text-base">@lang('accounting::lang.purchase_payments')</a>
                            <a href="#"
                                class="list-group-item text-center tw-font-bold tw-text-sm md:tw-text-base">@lang('accounting::lang.expenses')</a>


                                <a href="#" class="list-group-item text-center tw-font-bold">@lang('accounting::lang.opening_stock')</a>
                                <a href="#" class="list-group-item text-center tw-font-bold">@lang('accounting::lang.stock_adjustment')</a>
                        </div>
                    </div>
                    <div class="col-lg-10 col-md-10 col-sm-10 col-xs-10 pos-tab">
                        @include('accounting::transactions.partials.sales')
                    </div>
                    <div class="col-lg-10 col-md-10 col-sm-10 col-xs-10 pos-tab">
                        @include('accounting::transactions.partials.payments', [
                            'id' => 'sell_payment_table',
                        ])
                    </div>
                    <div class="col-lg-10 col-md-10 col-sm-10 col-xs-10 pos-tab">
                        @include('accounting::transactions.partials.purchases')
                    </div>
                    <div class="col-lg-10 col-md-10 col-sm-10 col-xs-10 pos-tab">
                        @include('accounting::transactions.partials.payments', [
                            'id' => 'purchase_payment_table',
                        ])
                    </div>
                    <div class="col-lg-10 col-md-10 col-sm-10 col-xs-10 pos-tab">
                        @include('accounting::transactions.partials.expenses')
                    </div>
<div class="col-lg-10 col-md-10 col-sm-10 col-xs-10 pos-tab">
    <div class="pos-tab-content">
        @include('accounting::transactions.partials.opening_stock')
    </div>
</div>

<div class="col-lg-10 col-md-10 col-sm-10 col-xs-10 pos-tab">
    <div class="pos-tab-content">
        @include('accounting::transactions.partials.stock_adjustment')
    </div>
</div>
                </div>
            @endcomponent
        </div>
        </div>

    </section>
    <!-- /.content -->
@stop

@section('javascript')
    @include('accounting::accounting.common_js')
<script type="text/javascript">
        var selectedSellIds = new Set();

        $(document).ready(function() {

            sell_table = $('#sell_table').DataTable({
                processing: true,
                serverSide: true,
                aaSorting: [[2, 'desc']],
                "ajax": {
                    "url": base_path + "/accounting/transactions?type=sell&datatable=sell",
                    "data": function(d) {
                        if ($('#sell_list_filter_date_range').val()) {
                            var start = $('#sell_list_filter_date_range').data('daterangepicker').startDate.format('YYYY-MM-DD');
                            var end = $('#sell_list_filter_date_range').data('daterangepicker').endDate.format('YYYY-MM-DD');
                            d.start_date = start;
                            d.end_date = end;
                        }
                        d.is_direct_sale = 1;
                        d.location_id = $('#sell_list_filter_location_id').val();
                        d.customer_id = $('#sell_list_filter_customer_id').val();
                        d.payment_status = $('#sell_list_filter_payment_status').val();
                        d.created_by = $('#created_by').val();
                        d.sales_cmsn_agnt = $('#sales_cmsn_agnt').val();
                        d.service_staffs = $('#service_staffs').val();
                        if ($('#shipping_status').length) {
                            d.shipping_status = $('#shipping_status').val();
                        }
                        if ($('#sell_list_filter_source').length) {
                            d.source = $('#sell_list_filter_source').val();
                        }
                        if ($('#only_subscriptions').is(':checked')) {
                            d.only_subscriptions = 1;
                        }
                        d = __datatable_ajax_callback(d);
                    }
                },
                scrollY: "75vh",
                scrollX: true,
                scrollCollapse: true,
                columns: [
                    {
                        data: 'row_id',
                        name: 'row_id',
                        orderable: false,
                        searchable: false,
                        render: function(data, type, row) {
                            return '<input type="checkbox" class="row-select" data-id="' + data + '">';
                        }
                    },
                    {
                        data: 'action',
                        name: 'action',
                        orderable: false,
                        searchable: false
                    },
                    { data: 'transaction_date', name: 'transaction_date' },
                    { data: 'invoice_no',       name: 'invoice_no' },
                    { data: 'conatct_name',     name: 'conatct_name' },
                    { data: 'mobile',           name: 'contacts.mobile' },
                    { data: 'business_location',name: 'bl.name' },
                    { data: 'payment_status',   name: 'payment_status' },
                    { data: 'payment_methods',  orderable: false, searchable: false },
                    { data: 'final_total',      name: 'final_total' },
                    { data: 'total_paid',       name: 'total_paid', searchable: false },
                    { data: 'added_by',         name: 'u.first_name' },
                    { data: 'additional_notes', name: 'additional_notes' },
                    { data: 'staff_note',       name: 'staff_note' }
                ],
                // ✅ fnDrawCallback واحدة بس هنا
                "fnDrawCallback": function(oSettings) {
                    __currency_convert_recursively($('#sell_table'));

                    // استرجع حالة الـ checkboxes بعد كل رسم
                    $('#sell_table tbody tr').each(function() {
                        var checkbox = $(this).find('input.row-select');
                        var id = checkbox.attr('data-id');
                        if (id) {
                            checkbox.prop('checked', selectedSellIds.has(id));
                        }
                    });
                }
            });

            // ✅ احفظ الـ ID عند التحديد
            $(document).on('change', '#sell_table tbody input.row-select', function() {
                var id = $(this).attr('data-id');
                if (id && id !== 'undefined') {
                    if ($(this).is(':checked')) {
                        selectedSellIds.add(id);
                    } else {
                        selectedSellIds.delete(id);
                    }
                }
                console.log('IDs المحددة:', Array.from(selectedSellIds));
            });

            // ✅ Select All
            $(document).on('change', '#select-all-rows', function() {
                var isChecked = $(this).prop('checked');
                $('#sell_table tbody input.row-select').each(function() {
                    $(this).prop('checked', isChecked);
                    var id = $(this).attr('data-id');
                    if (id && id !== 'undefined') {
                        if (isChecked) {
                            selectedSellIds.add(id);
                        } else {
                            selectedSellIds.delete(id);
                        }
                    }
                });
            });

            // ✅ زر الربط الجماعي
            $(document).on('click', '#bulk_map_accounts', function() {
                var selected_ids = Array.from(selectedSellIds);
                console.log('IDs النهائية:', selected_ids);

                if (selected_ids.length === 0) {
                    toastr.error("يرجى تحديد عملية واحدة على الأقل");
                    return;
                }

                $.ajax({
                    url: '/accounting/transactions/map',
                    method: 'GET',
                    traditional: false,
                    data: { id: selected_ids, type: 'sell' },
                    dataType: 'html',
                    success: function(result) {
                        $('.view_modal').html(result).modal('show');
                    },
                    error: function(xhr) {
                        console.log('Error:', xhr.responseText);
                    }
                });
            });

            sell_payment_table = $('#sell_payment_table').DataTable({
                processing: true,
                serverSide: true,
                "ajax": {
                    "url": base_path + "/accounting/transactions?transaction_type=sell&datatable=payment",
                    "data": function(d) {
                        // d.account_id = $('#account_id').val();
                        // var start_date = '';
                        // var endDate = '';
                        // if($('#date_filter').val()){
                        //     var start_date = $('#date_filter').data('daterangepicker').startDate.format('YYYY-MM-DD');
                        //     var endDate = $('#date_filter').data('daterangepicker').endDate.format('YYYY-MM-DD');
                        // }
                        // d.start_date = start_date;
                        // d.end_date = endDate;
                    }
                },
                columnDefs: [{
                    "targets": 0,
                    "orderable": false,
                    "searchable": false
                }],
                columns: [{
                        data: 'action',
                        name: 'action'
                    },
                    {
                        data: 'paid_on',
                        name: 'paid_on'
                    },
                    {
                        data: 'payment_ref_no',
                        name: 'payment_ref_no'
                    },
                    {
                        data: 'transaction_number',
                        name: 'transaction_number'
                    },
                    {
                        data: 'amount',
                        name: 'amount'
                    },
                    {
                        data: 'type',
                        name: 'T.type'
                    },
                    {
                        data: 'details',
                        name: 'details',
                        "searchable": false
                    },
                ],
                "fnDrawCallback": function(oSettings) {
                    __currency_convert_recursively($('#sell_payment_table'));
                }
            });
            purchase_payment_table = $('#purchase_payment_table').DataTable({
                processing: true,
                serverSide: true,
                "ajax": {
                    "url": base_path +
                        "/accounting/transactions?transaction_type=purchase&datatable=payment",
                    "data": function(d) {
                        // d.account_id = $('#account_id').val();
                        // var start_date = '';
                        // var endDate = '';
                        // if($('#date_filter').val()){
                        //     var start_date = $('#date_filter').data('daterangepicker').startDate.format('YYYY-MM-DD');
                        //     var endDate = $('#date_filter').data('daterangepicker').endDate.format('YYYY-MM-DD');
                        // }
                        // d.start_date = start_date;
                        // d.end_date = endDate;
                    }
                },
                columnDefs: [{
                    "targets": 0,
                    "orderable": false,
                    "searchable": false
                }],
                columns: [{
                        data: 'action',
                        name: 'action'
                    },
                    {
                        data: 'paid_on',
                        name: 'paid_on'
                    },
                    {
                        data: 'payment_ref_no',
                        name: 'payment_ref_no'
                    },
                    {
                        data: 'transaction_number',
                        name: 'transaction_number'
                    },
                    {
                        data: 'amount',
                        name: 'amount'
                    },
                    {
                        data: 'type',
                        name: 'T.type'
                    },
                    {
                        data: 'details',
                        name: 'details',
                        "searchable": false
                    },
                ],
                "fnDrawCallback": function(oSettings) {
                    __currency_convert_recursively($('#sell_payment_table'));
                }
            });

            //Purchase table
            purchase_table = $('#purchase_table').DataTable({
                processing: true,
                serverSide: true,
                ajax: {
                    url: '/accounting/transactions?datatable=purchase',
                    data: function(d) {
                        if ($('#purchase_list_filter_location_id').length) {
                            d.location_id = $('#purchase_list_filter_location_id').val();
                        }
                        if ($('#purchase_list_filter_supplier_id').length) {
                            d.supplier_id = $('#purchase_list_filter_supplier_id').val();
                        }
                        if ($('#purchase_list_filter_payment_status').length) {
                            d.payment_status = $('#purchase_list_filter_payment_status').val();
                        }
                        if ($('#purchase_list_filter_status').length) {
                            d.status = $('#purchase_list_filter_status').val();
                        }

                        var start = '';
                        var end = '';
                        if ($('#purchase_list_filter_date_range').val()) {
                            start = $('input#purchase_list_filter_date_range')
                                .data('daterangepicker')
                                .startDate.format('YYYY-MM-DD');
                            end = $('input#purchase_list_filter_date_range')
                                .data('daterangepicker')
                                .endDate.format('YYYY-MM-DD');
                        }
                        d.start_date = start;
                        d.end_date = end;

                        d = __datatable_ajax_callback(d);
                    },
                },
                aaSorting: [
                    [1, 'desc']
                ],
                columns: [{
                        data: 'action',
                        name: 'action',
                        orderable: false,
                        searchable: false
                    },
                    {
                        data: 'transaction_date',
                        name: 'transaction_date'
                    },
                    {
                        data: 'ref_no',
                        name: 'ref_no'
                    },
                    {
                        data: 'location_name',
                        name: 'BS.name'
                    },
                    {
                        data: 'name',
                        name: 'contacts.name'
                    },
                    {
                        data: 'status',
                        name: 'status'
                    },
                    {
                        data: 'payment_status',
                        name: 'payment_status'
                    },
                    {
                        data: 'final_total',
                        name: 'final_total'
                    },
                    {
                        data: 'payment_due',
                        name: 'payment_due',
                        orderable: false,
                        searchable: false
                    },
                    {
                        data: 'added_by',
                        name: 'u.first_name'
                    },
                ],
                fnDrawCallback: function(oSettings) {
                    __currency_convert_recursively($('#purchase_table'));
                }
            });

            $(document).on('submit', "form#save_accounting_map", function(e) {
                e.preventDefault();
                var form = $(this);
                var data = form.serialize();
                transaction_type = $('#transaction_type').val();

                $.ajax({
                    method: 'POST',
                    url: $(this).attr('action'),
                    dataType: 'json',
                    data: data,
                    success: function(result) {
                        if (result.success == true) {
                            $('div.view_modal').modal('hide');
                            toastr.success(result.msg);
                            if (transaction_type == 'sell') {
                                sell_table.ajax.reload();
                            } else if (transaction_type == 'sell_payment') {
                                sell_payment_table.ajax.reload();
                            } else if (transaction_type == 'purchase') {
                                purchase_table.ajax.reload();
                            } else if (transaction_type == 'purchase_payment') {
                                purchase_payment_table.ajax.reload();
                            } else if (transaction_type == 'expense') {
                                transaction_expense_table.ajax.reload();
                            }
                        } else {
                            toastr.error(result.msg);
                        }
                    },
                });


            });

            // expense_table
            transaction_expense_table = $('#transaction_expense_table').DataTable({
                processing: true,
                serverSide: true,
                ajax: {
                    url: '/accounting/transactions?type=expense&datatable=expense',
                    data: function(d) {},
                },
                scrollY: "75vh",
                scrollX: true,
                scrollCollapse: true,
                columns: [{
                        data: 'action',
                        name: 'action',
                        orderable: false,
                        searchable: false
                    },
                    {
                        data: 'transaction_date',
                        name: 'transaction_date'
                    },
                    {
                        data: 'ref_no',
                        name: 'ref_no'
                    },
                    {
                        data: 'recur_details',
                        name: 'recur_details',
                        orderable: false,
                        searchable: false
                    },
                    {
                        data: 'category',
                        name: 'ec.name'
                    },
                    {
                        data: 'sub_category',
                        name: 'esc.name'
                    },
                    {
                        data: 'location_name',
                        name: 'bl.name'
                    },
                    {
                        data: 'payment_status',
                        name: 'payment_status',
                        orderable: false
                    },
                    {
                        data: 'tax',
                        name: 'tr.name'
                    },
                    {
                        data: 'final_total',
                        name: 'final_total'
                    },
                    {
                        data: 'payment_due',
                        name: 'payment_due'
                    },
                    {
                        data: 'expense_for',
                        name: 'expense_for'
                    },
                    {
                        data: 'contact_name',
                        name: 'c.name'
                    },
                    {
                        data: 'additional_notes',
                        name: 'additional_notes'
                    },
                    {
                        data: 'added_by',
                        name: 'usr.first_name'
                    }
                ],
                fnDrawCallback: function(oSettings) {
                    __currency_convert_recursively($('#transaction_expense_table'));
                }
            });



   // Opening Stock Table
opening_stock_table = $('#opening_stock_table').DataTable({
    processing: true,
    serverSide: true,
    ajax: {
        // تأكد أن النوع مطابق لما يتوقعه الـ Controller
        url: '/accounting/transactions?type=opening_stock&datatable=opening_stock',
        data: function(d) {
            d = __datatable_ajax_callback(d);
        }
    },
    columns: [
        { data: 'action', name: 'action', orderable: false, searchable: false },
        { data: 'transaction_date', name: 'transaction_date' },
        { data: 'ref_no', name: 'ref_no' },
        // جرب تغيير location_name إلى business_location إذا كان هذا هو الاسم في موديول المحاسبة
        { data: 'business_location', name: 'bl.name' }, 
        { data: 'final_total', name: 'final_total' },
        { data: 'added_by', name: 'u.first_name' }
    ]
});

// Stock Adjustment Table (تعديل النوع إلى add_quantity)
stock_adjustment_table = $('#stock_adjustment_table').DataTable({
    processing: true,
    serverSide: true,
    ajax: {
        // غيرنا النوع هنا بناءً على ملاحظتك
        url: '/accounting/transactions?type=add_quantity&datatable=stock_adjustment',
        data: function(d) {
            d = __datatable_ajax_callback(d);
        }
    },
    columns: [
        { data: 'action', name: 'action', orderable: false, searchable: false },
        { data: 'transaction_date', name: 'transaction_date' },
        { data: 'ref_no', name: 'ref_no' },
        { data: 'business_location', name: 'bl.name' }, // تأكد من الاسم هنا أيضاً
        { data: 'adjustment_type', name: 'adjustment_type' },
        { data: 'final_total', name: 'final_total' },
        { data: 'total_amount_recovered', name: 'total_amount_recovered' },
        { data: 'additional_notes', name: 'additional_notes' },
        { data: 'added_by', name: 'u.first_name' }
    ]
});


$('div.pos-tab-menu>div.list-group>a').click(function(e) {
    e.preventDefault();
    $(this).siblings('a.active').removeClass('active');
    $(this).addClass('active');
    var index = $(this).index();
    
    // إظهار القسم المناسب وإخفاء الباقي
    $("div.pos-tab>div.pos-tab-content").removeClass("active");
    $("div.pos-tab").eq(index).find('div.pos-tab-content').addClass("active");
    
    // تحديث الجدول عند الضغط على التبويب لضمان تحميل البيانات
    if (index == 4) { // افترضنا أن ترتيب "المخزون الافتتاحي" هو 4
        opening_stock_table.ajax.reload();
    } else if (index == 5) { // افترضنا أن "إدخال الكميات" هو 5
        stock_adjustment_table.ajax.reload();
    }
});
        });
    </script>
@stop
