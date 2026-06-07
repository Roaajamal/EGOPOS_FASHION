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
            
            // جلب عدد الأعمدة ديناميكياً من الإعدادات للتحكم بصف المنتجات المميزة الجانبي
            $columns_per_row = !empty($pos_settings['products_per_row']) ? intval($pos_settings['products_per_row']) : 4;
        @endphp
        {!! Form::open([
            'url' => action([\App\Http\Controllers\SellPosController::class, 'store']),
            'method' => 'post',
            'id' => 'add_pos_sell_form',
        ]) !!}
 <!-- بداية قسم الحاوية المرنة الرئيسية المعدلة بدقة -->
        <div class="row">
            <div class="col-md-12">
                <div id="pos_flexible_container" class="tw-flex tw-flex-nowrap tw-w-full tw-gap-4" style="display: flex !important; flex-wrap: nowrap !important; align-items: flex-start; direction: rtl;">
                    
                    {{-- 1. قسم الفاتورة والبيع (تم تعديل الارتفاع هنا ليتطابق مع الجانب الآخر) --}}
                    <div id="pos_main_column" 
                         class="tw-transition-all tw-duration-500 tw-ease-in-out"
                         style="width: {{ empty($pos_settings['hide_product_suggestion']) ? '60%' : '100%' }}; flex-shrink: 0; box-sizing: border-box;">
                        
                        {{-- أضفنا min-height: 75vh و display: flex لتوزيع العناصر داخلياً بالطول الكامل --}}
                        <div class="tw-shadow-[rgba(17,_17,_26,_0.1)_0px_0px_16px] tw-rounded-2xl tw-bg-white tw-p-2" style="min-height: 75vh; display: flex; flex-direction: column;">
                            <div class="box-body pb-0 tw-flex-1" style="display: flex; flex-direction: column; height: 100%;">
                                {!! Form::hidden('location_id', $default_location->id ?? null, ['id' => 'location_id']) !!}
                                {!! Form::hidden('sub_type', isset($sub_type) ? $sub_type : null) !!}
                                
                                {{-- زر التبديل (إن وجد) --}}
                                @if(!empty($pos_settings['enable_exchange_button']) && empty($pos_settings['enable_fatora']))
                                    <div class="row">
                                        <div class="col-md-12">
                                            <button type="button" id="toggle_exchange_mode" class="btn btn-default btn-flat tw-mb-2 tw-rounded-xl tw-border-2 tw-border-blue-500 tw-text-blue-600" style="width: 100%; font-weight: bold;">
                                                <i class="fas fa-sync-alt"></i> وضع التبديل: <span id="exchange_status_text">معطل</span>
                                            </button>
                                            <input type="hidden" id="is_exchange_mode" value="0">
                                        </div>
                                    </div>
                                @endif

                               
                                <div class="pos-form-container" style="flex-1; max-height: calc(100% - 160px); width: 100%;">
                                    @include('sale_pos.partials.pos_form')
                                </div>
                                
                                <div class="pos-totals-section" style="margin-top: auto; flex-shrink: 0; width: 100%; background: #fff; padding-top: 10px;">
                                    @include('sale_pos.partials.pos_form_totals')
                                    @include('sale_pos.partials.payment_modal')
                                </div>
                            </div>
                        </div>
                    </div>

              {{-- 2. قسم المنتجات والجانب --}}
