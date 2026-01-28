<div class="modal-dialog modal-xl no-print" role="document">
  <div class="modal-content">
    <div class="modal-header">
    <button type="button" class="close no-print" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
    <h4 class="modal-title" id="modalTitle"> @lang('lang_v1.sell_return') (<b>@lang('sale.invoice_no'):</b> {{ $sell->return_parent->invoice_no }})
    </h4>
</div>
<div class="modal-body">
   <div class="row">
      <div class="col-sm-6 col-xs-6">
        <h4>@lang('lang_v1.sell_return_details'):</h4>
        <strong>@lang('lang_v1.return_date'):</strong> {{@format_date($sell->return_parent->transaction_date)}}<br>
        <strong>@lang('contact.customer'):</strong> {{ $sell->contact->name }} <br>
        <strong>@lang('purchase.business_location'):</strong> {{ $sell->location->name }}
      </div>
      <div class="col-sm-6 col-xs-6">
        <h4>@lang('lang_v1.sell_details'):</h4>
        <strong>@lang('sale.invoice_no'):</strong> {{ $sell->invoice_no }} <br>
        <strong>@lang('messages.date'):</strong> {{@format_date($sell->transaction_date)}}
      </div>
    </div>
    <br>
    <div class="row">
      <div class="col-sm-12">
        <br>
        <table class="table bg-gray">
          <thead>
            <tr class="bg-green">
                <th>#</th>
                <th>@lang('product.product_name')</th>
                <th>@lang('sale.unit_price')</th>
                <th>@lang('lang_v1.return_quantity')</th>
                <th>@lang('lang_v1.return_subtotal')</th>
            </tr>
        </thead>
        <tbody>
            @php
              $total_before_tax = 0;
            @endphp
            @foreach($sell->sell_lines as $sell_line)

            @if($sell_line->quantity_returned == 0)
                @continue
            @endif

            @php
              $unit_name = $sell_line->product->unit->short_name;

              if(!empty($sell_line->sub_unit)) {
                $unit_name = $sell_line->sub_unit->short_name;
              }
            @endphp

            <tr>
                <td>{{ $loop->iteration }}</td>
                <td>
                  {{ $sell_line->product->name }}
                  @if( $sell_line->product->type == 'variable')
                    - {{ $sell_line->variations->product_variation->name}}
                    - {{ $sell_line->variations->name}}
                  @endif
                </td>
                <td><span class="display_currency" data-currency_symbol="true">{{ $sell_line->unit_price_inc_tax }}</span></td>
                <td>{{@format_quantity($sell_line->quantity_returned)}} {{$unit_name}}</td>
                <td>
                  @php
                    $line_total = $sell_line->unit_price_inc_tax * $sell_line->quantity_returned;
                    $total_before_tax += $line_total ;
                  @endphp
                  <span class="display_currency" data-currency_symbol="true">{{$line_total}}</span>
                </td>
            </tr>
            @endforeach
          </tbody>
      </table>
    </div>
  </div>
  <div class="row">
    <div class="col-sm-6 col-sm-offset-6 col-xs-6 col-xs-offset-6">
      <table class="table">
        <tr>
          <th>@lang('purchase.net_total_amount'): </th>
          <td></td>
          <td><span class="display_currency pull-right" data-currency_symbol="true">{{ $total_before_tax }}</span></td>
        </tr>

        <tr>
          <th>@lang('lang_v1.return_discount'): </th>
          <td><b>(-)</b></td>
          <td class="text-right">@if($sell->return_parent->discount_type == 'percentage')
              @<strong><small>{{$sell->return_parent->discount_amount}}%</small></strong> -
              @endif
          <span class="display_currency pull-right" data-currency_symbol="true">{{ $total_discount }}</span></td>
        </tr>
        
        <tr>
          <th>@lang('lang_v1.total_return_tax'):</th>
          <td><b>(+)</b></td>
          <td class="text-right">
              @if(!empty($sell_taxes))
                @foreach($sell_taxes as $k => $v)
                  <strong><small>{{$k}}</small></strong> - <span class="display_currency pull-right" data-currency_symbol="true">{{ $v }}</span><br>
                @endforeach
              @else
              0.00
              @endif
            </td>
        </tr>
        <tr>
          <th>@lang('lang_v1.return_total'):</th>
          <td></td>
          <td><span class="display_currency pull-right" data-currency_symbol="true" >{{ $sell->return_parent->final_total }}</span></td>
        </tr>
      </table>
    </div>
  </div>
  <div class="row">
    <div class="col-md-12">
          <strong>{{ __('repair::lang.activities') }}:</strong><br>
          @includeIf('activity_log.activities', ['activity_type' => 'sell'])
      </div>
  </div>
