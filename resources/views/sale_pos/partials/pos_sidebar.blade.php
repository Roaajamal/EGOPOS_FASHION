<!-- تنسيق CSS -->
<style>
    #featured_products_box {
        max-height: 300px; 
        overflow-y: auto;
        background: #f9f9f9;
        border: 1px solid #ddd;
        border-radius: 8px;
        margin-bottom: 15px;
        padding: 15px;
        display: none; /* مخفي ويظهر عند الضغط على الكبسة */
    }

    #show_featured_products {
        width: 100%;
        border-radius: 10px;
        font-weight: bold;
        margin-bottom: 10px;
        background-color: blue; /* يمكنك تغيير اللون هنا أو تركه كما في التنسيق المتدرج بالأسفل */
    }

</style>

@php
    // جلب القيمة ديناميكياً من الإعدادات في أول الملف لتفادي خطأ التعريف
    $columns_per_row = !empty($pos_settings['products_per_row']) ? intval($pos_settings['products_per_row']) : 4;
@endphp

<!-- بداية تعديل منطقة المنتجات المميزة -->
@if(!empty($featured_products) && count($featured_products) > 0)
<div class="row" id="feature_product_wrapper">
    <div class="col-md-12">
        <div id="feature_product_div_container" style="margin-bottom: 10px;">
            <!-- 🆕 الزر الذهبي القديم مخفي — صار تبويب "المميزة" مع الأصناف/العلامات بالأسفل -->
            <button type="button"
                class="tw-bg-gradient-to-r tw-from-amber-500 tw-to-orange-600 tw-text-white tw-font-bold tw-rounded-xl tw-h-12 tw-w-full tw-flex tw-items-center tw-justify-center tw-gap-2"
                id="show_featured_products" style="display:none">
                <i class="fa fa-star"></i> @lang('lang_v1.featured_products')
            </button>

            <!-- الحاوية مع الـ Grid الديناميكي للمنتجات المميزة (مخفية افتراضياً وتظهر بزر "المميزة") -->
            <div id="featured_products_box" data-cols="{{ $columns_per_row }}" style="margin-top: 10px; display: none; grid-template-columns: repeat({{ $columns_per_row }}, minmax(0, 1fr)); gap: 8px; width: 100%;">
                @include('sale_pos.partials.featured_products')
            </div>
        </div>
    </div>
</div>
@endif
<!-- نهاية تعديل منطقة المنتجات المميزة -->

