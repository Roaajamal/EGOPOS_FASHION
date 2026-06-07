<!-- business information here -->
<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <meta http-equiv="X-UA-Compatible" content="ie=edge">
        <!-- <link rel="stylesheet" href="style.css"> -->
        <title>Receipt-{{$receipt_details->invoice_no}}</title>
    </head>
    <body>
        <div class="ticket">
    @if(empty($receipt_details->letter_head))
        <div style="text-align: center !important; width: 100%;">
            
            @if(!empty($receipt_details->logo))
                <div style="text-align: center; margin-bottom: 5px;">
                    <img src="{{$receipt_details->logo}}" style="max-height: 80px; width: auto; display: inline-block;" alt="Logo">
                </div>
            @endif

            @if(!empty($receipt_details->header_text))
                <div class="headings" style="font-weight: bold; margin-bottom: 3px;">
                    {!! $receipt_details->header_text !!}
                </div>
            @endif

            @if(!empty($receipt_details->display_name))
                <div class="headings" style="font-size: 18px; font-weight: bold; margin-bottom: 5px;">
                    {{$receipt_details->display_name}}
                </div>
            @endif
            
            <div style="font-size: 13px; line-height: 1.4;">
                @if(!empty($receipt_details->address))
                    {!! $receipt_details->address !!}<br/>
                @endif

                @if(!empty($receipt_details->contact))
                    {!! $receipt_details->contact !!}
                @endif

                @if(!empty($receipt_details->website))
                    , {{ $receipt_details->website }}
                @endif
                
                @if(!empty($receipt_details->location_custom_fields))
                    <br>{{ $receipt_details->location_custom_fields }}
                @endif

                @if(!empty($receipt_details->tax_info1))
                    <br><b>{{ $receipt_details->tax_label1 }}</b> {{ $receipt_details->tax_info1 }}
                @endif
            </div>

            @if(!empty($receipt_details->invoice_heading))
                <div style="margin-top: 10px;">
                    <span class="sub-headings" style="font-weight: bold; border-bottom: 1px solid #ddd;">{!! $receipt_details->invoice_heading !!}</span>
                </div>
            @endif
        </div>
    @endif

    @if(!empty($receipt_details->letter_head))
        <div class="text-box">
            <img style="width: 100%;margin-bottom: 10px;" src="{{$receipt_details->letter_head}}">
        </div>
    @endif
			<div class="invoice-info-small">
				<p class="f-left"><strong>{!! $receipt_details->invoice_no_prefix !!}</strong></p>
				<p class="f-right">
					{{$receipt_details->invoice_no}}
				</p>
			</div>
			<div class="textbox-info">
				<p class="f-left"><strong>{!! $receipt_details->date_label !!}</strong></p>
				<p class="f-right">
					{{$receipt_details->invoice_date}}
				</p>
			</div>
			
			@if(!empty($receipt_details->due_date_label))
				<div class="textbox-info">
					<p class="f-left"><strong>{{$receipt_details->due_date_label}}</strong></p>
					<p class="f-right">{{$receipt_details->due_date ?? ''}}</p>
				</div>
			@endif

			@if(!empty($receipt_details->sales_person_label))
				<div class="textbox-info">
					<p class="f-left"><strong>{{$receipt_details->sales_person_label}}</strong></p>
				
					<p class="f-right">{{$receipt_details->sales_person}}</p>
				</div>
			@endif
			@if(!empty($receipt_details->commission_agent_label))
				<div class="textbox-info">
					<p class="f-left"><strong>{{$receipt_details->commission_agent_label}}</strong></p>
				
					<p class="f-right">{{$receipt_details->commission_agent}}</p>
				</div>
			@endif

			@if(!empty($receipt_details->brand_label) || !empty($receipt_details->repair_brand))
				<div class="textbox-info">
					<p class="f-left"><strong>{{$receipt_details->brand_label}}</strong></p>
				
					<p class="f-right">{{$receipt_details->repair_brand}}</p>
				</div>
			@endif

			@if(!empty($receipt_details->device_label) || !empty($receipt_details->repair_device))
				<div class="textbox-info">
					<p class="f-left"><strong>{{$receipt_details->device_label}}</strong></p>
				
					<p class="f-right">{{$receipt_details->repair_device}}</p>
				</div>
			@endif
			
			@if(!empty($receipt_details->model_no_label) || !empty($receipt_details->repair_model_no))
				<div class="textbox-info">
					<p class="f-left"><strong>{{$receipt_details->model_no_label}}</strong></p>
				
					<p class="f-right">{{$receipt_details->repair_model_no}}</p>
				</div>
			@endif
			
			@if(!empty($receipt_details->serial_no_label) || !empty($receipt_details->repair_serial_no))
				<div class="textbox-info">
					<p class="f-left"><strong>{{$receipt_details->serial_no_label}}</strong></p>
				
					<p class="f-right">{{$receipt_details->repair_serial_no}}</p>
				</div>
			@endif

			@if(!empty($receipt_details->repair_status_label) || !empty($receipt_details->repair_status))
				<div class="textbox-info">
					<p class="f-left"><strong>
						{!! $receipt_details->repair_status_label !!}
					</strong></p>
					<p class="f-right">
						{{$receipt_details->repair_status}}
					</p>
				</div>
        	@endif

        	@if(!empty($receipt_details->repair_warranty_label) || !empty($receipt_details->repair_warranty))
	        	<div class="textbox-info">
	        		<p class="f-left"><strong>
	        			{!! $receipt_details->repair_warranty_label !!}
	        		</strong></p>
	        		<p class="f-right">
	        			{{$receipt_details->repair_warranty}}
	        		</p>
	        	</div>
        	@endif

        	<!-- Waiter info -->
			@if(!empty($receipt_details->service_staff_label) || !empty($receipt_details->service_staff))
	        	<div class="textbox-info">
	        		<p class="f-left"><strong>
	        			{!! $receipt_details->service_staff_label !!}
	        		</strong></p>
	        		<p class="f-right">
	        			{{$receipt_details->service_staff}}
					</p>
	        	</div>
	        @endif

	        @if(!empty($receipt_details->table_label) || !empty($receipt_details->table))
	        	<div class="textbox-info">
	        		<p class="f-left"><strong>
	        			@if(!empty($receipt_details->table_label))
							<b>{!! $receipt_details->table_label !!}</b>
						@endif
	        		</strong></p>
	        		<p class="f-right">
	        			{{$receipt_details->table}}
	        		</p>
	        	</div>
	        @endif

			@if (!empty($receipt_details->sell_custom_field_1_value))
				<div class="textbox-info">
					<p class="f-left"><strong>{!! $receipt_details->sell_custom_field_1_label !!}</strong></p>
					<p class="f-right">
						{{$receipt_details->sell_custom_field_1_value}}
					</p>
				</div>
			@endif
			@if (!empty($receipt_details->sell_custom_field_2_value))
				<div class="textbox-info">
					<p class="f-left"><strong>{!! $receipt_details->sell_custom_field_2_label !!}</strong></p>
					<p class="f-right">
						{{$receipt_details->sell_custom_field_2_value}}
					</p>
				</div>
			@endif
			@if (!empty($receipt_details->sell_custom_field_3_value))
				<div class="textbox-info">
					<p class="f-left"><strong>{!! $receipt_details->sell_custom_field_3_label !!}</strong></p>
					<p class="f-right">
						{{$receipt_details->sell_custom_field_3_value}}
					</p>
				</div>
			@endif
			@if (!empty($receipt_details->sell_custom_field_4_value))
				<div class="textbox-info">
					<p class="f-left"><strong>{!! $receipt_details->sell_custom_field_4_label !!}</strong></p>
					<p class="f-right">
						{{$receipt_details->sell_custom_field_4_value}}
					</p>
				</div>
			@endif

	        <!-- customer info -->
	       <div class="invoice-info-small customer-section" style="margin-top: 5px;">
    @if(!empty($receipt_details->customer_info))
        <div class="flex-row">
            <span class="label">
                <strong>{{$receipt_details->customer_label ?? 'العميل:'}}</strong>
            </span>
            <span class="value">
                {!! $receipt_details->customer_info !!}
            </span>
        </div>
    @endif
