          @php
                $custom_labels = json_decode(session('business.custom_labels'), true);
                $p_labels = $custom_labels['product'] ?? [];
            @endphp

<div class="modal-dialog modal-xl" role="document">
    <div class="modal-content">
        <div class="modal-header no-print">
            <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
            <h4 class="modal-title">@lang('quantity_entry.quantity_entry_details')</h4>
        </div>
        <div class="modal-body" style="padding: 10px;">
           <style>
    .table-bold-border, .table-bold-border th, .table-bold-border td {
        border: 1px solid #000 !important;
        font-size: 12px;        /* تصغير حجم الخط */
        padding: 4px 6px !important;  /* تصغير الـ padding بالجدول */
    }
    #quantity_show_table thead tr th {
        border-bottom: 2px solid #000 !important;
        font-size: 12px;
    }
    .invoice-info {
        margin-bottom: 8px;  /* تقليل المسافة تحت معلومات الفاتورة */
        font-size: 12px;
    }
    .row {
        margin-bottom: 5px;
    }
    @media print {
        .no-print { display: none !important; }
        .table-bold-border { border: 1px solid #000 !important; }
    }
</style>

            <div class="row no-print" style="margin-bottom: 8px; background: #f9f9f9; padding: 6px; border-radius: 5px; border: 1px solid #ddd; font-size: 12px;">
                <div class="col-sm-12">
                    <strong style="margin-right: 15px;">إظهار/إخفاء أعمدة:</strong>
                    <label style="margin-right: 10px; cursor: pointer;"><input type="checkbox" class="toggle-col" data-col="col-sku" checked> SKU</label>
                    <label style="margin-right: 10px; cursor: pointer;"> <input type="checkbox" class="toggle-col" data-col="col-product" checked> المنتج</label>
                    <label style="margin-right: 10px; cursor: pointer;"><input type="checkbox" class="toggle-col" data-col="col-price" checked> السعر</label>
                    <label style="margin-right: 10px; cursor: pointer;"><input type="checkbox" class="toggle-col" data-col="col-subtotal" checked> الإجمالي</label>
                </div>
            </div>

            <div class="row invoice-info">
                <div class="col-sm-4 invoice-col">
                    <b>@lang('purchase.ref_no'):</b> #{{ $quantity_entry->ref_no }}<br/>
                    <b>@lang('messages.date'):</b> {{ @format_datetime($quantity_entry->transaction_date) }}<br/>
                    <b>@lang('business.location'):</b> {{ $quantity_entry->location->name }}
                </div>
                
            </div>

            <div class="row" style="margin-top: 20px;">
                <div class="col-sm-12">
                    <div class="table-responsive">
                        <table class="table table-condensed table-bold-border" id="quantity_show_table">
                            <thead>
                                <tr class="bg-green">
                                    <th>#</th>
                                    <th class="col-sku">SKU</th>
                                    @if(!empty($p_labels['custom_field_3']))
                                        <th class="text-center col-cf3">{{ $p_labels['custom_field_3'] }}</th>
                                    @endif
                                    @if(!empty($p_labels['custom_field_1']))
                                        <th class="text-center col-cf1">{{ $p_labels['custom_field_1'] }}</th>
                                    @endif
                                    @if(!empty($p_labels['custom_field_2']))
                                        <th class="text-center col-cf2">{{ $p_labels['custom_field_2'] }}</th>
                                    @endif
                                    <th class="text-center col-product">@lang('sale.product')</th>
                                    <th class="text-center">@lang('sale.qty')</th>
                                    <th class="text-center col-price @cannot('view_purchase_price') hide @endcan">@lang('lang_v1.cost')</th>
                                    <th class="text-center col-subtotal @cannot('view_purchase_price') hide @endcan">@lang('quantity_entry.total')</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($quantity_entry->purchase_lines as $line)
                                    <tr>
                                        <td>{{ $loop->iteration }}</td>
                                        <td class="col-sku">{{ $line->variations->sub_sku ?? '' }}</td>
                                        @if(!empty($p_labels['custom_field_3']))
                                            <td class="text-center col-cf3">{{ $line->product->product_custom_field3 ?? '-' }}</td>
                                        @endif
                                        @if(!empty($p_labels['custom_field_1']))
                                            <td class="text-center col-cf1">{{ $line->product->product_custom_field1 ?? '-' }}</td>
                                        @endif
                                        @if(!empty($p_labels['custom_field_2']))
                                            <td class="text-center col-cf2">{{ $line->product->product_custom_field2 ?? '-' }}</td>
                                        @endif
                                        <td class="col-product">
                                            {{ $line->product->name }}
                                            @if($line->variations->name != 'DUMMY')
                                                - {{ $line->variations->name }}
                                            @endif
                                        </td>
                                        <td class="text-center">{{ @format_quantity($line->quantity) }}</td>
                                        <td class="text-center col-price @cannot('view_purchase_price') hide @endcan">{{ @num_format($line->purchase_price) }}</td>
                                        <td class="text-center col-subtotal @cannot('view_purchase_price') hide @endcan">{{ @num_format($line->purchase_price * $line->quantity) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

           <div class="row" style="margin-top: 8px;">
    <div class="col-md-6 col-md-offset-6 col-sm-12">
        <table class="table no-border" style="font-size: 11px;">
            <tr>
                <th>@lang('quantity_entry.total_of_quantity'): </th>
                <td>
                    <span class="pull-right">
                        {{ @format_quantity($total_quantity) }}
                    </span>
                </td>
            </tr>
            <tr>
                <th>@lang('quantity_entry.total'): </th>
                <td><span class="display_currency pull-right" data-currency_symbol="true">{{ $quantity_entry->final_total }}</span></td>
            </tr>
        </table>
    </div>
</div>

        <div class="modal-footer">
            <button type="button" class="tw-dw-btn tw-dw-btn-primary tw-text-white no-print" onclick="$(this).closest('div.modal-content').printThis();"><i class="fa fa-print"></i> @lang( 'messages.print' )</button>
            <button type="button" class="tw-dw-btn tw-dw-btn-neutral tw-text-white no-print" data-dismiss="modal">@lang( 'messages.close' )</button>
        </div>
    </div>
</div>

<script>
    $(document).ready(function(){
        $('.toggle-col').off('change').on('change', function() {
            var colClass = $(this).data('col');
            if($(this).is(':checked')) {
                $('.' + colClass).show();
            } else {
                $('.' + colClass).hide();
            }
        });
    });
</script>