<div id="pos_side_column" 
     class="tw-transition-all tw-duration-500 tw-ease-in-out"
     style="width: {{ empty($pos_settings['hide_product_suggestion']) ? '40%' : '0%' }}; 
            display: {{ empty($pos_settings['hide_product_suggestion']) ? 'block' : 'none' }}; 
            flex-shrink: 0; overflow: hidden; box-sizing: border-box;">
    
    <div class="tw-shadow-[rgba(17,_17,_26,_0.1)_0px_0px_16px] tw-rounded-2xl tw-bg-white tw-p-4" style="min-height: 75vh; display: flex; flex-direction: column;">
        @if(empty($pos_settings['hide_product_suggestion']))
            {{-- محتوى الـ Sidebar الأصلي (مثل الكاتالوج والفئات) --}}
            @include('sale_pos.partials.pos_sidebar')
        @else
            {{-- هذا هو القسم المعدل للمنتجات المميزة مع إضافة السكرول --}}
            <div id="featured_products_dynamic" class="tw-w-full tw-flex tw-flex-col" style="height: 100%;">
                <h4 class="tw-font-bold tw-text-orange-600 tw-flex tw-items-center tw-gap-2 tw-shrink-0">
                    <i class="fa fa-star"></i> المنتجات المميزة
                </h4>
                <hr class="tw-my-3 tw-border-gray-200 tw-shrink-0">
                
                {{-- 🔽🔽🔽 الحاوية الجديدة التي تحتوي على السكرول 🔽🔽🔽 --}}
                <div style="overflow-y: auto; max-height: 60vh; padding-left: 2px; padding-right: 2px;">
                    <div id="featured_products_box" style="display: grid !important; grid-template-columns: repeat({{ $columns_per_row }}, minmax(0, 1fr)) !important; gap: 8px !important; width: 100%;">
                        @include('sale_pos.partials.featured_products')
                    </div>
                </div>
                {{-- 🔼🔼🔼 نهاية الحاوية 🔼🔼🔼 --}}
                
            </div>
        @endif
    </div>
</div>

                </div>
            </div>
        </div>
        <!-- نهاية قسم الحاوية المرنة المعدلة -->

{{-- أزرار العمليات (حفظ، تعليق، إلخ) --}}
<div class="row">
    <div class="col-md-12">
        @include('sale_pos.partials.pos_form_actions')
    </div>
</div>
    </section>
    {!! Form::close() !!} 

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
    <input type="hidden" id="printing_type" value="{{ $business->printing_type ?? 'qz' }}">


    <input type="hidden" id="skip_invoice_check" 
    value="{{ !empty($pos_settings['skip_invoice_check']) ? 1 : 0 }}">

    

    {{-- هاد السطر بجيب أرقام الفروع المسموح لها من الإعدادات اللي عملناها --}}
<input type="hidden" id="enabled_label_locations" value="{{ json_encode($enabled_label_locations ?? []) }}">

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
    {{-- 1. تحميل المكتبات الأساسية --}}
    <script src="https://cdn.socket.io/4.7.2/socket.io.min.js"></script>
 
    <script src="{{ asset('js/product.js?v=' . $asset_v) }}"></script>
    <script src="{{ asset('js/opening_stock.js?v=' . $asset_v) }}"></script>
    <script src="{{ asset('js/pos.js?v=' . $asset_v) }}"></script>
    <script>
    window.exchange_skip_check = {{ !empty($pos_settings['skip_invoice_check']) ? 'true' : 'false' }};
