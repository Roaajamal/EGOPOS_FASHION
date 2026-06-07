@extends('layouts.app')
@section('title',  __('inventory.inventory_create'))
@section('content')

@php
$custom_labels = json_decode(session('business.custom_labels'), true);
$p_labels = $custom_labels['product'] ?? [];
@endphp

<section class="content-header">
    <h1 class="tw-text-xl md:tw-text-3xl tw-font-bold">{{__('inventory.inventory_create')}} </h1>
</section>

<section class="content">

@include('layouts.partials.error')

{!! Form::open([
    'url' => action([\App\Http\Controllers\InventoryController::class, 'store']),
    'method' => 'post',
    'id' => 'inventory_form',
    'files' => true 
]) !!}

@component('components.widget', ['class' => 'box-primary'])
<div class="row">
    <div class="col-md-3">
        <div class="form-group">
            {!! Form::label('ref_no', __('purchase.ref_no').':') !!}
            @show_tooltip(__('lang_v1.leave_empty_to_autogenerate'))
            {!! Form::text('ref_no', $ref_no ?? null, ['class' => 'form-control', 'id' => 'ref_no']); !!}
        </div>
    </div>

   <div class="col-sm-4">
    <div class="form-group">
        {!! Form::label('transaction_date', __('quantity_entry.quantity_date')) !!}
        <div class="input-group">
            <span class="input-group-addon"><i class="fa fa-calendar"></i></span>
            {!! Form::text('transaction_date', @format_datetime('now'), ['class' => 'form-control', 'id' => 'transaction_date', 'readonly', 'required']) !!}
        </div>
    </div>
</div>

     <div class="col-sm-4">
        <div class="form-group">
            {!! Form::label('location_id', __('inventory.location')) !!}
            {!! Form::select('location_id', $business_locations, null, ['class' => 'form-control select2', 'required', 'id' => 'location_id']) !!}
        </div>
    </div>

<div class="row">
    <div class="col-md-3">
        <div class="form-group">
            {!! Form::label('additional_notes',__('quantity_entry.additional_notes') . ':') !!}
            {!! Form::textarea('additional_notes', null, ['class' => 'form-control', 'rows' => 3]); !!}
        </div>
    </div>

    <div class="col-md-6">
        <div class="form-group">
            {!! Form::label('document', __('purchase.attach_document') . ':') !!}
            {!! Form::file('document', ['id' => 'upload_document', 'accept' => implode(',', array_keys(config('constants.document_upload_mimes_types')))]); !!}
            <p class="help-block">
                @lang('purchase.max_file_size', ['size' => (config('constants.document_size_limit') / 1000000)])
                @includeIf('components.document_help_text')
            </p>
        </div>
    </div>
</div>
@endcomponent

@component('components.widget', ['class' => 'box-primary'])
<div class="row">
    <div class="col-sm-12 missing-product-warning"></div>

    <div class="col-sm-2 text-center">
        <button type="button" class="tw-dw-btn tw-dw-btn-primary tw-text-white tw-dw-btn-sm" data-toggle="modal" data-target="#import_new_quantity_products_modal">
            @lang('inventory.import_quantities_entry')
        </button>

        <div class="mt-1">
            <label class="d-inline-flex align-items-center" style="cursor: pointer; gap: 6px;">
                {!! Form::checkbox('auto_select_products', 1, false, ['id' => 'auto_select_products_checkbox', 'class' => 'input-sm']) !!}
                <small class="help-block m-0" style="font-size: 11px; line-height: 1;">
                    @lang('quantity_entry.auto_select_products_help')
                </small>
            </label>
        </div>
    </div>

    <div class="col-sm-10">
        <div class="form-group">
            <div class="input-group">
                <span class="input-group-addon"><i class="fa fa-search"></i></span>
                {!! Form::text('search_product', null, ['class' => 'form-control', 'id' => 'search_product', 'placeholder' => __('lang_v1.search_product_placeholder')]) !!}
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-sm-12">
        <div class="table-responsive">
            <table class="table table-bordered table-striped" id="quantity_table">
               <thead>
                <tr>
                    <th class="text-center">#</th>
                    <th>{{ __('product.sku') }}</th>
                    @if(!empty($p_labels['custom_field_3']))
                                    <th class="text-center custom-field-3">{{ $p_labels['custom_field_3'] }}</th>
                                    @endif
                                    
                                    @if(!empty($p_labels['custom_field_1']))
                                        <th class="text-center custom-field-1">{{ $p_labels['custom_field_1'] }}</th>
                                    @endif
                                    
                                   @if(!empty($p_labels['custom_field_2']))
                                        <th class="text-center custom-field-2">{{ $p_labels['custom_field_2'] }}</th>
                                    @endif
                    <th>{{ __('product.product') }}</th>
                    <th class="text-center">{{ __('inventory.new_quantity') }} </th>
                    <th class="text-center">{{ __('inventory.current_quantity') }} </th>
                    <th class="text-center">{{ __('inventory.difference') }} </th>
                    <th class="text-center">{{ __('inventory.cost') }}</th>
                    <th class="text-center">{{ __('inventory.total') }}</th>
                    <th class="text-center"><i class="fa fa-trash"></i></th>
                </tr>
            </thead>
                <tbody></tbody>
            </table>
        </div>

        <hr>

        <div class="pull-right col-md-4">
            <table class="table">
                <tr>
                    <th class="text-right">{{ __('inventory.total_quantity')}} :</th>
                    <td><span id="total_quantity">0</span></td>
                </tr>
                <tr>
                    <th class="text-right"> {{ __('inventory.total_price')}}:</th>
                    <td>
                        <span id="grand_total" class="display_currency">0</span>
                        {!! Form::hidden('final_total', 0, ['id' => 'grand_total_hidden']) !!}
                    </td>
                </tr>
            </table>
        </div>

        <input type="hidden" id="row_count" value="0">
    </div>
