@php
    $custom_labels = json_decode(session('business.custom_labels'), true);
    $p_labels = $custom_labels['product'] ?? [];

    $cf1 = $product->product_custom_field1 ?? '-';
    $cf2 = $product->product_custom_field2 ?? '-';
    $cf3 = $product->product_custom_field3 ?? '-';
@endphp
<tr>
    {{-- 1. رقم السطر --}}
    <td><span class="sr_number"></span></td>

    {{-- 2. SKU --}}
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

    {{-- 3. المنتج والحقول المخفية --}}
    <td>
        <input type="hidden" class="variation_id" name="products[{{$row_count}}][variation_id]" value="{{ $variation->id }}">
        <input type="hidden" class="product_id" name="products[{{$row_count}}][product_id]" value="{{ $product->id }}">
    {{ $product->name }}
    {{-- نعدل الشرط ليتجاهل كلمة DUMMY تماماً ولا يظهرها --}}
    @if(!empty($variation->name) && $variation->name != 'DUMMY' && $product->name != $variation->name)
        - {{ $variation->name }}
    @endif
    
</td>

    {{-- 4. الكمية المدخلة (الجرد الفعلي) --}}
    <td>
        {{-- أضفنا input_number لضمان قبول أرقام فقط وتنسيقها --}}
        <input type="text" name="products[{{$row_count}}][quantity]" value="{{ @num_format(1) }}" class="form-control input-sm quantity text-center input_number" required>
    </td>

    {{-- 5. الكمية الحالية (النظام) --}}
    <td class="text-center">
        {{-- استخدام @num_format لجلب التنسيق المعتمد في إعدادات العمل --}}
        <span class="current_stock_text text-bold">{{ @num_format($current_qty) }}</span>
        <input type="hidden" class="current_stock" name="products[{{$row_count}}][current_stock]" value="{{ $current_qty }}">
    </td>

    {{-- 6. الفرق (يُحسب عبر JS) --}}
    <td class="text-center">
        <span class="stock_diff text-bold">0</span>
    </td>

    {{-- 7. التكلفة (سعر الشراء) --}}
    <td>
        <input type="text" name="products[{{$row_count}}][purchase_price]" value="{{ @num_format($purchase_price) }}" class="form-control input-sm purchase_price text-center input_number" required>
    </td>

    {{-- 8. الإجمالي المالي --}}
    <td class="text-center">
        {{-- استخدام display_currency لعرض الرمز المالي والتنسيق الصحيح --}}
        <span class="row_total display_currency" data-currency_symbol="true">{{ $purchase_price }}</span>
        <input type="hidden" class="line_total" name="products[{{$row_count}}][line_total]" value="{{ $purchase_price }}">
    </td>

    {{-- 9. حذف --}}
    <td class="text-center">
        <i class="fa fa-trash remove_row text-danger" style="cursor:pointer;"></i>
    </td>
</tr>