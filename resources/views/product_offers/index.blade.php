@extends('layouts.app')
@section('title', __('lang_v1.product_offers'))
@section('content')

<style>
    .offer-badge {
        position: absolute;
        top: 5px;
        right: 5px;
        background: #28a745;
        color: white;
        padding: 2px 8px;
        border-radius: 10px;
        font-size: 11px;
        font-weight: bold;
    }
    .quantity-input {
        max-width: 100px;
    }
    .offer-price {
        color: #e74c3c;
        font-weight: bold;
    }
    .tabs-container {
        background: white;
        border-radius: 8px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        margin-bottom: 20px;
    }
    .nav-tabs {
        border-bottom: 2px solid #dee2e6;
    }
    .nav-tabs .nav-link {
        border: none;
        color: #6c757d;
        font-weight: 500;
        padding: 12px 25px;
        transition: all 0.3s;
    }
    .nav-tabs .nav-link:hover {
        border: none;
        color: #007bff;
    }
    .nav-tabs .nav-link.active {
        color: #007bff;
        background: white;
        border-bottom: 3px solid #007bff;
        font-weight: 600;
    }
    .tab-content {
        padding: 20px;
    }
    .import-section {
        background: #f8f9fa;
        border-radius: 8px;
        padding: 20px;
        margin-bottom: 20px;
        border: 1px solid #dee2e6;
    }
    .import-options {
        display: flex;
        gap: 10px;
        margin: 10px 0;
    }
    .import-option {
        flex: 1;
        text-align: center;
        padding: 10px;
        border: 2px solid #dee2e6;
        border-radius: 6px;
        cursor: pointer;
        transition: all 0.3s;
    }
    .import-option:hover {
        border-color: #007bff;
        background: #f0f8ff;
    }
    .import-option.active {
        border-color: #007bff;
        background: #007bff;
        color: white;
    }
    .template-download {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        color: #28a745;
        text-decoration: none;
    }
    .template-download:hover {
        text-decoration: underline;
    }
    .offers-table th {
        background: #f8f9fa;
        font-weight: 600;
        white-space: nowrap;
    }
    .status-badge {
        padding: 3px 10px;
        border-radius: 15px;
        font-size: 12px;
        font-weight: 600;
    }
    .status-active {
        background: #d4edda;
        color: #155724;
    }
    .status-inactive {
        background: #f8d7da;
        color: #721c24;
    }
</style>

