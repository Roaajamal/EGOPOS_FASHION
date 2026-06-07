 @php
                $custom_labels = json_decode(session('business.custom_labels'), true);
                $p_labels = $custom_labels['product'] ?? [];
            @endphp

<div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">
        <div class="modal-header no-print">
            <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
            <h4 class="modal-title">@lang('quantity_entry.quantity_entry_details')</h4>
        </div>
        <div class="modal-body">
    <style>
        @media print {
            * {
                font-size: 10px !important;
                line-height: 1.2 !important;
            }
            
            .modal-body, .table, .table th, .table td {
                font-size: 10px !important;
                padding: 4px !important;
            }

            html, body {
                height: 99% !important;
                margin: 0 !important;
                padding: 0 !important;
                page-break-after: avoid !important;
                page-break-before: avoid !important;
            }

            @page {
                size: auto;
                margin: 0.5cm !important;
            }

            .no-print, .modal-footer, .modal-header .close, .btn {
                display: none !important;
            }

            .table {
                width: 100% !important;
                border: 1px solid #000 !important;
            }
            
            .bg-gray {
                background-color: #f1f1f1 !important;
                -webkit-print-color-adjust: exact;
            }
        }
    </style>

    <div class="modal-header">
        <button type="button" class="close no-print" data-dismiss="modal">&times;</button>
        <h4 class="modal-title">
            @lang('inventory.inventory_details'): {{ $transaction->ref_no }}
        </h4>
    </div>

    <div class="modal-body" id="inventory_print_area">
        <div class="row" style="margin-bottom: 10px;">
            <div class="col-xs-6">
                <b>@lang('messages.date'):</b> {{ @format_datetime($transaction->transaction_date) }}<br>
                <b>@lang('purchase.location'):</b> {{ $transaction->location->name }}
            </div>
            <div class="col-xs-6 text-left">
                <b>@lang('inventory.type'):</b> 
                @if($transaction->type == 'add_quantity')
                    @lang('inventory.quantity_entry')
                @else
                    @lang('inventory.stock_adjustment')
                @endif
                <br>
                <b>@lang('lang_v1.by'):</b> {{ $transaction->createdBy->user_full_name }}
            </div>
        </div>

        <div class="row" style="margin-top: 20px;">
            <div class="col-sm-12">
                <div class="table-responsive">
                    <table class="table table-bordered table-condensed">
                        <thead>
                            <tr class="bg-gray">
                                <th style="width: 12%;" class="text-center">SKU</th>
                                {{-- ✅ أعمدة جديدة --}}
                                @if(!empty($p_labels['custom_field_3']))
                                        <th class="text-center col-cf3">{{ $p_labels['custom_field_3'] }}</th>
                                    @endif
                                    @if(!empty($p_labels['custom_field_1']))
                                        <th class="text-center col-cf1">{{ $p_labels['custom_field_1'] }}</th>
                                    @endif
                                    @if(!empty($p_labels['custom_field_2']))
                                        <th class="text-center col-cf2">{{ $p_labels['custom_field_2'] }}</th>
                                    @endif
                                <th style="width: 30%;">@lang('sale.product')</th>
                                <th class="text-center">@lang('lang_v1.quantity')</th>
                                <th class="text-center">@lang('sale.unit_price')</th>
                                <th class="text-center">@lang('sale.subtotal')</th>
                            </tr>
                        </thead>
                        <tbody>
                            @php 
                                $lines = $transaction->type == 'add_quantity' 
                                    ? $transaction->purchase_lines 
                                    : $transaction->stock_adjustment_lines;
                                $grand_total = 0;
                            @endphp

                            @foreach($lines as $line)
                                @php 
                                    $price     = $line->purchase_price ?? $line->unit_price;
                                    $row_total = $line->quantity * $price;
                                    $grand_total += $row_total;
                                    $sku = !empty($line->variations->sub_sku) 
                                        ? $line->variations->sub_sku 
                                        : $line->product->sku;
                                @endphp
                                <tr>
                                    <td class="text-center">{{ $sku }}</td>
                                    {{-- ✅ بيانات الأعمدة الجديدة --}}
                                    @if(!empty($p_labels['custom_field_3']))
                                            <td class="text-center col-cf3">{{ $line->product->product_custom_field3 ?? '-' }}</td>
                                        @endif
                                        @if(!empty($p_labels['custom_field_1']))
                                            <td class="text-center col-cf1">{{ $line->product->product_custom_field1 ?? '-' }}</td>
                                        @endif
                                        @if(!empty($p_labels['custom_field_2']))
                                            <td class="text-center col-cf2">{{ $line->product->product_custom_field2 ?? '-' }}</td>
                                        @endif
                                    <td>
                                        {{ $line->product->name }}
                                        @if(!empty($line->variations->name) && $line->variations->name != 'DUMMY') 
                                            - {{ $line->variations->name }} 
                                        @endif
                                    </td>
                                    <td class="text-center">{{ @num_format($line->quantity) }}</td>
                                    <td class="text-center">{{ @num_format($price) }}</td>
                                    <td class="text-center">{{ @num_format($row_total) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr class="bg-gray">
                                <th colspan="7" class="text-right">@lang('sale.total'):</th>
                                <th class="text-center">{{ @num_format($grand_total) }}</th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="modal-footer">
        <button type="button" class="tw-dw-btn tw-dw-btn-primary tw-text-white no-print" 
                onclick="$(this).closest('div.modal-content').printThis();">
            <i class="fa fa-print"></i> @lang('messages.print')
        </button>
        <button type="button" class="tw-dw-btn tw-dw-btn-neutral tw-text-white no-print" 
                data-dismiss="modal">
            @lang('messages.close')
        </button>
    </div>
</div>