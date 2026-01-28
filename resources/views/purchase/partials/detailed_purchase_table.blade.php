<div class="table-responsive">
    <table class="table table-bordered table-striped" id="detailed_purchase_table" style="width: 100%;">
        <thead>
    <tr>
        <th>@lang('messages.action')</th> <th>@lang('messages.date')</th>
        <th>@lang('purchase.ref_no')</th>
        <th>@lang('purchase.location')</th>
        <th>@lang('purchase.supplier')</th> <th>@lang('sale.product')</th>
        <th>SKU</th>
        <th>الكمية المضافة</th>
        <th>@lang('purchase.purchase_status')</th>
        <th>@lang('purchase.payment_status')</th>
        
        <th>{{ $custom_labels['purchase']['custom_field_1'] ?? '' }}</th>
        <th>{{ $custom_labels['purchase']['custom_field_2'] ?? '' }}</th>
        <th>{{ $custom_labels['purchase']['custom_field_3'] ?? '' }}</th>
        <th>{{ $custom_labels['purchase']['custom_field_4'] ?? '' }}</th>
        <th>المجموع الإجمالي</th>
        <th>@lang('lang_v1.added_by')</th> 
    </tr>
</thead>
      <tfoot>
    <tr class="bg-gray font-17 text-center footer-total">
        <td colspan="7"><strong>@lang('sale.total'):</strong></td> 
        
        <td id="footer_total_qty"></td> 
        
        <td></td> 
        <td></td> 
        <td></td> 
        <td></td> 
        <td></td> 
        <td></td> 
        <td></td> 
        
        <td id="footer_line_total"></td> 
    </tr>
</tfoot>
    </table>
</div>