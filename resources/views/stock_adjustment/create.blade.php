@php 
    $custom_labels = json_decode(session('business.custom_labels'), true);
    $p_labels = $custom_labels['product'] ?? [];
    
    // حساب عدد الأعمدة الديناميكي للـ colspan في تذييل الجدول
    // الأعمدة الأساسية: الوصف (1)، الكمية (1)، س (1)، ج (1)، الحذف (1) = المجموع 5
    $dynamic_colspan = 2; // (الوصف + الكمية)
    if (!empty($p_labels['custom_field_1'])) $dynamic_colspan++;
    if (!empty($p_labels['custom_field_2'])) $dynamic_colspan++;
    if (!empty($p_labels['custom_field_3'])) $dynamic_colspan++;
@endphp

@extends('layouts.app')
@section('title', __('stock_adjustment.add'))

@section('content')
    <section class="content-header">
        <br>
        <h1 class="tw-text-xl md:tw-text-3xl tw-font-bold tw-text-black">@lang('stock_adjustment.add')</h1>
    </section>

    <section class="content no-print">
        {!! Form::open([
            'url' => action([\App\Http\Controllers\StockAdjustmentController::class, 'store']),
            'method' => 'post',
            'id' => 'stock_adjustment_form',
            'files' => true 
        ]) !!}

        @component('components.widget', ['class' => 'box-solid'])
            <div class="row">
                <div class="col-sm-3">
                    <div class="form-group">
                        {!! Form::label('location_id', __('purchase.business_location') . ':*') !!}
                        {!! Form::select('location_id', $business_locations, null, [
                            'class' => 'form-control select2',
                            'placeholder' => __('messages.please_select'),
                            'required',
                        ]) !!}
                    </div>
                </div>
                <div class="col-sm-3">
                    <div class="form-group">
                        {!! Form::label('ref_no', __('purchase.ref_no') . ':') !!}
                        {!! Form::text('ref_no', null, ['class' => 'form-control']) !!}
                    </div>
                </div>
                <div class="col-sm-3">
                    <div class="form-group">
                        {!! Form::label('transaction_date', __('messages.date') . ':*') !!}
                        <div class="input-group">
                            <span class="input-group-addon"><i class="fa fa-calendar"></i></span>
                            {!! Form::text('transaction_date', @format_datetime('now'), ['class' => 'form-control', 'readonly', 'required']) !!}
                        </div>
                    </div>
                </div>
                <div class="col-sm-3">
                    <div class="form-group">
                        {!! Form::label('adjustment_type', __('stock_adjustment.out_type') . ':*') !!}
                        {!! Form::select('adjustment_type', [
                            'normal' => __('stock_adjustment.normal'), 
                            'abnormal' => __('stock_adjustment.abnormal'),
                              ], null, ['class' => 'form-control select2', 'placeholder' => __('messages.please_select'), 'required']) !!}
                    </div> 
                </div>
            </div>
        @endcomponent

        @component('components.widget', ['class' => 'box-solid'])
            <div class="row">
                <div class="col-sm-10 col-sm-offset-1">
                    <div class="row">
                        <div class="col-sm-9">
                            <div class="form-group">
                                <div class="input-group">
                                    <span class="input-group-addon">
                                        <i class="fa fa-search"></i>
                                    </span>
                                    {!! Form::text('search_product', null, [
                                        'class' => 'form-control',
                                        'id' => 'search_product_for_srock_adjustment',
                                        'placeholder' => __('stock_adjustment.search_product'),
                                        'disabled'
                                    ]) !!}
                                </div>
                            </div>
                        </div>
                        <div class="col-sm-3">
                            <button type="button" class="tw-dw-btn tw-dw-btn-primary tw-text-white tw-dw-btn-sm tw-w-full" 
                                    style="height: 34px; width: 100%;" 
                                    data-toggle="modal" 
                                    data-target="#export_quantity_products_modal">
                                <i class="fa fa-file-excel-o"></i> @lang('stock_adjustment.export')
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-sm-12">
                    <input type="hidden" id="product_row_index" value="0">
                    <input type="hidden" id="total_amount" name="final_total" value="0">
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped table-condensed" id="stock_adjustment_product_table">
                            <thead>
                                <tr>
                                    @if(!empty($p_labels['custom_field_1']))
                                        <th class="text-center">{{ $p_labels['custom_field_1'] }}</th>
                                    @endif
                                    <th class="text-center">@lang('product.sku')</th>
                                    <th class="text-center">@lang('lang_v1.description')</th>
                                    <th class="text-center">@lang('sale.qty')</th>
                                    
                                    @if(!empty($p_labels['custom_field_2']))
                                        <th class="text-center">{{ $p_labels['custom_field_2'] }}</th>
                                    @endif
                                    
                                    @if(!empty($p_labels['custom_field_3']))
                                        <th class="text-center">{{ $p_labels['custom_field_3'] }}</th>
                                    @endif
                                    
                                    <th class="text-center"> @lang('lang_v1.cost') </th>
                                    <th class="text-center"> @lang('stock_adjustment.total') </th>
                                    <th class="text-center"><i class="fa fa-trash"></i></th>
                                </tr>
                            </thead>
                            <tbody>
                                {{-- سيتم إضافة الأسطر هنا بواسطة JS --}}
                            </tbody>
                            <tfoot>
        @php
    // زدنا الرقم الأساسي ليصبح 3 (SKU + وصف + كمية)
    $footer_colspan = 3; 
    if(!empty($p_labels['custom_field_1'])) $footer_colspan++;
    if(!empty($p_labels['custom_field_2'])) $footer_colspan++;
    if(!empty($p_labels['custom_field_3'])) $footer_colspan++;
    $footer_colspan++; // عمود السعر "س"