</script>

    <script>
    window.openCashDrawer = function() {
        if (typeof socket !== 'undefined' && socket.connected) {
            socket.emit('open_drawer', { printer_name: 'egopos' });
            console.log("✅ تم إرسال أمر فتح الدرج");
        } else {
            console.error("❌ سيرفر الطباعة غير متصل");
        }
    };

    // تعريف دالة بديلة للاختصار إذا كان النظام يستدعيها باسم مختلف
    window.triggerCashDrawer = window.openCashDrawer;

    $(document).ready(function() {
        // أي كود Ajax إضافي يوضع هنا داخل بلوك واحد مغلق بدقة
        console.log("DOM Ready - POS System Loaded");
    });
        // --- كود البحث عن المرتجعات بالباركود ---
        function executeReturnSearch() {
            var sku = $('#sku_input_return').val().trim();
            if (sku) { searchReturnInvoices(sku); } 
            else { toastr.error('يرجى إدخال الباركود أولاً'); }
        }

        $(document).on('click', '#btn_search_return', function() { executeReturnSearch(); });

        $(document).on('keydown', '#sku_input_return', function(e) {
            if (e.which == 13) { e.preventDefault(); executeReturnSearch(); }
        });

        $(document).on('shown.bs.modal', '#returnSearchModal', function () {
            $('#sku_input_return').val('');
            $('#invoices_results_area_return').html('<p class="text-center text-muted">انتظار المسح...</p>');
            setTimeout(function() { $('#sku_input_return').focus(); }, 500);
        });

        function searchReturnInvoices(sku) {
            var container = $('#invoices_results_area_return');
            container.html('<div class="text-center"><i class="fas fa-spinner fa-spin"></i> جاري البحث...</div>');
            $.ajax({
                method: 'get',
                url: '/search-invoices-by-product/' + encodeURIComponent(sku),
                data: { location_id: $('#location_id').val() },
                dataType: 'json',
                success: function(result) {
                    if (result.success && result.invoices.length > 0) {
                        var html = '<div class="table-responsive"><table class="table table-bordered table-striped" style="font-size: 12px;">' +
                                   '<thead class="bg-primary text-white"><tr><th>التاريخ</th><th>الفاتورة</th><th>المنتج</th><th>الكمية</th><th>إجراء</th></tr></thead><tbody>';
                        $.each(result.invoices, function(i, inv) {
                            html += '<tr><td>'+new Date(inv.transaction_date).toLocaleDateString()+'</td><td>'+inv.invoice_no+'</td><td>'+inv.product_name+'</td><td>'+parseFloat(inv.total_qty)+'</td>' +
                                    '<td><a href="/sell-return/add/'+inv.transaction_id+'" class="btn btn-primary btn-xs" target="_blank"><i class="fas fa-undo"></i> إرجاع</a></td></tr>';
                        });
                        html += '</tbody></table></div>';
                        container.html(html);
                    } else { container.html('<div class="alert alert-warning text-center">' + result.msg + '</div>'); }
                }
            });
        }

        // --- كود فحص المخزون بالباركود ---
        function executeStockLookup() {
            var sku = $('#sku_input_stock').val().trim();
            if (sku) { fetchProductStock(sku, $('#location_filter_stock').val()); } 
            else { toastr.error('يرجى إدخال الباركود'); }
        }

        $(document).on('click', '#btn_execute_stock_search', function() { executeStockLookup(); });
        $(document).on('keydown', '#sku_input_stock', function(e) { if (e.which == 13) { e.preventDefault(); executeStockLookup(); } });

        $(document).on('shown.bs.modal', '#StockSearchModal', function () {
            $('#sku_input_stock').val('').focus();
            $('#stock_qty_results_area').html('<p class="text-center text-muted">بانتظار المسح...</p>');
        });

       function fetchProductStock(sku, locationId) {
        var resultsContainer = $('#stock_qty_results_area');
        
        resultsContainer.html('<div class="text-center" style="margin-top:40px;"><i class="fas fa-spinner fa-spin fa-2x"></i> جاري جلب بيانات المخزون...</div>');
        
        $.ajax({
            method: 'get',
            url: '/get-product-stock-by-sku/' + encodeURIComponent(sku), // تأكد من مطابقة المسار في الـ Route
            data: { location_id: locationId }, // ✅ إرسال الفرع مع الطلب (فاضي = كل الفروع) 
            dataType: 'json',
            success: function(result) {
                if (result.success && result.stocks.length > 0) {
                    var tableHtml = '<div class="table-responsive"><table class="table table-bordered table-hover">' +
                                    '<thead style="background-color: #27ae60; color: white;">' +
                                    '<tr>' +
                                        '<th>اسم الفرع</th>' +
                                        '<th>المنتج</th>' + 
                                        '<th>SKU</th>' +   
                                        '<th>الحجم</th>' +
                                        '<th>اللون</th>' +  
                                        '<th>الموديل</th>' +
                                        '<th>الكمية المتاحة</th>' +
                                    '</tr>' +
                                    '</thead><tbody>';
                    
                    $.each(result.stocks, function(i, item) {
                        var qtyStyle = parseFloat(item.available_qty) <= 0 ? 'color: red; font-weight: bold;' : 'color: green; font-weight: bold;';
                        
                        tableHtml += '<tr>' +
                            '<td>' + item.location_name + '</td>' +
                            '<td>' + item.product_full_name + '</td>' +
                            '<td>' + item.sku + '</td>' +
                            '<td>' + (item.size ? item.size : '-') + '</td>' +
                            '<td>' + (item.color ? item.color : '-') + '</td>' +
                            '<td>' + (item.model ? item.model : '-') + '</td>' + 
                            '<td><span style="' + qtyStyle + '">' + parseFloat(item.available_qty) + '</span></td>' +
                        '</tr>';
                    });
                    
                    tableHtml += '</tbody></table></div>';
                    resultsContainer.html(tableHtml);
                } else {
                    resultsContainer.html('<div class="alert alert-info text-center">عذراً، هذا المنتج غير متوفر في أي فرع حالياً.</div>');
                }
            },
            error: function() {
                resultsContainer.html('<div class="alert alert-danger text-center">حدث خطأ أثناء الاتصال بالنظام، يرجى المحاولة لاحقاً.</div>');
            }
        });
    }

        // --- إجبار اختيار العميل والبائع ---
        var originalPosProductRow = window.pos_product_row;
        window.pos_product_row = function(variation_id, e) {
            if ({{ !empty($pos_settings['add_customer']) ? 'true' : 'false' }} && $('#customer_id option:selected').text().trim() === 'Walk-In Customer') {
                toastr.error('يرجى إضافة العميل أولاً');
                $('#customer_id').select2('open');
                return false;
            }
            if ({{ !empty($pos_settings['add_seller']) ? 'true' : 'false' }} && !$('#commission_agent').val()) {
                toastr.error('يرجى إضافة البائع أولاً');
                $('#commission_agent').select2('open');
                return false;
            }
            return originalPosProductRow(variation_id, e);
        };

    // --- كود المسح السريع بالباركود (خارج الـ ready لسرعة الاستجابة) ---
    document.addEventListener('DOMContentLoaded', function () {
        let buffer = '';
        let lastTime = Date.now();
        document.addEventListener('keydown', function(e) {
            if (e.target.tagName === 'INPUT' || e.target.tagName === 'TEXTAREA') return;
            const now = Date.now();
            if (now - lastTime > 50) buffer = '';
            lastTime = now;
            if (e.key === 'Enter') {
                e.preventDefault();
                let barcode = buffer.trim();
                buffer = '';
                if (barcode && typeof pos_product_row === 'function') {
                    let locId = document.getElementById('location_id')?.value;
                    fetch(`{{ route('pos.get-product-by-barcode') }}?barcode=${barcode}&location_id=${locId}`)
                    .then(r => r.json()).then(res => {
                        if (res.success && res.variation_id) pos_product_row(res.variation_id);
                    });
                }
            } else if (e.key.length === 1) { buffer += e.key; }
        });
    });