</div>
<div class="modal-footer">
    {{-- زر إرسال فاتورة المرتجعات لنظام الفوترة الأردني --}}
    <button type="button" class="tw-dw-btn tw-dw-btn-warning tw-text-white send-return-to-fatora" 
            data-return-id="{{$sell->return_parent->id}}" 
            data-original-invoice="{{$sell->invoice_no}}"
            id="send_return_to_fatora_btn_{{$sell->return_parent->id}}">
        <i class="fa fa-paper-plane" aria-hidden="true"></i> إرسال المرتجعات للفوترة الأردنية
    </button>
    <span id="fatora_return_status_{{$sell->return_parent->id}}" style="margin-left: 10px;"></span>

    <a href="#" class="print-invoice tw-dw-btn tw-dw-btn-primary tw-text-white" data-href="{{action([\App\Http\Controllers\SellReturnController::class, 'printInvoice'], [$sell->return_parent->id])}}"><i class="fa fa-print" aria-hidden="true"></i> @lang("messages.print")</a>
      <button type="button" class="tw-dw-btn tw-dw-btn-neutral tw-text-white no-print" data-dismiss="modal">@lang( 'messages.close' )</button>
    </div>
  </div>
</div>

<script type="text/javascript">
  $(document).ready(function(){
    var element = $('div.modal-xl');
    __currency_convert_recursively(element);

    // Check return invoice status on modal open
    var returnId = '{{$sell->return_parent->id}}';
    checkReturnFatoraStatus(returnId);

    // Send return to Fatora button click
    $(document).on('click', '.send-return-to-fatora', function(e){
      e.preventDefault();
      var btn = $(this);
      var returnId = btn.data('return-id');
      var originalInvoice = btn.data('original-invoice');
      
      // Use simple JavaScript confirm
      if (!confirm('هل أنت متأكد من إرسال فاتورة المرتجعات لنظام الفوترة الأردني؟')) {
        return;
      }
      
      // Ask for reason using simple prompt
      var returnReason = prompt('سبب الإرجاع (اختياري):\nمثال: منتج معيب، إلغاء الطلب، إرجاع بناءً على طلب العميل', 'إرجاع بضاعة');
      
      // If user clicked cancel on prompt, stop
      if (returnReason === null) {
        return;
      }
      
      // If empty, use default
      if (!returnReason || returnReason.trim() === '') {
        returnReason = 'إرجاع بضاعة';
      }
      
      btn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> جاري الإرسال...');
      
      $.ajax({
        url: '{{url("/fatora/send-credit-invoice")}}',
        method: 'POST',
        data: {
          _token: '{{csrf_token()}}',
          return_transaction_id: returnId,
          return_reason: returnReason
        },
        dataType: 'json',
        success: function(response) {
          if(response.success) {
            swal('نجح!', response.message + '\n\nالفاتورة الأصلية: ' + originalInvoice + '\nرقم المرتجعات: ' + (response.invoice_number || 'N/A'), 'success');
            checkReturnFatoraStatus(returnId);
          } else {
            swal('خطأ!', response.message || 'فشل إرسال فاتورة المرتجعات', 'error');
          }
          btn.prop('disabled', false).html('<i class="fa fa-paper-plane"></i> إرسال المرتجعات للفوترة الأردنية');
        },
        error: function(xhr) {
          var errorMsg = 'حدث خطأ أثناء إرسال فاتورة المرتجعات';
          if(xhr.responseJSON && xhr.responseJSON.message) {
            errorMsg = xhr.responseJSON.message;
          }
          swal('خطأ!', errorMsg, 'error');
          btn.prop('disabled', false).html('<i class="fa fa-paper-plane"></i> إرسال المرتجعات للفوترة الأردنية');
        }
      });
    });

    // Check Return Fatora status function
    function checkReturnFatoraStatus(returnId) {
      $.ajax({
        url: '{{url("/fatora/invoice-status")}}',
        method: 'GET',
        data: { transaction_id: returnId },
        dataType: 'json',
        success: function(response) {
          if(response.success && response.data) {
            var status = response.data.status;
            var statusHtml = '';
            
            if(status === 'sent' || status === 'accepted') {
              statusHtml = '<span class="badge badge-success"><i class="fa fa-check"></i> تم إرسال المرتجعات</span>';
              $('#send_return_to_fatora_btn_' + returnId).prop('disabled', true).addClass('tw-dw-btn-secondary').removeClass('tw-dw-btn-warning');
            } else if(status === 'rejected') {
              statusHtml = '<span class="badge badge-danger"><i class="fa fa-times"></i> مرفوضة</span>';
            } else if(status === 'pending') {
              statusHtml = '<span class="badge badge-warning"><i class="fa fa-clock-o"></i> قيد الانتظار</span>';
            }
            
            $('#fatora_return_status_' + returnId).html(statusHtml);
          }
        }
      });
    }
  });
</script>