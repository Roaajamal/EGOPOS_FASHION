<div class="modal-dialog modal-xl" role="document">
  <div class="modal-content">
    <div class="modal-header">
      <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
      <h4 class="modal-title">تفاصيل مبيعات اليوم ({{ @format_date($date) }})</h4>
    </div>
    <div class="modal-body">
      <table class="table table-bordered table-striped">
        <thead>
          <tr class="bg-blue">
            <th>رقم الفاتورة</th>
            <th>العميل</th>
            <th>الوقت</th>
            <th>قبل الضريبة</th>
            <th>الضريبة</th>
            <th>الإجمالي النهائي</th>
            <th>حالة الدفع</th>
          </tr>
        </thead>
        <tbody>
          @foreach($sales as $sale)
          <tr>
            <td>{{ $sale->invoice_no }}</td>
            <td>{{ $sale->customer_name }}</td>
            <td>{{ \Carbon\Carbon::parse($sale->transaction_date)->format('H:i') }}</td>
            <td>{{ @num_format($sale->total_before_tax) }}</td>
            <td>{{ @num_format($sale->total_tax) }}</td>
            <td><strong>{{ @num_format($sale->final_total) }}</strong></td>
            <td>
                <span class="label @if($sale->payment_status == 'paid') label-success @else label-danger @endif">
                    {{ __("lang_v1." . $sale->payment_status) }}
                </span>
            </td>
          </tr>
          @endforeach
        </tbody>
      </table>
    </div>
    <div class="modal-footer">
      <button type="button" class="btn btn-default" data-dismiss="modal">إغلاق</button>
    </div>
  </div>
</div>