@if(empty($pos_settings['hide_product_suggestion']))
{{-- 🆕 حاوية مرنة: شريط أيقونات (الصنف/العلامات/المميزة) عمودي بجانب شبكة المنتجات --}}
<div class="ego-prod-wrap">
<div class="row tw-mb-1">
    @if (!empty($categories))
        <div class="col-md-4 col-sm-4 !tw-px-2" id="product_category_div">
            <div class="tw-dw-drawer tw-dw-drawer-end">
                <input id="my-drawer-4" type="checkbox" class="tw-dw-drawer-toggle">
                <div class="tw-dw-drawer-content">
                    <!-- Page content here -->
                    <label for="my-drawer-4"
                        class="tw-bg-gradient-to-r tw-from-indigo-600 tw-to-blue-500 hover:tw-from-indigo-600 hover:tw-to-blue-600 focus:tw-outline-none focus:tw-ring-2 focus:tw-ring-blue-500 focus:tw-ring-offset-2 active:tw-from-indigo-700 active:tw-to-blue-700 lg:tw-w-[98%] tw-w-full tw-flex tw-items-center tw-justify-center tw-gap-1 tw-text-base md:tw-text-lg tw-text-white tw-font-semibold tw-rounded-xl tw-h-12 tw-cursor-pointer">
                        <svg xmlns="http://www.w3.org/2000/svg"
                            class="tw-w-5 icon icon-tabler icon-tabler-category-plus" width="44" height="44"
                            viewBox="0 0 24 24" stroke-width="1.5" stroke="#ffffff" fill="none"
                            stroke-linecap="round" stroke-linejoin="round">
                            <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                            <path d="M4 4h6v6h-6zm10 0h6v6h-6zm-10 10h6v6h-6zm10 3h6m-3 -3v6" />
                        </svg>
                        @lang('category.category')
                    </label>
                </div>
                <div class="tw-dw-drawer-side" style="z-index: 4000">
                    <label for="my-drawer-4" aria-label="close sidebar"
                        class="tw-dw-drawer-overlay overlay-category"></label>
                    <div class="tw-dw-menu tw-w-2/4 tw-min-h-full tw-bg-white tw-p-6">
                        <div class="tw-flex tw-items-center tw-mb-16">
                            <button type="button"
                                class="tw-dw-btn tw-dw-btn-accent category-back tw-bg-transparent tw-border-2"
                                style="display: none">
                                <svg xmlns="http://www.w3.org/2000/svg"
                                    class="tw-w-5 icon icon-tabler icon-tabler-chevron-left" width="44"
                                    height="44" viewBox="0 0 24 24" stroke-width="1.5" stroke="#2c3e50"
                                    fill="none" stroke-linecap="round" stroke-linejoin="round">
                                    <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                    <path d="M15 6l-6 6l6 6" />
                                </svg>
                            </button>

                            <h3 class="tw-text-center tw-flex-grow mx-auto category_heading tw-bg-gradient-to-r tw-from-indigo-600 tw-to-blue-500 tw-inline-block tw-text-transparent tw-bg-clip-text tw-font-bold tw-text-base md:tw-text-2xl"
                                style="margin-bottom: 0px; margin-top:5px;">@lang('category.category')</h3>

                            <button type="button" class="tw-dw-btn tw-dw-btn-error close-side-bar-category">
                                <i class="fa fa-times-circle" aria-hidden="true"></i>
                            </button>

                        </div>
                        {{-- 🆕 بحث عن صنف --}}
                        <div class="ego-drawer-search-wrap">
                            <i class="fa fa-search"></i>
                            <input type="text" id="ego_cat_search" class="ego-drawer-search" placeholder="ابحث عن صنف...">
                        </div>
                        <div class="row tw-mr-5">
                            <div class="col-md-3 col-xs-12 tw-mb-7 tw-w-auto  tw-h-auto tw-cursor-pointer  main-category-div main-category no-print"
                                data-value="all" data-parent="0">
                                <div class="tw-dw-card tw-w-25 tw-bg-base-100 tw-shadow-sm tw-h-auto tw-shadow-[rgba(17,_17,_26,_0.1)_0px_0px_16px] tw-bg-white hover:tw-bg-white/60 tw-cursor-pointer !tw-text-xs md:!tw-text-sm tw-font-semibold tw-text-center tw-border-2">
                                    <div class="tw-dw-card-body">
                                        <h4 class="tw-flex tw-items-center tw-justify-center" style="margin-bottom: 0px; margin-top:0px; font-size: inherit; font-weight: inherit;">@lang('lang_v1.all_category')</h4>
                                    </div>
                                </div>
                            </div>
                            @foreach ($categories as $category)
                                    <div class="col-md-3 col-xs-12 tw-mb-7 tw-w-auto  tw-h-28  tw-cursor-pointer main-category-div  no-print"
                                        data-value="{{ $category['id'] }}" data-name="{{ $category['name'] }}" data-parent="1">
                                        <div
                                            class="tw-dw-card tw-w-25 tw-bg-base-100 tw-shadow-sm tw-h-auto tw-shadow-[rgba(17,_17,_26,_0.1)_0px_0px_16px] tw-bg-white hover:tw-bg-white/60 tw-cursor-pointer tw-text-xs md:tw-text-sm tw-font-semibold !tw-text-center tw-border-2">
                                            <div class="tw-dw-card-body" style="margin-bottom: -20px">
                                                <h4 class="tw-flex tw-items-center tw-justify-center"
                                                    style="margin-bottom: 0px; margin-top:0px; font-size: inherit; font-weight: inherit;">
                                                    {{ $category['name'] }}</h4>
                                            </div>
                                            <div class="tw-dw-card-actions tw-justify-center">
                                                <button type="button" class="tw-dw-btn tw-dw-btn-accent tw-dw-btn-outline tw-dw-btn-sm main-category tw-mb-2" data-value="{{ $category['id'] }}" data-parent="0">{{ __('lang_v1.all') }}</button>
                                                @if (!empty($category['sub_categories']))
                                                <button type="button" class="tw-dw-btn tw-dw-btn-primary tw-dw-btn-outline tw-dw-btn-sm main-category tw-mb-2" data-parent="1" data-value="{{ $category['id'] }}" data-name="{{ $category['name'] }}">@lang('pagination.next')</button>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                            @endforeach
                            @foreach ($categories as $category)
                                @if (!empty($category['sub_categories']))
                                    <div class="{{ $category['id'] }} all-sub-category" style="display: none">
                                        @foreach ($category['sub_categories'] as $sc)
                                            @if ($sc['parent_id'] != 0)
                                                <div class="col-md-3 col-xs-12 tw-mb-5 tw-w-auto tw-h-auto tw-cursor-pointer product_category no-print"
                                                    data-value="{{ $sc['id'] }}">
                                                    <div
                                                        class="tw-dw-card tw-w-25 tw-bg-base-100 tw-shadow-sm tw-h-auto tw-shadow-[rgba(17,_17,_26,_0.1)_0px_0px_16px] tw-bg-white hover:tw-bg-white/60 tw-cursor-pointer tw-text-xs md:tw-text-sm tw-font-semibold tw-text-center tw-border-2">
                                                        <div class="tw-dw-card-body">
                                                            <h4 class="tw-flex tw-items-center tw-justify-center"
                                                                style="margin-bottom: 0px; margin-top:0px; font-size: inherit; font-weight: inherit;">
                                                                {{ $sc['name'] }}</h4>
                                                        </div>
                                                    </div>
                                                </div>
                                            @endif
                                        @endforeach
                                    </div>
                                @endif
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif

    @if (!empty($brands))
        <div class="col-md-4 col-sm-4 !tw-px-2" id="product_brand_div">
            <div class="tw-dw-drawer tw-dw-drawer-end">
                <input id="my-drawer-brand" type="checkbox" class="tw-dw-drawer-toggle">
                <div class="tw-dw-drawer-content">
                    <!-- Page content here -->
                    <label for="my-drawer-brand"
                        class="tw-bg-gradient-to-r tw-from-indigo-600 tw-to-blue-500 hover:tw-from-indigo-600 hover:tw-to-blue-600 focus:tw-outline-none focus:tw-ring-2 focus:tw-ring-blue-500 focus:tw-ring-offset-2 active:tw-from-indigo-700 active:tw-to-blue-700 lg:tw-w-[98%] tw-w-full tw-flex tw-items-center tw-justify-center tw-gap-1 tw-text-base md:tw-text-lg tw-text-white tw-font-semibold tw-rounded-xl tw-h-12 tw-cursor-pointer">
                        <svg xmlns="http://www.w3.org/2000/svg" class="tw-w-5 icon icon-tabler icon-tabler-brand-beats"
                            width="44" height="44" viewBox="0 0 24 24" stroke-width="1.5" stroke="#ffffff"
                            fill="none" stroke-linecap="round" stroke-linejoin="round">
                            <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                            <path d="M12 12m-9 0a9 9 0 1 0 18 0a9 9 0 1 0 -18 0" />
                            <path d="M12.5 12.5m-3.5 0a3.5 3.5 0 1 0 7 0a3.5 3.5 0 1 0 -7 0" />
                            <path d="M9 12v-8" />
                        </svg>
                        @lang('brand.brands')
                    </label>

                </div>
                <div class="tw-dw-drawer-side" style="z-index: 4000">
                    <label for="my-drawer-brand" aria-label="close sidebar"
                        class="tw-dw-drawer-overlay overlay-brand"></label>
                    <div class="tw-dw-menu tw-w-2/4 tw-min-h-full tw-bg-white tw-p-6">

                        <div class="tw-flex tw-items-center tw-mb-16">
                            <h3 class="tw-text-center tw-mx-auto tw-bg-gradient-to-r tw-from-indigo-600 tw-to-blue-500 tw-text-transparent tw-bg-clip-text tw-font-bold tw-text-base md:tw-text-2xl tw-mb-16"
                                style="margin-bottom: 0px; margin-top:5px;">@lang('brand.brands')</h3>
                            <button type="button" class="tw-dw-btn tw-dw-btn-error close-side-bar-brand">
                                <i class="fa fa-times-circle" aria-hidden="true"></i>
                            </button>
                        </div>

                        {{-- 🆕 بحث عن علامة تجارية --}}
                        <div class="ego-drawer-search-wrap">
                            <i class="fa fa-search"></i>
                            <input type="text" id="ego_brand_search" class="ego-drawer-search" placeholder="ابحث عن علامة تجارية...">
                        </div>

                        <div class="row tw-mr-5">
                            @foreach ($brands as $key => $brand)
                                <div class="col-md-4 col-xs-12 tw-mb-5 tw-w-auto tw-h-auto tw-cursor-pointer product_brand no-print"
                                    data-value="{{ $key }}">
                                    <div
                                        class="tw-dw-card tw-w-25 tw-bg-base-100 tw-shadow-sm tw-h-auto tw-shadow-[rgba(17,_17,_26,_0.1)_0px_0px_16px] tw-bg-white hover:tw-bg-white/60 tw-cursor-pointer tw-text-xs md:tw-text-sm tw-font-semibold tw-text-center tw-border-2">
                                        <div class="tw-dw-card-body">
                                            <h4 class="tw-flex tw-items-center tw-justify-center"
                                                style="margin-bottom: 0px; margin-top:0px; font-size: inherit; font-weight: inherit;">
                                                {{ $brand }}
                                            </h4>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- 🆕 تبويب "المميزة" بجانب الأصناف/العلامات يعرض المنتجات المميزة بنفس المكان --}}
    @if(!empty($featured_products) && count($featured_products) > 0)
        <div class="col-md-4 col-sm-4 !tw-px-2" id="product_featured_div">
            <button type="button" id="ego_featured_tab"
                class="tw-w-full tw-flex tw-items-center tw-justify-center tw-gap-1 tw-text-base md:tw-text-lg tw-font-bold tw-rounded-xl tw-h-12 tw-cursor-pointer"
                style="background:#fff;color:#1e293b;border:2px solid #fde68a;box-shadow:0 4px 12px rgba(245,158,11,.18)">
                <i class="fa fa-star" style="color:#f59e0b"></i> المميزة
            </button>
        </div>
    @endif

    <!-- used in repair : filter for service/product -->
    <div class="col-md-6 hide" id="product_service_div">
        {!! Form::select(
            'is_enabled_stock',
            ['' => __('messages.all'), 'product' => __('sale.product'), 'service' => __('lang_v1.service')],
            null,
            ['id' => 'is_enabled_stock', 'class' => 'select2', 'name' => null, 'style' => 'width:100% !important'],
        ) !!}
    </div>