@endphp
<td colspan="{{ $footer_colspan }}"></td>
        
        <td class="text-center">
            <b> @lang('stock_adjustment.total_amount'):</b>
        </td>
        <td class="text-center">
            <span id="total_adjustment">0.00</span>
            {{-- حقل مخفي لإرسال القيمة للفورم --}}
            <input type="hidden" name="final_total" id="total_adjustment_value" value="{{ $stock_adjustment->final_total ?? 0 }}">
        </td>
        <td></td>
    </tr>
</tfoot>
                        </table>
                    </div>
                </div>
            </div>
        @endcomponent

        @component('components.widget', ['class' => 'box-solid'])
            <div class="row">
                <div class="col-sm-4">
                    <div class="form-group">
                        {!! Form::label('total_amount_recovered', __('stock_adjustment.total_amount_recovered') . ':') !!}
                        {!! Form::text('total_amount_recovered', 0, ['class' => 'form-control input_number', 'placeholder' => __('stock_adjustment.total_amount_recovered')]) !!}
                    </div>
                </div>
                <div class="col-sm-4">
                    <div class="form-group">
                        {!! Form::label('additional_notes', __('stock_adjustment.reason_for_stock_adjustment') . ':') !!}
                        {!! Form::textarea('additional_notes', null, ['class' => 'form-control', 'placeholder' => __('stock_adjustment.reason_for_stock_adjustment'), 'rows' => 3]) !!}
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-sm-12 text-center">
                    <button type="submit" class="tw-dw-btn tw-dw-btn-primary tw-dw-btn-lg tw-text-white">@lang('messages.save')</button>
                </div>
            </div>
        @endcomponent
        {!! Form::close() !!}
    </section>

    @include('stock_adjustment.partials.export_quantity_products_modal')
@stop

@section('javascript')
    <script src="{{ asset('js/stock_adjustment.js?v=' . $asset_v) }}"></script>
    <script type="text/javascript">
        $(document).ready(function() {
            __page_leave_confirmation('#stock_adjustment_form');
        });
    </script>
@endsection