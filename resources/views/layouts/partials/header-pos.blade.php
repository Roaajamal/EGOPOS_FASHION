<!-- default value -->
@php
    $go_back_url = action([\App\Http\Controllers\SellPosController::class, 'index']);
    $transaction_sub_type = '';
    $view_suspended_sell_url = action([\App\Http\Controllers\SellController::class, 'index']) . '?suspended=1';
    $pos_redirect_url = action([\App\Http\Controllers\SellPosController::class, 'create']);
@endphp

@if (!empty($pos_module_data))
    @foreach ($pos_module_data as $key => $value)
        @php
            if (!empty($value['go_back_url'])) {
                $go_back_url = $value['go_back_url'];
            }

            if (!empty($value['transaction_sub_type'])) {
                $transaction_sub_type = $value['transaction_sub_type'];
                $view_suspended_sell_url .= '&transaction_sub_type=' . $transaction_sub_type;
                $pos_redirect_url .= '?sub_type=' . $transaction_sub_type;
            }
        @endphp
    @endforeach
@endif
<input type="hidden" name="transaction_sub_type" id="transaction_sub_type" value="{{ $transaction_sub_type }}">
@inject('request', 'Illuminate\Http\Request')
<div class="col-md-12 no-print pos-header">
    <input type="hidden" id="pos_redirect_url" value="{{ $pos_redirect_url }}">
    <div
        class="tw-flex tw-flex-col md:tw-flex-row tw-items-center tw-justify-between tw-shadow-[rgba(17,_17,_26,_0.1)_0px_0px_16px] tw-bg-white tw-rounded-xl tw-mx-0 tw-mt-1 tw-mb-0 md:tw-mb-0 tw-p-3">
        <div class="tw-w-full md:tw-w-1/3">
            <div class="tw-flex tw-items-center tw-gap-2">
                {{-- 🆕 بوكس الفرع منسّق مع التاريخ ورقم الفاتورة (التسمية على استقامة القائمة) --}}
                {{-- 🆕 بطاقة الفرع بتصميم أنيق: شارة أيقونة + التسمية فوق القيمة --}}
                <div class="tw-flex tw-items-center" style="gap:10px;background:#fff;border:1.5px solid #99f6e4;border-radius:12px;padding:6px 12px;box-shadow:0 3px 12px rgba(13,148,136,.14);white-space:nowrap">
                    <span style="width:32px;height:32px;border-radius:9px;background:linear-gradient(135deg,#0d9488,#0f766e);display:inline-flex;align-items:center;justify-content:center;flex:0 0 32px">
                        <i class="fa fa-store" style="color:#fff;font-size:14px"></i>
                    </span>
                    <div style="display:flex;flex-direction:column;line-height:1.2">
                        <span style="font-size:10px;color:#0d9488;font-weight:800;letter-spacing:.3px">@lang('sale.location')</span>
                        <div style="min-width:110px;font-weight:800;color:#0f172a;font-size:13px">
                            @if (empty($transaction->location_id))
                                @if (count($business_locations) > 1)
                                    {!! Form::select(
                                        'select_location_id',
                                        $business_locations,
                                        $default_location->id ?? null,
                                        ['class' => 'input-sm', 'id' => 'select_location_id', 'required', 'autofocus', 'style' => 'height:24px;border:none;padding:0;font-weight:800;color:#0f172a;background:transparent;box-shadow:none;cursor:pointer'],
                                        $bl_attributes,
                                    ) !!}
                                @else
                                    <span>{{ $default_location->name }}</span>
                                @endif
                            @else
                                <span>{{ $transaction->location->name }}</span>
                            @endif
                        </div>
                    </div>
                </div>
                {{-- 🆕 بوكس التاريخ منسّق ومصغّر --}}
                <div class="tw-hidden md:tw-flex tw-items-center tw-gap-2" style="background:#0d9488;border-radius:10px;padding:9px 14px;box-shadow:0 2px 8px rgba(0,0,0,.12);white-space:nowrap">
                    <i class="fa fa-clock text-white" style="font-size:14px;opacity:.9"></i>
                    <span class="curr_datetime text-white" style="font-size:12px;font-weight:600">{{ @format_datetime('now') }}</span>
                    <i class="fa fa-keyboard hover-q text-white" aria-hidden="true" data-container="body"
                        data-toggle="popover" data-placement="bottom" data-content="@include('sale_pos.partials.keyboard_shortcuts_details')"
                        data-html="true" data-trigger="hover" data-original-title="" title="" style="opacity:.8;cursor:pointer"></i>
                </div>

                 <!--   invoice number  006  -->
               @if(!empty($pos_settings['enable_invoice_number']))
        {{-- 🆕 بوكس رقم الفاتورة — بنفس حجم وتنسيق بوكس التاريخ المجاور --}}
        <div class="tw-hidden md:tw-flex tw-items-center tw-gap-2" style="background:#0d9488;color:#fff;border-radius:10px;padding:9px 14px;box-shadow:0 2px 8px rgba(0,0,0,.12);direction:rtl;white-space:nowrap;flex-wrap:nowrap">
            <i class="fas fa-file-invoice" style="font-size:15px;opacity:.9"></i>
            <span style="font-size:12px;font-weight:600;opacity:.9;white-space:nowrap">@lang('sale.invoice_number'):</span>
            <span id="next_invoice_no_display" style="font-size:13px;font-weight:800;color:#fff;white-space:nowrap">{{ $next_invoice_no }}</span>
        </div>
