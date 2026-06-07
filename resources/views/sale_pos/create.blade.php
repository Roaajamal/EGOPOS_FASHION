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

                               
                                <div class="pos-form-container" id="ego_cart_scroll" style="flex: 1 1 auto; min-height: 0; width: 100%; overflow: auto;">
                                    @include('sale_pos.partials.pos_form')
                                </div>

                                <div class="pos-totals-section" style="margin-top: auto; flex-shrink: 0; width: 100%; background: #fff; padding-top: 10px;">
                                    @include('sale_pos.partials.pos_form_totals')
                                    @include('sale_pos.partials.payment_modal')
                                </div>

                                {{-- 🆕 ملخص الدفع (أسفل المجاميع): الكلي / المدفوع (+أزرار سريعة) / الباقي --}}
                                <div id="ego_pay_summary">
                                    <div class="ego-ps-box ego-ps-total">
                                        <span class="lbl"><i class="fas fa-receipt"></i> الكلي</span>
                                        <span class="val ego-due-val">0.00</span>
                                    </div>
                                    <div class="ego-ps-paid">
                                        <span class="lbl"><i class="fas fa-money-bill-wave" style="color:#16a34a"></i> المدفوع</span>
                                        <input type="text" id="ego_paid_amount" class="ego-ps-input" placeholder="0.00" autocomplete="off" inputmode="decimal">
                                        <div class="ego-ps-quick">
                                            <button type="button" data-amt="5">5</button>
                                            <button type="button" data-amt="10">10</button>
                                            <button type="button" data-amt="20">20</button>
                                            <button type="button" data-amt="50">50</button>
                                            <button type="button" data-amt="100">100</button>
                                            <button type="button" class="ego-key ego-clear" data-k="clear"><i class="fas fa-eraser"></i></button>
                                        </div>
                                    </div>
                                    <div class="ego-ps-box ego-ps-change">
                                        <span class="lbl"><i class="fas fa-hand-holding-usd"></i> الباقي</span>
                                        <span class="val" id="ego_change_amount">0.00</span>
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
                                        #ego_side_panel .ego-sp-title{font-size:11px;font-weight:800;color:#64748b;margin-bottom:8px;text-align:center}
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
                                        #ego_side_panel .ego-op-stock i{color:#0284c7}
                                        #ego_side_panel .ego-op-recent i{color:#0d9488}
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
                                        #ego_cart_bar .ego-cb-logo{height:80px;width:auto;object-fit:contain;opacity:.97}
                                        #ego_cart_bar .ego-cb-counts{display:flex;gap:18px;font-size:15px;font-weight:700;color:#334155}
                                        #ego_cart_bar .ego-cb-counts b{font-size:18px;color:#0f172a}
                                        /* جعل شريط التمرير الأفقي للمنتجات أسفل المنطقة (وليس أعلى) */
                                        #ego_cart_scroll .table-responsive{overflow:visible !important;border:0 !important}

                                        /* 🆕 ملخص الدفع تحت المنتجات (مُصغّر ليبقى مكان المنتجات أوسع) */
                                        #ego_pay_summary{display:flex;align-items:center;gap:8px;padding:5px 6px;flex-wrap:wrap;border-top:1px solid #eef2f7;flex-shrink:0}
                                        #ego_pay_summary .ego-ps-box{flex:1;min-width:90px;border-radius:9px;padding:4px 10px;display:flex;align-items:center;justify-content:space-between;gap:6px}
                                        #ego_pay_summary .ego-ps-box .lbl{font-size:11px;font-weight:700;opacity:.85}
                                        #ego_pay_summary .ego-ps-box .val{font-size:16px;font-weight:800}
                                        #ego_pay_summary .ego-ps-total{background:#f1f5f9;color:#0f172a;border:1px solid #e2e8f0}
                                        #ego_pay_summary .ego-ps-change{background:#ecfdf5;border:1px dashed #10b981;color:#047857}
                                        #ego_pay_summary .ego-ps-paid{flex:1.5;min-width:150px;display:flex;align-items:center;gap:6px;background:#f8fafc;border:1px solid #e6eaef;border-radius:9px;padding:4px 8px}
                                        #ego_pay_summary .ego-ps-paid .lbl{font-size:11px;font-weight:700;color:#475569;white-space:nowrap}
                                        #ego_pay_summary .ego-ps-input{width:70px;border:1px solid #2563eb;border-radius:8px;padding:4px;text-align:center;font-size:15px;font-weight:800;color:#1d4ed8;background:#fff}
                                        #ego_pay_summary .ego-ps-quick{display:flex;gap:3px;flex-wrap:wrap}
                                        #ego_pay_summary .ego-ps-quick button{border:1px solid #34d399;background:#ecfdf5;color:#047857;border-radius:7px;padding:3px 7px;font-weight:700;cursor:pointer;font-size:11px}
                                        #ego_pay_summary .ego-ps-quick button:hover{background:#34d399;color:#fff}

                                        /* 🆕 إخفاء القائمة العلوية (pos-header) وإظهارها عند مرور الماوس بأعلى الشاشة */
                                        body.ego-pos-autohide .pos-header{position:fixed !important;top:-320px;left:0;right:0;z-index:1040;transition:top .25s ease;box-shadow:0 6px 16px rgba(0,0,0,.18);background:#fff}
                                        body.ego-pos-autohide .pos-header.ego-show{top:0}
                                        /* شريط رفيع شفّاف بأعلى الشاشة كمنطقة استشعار لإظهار القائمة */
                                        #ego_nav_hotzone{position:fixed;top:0;left:0;right:0;height:8px;z-index:1039;background:transparent}
                                    </style>

                                </div>

                                {{-- 🆕 القائمة الجانبية المنظّمة — تستبدل كل أزرار الأسفل (الكلي/المدفوع/الباقي ثم الدفع ثم العمليات ثم الخصومات) --}}
                                <div id="ego_side_panel">
                                    {{-- طرق الدفع --}}
                                    <div class="ego-sp-section">
                                        <div class="ego-sp-title">طرق الدفع</div>
                                        <div class="ego-sp-grid">
                                            <button type="button" class="ego-pay ego-pay-cash" id="ego_btn_cash"><i class="fas fa-money-bill-wave"></i> كاش</button>
                                            <button type="button" class="ego-pay ego-pay-card" data-ego-target='[data-pay_method="card"]'><i class="fas fa-credit-card"></i> بطاقة</button>
                                            <button type="button" class="ego-pay ego-pay-credit" data-ego-target='[data-pay_method="credit_sale"]'><i class="fas fa-user-friends"></i> آجل</button>
                                            <button type="button" class="ego-pay ego-pay-multi" data-ego-target='#pos-finalize'><i class="fas fa-money-check-alt"></i> طرق أخرى</button>
                                        </div>
                                    </div>

                                    {{-- 3) العمليات: مسودة / هدية / تعليق / عرض سعر / إلغاء --}}
                                    <div class="ego-sp-section">
                                        <div class="ego-sp-title">العمليات</div>
                                        <div class="ego-sp-grid">
                                            <button type="button" class="ego-op ego-op-draft" data-ego-target='#pos-draft'><i class="fas fa-save"></i> مسودة</button>
                                            <button type="button" class="ego-op ego-op-gift" data-ego-target='#is_gift_receipt'><i class="fas fa-gift"></i> هدية</button>
                                            <button type="button" class="ego-op ego-op-reserve" id="ego_btn_reserve"><i class="fas fa-thumbtack"></i> حجز</button>
                                            <button type="button" class="ego-op ego-op-cancel" data-ego-target='#pos-cancel'><i class="fas fa-times-circle"></i> إلغاء</button>
                                            <button type="button" class="ego-op ego-op-stock" id="ego_btn_stock" data-toggle="modal" data-target="#StockSearchModal"><i class="fas fa-boxes"></i> بحث مخزون</button>
                                            <button type="button" class="ego-op ego-op-returns" id="ego_btn_returns"><i class="fas fa-undo"></i> المرتجعات</button>
                                            <button type="button" class="ego-op ego-op-recent" data-ego-target="#recent-transactions"><i class="fas fa-history"></i> العمليات الأخيرة</button>
                                            <button type="button" class="ego-op ego-op-logout" id="ego_btn_logout"><i class="fas fa-sign-out-alt"></i> خروج</button>
                                        </div>
                                    </div>

                                    {{-- 4) الخصومات وفحص السعر --}}
                                    <div class="ego-sp-section">
                                        <div class="ego-sp-title">الخصومات</div>
                                        <div class="ego-sp-grid">
                                            <button type="button" class="ego-op ego-op-disc" data-toggle="modal" data-target="#ego_discount_modal"><i class="fas fa-percent"></i> خصم/عرض</button>
                                            <button type="button" class="ego-op ego-op-reserved" id="ego_btn_reserved"><i class="fas fa-bookmark"></i> الطلبات المحجوزة</button>
                                            <button type="button" class="ego-op ego-op-ledger" id="ego_btn_ledger"><i class="fas fa-book"></i> كشف حساب</button>
                                        </div>
                                    </div>
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
                                    function egoUpdateDue(){ $('.ego-due-val').text(egoFmt(egoGetDue())); egoCalcChange(); }
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

                                    // كيباد المدفوع
                                    $('#ego_paid_amount').on('input', egoCalcChange);
                                    $(document).on('click', '.ego-key', function(){
                                        var k = $(this).data('k'); var $inp = $('#ego_paid_amount');
                                        if (k === 'clear') { $inp.val(''); }
                                        else if (k === 'back') { $inp.val(($inp.val()||'').slice(0,-1)); }
                                        else { $inp.val(($inp.val()||'') + k); }
                                        egoCalcChange();
                                    });
                                    $(document).on('click', '[data-amt]', function(){
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
                                        // ضمان إعادة حساب الكلي فوراً (الخصم ينخصم من الكلي)
                                        if (typeof pos_total_row === 'function') { try { pos_total_row(); } catch(e){} }
                                        setTimeout(egoUpdateDue, 150);
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

                                    // زر كاش: دفع نقدي عادي يسجّل قيمة الطلب الصحيحة (الدرج = قيمة الطلب).
                                    // "المدفوع/الباقي" حاسبة مساعدة للكاشير فقط ولا تؤثر على الفاتورة أو الدرج.
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
                                        // لو ما في عميل حقيقي مختار → نفتح نافذة إضافة/اختيار العميل (يُخزَّن عند العملاء)
                                        if (!egoCustomerIsReal()){
                                            var addBtn = document.querySelector('.add_new_customer');
                                            if (addBtn) { addBtn.click(); }
                                            if (typeof toastr !== 'undefined') { toastr.info('اختر العميل أو أضِفه أولاً، ثم اضغط "حجز" مرة أخرى'); }
                                            return;
                                        }
                                        // عميل حقيقي مختار → نكمل الحجز باسمه (يظهر في قائمة الطلبات المحجوزة)
                                        var custName = ($('#customer_id option:selected').text() || '').trim() || 'عميل';
                                        egoAskReserveType(custName);
                                    });

                                    // اختصار المرتجعات: يفتح قائمة المرتجعات في تبويب جديد (نقطة 4)
                                    $(document).on('click', '#ego_btn_returns', function(){ window.open('/sell-return', '_blank'); });

                                    // زر الطلبات المحجوزة: يفتح قائمة المسودّات (الطلبات المحجوزة) في تبويب جديد
                                    $(document).on('click', '#ego_btn_reserved', function(){ window.open('/sells/drafts', '_blank'); });

                                    // اختصار تسجيل الخروج (نقطة 5)
                                    $(document).on('click', '#ego_btn_logout', function(){
                                        if (confirm('هل تريد تسجيل الخروج؟')) { window.location.href = '/logout'; }
                                    });

                                    // زر "كشف حساب" بالقائمة الجانبية يستدعي دالة كشف الحساب الأصلية
                                    $(document).on('click', '#ego_btn_ledger', function(){ if (typeof openCustomerLedger === 'function') { openCustomerLedger(); } });

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
                                    function egoSyncSideButtons(){
                                        $('[data-ego-target]').each(function(){
                                            if ($($(this).data('ego-target')).length) { $(this).show(); } else { $(this).hide(); }
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