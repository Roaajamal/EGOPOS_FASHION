<tr class="product_row">
    @php
        $custom_labels = json_decode(session('business.custom_labels'), true);
        $p_labels = $custom_labels['product'] ?? [];

        $cf1 = $product->custom_field_1 ?? ($product->product_custom_field1 ?? '-');
        $cf2 = $product->custom_field_2 ?? ($product->product_custom_field2 ?? '-');
        $cf3 = $product->custom_field_3 ?? ($product->product_custom_field3 ?? '-');

        // 1. تحديد السعر شامل الضريبة (dpp_inc_tax)
        $price_inc_tax = 0;
        if (!empty($product->dpp_inc_tax) && $product->dpp_inc_tax != 0) {
            $price_inc_tax = $product->dpp_inc_tax;
        } elseif (!empty($product->last_purchased_price) && $product->last_purchased_price != 0) {
            $price_inc_tax = $product->last_purchased_price;
        } elseif (!empty($product->unit_price) && $product->unit_price != 0) {
            $price_inc_tax = $product->unit_price;
        } elseif (!empty($product->default_purchase_price) && $product->default_purchase_price != 0) {
            $price_inc_tax = $product->default_purchase_price;
        }

        $product->unit_price = $price_inc_tax;

        $max_qty_rule = $product->qty_available;
        $formatted_max_quantity = $product->formatted_qty_available;
        $max_qty_msg = __('validation.custom-messages.quantity_not_available', ['qty'=> $formatted_max_quantity, 'unit' => $product->unit]);
        $allow_decimal = true;

        if(empty($product->quantity_ordered)) {
            $product->quantity_ordered = 1;
        }
        $multiplier = 1;
        if($product->unit_allow_decimal != 1) {
            $allow_decimal = false;
        }

        foreach($sub_units as $key => $value) {
            if(!empty($product->sub_unit_id) && $product->sub_unit_id == $key) {
                $multiplier = $value['multiplier'];
                $max_qty_rule = $max_qty_rule / $multiplier;
                $unit_name = $value['name'];
                $max_qty_msg = __('validation.custom-messages.quantity_not_available', ['qty'=> $max_qty_rule, 'unit' => $unit_name]);

                if(!empty($product->lot_no_line_id)){
                    $max_qty_msg = __('lang_v1.quantity_error_msg_in_lot', ['qty'=> $max_qty_rule, 'unit' => $unit_name]);
                }

                if($value['allow_decimal']) {
                    $allow_decimal = true;
                }
            }
        }
        $qty_ordered = $product->quantity_ordered / $multiplier;
    @endphp

    <td>
        {{$product->product_name}}
        <br/>
        <small class="text-primary"><strong>{{$product->sub_sku}}</strong></small>

        @if( session()->get('business.enable_lot_number') == 1 || session()->get('business.enable_product_expiry') == 1)
            @php
                $lot_enabled = session()->get('business.enable_lot_number');
                $exp_enabled = session()->get('business.enable_product_expiry');
                $lot_no_line_id = !empty($product->lot_no_line_id) ? $product->lot_no_line_id : '';
            @endphp
            
            @if($product->enable_stock == 1)
                <br>
                <small class="text-muted" style="white-space: nowrap;">@lang('report.current_stock'): 
                    <span class="qty_available_text">{{$product->formatted_qty_available}}</span> 
                    {{ $product->unit }}</small>
            @endif

            @if(!empty($product->lot_numbers))
                <select class="form-control lot_number" name="products[{{$row_index}}][lot_no_line_id]">
                    <option value="">@lang('lang_v1.lot_n_expiry')</option>
                    @foreach($product->lot_numbers as $lot_number)
                        @php
                            $selected = ($lot_number->purchase_line_id == $lot_no_line_id) ? "selected" : "";
                            if($selected == "selected"){
                                $max_qty_rule = $lot_number->qty_available;
                            }

                            $expiry_text = '';
                            if($exp_enabled == 1 && !empty($lot_number->exp_date)){
                                if( \Carbon::now()->gt(\Carbon::createFromFormat('Y-m-d', $lot_number->exp_date)) ){
                                    $expiry_text = '(' . __('report.expired') . ')';
                                }
                            }
                        @endphp
                        <option value="{{$lot_number->purchase_line_id}}" data-qty_available="{{$lot_number->qty_available}}" data-msg-max="@lang('lang_v1.quantity_error_msg_in_lot', ['qty'=> $lot_number->qty_formated, 'unit' => $product->unit])" {{$selected}}>
                            @if(!empty($lot_number->lot_number) && $lot_enabled == 1){{$lot_number->lot_number}} @endif 
                            @if($lot_enabled == 1 && $exp_enabled == 1) - @endif 
                            @if($exp_enabled == 1 && !empty($lot_number->exp_date)) @lang('product.exp_date'): {{@format_date($lot_number->exp_date)}} @endif 
                            {{$expiry_text}}
                        </option>
                    @endforeach
                </select>
            @endif
        @endif
    </td>

    {{-- مخصص 3 --}}
    @if(!empty($p_labels['custom_field_3']))
        <td class="text-center custom-field-3">{{ $cf3 }}</td>
    @endif

    {{-- مخصص 1 --}}
    @if(!empty($p_labels['custom_field_1']))
        <td class="text-center custom-field-1">{{ $cf1 }}</td>
    @endif

    {{-- مخصص 2 --}}
    @if(!empty($p_labels['custom_field_2']))
        <td class="text-center custom-field-2">{{ $cf2 }}</td>
    @endif

    <td>
        @if(!empty($product->transaction_sell_lines_id))
            <input type="hidden" name="products[{{$row_index}}][transaction_sell_lines_id]" 
            value="{{$product->transaction_sell_lines_id}}">
        @endif

        <input type="hidden" name="products[{{$row_index}}][product_id]" class="product_id" value="{{$product->product_id}}">
        <input type="hidden" name="products[{{$row_index}}][variation_id]" class="variation_id" value="{{$product->variation_id}}">
        <input type="hidden" name="products[{{$row_index}}][enable_stock]" class="enable_stock" value="{{$product->enable_stock}}">
        <input type="hidden" class="base_unit_multiplier" name="products[{{$row_index}}][base_unit_multiplier]" value="{{$multiplier}}">
        <input type="hidden" class="hidden_base_unit_price" value="{{$product->unit_price}}">
        <input type="hidden" name="products[{{$row_index}}][product_unit_id]" value="{{$product->unit_id}}">

        <input type="text" 
            class="form-control product_quantity input_number input_quantity" 
            value="{{@format_quantity($qty_ordered)}}" 
            name="products[{{$row_index}}][quantity]" 
            @if($product->unit_allow_decimal == 1) data-decimal=1 @else data-rule-abs_digit="true" data-msg-abs_digit="@lang('lang_v1.decimal_value_not_allowed')" data-decimal=0 @endif
            data-rule-required="true" 
            data-msg-required="@lang('validation.custom-messages.this_field_is_required')" 
            @if($product->enable_stock) 
                data-rule-max-value="{{$max_qty_rule}}" 
                data-msg-max-value="{{$max_qty_msg}}"
                data-qty_available="{{$product->qty_available}}" 
                data-msg_max_default="@lang('validation.custom-messages.quantity_not_available', ['qty'=> $product->formatted_qty_available, 'unit' => $product->unit])" 
            @endif >

        @if(!empty($sub_units))
            <br>
            <select name="products[{{$row_index}}][sub_unit_id]" class="form-control input-sm sub_unit">
                @foreach($sub_units as $key => $value)
                    <option value="{{$key}}" data-multiplier="{{$value['multiplier']}}" data-unit_name="{{$value['name']}}" data-allow_decimal="{{$value['allow_decimal']}}" @if(!empty($product->sub_unit_id) && $product->sub_unit_id == $key) selected @endif>
                        {{$value['name']}}
                    </option>
                @endforeach
            </select>
        @endif
    </td>

    <td class="show_price_with_permission">
        <input type="text" name="products[{{$row_index}}][unit_price]" 
        class="form-control product_unit_price input_number"
         value="{{@num_format($product->unit_price * $multiplier)}}">
    </td>

    <td class="show_price_with_permission">
        <input type="text" readonly name="products[{{$row_index}}][price]" 
        class="form-control product_line_total"
         value="{{@num_format($qty_ordered * $product->unit_price * $multiplier)}}">
    </td>

    <td class="text-center">
        <i class="fa fa-trash remove_product_row cursor-pointer" aria-hidden="true"></i>
    </td>
</tr>