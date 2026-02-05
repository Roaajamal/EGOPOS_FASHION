@extends('layouts.app')
@section('title', __('stock_adjustment.edit'))

@section('content')
<section class="content-header">
    <h1>@lang('stock_adjustment.edit')</h1>
</section>

<section class="content">
    {!! Form::open(['url' => action([\App\Http\Controllers\StockAdjustmentController::class, 'update'], [$stock_adjustment->id]), 'method' => 'PUT', 'id' => 'stock_adjustment_edit_form' ]) !!}
    
    @component('components.widget', ['class' => 'box-solid'])
        <div class="row">
            <div class="col-sm-3">
                <div class="form-group">
                    {!! Form::label('location_id', __('purchase.business_location').':*') !!}
                    {!! Form::select('location_id', $business_locations, $stock_adjustment->location_id, ['class' => 'form-control select2', 'placeholder' => __('messages.please_select'), 'required', 'disabled']); !!}
                    {!! Form::hidden('location_id', $stock_adjustment->location_id, ['id' => 'location_id']) !!}
                </div>
            </div>
            <div class="col-sm-3">
                <div class="form-group">
                    {!! Form::label('ref_no', __('purchase.ref_no').':') !!}
                    {!! Form::text('ref_no', $stock_adjustment->ref_no, ['class' => 'form-control', 'readonly']); !!}
                </div>
            </div>
            <div class="col-sm-3">
                <div class="form-group">
                    {!! Form::label('transaction_date', __('messages.date').':*') !!}
                    {!! Form::text('transaction_date', @format_datetime($stock_adjustment->transaction_date), ['class' => 'form-control', 'readonly', 'required']); !!}
                </div>
            </div>
            <div class="col-sm-3">
                <div class="form-group">
                    {!! Form::label('adjustment_type', __('stock_adjustment.out_type') . ':*') !!}
                    {!! Form::select('adjustment_type', [
                        'normal' => __('stock_adjustment.normal'), 
                        'abnormal' => __('stock_adjustment.abnormal'),
                        'from_warehouse_to_branch' => __('stock_adjustment.from_warehouse_to_branch'),
                        'from_branch_to_branch' => __('stock_adjustment.from_branch_to_branch'),
                        'from_warehouse_to_recipient' => __('stock_adjustment.from_warehouse_to_recipient')
                    ], $stock_adjustment->adjustment_type, ['class' => 'form-control select2', 'placeholder' => __('messages.please_select'), 'required']) !!}
                </div>
            </div>
        </div>
    @endcomponent

    @component('components.widget', ['class' => 'box-solid'])
        <div class="row">
            <div class="col-sm-8 col-sm-offset-2">
                <div class="form-group">
                    <div class="input-group">
                        <span class="input-group-addon"><i class="fa fa-search"></i></span>
                        {!! Form::text('search_product', null, ['class' => 'form-control', 'id' => 'search_product_for_s_adj', 'placeholder' => __('stock_adjustment.search_products')]); !!}
                    </div>
                </div>
            </div>
        </div>
        
        @php
            $business_id = $stock_adjustment->business_id;
            $business = \App\Business::find($business_id);
            $custom_labels = json_decode($business->custom_labels, true);
            $p_labels = $custom_labels['product'] ?? [];
        @endphp

        <div class="row">
            <div class="col-sm-12">
                <div class="table-responsive">
                    <table class="table table-bordered table-striped table-condensed" id="stock_adjustment_product_table">
                        <thead>
                            <tr>
                                @if(!empty($p_labels['custom_field_1']))
                                    <th class="text-center">{{ $p_labels['custom_field_1'] }}</th>
                                @endif
                                <th class="text-center">@lang('product.sku')</th> 
                                <th class="text-center">الوصف</th>
                                <th class="text-center">@lang('sale.qty')</th>

                                @if(!empty($p_labels['custom_field_2']))
                                    <th class="text-center">{{ $p_labels['custom_field_2'] }}</th>
                                @endif

                                @if(!empty($p_labels['custom_field_3']))
                                    <th class="text-center">{{ $p_labels['custom_field_3'] }}</th>
                                @endif

                                 <th class="text-center"> @lang('lang_v1.cost') </th>
                                 <th class="text-center"> @lang('stock_adjustment.total_amount') </th>
                                 <th class="text-center"><i class="fa fa-trash"></i></th>
                            </tr>
                        </thead>
                        <tbody>
    @foreach($stock_adjustment->stock_adjustment_lines as $line)
        @include('stock_adjustment.partials.product_table_row', [
            'product' => $line, // الآن الـ $line يحتوي على الـ sku والحقول المخصصة مباشرة
            'row_index' => $loop->index, 
            'quantity' => $line->quantity, 
            'unit_price' => $line->unit_price,
            'purchase_price' => $line->unit_price
        ])
    @endforeach
</tbody>
                        <tfoot>
                            <tr class="text-center">
                                @php
                                // زدنا الرقم الأساسي ليصبح 3 (SKU + وصف + كمية)
                                 $footer_colspan = 3; 
                                 if(!empty($p_labels['custom_field_1'])) $footer_colspan++;
                                 if(!empty($p_labels['custom_field_2'])) $footer_colspan++;
                                 if(!empty($p_labels['custom_field_3'])) $footer_colspan++;
                                 $footer_colspan++; // عمود السعر "س"
                                @endphp
                                <td colspan="{{ $footer_colspan }}"></td>
                                <td><b>ج الكلي:</b></td>
                                <td>
                                    <input type="hidden" name="final_total" id="total_adjustment_value" value="{{$stock_adjustment->final_total}}">
                                    <span id="total_adjustment">{{@num_format($stock_adjustment->final_total)}}</span>
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
@endsection

@section('javascript')
    <script src="{{ asset('js/stock_adjustment.js?v=' . $asset_v) }}"></script>
    <script type="text/javascript">
        $(document).ready(function() {
            // تشغيل الحسبة فور تحميل الصفحة لضمان ظهور الخصم المخزن
            // استخدمنا setTimeout بسيط للتأكد من تحميل كافة عناصر الجدول
            setTimeout(function() {
                update_table_total();
            }, 300);
        });
    </script>
@endsection