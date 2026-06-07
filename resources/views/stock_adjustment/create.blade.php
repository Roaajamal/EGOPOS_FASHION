@php 
    $custom_labels = json_decode(session('business.custom_labels'), true);
    $p_labels = $custom_labels['product'] ?? [];
    
    // حساب عدد الأعمدة الديناميكية
    $dynamic_colspan = 4; // (رقم السطر + SKU + الوصف + الكمية)
    if (!empty($p_labels['custom_field_1'])) $dynamic_colspan++;
    if (!empty($p_labels['custom_field_2'])) $dynamic_colspan++;
    if (!empty($p_labels['custom_field_3'])) $dynamic_colspan++;
    $dynamic_colspan++; // عمود السعر
    $dynamic_colspan++; // عمود المجموع
    $dynamic_colspan++; // عمود الإجراءات
@endphp

@extends('layouts.app')
@section('title', __('stock_adjustment.add'))

@section('content')
    <section class="content-header">
        <br>
        <h1 class="tw-text-xl md:tw-text-3xl tw-font-bold tw-text-black">@lang('stock_adjustment.add')</h1>
    </section>

    <section class="content no-print">
        {!! Form::open([
            'url' => action([\App\Http\Controllers\StockAdjustmentController::class, 'store']),
            'method' => 'post',
            'id' => 'stock_adjustment_form',
            'files' => true 
        ]) !!}

        @component('components.widget', ['class' => 'box-solid'])
            <div class="row">
                <div class="col-sm-3">
                    <div class="form-group">
                        {!! Form::label('location_id', __('purchase.business_location') . ':*') !!}
                        {!! Form::select('location_id', $business_locations, null, [
                            'class' => 'form-control select2',
                            'placeholder' => __('messages.please_select'),
                            'required',
                            'id' => 'location_id'
                        ]) !!}
                    </div>
                </div>
                <div class="col-sm-3">
                    <div class="form-group">
                        {!! Form::label('ref_no', __('purchase.ref_no') . ':') !!}
                       {!! Form::text('ref_no', $next_ref_no, ['class' => 'form-control', 'id' => 'ref_no', 'readonly']); !!}
                    </div>
                </div>
                <div class="col-sm-3">
                    <div class="form-group">
                        {!! Form::label('transaction_date', __('messages.date') . ':*') !!}
                        <div class="input-group">
                            <span class="input-group-addon"><i class="fa fa-calendar"></i></span>
                            {!! Form::text('transaction_date', @format_datetime('now'), ['class' => 'form-control', 'id' => 'transaction_date', 'readonly', 'required']) !!}
                        </div>
                    </div>
                </div>
                <div class="col-sm-3">
                    <div class="form-group">
                        {!! Form::label('adjustment_type', __('stock_adjustment.out_type') . ':*') !!}
                        {!! Form::select('adjustment_type', [
                            'normal' => __('stock_adjustment.normal'), 
                            'abnormal' => __('stock_adjustment.abnormal'),
                            
                        ], null, [
                            'class' => 'form-control select2', 
                            'placeholder' => __('messages.please_select'), 
                            'required',
                            'id' => 'adjustment_type'
                        ]) !!}
                    </div> 
                </div>
            </div>
        @endcomponent

        @component('components.widget', ['class' => 'box-solid'])
            <div class="row">
                <div class="col-sm-10 col-sm-offset-1">
                    <div class="row">
                        <div class="col-sm-9">
                            <div class="form-group">
                                <div class="input-group">
                                    <span class="input-group-addon">
                                        <i class="fa fa-search"></i>
                                    </span>
                                    {!! Form::text('search_product', null, [
                                        'class' => 'form-control',
                                        'id' => 'search_product_for_srock_adjustment',
                                        'placeholder' => __('stock_adjustment.search_product'),
                                        'disabled',
                                        'autocomplete' => 'off'
                                    ]) !!}
                                </div>
                              
                            </div>
                        </div>
                        <div class="col-sm-3">
                            <button type="button" class="tw-dw-btn tw-dw-btn-primary tw-text-white tw-dw-btn-sm tw-w-full" 
                                    style="height: 34px; width: 100%;" 
                                    data-toggle="modal" 
                                    data-target="#export_quantity_products_modal"
                                    id="export_excel_btn">
                                <i class="fa fa-file-excel-o"></i> @lang('stock_adjustment.export')
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row mt-3">
                <div class="col-sm-12">
                    <input type="hidden" id="product_row_index" value="0">
                    <input type="hidden" id="total_amount" name="final_total" value="0">
                    
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped table-condensed" id="stock_adjustment_product_table">
                            <thead>
                                <tr class="active">
                                    {{-- عمود رقم السطر --}}
                                    <th class="text-center" style="width: 50px;">#</th>
                                    <th class="text-center sku-column">@lang('product.sku')</th>
                                      @if(!empty($p_labels['custom_field_3']))
                                    <th class="text-center custom-field-3">{{ $p_labels['custom_field_3'] }}</th>
                                    @endif
                                    
                                    @if(!empty($p_labels['custom_field_1']))
                                        <th class="text-center custom-field-1">{{ $p_labels['custom_field_1'] }}</th>
                                    @endif
                                    
                                   @if(!empty($p_labels['custom_field_2']))
                                        <th class="text-center custom-field-2">{{ $p_labels['custom_field_2'] }}</th>
                                    @endif
                                    <th class="text-center product-name-column">@lang('lang_v1.description')</th>
                                    <th class="text-center quantity-column">@lang('sale.qty')</th>
                                    <th class="text-center price-column">@lang('lang_v1.cost')</th>
                                    <th class="text-center total-column">@lang('stock_adjustment.total')</th>
                                    <th class="text-center actions-column"><i class="fa fa-trash"></i></th>
                                </tr>
                            </thead>
                            <tbody>
                                {{-- سيتم إضافة الأسطر هنا بواسطة JS --}}
                            </tbody>
                           
                        </table>
                    </div>
                </div>
            </div>
            
            {{-- معلومات إضافية عن عدد المنتجات --}}
            <div class="pull-right col-md-4">
    <table class="table">
        <tr>
            <th class="text-right">إجمالي الكميات :</th>
            <td><span id="total_quantities">0</span></td>
        </tr>
        <tr>
            <th class="text-right">إجمالي القيمة :</th>
            <td>
                <span id="total_adjustment" class="display_currency">0</span>
                <input type="hidden" id="total_adjustment_value" name="final_total" value="0">
            </td>
        </tr>
    </table>