$(document).ready(function() {

    // Toggle admin discount section
    $(document).on('click', '#adminDiscountModal', function() {
        var section = $('#adminDiscountSection');
        if (section.is(':visible')) {
            section.slideUp();
            $('#service_staff_pin_input').val('');
            $('#pin_error_msg, #pin_success_msg').hide();
        } else {
            section.slideDown();
            $('#service_staff_pin_input').focus();
        }
    });

    // Verify admin PIN
    $(document).on('click', '#verifyAdminPin', function() {
        var pin = $('#service_staff_pin_input').val().trim();

        if (!pin) {
            $('#pin_error_msg').text('يرجى إدخال الرقم السري').show();
            $('#pin_success_msg').hide();
            return;
        }

        var btn = $(this);
        btn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i>');

        $.ajax({
            url: '/pos/verify-admin-discount-pin',
            method: 'POST',
            data: {
                pin: pin,
                _token: $('meta[name="csrf-token"]').attr('content')
            },
            success: function(response) {
                btn.prop('disabled', false).html('<i class="fa fa-check"></i> تحقق');

                if (response.success) {
                    $('#pin_success_msg').text(response.message).show();
                    $('#pin_error_msg').hide();

                    var maxDiscount = parseFloat(response.max_discount);

                    // تخزين القيمة الجديدة
                    window.admin_discount_override = true;
                    window.admin_max_discount = maxDiscount;

                    // تحديث الـ attribute والـ data
                    $('#discount_amount_modal')
                        .attr('data-max-discount', maxDiscount)
                        .attr('data-admin-override', '1')
                        .data('max-discount', maxDiscount)
                        .data('maxDiscount', maxDiscount);

                    // إعادة تطبيق الـ validation بالقيمة الجديدة
                    $('#discount_amount_modal').rules('remove', 'max-value');
                    $('#discount_amount_modal').rules('add', {
                        'max-value': maxDiscount,
                        messages: {
                            'max-value': 'الحد الأقصى للخصم هو ' + maxDiscount + '%'
                        }
                    });

                    setTimeout(function() {
                        $('#adminDiscountSection').slideUp();
                        $('#service_staff_pin_input').val('');
                    }, 1500);

                } else {
                    $('#pin_error_msg').text(response.message).show();
                    $('#pin_success_msg').hide();
                    $('#service_staff_pin_input').val('').focus();
                }
            },
            error: function() {
                btn.prop('disabled', false).html('<i class="fa fa-check"></i> تحقق');
                $('#pin_error_msg').text('حدث خطأ، حاول مرة أخرى').show();
            }
        });
    });

    // إخفاء الـ section لما يُغلق الموديل ومسح الـ override
    $('#posEditDiscountModal').on('hidden.bs.modal', function() {
        $('#adminDiscountSection').hide();
        $('#service_staff_pin_input').val('');
        $('#pin_error_msg, #pin_success_msg').hide();
        window.admin_discount_override = false;
        window.admin_max_discount = null;
        $('#discount_amount_modal').removeAttr('data-admin-override');
    });

});

