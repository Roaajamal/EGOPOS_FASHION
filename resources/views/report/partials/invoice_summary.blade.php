@if(isset($summaryData))
<div class="box box-info">
    <div class="box-header with-border">
        <h3 class="box-title">تقرير مجمّل</h3>
        <div class="box-tools pull-right">
            <span class="label label-info">
                {{ request('start_date') }} إلى {{ request('end_date') }}
            </span>
        </div>
    </div>
    <div class="box-body">
        {{-- جدول البيانات --}}
        <div class="table-responsive">
            <table class="table table-bordered table-striped table-report">
                <thead>
                    <tr class="bg-gray">
                        <th>رقم الفاتورة</th>
                        <th>التاريخ</th>
                        <th>الفرع</th>
                        <th>العميل</th>
                        <th>الإجمالي</th>
                        <th>قبل الضريبة</th>
                        <th>الضريبة</th>
                        <th>نوع العملية</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($summaryData['sales'] as $sale)
                    <tr>
                        <td>{{ $sale->invoice_no }}</td>
                        <td>{{ \Carbon\Carbon::parse($sale->transaction_date)->format('Y-m-d H:i') }}</td>
                        <td>{{ $sale->branch_name }}</td>
                        <td>
                            {{ $sale->customer_name }}
                            @if($sale->customer_mobile)
                                <br><small>{{ $sale->customer_mobile }}</small>
                            @endif
                        </td>
                        <td class="positive">{{ number_format($sale->final_total, 2) }}</td>
                        <td>{{ number_format($sale->total_before_tax, 2) }}</td>
                        <td class="text-info">{{ number_format($sale->tax_amount, 2) }}</td>
                        <td>
                            @if($sale->payment_status == 'paid')
                                <span class="label label-success">بيع - مدفوع</span>
                            @elseif($sale->payment_status == 'due')
                                <span class="label label-danger">بيع - مستحق</span>
                            @else
                                <span class="label label-warning">بيع - جزئي</span>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                    
                    @foreach($summaryData['returns'] as $return)
                    <tr>
                        <td>{{ $return->invoice_no }}</td>
                        <td>{{ \Carbon\Carbon::parse($return->transaction_date)->format('Y-m-d H:i') }}</td>
                        <td>{{ $return->branch_name }}</td>
                        <td>
                            {{ $return->customer_name }}
                            @if($return->customer_mobile)
                                <br><small>{{ $return->customer_mobile }}</small>
                            @endif
                        </td>
                        <td class="negative">-{{ number_format($return->final_total, 2) }}</td>
                        <td>-{{ number_format($return->total_before_tax, 2) }}</td>
                        <td class="text-danger">-{{ number_format($return->tax_amount, 2) }}</td>
                        <td>
                            <span class="label label-danger">مرتجع</span>
                            @if(isset($return->parent_invoice))
                                <br><small>للأصل: {{ $return->parent_invoice }}</small>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
                <tfoot class="bg-gray">
                    <tr>
                        <th colspan="4" class="text-right">المجموع:</th>
                        <th class="{{ $summaryData['totals']['net_total'] >= 0 ? 'positive' : 'negative' }}">
                            {{ number_format($summaryData['totals']['net_total'], 2) }}
                        </th>
                        <th>
                            {{ number_format($summaryData['totals']['total_sales'] - $summaryData['totals']['total_returns'] - $summaryData['totals']['total_tax'], 2) }}
                        </th>
                        <th>{{ number_format($summaryData['totals']['total_tax'], 2) }}</th>
                        <th></th>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>
@endif