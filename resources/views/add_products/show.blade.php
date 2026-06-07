<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>طباعة تفاصيل الاستيراد #{{ $import->id }}</title>
    
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css">
    
    <style>
    * {
        box-sizing: border-box;
    }
    
    body { 
        padding: 10px; 
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        background-color: #fff;
        font-size: 11px;
    }

    .invoice-info {
        margin-bottom: 10px;
        font-size: 11px;
        padding: 8px;
        border: 1px solid #ddd;
        border-radius: 4px;
    }

    .invoice-info b {
        color: #333;
    }

    .table-pdf {
        width: 100%;
        border-collapse: collapse;
        margin-top: 10px;
        font-size: 10px;
    }

    .table-pdf th, .table-pdf td {
        border: 1px solid #ccc !important;
        padding: 4px 5px;
        text-align: center;
        vertical-align: middle;
        word-break: break-word;
        max-width: 80px;
    }

    .table-pdf th {
        background-color: #f2f2f2 !important;
        font-weight: bold;
        font-size: 10px;
        white-space: nowrap;
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
    }

    .label-success { 
        border: 1px solid #5cb85c; 
        color: #5cb85c; 
        padding: 1px 4px; 
        border-radius: 3px;
        font-size: 9px;
        white-space: nowrap;
    }

    .label-warning { 
        border: 1px solid #f0ad4e; 
        color: #f0ad4e; 
        padding: 1px 4px; 
        border-radius: 3px;
        font-size: 9px;
        white-space: nowrap;
    }

    .print-header {
        text-align: center;
        margin-bottom: 10px;
        border-bottom: 2px solid #333;
        padding-bottom: 8px;
    }

    .print-header h4 {
        margin: 0;
        font-size: 14px;
        font-weight: bold;
    }

    .text-center { text-align: center; }

    .btn-area {
        margin-top: 15px;
        text-align: center;
    }

    @media print {
        .no-print { display: none !important; }
        body { padding: 5px; }
        .table-pdf { width: 100%; }
        @page { 
            margin: 0.5cm;
            size: landscape; /* ✅ أفقي لأن الأعمدة كثيرة */
        }
    }
</style>
</head>
<body>

  <div class="row invoice-info" >
    <div  class="col-sm-4 invoice-col">
        <b>@lang('product.import_products'):</b> #{{ $import->id }} <br/>
        <b>@lang('messages.date'):</b> {{ @format_datetime($import->created_at) }}<br/>
        @php
         $locations = is_string($import->locations) 
        ? json_decode($import->locations, true) 
        : ($import->locations ?? []);
                $locationNames = collect($locations)->pluck('name')->join(', ');
            @endphp
            <b>@lang('business.location'):</b> {{ $locationNames ?: 'الفرع غير محدد' }} <br/>
            <b>طبع بواسطة:</b> {{ auth()->user()->user_full_name }} <br/>
            <b>@lang('product.notes'):</b> {{ $import->notes }}
    </div>

    <table class="table-pdf">
        <thead>
            <tr>
                <th>#</th>
                <th>SKU</th>
                <th>{{ __("product.product") }}</th>
                <th>{{ __("product.unit") }}</th>
                <th>{{ __("product.category") }}</th>
                <th>{{ __("product.tax") }}</th>
                <th>{{ __("product.tax_type") }}</th>
                <th>{{ __("product.purchase_price_inc_tax") }}</th>
                <th>{{ __("product.selling_price_inc_tax") }}</th>
                <th>{{ __("product.opening_stock") }}</th>

                {{-- منطق الحقول المخصصة الديناميكي --}}
                @for ($i = 1; $i <= 20; $i++)
                    @if(!empty($p_labels['custom_field_' . $i]))
                        <th>{{ $p_labels['custom_field_' . $i] }}</th>
                    @endif
                @endfor

                <th>{{ __("messages.status") }}</th>
            </tr>
        </thead>
        <tbody>
            @forelse($products as $p)
                @php
                    // تحديد اللون إذا كانت زيادة كمية كما في المودال
                    $is_add_qty = !empty($p['is_add_qty']) ? true : false;
                    $row_style = $is_add_qty ? 'background-color: #fff3cd !important; -webkit-print-color-adjust: exact;' : '';
                @endphp
                <tr style="{{ $row_style }}">
                    <td>{{ $loop->iteration }}</td>
                    <td>{{ $p['sku'] ?? '-' }}</td>
                    <td>{{ $p['name'] ?? '-' }}</td>
                    <td>{{ $p['unit'] ?? '-' }}</td>
                    <td>{{ $p['category'] ?? '-' }}</td>
                    <td>{{ $p['tax'] ?? '-' }}</td>
                    <td>{{ $p['tax_type'] ?? 'inclusive' }}</td>
                    <td>{{ @num_format($p['purchase_price'] ?? 0) }}</td>
                    <td>{{ @num_format($p['selling_price'] ?? 0) }}</td>
                    <td>{{ is_numeric($p['opening_stock'] ?? null) ? @num_format($p['opening_stock']) : ($p['opening_stock'] ?? '-') }}</td>

                    {{-- عرض قيم الحقول المخصصة --}}
                    @for ($j = 1; $j <= 20; $j++)
                        @if(!empty($p_labels['custom_field_' . $j]))
                            <td>{{ $p['custom_field_' . $j] ?? '-' }}</td>
                        @endif
                    @endfor

                    <td>
                        @if($is_add_qty)
                            <span class="label-warning">زيادة كمية</span>
                        @else
                            <span class="label-success">منتج جديد</span>
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="100" class="text-center">{{ __("messages.no_records_found") }}</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="text-center no-print" style="margin-top: 30px;">
        <button onclick="window.print();" class="btn btn-primary">إعادة الطباعة</button>
        <button onclick="window.close();" class="btn btn-default">إغلاق الصفحة</button>
    </div>

    {{-- سكريبت الطباعة التلقائية --}}
    <script type="text/javascript">
        window.onload = function() {
            // تأخير بسيط لضمان تحميل التنسيقات بالكامل
            setTimeout(function() {
                window.print();
            }, 500);
        }

        // إغلاق النافذة تلقائياً بعد انتهاء الطباعة (اختياري)
        window.onafterprint = function() {
            window.close();
        };
    </script>
</body>
</html>