@endif
                  <!--   invoice number  006  -->
                     
                @if (empty($pos_settings['hide_product_suggestion']))
                    <button type="button" title="{{ __('lang_v1.view_products') }}" data-placement="bottom"
                        class="tw-shadow-[rgba(17,_17,_26,_0.1)_0px_0px_16px] tw-bg-white hover:tw-bg-white/60 tw-cursor-pointer tw-border-2 tw-flex tw-items-center tw-justify-center tw-rounded-md tw-w-8 tw-h-8 tw-text-gray-600 btn-modal pull-right tw-block md:tw-hidden"
                        data-toggle="modal" data-target="#mobile_product_suggestion_modal">
                        <strong><i class="fa fa-cubes fa-lg tw-text-[#00935F] !tw-text-sm"></i></strong>
                    </button>
                @endif

                <span class="tw-block md:tw-hidden">
                    <i class="fas hamburger fa-bars tw-mx-5"
                        onclick="document.getElementById('pos_header_more_options').classList.toggle('tw-hidden')"></i>
                </span>

            </div>
        </div>

    

        <div class="tw-w-full md:tw-w-2/3 !tw-p-0 tw-flex tw-items-center tw-justify-between tw-gap-4 tw-flex-col md:tw-flex-row tw-hidden md:tw-flex"
            id="pos_header_more_options">
            <a href="{{ $go_back_url }}" title="{{ __('lang_v1.go_back') }}"
                class="tw-shadow-[rgba(17,_17,_26,_0.1)_0px_0px_16px] tw-bg-white hover:tw-bg-white/60 tw-cursor-pointer tw-border-2 tw-flex tw-items-center tw-justify-center tw-rounded-md md:tw-w-8 tw-w-auto tw-h-8 tw-text-gray-600 pull-right">
                <strong class="!tw-m-3">
                    <i class="fa fa-backward fa-lg fa fa-backward tw-fa-lg tw-text-[#009EE4] !tw-text-sm"></i>
                    <span class="tw-inline md:tw-hidden">{{ __('lang_v1.go_back') }}</span>
                </strong>
            </a>

            {{-- <a href="{{ $go_back_url }}" title="{{ __('lang_v1.go_back') }}"
              class="md:tw-hidden tw-shadow-[rgba(17,_17,_26,_0.1)_0px_0px_16px] tw-bg-white hover:tw-bg-white/60 tw-cursor-pointer tw-border-2 tw-flex tw-items-center tw-justify-center tw-rounded-md md:tw-w-8 tw-w-auto tw-h-8 tw-text-gray-600 pull-right">
            <strong class="!tw-m-3">
                <i class="fa fa-backward fa-lg fa fa-backward tw-fa-lg tw-text-[#009EE4] !tw-text-sm"></i>
                <span class="tw-inline md:tw-hidden">{{ __('lang_v1.go_back') }}</span>
            </strong>
          </a> --}}

            @if (!isset($pos_settings['hide_recent_trans']) || $pos_settings['hide_recent_trans'] == 0)
                <button type="button"
                    class="md:tw-hidden tw-shadow-[rgba(17,_17,_26,_0.1)_0px_0px_16px] tw-bg-white hover:tw-bg-white/60 tw-cursor-pointer tw-border-2 tw-flex tw-items-center tw-justify-center tw-rounded-md md:tw-w-8 tw-w-auto tw-h-8 tw-text-gray-600 pull-right"
                    data-toggle="modal" data-target="#recent_transactions_modal" id="recent-transactions">
                        <strong class="!tw-m-3">
                            <i class="fa fa-clock fa-lg tw-text-[#646EE4] !tw-text-sm"></i>
                            <span class="tw-inline md:tw-hidden">{{ __('lang_v1.recent_transactions') }}</span>
                        </strong>
                </button>
            @endif

            @if (!empty($pos_settings['inline_service_staff']))
                <button type="button" id="show_service_staff_availability"
                    title="{{ __('lang_v1.service_staff_availability') }}"
                    class="tw-shadow-[rgba(17,_17,_26,_0.1)_0px_0px_16px] tw-bg-white hover:tw-bg-white/60 tw-cursor-pointer tw-border-2 tw-flex tw-items-center tw-justify-center tw-rounded-md md:tw-w-8 tw-w-auto tw-h-8 tw-text-gray-600 pull-right"
                    data-container=".view_modal"
                    data-href="{{ action([\App\Http\Controllers\SellPosController::class, 'showServiceStaffAvailibility']) }}">
                    <strong class="!tw-m-3">
                        <i class="fa fa-users fa-lg tw-text-[#646EE4] !tw-text-sm"></i>
                        <span class="tw-inline md:tw-hidden">{{ __('lang_v1.service_staff_availability') }}</span>
                    </strong>
                </button>
            @endif

            @can('close_cash_register')
                <button type="button" id="close_register" title="{{ __('cash_register.close_register') }}"
                    class="tw-shadow-[rgba(17,_17,_26,_0.1)_0px_0px_16px] tw-bg-white hover:tw-bg-white/60 tw-cursor-pointer tw-border-2 tw-flex tw-items-center tw-justify-center tw-rounded-md md:tw-w-8 tw-w-auto tw-h-8 tw-text-gray-600 btn-modal pull-right"
                    data-container=".close_register_modal"
                    data-href="{{ action([\App\Http\Controllers\CashRegisterController::class, 'getCloseRegister']) }}">
                    <strong class="!tw-m-3">
                        <i class="fa fa-window-close fa-lg tw-text-[#EF4B53] !tw-text-sm"></i>
                        <span class="tw-inline md:tw-hidden">{{ __('cash_register.close_register') }}</span>
                    </strong>
                </button>
            @endcan

            @if (
                !empty($pos_settings['inline_service_staff']) ||
                    (in_array('tables', $enabled_modules) || in_array('service_staff', $enabled_modules)))
                <button type="button"
                    class="tw-shadow-[rgba(17,_17,_26,_0.1)_0px_0px_16px] tw-bg-white hover:tw-bg-white/60 tw-cursor-pointer tw-border-2 tw-flex tw-items-center tw-justify-center tw-rounded-md md:tw-w-8 tw-w-auto tw-h-8 tw-text-gray-600 pull-right popover-default"
                    id="service_staff_replacement" title="{{ __('restaurant.service_staff_replacement') }}"
                    data-toggle="popover" data-trigger="click"
                    data-content='<div class="m-8"><input type="text" class="form-control" placeholder="@lang('sale.invoice_no')" id="send_for_sell_service_staff_invoice_no"></div><div class="w-100 text-center"><button type="button" class="tw-dw-btn tw-dw-btn-xs tw-dw-btn-outline tw-dw-btn-error" id="send_for_sercice_staff_replacement">@lang('lang_v1.send')</button></div>'
                    data-html="true" data-placement="bottom">

                    <strong class="!tw-m-3">
                        <i class="fa fa-user-plus fa-lg tw-text-[#646EE4] !tw-text-sm"></i>
                        <span class="tw-inline md:tw-hidden">{{ __('restaurant.service_staff_replacement') }}</span>
                    </strong>
                </button>
            @endif

            @can('view_cash_register')
                <button type="button" id="register_details" title="{{ __('cash_register.register_details') }}"
                    class="tw-shadow-[rgba(17,_17,_26,_0.1)_0px_0px_16px] tw-bg-white hover:tw-bg-white/60 tw-cursor-pointer tw-border-2 tw-flex tw-items-center tw-justify-center tw-rounded-md md:tw-w-8 tw-w-auto tw-h-8 tw-text-gray-600 btn-modal pull-right"
                    data-container=".register_details_modal"
                    data-href="{{ action([\App\Http\Controllers\CashRegisterController::class, 'getRegisterDetails']) }}">

                    <strong class="!tw-m-3">
                        <i class="fa fa-briefcase tw-fa-lg tw-text-[#00935F] !tw-text-sm" aria-hidden="true"></i>
                        <span class="tw-inline md:tw-hidden">{{ __('cash_register.register_details') }}</span>
                    </strong>
                </button>
            @endcan

            <button title="@lang('lang_v1.calculator')" id="btnCalculator" type="button"
                class="tw-shadow-[rgba(17,_17,_26,_0.1)_0px_0px_16px] tw-bg-white hover:tw-bg-white/60 tw-cursor-pointer tw-border-2 tw-flex tw-items-center tw-justify-center tw-rounded-md md:tw-w-8 tw-w-auto tw-h-8 tw-text-gray-600 pull-right popover-default"
                data-toggle="popover" data-trigger="click" data-content='@include('layouts.partials.calculator')' data-html="true"
                data-placement="bottom">


                <strong class="!tw-m-3">
                    <i class="fa fa-calculator fa-lg tw-text-[#00935F] !tw-text-sm" aria-hidden="true"></i>
                    <span class="tw-inline md:tw-hidden">{{ __('lang_v1.calculator') }}</span>
                </strong>
            </button>

            <button type="button"
                class="tw-shadow-[rgba(17,_17,_26,_0.1)_0px_0px_16px] tw-bg-white hover:tw-bg-white/60 tw-cursor-pointer tw-border-2 tw-flex tw-items-center tw-justify-center tw-rounded-md md:tw-w-8 tw-w-auto tw-h-8 tw-text-gray-600 pull-right pull-right popover-default"
                id="return_sale" title="@lang('lang_v1.sell_return')" data-toggle="popover" data-trigger="click"
                data-content='<div class="m-8"><input type="text" class="form-control" placeholder="@lang('sale.invoice_no')" id="send_for_sell_return_invoice_no"></div><div class="w-100 text-center"><button type="button" class="tw-dw-btn tw-dw-btn-error tw-text-white tw-dw-btn-sm" id="send_for_sell_return">@lang('lang_v1.send')</button></div>'
                data-html="true" data-placement="bottom">
                <strong class="!tw-m-3">
                    <i class="fas fa-undo fa-lg tw-text-[#EF4B53] !tw-text-sm"></i>
                    <span class="tw-inline md:tw-hidden">{{ __('lang_v1.sell_return') }}</span>
                </strong>
            </button>


            <button type="button" title="{{ __('lang_v1.full_screen') }}"
                class="tw-shadow-[rgba(17,_17,_26,_0.1)_0px_0px_16px] tw-bg-white hover:tw-bg-white/60 tw-cursor-pointer tw-border-2 tw-flex tw-items-center tw-justify-center tw-rounded-md md:tw-w-8 tw-w-auto tw-h-8 tw-text-gray-600 pull-right"
                id="full_screen">
                <strong class="!tw-m-3">
                    <i class="fa fa-window-maximize fa-lg tw-text-[#646EE4] !tw-text-sm"></i>
                    <span class="tw-inline md:tw-hidden">Full Screen</span>
                </strong>
            </button>

            <button type="button" id="view_suspended_sales" title="{{ __('lang_v1.view_suspended_sales') }}"
                class="tw-shadow-[rgba(17,_17,_26,_0.1)_0px_0px_16px] tw-bg-white hover:tw-bg-white/60 tw-cursor-pointer tw-border-2 tw-flex tw-items-center tw-justify-center tw-rounded-md md:tw-w-8 tw-w-auto tw-h-8 tw-text-gray-600 btn-modal pull-right"
                data-container=".view_modal" data-href="{{ $view_suspended_sell_url }}">
                <strong class="!tw-m-3">
                    <i class="fa fa-pause-circle fa-lg tw-text-[#A5ADBB] !tw-text-sm"></i>
                    <span class="tw-inline md:tw-hidden">{{ __('lang_v1.view_suspended_sales') }}</span>
                </strong>
            </button>
            @if (!empty($pos_settings['customer_display_screen']))
                <a href="{{route('pos_display')}}" id="customer_display_screen"  onclick="window.open(this.href, 'customer_display', 'width='+screen.width+',height='+screen.height+',top=0,left=0'); return false;"   title="{{ __('lang_v1.customer_display_screen') }}"
                    class="tw-shadow-[rgba(17,_17,_26,_0.1)_0px_0px_16px] tw-bg-white hover:tw-bg-white/60 tw-cursor-pointer tw-border-2 tw-flex tw-items-center tw-justify-center tw-rounded-md md:tw-w-8 tw-w-auto tw-h-8 tw-text-gray-600 pull-right">
                    <strong class="!tw-m-3">
                        <i class="fa fa-tv fa-lg tw-text-[#646EE4] !tw-text-sm"></i>
                        <span class="tw-inline md:tw-hidden">{{ __('lang_v1.customer_display_screen') }}</span>
                    </strong>
                </a>
            @endif


            @if (Module::has('Repair') && $transaction_sub_type != 'repair')
                @include('repair::layouts.partials.pos_header')
            @endif

            @if (in_array('pos_sale', $enabled_modules) && !empty($transaction_sub_type))
                @can('sell.create')
                    <a href="{{ action([\App\Http\Controllers\SellPosController::class, 'create']) }}"
                        title="@lang('sale.pos_sale')"
                        class="tw-shadow-[rgba(17,_17,_26,_0.1)_0px_0px_16px] tw-bg-white hover:tw-bg-white/60 tw-cursor-pointer tw-border-2 tw-w-auto tw-h-auto tw-py-1 tw-px-4 tw-rounded-md pull-right">
                        <strong><i class="fa fa-th-large tw-text-[#00935F] !tw-text-sm"></i> &nbsp;
                            @lang('sale.pos_sale')</strong>
                    </a>
                @endcan
            @endif

            
            <!--  sell return by barcode button  001  --> 
             <button type="button" 
                class="tw-shadow-[rgba(17,_17,_26,_0.1)_0px_0px_16px] tw-bg-white hover:tw-bg-white/60 tw-cursor-pointer tw-border-2 tw-w-auto tw-h-auto tw-py-1 tw-px-4 tw-rounded-md btn-modal pull-right"
                 onclick="$('#returnSearchModal').modal('show');">
                    <strong><i class="fa fa-barcode" aria-hidden="true"></i> ارجاع</strong>
                </button>

           </button>
            <!--  sell return by barcode button  001  -->

            <!--  search quantity of product   009  --> 
             @can('enable_search_quantity')
             <button type="button" 
    class="tw-shadow-[rgba(17,_17,_26,_0.1)_0px_0px_16px] tw-bg-white hover:tw-bg-white/60 tw-cursor-pointer tw-border-2 tw-w-auto tw-h-auto tw-py-1 tw-px-4 tw-rounded-md btn-modal pull-right"
    onclick="$('#StockSearchModal').modal('show');">
    <strong><i class="fa fa-search" aria-hidden="true"></i> بحث عن مخزون</strong>
