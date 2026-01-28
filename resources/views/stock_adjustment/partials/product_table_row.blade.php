<tr class="product_row">
    <td>
        {{-- عرض اسم المنتج والـ SKU بشكل صحيح --}}
        @php
            $product_name = $product->product_name ?? ($product->name ?? '');
            $sku = $product->sub_sku ?? '';
        @endphp
        
        {{ $product_name }}
        <br/>
        <small class="text-muted">SKU: {{ $sku }}</small>

        @if( session()->get('business.enable_lot_number') == 1 || session()->get('business.enable_product_expiry') == 1)
            @php
                $lot_enabled = session()->get('business.enable_lot_number');
                $exp_enabled = session()->get('business.enable_product_expiry');
                $lot_no_line_id = $product->lot_no_line_id ?? '';
            @endphp

            @if($product->enable_stock == 1)
                <br>
                <small class="text-muted">
                    @lang('report.current_stock'): 
                    <span class="qty_available_text">{{ $product->formatted_qty_available ?? '0' }}</span> 
                    {{ is_object($product->unit) ? ($product->unit->actual_name ?? '') : $product->unit }}
                </small>
            @endif
            
            @if(!empty($product->lot_numbers))
                <select class="form-control lot_number" name="products[{{$row_index}}][lot_no_line_id]">
                    <option value="">@lang('lang_v1.lot_n_expiry')</option>
                    @foreach($product->lot_numbers as $lot_number)
                        <option value="{{$lot_number->purchase_line_id}}" {{$lot_number->purchase_line_id == $lot_no_line_id ? 'selected' : ''}}>
                            {{ $lot_number->lot_number }}
                        </option>
                    @endforeach
                </select>
            @endif
        @endif
    </td>
    <td>
        {{-- الحقول المخفية --}}
        <input type="hidden" name="products[{{$row_index}}][product_id]" value="{{$product->product_id}}">
        <input type="hidden" class="variation_id" value="{{$product->variation_id}}" name="products[{{$row_index}}][variation_id]">
        <input type="hidden" value="{{$product->enable_stock}}" name="products[{{$row_index}}][enable_stock]">
        
        @php
            $qty = !empty($quantity) ? $quantity : (!empty($product->quantity_ordered) ? $product->quantity_ordered : 1);
        @endphp

        <input type="text" class="form-control product_quantity input_number input_quantity" 
            value="{{@format_quantity($qty)}}" 
            name="products[{{$row_index}}][quantity]">
        
        <span class="text-muted">
            {{ is_object($product->unit) ? ($product->unit->short_name ?? '') : $product->unit }}
        </span>
    </td>

    @php
        $unit_price = !empty($purchase_price) ? $purchase_price : ($product->last_purchased_price ?? 0);
    @endphp

    <td class="show_price_with_permission">
        <input type="text" name="products[{{$row_index}}][unit_price]" class="form-control product_unit_price input_number" value="{{@num_format($unit_price)}}">
    </td>
    <td class="show_price_with_permission">
        <input type="text" readonly name="products[{{$row_index}}][price]" class="form-control product_line_total" value="{{@num_format($qty * $unit_price)}}">
    </td>
    <td class="text-center">
        <i class="fa fa-trash remove_product_row cursor-pointer" aria-hidden="true"></i>
    </td>
</tr>