<div class="modal-dialog modal-xl" role="document">
    <div class="modal-content">
        <div class="modal-body">
            {{-- تنسيق الخطوط السوداء والواضحة للطباعة --}}
            <style>
                .table-bold-border, .table-bold-border th, .table-bold-border td {
                    border: 1px solid #000 !important;
                }
                #adjustment_show_table thead tr th {
                    border-bottom: 2px solid #000 !important;
                }
                @media print {
                    .table-bold-border, .table-bold-border th, .table-bold-border td {
                        border: 1px solid #000 !important;
                        -webkit-print-color-adjust: exact;
                    }
                }
            </style>
            @php
                $business_id = $stock_adjustment->business_id;
                $business = \App\Business::find($business_id);
                $custom_labels = json_decode($business->custom_labels, true);
                $p_labels = $custom_labels['product'] ?? [];
            @endphp 
            
            {{-- فلاتر التحكم بالأعمدة (no-print) --}}
            <div class="row no-print" style="margin-bottom: 15px; background: #f9f9f9; padding: 10px; border-radius: 5px; border: 1px solid #ddd;">
                <div class="col-sm-12">
                    <strong style="margin-right: 15px;">إظهار/إخفاء أعمدة:</strong>
                    <label style="margin-right: 10px; cursor: pointer;"><input type="checkbox" class="toggle-col" data-col="col-img" checked> الصورة</label>
                    
                    {{-- خيار الـ SKU الجديد --}}
                    <label style="margin-right: 10px; cursor: pointer;"><input type="checkbox" class="toggle-col" data-col="col-sku" checked> SKU</label>

                    @if(!empty($p_labels['custom_field1']) || !empty($p_labels['custom_field_1']))
                        <label style="margin-right: 10px; cursor: pointer;"><input type="checkbox" class="toggle-col" data-col="col-cf1" checked> {{ $p_labels['custom_field1'] ?? ($p_labels['custom_field_1'] ?? 'مخصص 1') }}</label>
                    @endif

                    <label style="margin-right: 10px; cursor: pointer;"><input type="checkbox" class="toggle-col" data-col="col-qty" checked> الكمية</label>

                    @if(!empty($p_labels['custom_field2']) || !empty($p_labels['custom_field_2']))
                        <label style="margin-right: 10px; cursor: pointer;"><input type="checkbox" class="toggle-col" data-col="col-cf2" checked> {{ $p_labels['custom_field2'] ?? ($p_labels['custom_field_2'] ?? 'مخصص 2')  }}</label>
                    @endif

                    @if(!empty($p_labels['custom_field3']) || !empty($p_labels['custom_field_3']))
                        <label style="margin-right: 10px; cursor: pointer;"><input type="checkbox" class="toggle-col" data-col="col-cf3" checked> {{ $p_labels['custom_field3'] ?? ($p_labels['custom_field_3'] ?? 'مخصص 3')  }}</label>
                    @endif

                    <label style="margin-right: 10px; cursor: pointer;"><input type="checkbox" class="toggle-col" data-col="col-price" checked> السعر</label>
                    <label style="margin-right: 10px; cursor: pointer;"><input type="checkbox" class="toggle-col" data-col="col-subtotal" checked> الإجمالي</label>
                </div>
            </div>

            <div class="row invoice-info">
                <div class="col-sm-4 invoice-col">
                    @lang('business.business'):
                     <address>
                    <strong>{{ $stock_adjustment->business->name }}</strong>
                  </address>
                </div>
                <div class="col-sm-4 invoice-col">
                    <b>@lang('purchase.ref_no'):</b> #{{ $stock_adjustment->ref_no }}<br/>
                    <b>@lang('messages.date'):</b> {{ @format_date($stock_adjustment->transaction_date) }}<br/>
                    <b>@lang('stock_adjustment.out_type'):</b> {{__('stock_adjustment.' . $stock_adjustment->adjustment_type) }}<br>
                    <b>@lang('stock_adjustment.reason_for_stock_adjustment'):</b> {{ $stock_adjustment->additional_notes }}<br>
                </div>
            </div>

            <div class="row">
                <div class="col-sm-12 col-xs-12">
                    <div class="table-responsive">
                        <table class="table table-condensed table-bold-border" id="adjustment_show_table">
                            <thead>
                                <tr class="bg-green">
                                    <th class="text-center col-img"><i class="fa fa-image"></i></th>
                                    
                                    {{-- عمود الـ SKU في الرأس --}}
                                    <th class="text-center col-sku">SKU</th>

                                    @if(!empty($p_labels['custom_field1']) || !empty($p_labels['custom_field_1']))
                                        <th class="text-center col-cf1">{{ $p_labels['custom_field1'] ?? ($p_labels['custom_field_1'] ?? 'مخصص 1') }}</th>
                                    @endif

                                    <th>@lang('sale.product')</th>
                                    <th class="text-center col-qty">@lang('sale.qty')</th>
                                    
                                    @if(!empty($p_labels['custom_field2']) || !empty($p_labels['custom_field_2']))
                                        <th class="text-center col-cf2">{{$p_labels['custom_field2'] ?? ($p_labels['custom_field_2'] ?? 'مخصص 2')  }}</th>
                                    @endif

                                    @if(!empty($p_labels['custom_field3']) || !empty($p_labels['custom_field_3']))
                                        <th class="text-center col-cf3">{{ $p_labels['custom_field3'] ?? ($p_labels['custom_field_3'] ?? 'مخصص 3')  }}</th>
                                    @endif

                                    @if(!empty($lot_n_exp_enabled))
                                        <th>{{ __('lang_v1.lot_n_expiry') }}</th>
                                    @endif

                                    <th class="text-center col-price @cannot('view_purchase_price') hide @endcan">@lang('lang_v1.cost')</th>
                                    <th class="text-center col-subtotal @cannot('view_purchase_price') hide @endcan">@lang('stock_adjustment.total')</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach( $stock_adjustment->stock_adjustment_lines as $line )
                                    <tr>
                                        <td class="text-center col-img">
                                            <img src="{{ asset(!empty($line->variation->product->image) ? 'uploads/img/' . $line->variation->product->image : 'img/default.png') }}" style="width: 40px; height: 40px;" class="img-thumbnail">
                                        </td>

                                        {{-- عمود الـ SKU في جسم الجدول --}}
                                        <td class="text-center col-sku">
                                            {{ $line->variation->sub_sku ?? '' }}
                                        </td>

                                        @if(!empty($p_labels['custom_field1']) || !empty($p_labels['custom_field_1']))
                                            <td class="text-center col-cf1">{{ $line->variation->product->product_custom_field1 ?? '-' }}</td>
                                        @endif

                                        <td>
                                            {{ $line->variation->product->name }} 
                                            @if($line->variation->name != 'DUMMY')
                                                - {{ $line->variation->name }}
                                            @endif
                                        </td>

                                        <td class="text-center col-qty">{{@format_quantity($line->quantity)}}</td>

                                        @if(!empty($p_labels['custom_field2']) || !empty($p_labels['custom_field_2']))
                                            <td class="text-center col-cf2">{{ $line->variation->product->product_custom_field2 ?? '-' }}</td>
                                        @endif

                                        @if(!empty($p_labels['custom_field3']) || !empty($p_labels['custom_field_3']))
                                            <td class="text-center col-cf3">{{ $line->variation->product->product_custom_field3 ?? '-' }}</td>
                                        @endif

                                        @if(!empty($lot_n_exp_enabled))
                                            <td>{{ $line->lot_details->lot_number ?? '--' }}</td>
                                        @endif

                                        <td class="text-center col-price @cannot('view_purchase_price') hide @endcan">{{@num_format($line->unit_price)}}</td>
                                        <td class="text-center col-subtotal @cannot('view_purchase_price') hide @endcan">{{@num_format($line->unit_price * $line->quantity)}}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        

            <div class="row">
                <div class="col-md-6 col-md-offset-6 col-sm-12 col-xs-12">
                    <div class="table-responsive">
                        <table class="table no-border @cannot('view_purchase_price') show_price_with_permission no-print @endcan">
                            <tr>
                                <th>@lang('stock_adjustment.total_amount'): </th>
                                <td><span class="display_currency pull-right" data-currency_symbol="true">{{ $stock_adjustment->final_total }}</span></td>
                            </tr>
                            <tr>
				            	<th>@lang('stock_adjustment.total_amount_recovered'): </th>
				            	<td><span class="display_currency pull-right" data-currency_symbol="true">{{ $stock_adjustment->total_amount_recovered }}</span></td>
				          	</tr>
                        </table>
                    </div>
                </div>
            </div>
            <div class="row">
              <div class="col-md-12">
                    <strong>{{ __('lang_v1.activities') }}:</strong><br>
                    @includeIf('activity_log.activities')
                </div>
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="tw-dw-btn tw-dw-btn-primary tw-text-white no-print" onclick="$(this).closest('div.modal-content').printThis();"><i class="fa fa-print"></i> @lang( 'messages.print' )</button>
            <button type="button" class="tw-dw-btn tw-dw-btn-neutral tw-text-white no-print" data-dismiss="modal">@lang( 'messages.close' )</button>
        </div>
    </div>

    <script>
        $(document).ready(function(){
            $('.toggle-col').on('change', function() {
                var colClass = $(this).data('col');
                if($(this).is(':checked')) {
                    $('.' + colClass).show();
                } else {
                    $('.' + colClass).hide();
                }
            });
        });
    </script>
</div>