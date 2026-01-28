@if(isset($detailedData) && !empty($detailedData['detailed_items']))
<div class="box box-info">
    <div class="box-header with-border">
        <h3 class="box-title">تقرير تفصيلي</h3>
        <div class="box-tools pull-right">
            <span class="label label-info">
                {{ request('start_date') }} إلى {{ request('end_date') }}
            </span>
            <span class="label label-primary">
                {{ $detailedData['totals']['items_count'] ?? 0 }} عنصر
            </span>
        </div>
    </div>
    
    <div class="box-body">
        {{-- فلترة إضافية داخل التقرير --}}
        <div class="row" style="margin-bottom: 15px;">
            <div class="col-md-12">
                <div class="pull-left">
                    <button class="btn btn-default btn-sm" onclick="window.print()">
                        <i class="fa fa-print"></i> طباعة
                    </button>
                </div>
                <div class="pull-right">
                    <div class="btn-group">
                        <button type="button" class="btn btn-default btn-sm dropdown-toggle" data-toggle="dropdown">
                            <i class="fa fa-download"></i> تصدير
                            <span class="caret"></span>
                        </button>
                        <ul class="dropdown-menu" role="menu">
                            <li><a href="#"><i class="fa fa-file-excel"></i> Excel</a></li>
                            <li><a href="#"><i class="fa fa-file-pdf"></i> PDF</a></li>
                            <li><a href="#"><i class="fa fa-file-csv"></i> CSV</a></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        {{-- جدول البيانات التفصيلي --}}
        <div class="table-responsive">
            <table class="table table-bordered table-hover table-striped" id="detailedTable">
                <thead>
                    <tr class="bg-gray">
                        <th width="30">#</th>
                        <th width="120">رقم الفاتورة</th>
                        <th width="150">التاريخ والوقت</th>
                        <th width="150">الفرع</th>
                        <th width="200">العميل</th>
                        <th width="250">الصنف</th>
                        <th width="80">الكمية</th>
                        <th width="100">السعر</th>
                        <th width="120">الإجمالي</th>
                        <th width="120">الضريبة</th>
                        <th width="100">الخصم</th>
                        <th width="120">السعر قبل الضريبة</th>
                        <th width="150">نوع العملية</th>
                        <th width="100">حالة الدفع</th>
                        <th width="100">الإجراءات</th>
                    </tr>
                </thead>
                <tbody>
                    @php $counter = 1; @endphp
                    @foreach($detailedData['detailed_items'] as $item)
                    @php
                        $isReturn = isset($item->type) && $item->type == 'sell_return';
                        $rowClass = $isReturn ? 'danger-row' : 'success-row';
                        $quantity = $isReturn ? -($item->quantity ?? 0) : ($item->quantity ?? 0);
                        $lineTotal = $item->line_total ?? 0;
                        $itemTax = $item->item_tax ?? 0;
                        $taxName = $item->tax_name ?? 'بدون ضريبة';
                        $taxRate = $item->tax_rate ?? 0;
                        $discount = $item->line_discount_amount ?? 0;
                        $unitPrice = $item->unit_price ?? 0;
                        $unitPriceIncTax = $item->unit_price_inc_tax ?? 0;
                        
                        // حساب السعر قبل الضريبة
                        $priceBeforeTax = $unitPriceIncTax;
                        if($taxRate > 0) {
                            $priceBeforeTax = $unitPriceIncTax / (1 + ($taxRate / 100));
                        }
                    @endphp
                    
                    <tr class="{{ $rowClass }}" data-id="{{ $item->transaction_id ?? '' }}">
                        <td class="text-center">{{ $counter++ }}</td>
                        
                        {{-- رقم الفاتورة --}}
                        <td>
                            <strong>{{ $item->invoice_no ?? 'غير معروف' }}</strong>
                            @if($isReturn && isset($item->parent_invoice) && $item->parent_invoice)
                            <br>
                            <small class="text-muted">
                                <i class="fa fa-arrow-left"></i> للأصل: {{ $item->parent_invoice }}
                            </small>
                            @endif
                        </td>
                        
                        {{-- التاريخ --}}
                        <td>
                            @if(isset($item->transaction_date))
                                {{ \Carbon\Carbon::parse($item->transaction_date)->format('Y-m-d') }}
                                <br>
                                <small class="text-muted">
                                    {{ \Carbon\Carbon::parse($item->transaction_date)->format('h:i A') }}
                                </small>
                            @else
                                -
                            @endif
                        </td>
                        
                        {{-- الفرع --}}
                        <td>{{ $item->branch_name ?? 'غير معروف' }}</td>
                        
                        {{-- العميل --}}
                        <td>
                            <div class="customer-info">
                                <strong>{{ $item->customer_name ?? 'عميل نقدي' }}</strong>
                                @if(isset($item->customer_mobile) && $item->customer_mobile)
                                <br>
                                <small class="text-primary">
                                    <i class="fa fa-phone"></i> {{ $item->customer_mobile }}
                                </small>
                                @endif
                            </div>
                        </td>
                        
                        {{-- الصنف --}}
                        <td>
                            <div class="product-info">
                                <strong>{{ $item->product_name ?? 'غير معروف' }}</strong>
                                @if(isset($item->variation_name) && $item->variation_name)
                                <br>
                                <small class="text-info">
                                    <i class="fa fa-tag"></i> {{ $item->variation_name }}
                                </small>
                                @endif
                                @if(isset($item->sku) && $item->sku)
                                <br>
                                <small class="text-warning">
                                    <i class="fa fa-barcode"></i> {{ $item->sku }}
                                </small>
                                @endif
                            </div>
                        </td>
                        
                        {{-- الكمية --}}
                        <td class="text-center {{ $isReturn ? 'text-danger' : 'text-success' }}">
                            <strong>{{ number_format($quantity, 2) }}</strong>
                        </td>
                        
                        {{-- السعر --}}
                        <td class="text-right">
                            <div class="price-info">
                                <span class="main-price">{{ number_format($unitPriceIncTax, 2) }}</span>
                                @if($unitPrice > 0)
                                <br>
                                <small class="text-muted">
                                    قبل الضريبة: {{ number_format($unitPrice, 2) }}
                                </small>
                                @endif
                            </div>
                        </td>
                        
                        {{-- الإجمالي --}}
                        <td class="text-right {{ $isReturn ? 'text-danger' : 'text-success' }}">
                            <strong>{{ number_format($lineTotal, 2) }}</strong>
                        </td>
                        
                        {{-- الضريبة --}}
                        <td class="text-right">
                            <div class="tax-info">
                                <span class="{{ $isReturn ? 'text-danger' : 'text-info' }}">
                                    {{ number_format($isReturn ? -$itemTax : $itemTax, 2) }}
                                </span>
                                @if($taxName != 'بدون ضريبة')
                                <br>
                                <small class="text-muted">
                                    {{ $taxName }} ({{ $taxRate }}%)
                                </small>
                                @endif
                            </div>
                        </td>
                        
                        {{-- الخصم --}}
                        <td class="text-right">
                            {{ number_format($discount, 2) }}
                        </td>
                        
                        {{-- السعر قبل الضريبة --}}
                        <td class="text-right">
                            {{ number_format($priceBeforeTax, 2) }}
                        </td>
                        
                        {{-- نوع العملية --}}
                        <td class="text-center">
                            @if($isReturn)
                            <span class="label label-danger">
                                <i class="fa fa-undo"></i> مرتجع
                            </span>
                            @else
                            <span class="label label-success">
                                <i class="fa fa-shopping-cart"></i> بيع
                            </span>
                            @endif
                        </td>
                        
                        {{-- حالة الدفع --}}
                        <td class="text-center">
                            @if(isset($item->payment_status))
                                @if($item->payment_status == 'paid')
                                <span class="label label-success" title="مدفوع بالكامل">
                                    <i class="fa fa-check-circle"></i> مدفوع
                                </span>
                                @elseif($item->payment_status == 'due')
                                <span class="label label-danger" title="غير مدفوع">
                                    <i class="fa fa-exclamation-circle"></i> مستحق
                                </span>
                                @elseif($item->payment_status == 'partial')
                                <span class="label label-warning" title="مدفوع جزئياً">
                                    <i class="fa fa-clock-o"></i> جزئي
                                </span>
                                @else
                                <span class="label label-default">
                                    {{ $item->payment_status }}
                                </span>
                                @endif
                            @else
                                <span class="label label-default">-</span>
                            @endif
                        </td>
                        
                        {{-- الإجراءات --}}
                        <td class="text-center">
                            <div class="btn-group">
                                <button type="button" class="btn btn-xs btn-info view-details" 
                                        data-toggle="tooltip" title="عرض التفاصيل"
                                        data-id="{{ $item->transaction_id ?? '' }}">
                                    <i class="fa fa-eye"></i>
                                </button>
                                <button type="button" class="btn btn-xs btn-default print-item" 
                                        data-toggle="tooltip" title="طباعة"
                                        data-invoice="{{ $item->invoice_no ?? '' }}">
                                    <i class="fa fa-print"></i>
                                </button>
                                @if($isReturn && isset($item->parent_invoice))
                                <button type="button" class="btn btn-xs btn-warning view-original" 
                                        data-toggle="tooltip" title="عرض الفاتورة الأصلية"
                                        data-invoice="{{ $item->parent_invoice }}">
                                    <i class="fa fa-external-link"></i>
                                </button>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
                <tfoot class="bg-gray-dark">
                    <tr>
                        <th colspan="5" class="text-right"><strong>الإجماليات:</strong></th>
                        <th class="text-center {{ $detailedData['totals']['total_quantity'] < 0 ? 'text-danger' : 'text-success' }}">
                            <strong>{{ number_format($detailedData['totals']['total_quantity'], 2) }}</strong>
                        </th>
                        <th class="text-center">-</th>
                        <th class="text-right {{ $detailedData['totals']['total_amount'] < 0 ? 'text-danger' : 'text-success' }}">
                            <strong>{{ number_format($detailedData['totals']['total_amount'], 2) }}</strong>
                        </th>
                        <th class="text-right {{ $detailedData['totals']['total_tax'] < 0 ? 'text-danger' : 'text-success' }}">
                            <strong>{{ number_format($detailedData['totals']['total_tax'], 2) }}</strong>
                        </th>
                        <th class="text-right">
                            <strong>{{ number_format($detailedData['totals']['total_discount'], 2) }}</strong>
                        </th>
                        <th class="text-center">-</th>
                        <th colspan="3" class="text-center">
                            <div class="summary-info">
                                <div class="summary-item">
                                    <span class="badge bg-green">
                                        مبيعات: {{ number_format($detailedData['totals']['total_sales_amount'], 2) }}
                                    </span>
                                </div>
                                <div class="summary-item">
                                    <span class="badge bg-red">
                                        مرتجعات: {{ number_format($detailedData['totals']['total_returns_amount'], 2) }}
                                    </span>
                                </div>
                            </div>
                        </th>
                    </tr>
                </tfoot>
            </table>
        </div>
        
        {{-- ملخص الإحصائيات --}}
        <div class="row" style="margin-top: 20px;">
            <div class="col-md-4">
                <div class="small-box bg-green">
                    <div class="inner">
                        <h3>{{ number_format($detailedData['totals']['total_sales_amount'], 2) }}</h3>
                        <p>إجمالي المبيعات</p>
                    </div>
                    <div class="icon">
                        <i class="fa fa-shopping-cart"></i>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4">
                <div class="small-box bg-red">
                    <div class="inner">
                        <h3>{{ number_format($detailedData['totals']['total_returns_amount'], 2) }}</h3>
                        <p>إجمالي المرتجعات</p>
                    </div>
                    <div class="icon">
                        <i class="fa fa-undo"></i>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4">
                <div class="small-box bg-blue">
                    <div class="inner">
                        <h3>{{ number_format($detailedData['totals']['total_amount'], 2) }}</h3>
                        <p>الصافي</p>
                    </div>
                    <div class="icon">
                        <i class="fa fa-calculator"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="box-footer">
        <div class="row">
            <div class="col-md-6">
                <div class="callout callout-info">
                    <h5><i class="fa fa-info-circle"></i> ملاحظات:</h5>
                    <ul>
                        <li>المرتجعات تظهر باللون الأحمر وقيمها سالبة</li>
                        <li>المبيعات تظهر باللون الأخضر وقيمها موجبة</li>
                        <li>الصافي = المبيعات - المرتجعات</li>
                        <li>يمكنك النقر على أيقونة العين لمشاهدة تفاصيل الفاتورة</li>
                    </ul>
                </div>
            </div>
            <div class="col-md-6 text-right">
                <div class="callout callout-success">
                    <h5><i class="fa fa-calendar"></i> فترة التقرير:</h5>
                    <p>
                        <strong>من:</strong> {{ request('start_date') }}<br>
                        <strong>إلى:</strong> {{ request('end_date') }}
                    </p>
                    <p class="text-muted">
                        تم إنشاء التقرير في: {{ \Carbon\Carbon::now()->format('Y-m-d H:i:s') }}
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- مودال تفاصيل الفاتورة --}}
<div class="modal fade" id="invoiceDetailsModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal">&times;</button>
                <h4 class="modal-title">تفاصيل الفاتورة</h4>
            </div>
            <div class="modal-body" id="invoiceDetailsContent">
                <div class="text-center">
                    <i class="fa fa-spinner fa-spin fa-3x"></i>
                    <p>جاري تحميل التفاصيل...</p>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    // تهيئة أدوات التلميح
    $('[data-toggle="tooltip"]').tooltip();
    
    // عرض تفاصيل الفاتورة
    $('.view-details').click(function() {
        var transactionId = $(this).data('id');
        if (!transactionId) {
            alert('لا يمكن عرض التفاصيل: معرف الفاتورة غير موجود');
            return;
        }
        
        $('#invoiceDetailsModal').modal('show');
        $('#invoiceDetailsContent').html(
            '<div class="text-center">' +
            '<i class="fa fa-spinner fa-spin fa-3x"></i>' +
            '<p>جاري تحميل التفاصيل...</p>' +
            '</div>'
        );
        
        $.ajax({
            url: '/invoice-statement/details/' + transactionId,
            type: 'GET',
            success: function(response) {
                if (response.success) {
                    $('#invoiceDetailsContent').html(response.html);
                } else {
                    $('#invoiceDetailsContent').html(
                        '<div class="alert alert-danger">' +
                        '<i class="fa fa-exclamation-triangle"></i> ' +
                        'حدث خطأ في تحميل التفاصيل' +
                        '</div>'
                    );
                }
            },
            error: function() {
                $('#invoiceDetailsContent').html(
                    '<div class="alert alert-danger">' +
                    '<i class="fa fa-exclamation-triangle"></i> ' +
                    'فشل في تحميل التفاصيل. الرجاء المحاولة مرة أخرى' +
                    '</div>'
                );
            }
        });
    });
    
    // طباعة العنصر
    $('.print-item').click(function() {
        var invoiceNo = $(this).data('invoice');
        if (invoiceNo) {
            window.open('/sells/print/' + invoiceNo, '_blank');
        }
    });
    
    // عرض الفاتورة الأصلية
    $('.view-original').click(function() {
        var parentInvoice = $(this).data('invoice');
        if (parentInvoice) {
            window.location.href = '/sells/' + parentInvoice;
        }
    });
    
    // تلوين الصفوف
    $('.danger-row').hover(
        function() {
            $(this).css('background-color', '#fff5f5');
        },
        function() {
            $(this).css('background-color', '');
        }
    );
    
    $('.success-row').hover(
        function() {
            $(this).css('background-color', '#f5fff5');
        },
        function() {
            $(this).css('background-color', '');
        }
    );
    
    // تنفيذ البحث في الجدول
    $('#detailedTable').DataTable({
        "pageLength": 50,
        "lengthMenu": [[10, 25, 50, 100, -1], [10, 25, 50, 100, "الكل"]],
        "language": {
            "url": "//cdn.datatables.net/plug-ins/1.10.25/i18n/Arabic.json"
        },
        "order": [[2, 'desc']],
        "dom": 'Bfrtip',
        "buttons": [
            'copy', 'excel', 'pdf', 'print'
        ]
    });
});
</script>

<style>
.danger-row {
    background-color: #fff5f5;
}
.success-row {
    background-color: #f5fff5;
}
.customer-info {
    max-width: 200px;
    word-wrap: break-word;
}
.product-info {
    max-width: 250px;
    word-wrap: break-word;
}
.tax-info small {
    font-size: 11px;
}
.price-info .main-price {
    font-weight: bold;
}
.price-info small {
    font-size: 11px;
    color: #666;
}
.summary-info {
    display: flex;
    flex-direction: column;
    gap: 5px;
}
.summary-item {
    display: flex;
    justify-content: center;
}
.badge {
    padding: 5px 10px;
    font-size: 12px;
}
.small-box .icon {
    top: 10px;
}
.callout {
    margin-bottom: 0;
}
.table-report th {
    text-align: center;
    vertical-align: middle;
}
.table-report td {
    vertical-align: middle;
}
.btn-group .btn {
    margin-right: 2px;
}
</style>
@else
<div class="alert alert-warning">
    <i class="fa fa-exclamation-triangle"></i>
    لا توجد بيانات متاحة للعرض. الرجاء تعديل الفلاتر والمحاولة مرة أخرى.
</div>
@endif