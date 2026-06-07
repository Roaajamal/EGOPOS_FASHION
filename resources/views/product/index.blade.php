@php
    $custom_labels = json_decode(session('business.custom_labels'), true);
    $active_custom_fields = [];
    for ($i = 1; $i <= 10; $i++) {
        $label = $custom_labels['product']['custom_field_' . $i] ?? '';
        if (!empty($label)) {
            $active_custom_fields['product_custom_field' . $i] = $label;
        }
    }
@endphp

@extends('layouts.app')
@section('title', __('sale.products'))

@section('content')

    <!-- Content Header (Page header) -->
    <section class="content-header">
        <h1 class="tw-text-xl md:tw-text-3xl tw-font-bold tw-text-black">@lang('sale.products')
            <small class="tw-text-sm md:tw-text-base tw-text-gray-700 tw-font-semibold">@lang('lang_v1.manage_products')</small>
        </h1>
        <!-- <ol class="breadcrumb">
                    <li><a href="#"><i class="fa fa-dashboard"></i> Level</a></li>
                    <li class="active">Here</li>
                </ol> -->
    </section>

    <!-- Main content -->
    <section class="content">
        <div class="row">
            <div class="col-md-12">
                @component('components.filters', ['title' => __('report.filters')])
                    <div class="col-md-3">
                        <div class="form-group">
                            {!! Form::label('type', __('product.product_type') . ':') !!}
                            {!! Form::select(
                                'type',
                                ['single' => __('lang_v1.single'), 'variable' => __('lang_v1.variable'), 'combo' => __('lang_v1.combo')],
                                null,
                                [
                                    'class' => 'form-control select2',
                                    'style' => 'width:100%',
                                    'id' => 'product_list_filter_type',
                                    'placeholder' => __('lang_v1.all'),
                                ],
                            ) !!}
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            {!! Form::label('category_id', __('product.category') . ':') !!}
                            {!! Form::select('category_id', $categories, null, [
                                'class' => 'form-control select2',
                                'style' => 'width:100%',
                                'id' => 'product_list_filter_category_id',
                                'placeholder' => __('lang_v1.all'),
                            ]) !!}
                        </div>
                    </div>

      <div class="col-md-2 daily_stock_filter" style="display:none;">
    <div class="form-group">
        {!! Form::label('snapshot_date', __('lang_v1.stock_date') ) !!}
        {!! Form::text('snapshot_date', now()->format('Y-m-d'), [
            'class'    => 'form-control',
            'id'       => 'snapshot_date',
            'readonly' => true,
        ]) !!}
    </div>
</div>

<div class="col-md-3 daily_stock_filter" style="display:none;">
    <div class="form-group">
        {!! Form::label('daily_location_ids', __('lang_v1.locations')) !!}
        {!! Form::select('daily_location_ids[]', $stock_locations, null, [
            'class'    => 'form-control select2',
            'style'    => 'width:100%',
            'id'       => 'daily_location_ids',
            'multiple' => 'multiple',
        ]) !!}
    </div>
</div> 

                  <div class="col-md-3 daily_stock_filter" style="display:none;">
    <div class="form-group">
        {!! Form::label('daily_stock_filter', __('lang_v1.stock_quantity') . ':') !!}
        {!! Form::select('daily_stock_filter', [
            ''    => __('messages.all'),
            'gt'  => __('lang_v1.greater_than_zero'),
            'lt'  => __('lang_v1.less_than_zero'),
            'gte' => __('lang_v1.greater_than_or_equal_zero'),
            'lte' => __('lang_v1.less_than_or_equal_zero'),
            'eq'  => __('lang_v1.equal_zero'),
        ], null, [
            'class' => 'form-control select2',
            'style' => 'width:100%',
            'id'    => 'daily_stock_filter_qty'
        ]) !!}
    </div>
</div>
                    <div class="col-md-3">
                        <div class="form-group">
                            {!! Form::label('unit_id', __('product.unit') . ':') !!}
                            {!! Form::select('unit_id', $units, null, [
                                'class' => 'form-control select2',
                                'style' => 'width:100%',
                                'id' => 'product_list_filter_unit_id',
                                'placeholder' => __('lang_v1.all'),
                            ]) !!}
                        </div>
                    </div>
                    @if (!empty($custom_labels['product']['custom_field_1'] ?? ''))
<div class="col-md-3" id="cf_filter_1">
    <div class="form-group">
        {!! Form::label('custom_field1', ($custom_labels['product']['custom_field_1']) . ':') !!}
        {!! Form::select('custom_field1', $custom_field1_values, null, [
            'class'       => 'form-control select2',
            'style'       => 'width:100%',
            'id'          => 'filter_cf1',
            'placeholder' => __('lang_v1.all'),
        ]) !!}
    </div>
</div>
@endif

@if (!empty($custom_labels['product']['custom_field_2'] ?? ''))
<div class="col-md-3" id="cf_filter_2">
    <div class="form-group">
        {!! Form::label('custom_field2', ($custom_labels['product']['custom_field_2']) . ':') !!}
        {!! Form::select('custom_field2', $custom_field2_values, null, [
            'class'       => 'form-control select2',
            'style'       => 'width:100%',
            'id'          => 'filter_cf2',
            'placeholder' => __('lang_v1.all'),
        ]) !!}
    </div>
</div>
@endif

@if (!empty($custom_labels['product']['custom_field_3'] ?? ''))
<div class="col-md-3" id="cf_filter_3">
    <div class="form-group">
        {!! Form::label('custom_field3', ($custom_labels['product']['custom_field_3']) . ':') !!}
        {!! Form::select('custom_field3', $custom_field3_values, null, [
            'class'       => 'form-control select2',
            'style'       => 'width:100%',
            'id'          => 'filter_cf3',
            'placeholder' => __('lang_v1.all'),
        ]) !!}
    </div>
