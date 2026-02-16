<div class="modal-dialog modal-xl" role="document">
    <div class="modal-content">
        <div class="modal-header no-print">
            <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
            <h4 class="modal-title">@lang('quantity_entry.quantity_entry_details')</h4>
        </div>
        <div class="modal-body">
            <style>
                .table-bold-border, .table-bold-border th, .table-bold-border td {
                    border: 1px solid #000 !important;
                }
                #quantity_show_table thead tr th {
                    border-bottom: 2px solid #000 !important;
                }
                @media print {
                    .no-print { display: none !important; }
                    .table-bold-border { border: 1px solid #000 !important; }
                }
            </style>

            <div class="row no-print" style="margin-bottom: 15px; background: #f9f9f9; padding: 10px; border-radius: 5px; border: 1px solid #ddd;">
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
                                    <th class="text-center col-product">@lang('sale.product')</th>
                                    <th class="text-center">@lang('sale.qty')</th>
                                    <th class="text-center col-price @cannot('view_purchase_price') hide @endcan">@lang('lang_v1.cost')</th>
                                    <th class="text-center col-subtotal @cannot('view_purchase_price') hide @endcan">@lang('quantity_entry.total')</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach( $quantity_entry->purchase_lines as $line )
                                  <tr>
        <td>{{ $loop->iteration }}</td>
        <td class="col-sku">{{ $line->variations->sub_sku ?? '' }}</td>
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

            <div class="row">
                <div class="col-md-6 col-md-offset-6 col-sm-12">
                    <table class="table no-border">
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