<div class="row">
    <div class="col-sm-12">
        <div class="tabs-container">
            <!-- التبويبات -->
            <ul class="nav nav-tabs" role="tablist">
                <li class="nav-item">
                    <a class="nav-link active" href="#offers-tab" data-toggle="tab">
                        <i class="fa fa-tags"></i> @lang('lang_v1.active_offers')
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="#import-tab" data-toggle="tab">
                        <i class="fa fa-file-excel-o"></i> @lang('lang_v1.import_from_excel')
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="#add-tab" data-toggle="tab">
                        <i class="fa fa-plus-circle"></i> @lang('lang_v1.add_new_offer')
                    </a>
                </li>
            </ul>

            <!-- محتوى التبويبات -->
            <div class="tab-content">
                
                <!-- تبويب العروض النشطة -->
                <div class="tab-pane fade show active" id="offers-tab" role="tabpanel">
                    <div class="row mb-3">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="filter_location">@lang('business.business_location'):</label>
                                <select id="filter_location" class="form-control">
                                    <option value="">@lang('lang_v1.all_locations')</option>
                                    @foreach($business_locations as $key => $value)
                                        <option value="{{ $key }}">{{ $value }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="filter_status">@lang('lang_v1.status'):</label>
                                <select id="filter_status" class="form-control">
                                    <option value="">@lang('lang_v1.all')</option>
                                    <option value="active">@lang('lang_v1.active')</option>
                                    <option value="inactive">@lang('lang_v1.inactive')</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="filter_product">@lang('product.search_product'):</label>
                                <input type="text" id="filter_product" class="form-control" placeholder="@lang('lang_v1.product_name_or_sku')">
                            </div>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-bordered table-striped offers-table" id="offers_table">
                            <thead>
                                <tr>
                                    <th>@lang('product.product')</th>
                                    <th>@lang('lang_v1.min_quantity')</th>
                                    <th>@lang('lang_v1.offer_price')</th>
                                    <th>@lang('lang_v1.price_type')</th>
                                    <th>@lang('business.business_location')</th>
                                    <th>@lang('lang_v1.validity_period')</th>
                                    <th>@lang('lang_v1.status')</th>
                                    <th>@lang('messages.action')</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </div>

                <!-- تبويب الاستيراد -->
                <div class="tab-pane fade" id="import-tab" role="tabpanel">
                    <div class="import-section">
                        <h5><i class="fa fa-cloud-upload"></i> @lang('lang_v1.import_product_offers')</h5>
                        
                        <div class="alert alert-info">
                            <i class="fa fa-info-circle"></i>
                            @lang('lang_v1.import_offers_instructions')
                        </div>

                        <form action="{{ route('product-offers.import-excel') }}" method="POST" enctype="multipart/form-data" id="import_form">
                            @csrf
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="import_location">@lang('business.business_location') *</label>
                                        <select name="location_id" id="import_location" class="form-control" required>
                                            <option value="">@lang('messages.please_select')</option>
                                            @foreach($business_locations as $key => $value)
                                                <option value="{{ $key }}">{{ $value }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                                
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="excel_file">@lang('lang_v1.excel_file') *</label>
                                        <input type="file" name="excel_file" id="excel_file" class="form-control" accept=".xlsx,.xls,.csv" required>
                                        <small class="text-muted">@lang('lang_v1.max_file_size_5mb')</small>
                                    </div>
                                </div>
                            </div>

                            <div class="form-group">
                                <label>@lang('lang_v1.import_mode') *</label>
                                <div class="import-options">
                                    <div class="import-option active" data-mode="add">
                                        <input type="radio" name="import_mode" value="add" checked hidden>
                                        <i class="fa fa-plus-circle fa-2x"></i>
                                        <div>@lang('lang_v1.add_new_only')</div>
                                        <small>@lang('lang_v1.add_new_explanation')</small>
                                    </div>
                                    <div class="import-option" data-mode="replace">
                                        <input type="radio" name="import_mode" value="replace" hidden>
                                        <i class="fa fa-refresh fa-2x"></i>
                                        <div>@lang('lang_v1.replace_all')</div>
                                        <small>@lang('lang_v1.replace_explanation')</small>
                                    </div>
                                </div>
                            </div>

                            <div class="form-group">
                                <a href="{{ route('product-offers.download-template') }}" class="template-download">
                                    <i class="fa fa-download"></i> @lang('lang_v1.download_template')
                                </a>
                            </div>

                            <button type="submit" class="btn btn-primary">
                                <i class="fa fa-upload"></i> @lang('lang_v1.import')
                            </button>
                        </form>
                    </div>

                    <!-- تنسيق ملف Excel -->
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0"><i class="fa fa-table"></i> @lang('lang_v1.excel_file_format')</h5>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-bordered">
                                    <thead class="thead-light">
                                        <tr>
                                            <th>@lang('lang_v1.column_name')</th>
                                            <th>@lang('lang_v1.description')</th>
                                            <th>@lang('lang_v1.example')</th>
                                            <th>@lang('lang_v1.required')</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td><strong>SKU/Barcode</strong></td>
                                            <td>@lang('lang_v1.product_sku_barcode')</td>
                                            <td>PROD001, PROD002</td>
                                            <td><span class="badge badge-danger">@lang('lang_v1.required')</span></td>
                                        </tr>
                                        <tr>
                                            <td><strong>Min Quantity</strong></td>
                                            <td>@lang('lang_v1.minimum_quantity_for_offer')</td>
                                            <td>3, 5, 10</td>
                                            <td><span class="badge badge-danger">@lang('lang_v1.required')</span></td>
                                        </tr>
                                        <tr>
                                            <td><strong>Offer Price</strong></td>
                                            <td>@lang('lang_v1.offer_price_description')</td>
                                            <td>15.50, 20.00</td>
                                            <td><span class="badge badge-danger">@lang('lang_v1.required')</span></td>
                                        </tr>
                                        <tr>
                                            <td><strong>Price Type</strong></td>
                                            <td>@lang('lang_v1.price_type_description')</td>
                                            <td>fixed, percentage, override</td>
                                            <td><span class="badge badge-warning">@lang('lang_v1.optional')</span></td>
                                        </tr>
                                        <tr>
                                            <td><strong>Start Date</strong></td>
                                            <td>@lang('lang_v1.start_date_description')</td>
                                            <td>2024-01-01</td>
                                            <td><span class="badge badge-warning">@lang('lang_v1.optional')</span></td>
                                        </tr>
                                        <tr>
                                            <td><strong>End Date</strong></td>
                                            <td>@lang('lang_v1.end_date_description')</td>
                                            <td>2024-12-31</td>
                                            <td><span class="badge badge-warning">@lang('lang_v1.optional')</span></td>
                                        </tr>
                                        <tr>
                                            <td><strong>Active</strong></td>
                                            <td>@lang('lang_v1.active_status_description')</td>
                                            <td>1 (نشط), 0 (غير نشط)</td>
                                            <td><span class="badge badge-warning">@lang('lang_v1.optional')</span></td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                            
                            <div class="alert alert-success mt-3">
                                <h6><i class="fa fa-lightbulb-o"></i> @lang('lang_v1.example_for_multiple_offers'):</h6>
                                <p>@lang('lang_v1.multiple_offers_example')</p>
                                <pre class="mt-2">
SKU/Barcode | Min Quantity | Offer Price | Price Type
PROD001     | 3           | 10.00       | fixed
PROD001     | 5           | 8.50        | fixed
PROD001     | 10          | 6.00        | fixed
PROD002     | 5           | 15          | percentage</pre>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- تبويب إضافة جديد -->
                <div class="tab-pane fade" id="add-tab" role="tabpanel">
                    <form id="offer_form" action="{{ route('product-offers.store') }}" method="POST">
                        @csrf
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="product_search">@lang('product.product') *</label>
                                    <select id="product_search" class="form-control select2" style="width: 100%;" required>
                                        <option value="">@lang('lang_v1.search_product_placeholder')</option>
                                    </select>
                                    <input type="hidden" name="variation_id" id="variation_id">
                                    <div id="product_info" class="mt-2" style="display: none;">
                                        <div class="alert alert-light">
                                            <strong id="product_name"></strong><br>
                                            <small class="text-muted">@lang('product.sku'): <span id="product_sku"></span></small>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="location_id">@lang('business.business_location') *</label>
                                    <select name="location_id" id="location_id" class="form-control" required>
                                        <option value="">@lang('messages.please_select')</option>
                                        @foreach($business_locations as $key => $value)
                                            <option value="{{ $key }}">{{ $value }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>

                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="is_active">@lang('lang_v1.status')</label>
                                    <select name="is_active" id="is_active" class="form-control">
                                        <option value="1">@lang('lang_v1.active')</option>
                                        <option value="0">@lang('lang_v1.inactive')</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="min_quantity">@lang('lang_v1.min_quantity') *</label>
                                    <input type="number" name="min_quantity" id="min_quantity" class="form-control" step="0.001" min="0.001" required>
                                </div>
                            </div>

                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="price_type">@lang('lang_v1.price_type') *</label>
                                    <select name="price_type" id="price_type" class="form-control" required>
                                        <option value="fixed">@lang('lang_v1.fixed_price')</option>
                                        <option value="percentage">@lang('lang_v1.percentage_discount')</option>
                                        <option value="override">@lang('lang_v1.override_price')</option>
                                    </select>
                                </div>
                            </div>

                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="offer_price">@lang('lang_v1.offer_price') *</label>
                                    <input type="number" name="offer_price" id="offer_price" class="form-control" step="0.01" min="0" required>
                                    <small id="price_help" class="form-text text-muted"></small>
                                </div>
                            </div>

                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="base_price_display">@lang('lang_v1.base_price')</label>
                                    <input type="text" id="base_price_display" class="form-control" readonly disabled>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="start_date">@lang('lang_v1.start_date')</label>
                                    <input type="text" name="start_date" id="start_date" class="form-control datepicker">
                                </div>
                            </div>

                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="end_date">@lang('lang_v1.end_date')</label>
                                    <input type="text" name="end_date" id="end_date" class="form-control datepicker">
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="notes">@lang('lang_v1.notes')</label>
                                    <textarea name="notes" id="notes" class="form-control" rows="1"></textarea>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-12">
                                <div class="alert alert-info">
                                    <i class="fa fa-calculator"></i> 
                                    <strong>@lang('lang_v1.price_calculation'):</strong>
                                    <span id="calculation_example">
                                        @lang('lang_v1.price_calculation_example')
                                    </span>
                                </div>
                            </div>
                        </div>

                        <div class="form-group text-right">
                            <button type="button" class="btn btn-default" id="reset_form">
                                <i class="fa fa-refresh"></i> @lang('messages.clear')
                            </button>
                            <button type="submit" class="btn btn-primary" id="save_offer">
                                <i class="fa fa-save"></i> @lang('messages.save')
                            </button>
                        </div>
                    </form>

                    <!-- عرض العروض الحالية للمنتج -->
                    <div id="existing_offers" class="mt-4" style="display: none;">
                        <h5><i class="fa fa-history"></i> @lang('lang_v1.existing_offers_for_product')</h5>
                        <div class="table-responsive">
                            <table class="table table-bordered table-sm" id="current_offers_table">
                                <thead>
                                    <tr>
                                        <th>@lang('lang_v1.min_quantity')</th>
                                        <th>@lang('lang_v1.offer_price')</th>
                                        <th>@lang('lang_v1.price_type')</th>
                                        <th>@lang('business.business_location')</th>
                                        <th>@lang('lang_v1.status')</th>
                                        <th>@lang('messages.action')</th>
                                    </tr>
                                </thead>
                                <tbody></tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- مودال التعديل -->
<div class="modal fade" id="edit_offer_modal" tabindex="-1" role="dialog"></div>

<!-- مودال التأكيد -->
@include('product_offers.partials.confirm_modal')

@endsection

@section('javascript')
<script>
$(document).ready(function() {
    // تهيئة DataTable
    var offers_table = $('#offers_table').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: "{{ route('product-offers.get-data') }}",
            data: function(d) {
                d.location_id = $('#filter_location').val();
                d.status = $('#filter_status').val();
                d.product_search = $('#filter_product').val();
            }
        },
        columns: [
            { data: 'product', name: 'product', orderable: false },
            { data: 'min_quantity', name: 'min_quantity' },
            { data: 'offer_price', name: 'offer_price' },
            { data: 'price_type', name: 'price_type' },
            { data: 'location_name', name: 'bl.name' },
            { 
                data: null,
                render: function(data) {
                    var start = data.start_date ? data.start_date : '-';
                    var end = data.end_date ? data.end_date : '-';
                    return start + ' <i class="fa fa-arrow-right text-muted"></i> ' + end;
                },
                orderable: false
            },
            { data: 'is_active', name: 'is_active', orderable: false },
            { data: 'action', name: 'action', orderable: false, searchable: false }
        ],
        order: [[1, 'asc']],
        language: {
            "url": "{{ asset('js/lang/' . app()->getLocale() . '.json') }}"
        }
    });

    // البحث الفلترة
    $('#filter_location, #filter_status, #filter_product').on('change keyup', function() {
        offers_table.ajax.reload();
    });

    // البحث عن المنتجات
    $('#product_search').select2({
        ajax: {
            url: "{{ route('product-offers.search-products') }}",
            dataType: 'json',
            delay: 250,
            data: function(params) {
                return {
                    term: params.term
                };
            },
            processResults: function(data) {
                return {
                    results: data
                };
            },
            cache: true
        },
        minimumInputLength: 2,
        placeholder: "{{ __('lang_v1.search_product_placeholder') }}",
        templateResult: formatProduct,
        templateSelection: formatProductSelection
    });

    // عند اختيار منتج
    $('#product_search').on('select2:select', function(e) {
        var data = e.params.data;
        $('#variation_id').val(data.id);
        
        // عرض معلومات المنتج
        $('#product_info').show();
        $('#product_name').text(data.text);
        $('#product_sku').text(data.sub_sku);
        
        // جلب العروض الحالية
        loadExistingOffers(data.id);
        
        // جلب السعر الأساسي
        getBasePrice(data.id);
    });

    // تغيير نوع السعر
    $('#price_type').on('change', function() {
        updatePriceHelpText();
        calculateExample();
    });

    // تحديث النص المساعد للسعر
    function updatePriceHelpText() {
        var type = $('#price_type').val();
        var helpText = '';
        
        switch(type) {
            case 'fixed':
                helpText = "{{ __('lang_v1.fixed_price_help') }}";
                break;
            case 'percentage':
                helpText = "{{ __('lang_v1.percentage_help') }}";
                break;
            case 'override':
                helpText = "{{ __('lang_v1.override_help') }}";
                break;
        }
        
        $('#price_help').text(helpText);
    }

    // تحديث مثال الحساب
    function calculateExample() {
        var basePrice = parseFloat($('#base_price_display').val().replace(/[^0-9.-]+/g, '')) || 0;
        var offerPrice = parseFloat($('#offer_price').val()) || 0;
        var type = $('#price_type').val();
        
        var result = 0;
        var explanation = '';
        
        switch(type) {
            case 'fixed':
                result = offerPrice;
                explanation = "{{ __('lang_v1.fixed_price_explanation', ['price' => '"+offerPrice+"']) }}";
                break;
            case 'percentage':
                var discount = (basePrice * offerPrice) / 100;
                result = basePrice - discount;
                explanation = "{{ __('lang_v1.percentage_explanation', ['percentage' => '"+offerPrice+"', 'discount' => '"+discount.toFixed(2)+"']) }}";
                break;
            case 'override':
                result = offerPrice;
                explanation = "{{ __('lang_v1.override_explanation', ['price' => '"+offerPrice+"']) }}";
                break;
        }
        
        $('#calculation_example').html(
            '<strong>{{ __("lang_v1.final_price") }}: ' + result.toFixed(2) + '</strong><br>' + 
            '<small class="text-muted">' + explanation + '</small>'
        );
    }

    // جلب العروض الحالية
    function loadExistingOffers(variation_id) {
        var location_id = $('#location_id').val();
        
        if (!location_id) {
            return;
        }
        
        $.ajax({
            url: "{{ route('product-offers.get-offers') }}",
            data: {
                variation_id: variation_id,
                location_id: location_id
            },
            success: function(response) {
                if (response.success && response.offers.length > 0) {
                    $('#existing_offers').show();
                    var tbody = $('#current_offers_table tbody');
                    tbody.empty();
                    
                    response.offers.forEach(function(offer) {
                        var row = '<tr>' +
                            '<td>' + offer.min_quantity + '</td>' +
                            '<td>' + offer.offer_price + ' (' + offer.price_type + ')</td>' +
                            '<td>' + offer.price_type + '</td>' +
                            '<td>' + $('#location_id option:selected').text() + '</td>' +
                            '<td>' + (offer.is_active ? 
                                '<span class="status-badge status-active">{{ __("lang_v1.active") }}</span>' : 
                                '<span class="status-badge status-inactive">{{ __("lang_v1.inactive") }}</span>') + '</td>' +
                            '<td>' +
                            '<button class="btn btn-xs btn-primary edit-offer" data-id="' + offer.id + '">' +
                            '<i class="fa fa-edit"></i></button> ' +
                            '<button class="btn btn-xs btn-danger delete-offer" data-id="' + offer.id + '">' +
                            '<i class="fa fa-trash"></i></button>' +
                            '</td>' +
                            '</tr>';
                        tbody.append(row);
                    });
                } else {
                    $('#existing_offers').hide();
                }
            }
        });
    }

    // جلب السعر الأساسي
    function getBasePrice(variation_id) {
        $.ajax({
            url: "{{ url('/products/get-variation-price') }}",
            data: { variation_id: variation_id },
            success: function(response) {
                if (response.success) {
                    $('#base_price_display').val(response.price_formatted);
                    calculateExample();
                }
            }
        });
    }

    // تغيير الموقع
    $('#location_id').on('change', function() {
        var variation_id = $('#variation_id').val();
        if (variation_id) {
            loadExistingOffers(variation_id);
        }
    });

    // تحديث مثال الحساب عند تغيير السعر
    $('#offer_price').on('keyup change', function() {
        calculateExample();
    });

    // حفظ العرض
    $('#offer_form').on('submit', function(e) {
        e.preventDefault();
        
        var formData = $(this).serialize();
        
        $.ajax({
            url: "{{ route('product-offers.store') }}",
            method: 'POST',
            data: formData,
            success: function(response) {
                if (response.success) {
                    toastr.success(response.msg);
                    $('#offer_form')[0].reset();
                    $('#product_info').hide();
                    $('#existing_offers').hide();
                    $('#product_search').val(null).trigger('change');
                    offers_table.ajax.reload();
                } else {
                    toastr.error(response.msg);
                }
            },
            error: function(xhr) {
                toastr.error("{{ __('messages.something_went_wrong') }}");
            }
        });
    });

    // زر التعديل في الجدول
    $(document).on('click', '.edit-btn', function() {
        var offerId = $(this).data('id');
        
        $.ajax({
            url: "{{ url('product-offers') }}/" + offerId + "/edit",
            success: function(response) {
                if (response.success) {
                    $('#edit_offer_modal').html(response.html).modal('show');
                } else {
                    toastr.error(response.msg);
                }
            }
        });
    });

    // زر الحذف في الجدول
    $(document).on('click', '.delete-btn', function() {
        var href = $(this).data('href');
        
        confirmModal({
            title: "{{ __('messages.are_you_sure') }}",
            body: "{{ __('lang_v1.delete_offer_confirmation') }}",
            action: function() {
                $.ajax({
                    url: href,
                    method: 'DELETE',
                    success: function(response) {
                        if (response.success) {
                            toastr.success(response.msg);
                            offers_table.ajax.reload();
                        } else {
                            toastr.error(response.msg);
                        }
                    }
                });
            }
        });
    });

    // خيارات الاستيراد
    $('.import-option').on('click', function() {
        $('.import-option').removeClass('active');
        $(this).addClass('active');
        $(this).find('input[type="radio"]').prop('checked', true);
    });

    // تحديث نص المساعدة للسعر أول مرة
    updatePriceHelpText();

    // تهيئة datepicker
    if ($.fn.datepicker) {
        $('.datepicker').datepicker({
            autoclose: true,
            format: datepicker_date_format,
            todayHighlight: true,
            language: "{{ app()->getLocale() }}"
        });
    }

    // إعادة تعيين النموذج
    $('#reset_form').on('click', function() {
        $('#offer_form')[0].reset();
        $('#product_info').hide();
        $('#existing_offers').hide();
        $('#product_search').val(null).trigger('change');
        updatePriceHelpText();
    });
});

// تنسيق عرض المنتج في Select2
function formatProduct(product) {
    if (!product.id) {
        return product.text;
    }
    
    var $result = $(
        '<div><strong>' + product.product_name + '</strong>' + 
        (product.variation_name && product.variation_name != 'DUMMY' ? 
            ' - ' + product.variation_name : '') +
        '<br><small class="text-muted">' + product.sub_sku + '</small></div>'
    );
    
    return $result;
}

function formatProductSelection(product) {
    if (!product.id) {
        return product.text;
    }
    
    return product.text;
}
</script>
@endsection