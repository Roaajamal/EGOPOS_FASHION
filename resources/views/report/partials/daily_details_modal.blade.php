<div class="modal-dialog modal-xl" role="document">
  <div class="modal-content">
    <div class="modal-header">
      <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
      <h4 class="modal-title">تفاصيل مبيعات اليوم ({{ @format_date($date) }})</h4>
    </div>
    <div class="modal-body">
      <div class="table-responsive">
          <table class="table table-bordered table-striped">
            <thead>
              <tr class="bg-blue">
                <th>رقم الفاتورة</th>
                <th>العميل</th>
                <th>الوقت</th>
                <th>نوع الضريبة</th> <th>قبل الضريبة</th>
                <th>الضريبة</th>
                <th>الإجمالي النهائي</th>
                <th>حالة الدفع</th>
              </tr>
            </thead>
            <tbody>
              @php
                $total_bt = 0;
                $total_t = 0;
                $total_ft = 0;
              @endphp
              @foreach($sales as $sale)
              <tr>
                <td>{{ $sale->invoice_no }}</td>
                <td>{{ $sale->customer_name }}</td>
                <td>{{ \Carbon\Carbon::parse($sale->transaction_date)->format('H:i') }}</td>
                <td><small>{{ $sale->applied_tax_types ?? $sale->tax_name }}</small></td>
                <td>{{ @num_format($sale->line_total_before_tax) }}</td>
                <td>{{ @num_format($sale->line_total_tax) }}</td>
                <td><strong>{{ @num_format($sale->final_total) }}</strong></td>
                <td>
                    <span class="label @if($sale->payment_status == 'paid') label-success @elseif($sale->payment_status == 'due') label-danger @else label-info @endif">
                        {{ __("lang_v1." . $sale->payment_status) }}
                    </span>
                </td>
              </tr>
              @php
                $total_bt += $sale->line_total_before_tax;
                $total_t += $sale->line_total_tax;
                $total_ft += $sale->final_total;
              @endphp
              @endforeach
            </tbody>
            <tfoot>
                <tr class="bg-gray font-17 footer-total">
                    <td colspan="4" class="text-center"><strong>{{__('sales_detailed.total')}}:</strong></td>
                    <td>{{ @num_format($total_bt) }}</td>
                    <td>{{ @num_format($total_t) }}</td>
                    <td>{{ @num_format($total_ft) }}</td>
                    <td></td>
                </tr>
            </tfoot>
          </table>
      </div>
    </div>
    <div class="modal-footer">
      <button type="button" class="btn btn-default" data-dismiss="modal">إغلاق</button>
    </div>
  </div>
</div>