@extends('layouts.app')

@section('title', __('accounting::lang.journal_entry'))

@section('content')

@include('accounting::layouts.nav')

<section class="content-header">
    <h1 class="tw-text-xl md:tw-text-3xl tw-font-bold tw-text-black">@lang( 'accounting::lang.journal_entry' )</h1>
</section>
<section class="content">

{!! Form::open(['url' => action([\Modules\Accounting\Http\Controllers\JournalEntryController::class, 'store']), 
    'method' => 'post', 'id' => 'journal_add_form']) !!}

    @component('components.widget', ['class' => 'box-primary'])

        <div class="row">
            <div class="col-sm-3">
                <div class="form-group">
                    {!! Form::label('ref_no', __('purchase.ref_no').':') !!}
                    @show_tooltip(__('lang_v1.leave_empty_to_autogenerate'))
                    {!! Form::text('ref_no', null, ['class' => 'form-control']); !!}
                </div>
            </div>

            <div class="col-sm-3">
                <div class="form-group">
                    {!! Form::label('journal_date', __('accounting::lang.journal_date') . ':*') !!}
                    <div class="input-group">
                        <span class="input-group-addon">
                            <i class="fa fa-calendar"></i>
                        </span>
                        {!! Form::text('journal_date', @format_datetime('now'), ['class' => 'form-control datetimepicker', 'readonly', 'required']); !!}
                    </div>
                </div>
            </div>

            {{-- تحديد الفرع للقيد بالكامل --}}
            <div class="col-sm-3">
                <div class="form-group">
                    {!! Form::label('location_id', __('purchase.business_location') . ':*') !!}
                    {!! Form::select('location_id', $business_locations, null, ['class' => 'form-control select2', 'placeholder' => __('messages.please_select'), 'required', 'id' => 'journal_location_id']); !!}
                </div>
            </div>

            <div class="col-sm-3">
                <div class="form-group">
                    {!! Form::label('currency_id', __('business.currency') . ':') !!}
                    {!! Form::select('currency_id', $currencies, null, ['class' => 'form-control select2', 'placeholder' => __('messages.please_select'), 'id' => 'journal_currency_id']); !!}
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-sm-3" id="exchange_rate_div" style="display: none;">
                <div class="form-group">
                    {!! Form::label('exchange_rate', __('lang_v1.currency_exchange_rate') . ':') !!}
                    {!! Form::text('exchange_rate', 1.0, ['class' => 'form-control input_number', 'id' => 'exchange_rate']); !!}
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-12">
                <div class="form-group">
                    {!! Form::label('note', __('lang_v1.additional_notes')) !!}
                    {!! Form::textarea('note', null, ['class' => 'form-control', 'rows' => 2]); !!}
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-sm-12">
                <table class="table table-bordered table-striped hide-footer" id="journal_table">
                    <thead>
                        <tr>
                            <th class="col-md-1">#</th>
                            <th class="col-md-5">@lang( 'accounting::lang.account' )</th>
                            <th class="col-md-3">@lang( 'accounting::lang.debit' )</th>
                            <th class="col-md-3">@lang( 'accounting::lang.credit' )</th>
                            <th class="col-md-1"><i class="fa fa-trash"></i></th> 
                        </tr>
                    </thead>
                    <tbody id="tableBody">
                        @for($i = 1; $i <= 10; $i++)
                            <tr class="journal-row">
                                <td class="row-number">{{$i}}</td>
                                <td>
                                    {!! Form::select('account_id[]', [], null, 
                                        ['class' => 'form-control accounts-dropdown account_id', 
                                        'placeholder' => __('messages.please_select'), 'style' => 'width: 100%;']); !!}
                                </td>
                                <td>
                                    {!! Form::text('debit[]', null, ['class' => 'form-control input_number debit']); !!}
                                </td>
                                <td>
                                    {!! Form::text('credit[]', null, ['class' => 'form-control input_number credit']); !!}
                                </td>
                                <td>
                                    <button type="button" tabindex="-1" class="btn btn-danger btn-xs remove_row" ><i class="fa fa-trash"></i></button>
                                    <button type="button" tabindex="-1" class="btn btn-success btn-xs add_row_between" title="إضافة سطر هنا"><i class="fa fa-plus"></i></button>
                                </td>
                            </tr>
                        @endfor
                    </tbody>

                    <tfoot>
                        <tr>
                            <td colspan="2"></td>
                            <td colspan="3">
                                <button type="button" id="addRow" class="tw-dw-btn tw-dw-btn-primary tw-text-white tw-dw-btn-sm pull-right">@lang('accounting::lang.add_more_row')</button>
                            </td>
                        </tr>
                        <tr>
                            <th></th>
                            <th class="text-center">@lang( 'accounting::lang.total' )</th>
                            <th><input type="hidden" class="total_debit_hidden"><span class="total_debit"></span></th>
                            <th><input type="hidden" class="total_credit_hidden"><span class="total_credit"></span></th>
                            <th></th>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>

        <div class="row">
            <div class="col-sm-12">
                <button type="button" class="tw-dw-btn tw-dw-btn-primary tw-text-white pull-right journal_add_btn">@lang('messages.save')</button>
            </div>
        </div>
        
    @endcomponent

    {!! Form::close() !!}
</section>

@stop

