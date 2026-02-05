<tr class="product_row">
    @php
        // جلب تسميات الحقول المخصصة من الجلسة
        $custom_labels = json_decode(session('business.custom_labels'), true);
        $p_labels = $custom_labels['product'] ?? [];

        /* توحيد منطق جلب البيانات: 
           نبحث عن المسمى الموحد (sku, product_name) أولاً، 
           وإذا لم يوجد (حالة التعديل التقليدية) نبحث في الكائنات الفرعية.
        */
        
        // 1. جلب الـ SKU
        $display_sku = $product->sku ?? ($product->sub_sku ?? ($product->variation->sub_sku ?? ''));

        // 2. جلب اسم المنتج
        $display_name = $product->product_name ?? ($product->product->name ?? ($product->name ?? ''));

        // 3. جلب السعر الأصلي (التكلفة) لعمل حسبة الخصم "خ"
        $original_price = $product->last_purchased_price ?? 
                          ($product->product->default_purchase_price ?? 
                          ($product->default_purchase_price ?? 
                          ($product->variation->default_purchase_price ?? 0)));

        // 4. جلب سعر السند الحالي للسطر
        $display_price = $purchase_price ?? ($unit_price ?? ($product->unit_price ?? $original_price));

        // 5. جلب الحقول المخصصة
        $cf1 = $product->product_custom_field1 ?? ($product->product->product_custom_field1 ?? '-');
        $cf2 = $product->product_custom_field2 ?? ($product->product->product_custom_field2 ?? '-');
        $cf3 = $product->product_custom_field3 ?? ($product->product->product_custom_field3 ?? '-');
    @endphp

    {{-- 1. مخصص 1 (يظهر فقط إذا كان مفعلاً) --}}
    @if(!empty($p_labels['custom_field_1']))
        <td class="text-center">
            {{ $cf1 }}
        </td>
    @endif

    {{-- عمود الـ SKU الموحد --}}
    <td class="text-center">
        {{ $display_sku }}
    </td>

    {{-- 2. اسم المنتج (الوصف) --}}
    <td>
        <strong>{{ $display_name }}</strong>
        
        {{-- الحقول المخفية المطلوبة للكنترولر --}}
        <input type="hidden" class="variation_id" 
               value="{{ $product->variation_id ?? ($product->id ?? $product->variation->id) }}" 
               name="products[{{$row_index}}][variation_id]">
               
        <input type="hidden" name="products[{{$row_index}}][product_id]" 
               value="{{ $product->product_id ?? ($product->product->id ?? $product->id) }}">
        
        {{-- حقل السعر الأصلي - المحرك الأساسي لحسبة الخصم "خ" في الجافا سكريبت --}}
        <input type="hidden" class="original_purchase_price" value="{{ $original_price }}">
    </td>

    {{-- 3. الكمية --}}
    <td>
        <input type="text" class="form-control product_quantity input_number" 
               value="{{ @format_quantity($quantity ?? 1) }}" 
               name="products[{{$row_index}}][quantity]">
    </td>

    {{-- 4. مخصص 2 (يظهر فقط إذا كان مفعلاً) --}}
    @if(!empty($p_labels['custom_field_2']))
        <td class="text-center">
            {{ $cf2 }}
        </td>
    @endif

    {{-- 5. مخصص 3 (يظهر فقط إذا كان مفعلاً) --}}
    @if(!empty($p_labels['custom_field_3']))
        <td class="text-center">
            {{ $cf3 }}
        </td>
    @endif

    {{-- 6. السعر  --}}
    <td>
        <input type="text" name="products[{{$row_index}}][unit_price]" 
               class="form-control product_unit_price input_number" 
               value="{{ @num_format($display_price) }}">
    </td>

    {{-- 7. المجموع (ج) --}}
    <td>
        <input type="text" readonly class="form-control product_line_total" 
               value="{{ @num_format(($quantity ?? 1) * $display_price) }}" style="font-weight: bold;">
    </td>

    {{-- 8. حذف السطر --}}
    <td class="text-center">
        <i class="fa fa-trash remove_product_row text-danger cursor-pointer"></i>
    </td>
</tr>