</div>
@endif
                    <div class="col-md-3">
                        <div class="form-group">
                            {!! Form::label('tax_id', __('product.tax') . ':') !!}
                            {!! Form::select('tax_id', $taxes, null, [
                                'class' => 'form-control select2',
                                'style' => 'width:100%',
                                'id' => 'product_list_filter_tax_id',
                                'placeholder' => __('lang_v1.all'),
                            ]) !!}
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            {!! Form::label('brand_id', __('product.brand') . ':') !!}
                            {!! Form::select('brand_id', $brands, null, [
                                'class' => 'form-control select2',
                                'style' => 'width:100%',
                                'id' => 'product_list_filter_brand_id',
                                'placeholder' => __('lang_v1.all'),
                            ]) !!}
                        </div>
                    </div>
                    <div class="col-md-3" id="location_filter">
                        <div class="form-group">
                            {!! Form::label('location_id', __('purchase.business_location') . ':') !!}
                            {!! Form::select('location_id', $business_locations, null, [
                                'class' => 'form-control select2',
                                'style' => 'width:100%',
                                'placeholder' => __('lang_v1.all'),
                            ]) !!}
                        </div>
                    </div>
                    <div class="col-md-3">
                        
                        <div class="form-group">
                            {!! Form::label('active_state', __('lang_v1.status') . ':') !!} 
                            {!! Form::select(
                                'active_state',
                                ['active' => __('business.is_active'), 'inactive' => __('lang_v1.inactive')],
                                null,
                                [
                                    'class' => 'form-control select2',
                                    'style' => 'width:100%',
                                    'id' => 'active_state',
                                    'placeholder' => __('lang_v1.all'),
                                ],
                            ) !!}
                        </div>
                    </div>

                    <!-- include module filter -->
                    @if (!empty($pos_module_data))
                        @foreach ($pos_module_data as $key => $value)
                            @if (!empty($value['view_path']))
                                @includeIf($value['view_path'], ['view_data' => $value['view_data']])
                            @endif
                        @endforeach
                    @endif

                    <div class="col-md-3">
                        <div class="form-group">
                            <br>
                            <label>
                                {!! Form::checkbox('not_for_selling', 1, false, ['class' => 'input-icheck', 'id' => 'not_for_selling']) !!} <strong>@lang('lang_v1.not_for_selling')</strong>
                            </label>
                        </div>
                    </div>
                    @if ($is_woocommerce)
                        <div class="col-md-3">
                            <div class="form-group">
                                <br>
                                <label>
                                    {!! Form::checkbox('woocommerce_enabled', 1, false, ['class' => 'input-icheck', 'id' => 'woocommerce_enabled']) !!} {{ __('lang_v1.woocommerce_enabled') }}
                                </label>
                            </div>
                        </div>
                    @endif
                @endcomponent
            </div>
        </div>
        @can('product.view')
            <div class="row">
                <div class="col-md-12">
                    <!-- Custom Tabs -->
                    <div class="nav-tabs-custom">
                        <ul class="nav nav-tabs">
                            <li class="active">
                                <a href="#product_list_tab" data-toggle="tab" aria-expanded="true"><i class="fa fa-cubes"
                                        aria-hidden="true"></i> @lang('lang_v1.all_products')</a>
                            </li>
                            @can('stock_report.view')
                                <li>
                                    <a href="#product_stock_report" class="product_stock_report" data-toggle="tab"
                                        aria-expanded="true"><i class="fa fa-hourglass-half" aria-hidden="true"></i>
                                     @lang('report.stock_report')</a>
                                </li>
                            @endcan
                            @can('daily_stock_tab.view')
                                <li>
                                     <a href="#product_daily_stock_report" {{-- ← هون التعديل --}}
                                        data-toggle="tab" aria-expanded="true">
                                              <i class="fa fa-calendar" aria-hidden="true"></i>
                                           @lang('report.daily_stock_report')
                                     </a>
                                </li>
                            @endcan
                            @can('current_stock_tab.view')
                                <li>
                                     <a href="#product_current_stock_report" data-toggle="tab" aria-expanded="true">
                                     <i class="fa fa-cubes" aria-hidden="true"></i>
                                        @lang('report.current_stock_report')
                                      </a>
                                </li>
                            @endcan  
                        </ul>

                        <div class="tab-content">
                            <div class="tab-pane active " id="product_list_tab">
                                @if ($is_admin)

                                    <a class="tw-dw-btn tw-dw-btn-primary tw-text-white tw-font-bold tw-rounded-full pull-right"
                                        href="{{ action([\App\Http\Controllers\ProductController::class, 'downloadExcel']) }}">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                                            viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                            stroke-linecap="round" stroke-linejoin="round"
                                            class="icon icon-tabler icons-tabler-outline icon-tabler-download">
                                            <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                            <path d="M4 17v2a2 2 0 0 0 2 2h12a2 2 0 0 0 2 -2v-2" />
                                            <path d="M7 11l5 5l5 -5" />
                                            <path d="M12 4l0 12" />
                                        </svg> @lang('lang_v1.download_excel')
                                    </a>
                                @endif
                                @can('product.create')

                                    <a class="tw-dw-btn tw-dw-btn-primary tw-text-white tw-font-bold tw-rounded-full pull-right"
                                        href="{{ action([\App\Http\Controllers\ProductController::class, 'create']) }}">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"
                                            fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                            stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-plus">
                                            <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                            <path d="M12 5l0 14" />
                                            <path d="M5 12l14 0" />
                                        </svg> @lang('messages.add')
                                    </a>
                                    <br><br>
                                @endcan
                                @include('product.partials.product_list')
                            </div>
                            @can('stock_report.view')
                                <div class="tab-pane" id="product_stock_report">
                                    @include('report.partials.stock_report_table')
                                </div>
                            @endcan
                          
                            @can('daily_stock_tab.view')
                              <div class="tab-pane" id="product_daily_stock_report">
                                 @include('product.partials.daily_stock_report_table', [
                                'locations' => $stock_locations  // ← مش $business_locations
                                  ])
                                   </div>
                                  @endcan

                                    @can('current_stock_tab.view')
                                          <div class="tab-pane" id="product_current_stock_report">
                                          @include('product.partials.current_stock_report_table')
                                           </div>
                                    @endcan 
                           
                        </div>
                    </div>
                </div>
            </div>
        @endcan
        <input type="hidden" id="is_rack_enabled" value="{{ $rack_enabled }}">

        <div class="modal fade product_modal" tabindex="-1" role="dialog" aria-labelledby="gridSystemModalLabel">
        </div>

        <div class="modal fade" id="view_product_modal" tabindex="-1" role="dialog"
            aria-labelledby="gridSystemModalLabel">
        </div>

        <div class="modal fade" id="opening_stock_modal" tabindex="-1" role="dialog"
            aria-labelledby="gridSystemModalLabel">
        </div>

        @if ($is_woocommerce)
            @include('product.partials.toggle_woocommerce_sync_modal')
        @endif
        @include('product.partials.edit_product_location_modal')

    </section>
    <!-- /.content -->

