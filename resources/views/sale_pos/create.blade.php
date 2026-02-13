@extends('layouts.app')

@section('title', __('sale.pos_sale'))

@section('content')
    <section class="content no-print">
        <input type="hidden" id="amount_rounding_method" value="{{ $pos_settings['amount_rounding_method'] ?? '' }}">
        @if (!empty($pos_settings['allow_overselling']))
            <input type="hidden" id="is_overselling_allowed">
        @endif
        @if (session('business.enable_rp') == 1)
            <input type="hidden" id="reward_point_enabled">
        @endif
        @php
            $is_discount_enabled = $pos_settings['disable_discount'] != 1 ? true : false;
            $is_rp_enabled = session('business.enable_rp') == 1 ? true : false;
        @endphp
        {!! Form::open([
            'url' => action([\App\Http\Controllers\SellPosController::class, 'store']),
            'method' => 'post',
            'id' => 'add_pos_sell_form',
        ]) !!}
        <div class="row mb-12">
            <div class="col-md-12 tw-pt-0 tw-mb-14">
                <div class="row tw-flex lg:tw-flex-row md:tw-flex-col sm:tw-flex-col tw-flex-col tw-items-start md:tw-gap-4">
                    {{-- <div class="@if (empty($pos_settings['hide_product_suggestion'])) col-md-7 @else col-md-10 col-md-offset-1 @endif no-padding pr-12"> --}}
                    <div class="tw-px-3 tw-w-full  lg:tw-px-0 lg:tw-pr-0 @if(empty($pos_settings['hide_product_suggestion'])) lg:tw-w-[60%]  @else lg:tw-w-[100%] @endif">

                        <div class="tw-shadow-[rgba(17,_17,_26,_0.1)_0px_0px_16px] tw-rounded-2xl tw-bg-white tw-mb-2 md:tw-mb-8 tw-p-2">

                            {{-- <div class="box box-solid mb-12 @if (!isMobile()) mb-40 @endif"> --}}
                                <div class="box-body pb-0">
                                    {!! Form::hidden('location_id', $default_location->id ?? null, [
                                        'id' => 'location_id',
                                        'data-receipt_printer_type' => !empty($default_location->receipt_printer_type)
                                            ? $default_location->receipt_printer_type
                                            : 'browser',
                                        'data-default_payment_accounts' => $default_location->default_payment_accounts ?? '',
                                    ]) !!}
                                    <!-- sub_type -->
                                    {!! Form::hidden('sub_type', isset($sub_type) ? $sub_type : null) !!}
                                    <input type="hidden" id="item_addition_method"
                                        value="{{ $business_details->item_addition_method }}">
                                    @include('sale_pos.partials.pos_form')

                                    @include('sale_pos.partials.pos_form_totals')

                                    @include('sale_pos.partials.payment_modal')

                                    @if (empty($pos_settings['disable_suspend']))
                                        @include('sale_pos.partials.suspend_note_modal')
                                    @endif

                                    @if (empty($pos_settings['disable_recurring_invoice']))
                                        @include('sale_pos.partials.recurring_invoice_modal')
                                    @endif
                                </div>
                            {{-- </div> --}}
                        </div>
                    </div>
                    @if (empty($pos_settings['hide_product_suggestion']) && !isMobile())
                        <div class="md:tw-no-padding tw-w-full lg:tw-w-[40%] tw-px-5">
                            @include('sale_pos.partials.pos_sidebar')
                        </div>
                    @endif
                </div>
            </div>
        </div>
        @include('sale_pos.partials.pos_form_actions')
        {!! Form::close() !!}
    </section>

    <!-- This will be printed -->
    <section class="invoice print_section" id="receipt_section">
    </section>
    <div class="modal fade contact_modal" tabindex="-1" role="dialog" aria-labelledby="gridSystemModalLabel">
        @include('contact.create', ['quick_add' => true])
    </div>
    @if (empty($pos_settings['hide_product_suggestion']) && isMobile())
        @include('sale_pos.partials.mobile_product_suggestions')
    @endif
    <!-- /.content -->
    <div class="modal fade register_details_modal" tabindex="-1" role="dialog" aria-labelledby="gridSystemModalLabel">
    </div>
    <div class="modal fade close_register_modal" tabindex="-1" role="dialog" aria-labelledby="gridSystemModalLabel">
    </div>
    <!-- quick product modal -->
    <div class="modal fade quick_add_product_modal" tabindex="-1" role="dialog" aria-labelledby="modalTitle"></div>

    <div class="modal fade" id="expense_modal" tabindex="-1" role="dialog" aria-labelledby="gridSystemModalLabel">
    </div>

    @include('sale_pos.partials.configure_search_modal')

    @include('sale_pos.partials.recent_transactions_modal')

    @include('sale_pos.partials.weighing_scale_modal')

