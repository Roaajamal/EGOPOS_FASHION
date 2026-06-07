<tr class="product_row" data-variation-id="{{ $variation->id }}" data-row-index="{{ $row_count }}">
    @php
        $custom_labels = json_decode(session('business.custom_labels'), true);
        $p_labels = $custom_labels['product'] ?? [];

        $cf1 = $product->product_custom_field1 ?? '-';
        $cf2 = $product->product_custom_field2 ?? '-';
        $cf3 = $product->product_custom_field3 ?? '-';

        $line_number = $row_count + 1;
        $display_price = $purchase_price ?? ($variation->dpp_inc_tax ?? 0);
        $quantity = $quantity ?? 1;
    @endphp

    {{-- رقم السطر --}}
    <td><span class="sr_number">{{ $line_number }}</span></td>

    {{-- SKU --}}
    <td>{{ $variation->sub_sku }}</td>

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

    {{-- اسم المنتج --}}
    <td>
        {{ $product->name }}
        @if($variation->name != 'DUMMY') - {{ $variation->name }} @endif
        <input type="hidden" name="products[{{ $row_count }}][product_id]"   value="{{ $product->id }}">
        <input type="hidden" class="variation_id" name="products[{{ $row_count }}][variation_id]" value="{{ $variation->id }}">
    </td>

    {{-- الكمية --}}
    <td>
        <input type="text" name="products[{{ $row_count }}][quantity]" 
               value="{{ $quantity }}" 
               class="form-control input-sm quantity" required>
    </td>

    {{-- السعر --}}
    <td>
        <input type="text" name="products[{{ $row_count }}][purchase_price]" 
               value="{{ $display_price }}" 
               class="form-control input-sm purchase_price" required>
    </td>

    {{-- المجموع --}}
    <td>
        <span class="row_total">{{ $quantity * $display_price }}</span>
        <input type="hidden" class="line_total" 
               name="products[{{ $row_count }}][line_total]" 
               value="{{ $quantity * $display_price }}">
    </td>

    {{-- حذف --}}
    <td class="text-center">
        <i class="fa fa-trash remove_row text-danger" style="cursor:pointer;"></i>
    </td>
</tr>