$(document).ready(function() {
    // 1. هل السيرفر بعث لنا transaction_id بعد الـ Refresh؟
    @if(session('status.transaction_id'))
        
        // 2. جلب إعدادات الفروع اللي بتطبع ملصقات (اللي ضفناها في Business Settings)
        var enabled_label_locations = {!! json_encode($enabled_label_locations ?? []) !!};
        
        // 3. جلب الفرع الحالي للبيعة (ممكن تجيبه من hidden input أو من السيرفر)
        var current_location_id = $('#location_id').val(); 

        // 4. إذا الفرع الحالي موجود في قائمة الفروع المسموحة، اطلب الطباعة
        if (enabled_label_locations.includes(current_location_id)) {
            print_pos_labels("{{ session('status.transaction_id') }}");
        }
    @endif
});

$(document).ready(function() {
    $(document).on('click', '#toggle_featured_dynamic', function() {
        var mainCol = $('#pos_main_column');
        var sideCol = $('#pos_side_column');
        var container = $('#pos_flexible_container');

        // تثبيت البقاء على صف واحد كإجراء وقائي إضافي لمنع النزول لأسفل
        container.css({'display': 'flex', 'flex-wrap': 'nowrap'});

        if (sideCol.is(':hidden') || sideCol.width() === 0) {
            // إغلاق العرض وتوزيع المساحة (60٪ للفاتورة و 40٪ للمقترحات أو المميزة)
            mainCol.css('width', '60%');
            sideCol.show().css('width', '40%');
            $(this).find('i').removeClass('fa-star').addClass('fa-times');
        } else {
            // إخفاء الـ Sidebar وإرجاع الفاتورة لعرض الشاشة الكاملة 100٪
            sideCol.css('width', '0');
            setTimeout(function() { sideCol.hide(); }, 400); 
            mainCol.css('width', '100%');
            $(this).find('i').removeClass('fa-times').addClass('fa-star');
        }
        
        // إعادة تهيئة وحساب أبعاد الـ Datatables أو الـ Grid الخاصة بالمنتجات تلقائياً لتناسب العرض الجديد
        setTimeout(function() { $(window).trigger('resize'); }, 500);
    });
});