</button>
@endcan

           </button>
            <!--  search quantity of product   009   -->
            
            <!-- Customer Ledger Modal Button -->
             @can('enable_customer_ledger')
<button type="button"
    class="tw-shadow-[rgba(17,_17,_26,_0.1)_0px_0px_16px] tw-bg-white hover:tw-bg-white/60 tw-cursor-pointer tw-border-2 tw-w-auto tw-h-auto tw-py-1 tw-px-4 tw-rounded-md pull-right"
    onclick="openCustomerLedger()">
    <strong>
        <i class="fa fa-book tw-text-[#646EE4] !tw-text-sm"></i> &nbsp;
          @lang('lang_v1.customer_ledger') 
    </strong>
</button>
@endcan
<!-- Customer Ledger Modal Button --> 


            @can('expense.add')
                <button type="button" title="{{ __('expense.add_expense') }}" data-placement="bottom"
                    class="tw-shadow-[rgba(17,_17,_26,_0.1)_0px_0px_16px] tw-bg-white hover:tw-bg-white/60 tw-cursor-pointer tw-border-2 tw-w-auto tw-h-auto tw-py-1 tw-px-4 tw-rounded-md btn-modal pull-right"
                    id="add_expense">
                    <strong><i class="fa fas fa-minus-circle"></i> @lang('expense.add_expense')</strong>
                </button>
            @endcan
            
            
            
            <!-- زر المنتجات المميزة السحري في الهيدر -->
