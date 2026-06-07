<tr>
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
    <td><span class="sr_number"></span></td>
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
 
   <td>
     <input type="hidden" class="variation_id" name="products[{{$row_count}}][variation_id]" value="{{ $variation->id }}">
        <input type="hidden" class="product_id" name="products[{{$row_count}}][product_id]" value="{{ $product->id }}">
    {{ $product->name }}
    {{-- استخدام str_contains للتأكد من عدم وجود الكلمة حتى لو كانت بأحرف صغيرة --}}
    @if(!empty($variation->name) && !str_contains(strtolower($variation->name), 'dummy') && $variation->name != $product->name)
        - {{ $variation->name }}
    @endif
</td>
    <td>
        {{-- استخدام d-inline-flex لضمان بقاء الأرقام واضحة --}}
        <input type="text" name="products[{{$row_count}}][quantity]" 
               value="{{ @num_format($quantity ?? 0) }}" 
               class="form-control input-sm quantity text-center input_number">
    </td>
    <td class="text-center">
        {{-- عرض الكمية الحالية بتنسيق النظام --}}
        <span class="current_stock_text">{{ @num_format($current_qty) }}</span>
        <input type="hidden" class="current_stock" value="{{ $current_qty }}">
    </td>
    <td class="text-center">
        {{-- الفرق سيتم تحديثه بالجافا سكربت --}}
        <span class="stock_diff text-bold">0</span>
    </td>
    <td>
        <input type="text" name="products[{{$row_count}}][purchase_price]" 
               value="{{ @num_format($purchase_price) }}" 
               class="form-control input-sm purchase_price text-center input_number">
    </td>
    <td class="text-center">
        {{-- عرض المجموع مع رمز العملة حسب إعدادات النظام --}}
        <span class="row_total display_currency" data-currency_symbol="true">0</span>
        <input type="hidden" class="line_total" name="products[{{$row_count}}][line_total]">
    </td>
    <td class="text-center">
        <i class="fa fa-trash remove_row text-danger" style="cursor:pointer;"></i>
    </td>
</tr>