@stop
@section('css')
    <!-- include module css -->
    @if (!empty($pos_module_data))
        @foreach ($pos_module_data as $key => $value)
            @if (!empty($value['module_css_path']))
                @includeIf($value['module_css_path'])
            @endif
        @endforeach
    @endif
@stop
@section('javascript')

    <script src="{{ asset('js/pos.js?v=' . $asset_v) }}"></script>
    <script src="{{ asset('js/printer.js?v=' . $asset_v) }}"></script>
    <script src="{{ asset('js/product.js?v=' . $asset_v) }}"></script>
    <script src="{{ asset('js/opening_stock.js?v=' . $asset_v) }}"></script>
<script>
//////// js for return by barcode button 001
 $(document).ready(function() {
    
    function executeReturnSearch() {
        var sku = $('#sku_input_return').val().trim();
        if (sku) {
            searchReturnInvoices(sku);
        } else {
            toastr.error('يرجى إدخال الباركود أولاً');
        }
    }

    $(document).on('click', '#btn_search_return', function() {
        executeReturnSearch();
    });

    $(document).on('keydown', '#sku_input_return', function(e) {
        e.stopPropagation(); 
        if (e.which == 13) { 
            e.preventDefault();
            executeReturnSearch();
        }
    });

    $(document).on('shown.bs.modal', '#returnSearchModal', function () {
        $('#sku_input_return').val('');
        $('#invoices_results_area_return').html('<p class="text-center text-muted">انتظار المسح أو إدخال الكود...</p>');
        setTimeout(function() { $('#sku_input_return').focus(); }, 500);
    });

    function searchReturnInvoices(sku) {
        var container = $('#invoices_results_area_return');
        var location_id = $('#location_id').val(); 

        container.html('<div class="text-center"><i class="fas fa-spinner fa-spin"></i> جاري البحث...</div>');
        
        $.ajax({
            method: 'get',
            url: '/search-invoices-by-product/' + encodeURIComponent(sku),
            data: { location_id: location_id },
            dataType: 'json',
            success: function(result) {
               if (result.success && result.invoices.length > 0) {
        var html = '<div class="table-responsive"><table class="table table-bordered table-striped" style="font-size: 12px;">' +
                   '<thead class="bg-primary text-white">' +
                   '<tr>' +
                       '<th>التاريخ</th>' +
                       '<th>الفاتورة</th>' +
                       '<th>المنتج</th>' + 
                       '<th>SKU</th>' +    
                       '<th>العميل</th>' +
                       '<th>الكمية</th>' +
                       '<th>كاش</th>' +
                       '<th>بطاقة</th>' +
                       '<th>إجراء</th>' +
                   '</tr>' +
                   '</thead><tbody>';
        
        $.each(result.invoices, function(i, inv) {
            var date = new Date(inv.transaction_date).toLocaleDateString('en-GB');
            var cash = inv.total_cash ? parseFloat(inv.total_cash).toFixed(2) : '0.00';
            var card = inv.total_card ? parseFloat(inv.total_card).toFixed(2) : '0.00';
            
            var return_url = '/sell-return/add/' + inv.transaction_id;

            html += '<tr>' +
                '<td>' + date + '</td>' +
                '<td>' + inv.invoice_no + '</td>' +
                '<td>' + inv.product_name + '</td>' +
                '<td>' + inv.sku + '</td>' +
                '<td>' + inv.customer_name + '</td>' +
                '<td><b class="text-danger">' + parseFloat(inv.total_qty) + '</b></td>' +
                '<td class="text-success">' + cash + '</td>' +
                '<td class="text-info">' + card + '</td>' +
                '<td>' +
                    '<a href="' + return_url + '" class="btn btn-primary btn-xs" target="_blank">' +
                        '<i class="fas fa-undo"></i> إرجاع' +
                    '</a>' +
                '</td>' +
            '</tr>';
        });
        
        html += '</tbody></table></div>';
        $('#invoices_results_area_return').html(html);
    } else {
        $('#invoices_results_area_return').html('<div class="alert alert-warning text-center">' + result.msg + '</div>');
    }
            },
            error: function() {
                container.html('<div class="alert alert-danger text-center">خطأ في الاتصال بالسيرفر</div>');
            }
        });
    }
    $('form#sell_return_form').on('submit', function(e) {
    var total_qty = 0;
    $('input.return_qty').each(function() {
        total_qty += __read_number($(this));
    });
    
    if (total_qty <= 0) {
        e.preventDefault();
        toastr.error('يجب إدخال كمية واحدة على الأقل للإرجاع');
    }
});
});
//////// js for return by barcode button 001
document.addEventListener('DOMContentLoaded', function () {
    let buffer = '';
    let lastTime = 0;
    const productCache = new Map();

    document.addEventListener('keydown', function(e) {
        if (e.target.tagName === 'TEXTAREA') return;

        const now = Date.now();
        if (now - lastTime > 20) buffer = '';
        lastTime = now;

        if (e.key === 'Enter') {
            e.preventDefault();
            const barcode = buffer.trim();
            buffer = '';
            const locationId = document.getElementById('location_id')?.value;
            if (!locationId) { toastr.warning('اختر الموقع أولاً'); return; }

            const cacheKey = `${barcode}_${locationId}`;
            if (productCache.has(cacheKey)) {
                const variationId = productCache.get(cacheKey);
                if (variationId && typeof pos_product_row === 'function') pos_product_row(variationId);
                return;
            }

            const url = `{{ route('pos.get-product-by-barcode') }}?barcode=${encodeURIComponent(barcode)}&location_id=${locationId}&_t=${Date.now()}`;
            fetch(url, { headers: { 'Accept':'application/json' } })
                .then(r => r.json())
                .then(res => {
                    if (res.success && res.product) {
                        const variationId = res.variation_id || res.product.variations?.[0]?.id || res.product.id;
                        if (variationId) {
                            productCache.set(cacheKey, variationId);
                            if (typeof pos_product_row === 'function') pos_product_row(variationId);
                        } else toastr.warning('لم يتم العثور على المنتج');
                    } else toastr.warning(res.message || 'المنتج غير موجود');
                })
                .catch(() => toastr.error('خطأ أثناء جلب المنتج'));

            return;
        }

        if (e.key.length === 1) buffer += e.key;
    });
});

