@php
    $custom_labels = json_decode(session('business.custom_labels'), true);
    $p_labels = $custom_labels['product'] ?? [];

    $cf1 = $product->product_custom_field1 ?? '-';
    $cf2 = $product->product_custom_field2 ?? '-';
    $cf3 = $product->product_custom_field3 ?? '-';
@endphp

<tr>
    {{-- رقم السطر --}}
    <td class="sr_number">{{ $row_count + 1 }}</td>

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
        <input type="hidden" name="products[{{ $row_count }}][product_id]" value="{{ $product->id }}">
        <input type="hidden" name="products[{{ $row_count }}][variation_id]" class="variation_id" value="{{ $variation->id }}">
    </td>

    {{-- الكمية + / - --}}
    <td>
        <div class="input-group input-group-sm" style="max-width: 130px;">
            <span class="input-group-btn">
                <button type="button" class="btn btn-default btn-flat decrement_qty">
                    <i class="fa fa-minus"></i>
                </button>
            </span>
            <input type="number"
                   name="products[{{ $row_count }}][quantity]"
                   class="form-control text-center quantity"
                   value="{{ $quantity ?? 1 }}"
                   min="1">
            <span class="input-group-btn">
                <button type="button" class="btn btn-default btn-flat increment_qty">
                    <i class="fa fa-plus"></i>
                </button>
            </span>
        </div>
    </td>

    {{-- سعر الشراء --}}
    <td>
        <input type="number"
               name="products[{{ $row_count }}][purchase_price]"
               class="form-control input-sm purchase_price"
               value="{{ $purchase_price }}"
               step="any">
    </td>

    {{-- الإجمالي --}}
    <td>
        <span class="row_total">0</span>
        <input type="hidden" name="products[{{ $row_count }}][line_total]" class="line_total">
    </td>

    {{-- حذف --}}
    <td>
        <button type="button" class="btn btn-danger btn-xs remove_row" title="نقص / حذف">
            <i class="fa fa-trash"></i>
        </button>
    </td>
</tr>