function openCustomerLedger() {
    var customerId = $('#customer_id').val() || $('select[name="contact_id"]').val();
    var customerName = $('#customer_id option:selected').text() || $('select[name="contact_id"] option:selected').text();

    if (!customerId || customerId == '') {
        toastr.warning('يرجى اختيار عميل أولاً');
        return;
    }

    if (customerName.trim().indexOf('Walk-In Customer') !== -1) {
        toastr.warning('يرجى اختيار عميل أولاً');
        return;
    }

    $('#customer_ledger_modal').modal('show');

    $('#ledger_modal_date_range').daterangepicker(dateRangeSettings, function(start, end) {
        $('#ledger_modal_date_range').val(
            start.format(moment_date_format) + ' ~ ' + end.format(moment_date_format)
        );
        applyLedgerFilter();
    });

    $('input[name="ledger_modal_format"][value="format_3"]').prop('checked', true);

    applyLedgerFilter();
}

$(document).on('shown.bs.modal', '#customer_ledger_modal', function() {
    if (!$('#ledger_modal_date_range').data('daterangepicker')) {
        $('#ledger_modal_date_range').daterangepicker(dateRangeSettings, function(start, end) {
            $('#ledger_modal_date_range').val(
                start.format(moment_date_format) + ' ~ ' + end.format(moment_date_format)
            );
            applyLedgerFilter();
        });
    }

    // صلح z-index للـ daterangepicker
    setTimeout(function() {
        $('.daterangepicker').css('z-index', '999999');
    }, 100);
});
function applyLedgerFilter() {
    var customerId = $('#customer_id').val() || $('select[name="contact_id"]').val();
    var startDate = '';
    var endDate = '';
    var format = $('input[name="ledger_modal_format"]:checked').val() || '';

    if ($('#ledger_modal_date_range').val()) {
        startDate = $('#ledger_modal_date_range').data('daterangepicker').startDate.format('YYYY-MM-DD');
        endDate = $('#ledger_modal_date_range').data('daterangepicker').endDate.format('YYYY-MM-DD');
    }

    loadLedger(customerId, startDate, endDate, format);
    // تحديث تلقائي عند تغيير الفورمات
$(document).on('change', 'input[name="ledger_modal_format"]', function() {
    applyLedgerFilter();
});
}

function loadLedger(customerId, startDate, endDate, format) {
    format = format || '';
    $('#ledger_content_area').html('<div class="text-center"><i class="fa fa-spinner fa-spin fa-2x"></i></div>');
    
    $.ajax({
        url: '/contacts/ledger',
        type: 'GET',
        data: { 
            contact_id: customerId,
            start_date: startDate,
            end_date: endDate,
            format: format
        },
        success: function(response) {
            $('#ledger_content_area').html(response);
            __currency_convert_recursively($('#ledger_content_area'));
            if ($('#ledger_table').length) {
                $('#ledger_table').DataTable({
                    searching: false,
                    ordering: false,
                    paging: false,
                    fixedHeader: false,
                    dom: 't'
                });
            }
        }
    });
}
    </script>

    @include('sale_pos.partials.keyboard_shortcuts')

    @if (in_array('tables', $enabled_modules) || in_array('modifiers', $enabled_modules) || in_array('service_staff', $enabled_modules))
        <script src="{{ asset('js/restaurant.js?v=' . $asset_v) }}"></script>
    @endif

    @if (!empty($pos_module_data))
        @foreach ($pos_module_data as $key => $value)
            @if (!empty($value['module_js_path']))
                @includeIf($value['module_js_path'], ['view_data' => $value['view_data']])
            @endif
        @endforeach
    @endif
@endsection