</div>
        @endcomponent

        @component('components.widget', ['class' => 'box-solid'])
            <div class="row">
                <div class="col-sm-4">
                    <div class="form-group">
                        {!! Form::label('total_amount_recovered', __('stock_adjustment.total_amount_recovered') . ':') !!}
                        <div class="input-group">
                            <span class="input-group-addon"><i class="fa fa-money"></i></span>
                            {!! Form::text('total_amount_recovered', 0, [
                                'class' => 'form-control input_number',
                                'id' => 'total_amount_recovered',
                                'placeholder' => __('stock_adjustment.total_amount_recovered')
                            ]) !!}
                        </div>
                    </div>
                </div>
                <div class="col-sm-4">
                    <div class="form-group">
                        {!! Form::label('additional_notes', __('stock_adjustment.reason_for_stock_adjustment') . ':') !!}
                        {!! Form::textarea('additional_notes', null, [
                            'class' => 'form-control',
                            'id' => 'additional_notes',
                            'placeholder' => __('stock_adjustment.reason_for_stock_adjustment'),
                            'rows' => 3
                        ]) !!}
                    </div>
                </div>
               
                </div>
            </div>
            
            <div class="row">
                <div class="col-sm-12 text-center">
                    <button type="submit" class="tw-dw-btn tw-dw-btn-primary tw-dw-btn-lg tw-text-white" id="submit_btn">
                        <i class="fa fa-save"></i> @lang('messages.save')
                    </button>
                   
                </div>
            </div>
        @endcomponent
        {!! Form::close() !!}
    </section>

    {{-- Modal استيراد من إكسل --}}
    @include('stock_adjustment.partials.export_quantity_products_modal')
    
    {{-- قالب مخفي لصف المنتج (لتحسين أداء الاستيراد) --}}
    @include('stock_adjustment.partials.product_row_template')
@stop

@section('javascript')
    <script src="{{ asset('js/stock_adjustment.js?v=' . $asset_v) }}"></script>
    <script type="text/javascript">
        $(document).ready(function() {
            __page_leave_confirmation('#stock_adjustment_form');
            
            // تحديث عدد المنتجات
            function updateProductsCount() {
                let count = $('#stock_adjustment_product_table tbody tr').length;
                $('#products_count').text(count);
            }
            
            // استدعاء الدالة عند إضافة أو حذف منتج
            $(document).on('product_added product_removed', function() {
                updateProductsCount();
            });
            
            // تفعيل tooltips
            $('[data-toggle="tooltip"]').tooltip();
        });
    </script>
@endsection

@push('css')
    <style>
        /* تنسيقات إضافية */
        #stock_adjustment_product_table th {
            background-color: #f4f4f4;
            font-weight: 600;
        }
        
        #stock_adjustment_product_table tbody tr:hover {
            background-color: #f9f9f9;
        }
        
        .line-number-column {
            background-color: #f8f9fa;
            font-weight: bold;
        }
        
        #total_adjustment {
            font-size: 16px;
            font-weight: bold;
        }
        
        .input-group-addon {
            background-color: #f0f0f0;
        }
        
        /* تنسيق رسائل المساعدة */
        small.text-muted {
            display: block;
            margin-top: 5px;
            font-size: 11px;
        }
        
        /* تحسين ظهور الأزرار */
        .remove_product_row {
            opacity: 0.7;
            transition: opacity 0.3s;
        }
        
        .remove_product_row:hover {
            opacity: 1;
        }
        
        /* تنسيق شريط التقدم أثناء الحفظ */
        #submit_btn:disabled {
            opacity: 0.7;
            cursor: not-allowed;
        }
        
        .fa-spinner {
            margin-right: 5px;
        }
    </style>
@endpush