@if (!empty($pos_settings['hide_product_suggestion']) && !empty($featured_products) && count($featured_products) > 0)
    <button type="button" title="عرض المنتجات المميزة" id="toggle_featured_dynamic"
        class="tw-shadow-[rgba(17,_17,_26,_0.1)_0px_0px_16px] tw-bg-white hover:tw-bg-orange-50 tw-cursor-pointer tw-border-2 tw-border-orange-400 tw-flex tw-items-center tw-justify-center tw-rounded-md md:tw-w-10 tw-w-auto tw-h-8 tw-text-orange-600">
        <strong class="!tw-m-3">
            <i class="fa fa-star fa-lg !tw-text-sm"></i>
            <span class="tw-inline md:tw-hidden">المميزة</span>
        </strong>
    </button>
@endif

        </div>
    </div>
</div>

<div class="modal fade" id="service_staff_modal" tabindex="-1" role="dialog"
    aria-labelledby="gridSystemModalLabel">
</div>


 <!--  modal for button return by barcode --> 
<div class="modal fade" id="returnSearchModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog" role="document" style="width: 75%; max-width: 950px; margin-top: 60px;">
        <div class="modal-content" style="border-radius: 8px; border: 1px solid #3498db; box-shadow: 0 4px 12px rgba(0,0,0,0.2);">
            <div class="modal-header" style="background-color: #f7f9fb; border-bottom: 1px solid #dce4ec;">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close" style="font-size: 24px;">&times;</button>
                <h4 class="modal-title" style="font-weight: bold;"><i class="fa fa-undo"></i> بحث عن فاتورة للإرجاع بالباركود</h4>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-10">
                        <div class="form-group">
                            <label style="font-weight: 600;">امسح باركود المنتج أو ادخل الـ SKU:</label>
                            <input type="text" id="sku_input_return" class="form-control" 
                                   style="height: 45px; font-size: 16px; border: 2px solid #3498db;" 
                                   placeholder="مثال: 1686..." autocomplete="off">
                        </div>
                    </div>
                    <div class="col-md-2">
                        <label>&nbsp;</label>
                        <button class="btn btn-block" type="button" id="btn_search_return" style="height: 45px;background:#0d9488;color:#fff;font-weight:700;border:none">
                            <i class="fa fa-search"></i> بحث
                        </button>
                    </div>
                </div>
                <hr>
                <div id="invoices_results_area_return" style="min-height: 100px; max-height: 400px; overflow-y: auto;">
                    <p class="text-center text-muted">انتظار إدخال الكود للبحث عن الفواتير المرتبطة...</p>
                </div>
            </div>
        </div>
    </div>
