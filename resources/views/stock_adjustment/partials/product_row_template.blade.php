{{-- قالب مخفي لصف المنتج - يستخدم لتحسين أداء الاستيراد من إكسل --}}
<template id="product_row_template">
    <tr class="product_row" data-variation-id="" data-row-index="">
        {{-- رقم السطر --}}
        <td class="text-center line-number-column" style="width: 50px;">
            <span class="badge badge-secondary line-number">1</span>
        </td>

        {{-- SKU --}}
        <td class="text-center sku-column">
            <strong class="text-primary"></strong>
        </td>
        
         {{-- مخصص 3 --}}
        @if(!empty($p_labels['custom_field_3']))
        <td class="text-center custom-field-3">-</td>
        @endif
        
        {{-- مخصص 1 --}}
        @if(!empty($p_labels['custom_field_1']))
        <td class="text-center custom-field-1">-</td>
        @endif

         {{-- مخصص 2 --}}
        @if(!empty($p_labels['custom_field_2']))
        <td class="text-center custom-field-2">-</td>
        @endif

        {{-- اسم المنتج --}}
        <td class="product-name-column">
            <strong></strong>
            <input type="hidden" class="variation_id" name="products[0][variation_id]" value="">
            <input type="hidden" class="product_id" name="products[0][product_id]" value="">
            <input type="hidden" class="original_purchase_price" value="0">
        </td>

        {{-- الكمية --}}
        <td class="quantity-column">
            <div class="form-group mb-0">
                <input type="text" class="form-control product_quantity input_number" 
                       name="products[0][quantity]" value="1" required
                       placeholder="@lang('sale.qty')">
                <small class="text-danger max-qty-warning" style="display: none;"></small>
            </div>
        </td>

       

       

        {{-- السعر --}}
        <td class="price-column">
            <input type="text" name="products[0][unit_price]" 
                   class="form-control product_unit_price input_number" 
                   value="0" placeholder="@lang('lang_v1.cost')">
        </td>

        {{-- المجموع --}}
        <td class="total-column">
            <div class="form-group mb-0">
                <input type="text" readonly class="form-control product_line_total" 
                       value="0.00" style="font-weight: bold; background-color: #f8f9fa;">
                <span class="product_line_total_text d-none">0.00</span>
            </div>
        </td>

        {{-- الإجراءات --}}
        <td class="text-center actions-column">
            <button type="button" class="btn btn-danger btn-xs remove_product_row" 
                    title="@lang('messages.delete')" data-toggle="tooltip">
                <i class="fa fa-trash"></i>
            </button>
        </td>
    </tr>
</template>