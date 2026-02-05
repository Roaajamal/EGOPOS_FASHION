<tr>
    {{-- رقم السطر --}}
    <td class="sr_number">{{ $row_count + 1 }}</td>

 

    <td>
        {{ $variation->sub_sku }}
    </td>
     
    {{-- اسم المنتج --}}
    <td>
        {{ $product->name }}

        <input type="hidden"
               name="products[{{ $row_count }}][product_id]"
               value="{{ $product->id }}">

        <input type="hidden"
               name="products[{{ $row_count }}][variation_id]"
               class="variation_id"
               value="{{ $variation->id }}">
    </td>

    {{-- الكمية + / - --}}
    <td>
        <div class="input-group input-group-sm" style="max-width: 130px;">
            <span class="input-group-btn">
                <button type="button"
                        class="btn btn-default btn-flat decrement_qty">
                    <i class="fa fa-minus"></i>
                </button>
            </span>

            <input type="number"
                   name="products[{{ $row_count }}][quantity]"
                   class="form-control text-center quantity"
                   value="{{@num_format($quantity ?? 1 )}}"
                   min="1"
                   >

            <span class="input-group-btn">
                <button type="button"
                        class="btn btn-default btn-flat increment_qty">
                    <i class="fa fa-plus"></i>
                </button>
            </span>
        </div>
    </td>

   {{-- سعر الشراء --}}
<td>
    <input type="number" 
           name="products[{{ $row_count }}][purchase_price]"
           {{-- حذفنا كلاس input_number ومنعنا التقريب --}}
           class="form-control input-sm purchase_price" 
          value="{{@num_format($purchase_price)}}"
           {{-- أضفنا step="any" للسماح بجميع الأعشار --}}
           step="any">
</td>

    {{-- الإجمالي --}}
    <td>
        <span class="row_total">0</span>
        <input type="hidden"
               name="products[{{ $row_count }}][line_total]"
               class="line_total">
    </td>

    {{-- حذف --}}
    <td>
        <button type="button"
                class="btn btn-danger btn-xs remove_row"
                title="نقص / حذف">
            <i class="fa fa-trash"></i>
        </button>
    </td>
</tr>