@endsection

@section('javascript')


@php
    $col_labels = [
        'product'        => __('sale.product'),
        'category'       => __('product.category'),
        'sub_category'   => __('product.sub_category'),
        'brand'          => __('product.brand'),
        'unit'           => __('product.unit'),
        'tax'            => __('product.tax'),
        'type'           => __('product.product_type'),
        'selling_price'  => __('lang_v1.selling_price'),
        'purchase_price' => __('lang_v1.unit_perchase_price'),
    ];

    $custom_labels = json_decode(session('business.custom_labels'), true);
    $active_custom_fields = [];
    for ($i = 1; $i <= 10; $i++) {
        $label = $custom_labels['product']['custom_field_' . $i] ?? '';
        if (!empty($label)) {
            $active_custom_fields['product_custom_field' . $i] = $label;
        }
    }
@endphp
 
    <script src="{{ asset('js/product.js?v=' . $asset_v) }}"></script>
    <script src="{{ asset('js/opening_stock.js?v=' . $asset_v) }}"></script>
    <script type="text/javascript">

     var daily_stock_table = null;

function loadDailyStockReport() {
    var snapshot_date = $('#snapshot_date').val();
    if (!snapshot_date) return;

    var selected_locations = $('#daily_location_ids').val() || [];

    if (!selected_locations || selected_locations.length === 0) {
        $('#daily_stock_report_container').html(
            '<p class="text-center text-muted">اختر فرع واحد على الأقل</p>'
        );
        return;
    }

    // destroy نهائي قبل أي شي
    if (daily_stock_table !== null) {
        daily_stock_table.destroy();
        daily_stock_table = null;
    }
    $('#daily_stock_thead').empty();
    $('#daily_stock_tfoot').empty();
    $('#daily_stock_table tbody').empty();

    // جيب أسماء الفروع المختارة
    var location_names = [];
    $('#daily_location_ids option:selected').each(function() {
        location_names.push($(this).text());
    });

    // بناء الـ thead
  @php
    $custom_labels = json_decode(session('business.custom_labels'), true);
    $product_custom_fields = [];
    for ($i = 1; $i <= 10; $i++) {
        $label = $custom_labels['product']['custom_field_' . $i] ?? '';
        if (!empty($label)) {
            $product_custom_fields[$i] = $label;
        }
    }
@endphp

var thead = '<th>SKU</th>' +
    '<th>{{ $col_labels["product"] }}</th>' +
    '<th>{{ $col_labels["category"] }}</th>' +
    '<th>{{ $col_labels["sub_category"] }}</th>' +
    '<th>{{ $col_labels["brand"] }}</th>' +
    '<th>{{ $col_labels["unit"] }}</th>' +
    '<th>{{ $col_labels["tax"] }}</th>' +
    '<th>{{ $col_labels["type"] }}</th>' +
    '<th>{{ $col_labels["selling_price"] }}</th>' +
    '<th>{{ $col_labels["purchase_price"] }}</th>';

var columns = [
    { data: 0, title: 'SKU' },
    { data: 1, title: '{{ $col_labels["product"] }}' },
    { data: 2, title: '{{ $col_labels["category"] }}' },
    { data: 3, title: '{{ $col_labels["sub_category"] }}' },
    { data: 4, title: '{{ $col_labels["brand"] }}' },
    { data: 5, title: '{{ $col_labels["unit"] }}' },
    { data: 6, title: '{{ $col_labels["tax"] }}' },
    { data: 7, title: '{{ $col_labels["type"] }}' },
    { data: 8, title: '{{ $col_labels["selling_price"] }}',  searchable: false },
    { data: 9, title: '{{ $col_labels["purchase_price"] }}', searchable: false },
];

// أضف الـ custom fields المفعلة فقط
@foreach($product_custom_fields as $index => $label)
    thead += '<th>{{ $label }}</th>';
    columns.push({ data: {{ $loop->index + 10 }}, title: '{{ $label }}' });
@endforeach

var fixed_cols = {{ count($product_custom_fields) + 10 }};

// الفروع
location_names.forEach(function(name, i) {
    thead += '<th>' + name + '</th>';
    columns.push({ data: i + fixed_cols, title: name, searchable: false });
});

thead += '<th>الإجمالي</th>';
columns.push({
    data      : location_names.length + fixed_cols,
    title     : 'الإجمالي',
    searchable: false,
    render    : function(data) {
        return '<strong>' + (data ?? 0) + '</strong>';
    }
    });

    $('#daily_stock_thead').html(thead);

    var tfoot = '';
    columns.forEach(function(col, i) {
        tfoot += '<th class="footer_col_' + i + '"></th>';
    });
    $('#daily_stock_tfoot').html(tfoot);

    daily_stock_table = $('#daily_stock_table').DataTable({
        processing : true,
        serverSide : true,
        pageLength : 25,
        ajax: {
            url  : '{{ route("product.daily_stock_history") }}',
            data : function(d) {
                d.snapshot_date    = $('#snapshot_date').val();
                d.location_ids     = $('#daily_location_ids').val();
                d.stock_filter   = $('#daily_stock_filter_qty').val();
                d.category_id   = $('#product_list_filter_category_id').val();
                d.brand_id      = $('#product_list_filter_brand_id').val();
                d.unit_id       = $('#product_list_filter_unit_id').val();
                d.tax_id        = $('#product_list_filter_tax_id').val();
                d.type          = $('#product_list_filter_type').val();
                d.active_state  = $('#active_state').val();

                d.custom_field1 = $('#filter_cf1').val();
                d.custom_field2 = $('#filter_cf2').val();
                d.custom_field3 = $('#filter_cf3').val(); 
            }
        },
      footerCallback: function() {
    var api = this.api();
    var grand_total = 0;

    columns.forEach(function(col, i) {
        if (i < fixed_cols) return;
        var total = api.column(i, { page: 'all' })
            .data()
            .reduce(function(a, b) {
                return parseFloat(a || 0) + parseFloat(b || 0);
            }, 0);
        if (i === columns.length - 1) {
            $('.footer_col_' + i).html('<strong>' + grand_total + '</strong>');
        } else {
            grand_total += total;
            $('.footer_col_' + i).html('<strong>' + total + '</strong>');
        }
    });

    for (var i = 0; i < fixed_cols; i++) {
        if (i === 1) {
            $('.footer_col_1').html('<strong>الإجمالي</strong>');
        } else {
            $('.footer_col_' + i).html('');
        }
    }
},  
 fnDrawCallback: function() {
            __currency_convert_recursively($('#daily_stock_table'));
        }
    });
}

      ////////////  for current stock report

      var current_stock_table = null;