</div>
<!--  modal for button return by barcode --> 
   

 <!--  modal for button search of quantity --> 
<div class="modal fade" id="StockSearchModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document" style="margin-top: 60px;">
        <div class="modal-content" style="border-radius: 8px; border: 1px solid #27ae60; box-shadow: 0 4px 12px rgba(0,0,0,0.2);">
            <div class="modal-header" style="background-color: #f8fff9; border-bottom: 1px solid #d4edda;">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close" style="font-size: 24px;">&times;</button>
                <h4 class="modal-title" style="font-weight: bold; color: #2c3e50;">
                    <i class="fa fa-boxes"></i> استعلام مخزون المنتج في الفروع
                </h4>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-10">
                        <div class="form-group">
                            <label style="font-weight: 600;">امسح باركود المنتج أو ادخل الـ SKU للاستعلام:</label>
                            <input type="text" id="sku_input_stock" class="form-control" 
                                   style="height: 45px; font-size: 16px; border: 2px solid #27ae60;" 
                                   placeholder="ادخل الباركود هنا..." autocomplete="off">
                        </div>
                    </div>
                      {{-- ✅ الإضافة الجديدة --}}
    <div class="col-md-4">
        <div class="form-group">
            <label style="font-weight: 400;">الفرع:</label>
            <select id="location_filter_stock" class="form-control" style="height: 35px; border: 2px solid #27ae60;">
    <option value="">-- كل الفروع --</option>
    @foreach($business_locations as $id => $name)
        <option value="{{ $id }}">{{ $name }}</option>
    @endforeach
