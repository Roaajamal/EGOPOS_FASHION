

<style>
    .flex-box { display: flex; justify-content: space-between; align-items: center; width: 100%; margin: 2px 0; }
    .text-right { text-align: right; }
    .text-left { text-align: left; }
    .text-center { text-align: center; }
    .width-100 { width: 100%; }
    .width-50 { width: 50%; }
    .sub-headings { font-weight: bold; font-size: 14px; }
    .border-bottom { border-bottom: 1px solid #000; }
    .centered { text-align: center; margin: 10px 0; }
    .mt-5 { margin-top: 5px; }
</style>

<div style="width: 100%; font-family: 'DejaVu Sans', sans-serif; direction: rtl; color: #000; padding: 5px;">
    
    {{-- 1. الترويسة (Header) --}}
    <center>
       @if(!empty($receipt_details->logo))
        <div style="text-align: center; width: 100%; margin-bottom: 10px;">
            <img style="max-height: 100px; width: auto; display: block; margin: 0 auto;" src="{{$receipt_details->logo}}">
        </div>
    @endif

       @if(!empty($receipt_details->header_text))
        <div style="font-size: 16px; font-weight: bold;">{!! $receipt_details->header_text !!}</div>
    @endif

         @if(!empty($receipt_details->display_name))
                <div class="headings" style="font-size: 18px; font-weight: bold; margin-bottom: 5px;">
                    {{$receipt_details->display_name}}
                </div>
            @endif

        <div style="font-size: 13px; margin-bottom: 5px;">
            {!! $receipt_details->address !!}
            @if(!empty($receipt_details->contact)) <br> هاتف: {{ $receipt_details->contact }} @endif
            @if(!empty($receipt_details->tax_info1)) <br> <b>{{ $receipt_details->tax_label1 }}</b> {{ $receipt_details->tax_info1 }} @endif
        </div>

      @if(!empty($layout->invoice_heading))
                <div style="margin-top: 10px;">
                    <span class="sub-headings" style="font-weight: bold; border-bottom: 1px solid #ddd;">{!! $layout->invoice_heading !!}</span>
                </div>
            @endif
    </center>

    {{-- 2. معلومات الفاتورة والعميل (تم التعديل لتوزيع الجهات) --}}
    <div style="width: 100%; border-top: 1px solid #000; padding-top: 5px; font-size: 13px;">
        <table style="width: 100%; border-collapse: collapse;">
            <tr>
                {{-- جهة اليمين: العميل --}}
                <td style="vertical-align: top; text-align: right; width: 50%;">
                    @if(!empty($receipt_details->customer_name) || !empty($receipt_details->contact_name))
                        <div style="margin-bottom: 3px;">
                            <strong>{{ $receipt_details->customer_label ?? 'العميل' }}:</strong> {{ $receipt_details->customer_name ?? $receipt_details->contact_name }}
                            @if(!empty($receipt_details->customer_info)) <br>{!! $receipt_details->customer_info !!} @endif
                        </div>
                    @endif
                </td>
                {{-- جهة اليسار: تفاصيل المرتجع --}}
                <td style="vertical-align: top; text-align: left; width: 50%;">
                    <strong>رقم المرتجع:</strong> {{$receipt_details->invoice_no}}<br>
                    <strong>التاريخ:</strong> {{$receipt_details->invoice_date}}
                    @if(!empty($receipt_details->sale_orders_invoice_no))
                        <br><strong>@lang('restaurant.order_no'):</strong> {{$receipt_details->sale_orders_invoice_no}}
                    @endif
                    @if(!empty($layout->table))
                        <br><strong>{{ $layout->table_label }}:</strong> {{ $layout->table }}
                    @endif
                    @if(!empty($layout->service_staff))
                        <br><strong>{{ $layout->service_staff_label }}:</strong> {{ $layout->service_staff }}
                    @endif
                </td>
            </tr>
        </table>
    </div>

    {{-- 3. جدول المنتجات الإضافات --}}
    <table style="width: 100%; margin-top: 10px; border-collapse: collapse; font-size: 12px;">
        <thead>
            <tr style="border-top: 1px solid #000; border-bottom: 1px solid #000; background-color: #f2f2f2;">
                <th style="padding: 5px; text-align: right;">#</th>
                <th style="text-align: right;">{{$receipt_details->table_product_label}}</th>
                <th style="text-align: center;">{{$receipt_details->table_qty_label}}</th>
                @if(empty($receipt_details->hide_price))
                    <th style="text-align: left;">{{$receipt_details->table_subtotal_label}}</th>
                @endif
            </tr>
        </thead>
        <tbody>
            @foreach($receipt_details->lines as $line)
                <tr style="border-bottom: 1px solid #eee;">
                    <td style="padding: 5px; vertical-align: top;">{{$loop->iteration}}</td>
                    <td>
                        {{$line['name']}} {{$line['variation']}}
                        @if(!empty($line['sub_sku'])), {{$line['sub_sku']}} @endif
                    </td>
                    <td style="text-align: center;">{{$line['quantity']}} {{$line['units']}}</td>
                    @if(empty($layout->hide_price))
                        <td style="text-align: left;">{{$line['line_total']}}</td>
                    @endif
                </tr>
                @if(!empty($line['modifiers']))
                    @foreach($line['modifiers'] as $modifier)
                        <tr style="font-size: 11px; color: #666;">
                            <td></td>
                            <td>
                                {{$modifier['name']}} {{$modifier['variation']}}
                                @if(!empty($modifier['sub_sku'])), {{$modifier['sub_sku']}} @endif
                                @if(!empty($modifier['sell_line_note'])) ({!!$modifier['sell_line_note']!!}) @endif
                            </td>
                            <td style="text-align: center;">{{$modifier['quantity']}} {{$modifier['units']}}</td>
                            @if(empty($layout->hide_price))
                                <td style="text-align: left;">{{$modifier['line_total']}}</td>
                            @endif
                        </tr>
                    @endforeach
                @endif
            @endforeach
        </tbody>
    </table>

    {{-- 4. قسم الإجماليات والضرائب (إصلاح تكرار المجموع) --}}
    <div style="margin-top: 10px; border-top: 1px solid #000; padding-top: 5px;">
        @if(empty($layout->hide_price))
            {{-- تم دمج التسمية والقيمة في سطر واحد لمنع التكرار الظاهر بالصورة --}}
            <div class="flex-box">
               
                <p class="sub-headings">{{ $receipt_details->subtotal ?? '' }}</p>
            </div>

            @if(!empty($layout->additional_expenses))
                @foreach($layout->additional_expenses as $key => $val)
                    <div class="flex-box">
                        <p>{{$key}}:</p>
                        <p>(+) {{$val}}</p>
                    </div>
                @endforeach
            @endif

            @if(!empty($layout->tax))
                <div class="flex-box">
                    <p>{!! $layout->tax_label !!}</p>
                    <p>(+) {{$layout->tax}}</p>
                </div>
            @endif

            <div class="flex-box" style="border-top: 1px solid #000; margin-top: 5px;">
                <p class="sub-headings">{!! $receipt_details->total_label !!}</p>
                <p class="sub-headings">{{$receipt_details->total}}</p>
            </div>
            
            @if(!empty($layout->tax_summary_label) && !empty($layout->taxes))
                <div style="margin-top: 10px; border: 1px solid #eee; padding: 5px;">
                    <div class="text-center" style="font-weight: bold; border-bottom: 1px solid #eee; margin-bottom: 5px;">{{$layout->tax_summary_label}}</div>
                    @foreach($layout->taxes as $key => $val)
                        <div class="flex-box" style="font-size: 11px;">
                            <span>{{$key}}</span>
                            <span>{{$val}}</span>
                        </div>
                    @endforeach
                </div>
            @endif

            @if(!empty($layout->payments))
                @foreach($layout->payments as $payment)
                    <div class="flex-box" style="font-size: 11px; color: #444;">
                        <p>{{$payment['method']}} ({{$payment['date']}})</p>
                        <p>{{$payment['amount']}}</p>
                    </div>
                @endforeach
            @endif
        @endif
    </div>

    {{-- 5. الملاحظات والباركود --}}
    <div class="centered">
        @if(!empty($layout->additional_notes))
            <p style="font-size: 12px; margin-top: 10px;">
                {!! nl2br($receipt_details->additional_notes) !!}
            </p>
        @endif

        @if($receipt_details->show_barcode)
            <div style="margin-top: 10px;">
                <img style="max-width: 150px;" src="data:image/png;base64,{{DNS1D::getBarcodePNG($receipt_details->invoice_no, 'C128', 2,30,array(39, 48, 54), true)}}">
            </div>
        @endif

        @if($receipt_details->show_qr_code && !empty($layout->qr_code_text))
            <div class="mt-5">
                <img style="max-width: 100px;" src="data:image/png;base64,{{DNS2D::getBarcodePNG($receipt_details->qr_code_text, 'QRCODE')}}">
            </div>
        @endif
        
        @if(!empty($layout->footer_text))
            <div style="font-size: 11px; margin-top: 10px; border-top: 1px dashed #eee; padding-top: 5px;">
                {!! $layout->footer_text !!}
            </div>
        @endif

        {{-- حقول زاتكا QR API المضافة --}}
        @if(!empty($receipt_details->show_qr_code) && !empty($receipt_details->qr_code_text))
            <div style="text-align: center; margin-top: 20px;">
                <img src="https://api.qrserver.com/v1/create-qr-code/?size=120x120&data={{ urlencode($receipt_details->qr_code_text) }}" alt="QR Code">
            </div>
        @endif
    </div>
</div>