</div>
			
			@if(!empty($receipt_details->client_id_label))
				<div class="textbox-info">
					<p class="f-left"><strong>
						{{ $receipt_details->client_id_label }}
					</strong></p>
					<p class="f-right">
						{{ $receipt_details->client_id }}
					</p>
				</div>
			@endif
			
			@if(!empty($receipt_details->customer_tax_label))
				<div class="textbox-info">
					<p class="f-left"><strong>
						{{ $receipt_details->customer_tax_label }}
					</strong></p>
					<p class="f-right">
						{{ $receipt_details->customer_tax_number }}
					</p>
				</div>
			@endif

			@if(!empty($receipt_details->customer_custom_fields))
				<div class="textbox-info">
					<p class="centered">
						{!! $receipt_details->customer_custom_fields !!}
					</p>
				</div>
			@endif
			
			@if(!empty($receipt_details->customer_rp_label))
				<div class="textbox-info">
					<p class="f-left"><strong>
						{{ $receipt_details->customer_rp_label }}
					</strong></p>
					<p class="f-right">
						{{ $receipt_details->customer_total_rp }}
					</p>
				</div>
			@endif
			@if(!empty($receipt_details->shipping_custom_field_1_label))
				<div class="textbox-info">
					<p class="f-left"><strong>
						{!!$receipt_details->shipping_custom_field_1_label!!} 
					</strong></p>
					<p class="f-right">
						{!!$receipt_details->shipping_custom_field_1_value ?? ''!!}
					</p>
				</div>
			@endif
			@if(!empty($receipt_details->shipping_custom_field_2_label))
				<div class="textbox-info">
					<p class="f-left"><strong>
						{!!$receipt_details->shipping_custom_field_2_label!!} 
					</strong></p>
					<p class="f-right">
						{!!$receipt_details->shipping_custom_field_2_value ?? ''!!}
					</p>
				</div>
			@endif
			@if(!empty($receipt_details->shipping_custom_field_3_label))
				<div class="textbox-info">
					<p class="f-left"><strong>
						{!!$receipt_details->shipping_custom_field_3_label!!} 
					</strong></p>
					<p class="f-right">
						{!!$receipt_details->shipping_custom_field_3_value ?? ''!!}
					</p>
				</div>
			@endif
			@if(!empty($receipt_details->shipping_custom_field_4_label))
				<div class="textbox-info">
					<p class="f-left"><strong>
						{!!$receipt_details->shipping_custom_field_4_label!!} 
					</strong></p>
					<p class="f-right">
						{!!$receipt_details->shipping_custom_field_4_value ?? ''!!}
					</p>
				</div>
			@endif
			@if(!empty($receipt_details->shipping_custom_field_5_label))
				<div class="textbox-info">
					<p class="f-left"><strong>
						{!!$receipt_details->shipping_custom_field_5_label!!} 
					</strong></p>
					<p class="f-right">
						{!!$receipt_details->shipping_custom_field_5_value ?? ''!!}
					</p>
				</div>
			@endif
			@if(!empty($receipt_details->sale_orders_invoice_no))
				<div class="textbox-info">
					<p class="f-left"><strong>
						@lang('restaurant.order_no')
					</strong></p>
					<p class="f-right">
						{!!$receipt_details->sale_orders_invoice_no ?? ''!!}
					</p>
				</div>
			@endif

			@if(!empty($receipt_details->sale_orders_invoice_date))
				<div class="textbox-info">
					<p class="f-left"><strong>
						@lang('lang_v1.order_dates')
					</strong></p>
					<p class="f-right">
						{!!$receipt_details->sale_orders_invoice_date ?? ''!!}
					</p>
				</div>
			@endif
            <table style="width: 100%; margin-top: 15px !important; table-layout: fixed; border-collapse: collapse;" class="table-f-12 mb-10">
                <thead class="border-bottom-dotted">
                    <tr>
                        <th class="serial_number">#</th>
                        <th class="description" width="30%">
                        	{{$receipt_details->table_product_label}}
                        </th>
                        <th class="quantity text-right">
                        	{{$receipt_details->table_qty_label}}
                        </th>
                        @if(empty($receipt_details->hide_price))
                        <th class="unit_price text-right">
                        	{{$receipt_details->table_unit_price_label}}
                        </th>
                        @if(!empty($receipt_details->discounted_unit_price_label))
							<th class="text-right">
								{{$receipt_details->discounted_unit_price_label}}
							</th>
						@endif
                        @if(!empty($receipt_details->item_discount_label))
							<th class="text-right">{{$receipt_details->item_discount_label}}</th>
						@endif
                        <th class="price text-right">{{$receipt_details->table_subtotal_label}}</th>
                        @endif
                    </tr>
                </thead>
                <tbody>
                	@forelse($receipt_details->lines as $line)
	                    <tr>
	                        <td class="serial_number" style="vertical-align: top;">
	                        	{{$loop->iteration}}
	                        </td>
	                        <td class="description">
	                        	{{$line['name']}} {{$line['product_variation']}} {{$line['variation']}} 
	                        	@if(!empty($line['sub_sku'])), {{$line['sub_sku']}} @endif @if(!empty($line['brand'])), {{$line['brand']}} @endif @if(!empty($line['cat_code'])), {{$line['cat_code']}}@endif
	                        	@if(!empty($line['product_custom_fields'])), {{$line['product_custom_fields']}} @endif
	                        	@if(!empty($line['product_description']))
	                            	<div class="f-8">
	                            		{!!$line['product_description']!!}
	                            	</div>
	                            @endif
	                        	@if(!empty($line['sell_line_note']))
	                        	<br>
	                        	<span class="f-8">
	                        	{!!$line['sell_line_note']!!}
	                        	</span>
	                        	@endif 
	                        	@if(!empty($line['lot_number']))<br> {{$line['lot_number_label']}}:  {{$line['lot_number']}} @endif 
	                        	@if(!empty($line['product_expiry'])), {{$line['product_expiry_label']}}:  {{$line['product_expiry']}} @endif
	                        	@if(!empty($line['warranty_name']))
	                            	<br>
	                            	<small>
	                            		{{$line['warranty_name']}}
	                            	</small>
	                            @endif
	                            @if(!empty($line['warranty_exp_date']))
	                            	<small>
	                            		- {{@format_date($line['warranty_exp_date'])}}
	                            </small>
	                            @endif
	                            @if(!empty($line['warranty_description']))
	                            	<small> {{$line['warranty_description'] ?? ''}}</small>
	                            @endif

	                            @if($receipt_details->show_base_unit_details && $line['quantity'] && $line['base_unit_multiplier'] !== 1)
		                            <br><small>
		                            	1 {{$line['units']}} = {{$line['base_unit_multiplier']}} {{$line['base_unit_name']}} <br>
                            			{{$line['base_unit_price']}} x {{$line['orig_quantity']}} = {{$line['line_total']}}
		                            </small>
		                            @endif
	                        </td>
	                        <td class="quantity text-right">{{$line['quantity']}} {{$line['units']}} @if($receipt_details->show_base_unit_details && $line['quantity'] && $line['base_unit_multiplier'] !== 1)
                            <br><small>
                            	{{$line['quantity']}} x {{$line['base_unit_multiplier']}} = {{$line['orig_quantity']}} {{$line['base_unit_name']}}
                            </small>
                            @endif</td>
	                        @if(empty($receipt_details->hide_price))
	                        <td class="unit_price text-right">{{$line['unit_price_before_discount']}}</td>

	                        @if(!empty($receipt_details->discounted_unit_price_label))
								<td class="text-right">
									{{$line['unit_price_inc_tax']}} 
								</td>
							@endif

	                        @if(!empty($receipt_details->item_discount_label))
								<td class="text-right">
									{{$line['total_line_discount'] ?? '0.00'}}
									@if(!empty($line['line_discount_percent']))
								 		({{$line['line_discount_percent']}}%)
									@endif
								</td>
							@endif
	                        <td class="price text-right">{{$line['line_total']}}</td>
	                        @endif
	                    </tr>
	                    @if(!empty($line['modifiers']))
							@foreach($line['modifiers'] as $modifier)
								<tr>
									<td>
										&nbsp;
									</td>
									<td>
			                            {{$modifier['name']}} {{$modifier['variation']}} 
			                            @if(!empty($modifier['sub_sku'])), {{$modifier['sub_sku']}} @endif @if(!empty($modifier['cat_code'])), {{$modifier['cat_code']}}@endif
			                            @if(!empty($modifier['sell_line_note']))({!!$modifier['sell_line_note']!!}) @endif 
			                        </td>
									<td class="text-right">{{$modifier['quantity']}} {{$modifier['units']}} </td>
									@if(empty($receipt_details->hide_price))
									<td class="text-right">{{$modifier['unit_price_inc_tax']}}</td>
									@if(!empty($receipt_details->discounted_unit_price_label))
										<td class="text-right">{{$modifier['unit_price_exc_tax']}}</td>
									@endif
									@if(!empty($receipt_details->item_discount_label))
										<td class="text-right">0.00</td>
									@endif
									<td class="text-right">{{$modifier['line_total']}}</td>
									@endif
								</tr>
							@endforeach
						@endif
                    @endforeach
                    <tr>
                    	<td @if(!empty($receipt_details->item_discount_label)) colspan="6" @else colspan="5" @endif>&nbsp;</td>
                    	@if(!empty($receipt_details->discounted_unit_price_label))
    					<td></td>
    					@endif
                    </tr>
                </tbody>
            </table>
			@if(!empty($receipt_details->total_quantity_label))
				<div class="flex-box">
					<p class="left text-right">
						{!! $receipt_details->total_quantity_label !!}
					</p>
					<p class="width-50 text-right">
						{{$receipt_details->total_quantity}}
					</p>
				</div>
			@endif
			@if(!empty($receipt_details->total_items_label))
				<div class="flex-box">
					<p class="left text-right">
						{!! $receipt_details->total_items_label !!}
					</p>
					<p class="width-50 text-right">
						{{$receipt_details->total_items}}
					</p>
				</div>
			@endif
			@if(empty($receipt_details->hide_price))
    <div class="totals-section" style="margin-top: 10px; border-top: 1px solid #000; padding-top: 5px;">

        {{-- المجموع الفرعي --}}
        <div class="flex-row">
            <span class="sub-headings">{!! $receipt_details->subtotal_label !!}</span>
            <span class="sub-headings bold">{{$receipt_details->subtotal}}</span>
        </div>

        {{-- مصاريف الشحن --}}
        @if(!empty($receipt_details->shipping_charges))
            <div class="flex-row">
                <span>{!! $receipt_details->shipping_charges_label !!}</span>
                <span>(+) {{$receipt_details->shipping_charges}}</span>
            </div>
        @endif

        {{-- مصاريف التغليف --}}
        @if(!empty($receipt_details->packing_charge))
            <div class="flex-row">
                <span>{!! $receipt_details->packing_charge_label !!}</span>
                <span>(+) {{$receipt_details->packing_charge}}</span>
            </div>
        @endif

        {{-- الخصومات (الخصم العام + خصم السطور) --}}
        @if(!empty($receipt_details->discount) || !empty($receipt_details->total_line_discount))
            <div class="flex-row">
                <span>{!! $receipt_details->discount_label !!} @if(!empty($receipt_details->total_line_discount)) (إضافي) @endif</span>
                <span class="discount-color">(-) {{ number_format((float)str_replace(',', '', $receipt_details->discount) + (float)str_replace(',', '', $receipt_details->total_line_discount), 2) }}</span>
            </div>
        @endif

        {{-- مصاريف إضافية --}}
        @if(!empty($receipt_details->additional_expenses))
            @foreach($receipt_details->additional_expenses as $key => $val)
                <div class="flex-row">
                    <span>{{$key}}:</span>
                    <span>(+) {{$val}}</span>
                </div>
            @endforeach
        @endif

        {{-- نقاط المكافآت --}}
        @if(!empty($receipt_details->reward_point_label))
            <div class="flex-row">
                <span>{!! $receipt_details->reward_point_label !!}</span>
                <span>(-) {{$receipt_details->reward_point_amount}}</span>
            </div>
        @endif

        {{-- الضريبة --}}
        @if(!empty($receipt_details->tax))
            <div class="flex-row">
                <span>{!! $receipt_details->tax_label !!}</span>
                <span>(+) {{$receipt_details->tax}}</span>
            </div>
        @endif

        {{-- جبر الكسر (Round off) --}}
        @if($receipt_details->round_off_amount > 0)
            <div class="flex-row">
                <span>{!! $receipt_details->round_off_label !!}</span>
                <span>{{$receipt_details->round_off}}</span>
            </div>
        @endif

        {{-- المجموع النهائي --}}
        <div class="flex-row total-line" style="border-top: 1px double #000; border-bottom: 1px double #000; margin: 5px 0; padding: 5px 0;">
            <span class="bold" style="font-size: 1.2em;">{!! $receipt_details->total_label !!}</span>
            <span class="bold" style="font-size: 1.2em;">{{$receipt_details->total}}</span>
        </div>

        {{-- المجموع بالكلمات --}}
        @if(!empty($receipt_details->total_in_words))
            <p style="text-align: center; font-size: 0.8em; margin-bottom: 5px;"><em>{{$receipt_details->total_in_words}}</em></p>
        @endif

        {{-- سجل الدفعات --}}
        @if(!empty($receipt_details->payments))
            @foreach($receipt_details->payments as $payment)
                <div class="flex-row" style="font-size: 0.9em; color: #444;">
                    <span>{{$payment['method']}} ({{$payment['date']}})</span>
                    <span>{{$payment['amount']}}</span>
                </div>
            @endforeach
        @endif

        <hr style="border-top: 1px dashed #ccc; margin: 5px 0;">

        {{-- المدفوع والمتبقي والديون السابقة --}}
        @if(!empty($receipt_details->total_paid))
            <div class="flex-row">
                <span>{!! $receipt_details->total_paid_label !!}</span>
                <span class="bold">{{$receipt_details->total_paid}}</span>
            </div>
        @endif

        @if(!empty($receipt_details->total_due))
            <div class="flex-row due-highlight">
                <span class="bold">{!! $receipt_details->total_due_label !!}</span>
                <span class="bold">{{$receipt_details->total_due}}</span>
            </div>
        @endif

        @if(!empty($receipt_details->total_previous_due))
            <div class="flex-row">
                <span>{!! $receipt_details->total_previous_due_label !!}</span>
                <span>{{$receipt_details->total_previous_due}}</span>
            </div>
        @endif

        @if(!empty($receipt_details->all_due))
            <div class="flex-row all-due-section">
                <span class="bold">{!! $receipt_details->all_bal_label !!}</span>
                <span class="bold" style="font-size: 1.1em; border-bottom: 2px solid #000;">{{$receipt_details->all_due}}</span>
            </div>
        @endif

    </div>
