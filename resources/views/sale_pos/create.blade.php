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
                        <div class="tw-shadow-[rgba(17,_17,_26,_0.1)_0px_0px_16px] tw-rounded-2xl tw-bg-white tw-p-2" style="height: calc(100vh - 96px); max-height: calc(100vh - 96px); display: flex; flex-direction: column;">
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

                               
                                {{-- 🆕 تبويبات تعدّد السلال (سلة 1 / سلة 2 / +) — لقطة/استرجاع من جهة العميل --}}
                                <div id="ego_cart_tabs">
                                    <button type="button" id="ego_cart_add" title="سلة جديدة"><i class="fas fa-plus"></i></button>
                                    <div id="ego_cart_tablist"></div>
                                </div>

                                <div class="pos-form-container" id="ego_cart_scroll" style="flex: 1 1 auto; min-height: 0; width: 100%; overflow-y: auto; overflow-x: hidden;">
                                    @include('sale_pos.partials.pos_form')
                                </div>
                                {{-- 🆕 أزرار تحكم بتمرير السلة (بدل السكرول الجانبي) --}}
                                <div id="ego_cart_scroll_ctrl">
                                    <button type="button" class="ego-scroll-btn" data-dir="up" title="أعلى"><i class="fas fa-caret-up"></i></button>
                                    <button type="button" class="ego-scroll-btn" data-dir="down" title="أسفل"><i class="fas fa-caret-down"></i></button>
                                </div>

                                {{-- 🆕 صف إجراءات السلة أسفل المنتجات: ⋮ (العروض/النقاط) | خصم | ملاحظة | توصيل --}}
                                <div id="ego_cart_actions">
                                    <span class="ego-more-wrap" style="position:relative;display:inline-flex">
                                        <button type="button" id="ego_more_btn" class="ego-ca-more" title="استرداد نقاط / كشف حساب / مصاريف"><i class="fas fa-ellipsis-v"></i></button>
                                        {{-- 🆕 قائمة منسدلة بنفس مكان الزر (لا نافذة بمنتصف الصفحة) --}}
                                        <div id="ego_more_dropdown" class="ego-more-dd"></div>
                                    </span>
                                    @if(empty($pos_settings['disable_discount']))
                                    <button type="button" class="ego-ca-btn ego-ca-toggleable ego-ca-discount" data-toggle="modal" data-target="#ego_discount_modal"><i class="fas fa-percent"></i> خصم</button>
                                    @endif
                                    <button type="button" id="ego_btn_note" class="ego-ca-btn ego-ca-toggleable ego-ca-note" data-toggle="modal" data-target="#ego_note_modal"><i class="fas fa-sticky-note"></i> ملاحظة</button>
                                    <button type="button" class="ego-ca-btn ego-ca-toggleable ego-ca-shipping" data-toggle="modal" data-target="#posShippingModal"><i class="fas fa-truck"></i> توصيل</button>
                                    <button type="button" class="ego-ca-btn ego-ca-cancel" data-ego-target="#pos-cancel"><i class="fas fa-times-circle"></i> إلغاء</button>
                                </div>

                                <div class="pos-totals-section" style="margin-top: auto; flex-shrink: 0; width: 100%; background: #fff; padding-top: 10px;">
                                    @include('sale_pos.partials.pos_form_totals')
                                    @include('sale_pos.partials.payment_modal')
                                </div>

                                {{-- 🆕 ملخص الدفع (أسفل المجاميع): الكلي / المدفوع (+أزرار سريعة) / الباقي --}}
                                {{-- 🆕 صندوق المجاميع — يبقى أسفل أسطر السلة (يسار) --}}
                                <div id="ego_pay_summary">
                                    <div class="ego-tot-list">
                                        <div class="ego-tot-row"><span>المجموع</span><b id="ego_t_sub">0.00</b></div>
                                        <div class="ego-tot-row ego-tot-disc"><span>الخصم / التوفير</span><b id="ego_t_disc">0.00</b></div>
                                        <div class="ego-tot-row"><span>الإجمالي</span><b id="ego_t_total">0.00</b></div>
                                        {{-- 🆕 المدفوع والباقي فوق المستحق (نُقلا من الكيباد) --}}
                                        <div class="ego-tot-row ego-tot-paid"><span><i class="fas fa-money-bill-wave"></i> المدفوع</span><input type="text" id="ego_paid_amount" placeholder="0.00" autocomplete="off" inputmode="decimal"></div>
                                        <div class="ego-tot-row ego-tot-change"><span><i class="fas fa-hand-holding-usd"></i> الباقي</span><b id="ego_change_amount">0.00</b></div>
                                        <div class="ego-tot-row ego-tot-due"><span>المستحق</span><b class="ego-due-val">0.00</b></div>
                                    </div>
                                </div>

                                {{-- 🆕 صندوق طرق الدفع — يُنقل داخل نافذة التسديد عبر JS (data-dismiss يُغلق النافذة ثم يُنفّذ الدفع) --}}
                                <div id="ego_keypad_box">
                                    <div class="ego-kb-pay">
                                        <button type="button" class="ego-pay ego-pay-cash" id="ego_btn_cash" data-dismiss="modal"><i class="fas fa-money-bill-wave"></i> كاش</button>
                                        <button type="button" class="ego-pay ego-pay-card" data-dismiss="modal"><i class="fas fa-credit-card"></i> بطاقة</button>
                                        <button type="button" class="ego-pay ego-pay-credit" data-ego-target='[data-pay_method="credit_sale"]' data-dismiss="modal"><i class="fas fa-user-friends"></i> ذمم</button>
                                        <button type="button" class="ego-pay ego-pay-multi" data-ego-target='#pos-finalize' data-dismiss="modal"><i class="fas fa-money-check-alt"></i> دفع متعدد</button>
                                    </div>
                                </div>

                                {{-- 🆕 شريط أسفل الصفحة: ملاحظة (يمين) • أسهم تتحكّم بتمرير المنتجات (وسط) • عدّاد كنص (يسار) --}}
                                <div id="ego_cart_bar">
                                    {{-- 🆕 (أُزيلت ملاحظة من هنا — انتقلت لصف الإجراءات أسفل المنتجات) --}}
                                    {{-- 🆕 زر التسديد: يفتح نافذة الدفع (تحوي المجاميع + أزرار الدفع) --}}
                                    <button type="button" id="ego_btn_checkout" class="ego-cb-pay"><i class="fas fa-cash-register"></i> تسديد</button>
                                    {{-- 🆕 (أُزيل شعار SST من هنا — انتقل إلى الشريط العلوي) --}}
                                    <div class="ego-cb-counts">
                                        <span>عدد الأسطر: <b id="ego_lines_count">0</b></span>
                                        <span>إجمالي الكمية: <b id="ego_qty_count">0</b></span>
                                    </div>
                                </div>

                                {{-- ============================================================= --}}
                                {{-- 🆕 قسم المزايا الجديدة (فحص السعر • حساب الباقي • الخصومات والعروض) --}}
                                {{-- أُضيف بالكامل داخل نفس الملف دون تعديل أي كود قديم. خلفية بيضاء وأيقونات ملوّنة. --}}
                                {{-- ============================================================= --}}
                                <div id="ego_features_panel" class="tw-mt-3" style="direction: rtl;">
                                    <style>
                                        #ego_features_panel{display:none}

                                        /* صف الدفع: المجاميع + (المدفوع/الباقي/الطرق) جنب بعض — دائماً */
                                        #ego_pay_wrap{display:flex;gap:10px;align-items:stretch;flex-wrap:wrap;flex-shrink:0}
                                        #ego_pay_wrap > #ego_pay_summary{flex:1;min-width:230px;border-top:0;padding:0}
                                        #ego_pay_wrap > #ego_keypad_box{flex:1.2;min-width:280px;margin-top:0}