function loadCurrentStockReport() {
    var selected_locations = $('#daily_location_ids').val() || [];

    if (!selected_locations || selected_locations.length === 0) {
        $('#current_stock_report_container').html(
            '<p class="text-center text-muted">اختر فرع واحد على الأقل</p>'
        );
        return;
    }

    if (current_stock_table !== null) {
        current_stock_table.destroy();
        current_stock_table = null;
    }
    $('#current_stock_thead').empty();
    $('#current_stock_tfoot').empty();
    $('#current_stock_table tbody').empty();

    var location_names = [];
    $('#daily_location_ids option:selected').each(function() {
        location_names.push($(this).text());
    });

    var thead = '<th>SKU</th>' +
    '<th>{{ $col_labels["product"] }}</th>' +
    '<th>{{ $col_labels["category"] }}</th>' +
    '<th>{{ $col_labels["sub_category"] }}</th>' +
    '<th>{{ $col_labels["brand"] }}</th>' +
    '<th>{{ $col_labels["unit"] }}</th>' +
    '<th>{{ $col_labels["tax"] }}</th>' +
    '<th>{{ $col_labels["type"] }}</th>' +
    '<th>{{ $col_labels["selling_price"] }}</th>' +
    '<th>{{ $col_labels["purchase_price"] }}</th>';

    var columns = [
    { data: 0, title: 'SKU' },
    { data: 1, title: '{{ $col_labels["product"] }}' },
    { data: 2, title: '{{ $col_labels["category"] }}' },
    { data: 3, title: '{{ $col_labels["sub_category"] }}' },
    { data: 4, title: '{{ $col_labels["brand"] }}' },
    { data: 5, title: '{{ $col_labels["unit"] }}' },
    { data: 6, title: '{{ $col_labels["tax"] }}' },
    { data: 7, title: '{{ $col_labels["type"] }}' },
    { data: 8, title: '{{ $col_labels["selling_price"] }}',  searchable: false },
    { data: 9, title: '{{ $col_labels["purchase_price"] }}', searchable: false },
];

    @foreach($active_custom_fields as $field => $label)
        thead += '<th>{{ $label }}</th>';
        columns.push({ data: {{ $loop->index + 10 }}, title: '{{ $label }}' });
    @endforeach

    var current_fixed_cols = {{ count($active_custom_fields) + 10 }};

    location_names.forEach(function(name, i) {
        thead += '<th>' + name + '</th>';
        columns.push({ data: i + current_fixed_cols, title: name, searchable: false });
    });

    thead += '<th>الإجمالي</th>';
    columns.push({
        data      : location_names.length + current_fixed_cols,
        title     : 'الإجمالي',
        searchable: false,
        render    : function(data) {
            return '<strong>' + (data ?? 0) + '</strong>';
        }
    });

    $('#current_stock_thead').html(thead);

    var tfoot = '';
    columns.forEach(function(col, i) {
        tfoot += '<th class="current_footer_col_' + i + '"></th>';
    });
    $('#current_stock_tfoot').html(tfoot);

    current_stock_table = $('#current_stock_table').DataTable({
        processing : true,
        serverSide : true,
        pageLength : 25,
        
        
        columns    : columns,
       
        ajax: {
            url  : '{{ route("product.current_stock") }}',
            data : function(d) {
                d.location_ids = $('#daily_location_ids').val();
                d.stock_filter = $('#daily_stock_filter_qty').val();
                d.category_id   = $('#product_list_filter_category_id').val();
                d.brand_id      = $('#product_list_filter_brand_id').val();
                d.unit_id       = $('#product_list_filter_unit_id').val();
                d.tax_id        = $('#product_list_filter_tax_id').val();
                d.type          = $('#product_list_filter_type').val();
                d.active_state  = $('#active_state').val();
                d.custom_field1 = $('#filter_cf1').val();
                d.custom_field2 = $('#filter_cf2').val();
                d.custom_field3 = $('#filter_cf3').val();
                }
        },
        footerCallback: function() {
            var api = this.api();
            var grand_total = 0;

            columns.forEach(function(col, i) {
                if (i < current_fixed_cols) return;
                var total = api.column(i, { page: 'all' })
                    .data()
                    .reduce(function(a, b) {
                        return parseFloat(a || 0) + parseFloat(b || 0);
                    }, 0);
                if (i === columns.length - 1) {
                    $('.current_footer_col_' + i).html('<strong>' + grand_total + '</strong>');
                } else {
                    grand_total += total;
                    $('.current_footer_col_' + i).html('<strong>' + total + '</strong>');
                }
            });

            for (var i = 0; i < current_fixed_cols; i++) {
                if (i === 1) {
                    $('.current_footer_col_1').html('<strong>الإجمالي</strong>');
                } else {
                    $('.current_footer_col_' + i).html('');
                }
            }
        },
        fnDrawCallback: function() {
            __currency_convert_recursively($('#current_stock_table'));
        }
    });
}

        $(document).ready(function() {
            product_table = $('#product_table').DataTable({
                processing: true,
                serverSide: true,
                fixedHeader:false,
                aaSorting: [
                    [3, 'asc']
                ],
                scrollY: "75vh",
                scrollX: true,
                scrollCollapse: true,
                "ajax": {
                    "url": "/products",
                    "data": function(d) {
                        d.type = $('#product_list_filter_type').val();
                        d.category_id = $('#product_list_filter_category_id').val();
                        d.brand_id = $('#product_list_filter_brand_id').val();
                        d.unit_id = $('#product_list_filter_unit_id').val();
                        d.tax_id = $('#product_list_filter_tax_id').val();
                        d.active_state = $('#active_state').val();
                        d.not_for_selling = $('#not_for_selling').is(':checked');
                        d.location_id = $('#location_id').val();
                        d.custom_field1 = $('#filter_cf1').val();
d.custom_field2 = $('#filter_cf2').val();
d.custom_field3 = $('#filter_cf3').val();
                        if ($('#repair_model_id').length == 1) {
                            d.repair_model_id = $('#repair_model_id').val();
                        }

                        if ($('#woocommerce_enabled').length == 1 && $('#woocommerce_enabled').is(
                                ':checked')) {
                            d.woocommerce_enabled = 1;
                        }

                        d = __datatable_ajax_callback(d);
                    }
                },
                columnDefs: [{
                    "targets": [0, 1, 2],
                    "orderable": false,
                    "searchable": false
                }],
                columns: [{
                        data: 'mass_delete'
                    },
                    {
                        data: 'image',
                        name: 'products.image'
                    },
                    {
                        data: 'action',
                        name: 'action'
                    },
                    {
                        data: 'product',
                        name: 'products.name'
                    },
                    {
                        data: 'product_locations',
                        name: 'product_locations'
                    },
                    @can('view_purchase_price')
                        {
                            data: 'purchase_price',
                            name: 'max_purchase_price',
                            searchable: false
                        },
                    @endcan
                    @can('access_default_selling_price')
                        {
                            data: 'selling_price',
                            name: 'max_price',
                            searchable: false
                        },
                    @endcan {
                        data: 'current_stock',
                        searchable: false
                    },
                    {
                        data: 'type',
                        name: 'products.type'
                    },
                    {
                        data: 'category',
                        name: 'c1.name'
                    },
                    {
                        data: 'brand',
                        name: 'brands.name'
                    },
                    {
                        data: 'tax',
                        name: 'tax_rates.name',
                        searchable: false
                    },
                    {
                        data: 'sku',
                        name: 'products.sku'
                    },
                    {
                        data: 'product_custom_field1',
                        name: 'products.product_custom_field1',
                        visible: $('#cf_1').text().length > 0
                    },
                    {
                        data: 'product_custom_field2',
                        name: 'products.product_custom_field2',
                        visible: $('#cf_2').text().length > 0
                    },
                    {
                        data: 'product_custom_field3',
                        name: 'products.product_custom_field3',
                        visible: $('#cf_3').text().length > 0
                    },
                    {
                        data: 'product_custom_field4',
                        name: 'products.product_custom_field4',
                        visible: $('#cf_4').text().length > 0
                    },
                    {
                        data: 'product_custom_field5',
                        name: 'products.product_custom_field5',
                        visible: $('#cf_5').text().length > 0
                    },
                    {
                        data: 'product_custom_field6',
                        name: 'products.product_custom_field6',
                        visible: $('#cf_6').text().length > 0
                    },
                    {
                        data: 'product_custom_field7',
                        name: 'products.product_custom_field7',
                        visible: $('#cf_7').text().length > 0
                    },
                ],
                createdRow: function(row, data, dataIndex) {
                    if ($('input#is_rack_enabled').val() == 1) {
                        var target_col = 0;
                        @can('product.delete')
                            target_col = 1;
                        @endcan
                        $(row).find('td:eq(' + target_col + ') div').prepend(
                            '<i style="margin:auto;" class="fa fa-plus-circle text-success cursor-pointer no-print rack-details" title="' +
                            LANG.details + '"></i>&nbsp;&nbsp;');
                    }
                    $(row).find('td:eq(0)').attr('class', 'selectable_td');
                },
                fnDrawCallback: function(oSettings) {
                    __currency_convert_recursively($('#product_table'));
                },
            });
            // Array to track the ids of the details displayed rows
            var detailRows = [];

            $('#product_table tbody').on('click', 'tr i.rack-details', function() {
                var i = $(this);
                var tr = $(this).closest('tr');
                var row = product_table.row(tr);
                var idx = $.inArray(tr.attr('id'), detailRows);

                if (row.child.isShown()) {
                    i.addClass('fa-plus-circle text-success');
                    i.removeClass('fa-minus-circle text-danger');

                    row.child.hide();

                    // Remove from the 'open' array
                    detailRows.splice(idx, 1);
                } else {
                    i.removeClass('fa-plus-circle text-success');
                    i.addClass('fa-minus-circle text-danger');

                    row.child(get_product_details(row.data())).show();

                    // Add to the 'open' array
                    if (idx === -1) {
                        detailRows.push(tr.attr('id'));
                    }
                }
            });

            $('#opening_stock_modal').on('hidden.bs.modal', function(e) {
                product_table.ajax.reload();
            });

            $('table#product_table tbody').on('click', 'a.delete-product', function(e) {
                e.preventDefault();
                swal({
                    title: LANG.sure,
                    icon: "warning",
                    buttons: true,
                    dangerMode: true,
                }).then((willDelete) => {
                    if (willDelete) {
                        var href = $(this).attr('href');
                        $.ajax({
                            method: "DELETE",
                            url: href,
                            dataType: "json",
                            success: function(result) {
                                if (result.success == true) {
                                    toastr.success(result.msg);
                                    product_table.ajax.reload();
                                } else {
                                    toastr.error(result.msg);
                                }
                            }
                        });
                    }
                });
            });

            $(document).on('click', '#delete-selected', function(e) {
                e.preventDefault();
                var selected_rows = getSelectedRows();

                if (selected_rows.length > 0) {
                    $('input#selected_rows').val(selected_rows);
                    swal({
                        title: LANG.sure,
                        icon: "warning",
                        buttons: true,
                        dangerMode: true,
                    }).then((willDelete) => {
                        if (willDelete) {
                            $('form#mass_delete_form').submit();
                        }
                    });
                } else {
                    $('input#selected_rows').val('');
                    swal('@lang('lang_v1.no_row_selected')');
                }
            });

            $(document).on('click', '#deactivate-selected', function(e) {
                e.preventDefault();
                var selected_rows = getSelectedRows();

                if (selected_rows.length > 0) {
                    $('input#selected_products').val(selected_rows);
                    swal({
                        title: LANG.sure,
                        icon: "warning",
                        buttons: true,
                        dangerMode: true,
                    }).then((willDelete) => {
                        if (willDelete) {
                            var form = $('form#mass_deactivate_form')

                            var data = form.serialize();
                            $.ajax({
                                method: form.attr('method'),
                                url: form.attr('action'),
                                dataType: 'json',
                                data: data,
                                success: function(result) {
                                    if (result.success == true) {
                                        toastr.success(result.msg);
                                        product_table.ajax.reload();
                                        form
                                            .find('#selected_products')
                                            .val('');
                                    } else {
                                        toastr.error(result.msg);
                                    }
                                },
                            });
                        }
                    });
                } else {
                    $('input#selected_products').val('');
                    swal('@lang('lang_v1.no_row_selected')');
                }
            })

            $(document).on('click', '#edit-selected', function(e) {
                e.preventDefault();
                var selected_rows = getSelectedRows();

                if (selected_rows.length > 0) {
                    $('input#selected_products_for_edit').val(selected_rows);
                    $('form#bulk_edit_form').submit();
                } else {
                    $('input#selected_products').val('');
                    swal('@lang('lang_v1.no_row_selected')');
                }
            })

            $('table#product_table tbody').on('click', 'a.activate-product', function(e) {
                e.preventDefault();
                var href = $(this).attr('href');
                $.ajax({
                    method: "get",
                    url: href,
                    dataType: "json",
                    success: function(result) {
                        if (result.success == true) {
                            toastr.success(result.msg);
                            product_table.ajax.reload();
                        } else {
                            toastr.error(result.msg);
                        }
                    }
                });
            });

          $(document).on('select2:select select2:unselect', '#daily_location_ids', function() {
    // destroy الجدول القديم نهائياً
    if (daily_stock_table !== null) {
        daily_stock_table.destroy();
        daily_stock_table = null;
    }
    
    // امسح الـ thead و tfoot و tbody
    $('#daily_stock_thead').empty();
    $('#daily_stock_tfoot').empty();
    $('#daily_stock_table tbody').empty();
    
    if ($('#product_daily_stock_report').hasClass('active')) {
        setTimeout(function() {
            loadDailyStockReport();
        }, 100);
    }
});
 
           $(document).on('change select2:select select2:unselect',
    '#product_list_filter_type, #product_list_filter_category_id, #product_list_filter_brand_id, #product_list_filter_unit_id, #product_list_filter_tax_id, #location_id, #active_state, #repair_model_id, #filter_cf1, #filter_cf2, #filter_cf3',
    function() {
        if ($("#product_list_tab").hasClass('active')) {
            product_table.ajax.reload();
        }

        if ($("#product_stock_report").hasClass('active')) {
            stock_report_table.ajax.reload();
        }

        if ($("#product_daily_stock_report").hasClass('active') && daily_stock_table !== null) {
            daily_stock_table.ajax.reload();
        }

        if ($("#product_current_stock_report").hasClass('active') && current_stock_table !== null) {
            current_stock_table.ajax.reload();
        }
    });

            $(document).on('ifChanged', '#not_for_selling, #woocommerce_enabled', function() {
                if ($("#product_list_tab").hasClass('active')) {
                    product_table.ajax.reload();
                }

                if ($("#product_stock_report").hasClass('active')) {
                    stock_report_table.ajax.reload();
                }
            });

            $('#product_location').select2({
                dropdownParent: $('#product_location').closest('.modal')
            });

            $('#product_location').select2({
    dropdownParent: $('#product_location').closest('.modal')
});

// ← حط هنا
$('#snapshot_date').daterangepicker({
    singleDatePicker : true,
    showDropdowns    : true,
    autoApply        : true,
    startDate        : moment(),
    locale           : {
        format : 'YYYY-MM-DD',
    }
}, function(start) {
    $('#snapshot_date').val(start.format('YYYY-MM-DD'));

    if ($('#product_daily_stock_report').hasClass('active')) {
        if (daily_stock_table !== null) {
            daily_stock_table.destroy();
            daily_stock_table = null;
        }
        $('#daily_stock_thead').empty();
        $('#daily_stock_tfoot').empty();
        $('#daily_stock_table tbody').empty();

        setTimeout(function() {
            loadDailyStockReport();
        }, 100);
    }
}); 

            @if ($is_woocommerce)
                $(document).on('click', '.toggle_woocomerce_sync', function(e) {
                    e.preventDefault();
                    var selected_rows = getSelectedRows();
                    if (selected_rows.length > 0) {
                        $('#woocommerce_sync_modal').modal('show');
                        $("input#woocommerce_products_sync").val(selected_rows);
                    } else {
                        $('input#selected_products').val('');
                        swal('@lang('lang_v1.no_row_selected')');
                    }
                });

                $(document).on('submit', 'form#toggle_woocommerce_sync_form', function(e) {
                    e.preventDefault();
                    var url = $('form#toggle_woocommerce_sync_form').attr('action');
                    var method = $('form#toggle_woocommerce_sync_form').attr('method');
                    var data = $('form#toggle_woocommerce_sync_form').serialize();
                    var ladda = Ladda.create(document.querySelector('.ladda-button'));
                    ladda.start();
                    $.ajax({
                        method: method,
                        dataType: "json",
                        url: url,
                        data: data,
                        success: function(result) {
                            ladda.stop();
                            if (result.success) {
                                $("input#woocommerce_products_sync").val('');
                                $('#woocommerce_sync_modal').modal('hide');
                                toastr.success(result.msg);
                                product_table.ajax.reload();
                            } else {
                                toastr.error(result.msg);
                            }
                        }
                    });
                });
            @endif
        });

        $(document).on('shown.bs.modal', 'div.view_product_modal, div.view_modal, #view_product_modal',
            function() {
                var div = $(this).find('#view_product_stock_details');
                if (div.length) {
                    $.ajax({
                        url: "{{ action([\App\Http\Controllers\ReportController::class, 'getStockReport']) }}" +
                            '?for=view_product&product_id=' + div.data('product_id'),
                        dataType: 'html',
                        success: function(result) {
                            div.html(result);
                            __currency_convert_recursively(div);
                        },
                    });
                }
                __currency_convert_recursively($(this));
            });
        var data_table_initailized = false;
        $('a[data-toggle="tab"]').on('shown.bs.tab', function(e) {
             $('.daily_stock_filter').hide();
             $('#location_filter').show();
             $('#active_state').closest('.col-md-3').show();
            if ($(e.target).attr('href') == '#product_stock_report') {
                if (!data_table_initailized) {
                    //Stock report table
                    var stock_report_cols = [{
                            data: 'action',
                            name: 'action',
                            searchable: false,
                            orderable: false
                        },
                        {
                            data: 'sku',
                            name: 'variations.sub_sku'
                        },
                        {
                            data: 'product',
                            name: 'p.name'
                        },
                        {
                            data: 'variation',
                            name: 'variation'
                        },
                        {
                            data: 'category_name',
                            name: 'c.name'
                        },
                        {
                            data: 'location_name',
                            name: 'l.name'
                        },
                        {
                            data: 'unit_price',
                            name: 'variations.sell_price_inc_tax'
                        },
                        {
                            data: 'stock',
                            name: 'stock',
                            searchable: false
                        },
                    ];
                    if ($('th.stock_price').length) {
                        stock_report_cols.push({
                            data: 'stock_price',
                            name: 'stock_price',
                            searchable: false
                        });
                        stock_report_cols.push({
                            data: 'stock_value_by_sale_price',
                            name: 'stock_value_by_sale_price',
                            searchable: false,
                            orderable: false
                        });
                        stock_report_cols.push({
                            data: 'potential_profit',
                            name: 'potential_profit',
                            searchable: false,
                            orderable: false
                        });
                    }

                    stock_report_cols.push({
                        data: 'total_sold',
                        name: 'total_sold',
                        searchable: false
                    });
                    stock_report_cols.push({
                        data: 'total_transfered',
                        name: 'total_transfered',
                        searchable: false
                    });
                    stock_report_cols.push({
                        data: 'total_adjusted',
                        name: 'total_adjusted',
                        searchable: false
                    });
                    stock_report_cols.push({
                        data: 'product_custom_field1',
                        name: 'p.product_custom_field1'
                    });
                    stock_report_cols.push({
                        data: 'product_custom_field2',
                        name: 'p.product_custom_field2'
                    });
                    stock_report_cols.push({
                        data: 'product_custom_field3',
                        name: 'p.product_custom_field3'
                    });
                    stock_report_cols.push({
                        data: 'product_custom_field4',
                        name: 'p.product_custom_field4'
                    });

                    if ($('th.current_stock_mfg').length) {
                        stock_report_cols.push({
                            data: 'total_mfg_stock',
                            name: 'total_mfg_stock',
                            searchable: false
                        });
                    }
                    
                    stock_report_table = $('#stock_report_table').DataTable({
                        order: [
                            [1, 'asc']
                        ],
                        processing: true,
                        serverSide: true,
                        scrollY: "75vh",
                        scrollX: true,
                        scrollCollapse: true,
                        fixedHeader:false,
                        ajax: {
                            url: '/reports/stock-report',
                            data: function(d) {
                                d.location_id = $('#location_id').val();
                                d.category_id = $('#product_list_filter_category_id').val();
                                d.brand_id = $('#product_list_filter_brand_id').val();
                                d.unit_id = $('#product_list_filter_unit_id').val();
                                d.type = $('#product_list_filter_type').val();
                                d.active_state = $('#active_state').val();
                                d.not_for_selling = $('#not_for_selling').is(':checked');
                                 d.product_custom_field1 = $('#filter_cf1').val();
        d.product_custom_field2 = $('#filter_cf2').val();
        d.product_custom_field3 = $('#filter_cf3').val(); 
                                if ($('#repair_model_id').length == 1) {
                                    d.repair_model_id = $('#repair_model_id').val();
                                }
                            }
                        },
                        columns: stock_report_cols,
                        fnDrawCallback: function(oSettings) {
                            __currency_convert_recursively($('#stock_report_table'));
                        },
                        "footerCallback": function(row, data, start, end, display) {
                            var footer_total_stock = 0;
                            var footer_total_sold = 0;
                            var footer_total_transfered = 0;
                            var total_adjusted = 0;
                            var total_stock_price = 0;
                            var footer_stock_value_by_sale_price = 0;
                            var total_potential_profit = 0;
                            var footer_total_mfg_stock = 0;
                            for (var r in data) {
                                footer_total_stock += $(data[r].stock).data('orig-value') ?
                                    parseFloat($(data[r].stock).data('orig-value')) : 0;

                                footer_total_sold += $(data[r].total_sold).data('orig-value') ?
                                    parseFloat($(data[r].total_sold).data('orig-value')) : 0;

                                footer_total_transfered += $(data[r].total_transfered).data(
                                        'orig-value') ?
                                    parseFloat($(data[r].total_transfered).data('orig-value')) : 0;

                                total_adjusted += $(data[r].total_adjusted).data('orig-value') ?
                                    parseFloat($(data[r].total_adjusted).data('orig-value')) : 0;

                                total_stock_price += $(data[r].stock_price).data('orig-value') ?
                                    parseFloat($(data[r].stock_price).data('orig-value')) : 0;

                                footer_stock_value_by_sale_price += $(data[r].stock_value_by_sale_price)
                                    .data('orig-value') ?
                                    parseFloat($(data[r].stock_value_by_sale_price).data(
                                        'orig-value')) : 0;

                                total_potential_profit += $(data[r].potential_profit).data(
                                        'orig-value') ?
                                    parseFloat($(data[r].potential_profit).data('orig-value')) : 0;

                                footer_total_mfg_stock += $(data[r].total_mfg_stock).data(
                                        'orig-value') ?
                                    parseFloat($(data[r].total_mfg_stock).data('orig-value')) : 0;
                            }

                            $('.footer_total_stock').html(__currency_trans_from_en(footer_total_stock,
                                false));
                            $('.footer_total_stock_price').html(__currency_trans_from_en(
                                total_stock_price));
                            $('.footer_total_sold').html(__currency_trans_from_en(footer_total_sold,
                                false));
                            $('.footer_total_transfered').html(__currency_trans_from_en(
                                footer_total_transfered, false));
                            $('.footer_total_adjusted').html(__currency_trans_from_en(total_adjusted,
                                false));
                            $('.footer_stock_value_by_sale_price').html(__currency_trans_from_en(
                                footer_stock_value_by_sale_price));
                            $('.footer_potential_profit').html(__currency_trans_from_en(
                                total_potential_profit));
                            if ($('th.current_stock_mfg').length) {
                                $('.footer_total_mfg_stock').html(__currency_trans_from_en(
                                    footer_total_mfg_stock, false));
                            }
                        },
                    });
                    data_table_initailized = true;
                    // الاستماع لحدث التغيير في فلاتر الحقول المخصصة لإعادة تحميل جدول تقرير المخزون
                    $(document).on('change select2:select select2:unselect', '#filter_cf1, #filter_cf2, #filter_cf3', function() {
    if ($("#product_stock_report").hasClass('active') && stock_report_table !== null) {
        stock_report_table.ajax.reload();
    }
});

                } else {
                    stock_report_table.ajax.reload();
                }

            } else if ($(e.target).attr('href') == '#product_daily_stock_report') {
                $('#location_filter').hide();
                $('.daily_stock_filter').show();
                $('#snapshot_date').closest('.col-md-2').show(); // ← أظهر فلتر التاريخ
                $('#active_state').closest('.col-md-3').hide();


                if (!$('#snapshot_date').val()) {
                    $('#snapshot_date').val(new Date().toISOString().split('T')[0]);
                }

                if (!$('#daily_location_ids').val() || $('#daily_location_ids').val().length === 0) {
                    var first_option = $('#daily_location_ids option:first').val();
                    if (first_option) {
                        $('#daily_location_ids').val([first_option]).trigger('change');
                    }
                }

                setTimeout(function() {
                    loadDailyStockReport();
                }, 200);

            } else if ($(e.target).attr('href') == '#product_current_stock_report') {
                $('#location_filter').hide();
                $('.daily_stock_filter').show();
                $('#snapshot_date').closest('.col-md-2').hide(); // ← أخفي فلتر التاريخ
                $('#active_state').closest('.col-md-3').hide();


                if (!$('#daily_location_ids').val() || $('#daily_location_ids').val().length === 0) {
                    var first_option = $('#daily_location_ids option:first').val();
                    if (first_option) {
                        $('#daily_location_ids').val([first_option]).trigger('change');
                    }
                }

                setTimeout(function() {
                    loadCurrentStockReport();
                }, 300);

            } else {
                $('.daily_stock_filter').hide();
                $('#location_filter').show();
                $('#active_state').closest('.col-md-3').show();

                $('#snapshot_date').closest('.col-md-2').hide(); // ← أخفي عند التابات الثانية
                product_table.ajax.reload();
            }

            $('.btn-default').removeClass('btn-default');
            $('.tw-dw-btn-outline').removeClass('btn');
        });

        // ← هنا بعد حدث التاب مباشرة
        $(document).on('change', '#daily_stock_filter_qty', function() {
            if ($('#product_daily_stock_report').hasClass('active') && daily_stock_table !== null) {
                daily_stock_table.ajax.reload();
            }
            if ($('#product_current_stock_report').hasClass('active') && current_stock_table !== null) {
                current_stock_table.ajax.reload();
            }
        });

      

   