@endif
            <div class="border-bottom width-100">&nbsp;</div>
            @if(empty($receipt_details->hide_price) && !empty($receipt_details->tax_summary_label) )
	            <!-- tax -->
	            @if(!empty($receipt_details->taxes))
	            	<table class="border-bottom width-100 table-f-12">
	            		<tr>
	            			<th colspan="2" class="text-center">{{$receipt_details->tax_summary_label}}</th>
	            		</tr>
	            		@foreach($receipt_details->taxes as $key => $val)
	            			<tr>
	            				<td class="left">{{$key}}</td>
	            				<td class="right">{{$val}}</td>
	            			</tr>
	            		@endforeach
	            	</table>
	            @endif
            @endif

            @if(!empty($receipt_details->additional_notes))
	            <p class="centered">
	            	{!! nl2br($receipt_details->additional_notes) !!}
	            </p>
            @endif

            {{-- Barcode --}}
			@if($receipt_details->show_barcode)
				<br/>
				<img class="center-block" src="data:image/png;base64,{{DNS1D::getBarcodePNG($receipt_details->invoice_no, 'C128', 2,30,array(39, 48, 54), true)}}">
			@endif

			@if($receipt_details->show_qr_code && !empty($receipt_details->qr_code_text))
				<img class="center-block mt-5" src="data:image/png;base64,{{DNS2D::getBarcodePNG($receipt_details->qr_code_text, 'QRCODE')}}">
			@endif
			
			@if(!empty($receipt_details->footer_text))
				<p class="centered">
					{!! $receipt_details->footer_text !!}
				</p>
			@endif
			
        </div>
        <!-- <button id="btnPrint" class="hidden-print">Print</button>
        <script src="script.js"></script> -->
    </body>