@if(empty($pos_settings['hide_product_suggestion']))
                                        /* 🆕 ترتيب الصفحة (فقط عند إظهار اقتراح المنتجات): المنتجات يميناً، السلة يساراً */
                                        /* 🆕 السلة (pos_main_column) يمين، والأصناف (pos_side_column) يسار */
                                        #pos_side_column{order:1 !important; overflow:visible !important; width:44% !important}
                                        #pos_main_column{order:0 !important; width:56% !important}
                                        /* 🆕 إزالة الفراغ العلوي ليبدأ المحتوى من أعلى الصفحة */
                                        .content.no-print{padding-top:4px !important}
                                        #pos_flexible_container{margin-top:0 !important}
                                        #pos_main_column, #pos_side_column{margin-top:0 !important}
                                        #pos_side_column > div:first-child{height:calc(100vh - 96px) !important; max-height:calc(100vh - 96px) !important}
                                        /* عند ضغط "إخفاء المنتجات": السلة تمتد على كامل الصفحة */
                                        body.ego-cart-full #pos_side_column{display:none !important}
                                        body.ego-cart-full #pos_main_column{width:100% !important}
                                        @media (max-width:1100px){#pos_side_column{width:42% !important}#pos_main_column{width:58% !important}}
                                        /* 🆕 منع تمرير الصفحة نهائياً — كل شيء في view واحد (التمرير داخلي فقط) */
                                        html, body{overflow:hidden !important; height:100% !important}
                                        .content.no-print{overflow:hidden !important}
                                        #pos_side_column > div:first-child{overflow-y:auto !important}
@endif

                                        /* 🆕 صندوق الكيباد + المدفوع أسفل المنتجات (يمين) */
                                        #ego_keypad_box{margin-top:8px;background:#fff;border:1px solid #e6eaef;border-radius:12px;padding:7px;box-shadow:0 4px 12px rgba(17,17,26,.06)}
                                        #ego_keypad_box .ego-kb-head{display:flex;gap:8px;margin-bottom:8px}
                                        #ego_keypad_box .ego-kb-paid,#ego_keypad_box .ego-kb-change{flex:1;display:flex;align-items:center;justify-content:space-between;gap:8px;border-radius:10px;padding:8px 12px;border:1px solid}
                                        #ego_keypad_box .ego-kb-paid{background:#eff6ff;border-color:#bfdbfe}
                                        #ego_keypad_box .ego-kb-change{background:#ecfdf5;border-color:#a7f3d0}
                                        #ego_keypad_box .ego-kb-paid .lbl{font-size:13px;font-weight:700;color:#1d4ed8;white-space:nowrap}
                                        #ego_keypad_box .ego-kb-change .lbl{font-size:13px;font-weight:700;color:#065f46;white-space:nowrap}
                                        #ego_keypad_box .ego-kb-paid input{width:92px;border:1px solid #93c5fd;border-radius:8px;padding:5px;text-align:center;font-size:18px;font-weight:800;color:#1d4ed8;background:#fff}
                                        #ego_keypad_box .ego-kb-change b{font-size:20px;font-weight:800;color:#047857}
                                        #ego_keypad_box .ego-kb-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:8px}
                                        #ego_keypad_box .ego-kb-grid .ego-key{border:none;border-radius:10px;padding:14px 0;font-size:20px;font-weight:800;background:#f1f5f9;color:#0f172a;cursor:pointer;transition:.15s}
                                        #ego_keypad_box .ego-kb-grid .ego-key:hover{background:#e2e8f0}
                                        #ego_keypad_box .ego-kb-grid .ego-back{background:#fef3c7;color:#92400e}
                                        #ego_keypad_box .ego-kb-quick{display:flex;gap:6px;margin-top:8px;flex-wrap:wrap}
                                        #ego_keypad_box .ego-kb-quick button{flex:1;min-width:14%;border:1px solid #34d399;background:#ecfdf5;color:#047857;border-radius:8px;padding:5px 0;font-weight:700;cursor:pointer;font-size:13px}
                                        #ego_keypad_box .ego-kb-quick button:hover{background:#34d399;color:#fff}
                                        #ego_keypad_box .ego-kb-quick .ego-clear{border-color:#fca5a5;background:#fee2e2;color:#b91c1c}
                                        /* طرق الدفع داخل صندوق الكيباد */
                                        #ego_keypad_box .ego-kb-pay{display:grid;grid-template-columns:repeat(2,1fr);gap:12px;margin-top:0}
                                        /* 🆕 أزرار طرق الدفع أكبر */
                                        #ego_keypad_box .ego-kb-pay .ego-pay{border:1px solid #e6eaef;background:#fff;border-radius:14px;padding:22px 10px;font-weight:800;font-size:17px;color:#334155;cursor:pointer;display:flex;flex-direction:column;align-items:center;justify-content:center;gap:8px;transition:.15s}
                                        #ego_keypad_box .ego-kb-pay .ego-pay i{font-size:30px}
                                        #ego_keypad_box .ego-kb-pay .ego-pay:hover{background:#f8fafc;transform:translateY(-1px)}
                                        #ego_keypad_box .ego-kb-pay .ego-pay-cash i{color:#16a34a}
                                        #ego_keypad_box .ego-kb-pay .ego-pay-card i{color:#2563eb}
                                        #ego_keypad_box .ego-kb-pay .ego-pay-credit i{color:#0891b2}
                                        #ego_keypad_box .ego-kb-pay .ego-pay-multi i{color:#0f172a}
                                        #ego_keypad_box .ego-kb-pay .ego-pay-cancelbtn{color:#dc2626;border-color:#fecaca}
                                        #ego_keypad_box .ego-kb-pay .ego-pay-cancelbtn i{color:#dc2626}
                                        #ego_keypad_box .ego-kb-pay .ego-pay-cancelbtn:hover{background:#fef2f2}

                                        /* عناصر داخل النوافذ المنبثقة */
                                        .ego-modal .modal-content{border-radius:16px;overflow:hidden}
                                        .ego-modal .modal-header{color:#fff}
                                        .ego-modal .ego-input{width:100%;border:1px solid #d1d5db;border-radius:10px;padding:8px 10px;font-size:16px;text-align:center;font-weight:700;background:#fff}
                                        .ego-modal .ego-keypad{display:grid;grid-template-columns:repeat(3,1fr);gap:8px;margin-top:10px}
                                        .ego-modal .ego-key{border:none;border-radius:12px;padding:16px 0;font-size:20px;font-weight:800;background:#f1f5f9;color:#0f172a;cursor:pointer;transition:.15s}
                                        .ego-modal .ego-key:hover{background:#e2e8f0}
                                        .ego-modal .ego-key.ego-clear{background:#fee2e2;color:#b91c1c}
                                        .ego-modal .ego-key.ego-back{background:#fef3c7;color:#92400e}
                                        .ego-modal .ego-quick{display:flex;flex-wrap:wrap;gap:6px;margin-top:10px}
                                        .ego-modal .ego-quick button{border:1px solid #34d399;background:#ecfdf5;color:#047857;border-radius:999px;padding:6px 14px;font-weight:700;cursor:pointer;font-size:14px}
                                        .ego-modal .ego-quick button:hover{background:#34d399;color:#fff}
                                        .ego-modal .ego-due{background:#0f172a;color:#fff;border-radius:12px;padding:10px;text-align:center}
                                        .ego-modal .ego-due .v{font-size:22px;font-weight:800}
                                        .ego-modal .ego-change{background:#ecfdf5;border:1px dashed #10b981;border-radius:12px;padding:10px;text-align:center;margin-top:10px}
                                        .ego-modal .ego-change .v{font-size:24px;font-weight:800;color:#047857}
                                        .ego-modal .ego-toggle{display:flex;gap:6px;margin-bottom:10px}
                                        .ego-modal .ego-toggle button{flex:1;border:1px solid #d1d5db;background:#fff;border-radius:10px;padding:8px;font-weight:700;cursor:pointer}
                                        .ego-modal .ego-toggle button.active{background:#2563eb;color:#fff;border-color:#2563eb}
                                        .ego-modal .ego-disc-quick,.ego-modal .ego-offers{display:flex;flex-wrap:wrap;gap:6px;margin-top:10px}
                                        .ego-modal .ego-disc-quick button{border:1px solid #93c5fd;background:#eff6ff;color:#1d4ed8;border-radius:999px;padding:6px 14px;font-weight:700;cursor:pointer}
                                        .ego-modal .ego-disc-quick button:hover{background:#3b82f6;color:#fff}
                                        .ego-modal .ego-offers button{border:1px solid #f59e0b;background:#fffbeb;color:#b45309;border-radius:10px;padding:6px 12px;font-weight:700;cursor:pointer}
                                        .ego-modal .ego-offers button:hover{background:#f59e0b;color:#fff}
                                        .ego-modal .ego-offers button.ego-offer-clear{border-color:#ef4444;background:#fef2f2;color:#b91c1c}

                                        /* 🆕 القائمة الجانبية تُنقل داخل نافذة "العمليات" (بدل ثبوتها يمين الشاشة) */
                                        #ego_side_panel{display:none;}                         /* مخفية حتى تُنقل للنافذة */
                                        body.ego-side-on #scrollable-container{padding-right:0 !important;}
                                        #ego_ops_body #ego_side_panel{display:flex !important;position:static !important;width:100% !important;max-height:none !important;right:auto !important;top:auto !important;z-index:auto !important;}
                                        /* زر "العمليات" (☰) داخل الشريط العلوي بجانب إغلاق الكاش */
                                        #ego_ops_btn.ego-ops-topbar{border:2px solid #e2e8f0;border-radius:8px;width:auto;min-width:38px;height:38px;padding:0 12px;font-weight:800;font-size:16px;color:#4f46e5;background:#fff;box-shadow:rgba(17,17,26,.1) 0px 0px 16px;cursor:pointer;display:inline-flex;align-items:center;justify-content:center;gap:6px;margin:0 4px}
                                        #ego_ops_btn.ego-ops-topbar:hover{background:#f5f3ff;border-color:#c7d2fe}
                                        #ego_side_panel::-webkit-scrollbar{width:6px}
                                        #ego_side_panel::-webkit-scrollbar-thumb{background:#cbd5e1;border-radius:6px}
                                        #ego_side_panel .ego-sp-section{background:#fff;border:1px solid #e6eaef;border-radius:14px;padding:10px;box-shadow:0 4px 12px rgba(17,17,26,.06)}
                                        #ego_side_panel .ego-sp-title{font-size:14px;font-weight:800;color:#0f172a;margin-bottom:10px;text-align:center;border-bottom:2px solid #e2e8f0;padding-bottom:6px}
                                        #ego_side_panel .ego-sp-row{display:flex;justify-content:space-between;align-items:center;font-weight:700;font-size:14px;padding:4px 2px}
                                        #ego_side_panel .ego-sp-total{color:#0f172a;border-bottom:1px dashed #e5e7eb;padding-bottom:8px}
                                        #ego_side_panel .ego-sp-total b{font-size:18px}
                                        #ego_side_panel .ego-sp-change{color:#047857;border-top:1px dashed #d1fae5;margin-top:8px;padding-top:8px}
                                        #ego_side_panel .ego-sp-change b{font-size:18px}
                                        #ego_side_panel .ego-sp-paid{margin:8px 0}
                                        #ego_side_panel .ego-sp-lbl{font-size:12px;color:#475569;font-weight:700}
                                        #ego_side_panel .ego-sp-input{width:100%;border:1px solid #2563eb;border-radius:10px;padding:8px;text-align:center;font-size:18px;font-weight:800;color:#1d4ed8;margin-top:4px;background:#fff}
                                        #ego_side_panel .ego-sp-quick{display:flex;flex-wrap:wrap;gap:4px;margin-top:8px}
                                        #ego_side_panel .ego-sp-quick button{flex:1;min-width:28%;border:1px solid #34d399;background:#ecfdf5;color:#047857;border-radius:8px;padding:6px 0;font-weight:700;cursor:pointer;font-size:12px}
                                        #ego_side_panel .ego-sp-quick button:hover{background:#34d399;color:#fff}
                                        #ego_side_panel .ego-sp-grid{display:grid;grid-template-columns:1fr 1fr;gap:6px}
                                        /* أزرار موحّدة بيضاء + أيقونات ملوّنة (مرتّبة، مو كل وحدة لون كامل) */
                                        #ego_side_panel .ego-pay,#ego_side_panel .ego-op{background:#fff;border:1px solid #e6eaef;border-radius:10px;padding:10px 4px;font-weight:700;font-size:12px;color:#334155;cursor:pointer;display:flex;flex-direction:column;align-items:center;gap:4px;transition:.15s}
                                        #ego_side_panel .ego-pay i,#ego_side_panel .ego-op i{font-size:18px;color:#475569}
                                        #ego_side_panel .ego-pay:hover,#ego_side_panel .ego-op:hover{background:#f8fafc;border-color:#cbd5e1;transform:translateY(-1px)}
                                        /* أيقونة كاش خضراء فقط (بدون خلفية خضراء) */
                                        #ego_side_panel .ego-pay-cash i{color:#16a34a}
                                        /* الإجراءات الحمراء فقط */
                                        #ego_side_panel .ego-op-cancel,#ego_side_panel .ego-op-logout{color:#dc2626;border-color:#fecaca}
                                        #ego_side_panel .ego-op-cancel i,#ego_side_panel .ego-op-logout i{color:#dc2626}
                                        /* لمسات لون خفيفة على الأيقونات فقط */
                                        #ego_side_panel .ego-pay-card i{color:#2563eb}
                                        #ego_side_panel .ego-pay-credit i{color:#0891b2}
                                        #ego_side_panel .ego-pay-multi i{color:#0f172a}
                                        #ego_side_panel .ego-op-draft i{color:#0ea5e9}
                                        /* 🆕 إخفاء الخصم القديم بالكامل (الزر + صف "الخصم (-)") — استُبدل بزر "خصم" الجديد المرتبط بإعداد تعطيل الخصم */
                                        #pos-edit-discount{display:none !important}
                                        .pos-totals-section td:has(#total_discount){display:none !important}
                                        #ego_side_panel .ego-op-gift i{color:#db2777}
                                        #ego_side_panel .ego-op-reserve i{color:#ea580c}
                                        #ego_side_panel .ego-op-disc i{color:#f59e0b}
                                        #ego_side_panel .ego-op-reserved i{color:#7c3aed}
                                        #ego_side_panel .ego-op-returns i{color:#0d9488}
                                        #ego_side_panel .ego-op-expense i{color:#dc2626}
                                        #ego_side_panel .ego-op-stock i{color:#0284c7}
                                        #ego_side_panel .ego-op-recent i{color:#0d9488}
                                        #ego_side_panel .ego-op-points i{color:#eab308}
                                        #ego_side_panel .ego-op.ego-active{background:#db2777;border-color:#db2777;color:#fff}
                                        #ego_side_panel .ego-op.ego-active i{color:#fff}
                                        #ego_side_panel .ego-op-drawer{flex-direction:row;gap:8px;font-size:15px;border:none;background:#16a34a;color:#fff;justify-content:center;padding:12px 6px}
                                        #ego_side_panel .ego-op-drawer i{color:#fff;font-size:20px}
                                        #ego_side_panel .ego-op-drawer:hover{background:#15803d}
                                        #ego_side_panel .ego-op-visa{flex-direction:row;gap:8px;font-size:15px;border:none;background:#2563eb;color:#fff;justify-content:center;padding:12px 6px}
                                        #ego_side_panel .ego-op-visa i{color:#fff;font-size:20px}
                                        #ego_side_panel .ego-op-visa:hover{background:#1d4ed8}
                                        #ego_side_panel .ego-op-toggle{flex-direction:row;gap:8px;font-size:15px;border:none;background:#7c3aed;color:#fff;justify-content:center;padding:12px 6px}
                                        #ego_side_panel .ego-op-toggle i{color:#fff;font-size:20px}
                                        #ego_side_panel .ego-op-toggle:hover{background:#6d28d9}
                                        #ego_side_panel .ego-op-print{flex-direction:row;gap:8px;font-size:15px;border:none;background:#0d9488;color:#fff;justify-content:center;padding:12px 6px}
                                        #ego_side_panel .ego-op-print i{color:#fff;font-size:20px}
                                        #ego_side_panel .ego-op-print:hover{background:#0f766e}
                                        #ego_side_panel .ego-op-ledger i{color:#9333ea}
                                        @media (max-width:1200px){#ego_side_panel{width:142px}}

                                        /* 🆕 نُخفي شريط أزرار النظام السفلي دائماً (مفصول عن ego-side-on الذي كان يحجز الفراغ) */
                                        .pos-form-actions{display:none !important}

                                        /* 🆕 شريط أسفل الصفحة (ملاحظة يمين / عدّاد يسار) */
                                        /* 🆕 نُبقي شريط السلة (ملاحظة/تسديد/العمليات/العدّادات) ظاهراً، ونُخفي غلاف الدفع المخصّص فقط (المجاميع في نافذة الدفع) */
                                        #ego_pay_wrap { display:none !important; }
                                        #ego_cart_bar{display:flex;align-items:center;justify-content:space-between;gap:10px;padding:8px 6px;border-top:1px solid #eef2f7;margin-top:6px}
                                        #ego_cart_bar .ego-cb-note{border:none;background:#16a34a;color:#fff;border-radius:10px;padding:8px 18px;font-weight:800;font-size:14px;cursor:pointer;display:flex;align-items:center;gap:6px}
                                        #ego_cart_bar .ego-cb-note:hover{filter:brightness(1.07)}
                                        /* 🆕 زر التسديد — أخضر وعريض (يملأ السطر) */
                                        #ego_cart_bar .ego-cb-pay{flex:1 1 auto;justify-content:center;border:none;background:#0d9488;color:#fff;border-radius:12px;padding:15px 30px;font-weight:800;font-size:19px;cursor:pointer;display:flex;align-items:center;gap:8px;box-shadow:0 5px 14px rgba(13,148,136,.32)}
                                        #ego_cart_bar .ego-cb-pay:hover{filter:brightness(1.06)}
                                        /* 🆕 درج الكاش + طباعة الفاتورة — بنمط ملاحظة (نظيف) في السطر السفلي */
                                        #ego_cart_bar .ego-cb-extra{width:auto !important;max-height:none !important;border:1.5px solid #e2e8f0 !important;background:#fff !important;color:#334155 !important;border-radius:10px !important;padding:9px 14px !important;font-weight:800 !important;font-size:13px !important;cursor:pointer;display:flex !important;flex-direction:row !important;align-items:center;justify-content:center !important;gap:6px;margin:0 !important;box-shadow:none !important}
                                        #ego_cart_bar .ego-cb-extra:hover{background:#f8fafc !important;border-color:#cbd5e1 !important}
                                        #ego_cart_bar .ego-cb-extra i{color:#0d9488 !important;font-size:15px !important}
                                        /* 🆕 صف إجراءات السلة (⋮ / خصم / ملاحظة / توصيل) أسفل المنتجات */
                                        #ego_cart_actions{display:flex;flex-direction:row;justify-content:flex-start;align-items:center;gap:8px;padding:8px 4px;border-top:1px solid #eef2f7;direction:rtl;flex-wrap:wrap}
                                        #ego_cart_actions .ego-ca-btn, #ego_cart_actions .ego-ca-more{border:1.5px solid #e2e8f0;background:#fff;color:#334155;border-radius:10px;padding:8px 14px;font-weight:800;font-size:13px;cursor:pointer;display:flex;align-items:center;gap:6px;transition:.15s}
                                        #ego_cart_actions .ego-ca-btn:hover, #ego_cart_actions .ego-ca-more:hover{background:#f8fafc;border-color:#cbd5e1}
                                        #ego_cart_actions .ego-ca-more{padding:8px 12px;color:#334155;font-size:16px}
                                        /* 🆕 أزرار السلة القابلة للإظهار عبر ⋮ (مخفية افتراضياً) */
                                        #ego_cart_actions .ego-ca-toggleable{display:none !important}
                                        #ego_cart_actions .ego-ca-toggleable.ego-on{display:flex !important}
                                        #ego_more_dropdown .ego-more-toggle{display:flex;align-items:center;justify-content:space-between;gap:10px;padding:9px 12px;border-radius:8px;font-weight:800;font-size:14px;color:#334155;cursor:pointer}
                                        #ego_more_dropdown .ego-more-toggle:hover{background:#f5f3ff}
                                        #ego_more_dropdown .ego-more-sep{border-top:1px solid #eef2f7;margin:6px 4px 2px;padding-top:6px;font-size:11px;font-weight:800;color:#94a3b8}
                                        #ego_cart_actions .ego-ca-btn i{color:#0d9488}
                                        /* 🆕 إخفاء خصم/عرض + إلغاء + قسم الخصومات الفارغ من نافذة العمليات */
                                        #ego_side_panel .ego-op-disc, #ego_side_panel .ego-op-cancel, #ego_sp_discounts{display:none !important;}
                                        /* 🆕 زر إظهار/إخفاء المنتجات — صغير */
                                        #ego_side_panel #ego_toggle_products.ego-op-small{width:auto !important;display:inline-flex !important;align-items:center;gap:6px;padding:7px 14px !important;font-size:12px !important;border-radius:10px !important;margin-top:6px}
                                        /* 🆕 زر إلغاء في صف الإجراءات (أحمر) */
                                        #ego_cart_actions .ego-ca-cancel{color:#dc2626;border-color:#fecaca}
                                        #ego_cart_actions .ego-ca-cancel i{color:#dc2626}
                                        #ego_cart_actions .ego-ca-cancel:hover{background:#fef2f2;border-color:#fca5a5}
                                        /* 🆕 قائمة ⋮ المنسدلة (تفتح بنفس مكان الزر للأعلى) */
                                        #ego_more_dropdown{position:absolute;bottom:100%;right:0;margin-bottom:6px;display:none;flex-direction:column;gap:6px;background:#fff;border:1px solid #e2e8f0;border-radius:12px;box-shadow:0 10px 30px rgba(2,6,23,.18);padding:8px;min-width:190px;z-index:9999}
                                        #ego_more_dropdown.open{display:flex}
                                        #ego_more_dropdown .ego-more-item{display:flex !important;width:100% !important;align-items:center;justify-content:flex-start;gap:8px;border:none !important;background:#fff !important;color:#334155 !important;border-radius:8px !important;padding:10px 12px !important;font-weight:800 !important;font-size:14px !important;cursor:pointer;margin:0 !important;box-shadow:none !important;max-height:none !important;text-align:right}
                                        #ego_more_dropdown .ego-more-item:hover{background:#f5f3ff !important}
                                        #ego_more_dropdown .ego-more-item i{font-size:16px;color:#0d9488 !important;width:20px;text-align:center}
                                        /* أزرار الدفع داخل نافذة التسديد */
                                        /* 🆕 طرق الدفع بتنسيق موحّد (نفس اللون والشكل لكل الأزرار) */
                                        #ego_pay_modal_methods .ego-kb-pay{display:grid;grid-template-columns:repeat(2,1fr);gap:12px}
                                        #ego_pay_modal_methods .ego-pay{border:1.5px solid #e2e8f0;background:#fff;border-radius:14px;padding:20px 10px;font-weight:800;font-size:16px;color:#334155;cursor:pointer;display:flex;flex-direction:column;align-items:center;justify-content:center;gap:8px;transition:.15s}
                                        #ego_pay_modal_methods .ego-pay i{font-size:26px;color:#0d9488}     /* أيقونة موحّدة اللون */
                                        #ego_pay_modal_methods .ego-pay:hover{background:#f0fdfa;border-color:#0d9488;transform:translateY(-1px)}
                                        /* زر طباعة الفاتورة داخل نافذة الدفع */
                                        #ego_pay_modal_print{width:100%;margin-top:14px;border:1.5px solid #cbd5e1;background:#f8fafc;color:#334155;border-radius:12px;padding:12px;font-weight:800;font-size:15px;cursor:pointer;display:flex;align-items:center;justify-content:center;gap:8px}
                                        #ego_pay_modal_print:hover{background:#eef2f7}
                                        #ego_cart_bar .ego-cb-toggle{border:1px solid #cbd5e1;background:#f8fafc;color:#334155;border-radius:10px;padding:8px 14px;font-weight:800;font-size:13px;cursor:pointer;display:flex;align-items:center;gap:6px}
                                        #ego_cart_bar .ego-cb-toggle:hover{background:#e2e8f0}
                                        #ego_cart_bar .ego-cb-toggle i{color:#2563eb}
                                        #ego_cart_bar .ego-cb-logo{height:80px;width:auto;object-fit:contain;opacity:.97}
                                        #ego_cart_bar .ego-cb-counts{display:flex;gap:18px;font-size:15px;font-weight:700;color:#334155}
                                        #ego_cart_bar .ego-cb-counts b{font-size:18px;color:#0f172a}
                                        /* 🆕 شريط أيقونات جانبي (الصنف/العلامات/المميزة) بجانب شبكة المنتجات */
                                        .ego-prod-wrap{display:flex;flex-direction:row-reverse;gap:10px;align-items:stretch;width:100%;flex:1 1 auto;min-height:0}
                                        /* 🆕 عمود المنتجات يملأ الارتفاع والتمرير داخله (بلا فراغ أسفل الصور) */
                                        #pos_side_column > div:first-child{display:flex !important;flex-direction:column !important;overflow:hidden !important}
                                        .ego-prod-wrap > .row:not(.tw-mb-1){flex:1 1 auto !important;min-height:0;overflow-y:auto;align-content:flex-start}
                                        .ego-prod-wrap > .row.tw-mb-1{flex:0 0 70px;max-width:70px;display:flex;flex-direction:column;gap:8px;margin:0}
                                        .ego-prod-wrap > .row.tw-mb-1 > div{width:70px !important;max-width:70px !important;padding:0 !important;margin:0 !important;float:none !important}
                                        .ego-prod-wrap > .row.tw-mb-1 > #product_service_div{display:none !important}
                                        .ego-prod-wrap > .row:not(.tw-mb-1){flex:1 1 auto;margin:0}
                                        /* الأزرار الثلاثة كأيقونات مربعة بنص صغير تحتها */
                                        .ego-prod-wrap #product_category_div .tw-dw-drawer-content > label,
                                        .ego-prod-wrap #product_brand_div .tw-dw-drawer-content > label,
                                        .ego-prod-wrap #ego_featured_tab{
                                            width:68px !important;height:62px !important;min-height:62px !important;border-radius:14px !important;
                                            font-size:9px !important;line-height:1.1 !important;font-weight:800 !important;
                                            display:flex !important;flex-direction:column !important;align-items:center !important;justify-content:center !important;
                                            gap:4px !important;padding:5px 3px !important;white-space:normal !important;text-align:center !important;
                                        }
                                        .ego-prod-wrap #product_category_div svg,
                                        .ego-prod-wrap #product_brand_div svg{width:24px !important;height:24px !important;margin:0 !important}
                                        .ego-prod-wrap #ego_featured_tab i{font-size:22px !important;margin:0 !important}
                                        /* 🆕 تحسين شكل السايد الجانبي للأصناف/العلامات (أنظف بطابع تركوازي) */
                                        .tw-dw-drawer-side .tw-dw-menu{background:#ffffff !important;padding:20px !important;box-shadow:-10px 0 28px rgba(2,6,23,.10) !important;border-radius:18px 0 0 18px}
                                        .tw-dw-drawer-side .category_heading, .tw-dw-drawer-side h3{color:#0d9488 !important;-webkit-text-fill-color:#0d9488 !important;background:none !important}
                                        .tw-dw-drawer-side .tw-dw-card{border:1.5px solid #e6eaef !important;border-radius:14px !important;transition:.18s !important;box-shadow:0 2px 6px rgba(17,17,26,.05) !important}
                                        .tw-dw-drawer-side .main-category-div:hover .tw-dw-card, .tw-dw-drawer-side .product_category:hover .tw-dw-card, .tw-dw-drawer-side .product_brand:hover .tw-dw-card{border-color:#0d9488 !important;box-shadow:0 8px 18px rgba(13,148,136,.18) !important;transform:translateY(-2px)}
                                        .tw-dw-drawer-side .tw-dw-btn-accent, .tw-dw-drawer-side .tw-dw-btn-primary{border-color:#0d9488 !important;color:#0d9488 !important}
                                        .ego-drawer-search{border:1.5px solid #e2e8f0 !important;border-radius:10px !important}
                                        .close-side-bar-category, .close-side-bar-brand{background:#fef2f2 !important;border-color:#fecaca !important;color:#dc2626 !important}
                                        /* 🆕 تصميم احترافي لبطاقات المنتجات والتبويبات */
                                        #product_list_body, #featured_products_box{gap:8px !important}
                                        #product_list_body .product_box, #featured_products_box .product_box{background:#fff;border:1px solid #e6eaef;border-radius:12px;overflow:hidden;cursor:pointer;transition:.18s;display:flex;flex-direction:column;box-shadow:0 2px 6px rgba(17,17,26,.05);height:100%;min-height:150px}
                                        #product_list_body .product_box:hover, #featured_products_box .product_box:hover{transform:translateY(-2px);box-shadow:0 8px 18px rgba(37,99,235,.16);border-color:#93c5fd}
                                        #product_list_body .image-container, #featured_products_box .image-container{width:100%;flex:1 1 auto;height:auto !important;min-height:82px;background-color:#f8fafc !important;background-size:contain !important;background-repeat:no-repeat !important;background-position:center !important;border-bottom:1px solid #f1f5f9}
                                        /* 🆕 إخفاء رسالة "لا يوجد منتجات لعرضها" */
                                        #product_list_body #no_products_found + .col-md-12, #featured_products_box #no_products_found + .col-md-12{display:none !important}
                                        #product_list_body .text_div, #featured_products_box .text_div{padding:5px 5px 7px;text-align:center;display:flex;flex-direction:column;gap:2px;flex:1}
                                        #product_list_body .text_div .text:first-child, #featured_products_box .text_div .text:first-child{font-weight:800 !important;color:#1f2937 !important;font-size:11px !important;line-height:1.25;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden;min-height:28px}
                                        #product_list_body .text_div small.text-muted, #featured_products_box .text_div small.text-muted{color:#94a3b8 !important;font-size:10px !important}
                                        #product_list_body .ego-price-badge, #featured_products_box .ego-price-badge{margin-top:auto;background:#ecfdf5;color:#047857;font-weight:800;font-size:12px;border-radius:7px;padding:3px 6px}
                                        #product_list_body .ego-stock-badge, #featured_products_box .ego-stock-badge{font-size:11px;font-weight:700;border-radius:6px;padding:2px 6px;display:inline-block}
                                        #product_list_body .ego-stock-in, #featured_products_box .ego-stock-in{background:#eff6ff;color:#1d4ed8}
                                        #product_list_body .ego-stock-out, #featured_products_box .ego-stock-out{background:#fef2f2;color:#b91c1c}
                                        /* 🆕 تنسيق زر التبويب فقط (داخل drawer-content) — لا يطال طبقة التغطية فلا شاشة بيضاء */
                                        #product_category_div .tw-dw-drawer-content label, #product_brand_div .tw-dw-drawer-content label{background-image:none !important;background:#ffffff !important;color:#1e293b !important;border:2px solid #e2e8f0 !important;box-shadow:0 4px 12px rgba(17,17,26,.06) !important;border-radius:14px !important;font-weight:800 !important}
                                        #product_category_div .tw-dw-drawer-content label{border-color:#c7d2fe !important}
                                        #product_brand_div .tw-dw-drawer-content label{border-color:#fbcfe8 !important}
                                        #product_category_div .tw-dw-drawer-content label:hover{background:#eef2ff !important;border-color:#6366f1 !important}
                                        #product_brand_div .tw-dw-drawer-content label:hover{background:#fdf2f8 !important;border-color:#db2777 !important}
                                        #product_category_div .tw-dw-drawer-content label svg{stroke:#6366f1 !important}
                                        #product_brand_div .tw-dw-drawer-content label svg{stroke:#db2777 !important}
                                        /* 🆕 إبقاء زر إضافة العميل (+) ملاصقاً لخانة العميل عند توسّع السلة */
                                        .input-group:has(.add_new_customer){display:flex !important;align-items:stretch;width:100%}
                                        .input-group:has(.add_new_customer) > .select2-container{flex:1 1 auto !important;width:auto !important;min-width:0}
                                        .input-group:has(.add_new_customer) > .input-group-addon,
                                        .input-group:has(.add_new_customer) > .input-group-btn{flex:0 0 auto;white-space:nowrap}

                                        /* تمرير عمودي واضح جنب المنتجات */
                                        #ego_cart_scroll .table-responsive{overflow:visible !important;border:0 !important}
                                        /* 🆕 سلة بتمرير عمودي واحد واضح فقط (بدون أفقي، وبدون سكرول مزدوج) */
                                        #ego_cart_scroll{max-height:calc(100vh - 250px) !important;overflow-y:scroll !important;overflow-x:hidden !important;overscroll-behavior:contain;scroll-behavior:smooth;scrollbar-width:auto;scrollbar-color:#64748b #e2e8f0}
                                        /* 🆕 رأس جدول البيع يبقى ثابتاً أثناء تمرير الأسطر */
                                        #ego_cart_scroll #pos_table thead th{position:sticky;top:0;z-index:3;background:#fff}
                                        #ego_cart_scroll::-webkit-scrollbar{width:20px}
                                        #ego_cart_scroll::-webkit-scrollbar-track{background:#e2e8f0;border-radius:10px}
                                        #ego_cart_scroll::-webkit-scrollbar-thumb{background:#64748b;border-radius:10px;border:3px solid #e2e8f0;min-height:60px}
                                        #ego_cart_scroll::-webkit-scrollbar-thumb:hover{background:#334155}
                                        /* منع التمرير الداخلي المزدوج: جدول السلة لا يمرّر بنفسه (يمرّر فقط الحاوية) */
                                        #ego_cart_scroll .table-responsive{overflow:visible !important;width:100% !important;max-height:none !important}
                                        /* عناوين الأعمدة لا تنكسر حرفاً حرفاً */
                                        #pos_table{width:100% !important}
                                        #pos_table th{white-space:nowrap}
                                        /* 🆕 تصغير وتنسيق مربع الكمية (الزرّان والحقل بمحاذاة وارتفاع موحّد) */
                                        #pos_table td .input-group{display:inline-flex !important;align-items:center;justify-content:center;flex-wrap:nowrap;width:auto;gap:3px}
                                        #pos_table td .input-group-btn{display:inline-flex !important;width:auto !important}
                                        #pos_table .quantity-up, #pos_table .quantity-down{height:40px;width:38px;padding:0 !important;display:inline-flex;align-items:center;justify-content:center;border-radius:8px;line-height:1}
                                        #pos_table .pos_quantity{height:40px;max-width:72px !important;text-align:center;padding:4px !important;border-radius:8px;font-size:15px;font-weight:700}
                                        /* عمود السعر شامل الضريبة */
                                        #pos_table .pos_unit_price_inc_tax{max-width:100px !important;height:40px;padding:4px 8px !important;border-radius:8px;text-align:center}
                                        /* تلوين أسطر السلة بالتناوب (أبيض/رمادي) */
                                        #pos_table tbody tr.product_row:nth-child(odd) > td{background:#ffffff !important}
                                        #pos_table tbody tr.product_row:nth-child(even) > td{background:#f1f5f9 !important}

                                        /* 🆕 تجاوب مع شاشات POS والكمبيوتر (أحجام مختلفة) لمنع التزاحم والتمرير الأفقي */
                                        @media (max-width:1500px){
                                            #ego_side_panel{width:188px}
                                            body.ego-side-on #scrollable-container{padding-right:202px !important}
                                            #ego_side_panel .ego-pay,#ego_side_panel .ego-op{font-size:11px;padding:9px 3px}
                                            #ego_side_panel .ego-pay i,#ego_side_panel .ego-op i{font-size:16px}
                                            #ego_side_panel .ego-sp-title{font-size:13px}
                                        }
                                        @media (max-width:1200px){
                                            #ego_side_panel{width:164px}
                                            body.ego-side-on #scrollable-container{padding-right:176px !important}
                                            #ego_pay_summary .ego-tot-row{font-size:13px}
                                            #ego_pay_summary .ego-tot-row b{font-size:15px}
                                            #ego_pay_summary .ego-tot-due b{font-size:19px}
                                            #ego_keypad_box .ego-kb-pay .ego-pay{font-size:15px;padding:16px 6px}
                                            #ego_keypad_box .ego-kb-pay .ego-pay i{font-size:26px}
                                        }
                                        @media (max-width:992px){
                                            #ego_side_panel{width:144px}
                                            body.ego-side-on #scrollable-container{padding-right:156px !important}
                                            #ego_side_panel .ego-sp-grid{grid-template-columns:1fr}
                                            #product_list_body .image-container,#featured_products_box .image-container{min-height:64px}
                                        }

                                        /* 🆕 ملخص الدفع تحت المنتجات (أكبر وأوضح) */
                                        #ego_pay_summary{display:flex;align-items:stretch;gap:12px;padding:10px 8px;flex-wrap:wrap;border-top:1px solid #eef2f7;flex-shrink:0}
                                        #ego_pay_summary .ego-ps-box{flex:1;min-width:130px;border-radius:12px;padding:10px 16px;display:flex;flex-direction:column;align-items:center;justify-content:center;gap:3px}
                                        #ego_pay_summary .ego-ps-box .lbl{font-size:14px;font-weight:700;opacity:.9}
                                        #ego_pay_summary .ego-ps-box .val{font-size:28px;font-weight:800}
                                        #ego_pay_summary .ego-ps-total{background:#f1f5f9;color:#0f172a;border:1px solid #e2e8f0}
                                        #ego_pay_summary .ego-ps-change{background:#ecfdf5;border:1px dashed #10b981;color:#047857}
                                        #ego_pay_summary .ego-ps-paid{flex:1.6;min-width:200px;display:flex;flex-direction:column;gap:8px;background:#f8fafc;border:1px solid #e6eaef;border-radius:12px;padding:10px 14px}
                                        #ego_pay_summary .ego-ps-paid .lbl{font-size:14px;font-weight:700;color:#475569;white-space:nowrap}
                                        #ego_pay_summary .ego-ps-input{width:100%;border:1px solid #2563eb;border-radius:10px;padding:8px;text-align:center;font-size:24px;font-weight:800;color:#1d4ed8;background:#fff}
                                        #ego_pay_summary .ego-ps-quick{display:flex;gap:6px;flex-wrap:wrap}
                                        #ego_pay_summary .ego-ps-quick button{flex:1;min-width:15%;border:1px solid #34d399;background:#ecfdf5;color:#047857;border-radius:8px;padding:7px 0;font-weight:700;cursor:pointer;font-size:14px}
                                        #ego_pay_summary .ego-ps-quick button:hover{background:#34d399;color:#fff}
                                        /* قائمة المجاميع المرتّبة (المجموع/الخصم/الإجمالي/المستحق) */
                                        #ego_pay_summary .ego-tot-list{flex:1.4;min-width:200px;display:flex;flex-direction:column;gap:1px;background:#fff;border:1px solid #e6eaef;border-radius:10px;padding:6px 12px}
                                        #ego_pay_summary .ego-tot-row{display:flex;justify-content:space-between;align-items:center;font-size:13px;font-weight:700;color:#334155;padding:2px 0}
                                        #ego_pay_summary .ego-tot-row b{font-size:14px;color:#0f172a}
                                        #ego_pay_summary .ego-tot-disc b{color:#dc2626}
                                        #ego_pay_summary .ego-tot-due{border-top:1px solid #e2e8f0;margin-top:2px;padding-top:4px}
                                        #ego_pay_summary .ego-tot-due span{font-size:14px;font-weight:800;color:#0f172a}
                                        #ego_pay_summary .ego-tot-due b{font-size:18px;color:#16a34a}
                                        #ego_pay_summary .ego-tot-change span{font-weight:800;color:#065f46}
                                        #ego_pay_summary .ego-tot-change b{font-size:16px;color:#047857}
                                        /* 🆕 صف المدفوع (إدخال) فوق المستحق */
                                        #ego_pay_summary .ego-tot-paid span{font-weight:800;color:#1d4ed8}
                                        /* 🆕 حقل "المدفوع" مُظلَّل بوضوح، والقيمة تُحدَّد بالأزرق تلقائياً عند التسديد */
                                        #ego_pay_summary .ego-tot-paid input{width:130px;border:2px solid #2563eb;border-radius:8px;padding:5px 8px;text-align:center;font-size:18px;font-weight:800;color:#1d4ed8;background:#fff;box-shadow:0 0 0 3px rgba(37,99,235,.15)}
                                        #ego_pay_summary .ego-tot-paid input:focus{outline:none;border-color:#1d4ed8;box-shadow:0 0 0 4px rgba(37,99,235,.3)}
                                        #ego_pay_summary .ego-tot-paid input::selection{background:#2563eb;color:#fff}
                                        #ego_pay_summary .ego-tot-paid input::-moz-selection{background:#2563eb;color:#fff}
                                        #ego_pay_summary .ego-tot-paid{background:#eff6ff;border:2px solid #bfdbfe;border-radius:10px;padding:4px 8px;margin-top:2px}
                                        #ego_pay_summary .ego-tot-change{background:#ecfdf5;border-radius:8px;padding:3px 6px}

                                        /* 🆕 إخفاء القائمة العلوية (pos-header) وإظهارها بزر ثابت (☰) */
                                        body.ego-pos-autohide .pos-header{position:fixed !important;top:-400px;left:0;right:0;z-index:1040;transition:top .28s ease;box-shadow:0 6px 16px rgba(0,0,0,.18);background:#fff}
                                        body.ego-pos-autohide .pos-header.ego-show{top:0}
                                        /* زر ثابت (3 شحطات) أعلى يمين الشاشة لإظهار/إخفاء القائمة العلوية */
                                        #ego_nav_toggle{position:fixed;top:8px;right:10px;z-index:1050;width:48px;height:42px;border:none;border-radius:12px;background:linear-gradient(135deg,#4f46e5,#7c3aed);color:#fff;font-size:19px;cursor:pointer;box-shadow:0 5px 14px rgba(79,70,229,.38);display:flex;align-items:center;justify-content:center}
                                        #ego_nav_toggle:hover{filter:brightness(1.08)}
                                        #ego_nav_toggle.active{background:#dc2626;box-shadow:0 5px 14px rgba(220,38,38,.38)}

                                        /* 🆕 المرحلة 1: القائمة الرئيسية (SST + ☰ + المستخدم) في الشريط العلوي */
                                        #ego_mainmenu_wrap{display:inline-flex;align-items:center;gap:10px;direction:ltr}
                                        #ego_mainmenu_wrap .ego-mm-logo{font-weight:900;font-size:21px;letter-spacing:1px;background:linear-gradient(135deg,#4f46e5,#0891b2);-webkit-background-clip:text;background-clip:text;color:transparent}
                                        .ego-mm-menuwrap{position:relative;display:inline-flex}
                                        #ego_mm_btn,.ego-mm-home,.ego-mm-user{height:38px;border:1.5px solid #e2e8f0;background:#fff;border-radius:10px;cursor:pointer;display:inline-flex;align-items:center;gap:6px;font-weight:800;color:#334155;padding:0 12px;text-decoration:none}
                                        #ego_mm_btn,.ego-mm-home{width:44px;justify-content:center;font-size:17px}
                                        .ego-mm-home i{color:#0d9488}
                                        /* 🆕 توحيد لون زرّي + (إضافة عميل / إضافة منتج) إلى نفس الأخضر */
                                        .add_new_customer .fa-plus-circle, .pos_add_quick_product .fa-plus-circle{color:#0d9488 !important}
                                        /* 🆕 توحيد لون زر الحفظ/التحديث في نافذتي التوصيل وإضافة عميل إلى التركوازي */
                                        #posShippingModalUpdate, .contact_modal button[type="submit"].tw-dw-btn-primary{background:#0d9488 !important;border-color:#0d9488 !important;color:#fff !important}
                                        #posShippingModalUpdate:hover, .contact_modal button[type="submit"].tw-dw-btn-primary:hover{filter:brightness(1.07)}
                                        #ego_mm_btn:hover,.ego-mm-user:hover{background:#f8fafc;border-color:#cbd5e1}
                                        .ego-mm-user i{color:#0d9488;font-size:18px}.ego-mm-user span{font-size:13px}
                                        .ego-mm-dd{position:absolute;top:100%;left:0;margin-top:6px;display:none;flex-direction:column;min-width:236px;background:#fff;border:1px solid #e2e8f0;border-radius:12px;box-shadow:0 12px 32px rgba(2,6,23,.18);padding:8px;z-index:99999;direction:rtl}
                                        .ego-mm-dd.open{display:flex}
                                        .ego-mm-item{display:flex;align-items:center;gap:10px;border:none;background:#fff;color:#334155;border-radius:8px;padding:11px 12px;font-weight:800;font-size:14px;cursor:pointer;text-align:right;width:100%}
                                        .ego-mm-item:hover{background:#f5f3ff}
                                        .ego-mm-item i:first-child{width:20px;text-align:center;color:#0d9488}
                                        .ego-mm-logout{color:#dc2626}.ego-mm-logout i{color:#dc2626 !important}
                                        .ego-mm-caret{margin-right:auto;font-size:12px;color:#94a3b8}
                                        .ego-mm-settings{display:none;flex-direction:column;gap:2px;background:#f8fafc;border-radius:8px;margin:2px 4px;padding:4px}
                                        .ego-mm-settings.open{display:flex}
                                        .ego-mm-srow{display:flex;align-items:center;justify-content:space-between;padding:9px 10px;margin:0;font-weight:700;font-size:13px;color:#334155;cursor:pointer}
                                        .ego-sw{position:relative;display:inline-block;width:42px;height:24px;flex:0 0 42px}
                                        .ego-sw input{opacity:0;width:0;height:0}
                                        .ego-sw-slider{position:absolute;inset:0;background:#cbd5e1;border-radius:24px;transition:.2s}
                                        .ego-sw-slider:before{content:"";position:absolute;width:18px;height:18px;right:3px;top:3px;background:#fff;border-radius:50%;transition:.2s}
                                        .ego-sw input:checked + .ego-sw-slider{background:#16a34a}
                                        .ego-sw input:checked + .ego-sw-slider:before{transform:translateX(-18px)}
                                        #ego_mainmenu_wrap .ego-mm-logo-img{height:58px;width:auto;object-fit:contain}
                                        /* 🆕 تبويبات تعدّد السلال */
                                        #ego_cart_tabs{display:flex;align-items:center;gap:8px;padding:6px 4px;border-bottom:1px solid #eef2f7;direction:rtl;flex-wrap:wrap}
                                        /* 🆕 إخفاء سكرول السلة + أزرار تمرير بسهمين */
                                        #ego_cart_scroll{scrollbar-width:none;-ms-overflow-style:none}
                                        #ego_cart_scroll::-webkit-scrollbar{display:none;width:0;height:0}
                                        #ego_cart_scroll_ctrl{display:flex;gap:10px;justify-content:center;padding:6px 0 2px;flex-shrink:0}
                                        .ego-scroll-btn{width:54px;height:30px;border:1.5px solid #e2e8f0;background:#fff;border-radius:9px;color:#0d9488;cursor:pointer;font-size:18px;display:inline-flex;align-items:center;justify-content:center;transition:.15s}
                                        .ego-scroll-btn:hover{background:#f0fdfa;border-color:#0d9488}
                                        #ego_cart_add{width:36px;height:34px;border:none;border-radius:9px;background:#0d9488;color:#fff;font-size:15px;cursor:pointer;display:flex;align-items:center;justify-content:center;flex:0 0 auto}
                                        #ego_cart_add:hover{filter:brightness(1.08)}
                                        #ego_cart_tablist{display:flex;align-items:center;gap:6px;flex-wrap:wrap}
                                        .ego-ct-tab{display:flex;align-items:center;gap:8px;background:#f1f5f9;border:1.5px solid #e2e8f0;border-radius:10px;padding:6px 12px;font-weight:800;font-size:13px;color:#475569;cursor:pointer}
                                        .ego-ct-tab.active{background:#fff;border-color:#16a34a;color:#0f172a;box-shadow:0 2px 8px rgba(22,163,74,.15)}
                                        .ego-ct-tab .ego-ct-x{color:#94a3b8;font-size:12px;cursor:pointer}
                                        .ego-ct-tab .ego-ct-x:hover{color:#dc2626}
                                        /* 🆕 إصلاح ظهور زر التسديد (خاصة في وضع ملء الشاشة) — تثبيت الشريط أسفل عمود السلة */
                                        #ego_cart_bar{position:sticky;bottom:0;background:#fff;z-index:6}
                                    </style>

                                </div>

                                {{-- 🆕 القائمة الجانبية المنظّمة — تستبدل كل أزرار الأسفل (الكلي/المدفوع/الباقي ثم الدفع ثم العمليات ثم الخصومات) --}}
                                <div id="ego_side_panel">
                                    {{-- 1) الخصومات (فوق) — أصبح فارغاً (انتقلت أزراره لصف الإجراءات و⋮) فنُخفيه --}}
                                    <div class="ego-sp-section" id="ego_sp_discounts">
                                        <div class="ego-sp-title">الخصومات</div>
                                        <div class="ego-sp-grid">
                                            @if($is_discount_enabled)
                                            <button type="button" class="ego-op ego-op-disc" data-toggle="modal" data-target="#ego_discount_modal"><i class="fas fa-percent"></i> خصم/عرض</button>
                                            @endif
                                            <button type="button" class="ego-op ego-op-points" id="ego_btn_points" data-toggle="modal" data-target="#posEditDiscountModal"><i class="fas fa-star"></i> استبدال نقاط</button>
                                            @can('expense.add')
                                            <button type="button" class="ego-op ego-op-expense" id="ego_btn_expense"><i class="fas fa-minus-circle"></i> إضافة مصاريف</button>
                                            @endcan
                                            @can('enable_customer_ledger')
                                            <button type="button" class="ego-op ego-op-ledger" id="ego_btn_ledger"><i class="fas fa-book"></i> كشف حساب</button>
                                            @endcan
                                        </div>
                                    </div>

                                    {{-- 2) العمليات (وسط) --}}
                                    <div class="ego-sp-section">
                                        <div class="ego-sp-title">العمليات</div>
                                        <div class="ego-sp-grid">
                                            @if(!Gate::check('disable_draft') || auth()->user()->can('superadmin') || auth()->user()->can('admin'))
                                            <button type="button" class="ego-op ego-op-draft" data-ego-target='#pos-draft'><i class="fas fa-save"></i> مسودة</button>
                                            @endif
                                            <button type="button" class="ego-op ego-op-gift" id="ego_btn_gift"><i class="fas fa-gift"></i> فاتورة هدية</button>
                                            <button type="button" class="ego-op ego-op-reserve" id="ego_btn_reserve"><i class="fas fa-thumbtack"></i> حجز</button>
                                            <button type="button" class="ego-op ego-op-cancel" data-ego-target='#pos-cancel'><i class="fas fa-times-circle"></i> إلغاء</button>
                                            @can('enable_search_quantity')
                                            <button type="button" class="ego-op ego-op-stock" id="ego_btn_stock" data-toggle="modal" data-target="#StockSearchModal"><i class="fas fa-boxes"></i> بحث مخزون</button>
                                            @endcan
                                            <button type="button" class="ego-op ego-op-returns" id="ego_btn_return_barcode" data-toggle="modal" data-target="#returnSearchModal"><i class="fas fa-barcode"></i> إرجاع بالباركود</button>
                                            <button type="button" class="ego-op ego-op-recent" data-ego-target="#recent-transactions"><i class="fas fa-history"></i> العمليات الأخيرة</button>
                                            <button type="button" class="ego-op ego-op-logout" id="ego_btn_logout"><i class="fas fa-sign-out-alt"></i> خروج</button>
                                        </div>
                                    </div>

                                    {{-- 🆕 فتح درج الكاش + إرسال الفيزا — أيقونات مستقلة آخر القائمة تستدعي أزرار النظام الأصلية --}}
                                    @can('open_cash_drawer')
                                    <button type="button" class="ego-op ego-op-drawer" id="ego_btn_drawer" style="width:100%"><i class="fas fa-cash-register"></i> فتح درج الكاش</button>
                                    @endcan
                                    @can('sell.send_to_visa')
                                    <button type="button" class="ego-op ego-op-visa" data-ego-target="#pay_card_full" style="width:100%"><i class="fas fa-credit-card"></i> إرسال للفيزا</button>
                                    @endcan
                                    @if(empty($pos_settings['hide_product_suggestion']))
                                    <button type="button" class="ego-op ego-op-toggle ego-op-small" id="ego_toggle_products"><i class="fas fa-th-large"></i> <span class="lbl">إخفاء المنتجات</span></button>
                                    @endif
                                    {{-- 🆕 زر طباعة الفاتورة (طباعة يدوية عند الطلب — لا طباعة تلقائية) --}}
                                    <button type="button" class="ego-op ego-op-print" id="ego_btn_print" style="width:100%"><i class="fas fa-print"></i> طباعة الفاتورة</button>
                                </div>


                                {{-- 🆕 نافذة الخصومات والعروض --}}
                                <div class="modal fade ego-modal" id="ego_discount_modal" tabindex="-1" role="dialog" aria-hidden="true">
                                    <div class="modal-dialog" role="document" style="direction:rtl">
                                        <div class="modal-content">
                                            <div class="modal-header" style="background:linear-gradient(135deg,#f59e0b,#d97706)">
                                                <button type="button" class="close" data-dismiss="modal" aria-label="Close" style="color:#fff;opacity:.9"><span aria-hidden="true">&times;</span></button>
                                                <h4 class="modal-title"><i class="fas fa-percent"></i> الخصومات والعروض</h4>
                                            </div>
                                            <div class="modal-body">
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
                                                <div style="font-size:12px;color:#6b7280;margin-top:14px"><i class="fas fa-gift" style="color:#ef4444"></i> عروض جاهزة</div>
                                                <div class="ego-offers">
                                                    <button type="button" class="ego_offer" data-type="percentage" data-val="25">عرض 25%</button>
                                                    <button type="button" class="ego_offer" data-type="percentage" data-val="50">تخفيضات 50%</button>
                                                    <button type="button" class="ego_offer ego-offer-clear" data-type="fixed" data-val="0"><i class="fas fa-times"></i> إلغاء الخصم</button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                {{-- 🆕 نافذة الملاحظة (تُحفظ مع الفاتورة عبر حقل sale_note الأصلي) --}}
                                <div class="modal fade ego-modal" id="ego_note_modal" tabindex="-1" role="dialog" aria-hidden="true">
                                    <div class="modal-dialog" role="document" style="direction:rtl">
                                        <div class="modal-content">
                                            <div class="modal-header" style="background:linear-gradient(135deg,#0d9488,#0f766e)">
                                                <button type="button" class="close" data-dismiss="modal" aria-label="Close" style="color:#fff;opacity:.9"><span aria-hidden="true">&times;</span></button>
                                                <h4 class="modal-title"><i class="fas fa-sticky-note"></i> ملاحظة الفاتورة</h4>
                                            </div>
                                            <div class="modal-body">
                                                <textarea id="ego_note_text" class="ego-input" style="text-align:right;height:120px" placeholder="اكتب ملاحظة تظهر مع الفاتورة..."></textarea>
                                                <button type="button" id="ego_note_save" class="ego-pricecheck-btn" data-dismiss="modal" style="margin-top:12px;width:100%;border:none;border-radius:12px;padding:10px;font-weight:800;color:#fff;background:#0d9488;cursor:pointer"><i class="fas fa-check"></i> حفظ الملاحظة</button>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                {{-- 🆕 ملخص وردية المستخدم (يفتح بالضغط على اسم المستخدم في الشريط العلوي) --}}
                                @php
                                    $egoUserSummary = null;
                                    try {
                                        $egoReg = \App\CashRegister::where('user_id', auth()->id())->where('status', 'open')->orderByDesc('id')->first();
                                        if ($egoReg) {
                                            $egoRd = app(\App\Utils\CashRegisterUtil::class)->getRegisterDetails($egoReg->id);
                                            $egoInvCount = \App\Transaction::where('business_id', $egoReg->business_id)
                                                ->where('created_by', auth()->id())->where('type', 'sell')->where('status', 'final')
                                                ->where('created_at', '>=', $egoRd->open_time)->count();
                                            $egoUserSummary = [
                                                'name' => trim((auth()->user()->first_name ?? '') . ' ' . (auth()->user()->surname ?? '')) ?: (auth()->user()->username ?? 'مستخدم'),
                                                'open_time' => $egoRd->open_time,
                                                'invoices' => $egoInvCount,
                                                'total_sales' => $egoRd->total_sales ?? 0,
                                                'opening_cash' => $egoRd->cash_in_hand ?? 0,
                                            ];
                                        }
                                    } catch (\Throwable $e) { $egoUserSummary = null; }
                                @endphp
                                <style>
                                    #ego_user_modal .ego-us-row{display:flex;justify-content:space-between;align-items:center;padding:13px 18px;border-bottom:1px solid #f1f5f9;font-size:14px}
                                    #ego_user_modal .ego-us-row span{color:#64748b;font-weight:600}
                                    #ego_user_modal .ego-us-row b{color:#0f172a;font-weight:800}
                                    #ego_user_modal .ego-us-row:last-child{border-bottom:none}
                                </style>
                                <button type="button" id="ego_user_modal_open" data-toggle="modal" data-target="#ego_user_modal" style="display:none"></button>
                                <div class="modal fade ego-modal" id="ego_user_modal" tabindex="-1" role="dialog" aria-hidden="true">
                                    <div class="modal-dialog" role="document" style="direction:rtl;width:460px;max-width:95%">
                                        <div class="modal-content" style="border-radius:16px;overflow:hidden">
                                            <div class="modal-header" style="background:linear-gradient(135deg,#0d9488,#0f766e);color:#fff;text-align:center">
                                                <button type="button" class="close" data-dismiss="modal" style="color:#fff;opacity:.9"><span aria-hidden="true">&times;</span></button>
                                                <div style="display:flex;flex-direction:column;align-items:center;gap:6px;padding:4px 0">
                                                    <span style="width:54px;height:54px;border-radius:50%;background:rgba(255,255,255,.2);display:inline-flex;align-items:center;justify-content:center"><i class="fas fa-user" style="font-size:24px"></i></span>
                                                    <h4 class="modal-title" style="margin:0;color:#fff">{{ $egoUserSummary['name'] ?? (optional(auth()->user())->first_name ?? 'مستخدم') }}</h4>
                                                </div>
                                            </div>
                                            <div class="modal-body" style="padding:0">
                                                <div class="ego-us-row"><span>فاتح الوردية</span><b id="ego_us_name">{{ $egoUserSummary['name'] ?? (optional(auth()->user())->first_name ?? '—') }}</b></div>
                                                <div class="ego-us-row"><span>التاريخ والوقت</span><b id="ego_us_time">{{ isset($egoUserSummary['open_time']) ? \Carbon\Carbon::parse($egoUserSummary['open_time'])->format('d-m-Y h:i A') : '—' }}</b></div>
                                                <div class="ego-us-row"><span>عدد الفواتير اليوم</span><b id="ego_us_inv">{{ $egoUserSummary['invoices'] ?? 0 }}</b></div>
                                                <div class="ego-us-row"><span>إجمالي المبيعات</span><b id="ego_us_sales">@format_currency($egoUserSummary['total_sales'] ?? 0)</b></div>
                                                <div class="ego-us-row"><span>افتتاحية الكاش</span><b id="ego_us_open">@format_currency($egoUserSummary['opening_cash'] ?? 0)</b></div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                {{-- 🆕 زر مخفي لفتح نافذة الدفع عبر data-toggle (موثوق مع تعدّد نسخ jQuery) --}}
                                <button type="button" id="ego_pay_modal_open" data-toggle="modal" data-target="#ego_pay_modal" style="display:none"></button>

                                {{-- 🆕 نافذة الدفع المنبثقة: تظهر الإجمالي/الخصم/المستحق/المدفوع/الباقي بعد اختيار طريقة الدفع --}}
                                <div class="modal fade ego-modal" id="ego_pay_modal" tabindex="-1" role="dialog" aria-hidden="true">
                                    <div class="modal-dialog" role="document" style="direction:rtl">
                                        <div class="modal-content" style="border-radius:16px;overflow:hidden">
                                            <div class="modal-header" style="background:linear-gradient(135deg,#0d9488,#0f766e);color:#fff">
                                                <button type="button" class="close" data-dismiss="modal" aria-label="Close" style="color:#fff;opacity:.9"><span aria-hidden="true">&times;</span></button>
                                                <h4 class="modal-title"><i class="fas fa-cash-register"></i> التسديد والدفع</h4>
                                            </div>
                                            <div class="modal-body">
                                                {{-- هنا يُنقل صندوق المجاميع (#ego_pay_summary) وأزرار الدفع (#ego_keypad_box) تلقائياً عبر JS --}}
                                                <div id="ego_pay_modal_totals"></div>
                                                <div id="ego_pay_modal_methods" style="margin-top:14px"></div>
                                                {{-- 🆕 (أُزيل) زر طباعة الفاتورة من نافذة التسديد — الطباعة متاحة من شريط السلة --}}
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                {{-- 🆕 زر "العمليات" العائم + زر مخفي لفتح النافذة عبر data-toggle --}}
                                <button type="button" id="ego_ops_btn"><i class="fas fa-sliders-h"></i> العمليات</button>
                                <button type="button" id="ego_ops_open" data-toggle="modal" data-target="#ego_ops_modal" style="display:none"></button>

                                {{-- 🆕 نافذة العمليات: تُنقل إليها القائمة الجانبية (خصومات/عمليات/درج/طباعة/هدية/فحص/مرتجعات/حجز) --}}
                                <div class="modal fade ego-modal" id="ego_ops_modal" tabindex="-1" role="dialog" aria-hidden="true">
                                    <div class="modal-dialog" role="document" style="direction:rtl">
                                        <div class="modal-content" style="border-radius:16px;overflow:hidden">
                                            <div class="modal-header" style="background:linear-gradient(135deg,#4f46e5,#7c3aed);color:#fff">
                                                <button type="button" class="close" data-dismiss="modal" aria-label="Close" style="color:#fff;opacity:.9"><span aria-hidden="true">&times;</span></button>
                                                <h4 class="modal-title"><i class="fas fa-sliders-h"></i> العمليات والخصومات</h4>
                                            </div>
                                            <div class="modal-body" id="ego_ops_body"></div>
                                        </div>
                                    </div>
                                </div>

                                {{-- 🆕 نافذة فحص السعر (للعرض فقط — لا تضيف القطعة للسلة) --}}
                                <div class="modal fade ego-modal" id="ego_price_check_modal" tabindex="-1" role="dialog" aria-hidden="true">
                                    <div class="modal-dialog" role="document" style="direction:rtl">
                                        <div class="modal-content" style="border-radius:16px;overflow:hidden">
                                            <div class="modal-header" style="background:linear-gradient(135deg,#0d9488,#0f766e);color:#fff">
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
                                    // عند فراغ السلة نُرجع 0 حتى لا يظهر الكلي/الباقي بقيمة قديمة أو بالسالب بعد البيع
                                    function egoGetDue(){
                                        if ($('#pos_table tbody tr.product_row').length === 0) { return 0; }
                                        return egoReadNum($('#final_total_input'));
                                    }
                                    var egoPaidManual = false; // هل عدّل الكاشير حقل المدفوع يدوياً؟
                                    // قراءة رقم من نص عنصر (يتجاهل رموز العملة)
                                    function egoReadText(sel){ var v = parseFloat(($(sel).first().text()||'').toString().replace(/[^0-9.\-]/g,'')); return isNaN(v)?0:v; }
                                    function egoUpdateTotals(){
                                        var hasItems = $('#pos_table tbody tr.product_row').length > 0;
                                        // 🆕 المجموع الأصلي = مجموع (سعر القطعة شامل الضريبة × الكمية) قبل خصومات العروض.
                                        //    (سعر القطعة لا يتغيّر بمحرّك العروض، فيبقى المجموع الأصلي صحيحاً ويظهر فرق التوفير بالأحمر)
                                        var origSub = 0;
                                        if (hasItems) {
                                            $('#pos_table tbody tr.product_row').each(function(){
                                                var unit = egoReadNum($(this).find('input.pos_unit_price_inc_tax'));
                                                var qty  = egoReadNum($(this).find('input.pos_quantity'));
                                                origSub += unit * qty;
                                            });
                                        }
                                        var total = egoGetDue();
                                        // التوفير = الفرق بين المجموع الأصلي والإجمالي بعد كل الخصومات (عروض + خصم يدوي)
                                        var disc = Math.max(0, origSub - total);
                                        $('#ego_t_sub').text(egoFmt(origSub));
                                        $('#ego_t_disc').text(egoFmt(disc));
                                        $('#ego_t_total').text(egoFmt(total));
                                        // 🆕 زر التسديد يعرض المبلغ المطلوب بدل كلمة "تسديد"
                                        $('#ego_btn_checkout').html('<i class="fas fa-cash-register"></i> ' + egoFmt(total));
                                    }
                                    function egoUpdateDue(){
                                        var due = egoGetDue();
                                        $('.ego-due-val').text(egoFmt(due));
                                        egoUpdateTotals();
                                        // تعبئة "المدفوع" تلقائياً بقيمة الكلي ما لم يعدّلها الكاشير (قابلة للتعديل)
                                        if (!egoPaidManual && $('#pos_table tbody tr.product_row').length > 0) {
                                            $('#ego_paid_amount').val(egoFmt(due));
                                        }
                                        egoCalcChange();
                                    }
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
                                    // مراقبة خانة الخصم في النظام لتحديث "الخصم/التوفير" فوراً عند تطبيق أي خصم
                                    if (window.MutationObserver){
                                        var td = document.getElementById('total_discount');
                                        if (td){ new MutationObserver(function(){ egoUpdateTotals(); }).observe(td, {childList:true, characterData:true, subtree:true}); }
                                    }

                                    // ---------- 🆕 عدّاد الأسطر وإجمالي الكمية + مسح المدفوع/الباقي بعد البيع ----------
                                    var egoPrevLines = 0;
                                    function egoUpdateCounters(){
                                        var lines = $('#pos_table tbody tr.product_row').length;
                                        var qty = 0;
                                        $('#pos_table tbody .pos_quantity').each(function(){ qty += egoParse($(this).val()); });
                                        var qtyTxt = (Math.round(qty*100)/100).toString();
                                        $('#ego_lines_count').text(lines);
                                        $('#ego_qty_count').text(qtyTxt);
                                        // عند إفراغ السلة (بعد البيع/الإلغاء) صفّر حقل المدفوع والباقي
                                        if (egoPrevLines > 0 && lines === 0) {
                                            egoPaidManual = false;       // إعادة التعبئة التلقائية للبيعة الجديدة
                                            $('#ego_paid_amount').val('');
                                            egoCalcChange();
                                        }
                                        egoPrevLines = lines;
                                    }
                                    // تحديث عند تغيّر الكمية أو إضافة/حذف صف
                                    $(document).on('change keyup', '#pos_table .pos_quantity', egoUpdateCounters);
                                    if (window.MutationObserver){
                                        var posBody = document.querySelector('#pos_table tbody');
                                        if (posBody){ new MutationObserver(egoUpdateCounters).observe(posBody, {childList:true, subtree:true}); }
                                    }
                                    setInterval(egoUpdateCounters, 1500); // احتياطي

                                    // كيباد المدفوع (أي إدخال يدوي يوقف التعبئة التلقائية)
                                    $('#ego_paid_amount').on('input', function(){ egoPaidManual = true; egoCalcChange(); });
                                    $(document).on('click', '.ego-key', function(){
                                        egoPaidManual = true;
                                        var k = $(this).data('k'); var $inp = $('#ego_paid_amount');
                                        if (k === 'clear') { $inp.val(''); }
                                        else if (k === 'back') { $inp.val(($inp.val()||'').slice(0,-1)); }
                                        else { $inp.val(($inp.val()||'') + k); }
                                        egoCalcChange();
                                    });
                                    $(document).on('click', '[data-amt]', function(){
                                        egoPaidManual = true;
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
                                        // ضمان إعادة حساب الكلي فوراً (الخصم ينخصم من الكلي ويظهر في خانة الخصم/التوفير)
                                        if (typeof pos_total_row === 'function') { try { pos_total_row(); } catch(e){} }
                                        egoUpdateTotals();
                                        setTimeout(function(){ egoUpdateDue(); egoUpdateTotals(); }, 200);
                                    }
                                    $('#ego_disc_pct').on('click', function(){ egoSetType('percentage'); });
                                    $('#ego_disc_fixed').on('click', function(){ egoSetType('fixed'); });
                                    $('#ego_disc_value').on('change', function(){ egoApplyDiscount(egoDiscType, egoParse($(this).val())); });
                                    $(document).on('click', '.ego_disc_quick', function(){ var v = egoParse($(this).data('val')); $('#ego_disc_value').val(v); egoApplyDiscount('percentage', v); });
                                    $(document).on('click', '.ego_offer', function(){ var t = $(this).data('type'); var v = egoParse($(this).data('val')); $('#ego_disc_value').val(v); egoApplyDiscount(t, v); });

                                    // 🆕 إصلاح: منع بقاء الخصم محفوظاً وتطبيقه على كل البيعات.
                                    //     نجعل الافتراضي صفراً، ونصفّر خصم الفاتورة عند بدء بيعة جديدة (السلة فارغة).
                                    function egoResetOrderDiscount(force){
                                        var $d = $('#discount_amount');
                                        if (!$d.length) { return; }
                                        $d.data('default', 0);                 // ليصفّره reset النظام بدل الافتراضي القديم
                                        $('#discount_type').data('default', 'percentage');
                                        var cartEmpty = $('#pos_table tbody tr.product_row').length === 0;
                                        if (force || cartEmpty) {
                                            egoWriteNum($d, 0);
                                            $('#discount_type').val('percentage');
                                            $('#ego_disc_value').val('');
                                            $d.trigger('change');
                                            if (typeof pos_total_row === 'function') { try { pos_total_row(); } catch(e){} }
                                        }
                                    }
                                    egoResetOrderDiscount();
                                    $(window).on('load', function(){ setTimeout(egoResetOrderDiscount, 600); });

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

                                    // ---------- أزرار القائمة الجانبية: تستدعي أزرار النظام الأصلية الموجودة فعلاً ----------
                                    $(document).on('click', '[data-ego-target]', function(){
                                        var sel = $(this).data('ego-target');
                                        var $btn = $(sel).first();
                                        if ($btn.length && $btn[0]) { $btn[0].click(); }
                                        else if (typeof toastr !== 'undefined') { toastr.error('هذا الخيار غير مفعّل في الإعدادات'); }
                                    });

                                    // زر "فاتورة هدية": يفعّل/يلغي خانة الهدية الأصلية ويُظهر الحالة
                                    $(document).on('click', '#ego_btn_gift', function(){
                                        var $g = $('#is_gift_receipt');
                                        if (!$g.length) { if (typeof toastr !== 'undefined') { toastr.error('فاتورة الهدية غير مفعّلة في الإعدادات'); } return; }
                                        var newState = !$g.prop('checked');
                                        $g.prop('checked', newState).trigger('change');
                                        $(this).toggleClass('ego-active', newState);
                                        if (typeof toastr !== 'undefined') { toastr.info(newState ? '🎁 تم تفعيل فاتورة الهدية' : 'تم إلغاء فاتورة الهدية'); }
                                    });
                                    // زر "استبدال نقاط" يفتح نافذة النقاط/الخصم الأصلية (#posEditDiscountModal)

                                    // زر كاش: دفع نقدي عادي يسجّل قيمة الطلب فقط (الوردية = صافي قيمة الطلب، المستحق صفر، بدون تخزين باقي).
                                    // "المدفوع/الباقي" حاسبة مساعدة للكاشير فقط لمعرفة الباقي — لا تؤثّر على الفاتورة أو الوردية.
                                    $(document).on('click', '#ego_btn_cash', function(){
                                        var $cash = $('[data-pay_method="cash"]').first();
                                        if ($cash.length && $cash[0]) { $cash[0].click(); }
                                        else if (typeof toastr !== 'undefined') { toastr.error('الدفع نقداً غير مفعّل في الإعدادات'); }
                                    });

                                    // 🆕 بطاقة: تُسدَّد مباشرةً مثل الكاش دون إظهار نافذة تفاصيل البطاقة إطلاقاً
                                    $(document).on('click', '.ego-pay-card', function(e){
                                        e.preventDefault();
                                        if ($('#pos_table tbody').find('.product_row').length <= 0){ if(typeof toastr!=='undefined'){ toastr.warning('أضف منتجات أولاً'); } return; }
                                        try {
                                            if ($('#is_credit_sale').length){ $('#is_credit_sale').val(0); }
                                            var total_payable = __read_number($('input#final_total_input'));
                                            var total_paying  = __read_number($('input#total_paying_input'));
                                            if (total_payable > total_paying){
                                                var first_row = $('#payment_rows_div').find('.payment-amount').first();
                                                __write_number(first_row, __read_number(first_row) + (total_payable - total_paying));
                                                first_row.trigger('change');
                                            }
                                            var dd = $('#payment_rows_div').find('.payment_types_dropdown').first();
                                            dd.val('card'); dd.change();
                                        } catch(err){}
                                        if (typeof pos_form_obj !== 'undefined' && pos_form_obj){ pos_form_obj.submit(); }
                                    });
                                    // شبكة أمان: لو فُتحت نافذة تفاصيل البطاقة لأي سبب، تُؤكَّد تلقائياً فوراً (بلا إدخال يدوي)
                                    $(document).on('shown.bs.modal', '#card_details_modal', function(){
                                        setTimeout(function(){ var b = document.getElementById('pos-save-card'); if (b) { b.click(); } }, 40);
                                    });

                                    // 🆕 إرسال للفيزا: تُنهى البيعة تلقائياً (يُضغط "إنهاء البيعة") دون خطوة إضافية
                                    $(document).on('click', '.ego-op-visa', function(){ window.egoVisaAuto = true; setTimeout(function(){ window.egoVisaAuto = false; }, 7000); });
                                    $(document).on('shown.bs.modal', '#modal_payment', function(){
                                        if (!window.egoVisaAuto) { return; }
                                        window.egoVisaAuto = false;
                                        setTimeout(function(){
                                            var dd = $('#modal_payment').find('.payment_types_dropdown').first();
                                            if (dd.length) { dd.val('card').trigger('change'); }
                                            var b = document.getElementById('pos-save'); if (b) { b.click(); }
                                        }, 250);
                                    });

                                    // زر "حجز": اسم العميل (إجباري) + اختيار (توصيل/تعليق)، ثم يُحفظ كطلب محجوز غير مدفوع
                                    function egoDoReserve(type, name){
                                        name = (name || '').trim();
                                        if (!name) { return; }
                                        var emoji = (type === 'توصيل') ? '🚚' : '📌';
                                        var note = emoji + ' ' + type + ' — حجز للعميل: ' + name;
                                        // 1) نكتب الملاحظة في حقل sale_note (داخل الفورم)
                                        $('#sale_note').val(note);
                                        if ($('#additional_notes').length) { $('#additional_notes').val(note); }
                                        // 2) نحقن حقول مخفية داخل الفورم لضمان حفظ الملاحظة في كل المسارات (sale_note + additional_notes)
                                        var $form = $('#add_pos_sell_form');
                                        $form.find('input.ego-note-field').remove();
                                        $('<input>', {type:'hidden', name:'additional_notes', 'class':'ego-note-field', value: note}).appendTo($form);
                                        // 3) يُحفظ كمسودة/طلب محجوز: لا دفع، لا تسجيل بيعة، ولا فحص حد ائتمان
                                        var dBtn = document.getElementById('pos-draft');
                                        if (dBtn) { dBtn.click(); }
                                        else if (typeof toastr !== 'undefined') { toastr.error('ميزة الحفظ كطلب محجوز غير مفعّلة'); }
                                    }
                                    function egoAskReserveType(name){
                                        if (typeof Swal === 'undefined') { egoDoReserve('تعليق', name); return; }
                                        Swal.fire({
                                            title: 'نوع الطلب المحجوز',
                                            text: 'العميل: ' + name + ' — اختر نوع الحجز:',
                                            icon: 'question',
                                            showDenyButton: true,
                                            showCancelButton: true,
                                            confirmButtonText: '📌 تعليق',
                                            denyButtonText: '🚚 توصيل',
                                            cancelButtonText: 'إلغاء',
                                            confirmButtonColor: '#16a34a',
                                            denyButtonColor: '#2563eb'
                                        }).then(function(c){
                                            if (c.isConfirmed) { egoDoReserve('تعليق', name); }
                                            else if (c.isDenied) { egoDoReserve('توصيل', name); }
                                        });
                                    }
                                    // هل العميل المختار حقيقي (وليس الزبون الافتراضي walk-in)؟
                                    function egoCustomerIsReal(){
                                        var cid = ($('#customer_id').val() || '').toString();
                                        var def = ($('#default_customer_id').val() || '').toString();
                                        return cid !== '' && cid !== def;
                                    }
                                    $(document).on('click', '#ego_btn_reserve', function(){
                                        if ($('#pos_table tbody tr.product_row').length <= 0) {
                                            if (typeof toastr !== 'undefined') { toastr.warning('أضف منتجات أولاً قبل الحجز'); }
                                            return;
                                        }
                                        // عرّف العميل أولاً: لو ما في عميل حقيقي مختار نفتح نافذة اختيار/إضافة العميل
                                        // إذا العميل محدد مسبقاً نكمل الحجز مباشرة
                                        if (egoCustomerIsReal()){
                                            var cn = ($('#customer_id option:selected').text() || '').trim() || 'عميل';
                                            egoAskReserveType(cn);
                                            return;
                                        }
                                        if (typeof Swal === 'undefined'){
                                            var ab0 = document.querySelector('.add_new_customer'); if (ab0) { ab0.click(); }
                                            return;
                                        }
                                        // اسأل: عميل سابق أم جديد؟
                                        Swal.fire({
                                            title: 'حجز الطلب — العميل',
                                            text: 'هل العميل سابق أم جديد؟',
                                            icon: 'question',
                                            showDenyButton: true,
                                            showCancelButton: true,
                                            confirmButtonText: '👤 عميل سابق',
                                            denyButtonText: '➕ عميل جديد',
                                            cancelButtonText: 'إلغاء',
                                            confirmButtonColor: '#2563eb',
                                            denyButtonColor: '#16a34a'
                                        }).then(function(r){
                                            if (r.isConfirmed){
                                                // عميل سابق: افتح قائمة العملاء للبحث والاختيار
                                                try { $('#customer_id').select2('open'); } catch(e){ try { $('#customer_id').focus(); } catch(e2){} }
                                                if (typeof toastr !== 'undefined') { toastr.info('ابحث واختر العميل، ثم اضغط "حجز" مرة أخرى'); }
                                            } else if (r.isDenied){
                                                // عميل جديد: افتح نافذة إضافة عميل
                                                var ab = document.querySelector('.add_new_customer');
                                                if (ab) { ab.click(); }
                                                if (typeof toastr !== 'undefined') { toastr.info('أضف بيانات العميل، ثم اضغط "حجز" مرة أخرى'); }
                                            }
                                        });
                                    });

                                    // زر "إضافة مصاريف": يستدعي زر النظام الأصلي (#add_expense)
                                    $(document).on('click', '#ego_btn_expense', function(){
                                        var b = document.getElementById('add_expense');
                                        if (b) { b.click(); }
                                        else if (typeof toastr !== 'undefined') { toastr.error('إضافة المصاريف غير مفعّلة (تحتاج صلاحية)'); }
                                    });
                                    // زر "إرجاع بالباركود" يفتح نافذة الإرجاع عبر data-toggle (returnSearchModal)
                                    // إخفاء الزرّين الأصليين من القائمة العلوية (انتقلا للقائمة الجانبية)
                                    $(window).on('load', function(){
                                        setTimeout(function(){
                                            $('button[onclick*="returnSearchModal"]').hide();
                                            $('#add_expense').hide();
                                        }, 400);
                                    });

                                    // اختصار تسجيل الخروج (نقطة 5)
                                    $(document).on('click', '#ego_btn_logout', function(){
                                        if (confirm('هل تريد تسجيل الخروج؟')) { window.location.href = '/logout'; }
                                    });

                                    // زر "كشف حساب" بالقائمة الجانبية يستدعي دالة كشف الحساب الأصلية
                                    $(document).on('click', '#ego_btn_ledger', function(){ if (typeof openCustomerLedger === 'function') { openCustomerLedger(); } });

                                    // 🆕 تبويب "المميزة": يُظهر/يُخفي صندوق المنتجات المميزة بنفس مكان المنتجات
                                    $(document).on('click', '#ego_featured_tab', function(){
                                        var box = document.getElementById('featured_products_box');
                                        if (!box) { return; }
                                        var hidden = (box.style.display === 'none' || box.style.display === '');
                                        box.style.display = hidden ? 'grid' : 'none';
                                        $(this).css(hidden ? {background:'#f59e0b', color:'#fff'} : {background:'#fff', color:'#1e293b'});
                                    });

                                    // 🆕 إيقاف الطباعة التلقائية بعد البيع: نتجاوز pos_print لتخزّن الفاتورة بدل ما تطبع
                                    $(window).on('load', function(){
                                        setTimeout(function(){
                                            if (typeof window.pos_print === 'function' && !window.egoPrintOverridden) {
                                                window.egoOrigPosPrint = window.pos_print;   // نحفظ دالة الطباعة الأصلية
                                                window.egoPrintOverridden = true;
                                                window.pos_print = function(receipt){
                                                    window.egoLastReceipt = receipt;          // نخزّن الفاتورة دائماً (للطباعة اليدوية)
                                                    if (!window.EGO_NO_INSTANT_PRINT) {
                                                        try { window.egoOrigPosPrint(receipt); } catch(e){}   // 🆕 طباعة فورية بعد كل بيعة
                                                    } else if (typeof toastr !== 'undefined') {
                                                        toastr.info('🧾 الطباعة الفورية معطّلة — اضغط "طباعة الفاتورة" للطباعة');
                                                    }
                                                };
                                            }
                                        }, 500);
                                    });
                                    // زر "طباعة الفاتورة": يطبع آخر فاتورة عند الطلب فقط (نفس دالة الطباعة الأصلية)
                                    $(document).on('click', '#ego_btn_print', function(){
                                        if (window.egoLastReceipt && typeof window.egoOrigPosPrint === 'function') {
                                            window.egoOrigPosPrint(window.egoLastReceipt);
                                        } else if (typeof toastr !== 'undefined') {
                                            toastr.warning('لا توجد فاتورة للطباعة — أكمل بيعة أولاً');
                                        }
                                    });

                                    // 🆕 زر "فتح درج الكاش": يستدعي زر النظام الأصلي (#open_cash_drawer_btn) الذي يملك
                                    //     منطق QZ الكامل (اختيار الطابعة + 5 أوامر فتح + تسجيل السبب). نقرة DOM أصلية لتجاوز
                                    //     مشكلة تعدّد نسخ jQuery.
                                    $(document).on('click', '#ego_btn_drawer', function(){
                                        var b = document.getElementById('open_cash_drawer_btn');
                                        if (b) {
                                            b.click();
                                        } else if (typeof toastr !== 'undefined') {
                                            toastr.error('فتح درج الكاش غير مفعّل (تحتاج صلاحية فتح الدرج)');
                                        }
                                    });

                                    // 🆕 بحث داخل نافذة الأصناف (يعمل بعد تحميل jQuery، تفويض على document)
                                    $(document).on('input', '#ego_cat_search', function(){
                                        var q = ($(this).val() || '').toLowerCase().trim();
                                        $('#product_category_div .main-category-div').each(function(){
                                            var name = ((($(this).attr('data-name') || '') + ' ' + $(this).find('h4').first().text()) || '').toLowerCase();
                                            $(this).css('display', (q === '' || name.indexOf(q) !== -1) ? '' : 'none');
                                        });
                                    });
                                    // 🆕 بحث داخل نافذة العلامات التجارية
                                    $(document).on('input', '#ego_brand_search', function(){
                                        var q = ($(this).val() || '').toLowerCase().trim();
                                        $('#product_brand_div .product_brand').each(function(){
                                            var name = ($(this).find('h4').first().text() || $(this).text() || '').toLowerCase();
                                            $(this).css('display', (q === '' || name.indexOf(q) !== -1) ? '' : 'none');
                                        });
                                    });

                                    // زر "ملاحظة": يُفتح عبر data-toggle، وهنا فقط نعبّئ النص الحالي من حقل sale_note الأصلي
                                    $(document).on('click', '#ego_btn_note', function(){
                                        $('#ego_note_text').val($('#sale_note').val() || '');
                                    });
                                    // حفظ الملاحظة في حقل النظام sale_note ليُحفظ مع الفاتورة ويظهر بالمبيعات
                                    $(document).on('click', '#ego_note_save', function(){
                                        $('#sale_note').val($('#ego_note_text').val());
                                        if (typeof toastr !== 'undefined') { toastr.success('تم حفظ الملاحظة'); }
                                    });


                                    // نقل الأزرار من الأعلى: إخفاء "بحث عن مخزون" و"كشف حساب عميل" من القائمة العلوية
                                    $('button[onclick*="StockSearchModal"]').hide();
                                    $('button[onclick*="openCustomerLedger"]').hide();

                                    // إخفاء العناصر/المجموع/الشحن من المربع الأبيض (تبقى قيمها فعّالة بالخلفية)
                                    function egoHideTotalsCells(){
                                        $('.pos_form_totals .total_quantity').closest('td').hide();
                                        $('.pos_form_totals .price_total').closest('td').hide();
                                        $('.pos_form_totals #shipping_charges_amount').closest('td').hide();
                                    }
                                    egoHideTotalsCells();
                                    $(window).on('load', function(){ setTimeout(egoHideTotalsCells, 400); });

                                    // إخفاء/إظهار أزرار القائمة الجانبية حسب توفّر هدفها — يُشغّل بعد اكتمال تحميل الصفحة (نقطة 1)
                                    // 🆕 محرّك العروض الموحّد لنقطة البيع:
                                    //     - عروض الكمية تُطبَّق كـ "خصم لكل سطر" (لا يُعدَّل سعر القطعة شامل الضريبة).
                                    //     - الحزم لها الأولوية: عند اجتماع منتجاتها (بأي ترتيب) يُطبَّق خصم الحزمة على أسطرها.
                                    //     - يظهر مبلغ الخصم في عمود "الخصم" ويُخزَّن مع الفاتورة (line_discount_amount).
                                    var egoOffersTimer = null;
                                    function egoReadUnit($tr){
                                        return (typeof __read_number === 'function')
                                            ? __read_number($tr.find('input.pos_unit_price_inc_tax'))
                                            : (parseFloat($tr.find('input.pos_unit_price_inc_tax').val()) || 0);
                                    }
                                    function egoCollectLines(){
                                        var lines = [];
                                        $('#pos_table tbody tr.product_row').each(function(){
                                            var $tr = $(this);
                                            var vid = $tr.find('.row_variation_id').val();
                                            var qty = parseFloat($tr.find('input.pos_quantity').val()) || 0;
                                            if (vid && qty > 0) { lines.push({ variation_id: vid, quantity: qty, unit_price: egoReadUnit($tr) }); }
                                        });
                                        return lines;
                                    }
                                    window.egoLastSig = '';
                                    function egoApplyOffers(){
                                        var lines = egoCollectLines();
                                        if (lines.length === 0) {
                                            window.egoLastSig = '';
                                            // 🆕 إن كان خصم عرض مطبّقاً، نُصفّره عند تفريغ السلة (دون المساس بخصم يدوي)
                                            if (window.egoOfferDiscountActive) { egoApplyDiscount('fixed', 0); window.egoOfferDiscountActive = false; }
                                            return;
                                        }
                                        // بصمة السلة (صنف:كمية:سعر) — إن لم تتغيّر لا نعيد الحساب (يمنع الحلقات والطلبات الزائدة)
                                        var sig = lines.map(function(l){ return l.variation_id + ':' + l.quantity + ':' + l.unit_price; }).join('|') + '@' + ($('#location_id').val() || '');
                                        if (sig === window.egoLastSig) { return; }
                                        window.egoLastSig = sig;
                                        $.post("{{ route('pos.calc-offers') }}", {
                                            lines: lines,
                                            location_id: $('#location_id').val(),
                                            _token: $('meta[name="csrf-token"]').attr('content')
                                        }).done(function(r){
                                            if (!r || !r.success) { return; }
                                            var map = r.map || {};
                                            egoStopObserver(); // نوقف المراقبة أثناء تعديلنا للـ DOM لتفادي حلقة لا نهائية
                                            // الخصم في map إجمالي لكل صنف؛ نوزّعه على أسطر الصنف حسب قيمة كل سطر
                                            // (يمنع التطبيق المزدوج إذا تكرّر الصنف في أكثر من سطر مثل المنتجات التسلسلية)
                                            var vidTotalValue = {};
                                            $('#pos_table tbody tr.product_row').each(function(){
                                                var $tr = $(this);
                                                var vid = $tr.find('.row_variation_id').val();
                                                var qty = parseFloat($tr.find('input.pos_quantity').val()) || 0;
                                                vidTotalValue[vid] = (vidTotalValue[vid] || 0) + egoReadUnit($tr) * qty;
                                            });
                                            var totalDiscount = 0; // 🆕 إجمالي توفير العروض → يُطبَّق كخصم طلب
                                            $('#pos_table tbody tr.product_row').each(function(){
                                                var $tr = $(this);
                                                var vid = $tr.find('.row_variation_id').val();
                                                var qty = parseFloat($tr.find('input.pos_quantity').val()) || 0;
                                                var unit = egoReadUnit($tr);
                                                var info = map[vid] || { discount: 0, bundle: false };
                                                var rowValue = unit * qty;
                                                var totalVal = vidTotalValue[vid] || 0;
                                                // حصة هذا السطر من خصم الصنف
                                                var D = (parseFloat(info.discount) || 0) * (totalVal > 0 ? (rowValue / totalVal) : 0);
                                                if (D > rowValue) { D = rowValue; } // الخصم لا يتجاوز قيمة السطر
                                                // عرض مبلغ الخصم (للعرض فقط في عمود الخصم بالسلة)
                                                $tr.find('.ego-line-discount-display').text(D ? D.toFixed(2) : '0').css('color', info.bundle ? '#7c3aed' : '#dc2626');
                                                totalDiscount += D;
                                                // 🆕 نُصفّر خصم السطر — العرض يُطبَّق كخصم طلب (يظهر بالفاتورة مثل الخصم العادي) والسعر يبقى كاملاً
                                                $tr.find('.ego-line-discount-amount').val(0);
                                                $tr.find('.ego-line-discount-type').val('fixed');
                                                $tr.find('input.row_discount_amount').val(0);
                                                $tr.find('select.row_discount_type').val('fixed');
                                                var lineTotal = rowValue;
                                                if (typeof __write_number === 'function') { __write_number($tr.find('input.pos_line_total'), lineTotal, false); }
                                                else { $tr.find('input.pos_line_total').val(lineTotal.toFixed(2)); }
                                                $tr.find('span.pos_line_total_text').text((typeof __currency_trans_from_en === 'function') ? __currency_trans_from_en(lineTotal, true) : lineTotal.toFixed(2));
                                            });
                                            // 🆕 تطبيق إجمالي خصم العروض كـ"خصم طلب" (يُسجَّل في حقل الخصم ويظهر بالفاتورة المطبوعة)
                                            if (totalDiscount > 0.0001) { egoApplyDiscount('fixed', parseFloat(totalDiscount.toFixed(2))); window.egoOfferDiscountActive = true; }
                                            else if (window.egoOfferDiscountActive) { egoApplyDiscount('fixed', 0); window.egoOfferDiscountActive = false; }
                                            if (typeof pos_total_row === 'function') { pos_total_row(); }
                                            if (typeof egoUpdateTotals === 'function') { egoUpdateTotals(); } // 🆕 يحدّث "الخصم/التوفير" الأحمر فوراً
                                            if (r.bundles && r.bundles.length && typeof toastr !== 'undefined') {
                                                toastr.success('🎁 طُبّق عرض: ' + r.bundles.join('، '));
                                            }
                                            // نعيد تشغيل المراقبة بعد استقرار التعديلات
                                            setTimeout(egoStartObserver, 120);
                                        }).fail(function(){ egoStartObserver(); });
                                    }
                                    window.egoOffersSchedule = function(){ clearTimeout(egoOffersTimer); egoOffersTimer = setTimeout(egoApplyOffers, 350); };

                                    // 🆕 مراقب موحّد لأي تغيّر في السلة (كمية/إجمالي/إضافة/حذف).
                                    //     السبب: تعدّد نسخ jQuery يجعل حدث change المُطلَق من pos.js لا يصل لمستمعنا،
                                    //     لكن تغيّر DOM (نص الإجمالي/الكمية) أصلي فيلتقطه MutationObserver دائماً.
                                    var egoObserver = null;
                                    function egoStartObserver(){
                                        var tb = document.querySelector('#pos_table tbody');
                                        if (!tb || !window.MutationObserver) { return; }
                                        if (!egoObserver) {
                                            egoObserver = new MutationObserver(function(){ window.egoOffersSchedule(); });
                                        }
                                        egoObserver.observe(tb, { childList: true, subtree: true, characterData: true, attributes: true, attributeFilter: ['value'] });
                                    }
                                    function egoStopObserver(){ if (egoObserver) { egoObserver.disconnect(); } }
                                    egoStartObserver();
                                    $(window).on('load', function(){ setTimeout(egoStartObserver, 500); });

                                    // محفّزات إضافية (تعمل عندما تكون نسخة jQuery متطابقة): حذف صف + تغيير الفرع
                                    $(document).on('change', '#pos_table input.pos_quantity', function(){ window.egoOffersSchedule(); });
                                    $(document).on('click', '#pos_table .pos_remove_row', function(){ setTimeout(window.egoOffersSchedule, 300); });
                                    $(document).on('change', '#location_id, #select_location_id', function(){ setTimeout(window.egoOffersSchedule, 800); });

                                    function egoSyncSideButtons(){
                                        $('[data-ego-target]').each(function(){
                                            // 🆕 زر "ذمم" يبقى ظاهراً دائماً (لا يُخفى رغم إخفاء النظام له مع عميل Walk-In)
                                            if ($(this).hasClass('ego-pay-credit')) { $(this).show(); return; }
                                            var $t = $($(this).data('ego-target'));
                                            // يُخفى زرّي إذا كان هدف النظام غير موجود أو معطّلاً (عليه كلاس hide بسبب إعدادات الشركة)
                                            if ($t.length && !$t.hasClass('hide')) { $(this).show(); } else { $(this).hide(); }
                                        });
                                    }
                                    $(window).on('load', function(){ egoSyncSideButtons(); setTimeout(egoSyncSideButtons, 600); });
                                    $(function(){ setTimeout(egoSyncSideButtons, 300); });

                                    // عند فتح نافذة الباقي، حدّث القيم وركّز على حقل المدفوع
                                    $('#ego_calc_modal').on('shown.bs.modal', function(){ egoUpdateDue(); $('#ego_paid_amount').focus(); });

                                    // منع زر Enter داخل حقول المزايا من إرسال فاتورة البيع بالخطأ
                                    $(document).on('keydown', '.ego-modal input, #ego_side_panel input', function(e){
                                        if (e.key === 'Enter' || e.keyCode === 13) { e.preventDefault(); }
                                    });

                                    // 🆕 أُلغي تفعيل وضع القائمة الجانبية: كان يضيف padding-right كبيراً لحجز مكان القائمة (المُزالة)
                                    //     فيترك فراغاً على يمين السلة. الآن لا نضيف ego-side-on فيختفي الفراغ.
                                    document.body.classList.remove('ego-side-on');

                                    // ---------- 🆕 إخفاء القائمة العلوية وإظهارها عند مرور الماوس بأعلى الشاشة ----------
                                    // 🆕 أُلغي إخفاء القائمة العلوية + زر ☰ المخصّص: لأن ‎.pos-header يحوي شريط البحث عن المنتج
                                    //     (أساسي للكاشير) فإخفاؤه كان يخرّب الواجهة. القائمة العلوية تبقى ظاهرة دائماً،
                                    //     ويبقى زر القائمة الخاص بالنظام في مكانه الصحيح.
                                    (function egoNavToggle(){
                                        return; // مُعطّل عمداً
                                        var header = document.querySelector('.pos-header') || document.querySelector('.main-header');
                                        if (!header) { return; }
                                        document.body.classList.add('ego-pos-autohide');
                                        var btn = document.getElementById('ego_nav_toggle');
                                        if (!btn) {
                                            btn = document.createElement('button');
                                            btn.id = 'ego_nav_toggle';
                                            btn.type = 'button';
                                            btn.title = 'القائمة العلوية';
                                            btn.innerHTML = '<i class="fas fa-bars"></i>';
                                            document.body.appendChild(btn);
                                        }
                                        btn.addEventListener('click', function(e){
                                            e.stopPropagation();
                                            var shown = header.classList.toggle('ego-show');
                                            btn.classList.toggle('active', shown);
                                            btn.innerHTML = shown ? '<i class="fas fa-times"></i>' : '<i class="fas fa-bars"></i>';
                                        });
                                        // إخفاء عند النقر خارج القائمة
                                        document.addEventListener('click', function(e){
                                            if (header.classList.contains('ego-show') && !header.contains(e.target) && e.target !== btn && !btn.contains(e.target)) {
                                                header.classList.remove('ego-show');
                                                btn.classList.remove('active');
                                                btn.innerHTML = '<i class="fas fa-bars"></i>';
                                            }
                                        });
                                    })();

                                    // 🆕 المجاميع + المدفوع + طرق الدفع تبقى مع السلة (يسار) حتى تظل ظاهرة عند إخفاء المنتجات
                                    // (المجاميع والدفع جنب بعض في صف واحد لتوفير المساحة)
                                    (function egoLayoutPay(){
                                        var sum = document.getElementById('ego_pay_summary');
                                        var kb = document.getElementById('ego_keypad_box');
                                        if (sum && kb && sum.parentNode) {
                                            var wrap = document.getElementById('ego_pay_wrap');
                                            if (!wrap) {
                                                wrap = document.createElement('div');
                                                wrap.id = 'ego_pay_wrap';
                                                sum.parentNode.insertBefore(wrap, sum);
                                            }
                                            wrap.appendChild(sum);
                                            wrap.appendChild(kb);
                                        }
                                    })();

                                    // 🆕 زر "تسديد" يفتح نافذة فيها المجاميع + أزرار الدفع معاً، وضغط أي طريقة دفع يُنفّذها مباشرة.
                                    (function egoPayPopup(){
                                        var sum = document.getElementById('ego_pay_summary');
                                        var totalsHolder = document.getElementById('ego_pay_modal_totals');
                                        var kb = document.getElementById('ego_keypad_box');
                                        var methodsHolder = document.getElementById('ego_pay_modal_methods');
                                        if (sum && totalsHolder) { totalsHolder.appendChild(sum); }   // المجاميع داخل النافذة
                                        if (kb && methodsHolder) { methodsHolder.appendChild(kb); }    // أزرار الدفع داخل النافذة

                                        // 🆕 زر التسديد/المبلغ: يفتح النافذة المخصّصة (طرق دفع موحّدة + طباعة) بدل الدفع المتعدّد للنظام
                                        $(document).on('click', '#ego_btn_checkout', function(){
                                            if ($('#pos_table tbody tr.product_row').length === 0) {
                                                if (typeof toastr !== 'undefined') { toastr.warning('أضف منتجات أولاً'); }
                                                return;
                                            }
                                            var o = document.getElementById('ego_pay_modal_open');
                                            if (o) { o.click(); }
                                        });

                                        // عند فتح النافذة: حدّث المجاميع وركّز حقل المدفوع وحدّد قيمته بالأزرق
                                        var egoSelectPaid = function(){ var el = document.getElementById('ego_paid_amount'); if (el) { el.focus(); el.select(); } };
                                        $('#ego_pay_modal').on('shown.bs.modal', function(){
                                            if (typeof egoUpdateDue === 'function') { egoUpdateDue(); }
                                            // 🆕 تحديد قيمة المدفوع بالأزرق تلقائياً (كأنها ضُغطت مرتين) — نكرّرها لتصمد أمام إعادة التعبئة الدورية
                                            setTimeout(egoSelectPaid, 130); setTimeout(egoSelectPaid, 400);
                                        });
                                        // 🆕 أي تركيز/نقر على حقل المدفوع يحدّد قيمته بالكامل بالأزرق (focusin يتصاعد فيعمل بالتفويض)
                                        $(document).on('focusin click', '#ego_paid_amount', function(){ var el = this; setTimeout(function(){ el.select(); }, 0); });
                                        // 🆕 تنظيف أي "ظل أسود" (backdrop) عالق بعد إغلاق النافذة وتنفيذ الدفع
                                        $('#ego_pay_modal').on('hidden.bs.modal', function(){
                                            setTimeout(function(){
                                                if (!$('.modal.in:visible, .modal.show:visible').length) {
                                                    $('.modal-backdrop').remove();
                                                    $('body').removeClass('modal-open').css('padding-right','');
                                                }
                                            }, 400);
                                        });
                                        // زر "طباعة الفاتورة" داخل نافذة الدفع → يستدعي منطق الطباعة الموجود (#ego_btn_print)
                                        $(document).on('click', '#ego_pay_modal_print', function(){
                                            var p = document.getElementById('ego_btn_print');
                                            if (p) { p.click(); }
                                            else if (typeof toastr !== 'undefined') { toastr.info('الطباعة تتاح بعد إتمام البيعة'); }
                                        });
                                        // أزرار الدفع الآن داخل النافذة وتعمل بمعالجاتها الأصلية مباشرة (data-dismiss يغلق النافذة)
                                    })();

                                    // 🆕 (أُزيل) كان اعتراض .pos-express-finalize يعيد فتح نافذة الدفع عند تنفيذ "كاش" من داخلها
                                    //     فيمنع إتمام البيعة ويُبقي الظل الأسود. أزرار النظام مخفيّة أصلاً، فلا حاجة للاعتراض.

                                    // 🆕 نقل القائمة الجانبية داخل نافذة "العمليات"، وزر العمليات يفتحها
                                    (function egoOpsPanel(){
                                        var sp = document.getElementById('ego_side_panel');
                                        var body = document.getElementById('ego_ops_body');
                                        if (sp && body) { body.appendChild(sp); }   // كل أدوات القائمة الجانبية داخل النافذة
                                        // 🆕 ننقل زر "العمليات" (☰) إلى الشريط العلوي بجانب زر "إغلاق الكاش"
                                        var obtn = document.getElementById('ego_ops_btn');
                                        if (obtn) {
                                            obtn.innerHTML = '<i class="fas fa-bars"></i>';
                                            obtn.title = 'العمليات';
                                            obtn.classList.add('ego-ops-topbar');
                                            var cr = document.getElementById('close_register');
                                            if (cr && cr.parentNode) { cr.parentNode.insertBefore(obtn, cr); }
                                        }
                                        $(document).on('click', '#ego_ops_btn', function(){
                                            var o = document.getElementById('ego_ops_open');
                                            if (o) { o.click(); }   // فتح موثوق عبر data-toggle
                                        });

                                        // 🆕 درج الكاش + طباعة الفاتورة → الشريط السفلي (السطر الذي تحت صف الإجراءات) بنمط ملاحظة
                                        var bar     = document.getElementById('ego_cart_bar');
                                        var drawer  = document.getElementById('ego_btn_drawer');
                                        var printB  = document.getElementById('ego_btn_print');
                                        if (bar) {
                                            if (drawer) { drawer.classList.add('ego-cb-extra','ego-cb-drawer'); bar.insertBefore(drawer, bar.firstChild); }
                                            if (printB) { printB.classList.add('ego-cb-extra','ego-cb-print'); bar.insertBefore(printB, drawer ? drawer.nextSibling : bar.firstChild); }
                                        }
                                        // إرسال للفيزا → طرق الدفع في نافذة التسديد (بنفس التنسيق الموحّد)
                                        var visa = document.querySelector('.ego-op-visa');
                                        var payGrid = document.querySelector('#ego_pay_modal_methods .ego-kb-pay');
                                        if (visa && payGrid) {
                                            visa.classList.add('ego-pay');
                                            visa.setAttribute('data-dismiss', 'modal');
                                            payGrid.appendChild(visa);
                                        }
                                        // 🆕 زر إلغاء بجانب إرسال للفيزا في نافذة الدفع (يلغي البيعة)
                                        if (payGrid && !document.getElementById('ego_pay_cancel_btn')) {
                                            var cancelBtn = document.createElement('button');
                                            cancelBtn.type = 'button';
                                            cancelBtn.id = 'ego_pay_cancel_btn';
                                            cancelBtn.className = 'ego-pay ego-pay-cancelbtn';
                                            cancelBtn.setAttribute('data-ego-target', '#pos-cancel');
                                            cancelBtn.setAttribute('data-dismiss', 'modal');
                                            cancelBtn.innerHTML = '<i class="fas fa-times-circle"></i> إلغاء';
                                            payGrid.appendChild(cancelBtn);
                                        }
                                        // 🆕 ننقل أزرار (نقاط/كشف/مصاريف/مسودة) إلى صف الإجراءات (مخفية قابلة للتفعيل)، و⋮ = مفاتيح لكل الأزرار
                                        var moreDd = document.getElementById('ego_more_dropdown');
                                        var actionsRow = document.getElementById('ego_cart_actions');
                                        var cancelRef = actionsRow ? actionsRow.querySelector('.ego-ca-cancel') : null;
                                        function egoMoveToActions(el, cls){
                                            if (!el || !actionsRow) { return; }
                                            el.classList.remove('ego-op', 'ego-more-item');
                                            el.classList.add('ego-ca-btn', 'ego-ca-toggleable', cls);
                                            el.removeAttribute('data-dismiss'); el.style.width = ''; el.style.maxHeight = '';
                                            actionsRow.insertBefore(el, cancelRef);
                                        }
                                        egoMoveToActions(document.getElementById('ego_btn_points'), 'ego-ca-points');
                                        egoMoveToActions(document.getElementById('ego_btn_ledger'), 'ego-ca-ledger');
                                        egoMoveToActions(document.getElementById('ego_btn_expense'), 'ego-ca-expense');
                                        egoMoveToActions(document.querySelector('.ego-op-draft'), 'ego-ca-draft');
                                        if (moreDd) {
                                            var _sep = document.createElement('div'); _sep.className = 'ego-more-sep'; _sep.textContent = 'أظهر/أخفِ أزرار السلة:';
                                            moreDd.appendChild(_sep);
                                            [{key:'discount',sel:'.ego-ca-discount',label:'خصم'},{key:'note',sel:'.ego-ca-note',label:'ملاحظة'},{key:'shipping',sel:'.ego-ca-shipping',label:'توصيل'},{key:'points',sel:'.ego-ca-points',label:'استرداد نقاط'},{key:'ledger',sel:'.ego-ca-ledger',label:'كشف حساب'},{key:'expense',sel:'.ego-ca-expense',label:'مصاريف'},{key:'draft',sel:'.ego-ca-draft',label:'مسودة'}].forEach(function(t){
                                                if (!$('#ego_cart_actions ' + t.sel).length) { return; } // 🆕 تخطّى الأزرار غير المُصرّح بها / المخفية (مثل الخصم عند تعطيله)
                                                var on = localStorage.getItem('ego_cartbtn_' + t.key) === '1';
                                                $('#ego_cart_actions ' + t.sel).toggleClass('ego-on', on);
                                                var lbl = document.createElement('label'); lbl.className = 'ego-more-toggle';
                                                var chk = document.createElement('input'); chk.type = 'checkbox'; chk.checked = on; chk.setAttribute('data-ego-cartbtn', t.key); chk.setAttribute('data-sel', t.sel);
                                                lbl.innerHTML = '<span>' + t.label + '</span>';
                                                lbl.appendChild(chk);
                                                moreDd.appendChild(lbl);
                                            });
                                        }
                                        // تبديل إظهار أزرار السلة (لا يُغلق القائمة)
                                        $(document).on('click', '#ego_more_dropdown .ego-more-toggle', function(e){ e.stopPropagation(); });
                                        $(document).on('change', '#ego_more_dropdown input[data-ego-cartbtn]', function(){
                                            var key = $(this).attr('data-ego-cartbtn'), sel = $(this).attr('data-sel'), on = this.checked;
                                            localStorage.setItem('ego_cartbtn_' + key, on ? '1' : '0');
                                            $('#ego_cart_actions ' + sel).toggleClass('ego-on', on);
                                        });
                                        // فتح/إغلاق القائمة المنسدلة عند ⋮
                                        $(document).on('click', '#ego_more_btn', function(e){
                                            e.stopPropagation();
                                            if (moreDd) { moreDd.classList.toggle('open'); }
                                        });
                                        // إغلاقها عند اختيار خيار أو الضغط خارجها
                                        $(document).on('click', '#ego_more_dropdown .ego-more-item', function(){ if (moreDd) moreDd.classList.remove('open'); });
                                        $(document).on('click', function(ev){
                                            if (moreDd && moreDd.classList.contains('open') && !ev.target.closest('.ego-more-wrap')) { moreDd.classList.remove('open'); }
                                        });
                                    })();

                                    // 🆕🆕 المرحلة 1: شريط علوي مبسّط (SST + ☰ + المستخدم) + قائمة ☰ + الإعدادات بمفاتيح
                                    (function egoMainMenu(){
                                        if (document.getElementById('ego_mainmenu_wrap')) return;
                                        function srow(k,l){ return '<label class="ego-mm-srow"><span>'+l+'</span><span class="ego-sw"><input type="checkbox" data-ego-set="'+k+'"><span class="ego-sw-slider"></span></span></label>'; }
                                        var wrap = document.createElement('div');
                                        wrap.id = 'ego_mainmenu_wrap';
                                        wrap.innerHTML =
                                            '<img src="/img/sst-logo.png" alt="SST" class="ego-mm-logo-img">' +
                                            '<span class="ego-mm-menuwrap">' +
                                                '<button type="button" id="ego_mm_btn" class="ego-mm-btn" title="القائمة"><i class="fas fa-bars"></i></button>' +
                                                '<div id="ego_mm_dd" class="ego-mm-dd">' +
                                                    '<button type="button" class="ego-mm-item" data-ego-do="drafts"><i class="fas fa-file-invoice"></i> المسودات</button>' +
                                                    '<button type="button" class="ego-mm-item" data-ego-do="reg_details"><i class="fas fa-briefcase"></i> تفاصيل الوردية</button>' +
                                                    '<button type="button" class="ego-mm-item" data-ego-do="close_reg"><i class="fas fa-window-close"></i> إغلاق الوردية</button>' +
                                                    '<button type="button" class="ego-mm-item ego-mm-settings-toggle"><i class="fas fa-cog"></i> الإعدادات <i class="fas fa-chevron-left ego-mm-caret"></i></button>' +
                                                    '<div id="ego_mm_settings" class="ego-mm-settings">' + srow('no_instant_print','تعطيل الطباعة الفورية') + srow('fullscreen','ملء الشاشة') + srow('mute','كتم أصوات نقطة البيع') + srow('show_products','إظهار المنتجات') + '</div>' +
                                                    '<button type="button" class="ego-mm-item ego-mm-addons-toggle"><i class="fas fa-puzzle-piece"></i> إضافات <i class="fas fa-chevron-left ego-mm-caret"></i></button>' +
                                                    '<div id="ego_addons_sub" class="ego-mm-settings"></div>' +
                                                    '<button type="button" class="ego-mm-item" data-ego-do="account"><i class="fas fa-user"></i> حسابي</button>' +
                                                    '<button type="button" class="ego-mm-item ego-mm-logout" data-ego-do="logout"><i class="fas fa-sign-out-alt"></i> تسجيل الخروج</button>' +
                                                '</div>' +
                                            '</span>' +
                                            '<a href="{{ url('/home') }}" id="ego_mm_home" class="ego-mm-home" title="الرئيسية"><i class="fas fa-home"></i></a>' +
                                            '<button type="button" id="ego_mm_user" class="ego-mm-user" title="ملخص اليوم"><i class="fas fa-user-circle"></i> <span>{{ optional(auth()->user())->first_name ?? 'مستخدم' }}</span></button>' +
                                            '<form id="ego_logout_form" action="{{ url('/logout') }}" method="POST" style="display:none"><input type="hidden" name="_token" value="{{ csrf_token() }}"></form>';
                                        // حقن العناصر في يسار الشريط العلوي
                                        var _lt = document.querySelector('.tw-w-full.md\\:tw-w-1\\/3');
                                        var host = (_lt && _lt.parentElement) || document.querySelector('.tw-items-center.tw-justify-between') || document.querySelector('.pos-header');
                                        if (host) { host.appendChild(wrap); }   // 🆕 آخر عنصر = أقصى اليسار (RTL)
                                        // إخفاء زر الرجوع من الأعلى
                                        var bk = document.querySelector('#pos_header_more_options a .fa-backward'); if (bk && bk.closest('a')) { bk.closest('a').style.display='none'; }
                                        // إخفاء أزرار الشريط العلوي القديمة (تُستدعى عبر القائمة)
                                        var st = document.createElement('style');
                                        st.textContent = '#close_register,#register_details,#view_suspended_sales,#btnCalculator,#full_screen,#recent-transactions,#service_staff_replacement,#show_service_staff_availability,#customer_display_screen{display:none !important}';
                                        document.head.appendChild(st);
                                        // 🆕 نقل عناصر "العمليات" إلى "إضافات" داخل القائمة، وحذف زر العمليات
                                        var addonsSub = document.getElementById('ego_addons_sub');
                                        if (addonsSub) {
                                            [document.getElementById('ego_btn_gift'), document.getElementById('ego_btn_reserve'), document.getElementById('ego_btn_stock'), document.getElementById('ego_btn_return_barcode'), document.querySelector('.ego-op-recent')].forEach(function(el){ if(el){ el.classList.remove('ego-op'); el.classList.add('ego-mm-item'); el.style.width=''; el.style.maxHeight=''; addonsSub.appendChild(el); } });
                                            // زر "ارجاع حسب فاتورة" (زر الإرجاع العلوي ذو الـ popover) → إضافات
                                            var _retInv=document.getElementById('return_sale');
                                            if(_retInv){ _retInv.classList.add('ego-mm-item'); _retInv.style.display=''; _retInv.innerHTML='<i class="fas fa-undo"></i> ارجاع حسب فاتورة'; addonsSub.appendChild(_retInv);
                                                try{ $(_retInv).popover('destroy'); }catch(e){}
                                                try{ $(_retInv).popover({html:true, placement:'bottom', trigger:'click'}); }catch(e){}
                                            }
                                        }
                                        // "إظهار المنتجات" يبقى في DOM للنقر لكن مخفي، و"خروج" يُحذف، وزر العمليات يُحذف
                                        var _tp=document.getElementById('ego_toggle_products'); if(_tp) _tp.style.display='none';
                                        var _lg=document.getElementById('ego_btn_logout'); if(_lg) _lg.remove();
                                        var _ob=document.getElementById('ego_ops_btn'); if(_ob) _ob.remove();

                                        function clk(id){ var el=document.getElementById(id); if(el) el.click(); }
                                        function egoCollapseSubs(){ var s=document.getElementById('ego_mm_settings'); if(s)s.classList.remove('open'); var a=document.getElementById('ego_addons_sub'); if(a)a.classList.remove('open'); }
                                        $(document).on('click','#ego_mm_btn',function(e){ e.stopPropagation(); document.getElementById('ego_mm_dd').classList.toggle('open'); egoCollapseSubs(); });
                                        $(document).on('click','.ego-mm-settings-toggle',function(e){ e.stopPropagation(); document.getElementById('ego_mm_settings').classList.toggle('open'); });
                                        $(document).on('click','.ego-mm-addons-toggle',function(e){ e.stopPropagation(); document.getElementById('ego_addons_sub').classList.toggle('open'); });
                                        $(document).on('click','#ego_addons_sub .ego-mm-item',function(){ if(this.getAttribute('data-toggle')==='popover')return; var dd=document.getElementById('ego_mm_dd'); if(dd) dd.classList.remove('open'); });
                                        $(document).on('click', function(ev){ var dd=document.getElementById('ego_mm_dd'); if(dd&&dd.classList.contains('open')&&!ev.target.closest('.ego-mm-menuwrap')&&!ev.target.closest('.popover')){ dd.classList.remove('open'); egoCollapseSubs(); } });
                                        // 🆕 مزامنة مفتاح ملء الشاشة عند الخروج بـ Esc
                                        $(document).on('fullscreenchange webkitfullscreenchange', function(){ var fs=!!(document.fullscreenElement||document.webkitFullscreenElement); var sw=document.querySelector('#ego_mm_settings input[data-ego-set="fullscreen"]'); if(sw){ sw.checked=fs; localStorage.setItem('ego_set_fullscreen', fs?'1':'0'); } });
                                        $(document).on('click','.ego-mm-item[data-ego-do]',function(){
                                            var a=this.getAttribute('data-ego-do'), dd=document.getElementById('ego_mm_dd');
                                            if(a==='toggle_products') clk('ego_toggle_products');
                                            else if(a==='drafts'){ window.location='{{ url('/sells/drafts') }}'; }
                                            else if(a==='reg_details') clk('register_details');
                                            else if(a==='close_reg') clk('close_register');
                                            else if(a==='addons'){ if(window.toastr) toastr.info('الإضافات — قريباً'); }
                                            else if(a==='account'){ window.location='{{ url('/user/profile') }}'; }
                                            else if(a==='logout'){ window.location='{{ url('/logout') }}'; }
                                            if(dd) dd.classList.remove('open');
                                        });
                                        // 🆕 جلب ملخص اليوم حديثاً (مع منع الكاش) — يُستدعى عند كل ضغط على admin
                                        function egoLoadUserSummary(){
                                            $.ajax({ url: "{{ route('pos.ego-user-summary') }}", cache: false, dataType: 'json' }).done(function(d){
                                                if (!d || !d.success) { return; }
                                                $('#ego_us_name').text(d.name);
                                                $('#ego_us_time').text(d.open_time);
                                                $('#ego_us_inv').text(d.invoices);
                                                $('#ego_us_sales').text(typeof __currency_trans_from_en === 'function' ? __currency_trans_from_en(d.total_sales, true) : d.total_sales);
                                                $('#ego_us_open').text(typeof __currency_trans_from_en === 'function' ? __currency_trans_from_en(d.opening_cash, true) : d.opening_cash);
                                            });
                                        }
                                        window.egoLoadUserSummary = egoLoadUserSummary;
                                        $(document).on('click','#ego_mm_user',function(){ var o=document.getElementById('ego_user_modal_open'); if(o){ o.click(); } else { clk('register_details'); } egoLoadUserSummary(); });
                                        $(document).on('shown.bs.modal','#ego_user_modal', egoLoadUserSummary);

                                        // مفاتيح الإعدادات (تُحفظ في localStorage)
                                        function sGet(k){ return localStorage.getItem('ego_set_'+k)==='1'; }
                                        function sPut(k,v){ localStorage.setItem('ego_set_'+k, v?'1':'0'); }
                                        $('#ego_mm_settings input[data-ego-set]').each(function(){ this.checked = sGet($(this).attr('data-ego-set')); });
                                        // مفتاح "إظهار المنتجات" يعكس الحالة الفعلية (المنتجات ظاهرة افتراضياً)
                                        var _sp=document.querySelector('#ego_mm_settings input[data-ego-set="show_products"]'); if(_sp){ _sp.checked = !document.body.classList.contains('ego-cart-full'); }
                                        window.EGO_MUTE = sGet('mute'); window.EGO_NO_INSTANT_PRINT = sGet('no_instant_print');
                                        $(document).on('change','#ego_mm_settings input[data-ego-set]',function(){
                                            var k=$(this).attr('data-ego-set'), on=this.checked; sPut(k,on);
                                            if(k==='fullscreen'){ try{ if(on){ var de=document.documentElement; (de.requestFullscreen||de.webkitRequestFullscreen).call(de); } else { (document.exitFullscreen||document.webkitExitFullscreen).call(document); } }catch(e){} }
                                            else if(k==='show_products'){ var shown=!document.body.classList.contains('ego-cart-full'); if(shown!==on){ clk('ego_toggle_products'); } }
                                            else if(k==='mute'){ window.EGO_MUTE = on; }
                                            else if(k==='no_instant_print'){ window.EGO_NO_INSTANT_PRINT = on; }
                                        });
                                        // كتم الصوت: ترقيع تشغيل الصوت (كود جديد فقط — لا يعدّل ملفات النظام)
                                        try { var _ap = HTMLAudioElement.prototype.play; HTMLAudioElement.prototype.play = function(){ if(window.EGO_MUTE){ try{this.pause();}catch(e){} return Promise.resolve(); } return _ap.apply(this, arguments); }; } catch(e){}
                                    })();

                                    // 🆕🆕 تعدّد السلال (تبويبات سلة 1/2/+) — لقطة/استرجاع لمحتوى السلة من جهة العميل
                                    (function egoMultiCart(){
                                        if (!document.getElementById('ego_cart_tabs')) return;
                                        var carts = [], active = -1, seq = 0;
                                        // ملاحظة: العميل مشترك بين السلال (لا نُفرّغه) حتى لا يطلب اسم عميل إلزامي في سلة جديدة
                                        function snap(){ return { html: $('#pos_table tbody').html(), rowCount: ($('#product_row_count').val()||0) }; }
                                        function restore(s){
                                            $('#pos_table tbody').html((s&&s.html)||'');
                                            if ($('#product_row_count').length) { $('#product_row_count').val((s&&s.rowCount)||0); }
                                            if (typeof window.pos_total_row==='function'){ try{ window.pos_total_row(); }catch(e){} }
                                        }
                                        function addCart(){ seq++; carts.push({ name:'السلة '+seq, data:{html:'',rowCount:0} }); return carts.length-1; }
                                        function render(){
                                            var list=document.getElementById('ego_cart_tablist'); if(!list) return; list.innerHTML='';
                                            carts.forEach(function(c,i){
                                                var t=document.createElement('div'); t.className='ego-ct-tab'+(i===active?' active':''); t.setAttribute('data-i',i);
                                                t.innerHTML='<span class="ego-ct-name">'+c.name+'</span>'+(carts.length>1?'<i class="fas fa-times ego-ct-x"></i>':'');
                                                list.appendChild(t);
                                            });
                                        }
                                        function switchTo(i){ if(i===active||!carts[i])return; if(active>-1&&carts[active])carts[active].data=snap(); active=i; restore(carts[i].data); render(); }
                                        // السلة الأولى من الحالة الحالية
                                        active=addCart(); carts[0].data=snap(); render();
                                        $(document).on('click','#ego_cart_add',function(){ if(active>-1&&carts[active])carts[active].data=snap(); active=addCart(); restore({html:'',customer:'',rowCount:0}); render(); });
                                        $(document).on('click','.ego-ct-tab .ego-ct-name',function(){ switchTo(+$(this).closest('.ego-ct-tab').attr('data-i')); });
                                        $(document).on('click','.ego-ct-x',function(e){ e.stopPropagation(); var i=+$(this).closest('.ego-ct-tab').attr('data-i'); if(carts.length<=1)return; carts.splice(i,1); if(active>=carts.length)active=carts.length-1; else if(active>i)active--; restore(carts[active].data); render(); });
                                        // 🆕 إغلاق السلة الحالية بعد إتمام بيعتها (إن كان هناك أكثر من سلة)
                                        window.egoCloseActiveCart = function(){
                                            if (carts.length<=1) { return; }
                                            carts.splice(active,1);
                                            if (active>=carts.length) active=carts.length-1;
                                            restore(carts[active].data); render();
                                        };
                                    })();

                                    // 🆕 ملاءمة الشاشة: لا تمرير للصفحة — تُحسب أعمدة (السلة/المنتجات) حسب ارتفاع الشريط العلوي فعلياً
                                    function egoFitScreen(){
                                        var c=document.getElementById('pos_flexible_container'); if(!c) return;
                                        var top=c.getBoundingClientRect().top + (window.scrollY||0);
                                        var h=window.innerHeight - top - 8; if(h<360)h=360;
                                        var m=document.querySelector('#pos_main_column > div'); var s=document.querySelector('#pos_side_column > div');
                                        if(m){ m.style.height=h+'px'; m.style.maxHeight=h+'px'; m.style.minHeight='0'; }
                                        if(s){ s.style.height=h+'px'; s.style.maxHeight=h+'px'; s.style.minHeight='0'; }
                                    }
                                    window.egoFitScreen=egoFitScreen;
                                    $(window).on('resize', egoFitScreen);
                                    $(function(){ egoFitScreen(); setTimeout(egoFitScreen,300); setTimeout(egoFitScreen,900); });

                                    // 🆕 صندوق "المميزة" داخل نفس حاوية شبكة صور الأصناف ليكون بنفس الحجم/المكان
                                    $(function(){ var fb=document.getElementById('featured_products_box'); var plb=document.getElementById('product_list_body'); if(fb&&plb&&plb.parentElement&&fb.parentElement!==plb.parentElement){ plb.parentElement.insertBefore(fb, plb); } });

                                    // 🆕 فصل "المميزة" عن الأصناف العادية: كلٌّ يفتح لوحده
                                    $(document).on('click', '#ego_featured_tab', function(){
                                        setTimeout(function(){
                                            if ($('#featured_products_box').is(':visible')) { $('#product_list_body').hide(); }
                                            else { $('#product_list_body').show(); }
                                        }, 380);
                                    });
                                    // عند اختيار صنف/علامة أو إغلاق الدرج → إظهار العادية وإخفاء المميزة
                                    $(document).on('click', '.product_category, .product_brand, .main-category, .main-category-div, .close-side-bar-category, .close-side-bar-brand', function(){
                                        $('#featured_products_box').hide();
                                        $('#product_list_body').show();
                                    });

                                    // 🆕 أسهم تمرير أسطر البيع: المُعالج في سكربت مستقل بنهاية الصفحة (ليبقى فعّالاً مهما حدث)

                                    // 🆕 بائع كل منتج يأخذ افتراضياً البائع المختار بالأعلى (#commission_agent)، ويمكن تغييره لكل سطر
                                    function egoTopSeller(){ return $('#commission_agent').val() || ''; }
                                    (function(){
                                        var tb = document.querySelector('#pos_table tbody'); if (!tb) { return; }
                                        new MutationObserver(function(muts){
                                            var top = egoTopSeller(); if (!top) { return; }
                                            muts.forEach(function(m){
                                                $(m.addedNodes).find('.ego-seller-select').addBack('.ego-seller-select').each(function(){
                                                    if (!$(this).val()) { $(this).val(top); }
                                                });
                                            });
                                        }).observe(tb, {childList:true});
                                    })();
                                    // عند تغيير البائع بالأعلى: عبّئ الأسطر التي بلا بائع
                                    $(document).on('change', '#commission_agent', function(){
                                        var top = $(this).val() || ''; if (!top) { return; }
                                        $('#pos_table .ego-seller-select').each(function(){ if (!$(this).val()) { $(this).val(top); } });
                                    });

                                    {{-- 🆕 (أُزيل تجاوز مكرّر — منطق الطباعة الفورية صار داخل تجاوز pos_print الأساسي) --}}

                                    // 🆕 إغلاق السلة الحالية تلقائياً بعد إتمام تسديدها (للمسار الذي لا يُعيد تحميل الصفحة)
                                    $(document).on('click','#ego_btn_cash, .ego-pay, #pos-finalize, .pos-express-finalize, [data-pay_method]', function(){ window.egoCheckingOut=true; setTimeout(function(){ window.egoCheckingOut=false; }, 9000); });
                                    (function(){ var tb=document.querySelector('#pos_table tbody'); if(!tb) return; new MutationObserver(function(){ if(window.egoCheckingOut && tb.querySelectorAll('tr').length===0){ window.egoCheckingOut=false; if(typeof window.egoCloseActiveCart==='function') window.egoCloseActiveCart(); } }).observe(tb,{childList:true}); })();

                                    // زر إظهار/إخفاء المنتجات: عند الإخفاء تمتد السلة على كامل الصفحة
                                    $(document).on('click', '#ego_toggle_products', function(){
                                        $('body').toggleClass('ego-cart-full');
                                        var on = $('body').hasClass('ego-cart-full');
                                        $(this).find('.lbl').text(on ? 'إظهار المنتجات' : 'إخفاء المنتجات');
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
    
    <div class="tw-shadow-[rgba(17,_17,_26,_0.1)_0px_0px_16px] tw-rounded-2xl tw-bg-white tw-p-4" style="height: calc(100vh - 96px); max-height: calc(100vh - 96px); min-height: 0; display: flex; flex-direction: column; overflow: hidden;">
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

    {{-- 🆕 نافذة التعليق/الحجز (كانت مفقودة من هذه الشاشة) — لازمة لحفظ الطلبات المحجوزة --}}
    @include('sale_pos.partials.suspend_note_modal')

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

    {{-- 🆕 مُعالج مستقل لأسهم تمرير أسطر البيع (Vanilla JS، معزول عن باقي السكربتات لضمان عمله دائماً) --}}
    <script>
    (function(){
        // 🆕 إخراج صفوف العميل/الخيارات من صندوق التمرير وتثبيتها فوقه — فيبقى داخل الصندوق جدول البيع فقط (فيتجاوز الصندوق ويصير قابلاً للتمرير)
        function egoIsolateTable(){
            try {
                var scroll = document.getElementById('ego_cart_scroll');
                var table  = document.getElementById('pos_table');
                if (!scroll || !table || !scroll.contains(table)) { return; }
                var directChild = table;
                while (directChild.parentNode && directChild.parentNode !== scroll) { directChild = directChild.parentNode; }
                if (directChild.parentNode !== scroll) { return; }
                if (scroll.firstElementChild === directChild) { return; } // لا شيء قبل الجدول
                var fixedTop = document.getElementById('ego_cart_fixed_top');
                if (!fixedTop) {
                    fixedTop = document.createElement('div');
                    fixedTop.id = 'ego_cart_fixed_top';
                    fixedTop.style.flexShrink = '0';
                    fixedTop.style.background = '#fff';
                    scroll.parentNode.insertBefore(fixedTop, scroll);
                }
                while (scroll.firstChild && scroll.firstChild !== directChild) {
                    fixedTop.appendChild(scroll.firstChild);
                }
            } catch (e) {}
        }
        if (document.readyState === 'loading') { document.addEventListener('DOMContentLoaded', egoIsolateTable); }
        else { egoIsolateTable(); }
        setTimeout(egoIsolateTable, 800); // إعادة محاولة بعد اكتمال البناء

        // نجد العنصر الذي يتمرّر فعلاً ويحوي أسطر البيع (نُفضّل صندوق السلة، ثم أي سلف قابل للتمرير — عدا الصفحة نفسها)
        function egoFindScroller(){
            var box = document.getElementById('ego_cart_scroll');
            if (box && (box.scrollHeight - box.clientHeight) > 4) { return box; }
            var el = document.getElementById('pos_table');
            el = el ? el.parentElement : null;
            while (el && el !== document.body && el !== document.documentElement) {
                var oy = getComputedStyle(el).overflowY;
                if ((oy === 'auto' || oy === 'scroll') && (el.scrollHeight - el.clientHeight) > 4) { return el; }
                el = el.parentElement;
            }
            return box; // احتياط (قد لا يتمرّر، لكن لن نُمرّر الصفحة أبداً)
        }
        function egoScrollStep(dir){
            var el = egoFindScroller(); if (!el) { return; }
            var row = document.querySelector('#pos_table tbody tr');
            var rowH = (row && row.offsetHeight) ? row.offsetHeight : 56;
            var step = Math.max(rowH * 3, Math.round(el.clientHeight * 0.6));
            var before = el.scrollTop;
            el.scrollTop = before + (dir * step);
            // لو لم يتغيّر (لا تمرير في هذا العنصر) نبحث عن سلف آخر قابل للتمرير ونحرّكه
            if (el.scrollTop === before) {
                var p = el.parentElement;
                while (p && p !== document.body && p !== document.documentElement) {
                    if ((p.scrollHeight - p.clientHeight) > 4) { p.scrollTop = p.scrollTop + (dir * step); if (p.scrollTop !== 0 || dir < 0) break; }
                    p = p.parentElement;
                }
            }
        }
        var holdTimer = null, holdInterval = null;
        function stopHold(){ if (holdTimer) { clearTimeout(holdTimer); holdTimer = null; } if (holdInterval) { clearInterval(holdInterval); holdInterval = null; } }
        document.addEventListener('click', function(e){
            var btn = e.target.closest ? e.target.closest('.ego-scroll-btn') : null;
            if (!btn) { return; }
            e.preventDefault();
            egoScrollStep(btn.getAttribute('data-dir') === 'up' ? -1 : 1);
        });
        // ضغط مطوّل = تمرير مستمر مثل السكرول
        document.addEventListener('mousedown', function(e){
            var btn = e.target.closest ? e.target.closest('.ego-scroll-btn') : null;
            if (!btn) { return; }
            var dir = btn.getAttribute('data-dir') === 'up' ? -1 : 1;
            holdTimer = setTimeout(function(){ holdInterval = setInterval(function(){ egoScrollStep(dir); }, 350); }, 350);
        });
        ['mouseup','mouseleave','blur'].forEach(function(ev){ document.addEventListener(ev, stopHold, true); });

        // 🆕 تركيز + تظليل قيمة المدفوع بالأزرق عند التسديد (Vanilla — مضمون التنفيذ بلا اعتماد على jQuery/أحداث النافذة)
        function egoFocusPaid(){
            var el = document.getElementById('ego_paid_amount');
            if (el && el.offsetParent !== null) { el.focus(); el.select(); return true; }
            return false;
        }
        document.addEventListener('click', function(e){
            var t = e.target.closest ? e.target.closest('#ego_btn_checkout, .ego-cb-pay') : null;
            if (!t) { return; }
            // ننتظر فتح النافذة ثم نُركّز الحقل ونحدّد قيمته (عدة محاولات لتصمد أمام إعادة التعبئة)
            [300, 500, 750, 1000].forEach(function(ms){ setTimeout(egoFocusPaid, ms); });
        });
        // أي نقر/تركيز على الحقل يحدّد قيمته كاملةً بالأزرق
        ['click','focusin'].forEach(function(ev){
            document.addEventListener(ev, function(e){
                var el = e.target;
                if (el && el.id === 'ego_paid_amount') { setTimeout(function(){ try { el.select(); } catch(_){} }, 0); }
            });
        });
    })();
    </script>
@endsection