</div>

<div class="row">
    <div class="col-sm-4">
        <div class="well well-sm bg-warning tw-border-red-300 tw-border-2" style="background-color: #fff3cd;">
            <label class="d-inline-flex align-items-center" style="cursor: pointer; gap: 10px; margin: 0;">
               
                {!! Form::checkbox('final_zero_out', 1, false, [
                    'id' => 'final_zero_out_checkbox', 
                    'class' => 'input-sm'
                ]) !!}
                <span class="tw-font-bold tw-text-red-700">
                    <i class="fa fa-exclamation-triangle"></i> 
                    @lang('inventory.zero_out_stock') 
                </span>
            </label>
        </div>
    </div>
</div>

@endcomponent

@component('components.widget', ['class' => 'box-primary'])



<div class="row">
    <div class="col-md-12 text-center">
        <button type="submit" class="tw-dw-btn tw-dw-btn-primary tw-dw-btn-lg tw-text-white">{{ __('messages.submit')}}</button>
    </div>
</div>
@endcomponent

{!! Form::close() !!}

</section>

<div class="modal fade" id="review_additions_modal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal">&times;</button>
                <h4 class="modal-title text-success"><i class="fa fa-plus-circle"></i> {{ __('inventory.confirm_additions') }}</h4>
            </div>
            <div class="modal-body" style="max-height: 70vh; overflow-y: auto;">
                <table class="table table-bordered table-striped" id="additions_review_table">
                    <thead>
                        <tr class="bg-green">
                            <th>{{ __('product.product') }}</th>
                            <th class="text-center">{{ __('inventory.current_quantity') }}</th>
                            <th class="text-center">{{ __('inventory.new_quantity') }}</th>
                            <th class="text-center">{{ __('inventory.add_diff') }}</th>
                        </tr>
                    </thead>
                    <tbody class="text-center"></tbody>
                </table>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary" id="confirm_additions_btn">{{ __('inventory.save_and_continue') }}</button>
                <button type="button" class="btn btn-default" data-dismiss="modal">{{ __('messages.cancel') }}</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="review_adjustments_modal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal">&times;</button>
                <h4 class="modal-title text-danger"><i class="fa fa-minus-circle"></i> {{ __('inventory.confirm_adjustments') }}</h4>
            </div>
            <div class="modal-body" style="max-height: 70vh; overflow-y: auto;">
                <table class="table table-bordered table-striped" id="adjustments_review_table">
                    <thead>
                        <tr class="bg-red">
                            <th>{{ __('product.product') }}</th>
                            <th class="text-center">{{ __('inventory.current_quantity') }}</th>
                            <th class="text-center">{{ __('inventory.new_quantity') }}</th>
                            <th class="text-center">{{ __('inventory.adj_diff') }}</th>
                        </tr>
                    </thead>
                    <tbody class="text-center"></tbody>
                </table>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-danger" id="confirm_final_save_btn">{{ __('inventory.confirm_final_save') }}</button>
            </div>
        </div>
    </div>
</div>

@include('inventory.partials.import_new_quantity_products_modal')
@endsection

@section('javascript')
    @parent
    <script src="{{ asset('js/inventory.js') }}"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
$(document).ready(function () {
    __page_leave_confirmation('#inventory_form');
});
</script>
@endsection

