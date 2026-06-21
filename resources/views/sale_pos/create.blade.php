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
                        <div class="tw-shadow-[rgba(17,_17,_26,_0.1)_0px_0px_16px] tw-rounded-2xl tw-bg-white tw-p-2" style="height: calc(100vh - 24px); min-height: calc(100vh - 24px); display: flex; flex-direction: column;">
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

                               
                                <div class="pos-form-container" id="ego_cart_scroll" style="flex: 1 1 auto; min-height: 0; width: 100%; overflow-y: auto; overflow-x: hidden;">
                                    @include('sale_pos.partials.pos_form')
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
                                        <div class="ego-tot-row ego-tot-due"><span>المستحق</span><b class="ego-due-val">0.00</b></div>
                                    </div>
                                </div>

                                {{-- 🆕 صندوق المدفوع + الكيباد — يُنقل ليظهر أسفل المنتجات (يمين) --}}
                                <div id="ego_keypad_box">
                                    <div class="ego-kb-head">
                                        <div class="ego-kb-paid"><span class="lbl"><i class="fas fa-money-bill-wave"></i> المدفوع</span><input type="text" id="ego_paid_amount" placeholder="0.00" autocomplete="off" inputmode="decimal"></div>
                                        <div class="ego-kb-change"><span class="lbl"><i class="fas fa-hand-holding-usd"></i> الباقي</span><b id="ego_change_amount">0.00</b></div>
                                    </div>
                                    <div class="ego-kb-quick">
                                        <button type="button" data-amt="5">5</button>
                                        <button type="button" data-amt="10">10</button>
                                        <button type="button" data-amt="20">20</button>
                                        <button type="button" data-amt="50">50</button>
                                        <button type="button" data-amt="100">100</button>
                                        <button type="button" class="ego-key ego-clear" data-k="clear"><i class="fas fa-eraser"></i> مسح</button>
                                    </div>
                                    <div class="ego-kb-pay">
                                        <button type="button" class="ego-pay ego-pay-cash" id="ego_btn_cash"><i class="fas fa-money-bill-wave"></i> كاش</button>
                                        <button type="button" class="ego-pay ego-pay-card" data-ego-target='[data-pay_method="card"]'><i class="fas fa-credit-card"></i> بطاقة</button>
                                        <button type="button" class="ego-pay ego-pay-credit" data-ego-target='[data-pay_method="credit_sale"]'><i class="fas fa-user-friends"></i> آجل</button>
                                        <button type="button" class="ego-pay ego-pay-multi" data-ego-target='#pos-finalize'><i class="fas fa-money-check-alt"></i> طرق أخرى</button>
                                    </div>
                                </div>

                                {{-- 🆕 شريط أسفل الصفحة: ملاحظة (يمين) • أسهم تتحكّم بتمرير المنتجات (وسط) • عدّاد كنص (يسار) --}}
                                <div id="ego_cart_bar">
                                    <button type="button" id="ego_btn_note" class="ego-cb-note" data-toggle="modal" data-target="#ego_note_modal"><i class="fas fa-sticky-note"></i> ملاحظة</button>
                                    <img src="/img/sst-logo.png" alt="SST" class="ego-cb-logo">
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
                                        #pos_side_column{order:0 !important; overflow:visible !important; width:44% !important}
                                        #pos_main_column{order:1 !important; width:56% !important}
                                        #pos_side_column > div:first-child{height:calc(100vh - 24px) !important; min-height:calc(100vh - 24px) !important}
                                        /* عند ضغط "إخفاء المنتجات": السلة تمتد على كامل الصفحة */
                                        body.ego-cart-full #pos_side_column{display:none !important}
                                        body.ego-cart-full #pos_main_column{width:100% !important}
                                        @media (max-width:1100px){#pos_side_column{width:42% !important}#pos_main_column{width:58% !important}}
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
                                        #ego_keypad_box .ego-kb-pay{display:grid;grid-template-columns:repeat(2,1fr);gap:8px;margin-top:10px}
                                        #ego_keypad_box .ego-kb-pay .ego-pay{border:1px solid #e6eaef;background:#fff;border-radius:10px;padding:8px 6px;font-weight:800;font-size:13px;color:#334155;cursor:pointer;display:flex;flex-direction:row;align-items:center;justify-content:center;gap:8px;transition:.15s}
                                        #ego_keypad_box .ego-kb-pay .ego-pay i{font-size:20px}
                                        #ego_keypad_box .ego-kb-pay .ego-pay:hover{background:#f8fafc;transform:translateY(-1px)}
                                        #ego_keypad_box .ego-kb-pay .ego-pay-cash i{color:#16a34a}
                                        #ego_keypad_box .ego-kb-pay .ego-pay-card i{color:#2563eb}
                                        #ego_keypad_box .ego-kb-pay .ego-pay-credit i{color:#0891b2}
                                        #ego_keypad_box .ego-kb-pay .ego-pay-multi i{color:#0f172a}

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

                                        /* 🆕 القائمة الجانبية المنظّمة الثابتة على يمين الشاشة */
                                        #ego_side_panel{position:fixed;top:8px;right:6px;width:212px;max-height:calc(100vh - 16px);overflow-y:auto;z-index:1035;display:flex;flex-direction:column;gap:10px;direction:rtl;padding-bottom:8px}
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

                                        /* دفع مساحة على يمين الشاشة حتى لا تغطّي القائمة المحتوى + إخفاء الشريط السفلي القديم */
                                        body.ego-side-on #scrollable-container{padding-right:228px !important}
                                        @media (max-width:1200px){body.ego-side-on #scrollable-container{padding-right:154px !important}}
                                        body.ego-side-on .pos-form-actions{display:none !important}

                                        /* 🆕 شريط أسفل الصفحة (ملاحظة يمين / عدّاد يسار) */
                                        #ego_cart_bar{display:flex;align-items:center;justify-content:space-between;gap:10px;padding:8px 6px;border-top:1px solid #eef2f7;margin-top:6px}
                                        #ego_cart_bar .ego-cb-note{border:none;background:#16a34a;color:#fff;border-radius:10px;padding:8px 18px;font-weight:800;font-size:14px;cursor:pointer;display:flex;align-items:center;gap:6px}
                                        #ego_cart_bar .ego-cb-note:hover{filter:brightness(1.07)}
                                        #ego_cart_bar .ego-cb-toggle{border:1px solid #cbd5e1;background:#f8fafc;color:#334155;border-radius:10px;padding:8px 14px;font-weight:800;font-size:13px;cursor:pointer;display:flex;align-items:center;gap:6px}
                                        #ego_cart_bar .ego-cb-toggle:hover{background:#e2e8f0}
                                        #ego_cart_bar .ego-cb-toggle i{color:#2563eb}
                                        #ego_cart_bar .ego-cb-logo{height:80px;width:auto;object-fit:contain;opacity:.97}
                                        #ego_cart_bar .ego-cb-counts{display:flex;gap:18px;font-size:15px;font-weight:700;color:#334155}
                                        #ego_cart_bar .ego-cb-counts b{font-size:18px;color:#0f172a}
                                        /* 🆕 تصميم احترافي لبطاقات المنتجات والتبويبات */
                                        #product_list_body, #featured_products_box{gap:8px !important}
                                        #product_list_body .product_box, #featured_products_box .product_box{background:#fff;border:1px solid #e6eaef;border-radius:12px;overflow:hidden;cursor:pointer;transition:.18s;display:flex;flex-direction:column;box-shadow:0 2px 6px rgba(17,17,26,.05);height:100%}
                                        #product_list_body .product_box:hover, #featured_products_box .product_box:hover{transform:translateY(-2px);box-shadow:0 8px 18px rgba(37,99,235,.16);border-color:#93c5fd}
                                        #product_list_body .image-container, #featured_products_box .image-container{width:100%;height:80px;background-color:#f8fafc !important;background-size:contain !important;border-bottom:1px solid #f1f5f9}
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
                                        #ego_cart_scroll{max-height:calc(100vh - 250px) !important;overflow-y:scroll !important;overflow-x:hidden !important;scrollbar-width:auto;scrollbar-color:#64748b #e2e8f0}
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
                                            #ego_keypad_box .ego-kb-pay .ego-pay{font-size:12px;padding:9px 4px}
                                        }
                                        @media (max-width:992px){
                                            #ego_side_panel{width:144px}
                                            body.ego-side-on #scrollable-container{padding-right:156px !important}
                                            #ego_side_panel .ego-sp-grid{grid-template-columns:1fr}
                                            #product_list_body .image-container,#featured_products_box .image-container{height:64px}
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

                                        /* 🆕 إخفاء القائمة العلوية (pos-header) وإظهارها عند مرور الماوس بأعلى الشاشة */
                                        body.ego-pos-autohide .pos-header{position:fixed !important;top:-320px;left:0;right:0;z-index:1040;transition:top .25s ease;box-shadow:0 6px 16px rgba(0,0,0,.18);background:#fff}
                                        body.ego-pos-autohide .pos-header.ego-show{top:0}
                                        /* شريط رفيع شفّاف بأعلى الشاشة كمنطقة استشعار لإظهار القائمة */
                                        #ego_nav_hotzone{position:fixed;top:0;left:0;right:0;height:8px;z-index:1039;background:transparent}
                                    </style>

                                </div>

                                {{-- 🆕 القائمة الجانبية المنظّمة — تستبدل كل أزرار الأسفل (الكلي/المدفوع/الباقي ثم الدفع ثم العمليات ثم الخصومات) --}}
                                <div id="ego_side_panel">
                                    {{-- 1) الخصومات (فوق) --}}
                                    <div class="ego-sp-section">
                                        <div class="ego-sp-title">الخصومات</div>
                                        <div class="ego-sp-grid">
                                            <button type="button" class="ego-op ego-op-disc" data-toggle="modal" data-target="#ego_discount_modal"><i class="fas fa-percent"></i> خصم/عرض</button>
                                            <button type="button" class="ego-op ego-op-points" id="ego_btn_points" data-toggle="modal" data-target="#posEditDiscountModal"><i class="fas fa-star"></i> استبدال نقاط</button>
                                            <button type="button" class="ego-op ego-op-expense" id="ego_btn_expense"><i class="fas fa-minus-circle"></i> إضافة مصاريف</button>
                                            <button type="button" class="ego-op ego-op-ledger" id="ego_btn_ledger"><i class="fas fa-book"></i> كشف حساب</button>
                                        </div>
                                    </div>

                                    {{-- 2) العمليات (وسط) --}}
                                    <div class="ego-sp-section">
                                        <div class="ego-sp-title">العمليات</div>
                                        <div class="ego-sp-grid">
                                            <button type="button" class="ego-op ego-op-draft" data-ego-target='#pos-draft'><i class="fas fa-save"></i> مسودة</button>
                                            <button type="button" class="ego-op ego-op-gift" id="ego_btn_gift"><i class="fas fa-gift"></i> فاتورة هدية</button>
                                            <button type="button" class="ego-op ego-op-reserve" id="ego_btn_reserve"><i class="fas fa-thumbtack"></i> حجز</button>
                                            <button type="button" class="ego-op ego-op-cancel" data-ego-target='#pos-cancel'><i class="fas fa-times-circle"></i> إلغاء</button>
                                            <button type="button" class="ego-op ego-op-stock" id="ego_btn_stock" data-toggle="modal" data-target="#StockSearchModal"><i class="fas fa-boxes"></i> بحث مخزون</button>
                                            <button type="button" class="ego-op ego-op-returns" id="ego_btn_return_barcode" data-toggle="modal" data-target="#returnSearchModal"><i class="fas fa-barcode"></i> إرجاع بالباركود</button>
                                            <button type="button" class="ego-op ego-op-recent" data-ego-target="#recent-transactions"><i class="fas fa-history"></i> العمليات الأخيرة</button>
                                            <button type="button" class="ego-op ego-op-logout" id="ego_btn_logout"><i class="fas fa-sign-out-alt"></i> خروج</button>
                                        </div>
                                    </div>

                                    {{-- 🆕 فتح درج الكاش + إرسال الفيزا — أيقونات مستقلة آخر القائمة تستدعي أزرار النظام الأصلية --}}
                                    <button type="button" class="ego-op ego-op-drawer" id="ego_btn_drawer" style="width:100%"><i class="fas fa-cash-register"></i> فتح درج الكاش</button>
                                    <button type="button" class="ego-op ego-op-visa" data-ego-target="#pay_card_full" style="width:100%"><i class="fas fa-credit-card"></i> إرسال للفيزا</button>
                                    @if(empty($pos_settings['hide_product_suggestion']))
                                    <button type="button" class="ego-op ego-op-toggle" id="ego_toggle_products" style="width:100%"><i class="fas fa-th-large"></i> <span class="lbl">إخفاء المنتجات</span></button>
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
                                            <div class="modal-header" style="background:linear-gradient(135deg,#16a34a,#15803d)">
                                                <button type="button" class="close" data-dismiss="modal" aria-label="Close" style="color:#fff;opacity:.9"><span aria-hidden="true">&times;</span></button>
                                                <h4 class="modal-title"><i class="fas fa-sticky-note"></i> ملاحظة الفاتورة</h4>
                                            </div>
                                            <div class="modal-body">
                                                <textarea id="ego_note_text" class="ego-input" style="text-align:right;height:120px" placeholder="اكتب ملاحظة تظهر مع الفاتورة..."></textarea>
                                                <button type="button" id="ego_note_save" class="ego-pricecheck-btn" data-dismiss="modal" style="margin-top:12px;width:100%;border:none;border-radius:12px;padding:10px;font-weight:800;color:#fff;background:#16a34a;cursor:pointer"><i class="fas fa-check"></i> حفظ الملاحظة</button>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                {{-- 🆕 نافذة فحص السعر (للعرض فقط — لا تضيف القطعة للسلة) --}}
                                <div class="modal fade ego-modal" id="ego_price_check_modal" tabindex="-1" role="dialog" aria-hidden="true">
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
                                        var sub = hasItems ? egoReadText('.price_total') : 0;
                                        var total = egoGetDue();
                                        var sysDisc = hasItems ? egoReadText('#total_discount') : 0;
                                        // إن لم يُقرأ الخصم من النظام نحسب التوفير = المجموع − الإجمالي
                                        var disc = sysDisc > 0 ? sysDisc : Math.max(0, sub - total);
                                        $('#ego_t_sub').text(egoFmt(sub));
                                        $('#ego_t_disc').text(egoFmt(disc));
                                        $('#ego_t_total').text(egoFmt(total));
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
                                                    window.egoLastReceipt = receipt;          // نخزّن الفاتورة فقط (بدون طباعة)
                                                    if (typeof toastr !== 'undefined') { toastr.info('🧾 تمت العملية — اضغط "طباعة الفاتورة" للطباعة'); }
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
                                        if (lines.length === 0) { window.egoLastSig = ''; return; }
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
                                                // عرض مبلغ الخصم (إجمالي السطر) بلون مميّز للحزمة
                                                $tr.find('.ego-line-discount-display').text(D ? D.toFixed(2) : '0').css('color', info.bundle ? '#7c3aed' : '#dc2626');
                                                // التخزين للداتابيس: خصم لكل قطعة (fixed)
                                                var perUnit = qty > 0 ? (D / qty) : 0;
                                                $tr.find('.ego-line-discount-amount').val(perUnit.toFixed(4));
                                                $tr.find('.ego-line-discount-type').val('fixed');
                                                // إجمالي السطر بعد الخصم (السعر يبقى كما هو)
                                                var lineTotal = rowValue - D;
                                                if (lineTotal < 0) { lineTotal = 0; }
                                                if (typeof __write_number === 'function') { __write_number($tr.find('input.pos_line_total'), lineTotal, false); }
                                                else { $tr.find('input.pos_line_total').val(lineTotal.toFixed(2)); }
                                                $tr.find('span.pos_line_total_text').text((typeof __currency_trans_from_en === 'function') ? __currency_trans_from_en(lineTotal, true) : lineTotal.toFixed(2));
                                            });
                                            if (typeof pos_total_row === 'function') { pos_total_row(); }
                                            if (r.bundles && r.bundles.length && typeof toastr !== 'undefined') {
                                                toastr.success('🎁 طُبّقت حزمة: ' + r.bundles.join('، '));
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

                                    // تفعيل وضع القائمة الجانبية (يخفي الشريط السفلي ويفسح مساحة يمين الشاشة)
                                    document.body.classList.add('ego-side-on');

                                    // ---------- 🆕 إخفاء القائمة العلوية وإظهارها عند مرور الماوس بأعلى الشاشة ----------
                                    (function egoAutoHideNav(){
                                        var header = document.querySelector('.pos-header') || document.querySelector('.main-header');
                                        if (!header) { return; }
                                        document.body.classList.add('ego-pos-autohide');
                                        // منطقة استشعار رفيعة بأعلى الشاشة
                                        if (!document.getElementById('ego_nav_hotzone')) {
                                            var hz = document.createElement('div');
                                            hz.id = 'ego_nav_hotzone';
                                            document.body.appendChild(hz);
                                            hz.addEventListener('mouseenter', function(){ header.classList.add('ego-show'); });
                                        }
                                        // إظهار عند اقتراب الماوس من أعلى الشاشة
                                        document.addEventListener('mousemove', function(e){
                                            if (e.clientY <= 6) { header.classList.add('ego-show'); }
                                        });
                                        // إخفاء عند مغادرة الماوس للقائمة
                                        header.addEventListener('mouseleave', function(){ header.classList.remove('ego-show'); });
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
    
    <div class="tw-shadow-[rgba(17,_17,_26,_0.1)_0px_0px_16px] tw-rounded-2xl tw-bg-white tw-p-4" style="height: calc(100vh - 24px); min-height: calc(100vh - 24px); display: flex; flex-direction: column;">
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
@endsection