</select>
        </div>
    </div>
                    <div class="col-md-2">
                        <label>&nbsp;</label>
                        <button class="btn btn-success btn-block" type="button" id="btn_execute_stock_search" style="height: 45px;">
                            <i class="fa fa-search"></i> فحص
                        </button>
                    </div>
                </div>
                <hr>
                <div id="stock_qty_results_area" style="min-height: 150px; max-height: 450px; overflow-y: auto;">
                    <p class="text-center text-muted" style="margin-top: 40px;">
                        <i class="fa fa-barcode fa-3x" style="display: block; margin-bottom: 10px; opacity: 0.3;"></i>
                        يرجى مسح الباركود لعرض تفاصيل المخزون...
                    </p>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">إغلاق</button>
            </div>
        </div>
    </div>
</div>
<!--  modal for button search of quantity -->
 
 <!-- Customer Ledger Modal -->
<div class="modal fade" id="customer_ledger_modal" tabindex="-1" role="dialog" style="z-index: 99999 !important;">
    <div class="modal-dialog" role="document" style="width: 95%; max-width: 1400px; margin: 20px auto;">
        <div class="modal-content" style="position: relative; z-index: 99999;">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
                <h4 class="modal-title">
                    <i class="fa fa-book"></i> كشف حساب العميل
                </h4>
            </div>
            <div class="modal-body" style="padding: 15px; max-height: 80vh; overflow-y: auto;">
                <div class="row" style="margin-bottom:15px;">
                    <div class="col-md-4">
                        <label>نطاق التاريخ:</label>
                        <input type="text" id="ledger_modal_date_range" 
                               class="form-control" 
                               placeholder="نطاق التاريخ">
                    </div>
                    <div class="col-md-5">
                        <label><input type="radio" name="ledger_modal_format" value=""> Format 1</label> &nbsp;
<label><input type="radio" name="ledger_modal_format" value="format_2"> Format 2</label> &nbsp;
<label><input type="radio" name="ledger_modal_format" value="format_3" checked> Format 3</label> &nbsp;
<label><input type="radio" name="ledger_modal_format" value="format_4"> التنسيق 4</label>
                    </div>
                    
                </div>
                <div id="ledger_content_area">
                    <div class="text-center">
                        <i class="fa fa-spinner fa-spin fa-2x"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<!-- Customer Ledger Modal -->
 
