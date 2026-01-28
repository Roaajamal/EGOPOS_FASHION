@extends('layouts.app')
@section('title', __('lang_v1.sell_return'))

@section('content')
<section class="content-header no-print">
    <h1 class="tw-text-xl md:tw-text-3xl tw-font-bold tw-text-black">@lang('lang_v1.sell_return')</h1>
</section>

<section class="content no-print">
    
    {!! Form::hidden('location_id', $sell->location->id, ['id' => 'location_id']); !!}
    {!! Form::open(['url' => action([\App\Http\Controllers\SellReturnController::class, 'store']), 'method' => 'post', 'id' => 'sell_return_form' ]) !!}
    {!! Form::hidden('transaction_id', $sell->id); !!}
    {!! Form::hidden('return_to_pos', 1); !!}
    
    <div class="box box-solid">
        <div class="box-body">
            <div class="row">
                <div class="col-sm-4">
                    <strong>@lang('sale.invoice_no'):</strong> {{ $sell->invoice_no }} <br>
                    <strong>@lang('contact.customer'):</strong> {{ $sell->contact->name }}
                </div>
            </div>
        </div>
    </div>

    <div class="box box-solid">
        <div class="box-body">
            <div class="row">
                <div class="col-sm-12">
                    <table class="table bg-gray" id="sell_return_table">
                        <thead>
                            <tr class="bg-green">
                                <th>#</th>
                                <th>@lang('product.product_name')</th>
                                <th>@lang('sale.unit_price')</th>
                                <th>@lang('lang_v1.sell_quantity')</th>
                                <th>@lang('lang_v1.return_quantity')</th>
                                <th>@lang('lang_v1.return_subtotal')</th>
                            </tr>
                        </thead>
                        <tbody>
                            @php $i = 1; @endphp
                            @foreach($sell->sell_lines as $sell_line)
                                @php
                                    // التحقق من الـ SKU
                                    $is_target = (!empty($sku) && $sell_line->variations->sub_sku == $sku);
                                    
                                    // إخفاء أي منتج آخر
                                    if(!empty($sku) && !$is_target) continue;

                                    $check_decimal = ($sell_line->product->unit->allow_decimal == 0) ? 'true' : 'false';
                                    $unit_name = $sell_line->product->unit->short_name;
                                    
                                    // الكمية الافتراضية 1 للمنتج المختار
                                    $default_qty = $is_target ? 1 : 0;
                                @endphp
                                <tr>
                                    <td>{{ $i }}</td>
                                    <td>
                                        {{ $sell_line->product->name }}
                                        <br><small>{{ $sell_line->variations->sub_sku }}</small>
                                    </td>
                                    <td><span class="display_currency" data-currency_symbol="true">{{ $sell_line->unit_price_inc_tax }}</span></td>
                                    <td>{{ $sell_line->formatted_qty }} {{$unit_name}}</td>
                                    <td>
                                        <input type="text" name="products[{{$loop->index}}][quantity]" 
                                               value="{{@format_quantity($default_qty)}}" 
                                               class="form-control input-sm input_number return_qty input_quantity" 
                                               style="border: 2px solid #2ecc71; background-color: #f0fff4;">
                                        <input name="products[{{$loop->index}}][unit_price_inc_tax]" type="hidden" class="unit_price" value="{{@num_format($sell_line->unit_price_inc_tax)}}">
                                        <input name="products[{{$loop->index}}][sell_line_id]" type="hidden" value="{{$sell_line->id}}">
                                    </td>
                                    <td><div class="return_subtotal"></div></td>
                                </tr>
                                @php $i++; @endphp
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="row">
                @php
                    $tax_percent = !empty($sell->tax) ? $sell->tax->amount : 0;
                @endphp
                {!! Form::hidden('tax_id', $sell->tax_id); !!}
                {!! Form::hidden('tax_amount', 0, ['id' => 'tax_amount']); !!}
                {!! Form::hidden('tax_percent', $tax_percent, ['id' => 'tax_percent']); !!}
                
                <div class="col-sm-12 text-right">
                    <strong>@lang('lang_v1.return_total'): </strong>&nbsp; <span id="net_return">0</span>
                </div>
            </div>
            <br>
            <div class="row">
                <div class="col-sm-12 text-right">
                    <button type="submit" class="btn btn-primary btn-lg">@lang('messages.save')</button>
                </div>
            </div>
        </div>
    </div>
    {!! Form::close() !!}
</section>
@stop