</div>

<div class="row">
    <input type="hidden" id="suggestion_page" value="1">
    <div class="col-md-12">
        <!-- الحاوية مع الـ Grid الديناميكي للمنتجات العادية -->
        <div class="eq-height-row" id="product_list_body" 
             style="display: grid !important; grid-template-columns: repeat({{ $columns_per_row }}, minmax(0, 1fr)) !important; gap: 8px !important; width: 100%;">
        </div>
    </div>
    <div class="col-md-12 text-center" id="suggestion_page_loader" style="display: none;">
        <i class="fa fa-spinner fa-spin fa-2x"></i>
    </div>
</div>
</div>{{-- 🆕 نهاية الحاوية المرنة --}}

<style>
    #product_list_body > div, #featured_products_box > div {
        width: 100% !important;
        float: none !important;
        clear: none !important;
        padding: 0 !important;
        margin: 0 !important;
    }
</style>
@endif

<script>
    // 🆕 ننتظر تحميل jQuery قبل الربط (هذا السكربت يُحمَّل ضمن المحتوى قبل jQuery، فكان يسبّب "$ is not defined")
    (function egoSidebarWait(){
        if (typeof window.jQuery === 'undefined') { return setTimeout(egoSidebarWait, 60); }
        var $ = window.jQuery;
        $(document).on('click', '#show_featured_products', function() {
            $('#featured_products_box').slideToggle();
        });
    })();
</script>
<style>
    /* 🆕 صندوق البحث داخل نوافذ الأصناف/العلامات التجارية */
    .ego-drawer-search-wrap{position:relative;margin-bottom:20px}
    .ego-drawer-search-wrap i{position:absolute;top:50%;transform:translateY(-50%);right:14px;color:#94a3b8}
    .ego-drawer-search{width:100%;border:2px solid #e2e8f0;border-radius:12px;padding:12px 42px 12px 14px;font-size:16px;font-weight:600;outline:none;background:#fff}
    .ego-drawer-search:focus{border-color:#2563eb;box-shadow:0 0 0 3px rgba(37,99,235,.12)}
</style>