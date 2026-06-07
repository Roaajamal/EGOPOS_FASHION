@extends('layouts.app')
@section('title', __('lang_v1.stock_transfers'))

@section('content')

<!-- Content Header (Page header) -->
<section class="content-header no-print">
    <h1 class="tw-text-xl md:tw-text-3xl tw-font-bold tw-text-black">@lang('lang_v1.stock_transfers')
    </h1>
</section>

<!-- Main content -->
<section class="content no-print">

<div class="row">
        <div class="col-md-12">
            @component('components.filters', ['title' => __('report.filters')])

                <div class="col-md-3" id="location_filter">
                    <div class="form-group">
                        {!! Form::label('location_id_filter', __('purchase.business_location') . ':') !!}
                        {!! Form::select('location_id_filter', $business_locations, null, [
                            'class'       => 'form-control select2',
                            'style'       => 'width:100%',
                            'placeholder' => __('lang_v1.all'),
                        ]) !!}
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="form-group">
                        {!! Form::label('qer_date_filter', __('report.date_range') . ':') !!}
                        {!! Form::text('date_range', null, [
                            'placeholder' => __('lang_v1.select_a_date_range'),
                            'class'       => 'form-control',
                            'id'          => 'qer_date_filter',
                            'readonly'
                        ]); !!}
                    </div>
                </div>

            @endcomponent
        </div>
    </div>
    
    @component('components.widget', ['class' => 'box-primary', 'title' => __('lang_v1.all_stock_transfers')])
        @slot('tool')
           <div class="box-tools">
    @if(auth()->user()->can('stock_transfer.create'))
        <a class="tw-dw-btn tw-dw-btn-primary tw-text-white tw-font-bold tw-rounded-full pull-right"
            href="{{action([\App\Http\Controllers\StockTransferController::class, 'create'])}}">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                class="icon icon-tabler icons-tabler-outline icon-tabler-plus">
                <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                <path d="M12 5l0 14" />
                <path d="M5 12l14 0" />
            </svg> @lang('messages.add')
        </a>
    @endif
</div>
        @endslot
        <div class="table-responsive">
            <table class="table table-bordered table-striped ajax_view" id="stock_transfer_table">
                <thead>
                    <tr>
                        <th>@lang('messages.date')</th>
                        <th>@lang('purchase.ref_no')</th>
                        <th>@lang('lang_v1.location_from')</th>
                        <th>@lang('lang_v1.location_to')</th>
                        <th>@lang('lang_v1.total_of_quantity')</th>
                        <th>@lang('sale.status')</th>
                        <th>@lang('lang_v1.shipping_charges')</th>
                        <th>@lang('stock_adjustment.total_amount')</th>
                        <th>@lang('purchase.additional_notes')</th>
                        <th class="tw-w-full">@lang('messages.action')</th>
                    </tr>
                </thead>
            </table>
        </div>
    @endcomponent
</section>

@include('stock_transfer.partials.update_status_modal')

<section id="receipt_section" class="print_section"></section>

<!-- /.content -->
@stop
@section('javascript')
	<script src="{{ asset('js/stock_transfer.js?v=' . $asset_v) }}"></script>
@endsection

@cannot('view_purchase_price')
    <style>
        .show_price_with_permission {
            display: none !important;
        }
    </style>
@endcannot