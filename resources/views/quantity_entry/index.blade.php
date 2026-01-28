@extends('layouts.app')
@section('title',  __('quantity_entry.quantity_entry'))
@section('content')

@php
$custom_labels = json_decode(session('business.custom_labels'), true);
@endphp

<section class="content-header">
    <h1 class="tw-text-xl md:tw-text-3xl tw-font-bold">{{__('quantity_entry.quantity_entry')}} </h1>
</section>

<section class="content">

@include('layouts.partials.error')

{!! Form::open([
    'url' => action([\App\Http\Controllers\QuantityEntryController::class, 'store']),
    'method' => 'post',
    'id' => 'add_quantity_form',
    'files' => true  // 👈 ضروري جداً لأنك ترفع ملف Document
]) !!}

@component('components.widget', ['class' => 'box-primary'])
<div class="row">
    <div class="col-md-3">
        <div class="form-group">
            {!! Form::label('ref_no', __('purchase.ref_no').':') !!}
            @show_tooltip(__('lang_v1.leave_empty_to_autogenerate'))
            {!! Form::text('ref_no', $ref_no ?? null, ['class' => 'form-control']); !!}
        </div>
    </div>

    <div class="col-sm-4">
        <div class="form-group">
            {!! Form::label('transaction_date', __('quantity_entry.quantity_date')) !!}
            <div class="input-group">
                <span class="input-group-addon"><i class="fa fa-calendar"></i></span>
                {!! Form::text('transaction_date', @format_datetime('now'), ['class' => 'form-control', 'readonly', 'required']) !!}
            </div>
        </div>
    </div>

    <div class="col-sm-4">
        <div class="form-group">
            {!! Form::label('location_id', __('quantity_entry.location')) !!}
            {!! Form::select('location_id', $business_locations, null, ['class' => 'form-control select2', 'required']) !!}
        </div>
    </div>

    <div class="col-sm-2">
        <div class="form-group">
            <button tabindex="-1" type="button" class="btn btn-link btn-modal" data-href="{{action([\App\Http\Controllers\ProductController::class, 'quickAdd'])}}" data-container=".quick_add_product_modal">
                <i class="fa fa-plus"></i> @lang('product.add_new_product')
            </button>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-3">
        <div class="form-group">
            {!! Form::label('quantity_entry.additional_notes',__('quantity_entry.additional_notes')) !!}
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
            @lang('quantity_entry.import_quantities_entry')
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
                {!! Form::text('search_product', null, ['class' => 'form-control', 'id' => 'search_product', 'placeholder' => 'ابحث عن منتج']) !!}
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-sm-12">
        <div class="table-responsive">
            <table class="table table-bordered table-striped" id="purchase_entry_table">
                <thead>
                    <tr>
                        <th>#</th>
                       
                        <th>SKU</th>
                        <th> {{ __('quantity_entry.product_name')}}</th>
                        <th>{{ __('quantity_entry.new_quantity')}}</th>
                        <th>{{ __('quantity_entry.cost_quantity_entry')}}</th>
                        <th>{{ __('quantity_entry.total')}}</th>
                        <th><i class="fa fa-trash"></i></th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>

        <hr>

        <div class="pull-right col-md-4">
            <table class="table">
                <tr>
                    <th class="text-right">{{ __('quantity_entry.total_quantity')}} :</th>
                    <td><span id="total_quantity">0</span></td>
                </tr>
                <tr>
                    <th class="text-right"> {{ __('quantity_entry.total_price')}}:</th>
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
@endcomponent

@component('components.widget', ['class' => 'box-primary'])
<div class="row">
    <div class="col-md-12 text-center">
        <button type="submit" class="tw-dw-btn tw-dw-btn-primary tw-dw-btn-lg tw-text-white">{{ __('quantity_entry.submit')}}</button>
    </div>
</div>
@endcomponent

{!! Form::close() !!}

</section>

<div class="modal fade quick_add_product_modal" tabindex="-1" role="dialog"></div>
@include('quantity_entry.partials.import_new_quantity_products_modal')



@endsection

@section('javascript')
<script src="{{ asset('js/quantity_entry.js') }}"></script>

<script>
$(document).ready(function () {
    __page_leave_confirmation('#add_quantity_form');
});
</script>
@endsection
