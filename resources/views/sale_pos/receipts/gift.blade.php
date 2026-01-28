 @php 
    $design_name = 'gift';
    $bid = $receipt_details->business_id ?? session()->get('user.business_id');
    $layout = \App\InvoiceLayout::where('business_id', $bid)
                                ->where('design', $design_name)
                                ->first();

    $logo_base64 = null;
    if(!empty($layout->logo)) {
        // بناء المسار الفيزيائي للصورة على السيرفر
        $path = public_path('uploads/invoice_logos/' . $layout->logo);
        
        if(file_exists($path)) {
            // تحويل الصورة إلى بيانات مشفرة Base64
            $type = pathinfo($path, PATHINFO_EXTENSION);
            $data = file_get_contents($path);
            $logo_base64 = 'data:image/' . $type . ';base64,' . base64_encode($data);
        }
    }
@endphp

<style>
    .flex-box { display: flex; justify-content: space-between; align-items: center; width: 100%; margin: 2px 0; }
    .text-right { text-align: right; }
    .text-left { text-align: left; }
    .text-center { text-align: center; }
    .width-100 { width: 100%; }
    .width-50 { width: 50%; }
    .sub-headings { font-weight: bold; font-size: 14px; }
    .border-bottom { border-bottom: 1px solid #312e2e; }
    .centered { text-align: center; margin: 10px 0; }
    .mt-5 { margin-top: 5px; }
    .ticket { width: 100%; max-width: 100%; }

    @media print {
        body { margin: 0; padding: 0; }
        .no-print { display: none !important; }
        @page { margin: 0; }
    }
</style>

<div style="width: 100%; font-family: 'DejaVu Sans', sans-serif; direction: rtl; color: #000; padding: 5px;">
    
    {{-- 1. الترويسة (Header) --}}
    <center>
        {{-- عرض اللوجو --}}
       
    {{-- عرض اللوجو بالطريقة الصحيحة --}}
     @if(!empty($logo_base64))
        <div style="text-align: center; width: 100%; margin-bottom: 10px;">
            {{-- هنا الصورة لا تحتاج لرابط، هي موجودة فعلياً داخل الكود --}}
            <img style="max-height: 100px; width: auto; display: block; margin: 0 auto;" 
                 src="{{ $logo_base64 }}" 
                 alt="Logo">
        </div>
    @elseif(!empty($receipt_details->logo))
        {{-- محاولة احتياطية إذا فشل المسار الأول --}}
        <div style="text-align: center; width: 100%; margin-bottom: 10px;">
            <img style="max-height: 100px; width: auto; display: block; margin: 0 auto;" 
                 src="{{ $receipt_details->logo }}">
        </div>
    @endif
        {{-- نصوص الترويسة من الإعدادات التي جلبناها --}}
        @if(!empty($layout->header_text))
            <div style="font-size: 16px; font-weight: bold;">{!! $layout->header_text !!}</div>
        @endif

        

        <div style="font-size: 13px; margin-bottom: 5px;">
            {!! $layout->address ?? '' !!}
            @if(!empty($layout->contact)) <br> هاتف: {{ $layout->contact }} @endif
        </div>

        @if(!empty($receipt_details->invoice_heading))
                <div style="margin-top: 10px;">
                    <span class="sub-headings" style="font-weight: bold; border-bottom: 1px solid #ddd;">{!! $receipt_details->invoice_heading !!}</span>
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
                {{-- جهة اليسار: تفاصيل الفاتورة --}}
                 <td style="vertical-align: top; text-align: left; width: 50%;">
                    <strong>رقم الفاتورة:</strong> {{$receipt_details->invoice_no}}<br>
                    <strong>التاريخ:</strong> {{$receipt_details->invoice_date}}
                    @if(!empty($receipt_details->sale_orders_invoice_no))
                        <br><strong>@lang('restaurant.order_no'):</strong> {{$receipt_details->sale_orders_invoice_no}}
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
                <th style="text-align: right;">{{$layout->table_product_label}}</th>
                <th style="text-align: center;">{{$layout->table_qty_label}}</th>
               
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
                           
                        </tr>
                    @endforeach
                @endif
            @endforeach
        </tbody>
    </table>


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

        @if($layout->show_qr_code && !empty($layout->qr_code_text))
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