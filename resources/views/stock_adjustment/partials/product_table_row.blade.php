<tr class="product_row" data-variation-id="{{ $product->variation_id ?? ($product->id ?? $product->variation->id ?? null) }}" data-row-index="{{ $row_index }}">
    @php
        // جلب تسميات الحقول المخصصة من الجلسة
        $custom_labels = json_decode(session('business.custom_labels'), true);
        $p_labels = $custom_labels['product'] ?? [];

        /* توحيد منطق جلب البيانات */
        
        // 1. جلب الـ SKU
        $display_sku = $product->sku ?? ($product->sub_sku ?? ($product->variation->sub_sku ?? ''));

        // 2. جلب اسم المنتج
        $display_name = $product->product_name ?? ($product->product->name ?? ($product->name ?? ''));

        // 3. جلب السعر الأصلي (التكلفة)
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

        // 6. جلب الكمية (مع قيمة افتراضية 1)
        $quantity = $quantity ?? 1;

        // 7. جلب معرفات المنتج
        $variation_id = $product->variation_id ?? ($product->id ?? $product->variation->id ?? null);
        $product_id = $product->product_id ?? ($product->product->id ?? $product->id ?? null);

        // 8. حساب رقم السطر (نبدأ من 1)
        $line_number = $row_index + 1;
    @endphp

    {{-- عمود رقم السطر --}}
    <td class="text-center line-number-column" style="width: 50px;">
        <span class="badge badge-secondary line-number">{{ $line_number }}</span>
    </td>
    
     {{-- عمود الـ SKU الموحد --}}
    <td class="text-center sku-column">
        <strong class="text-primary">{{ $display_sku }}</strong>
    </td>
    
     {{-- 5. مخصص 3 (يظهر فقط إذا كان مفعلاً) --}}
    @if(!empty($p_labels['custom_field_3']))
        <td class="text-center custom-field-3">
            {{ $cf3 }}
        </td>
    @endif

    {{-- 1. مخصص 1 (يظهر فقط إذا كان مفعلاً) --}}
    @if(!empty($p_labels['custom_field_1']))
        <td class="text-center custom-field-1">
            {{ $cf1 }}
        </td>
    @endif

    
    {{-- 4. مخصص 2 (يظهر فقط إذا كان مفعلاً) --}}
    @if(!empty($p_labels['custom_field_2']))
        <td class="text-center custom-field-2">
            {{ $cf2 }}
        </td>
    @endif


    {{-- 2. اسم المنتج (الوصف) --}}
   <td class="product-name-column">
        <strong>{{ $display_name }}</strong>
        
        {{-- الحقول المخفية المطلوبة للنظام ولعملية الـ Chunks --}}
        
        {{-- variation_id مع كلاس واضح --}}
        <input type="hidden" class="variation_id" 
               value="{{ $variation_id }}" 
               name="products[{{$row_index}}][variation_id]">
               
        {{-- product_id مع كلاس واضح --}}
        <input type="hidden" class="product_id" 
               name="products[{{$row_index}}][product_id]" 
               value="{{ $product_id }}">
        
        {{-- حقل السعر الأصلي --}}
        <input type="hidden" class="original_purchase_price" value="{{ $original_price }}">
        
        {{-- حقل Lot Number إذا كان موجوداً (للتسويات المتقدمة) --}}
        @if(isset($lot_no_line_id))
            <input type="hidden" class="lot_number" 
                   name="products[{{$row_index}}][lot_no_line_id]" 
                   value="{{ $lot_no_line_id }}">
        @endif
    </td>

    {{-- 3. الكمية --}}
    <td class="quantity-column">
        <div class="form-group mb-0">
            <input type="text" 
                   class="form-control product_quantity input_number" 
                   value="{{ @format_quantity($quantity) }}" 
                   name="products[{{$row_index}}][quantity]"
                   required
                   data-max-qty="{{ $product->qty_available ?? 0 }}"
                   placeholder="الكمية">
            
            {{-- رسالة تحذير عند تجاوز الكمية المتاحة --}}
            <small class="text-danger max-qty-warning" style="display: none;">
                الكمية تتجاوز الرصيد المتوفر: {{ $product->qty_available ?? 0 }}
            </small>
            
            {{-- عرض الكمية المتاحة كمعلومة مساعدة --}}
            @if(isset($product->qty_available) && $product->qty_available > 0)
                <small class="text-muted d-block">
                    المتوفر: {{ @format_quantity($product->qty_available) }}
                </small>
            @endif
        </div>
    </td>


    {{-- 6. السعر --}}
    <td class="price-column">
        <input type="text" 
               name="products[{{$row_index}}][unit_price]" 
               class="form-control product_unit_price input_number" 
               value="{{ @num_format($display_price) }}"
               placeholder="السعر">
    </td>

    {{-- 7. المجموع (ج) --}}
    <td class="total-column">
        <div class="form-group mb-0">
            <input type="text" 
                   readonly 
                   class="form-control product_line_total" 
                   value="{{ @num_format($quantity * $display_price) }}" 
                   style="font-weight: bold; background-color: #f8f9fa;">
        </div>
    </td>

    {{-- 8. حذف السطر --}}
    <td class="text-center actions-column">
        <button type="button" class="btn btn-danger btn-xs remove_product_row" 
                title="حذف المنتج" data-toggle="tooltip">
            <i class="fa fa-trash"></i>
        </button>
    </td>
</tr>