</html>

<style type="text/css">
* {
    font-family: 'Arial', sans-serif;
    color: #000;
    line-height: 1.5;
}

@media print {
    @page { margin: 0; }
    body { margin: 0; padding: 0; direction: rtl; } /* إضافة اتجاه اليمين لليسار */
    
    .ticket {
        width: 76mm; /* ترك هامش بسيط للطابعة */
        margin: 0 auto;
        padding: 4mm;
    }

    /* ضبط الجدول لمنع التداخل */
    table {
        width: 100%;
        border-collapse: collapse;
        table-layout: fixed; /* إجبار الأعمدة على احترام العرض */
        margin-top: 6px;
    }

    th, td {
        font-size: 10px; /* تصغير الخط قليلاً لمنع التداخل */
        padding: 3px 0;
        word-wrap: break-word;
        vertical-align: top;
    }

    /* العرض الجديد للأعمدة لتقريب المسافات */
    .col-index { width: 15px; text-align: right; }      /* رقم السطر - مساحة صغيرة جداً */
    .col-desc  { width: auto; text-align: right; }     /* اسم المنتج - يأخذ باقي المساحة */
    .col-qty   { width: 35px; text-align: center; }    /* الكمية */
    .col-price { width: 35px; text-align: left; }      /* الإجمالي النهائي */

    .border-bottom-dotted { border-bottom: 1px dotted #000; }
    .f-8 { font-size: 8px; }
    .centered { text-align: center; }
}

/* تنسيقات العرض خارج الطباعة */
.flex-box { display: flex; justify-content: space-between; font-size: 11px; margin: 2px 0; }
.f-left { float: right; font-weight: bold; } /* عكس الفلوت ليناسب العربي */
.f-right { float: left; }
.textbox-info { clear: both; width: 100%; display: block; margin-bottom: 2px; }

.flex-row {
    display: flex;
    justify-content: space-between; /* هذا ما يجعل النص يميناً والسعر يساراً */
    align-items: center;
    width: 100%;
    margin-bottom: 4px;
}

.bold { font-weight: bold; }

.due-highlight {
    background-color: #f2f2f2;
    padding: 3px;
   
}

.all-due-section {
    margin-top: 5px;
    padding-top: 5px;
    border-top: 1px solid #333;
}

.discount-color { color: #000; } /* يمكنك تغييرها لـ red إذا كانت الطابعة ملونة */

.invoice-info-small {
    font-size: 10px; /* يمكنك تغيير الرقم لـ 10px إذا أردته أصغر أكثر */
    color: #333;
    margin-bottom: 10px;
    line-height: 1.2;
}

.invoice-info-small .flex-row {
    margin-bottom: 3px; /* تقليل المسافة بين السطور */
}

/* لضمان أن العناوين (مثل "التاريخ:") لا تأخذ مساحة كبيرة */
.invoice-info-small span:first-child {
    font-weight: normal;
}

</style>