@section('javascript')
@include('accounting::accounting.common_js')
<script type="text/javascript">
    $(document).ready(function(){

        // منطق العملة
        $('#journal_currency_id').change(function() {
            var currency_id = $(this).val();
            if (currency_id && currency_id != "{{ session('business.currency_id') }}") {
                $('#exchange_rate_div').fadeIn();
            } else {
                $('#exchange_rate_div').fadeOut();
                $('#exchange_rate').val(1);
            }
        });

        function reorderRows() {
            $('#tableBody tr').each(function(index) {
                $(this).find('.row-number').text(index + 1);
            });
        }

        function getNewRow() {
            return `
                <tr class="journal-row">
                    <td class="row-number"></td>
                    <td>
                        <select name="account_id[]" class="form-control accounts-dropdown account_id" style="width: 100%;"></select>
                    </td>
                    <td><input name="debit[]" type="text" class="form-control input_number debit"></td>
                    <td><input name="credit[]" type="text" class="form-control input_number credit"></td>
                    <td>
                        <button type="button" tabindex="-1" class="btn btn-danger btn-xs remove_row"><i class="fa fa-trash"></i></button>
                        <button type="button" tabindex="-1" class="btn btn-success btn-xs add_row_between" title="إضافة سطر هنا"><i class="fa fa-plus"></i></button>
                    </td>
                </tr>`;
        }

        function initializeAccountSelect2(element) {
            element.select2({
                ajax: {
                    url: '{{route("accounts-dropdown")}}',
                    dataType: 'json',
                    processResults: function (data) {
                        return { results: data };
                    },
                },
                escapeMarkup: function(markup) { return markup; },
                templateResult: function(data) { return data.html; },
                templateSelection: function(data) { return data.text; }
            });
        }

        // إضافة سطر جديد
        $('#addRow').click(function() {
            var row = $(getNewRow());
            $('#tableBody').append(row);
            initializeAccountSelect2(row.find('.accounts-dropdown'));
            reorderRows();
        });

        $(document).on('click', '.add_row_between', function() {
            var currentRow = $(this).closest('tr');
            var row = $(getNewRow());
            currentRow.after(row);
            initializeAccountSelect2(row.find('.accounts-dropdown'));
            reorderRows();
        });

        $(document).on('click', '.remove_row', function() {
            if ($('#tableBody tr').length > 1) {
                $(this).closest('tr').remove();
                reorderRows();
                calculate_total();
            } else {
                alert("لا يمكن حذف جميع الأسطر");
            }
        });

        // التنقل بالـ Enter والأسهم
        $(document).on('keydown', 'input, .select2-selection', function(e) {
            var tr = $(this).closest('tr');
            var index = $(this).closest('td').index();

            if (e.which == 13) {
                e.preventDefault();
                if ($(this).hasClass('debit')) {
                    tr.find('.credit').focus().select();
                } else if ($(this).hasClass('credit')) {
                    var nextTr = tr.next();
                    if (nextTr.length) {
                        nextTr.find('.account_id').select2('open');
                    } else {
                        $('#addRow').click();
                        setTimeout(function(){ 
                            $('#tableBody tr:last').find('.account_id').select2('open'); 
                        }, 150);
                    }
                }
            }

            if (e.which == 38) { // Up
                e.preventDefault();
                var prevField = tr.prev().find('td').eq(index).find('input, .select2-selection');
                if(prevField.length) prevField.focus().select();
            }
            if (e.which == 40) { // Down
                e.preventDefault();
                var nextField = tr.next().find('td').eq(index).find('input, .select2-selection');
                if(nextField.length) nextField.focus().select();
            }
        });

        // بعد اختيار الحساب، انتقل مباشرة للمدين
        $(document).on('select2:select', '.account_id', function (e) {
            var tr = $(this).closest('tr');
            setTimeout(function() {
                tr.find('.debit').focus().select();
            }, 50);
        });

        $(document).on('change', '.credit, .debit', function(){
            var tr = $(this).closest('tr');
            var debitField = tr.find('.debit');
            var creditField = tr.find('.credit');
            
            if($(this).hasClass('credit') && __read_number(creditField) > 0 && __read_number(debitField) > 0){
                if(confirm("هذا السطر يحتوي على قيمة (مدين)، هل تريد مسحها؟")){
                    debitField.val('');
                } else {
                    creditField.val('');
                }
            } else if($(this).hasClass('debit') && __read_number(debitField) > 0 && __read_number(creditField) > 0){
                if(confirm("هذا السطر يحتوي على قيمة (دائن)، هل تريد مسحها؟")){
                    creditField.val('');
                } else {
                    debitField.val('');
                }
            }
            calculate_total();
        });

        $('.journal_add_btn').click(function(e){
            calculate_total();
            var is_valid = true;

            // التأكد من اختيار الفرع الرئيسي
            if($('#journal_location_id').val() == ''){
                is_valid = false;
                alert("الرجاء اختيار الفرع أولاً");
                return false;
            }

            if($('.total_credit_hidden').val() != $('.total_debit_hidden').val()){
                is_valid = false;
                alert("@lang('accounting::lang.credit_debit_equal')");
            }

            $('#tableBody tr').each(function(index, tr) { 
                var credit = __read_number($(tr).find('.credit'));
                var debit = __read_number($(tr).find('.debit'));
                if(credit != 0 || debit != 0){
                    if($(tr).find('.account_id').val() == ''){
                        is_valid = false;
                        alert("@lang('accounting::lang.select_all_accounts')");
                        return false; 
                    }
                }
            });

            if(is_valid){
                $('form#journal_add_form').submit();
            }
        });
    });

    function calculate_total(){
        var total_credit = 0;
        var total_debit = 0;
        $('#tableBody tr').each(function(index, tr) { 
            total_credit += __read_number($(tr).find('.credit'));
            total_debit += __read_number($(tr).find('.debit'));
        });

        $('.total_credit_hidden').val(total_credit);
        $('.total_debit_hidden').val(total_debit);
        $('.total_credit').text(__currency_trans_from_en(total_credit));
        $('.total_debit').text(__currency_trans_from_en(total_debit));
    }
</script>
@endsection