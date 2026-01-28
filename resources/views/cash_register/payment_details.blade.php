<div class="row mini_print">
    <div class="col-sm-12">
        <table class="table table-condensed">
            <tr>
                <th>@lang('lang_v1.payment_method')</th>
                <th>@lang('sale.sale')</th>
            </tr>
            <tr>
                <td>@lang('cash_register.cash_in_hand'):</td>
                <td><span class="display_currency" data-currency_symbol="true">{{ $register_details->cash_in_hand }}</span></td>
                
            </tr>
            <tr>
                <td>@lang('cash_register.cash_payment'):</td> <td><span class="display_currency" data-currency_symbol="true">{{ $register_details->total_cash }}</span></td>
                
            </tr>
            <tr>
                <td>@lang('cash_register.checque_payment'):</td>
                <td><span class="display_currency" data-currency_symbol="true">{{ $register_details->total_cheque }}</span></td>
                
            </tr>
            <tr>
                <td>@lang('cash_register.card_payment'):</td>
                <td><span class="display_currency" data-currency_symbol="true">{{ $register_details->total_card }}</span></td>
                
            </tr>
            <tr>
                <td>@lang('cash_register.bank_transfer'):</td>
                <td><span class="display_currency" data-currency_symbol="true">{{ $register_details->total_bank_transfer }}</span></td>
               
            </tr>
            
            {{-- Custom Payments Loop --}}
            @foreach(['custom_pay_1', 'custom_pay_2', 'custom_pay_3', 'custom_pay_4', 'custom_pay_5', 'custom_pay_6', 'custom_pay_7'] as $pay_type)
                @if(array_key_exists($pay_type, $payment_types))
                <tr>
                    <td>{{$payment_types[$pay_type]}}:</td>
                    <td><span class="display_currency" data-currency_symbol="true">{{ $register_details->{'total_' . $pay_type} }}</span></td>
                </tr>
                @endif
            @endforeach

            <tr>
                <td>@lang('cash_register.other_payments'):</td>
                <td><span class="display_currency" data-currency_symbol="true">{{ $register_details->total_other }}</span></td>
            </tr>
        </table>
        
        <hr>

        {{-- الجداول المالية --}}
        <div class="box box-solid">
            <table class="table table-condensed table-bordered">
                <tr class="info">
                    <th> @lang('cash_register.total_sales') </th>
                    <td><b><span class="display_currency" data-currency_symbol="true">{{ $register_details->total_sales }}</span></b></td>
                </tr>
                <tr class="success">
                    <th> @lang('cash_register.total_final_cash') </th>
                    <td><b><span class="display_currency" data-currency_symbol="true">{{ $register_details->total_final_cash }}</span></b></td>
                </tr>
                <tr class="success">
                    <th> @lang('cash_register.total_final_visa') </th>
                    <td><b><span class="display_currency" data-currency_symbol="true">{{ $register_details->total_final_card }}</span></b></td>
                </tr>
            </table>
        </div>

        <table class="table table-condensed table-bordered">
            <tr class="warning">
                <th> @lang('cash_register.total_due') </th>
                <td><span class="display_currency" data-currency_symbol="true">{{ $register_details->total_due }}</span></td>
            </tr>
            <tr class="danger">
                <th> @lang('cash_register.total_return') </th>
                <td><span class="display_currency" data-currency_symbol="true">{{ $register_details->total_return }}</span></td>
            </tr>
            <tr class="danger">
                <th> @lang('cash_register.total_expense') </th>
                <td><span class="display_currency" data-currency_symbol="true">{{ $register_details->total_expense }}</span></td>
            </tr>
            <tr class="active">
                <th> @lang('cash_register.cash_in_hand') </th>
                <td><span class="display_currency" data-currency_symbol="true">{{ $register_details->cash_in_hand }}</span></td>
            </tr>
        </table>

        <hr>

        <div class="well">
            <div class="row">
                <div class="col-sm-6">
                    <p><b> @lang('cash_register.net_total_sales') </b></p>
                    <h3><span class="display_currency" data-currency_symbol="true">{{ $register_details->net_total_sales }}</span></h3>
                </div>
                <div class="col-sm-6 text-right">
                    <p><b> @lang('cash_register.net_total_cash') </b></p>
                    <h3 class="text-success"><span class="display_currency" data-currency_symbol="true">{{ $register_details->net_total_cash }}</span></h3>
                </div>
            </div>
        </div>
    </div>
</div>
{{-- هذا الشرط يقرأ القيمة من إعدادات نقاط البيع  --}}
@if(!empty($pos_settings['show_product_details_on_close_register']))
    @include('cash_register.register_product_details')
@endif