</script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const search = document.getElementById('search_product');
    let userClickedSearch = false;

    if (!search) return;

    // فقط إذا المستخدم كبس بإيده
    search.addEventListener('mousedown', function () {
        userClickedSearch = true;
    });

    search.addEventListener('focus', function () {
        if (!userClickedSearch) {
            search.blur(); // الغي أي فوكس تلقائي
        }
    });

    // لما يطلع من الحقل
    search.addEventListener('blur', function () {
        userClickedSearch = false;
    });

    // عند فتح أي drawer / modal
    document.querySelectorAll('.tw-dw-drawer-toggle').forEach(el => {
        el.addEventListener('change', () => {
            search.blur();
        });
    });

    
});

</script>
<script>
// --- كود تحديث رقم الفاتورة (008) ---

// 1. الدالة الأساسية لجلب الرقم من السيرفر
// أضفنا لها معامل delay للتحكم في وقت الانتظار
function update_next_invoice_no(delay = 1500) {
    if ($('#next_invoice_no_display').length <= 0) {
        return false; 
    }
    // جلب الـ ID الخاص بالموقع (الفرع)
    var location_id = $('select#select_location_id').val() || $('input#location_id').val();
    
    if (location_id) {
        // استخدام setTimeout لضمان تحديث قاعدة البيانات قبل الطلب
        setTimeout(function() {
            $.ajax({
                method: 'GET',
                url: '/get-next-invoice-no',
                data: { location_id: location_id },
                success: function(next_no) {
                    if (next_no) {
                        $('#next_invoice_no_display').text(next_no);
                    }
                },
                error: function() {
                    console.log("تنبيه: تعذر تحديث رقم الفاتورة");
                }
            });
        }, delay);
    }
}

$(document).ready(function() {
    // 2. تحديث الرقم فوراً عند تغيير الفرع يدويًا من القائمة
    $(document).on('change', 'select#select_location_id', function() {
        // نمرر delay = 0 لأننا نريد التغيير فوراً عند اختيار الفرع
        if (typeof update_next_invoice_no === "function") {
            update_next_invoice_no(0);
        }
    });
});
</script>




    @include('sale_pos.partials.keyboard_shortcuts')

    <!-- Call restaurant module if defined -->
    @if (in_array('tables', $enabled_modules) ||
            in_array('modifiers', $enabled_modules) ||
            in_array('service_staff', $enabled_modules))
        <script src="{{ asset('js/restaurant.js?v=' . $asset_v) }}"></script>
    @endif
    <!-- include module js -->
    @if (!empty($pos_module_data))
        @foreach ($pos_module_data as $key => $value)
            @if (!empty($value['module_js_path']))
                @includeIf($value['module_js_path'], ['view_data' => $value['view_data']])
            @endif
        @endforeach
    @endif
@endsection
