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

                                {{-- ============================================================= --}}
                                {{-- 🆕 قسم المزايا الجديدة (فحص السعر • حساب الباقي • الخصومات والعروض) --}}
                                {{-- أُضيف بالكامل داخل نفس الملف دون تعديل أي كود قديم. خلفية بيضاء وأيقونات ملوّنة. --}}
                                {{-- ============================================================= --}}
                                <div id="ego_features_panel" class="tw-mt-3" style="direction: rtl;">
                                    <style>
                                        /* 🆕 تنسيقات قسم المزايا الجديدة فقط (لا يؤثر على باقي الشاشة) */
                                        #ego_features_panel .ego-grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:12px}
                                        @media (max-width:991px){#ego_features_panel .ego-grid{grid-template-columns:1fr}}
                                        #ego_features_panel .ego-card{background:#fff;border:1px solid #e6eaef;border-radius:16px;padding:12px;box-shadow:0 4px 14px rgba(17,17,26,.06);display:flex;flex-direction:column}
                                        #ego_features_panel .ego-title{font-weight:700;font-size:14px;display:flex;align-items:center;gap:6px;margin-bottom:10px;color:#1f2937}
                                        #ego_features_panel .ego-input{width:100%;border:1px solid #d1d5db;border-radius:10px;padding:8px 10px;font-size:16px;text-align:center;font-weight:700;background:#fff}
                                        #ego_features_panel .ego-pricecheck-btn{width:100%;border:none;border-radius:12px;padding:12px;font-weight:800;font-size:15px;color:#fff;background:linear-gradient(135deg,#6366f1,#4f46e5);cursor:pointer;display:flex;align-items:center;justify-content:center;gap:8px}
                                        #ego_features_panel .ego-due{background:#0f172a;color:#fff;border-radius:12px;padding:10px;text-align:center;margin-top:12px}
                                        #ego_features_panel .ego-due .v{font-size:22px;font-weight:800}
                                        #ego_features_panel .ego-keypad{display:grid;grid-template-columns:repeat(3,1fr);gap:6px;margin-top:8px}
                                        #ego_features_panel .ego-key{border:none;border-radius:10px;padding:10px 0;font-size:16px;font-weight:700;background:#f1f5f9;color:#0f172a;cursor:pointer;transition:.15s}
                                        #ego_features_panel .ego-key:hover{background:#e2e8f0}
                                        #ego_features_panel .ego-key.ego-clear{background:#fee2e2;color:#b91c1c}
                                        #ego_features_panel .ego-key.ego-back{background:#fef3c7;color:#92400e}
                                        #ego_features_panel .ego-quick{display:flex;flex-wrap:wrap;gap:6px;margin-top:8px}
                                        #ego_features_panel .ego-quick button{border:1px solid #34d399;background:#ecfdf5;color:#047857;border-radius:999px;padding:4px 12px;font-weight:700;cursor:pointer;font-size:13px}
                                        #ego_features_panel .ego-quick button:hover{background:#34d399;color:#fff}
                                        #ego_features_panel .ego-change{background:#ecfdf5;border:1px dashed #10b981;border-radius:12px;padding:10px;text-align:center;margin-top:8px}
                                        #ego_features_panel .ego-change .v{font-size:22px;font-weight:800;color:#047857}
                                        #ego_features_panel .ego-toggle{display:flex;gap:6px;margin-bottom:8px}
                                        #ego_features_panel .ego-toggle button{flex:1;border:1px solid #d1d5db;background:#fff;border-radius:10px;padding:6px;font-weight:700;cursor:pointer}
                                        #ego_features_panel .ego-toggle button.active{background:#2563eb;color:#fff;border-color:#2563eb}
                                        #ego_features_panel .ego-disc-quick{display:flex;flex-wrap:wrap;gap:6px;margin-top:8px}
                                        #ego_features_panel .ego-disc-quick button{border:1px solid #93c5fd;background:#eff6ff;color:#1d4ed8;border-radius:999px;padding:4px 12px;font-weight:700;cursor:pointer;font-size:13px}
                                        #ego_features_panel .ego-disc-quick button:hover{background:#3b82f6;color:#fff}
                                        #ego_features_panel .ego-offers{display:flex;flex-wrap:wrap;gap:6px;margin-top:8px}
                                        #ego_features_panel .ego-offers button{border:1px solid #f59e0b;background:#fffbeb;color:#b45309;border-radius:10px;padding:6px 10px;font-weight:700;cursor:pointer;font-size:13px}
                                        #ego_features_panel .ego-offers button:hover{background:#f59e0b;color:#fff}
                                        #ego_features_panel .ego-offers button.ego-offer-clear{border-color:#ef4444;background:#fef2f2;color:#b91c1c}
                                        #ego_features_panel .ego-offers button.ego-offer-clear:hover{background:#ef4444;color:#fff}
                                        /* 🆕 شريط أزرار الدفع السريعة (يشبه الصورة) */
                                        #ego_features_panel .ego-paybar{display:grid;grid-template-columns:repeat(6,minmax(0,1fr));gap:8px;margin-top:14px}
                                        @media (max-width:991px){#ego_features_panel .ego-paybar{grid-template-columns:repeat(2,1fr)}}
                                        #ego_features_panel .ego-pay{border:none;border-radius:12px;padding:14px 8px;font-weight:800;font-size:14px;color:#fff;cursor:pointer;display:flex;flex-direction:column;align-items:center;justify-content:center;gap:6px;transition:.15s}
                                        #ego_features_panel .ego-pay i{font-size:18px}
                                        #ego_features_panel .ego-pay:hover{transform:translateY(-2px);filter:brightness(1.05)}
                                        #ego_features_panel .ego-pay-cash{background:#16a34a}
                                        #ego_features_panel .ego-pay-card{background:#2563eb}
                                        #ego_features_panel .ego-pay-credit{background:#0891b2}
                                        #ego_features_panel .ego-pay-multi{background:#0f172a}
                                        #ego_features_panel .ego-pay-disc{background:#f59e0b}
                                        #ego_features_panel .ego-pay-cancel{background:#dc2626}
                                    </style>

                                    <div class="ego-grid">
                                        {{-- بطاقة 1: فحص السعر + المبلغ المستحق --}}
                                        <div class="ego-card">
                                            <div class="ego-title"><i class="fas fa-tags" style="color:#6366f1"></i> فحص السعر</div>
                                            <button type="button" class="ego-pricecheck-btn" data-toggle="modal" data-target="#ego_price_check_modal">
                                                <i class="fas fa-search-dollar"></i> فحص سعر قطعة
                                            </button>
                                            <div class="ego-due">
                                                <div style="font-size:13px;opacity:.85"><i class="fas fa-receipt"></i> المبلغ المستحق</div>
                                                <div class="v"><span id="ego_due_amount">0.00</span></div>
                                            </div>
                                        </div>

                                        {{-- بطاقة 2: حساب الباقي --}}
                                        <div class="ego-card">
                                            <div class="ego-title"><i class="fas fa-calculator" style="color:#10b981"></i> حساب الباقي</div>
                                            <input type="text" id="ego_paid_amount" class="ego-input" placeholder="المبلغ المدفوع" autocomplete="off" inputmode="decimal">
                                            <div class="ego-keypad">
                                                <button type="button" class="ego-key" data-k="7">7</button>
                                                <button type="button" class="ego-key" data-k="8">8</button>
                                                <button type="button" class="ego-key" data-k="9">9</button>
                                                <button type="button" class="ego-key" data-k="4">4</button>
                                                <button type="button" class="ego-key" data-k="5">5</button>
                                                <button type="button" class="ego-key" data-k="6">6</button>
                                                <button type="button" class="ego-key" data-k="1">1</button>
                                                <button type="button" class="ego-key" data-k="2">2</button>
                                                <button type="button" class="ego-key" data-k="3">3</button>
                                                <button type="button" class="ego-key" data-k="0">0</button>
                                                <button type="button" class="ego-key" data-k=".">.</button>
                                                <button type="button" class="ego-key ego-back" data-k="back"><i class="fas fa-backspace"></i></button>
                                            </div>
                                            <div class="ego-quick">
                                                <button type="button" data-amt="5">5</button>
                                                <button type="button" data-amt="10">10</button>
                                                <button type="button" data-amt="20">20</button>
                                                <button type="button" data-amt="50">50</button>
                                                <button type="button" data-amt="100">100</button>
                                                <button type="button" class="ego-key ego-clear" data-k="clear" style="border-radius:999px;padding:4px 12px;font-size:13px"><i class="fas fa-eraser"></i> مسح</button>
                                            </div>
                                            <div class="ego-change">
                                                <div style="font-size:13px;color:#065f46"><i class="fas fa-hand-holding-usd"></i> الباقي للعميل</div>
                                                <div class="v"><span id="ego_change_amount">0.00</span></div>
                                            </div>
                                        </div>

                                        {{-- بطاقة 3: الخصومات والعروض --}}
                                        <div class="ego-card">
                                            <div class="ego-title"><i class="fas fa-percent" style="color:#f59e0b"></i> الخصومات والعروض</div>
                                            <div class="ego-toggle">
                                                <button type="button" id="ego_disc_pct" class="active" data-type="percentage"><i class="fas fa-percentage"></i> نسبة %</button>
                                                <button type="button" id="ego_disc_fixed" data-type="fixed"><i class="fas fa-coins"></i> مبلغ ثابت</button>
                                            </div>
                                            <input type="text" id="ego_disc_value" class="ego-input" placeholder="قيمة الخصم" autocomplete="off" inputmode="decimal">
                                            <div class="ego-disc-quick">
                                                <button type="button" class="ego_disc_quick" data-val="5">5%</button>
                                                <button type="button" class="ego_disc_quick" data-val="10">10%</button>
                                                <button type="button" class="ego_disc_quick" data-val="15">15%</button>
                                                <button type="button" class="ego_disc_quick" data-val="20">20%</button>
                                            </div>
                                            <div style="font-size:12px;color:#6b7280;margin-top:12px"><i class="fas fa-gift" style="color:#ef4444"></i> عروض جاهزة</div>
                                            <div class="ego-offers">
                                                <button type="button" class="ego_offer" data-type="percentage" data-val="25">عرض 25%</button>
                                                <button type="button" class="ego_offer" data-type="percentage" data-val="50">تخفيضات 50%</button>
                                                <button type="button" class="ego_offer ego-offer-clear" data-type="fixed" data-val="0"><i class="fas fa-times"></i> إلغاء الخصم</button>
                                            </div>
                                        </div>
                                    </div>

                                    {{-- 🆕 شريط أزرار الدفع السريعة (كاش/بطاقة/عملاء/طرق أخرى/خصم/إلغاء) --}}
                                    {{-- كل زر يستدعي زر النظام الموجود فعلاً، ولا يكرّر أي وظيفة --}}
                                    <div class="ego-paybar">
                                        <button type="button" class="ego-pay ego-pay-cash" data-ego-target='[data-pay_method="cash"]'><i class="fas fa-money-bill-wave"></i> كاش</button>
                                        <button type="button" class="ego-pay ego-pay-card" data-ego-target='[data-pay_method="card"]'><i class="fas fa-credit-card"></i> بطاقة الائتمان</button>
                                        <button type="button" class="ego-pay ego-pay-credit" data-ego-target='[data-pay_method="credit_sale"]'><i class="fas fa-user-friends"></i> عملاء (آجل)</button>
                                        <button type="button" class="ego-pay ego-pay-multi" data-ego-target='#pos-finalize'><i class="fas fa-money-check-alt"></i> طرق دفع أخرى</button>
                                        <button type="button" class="ego-pay ego-pay-disc" id="ego_pay_discount"><i class="fas fa-percent"></i> الخصم</button>
                                        <button type="button" class="ego-pay ego-pay-cancel" data-ego-target='#pos-cancel'><i class="fas fa-times-circle"></i> إلغاء</button>
                                    </div>
                                </div>

                                {{-- 🆕 نافذة فحص السعر (للعرض فقط — لا تضيف القطعة للسلة) --}}
                                <div class="modal fade" id="ego_price_check_modal" tabindex="-1" role="dialog" aria-hidden="true">
                                    <div class="modal-dialog" role="document" style="direction:rtl">
                                        <div class="modal-content" style="border-radius:16px;overflow:hidden">
                                            <div class="modal-header" style="background:linear-gradient(135deg,#6366f1,#4f46e5);color:#fff">
                                                <button type="button" class="close" data-dismiss="modal" aria-label="Close" style="color:#fff;opacity:.9"><span aria-hidden="true">&times;</span></button>
                                                <h4 class="modal-title"><i class="fas fa-search-dollar"></i> فحص سعر قطعة (بدون بيع)</h4>
                                            </div>
                                            <div class="modal-body">
                                                <input type="text" id="ego_price_check_search" class="ego-input" placeholder="اكتب اسم القطعة أو الباركود..." autocomplete="off" style="margin-bottom:12px">
                                                <div id="ego_price_check_results" style="max-height:340px;overflow-y:auto"></div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                {{-- ============================================================= --}}
                                {{-- 🆕 سكربت المزايا الجديدة — كود جديد بالكامل، لا يعدّل أي دالة قديمة. --}}
                                {{-- يعيد فقط استخدام دوال النظام القديمة (pos_total_row / __read_number / /products/list). --}}
                                {{-- ============================================================= --}}
                                <script>
                                (function egoWaitJQ(){
                                    if (typeof window.jQuery === 'undefined') { return setTimeout(egoWaitJQ, 60); }
                                    var $ = window.jQuery;

                                    // قراءة/كتابة رقمية آمنة (تستخدم دوال النظام إن وُجدت)
                                    function egoReadNum($el){ try { if (typeof __read_number === 'function') return __read_number($el); } catch(e){} var v = parseFloat(($el.val()||'').toString().replace(/,/g,'')); return isNaN(v)?0:v; }
                                    function egoWriteNum($el, val){ try { if (typeof __write_number === 'function'){ __write_number($el, val); return; } } catch(e){} $el.val(val); }
                                    function egoFmt(n){ n = Math.round(n*100)/100; return n.toFixed(2); }
                                    function egoParse(str){ var v = parseFloat((str||'').toString().replace(/,/g,'')); return isNaN(v)?0:v; }

                                    // ---------- المبلغ المستحق + الباقي ----------
                                    function egoGetDue(){ return egoReadNum($('#final_total_input')); }
                                    function egoUpdateDue(){ $('#ego_due_amount').text(egoFmt(egoGetDue())); egoCalcChange(); }
                                    function egoCalcChange(){
                                        var due = egoGetDue();
                                        var paid = egoParse($('#ego_paid_amount').val());
                                        var change = paid - due;
                                        var $c = $('#ego_change_amount');
                                        $c.text(egoFmt(change));
                                        $c.css('color', change < 0 ? '#b91c1c' : '#047857');
                                    }

                                    // الإجمالي يتحدّث عبر pos_total_row القديمة؛ نراقب span#total_payable لرصد التغيّر
                                    $(document).on('change keyup', '#final_total_input', egoUpdateDue);
                                    if (window.MutationObserver){
                                        var tp = document.getElementById('total_payable');
                                        if (tp){ new MutationObserver(egoUpdateDue).observe(tp, {childList:true, characterData:true, subtree:true}); }
                                    }
                                    setInterval(egoUpdateDue, 1200); // احتياطي خفيف

                                    // كيباد المدفوع
                                    $('#ego_paid_amount').on('input', egoCalcChange);
                                    $('#ego_features_panel').on('click', '.ego-key', function(){
                                        var k = $(this).data('k'); var $inp = $('#ego_paid_amount');
                                        if (k === 'clear') { $inp.val(''); }
                                        else if (k === 'back') { $inp.val(($inp.val()||'').slice(0,-1)); }
                                        else { $inp.val(($inp.val()||'') + k); }
                                        egoCalcChange();
                                    });
                                    $('#ego_features_panel').on('click', '.ego-quick button[data-amt]', function(){
                                        var $inp = $('#ego_paid_amount');
                                        $inp.val(egoParse($inp.val()) + egoParse($(this).data('amt')));
                                        egoCalcChange();
                                    });

                                    // ---------- الخصومات والعروض (تعيد استخدام pos_total_row القديمة) ----------
                                    var egoDiscType = 'percentage';
                                    function egoSetType(t){ egoDiscType = t; $('#ego_disc_pct').toggleClass('active', t==='percentage'); $('#ego_disc_fixed').toggleClass('active', t==='fixed'); }
                                    function egoApplyDiscount(type, val){
                                        egoSetType(type);
                                        $('#discount_type').val(type);            // حقل النظام القديم
                                        egoWriteNum($('#discount_amount'), val);   // حقل النظام القديم
                                        $('#discount_amount').trigger('change');   // يطلق pos_total_row القديمة
                                        setTimeout(egoUpdateDue, 120);
                                    }
                                    $('#ego_disc_pct').on('click', function(){ egoSetType('percentage'); });
                                    $('#ego_disc_fixed').on('click', function(){ egoSetType('fixed'); });
                                    $('#ego_disc_value').on('change', function(){ egoApplyDiscount(egoDiscType, egoParse($(this).val())); });
                                    $('#ego_features_panel').on('click', '.ego_disc_quick', function(){ var v = egoParse($(this).data('val')); $('#ego_disc_value').val(v); egoApplyDiscount('percentage', v); });
                                    $('#ego_features_panel').on('click', '.ego_offer', function(){ var t = $(this).data('type'); var v = egoParse($(this).data('val')); $('#ego_disc_value').val(v); egoApplyDiscount(t, v); });

                                    // ---------- فحص السعر (يعرض فقط من /products/list دون إضافة للسلة) ----------
                                    var egoPCTimer = null;
                                    $('#ego_price_check_search').on('keyup', function(){
                                        var term = $(this).val();
                                        clearTimeout(egoPCTimer);
                                        if (term.length < 2){ $('#ego_price_check_results').html(''); return; }
                                        egoPCTimer = setTimeout(function(){
                                            var price_group = $('#price_group').length ? $('#price_group').val() : '';
                                            var search_fields = [];
                                            $('.search_fields:checked').each(function(i){ search_fields[i] = $(this).val(); });
                                            $('#ego_price_check_results').html('<div style="text-align:center;padding:20px;color:#6b7280"><i class="fas fa-spinner fa-spin"></i> جاري البحث...</div>');
                                            $.getJSON('/products/list', {
                                                price_group: price_group,
                                                location_id: $('#location_id').val(),
                                                term: term,
                                                not_for_selling: 0,
                                                search_fields: search_fields
                                            }, function(data){
                                                if (!data || !data.length){ $('#ego_price_check_results').html('<div style="text-align:center;padding:20px;color:#b91c1c">لا توجد نتائج</div>'); return; }
                                                var html = '';
                                                data.forEach(function(it){
                                                    var price = it.variation_group_price ? it.variation_group_price : it.selling_price;
                                                    var name = it.name + (it.type === 'variable' && it.variation ? ' - ' + it.variation : '');
                                                    var stock = (it.enable_stock == 1)
                                                        ? '<span style="color:' + (it.qty_available > 0 ? '#047857' : '#b91c1c') + '">المتوفر: ' + (it.qty_available || 0) + '</span>'
                                                        : '<span style="color:#6b7280">غير محدود</span>';
                                                    html += '<div style="display:flex;justify-content:space-between;align-items:center;border:1px solid #eef2f7;border-radius:12px;padding:10px;margin-bottom:8px">'
                                                          +   '<div><div style="font-weight:700">' + name + '</div><div style="font-size:12px;color:#6b7280">' + (it.sub_sku || '') + ' • ' + stock + '</div></div>'
                                                          +   '<div style="font-weight:800;font-size:18px;color:#4f46e5">' + price + '</div>'
                                                          + '</div>';
                                                });
                                                $('#ego_price_check_results').html(html);
                                            }).fail(function(){ $('#ego_price_check_results').html('<div style="text-align:center;padding:20px;color:#b91c1c">تعذّر جلب البيانات</div>'); });
                                        }, 400);
                                    });
                                    $('#ego_price_check_modal').on('shown.bs.modal', function(){ $('#ego_price_check_search').val('').focus(); $('#ego_price_check_results').html(''); });

                                    // ---------- أزرار الدفع السريعة: تستدعي أزرار النظام الموجودة فعلاً ----------
                                    $('#ego_features_panel').on('click', '.ego-pay[data-ego-target]', function(){
                                        var sel = $(this).data('ego-target');
                                        var $btn = $(sel).filter(':visible').first();
                                        if (!$btn.length) { $btn = $(sel).first(); }
                                        if ($btn.length && $btn[0]) { $btn[0].click(); }
                                        else if (typeof toastr !== 'undefined') { toastr.error('هذا الخيار غير مفعّل في الإعدادات'); }
                                    });
                                    // زر "الخصم": ينقل التركيز إلى لوحة الخصومات الجديدة
                                    $('#ego_pay_discount').on('click', function(){
                                        var el = document.getElementById('ego_disc_value');
                                        if (el) { el.scrollIntoView({behavior:'smooth', block:'center'}); el.focus(); }
                                    });
                                    // إخفاء أي زر دفع سريع هدفه غير موجود (مُعطّل من الإعدادات/الصلاحيات)
                                    $('.ego-pay[data-ego-target]').each(function(){
                                        if (!$($(this).data('ego-target')).length) { $(this).hide(); }
                                    });

                                    // منع زر Enter داخل حقول المزايا من إرسال فاتورة البيع بالخطأ
                                    $('#ego_features_panel, #ego_price_check_modal').on('keydown', 'input', function(e){
                                        if (e.key === 'Enter' || e.keyCode === 13) { e.preventDefault(); }
                                    });

                                    // تحديث أولي بعد جاهزية الصفحة
                                    $(function(){ setTimeout(egoUpdateDue, 300); });
                                })();
                                </script>
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





    </script>

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

//////// js for search product  009 
$(document).ready(function() {
    
    // دالة التحقق قبل الإرسال
     function executeStockLookup() {
        var sku = $('#sku_input_stock').val().trim();
        var locationId = $('#location_filter_stock').val(); // ✅ جلب قيمة الفرع
        
        if (sku) {
            fetchProductStock(sku, locationId);
        } else {
            toastr.error('يرجى إدخال الباركود أو الـ SKU');
        }
    }

       // عند الضغط على زر البحث
    $(document).on('click', '#btn_execute_stock_search', function() {
        executeStockLookup();
    });

    // عند الضغط على Enter داخل حقل الإدخال
    $(document).on('keydown', '#sku_input_stock', function(e) {
        if (e.which == 13) { 
            e.preventDefault();
            executeStockLookup();
        }
    });

        // تصفير المودال والتركيز عند الفتح
    $(document).on('shown.bs.modal', '#StockSearchModal', function () {
        $('#sku_input_stock').val('');
        $('#location_filter_stock').val(''); // ✅ إعادة لـ "كل الفروع"
        $('#stock_qty_results_area').html('<p class="text-center text-muted" style="margin-top:40px;">بانتظار المسح...</p>');
        setTimeout(function() { $('#sku_input_stock').focus(); }, 400);
    });
    // الدالة الأساسية لجلب البيانات
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
});
//////// js for search product 009  
document.addEventListener('DOMContentLoaded', function () {
    let buffer = '';
    let lastTime = 0;
    const productCache = new Map();

    document.addEventListener('keydown', function(e) {
        if (e.target.tagName === 'TEXTAREA') return;

        const now = Date.now();
        if (now - lastTime > 100) buffer = '';
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