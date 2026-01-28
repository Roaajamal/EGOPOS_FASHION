<tr>
    <td><span class="sr_number"></span></td>
    <td>{{ $variation->sub_sku }}</td>
    <td>
        {{ $product->name }}
        @if($variation->name != 'DUMMY') - {{ $variation->name }} @endif
        <input type="hidden" name="products[{{$row_count}}][product_id]" value="{{$product->id}}">
        <input type="hidden" class="variation_id" name="products[{{$row_count}}][variation_id]" value="{{$variation->id}}">
    </td>
    <td>
        <input type="text" name="products[{{$row_count}}][quantity]" value="{{$quantity}}" class="form-control input-sm quantity" required>
    </td>
    <td>
        <input type="text" name="products[{{$row_count}}][purchase_price]" value="{{$purchase_price}}" class="form-control input-sm purchase_price" required>
    </td>
    <td>
        <span class="row_total">{{ $quantity * $purchase_price }}</span>
        <input type="hidden" class="line_total" name="products[{{$row_count}}][line_total]" value="{{ $quantity * $purchase_price }}">
    </td>
    <td class="text-center">
        <i class="fa fa-trash remove_row text-danger" style="cursor:pointer;"></i>
    </td>
</tr>