// ── Daily Stock Report ─────────────────────────────────────────────





 $(document).on('select2:select select2:unselect', '#daily_location_ids', function() {
    if (daily_stock_table !== null) {
        daily_stock_table.destroy();
        daily_stock_table = null;
    }
    $('#daily_stock_thead').empty();
    $('#daily_stock_tfoot').empty();
    $('#daily_stock_table tbody').empty();

    if (current_stock_table !== null) {
        current_stock_table.destroy();
        current_stock_table = null;
    }
    $('#current_stock_thead').empty();
    $('#current_stock_tfoot').empty();
    $('#current_stock_table tbody').empty();

    if ($('#product_daily_stock_report').hasClass('active')) {
        setTimeout(function() { loadDailyStockReport(); }, 100);
    }

    if ($('#product_current_stock_report').hasClass('active')) {
        setTimeout(function() { loadCurrentStockReport(); }, 100);
    }
});

        $(document).on('click', '.update_product_location', function(e) {
            e.preventDefault();
            var selected_rows = getSelectedRows();

            if (selected_rows.length > 0) {
                $('input#selected_products').val(selected_rows);
                var type = $(this).data('type');
                var modal = $('#edit_product_location_modal');
                if (type == 'add') {
                    modal.find('.remove_from_location_title').addClass('hide');
                    modal.find('.add_to_location_title').removeClass('hide');
                } else if (type == 'remove') {
                    modal.find('.add_to_location_title').addClass('hide');
                    modal.find('.remove_from_location_title').removeClass('hide');
                }

                modal.modal('show');
                modal.find('#product_location').select2({
                    dropdownParent: modal
                });
                modal.find('#product_location').val('').change();
                modal.find('#update_type').val(type);
                modal.find('#products_to_update_location').val(selected_rows);
            } else {
                $('input#selected_products').val('');
                swal('@lang('lang_v1.no_row_selected')');
            }
        });

        $(document).on('submit', 'form#edit_product_location_form', function(e) {
            e.preventDefault();
            var form = $(this);
            var data = form.serialize();

            $.ajax({
                method: $(this).attr('method'),
                url: $(this).attr('action'),
                dataType: 'json',
                data: data,
                beforeSend: function(xhr) {
                    __disable_submit_button(form.find('button[type="submit"]'));
                },
                success: function(result) {
                    if (result.success == true) {
                        $('div#edit_product_location_modal').modal('hide');
                        toastr.success(result.msg);
                        product_table.ajax.reload();
                        $('form#edit_product_location_form')
                            .find('button[type="submit"]')
                            .attr('disabled', false);
                    } else {
                        toastr.error(result.msg);
                    }
                },
            });
        });

        // بعد «حفظ وطباعة»: فتح نافذة صغيرة للطباعة المباشرة ثم إغلاقها (بدون نقل الصفحة الرئيسية)
        $(function() {
            var params = new URLSearchParams(window.location.search);
            var printProductId = params.get('print_product_id');
            var printAll = params.get('print_all');
            var printCopies = params.get('print_copies') || '1';
            var printSendMode = params.get('print_send_mode') || 'one_by_one';
            var autoPrint = params.get('auto_print') || '0';
            var defaultPrinter = params.get('default_printer') || '';
            if (printProductId && printAll) {
                var printUrl = '{{ url("/print-barcode") }}?product_id=' + encodeURIComponent(printProductId) + '&print_all=1&print_copies=' + encodeURIComponent(printCopies) + '&print_send_mode=' + encodeURIComponent(printSendMode) + '&auto_print=1&default_printer=' + encodeURIComponent(defaultPrinter);
                window.open(printUrl, 'print_barcode_popup', 'width=400,height=320,scrollbars=no,menubar=no,toolbar=no');
                if (window.history && window.history.replaceState) {
                    window.history.replaceState({}, document.title, window.location.pathname);
                }
            }
        });
    </script>
@endsection
