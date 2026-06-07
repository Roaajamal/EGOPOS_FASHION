@php
    $custom_labels = json_decode(session('business.custom_labels'), true);
    $p_labels = $custom_labels['product'] ?? [];
@endphp

@extends('layouts.app')
@section('title', __('product.import_products'))

@section('content')

<style>
    /* ====== Import Products Page Styles ====== */
    .import-page-header {
        margin-bottom: 1.5rem;
        padding-bottom: 1rem;
        border-bottom: 1px solid #e8e8e8;
    }
    .import-page-header h1 {
        font-size: 22px;
        font-weight: 600;
        color: #1a1a1a;
        margin: 0;
    }

    /* Cards */
    .import-card {
        background: #fff;
        border: 1px solid #e4e4e4;
        border-radius: 8px;
        padding: 1.25rem 1.5rem;
        margin-bottom: 1.25rem;
        box-shadow: 0 1px 3px rgba(0,0,0,.04);
    }
    .import-card-title {
        font-size: 12px;
        font-weight: 600;
        color: #888;
        text-transform: uppercase;
        letter-spacing: .05em;
        margin: 0 0 1rem;
    }

    /* Settings grid */
    .import-settings-grid {
        display: grid;
        grid-template-columns: 1fr 1fr 1fr;
        gap: 1rem;
        align-items: end;
    }
    @media (max-width: 768px) {
        .import-settings-grid { grid-template-columns: 1fr; }
    }

    .import-form-group {
        display: flex;
        flex-direction: column;
        gap: 5px;
    }
    .import-form-group label {
        font-size: 13px;
        font-weight: 500;
        color: #555;
        margin: 0;
    }
    .import-form-group .form-control {
        border-radius: 6px;
        border: 1px solid #d0d0d0;
        font-size: 13px;
        height: 38px;
        padding: 6px 10px;
        transition: border-color .15s;
    }
    .import-form-group .form-control:focus {
        border-color: #4a90d9;
        box-shadow: 0 0 0 2px rgba(74,144,217,.15);
        outline: none;
    }
    .import-form-group input[type="file"] {
        border-radius: 6px;
        border: 1px dashed #c0c0c0;
        background: #fafafa;
        font-size: 13px;
        padding: 7px 10px;
        cursor: pointer;
        width: 100%;
        height: auto;
    }
    .import-form-group input[type="file"]:hover {
        border-color: #4a90d9;
        background: #f0f6ff;
    }

    /* Checkbox row */
    .import-checkbox-row {
        display: flex;
        align-items: center;
        gap: 8px;
        background: #f7f7f7;
        border: 1px solid #e4e4e4;
        border-radius: 6px;
        padding: 9px 12px;
    }
    .import-checkbox-row input[type="checkbox"] {
        width: 15px;
        height: 15px;
        margin: 0;
        cursor: pointer;
        flex-shrink: 0;
    }
    .import-checkbox-row span {
        font-size: 13px;
        color: #555;
        line-height: 1.3;
    }

    /* Divider */
    .import-divider {
        height: 1px;
        background: #f0f0f0;
        margin: 1rem 0;
    }

    /* Buttons row */
    .import-btn-row {
        display: flex;
        align-items: center;
        gap: 10px;
        flex-wrap: wrap;
    }
    .btn-import-primary {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        background: #4a90d9;
        color: #fff !important;
        border: none;
        border-radius: 6px;
        padding: 8px 18px;
        font-size: 13px;
        font-weight: 500;
        cursor: pointer;
        transition: background .15s, transform .1s;
        text-decoration: none;
    }
    .btn-import-primary:hover {
        background: #3a7bc8;
        transform: translateY(-1px);
        color: #fff !important;
    }
    .btn-import-success {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        background: #52a352;
        color: #fff !important;
        border: none;
        border-radius: 6px;
        padding: 8px 18px;
        font-size: 13px;
        font-weight: 500;
        cursor: pointer;
        transition: background .15s;
        text-decoration: none;
    }
    .btn-import-success:hover {
        background: #428042;
        color: #fff !important;
    }
    .btn-import-save {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        background: #4a90d9;
        color: #fff;
        border: none;
        border-radius: 6px;
        padding: 10px 32px;
        font-size: 14px;
        font-weight: 600;
        cursor: pointer;
        transition: background .15s, transform .1s;
        min-width: 140px;
        justify-content: center;
    }
    .btn-import-save:hover {
        background: #3a7bc8;
        transform: translateY(-1px);
    }

    /* Table wrapper */
    .import-table-wrap {
        overflow-x: auto;
        border-radius: 6px;
        border: 1px solid #e4e4e4;
    }
    #add_products_table {
        width: 100%;
        border-collapse: collapse;
        font-size: 13px;
        margin: 0;
    }
    #add_products_table thead tr {
        background: #f5f5f5;
    }
    #add_products_table th {
        padding: 10px 14px;
        font-weight: 600;
        color: #555;
        border-bottom: 1px solid #e4e4e4;
        white-space: nowrap;
        font-size: 12px;
        text-transform: uppercase;
        letter-spacing: .03em;
    }
    #add_products_table td {
        padding: 10px 14px;
        border-bottom: 1px solid #f0f0f0;
        color: #333;
    }
    #add_products_table tbody tr:last-child td {
        border-bottom: none;
    }
    #add_products_table tbody tr:hover {
        background: #fafbff;
    }

    /* Empty state */
    .import-empty-state {
        text-align: center;
        padding: 2.5rem 1rem;
        color: #aaa;
        font-size: 13px;
    }
    .import-empty-state i {
        font-size: 28px;
        display: block;
        margin-bottom: 8px;
        color: #ccc;
    }

    /* Stats row */
    .import-stats-row {
        display: flex;
        gap: 12px;
        justify-content: flex-end;
        margin-top: 1rem;
    }
    .import-stat-box {
        background: #f7f7f7;
        border: 1px solid #e8e8e8;
        border-radius: 6px;
        padding: 10px 20px;
        text-align: center;
        min-width: 140px;
    }
    .import-stat-box .stat-label {
        font-size: 11px;
        color: #888;
        text-transform: uppercase;
        letter-spacing: .04em;
        margin-bottom: 4px;
    }
    .import-stat-box .stat-val {
        font-size: 22px;
        font-weight: 600;
        color: #333;
    }

    /* Notes + save section */
    .import-bottom-grid {
        display: grid;
        grid-template-columns: 1fr 2fr;
        gap: 1.5rem;
        align-items: start;
        margin-top: .5rem;
    }
    @media (max-width: 768px) {
        .import-bottom-grid { grid-template-columns: 1fr; }
    }
    .import-bottom-grid textarea.form-control {
        border-radius: 6px;
        border: 1px solid #d0d0d0;
        font-size: 13px;
        resize: vertical;
        min-height: 80px;
        padding: 8px 10px;
    }
    .import-save-col {
        display: flex;
        align-items: flex-end;
        justify-content: flex-end;
        padding-bottom: 2px;
    }

    /* Alerts */
    .import-alert {
        border-radius: 6px;
        border: 1px solid #f0a0a0;
        background: #fff5f5;
        color: #a33;
        padding: 10px 16px;
        font-size: 13px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 1rem;
    }
</style>

<section class="content-header">
    <div class="import-page-header">
        <h1>@lang('product.import_products')</h1>
    </div>
</section>

<section class="content">

    @if (session('notification') || !empty($notification))
        <div class="import-alert">
            <span>
                @if(!empty($notification['msg']))
                    {{ $notification['msg'] }}
                @elseif(session('notification.msg'))
                    {{ session('notification.msg') }}
                @endif
            </span>
            <button type="button" onclick="this.closest('.import-alert').remove()"
                    style="background:none;border:none;cursor:pointer;color:#a33;font-size:18px;line-height:1;padding:0 2px">&times;</button>
        </div>
    @endif

    {{-- ===== Card 1: Settings ===== --}}
    <div class="import-card">
        <p class="import-card-title">إعدادات الاستيراد</p>

        <div class="import-settings-grid">

            {{-- File input --}}
            <div class="import-form-group">
                {!! Form::label('products_csv', __('product.file_to_import') . ':') !!}
                {!! Form::file('products_csv', [
                    'id'       => 'products_csv',
                    'accept'   => '.xls, .xlsx, .csv',
                    'required' => 'required',
                ]) !!}
            </div>

            {{-- Location --}}
            <div class="import-form-group">
                {!! Form::label('location_id', __('quantity_entry.location')) !!}
                {!! Form::select('location_id', $business_locations, null, [
                    'class'    => 'form-control select2',
                    'required' => 'required',
                ]) !!}
            </div>

            {{-- Select all locations checkbox --}}
            <div style="display:flex;flex-direction:column;justify-content:flex-end">
                <div class="import-checkbox-row">
                    {!! Form::checkbox('select_all_location', 1, false, [
                        'id'    => 'select_all_location',
                        'class' => 'input-sm',
                    ]) !!}
                    <span>@lang('product.select_all_location')</span>
                </div>
            </div>

        </div>

        <div class="import-divider"></div>

        <div class="import-btn-row">
            <button type="button" id="preview_btn" class="btn-import-primary">
                <i class="fa fa-eye"></i> Preview
            </button>
            <a href="{{ asset('files/add_products_csv_template.xls') }}"
               class="btn-import-success" download>
                <i class="fa fa-download"></i> @lang('lang_v1.download_template_file')
            </a>
        </div>
    </div>

    {{-- ===== Card 2: Preview & Save ===== --}}
    <div class="import-card">
        <p class="import-card-title">معاينة البيانات</p>

        {{-- Missing product warning --}}
        <div class="missing-product-warning"></div>

        <div id="preview_section" style="margin-top:4px">

            {{-- Table --}}
            <div class="import-table-wrap">
                <table id="add_products_table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>SKU</th>
                            <th>{{ __('product.product') }}</th>
                            <th>{{ __('product.unit') }}</th>
                            <th>{{ __('product.category') }}</th>
                            <th>{{ __('product.tax') }}</th>
                            <th>{{ __('product.tax_type') }}</th>
                            <th>{{ __('product.purchase_price_inc_tax') }}</th>
                            <th>{{ __('product.selling_price_inc_tax') }}</th>
                            <th>{{ __('product.opening_stock') }}</th>
                            @for ($i = 1; $i <= 20; $i++)
                                @if (!empty($p_labels['custom_field_' . $i]))
                                    <th data-field="custom_field_{{ $i }}">
                                        {{ $p_labels['custom_field_' . $i] }}
                                    </th>
                                @endif
                            @endfor
                            <th><i class="fa fa-trash"></i></th>
                        </tr>
                    </thead>
                    <tbody id="preview_tbody">
                        <tr>
                            <td colspan="20">
                                <div class="import-empty-state">
                                    <i class="fa fa-upload"></i>
                                    اختر ملفاً واضغط "Preview" لعرض البيانات
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            {{-- Stats --}}
            <div class="import-stats-row">
                <div class="import-stat-box">
                    <div class="stat-label">{{ __('product.total_products') }}</div>
                    <div class="stat-val"><span id="total_products">0</span></div>
                </div>
                <div class="import-stat-box">
                    <div class="stat-label">{{ __('product.total_of_quantity') }}</div>
                    <div class="stat-val"><span id="total_quantity">0</span></div>
                </div>
            </div>

            <div class="import-divider"></div>

            {{-- Notes + Save --}}
            <form method="POST" action="{{ route('add-products.store') }}">
                @csrf
                <input type="hidden" name="rows_json"           id="rows_json_input">
                <input type="hidden" name="location_id"         id="form_location_id">
                <input type="hidden" name="select_all_location" id="form_select_all_location" value="0">

                <div class="import-bottom-grid">
                    <div class="import-form-group">
                        {!! Form::label('notes', __('product.notes') . ':') !!}
                        {!! Form::textarea('notes', null, [
                            'class'       => 'form-control',
                            'name'        => 'notes',
                            'id'          => 'notes',
                            'placeholder' => __('product.notes'),
                            'rows'        => 3,
                        ]) !!}
                    </div>
                    <div class="import-save-col">
                        <button type="submit" id="submit_save_btn" class="btn-import-save save-products-btn">
                            <i class="fa fa-save submit-icon"></i>
                            <i class="fa fa-spinner fa-spin loading-icon" style="display:none"></i>
                            <span class="btn-text">@lang('messages.save')</span>
                        </button>
                    </div>
                </div>
            </form>

        </div>

        <input type="hidden" id="row_count" value="0">
    </div>

</section>

{{-- ===== Modal: Duplicate Products ===== --}}
<div class="modal fade" id="duplicate_modal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content" style="border-radius:8px;border:none;box-shadow:0 4px 24px rgba(0,0,0,.15)">
            <div class="modal-header" style="border-bottom:1px solid #f0f0f0;padding:14px 20px">
                <h4 class="modal-title" style="font-size:15px;font-weight:600;margin:0">منتجات موجودة مسبقاً</h4>
            </div>
            <div class="modal-body" style="padding:16px 20px">
                <p style="font-size:13px;color:#666;margin-bottom:12px">المنتجات التالية موجودة في النظام — حدد القرار لكل منتج:</p>
                <div id="duplicate_bulk_table"></div>
                <div id="modal_location_warning"   style="display:none"></div>
                <div id="modal_differences_section" style="display:none">
                    <table><tbody id="modal_differences_body"></tbody></table>
                </div>
            </div>
            <div class="modal-footer" style="border-top:1px solid #f0f0f0;padding:12px 20px;display:flex;gap:8px;justify-content:flex-end">
                <button type="button" class="btn btn-default" id="modal_ignore_all" style="border-radius:6px;font-size:13px">
                    <i class="fa fa-ban"></i> تجاهل الكل
                </button>
                <button type="button" class="btn btn-primary" id="modal_apply_all" style="border-radius:6px;font-size:13px">
                    <i class="fa fa-check"></i> تطبيق القرارات
                </button>
            </div>
        </div>
    </div>
</div>

{{-- ===== Modal: Import Details ===== --}}
<div class="modal fade" id="import_details_modal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content" style="border-radius:8px;border:none;box-shadow:0 4px 24px rgba(0,0,0,.15)">
            <div class="modal-header" style="border-bottom:1px solid #f0f0f0;padding:14px 20px">
                <button type="button" class="close" data-dismiss="modal" style="font-size:20px">&times;</button>
                <h4 class="modal-title" style="font-size:15px;font-weight:600;margin:0">تفاصيل الاستيراد</h4>
            </div>
            <div class="modal-body" style="padding:16px 20px">
                <div id="import_details_content">
                    <i class="fa fa-spinner fa-spin"></i> Loading...
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Column mapping hidden input --}}
<input type="hidden" name="column_mapping" id="column_mapping_input">

{{-- ===== Modal: Column Mapping ===== --}}
<div class="modal fade" id="mapping_modal" tabindex="-1" role="dialog" data-backdrop="static" data-keyboard="false">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content" style="border-radius:8px;border:none;box-shadow:0 4px 24px rgba(0,0,0,.15)">
            <div class="modal-header" style="border-bottom:1px solid #f0f0f0;padding:14px 20px">
                <h4 class="modal-title" style="font-size:15px;font-weight:600;margin:0">ربط الأعمدة</h4>
            </div>
            <div class="modal-body" style="padding:16px 20px">
                <p class="text-muted" style="font-size:13px;margin-bottom:12px">اربط كل حقل بالعمود المناسب في ملف الإكسل</p>
                <div id="mapping_fields_container"></div>
            </div>
            <div class="modal-footer" style="border-top:1px solid #f0f0f0;padding:12px 20px;display:flex;gap:8px;justify-content:flex-end">
                <button type="button" class="btn btn-default" data-dismiss="modal" style="border-radius:6px;font-size:13px">إلغاء</button>
                <button type="button" class="btn btn-primary" id="apply_mapping_btn" style="border-radius:6px;font-size:13px">
                    <i class="fa fa-check"></i> تطبيق وعرض البيانات
                </button>
            </div>
        </div>
    </div>
</div>

{{-- ===== Modal: Error Edit ===== --}}
<div class="modal fade" id="error_edit_modal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content" style="border-radius:8px;border:none;box-shadow:0 4px 24px rgba(0,0,0,.15)">
            <div class="modal-header" style="background:#fff5f5;border-bottom:1px solid #f5c0c0;padding:14px 20px;border-radius:8px 8px 0 0">
                <h4 class="modal-title" style="color:#a32d2d;font-size:15px;font-weight:600;margin:0">
                    <i class="fa fa-exclamation-triangle"></i> خطأ في الاستيراد
                </h4>
                <span class="pull-right label label-default" id="error_modal_row_no"
                      style="font-size:12px;border-radius:4px;padding:3px 8px;margin-top:2px"></span>
            </div>
            <div class="modal-body" style="padding:16px 20px">
                <div class="alert alert-danger" id="error_modal_message"
                     style="font-size:13px;border-radius:6px;margin-bottom:12px"></div>
                <p class="text-muted" style="font-size:12px;margin-bottom:10px">
                    عدّل البيانات أدناه ثم اضغط "تصحيح والمتابعة"، أو تجاهل هذا الصف وأكمل الباقي.
                </p>
                <div id="error_modal_fields"></div>
            </div>
            <div class="modal-footer" style="border-top:1px solid #f0f0f0;padding:12px 20px;display:flex;gap:8px;justify-content:flex-end">
                <button type="button" class="btn btn-danger" id="error_modal_ignore" style="border-radius:6px;font-size:13px">
                    <i class="fa fa-ban"></i> تجاهل هذا الصف والمتابعة
                </button>
                <button type="button" class="btn btn-primary" id="error_modal_apply" style="border-radius:6px;font-size:13px">
                    <i class="fa fa-check"></i> تصحيح والمتابعة
                </button>
            </div>
        </div>
    </div>
</div>

@endsection

@section('javascript')
<script>
    var businessSettings    = @json($business->custom_product_settings ?? []);
    var pLabels             = @json($p_labels ?? []);
    var importPreviewUrl    = "{{ route('import-products.preview') }}";
    var importGetHeadersUrl = "{{ route('import-products.get-headers') }}";
    var importStoreUrl      = "{{ route('add-products.store') }}";
    var csrfToken           = "{{ csrf_token() }}";

    var addProductLabels = {
        description:            '{{ __("add_product.description") }}',
        unit:                   '{{ __("add_product.unit") }}',
        brand:                  '{{ __("add_product.brand") }}',
        category:               '{{ __("add_product.category") }}',
        product_type:           '{{ __("add_product.product_type") }}',
        barcode_type:           '{{ __("add_product.barcode_type") }}',
        opening_stock:          '{{ __("add_product.opening_stock") }}',
        enable_stock:           '{{ __("add_product.enable_stock") }}',
        profit_margin:          '{{ __("add_product.profit_margin") }}',
        purchase_price_inc_tax: '{{ __("add_product.purchase_price_inc_tax") }}',
        purchase_price_exc_tax: '{{ __("add_product.purchase_price_exc_tax") }}',
        sub_category:           '{{ __("add_product.sub_category") }}',
        tax:                    '{{ __("add_product.tax") }}',
        tax_type:               '{{ __("add_product.tax_type") }}',
        variation_name:         '{{ __("add_product.variation_name") }}',
        variation_values:       '{{ __("add_product.variation_values") }}',
        variation_skus:         '{{ __("add_product.variation_skus") }}',
        alert_quantity:         '{{ __("add_product.alert_quantity") }}',
        not_for_selling:        '{{ __("add_product.not_for_selling") }}',
        expiry_period:          '{{ __("add_product.expiry_period") }}',
        expiry_period_type:     '{{ __("add_product.expiry_period_type") }}',
        expiry_date:            '{{ __("add_product.expiry_date") }}',
        weight:                 '{{ __("add_product.weight") }}',
        rack:                   '{{ __("add_product.rack") }}',
        row:                    '{{ __("add_product.row") }}',
        position:               '{{ __("add_product.position") }}',
        image:                  '{{ __("add_product.image") }}',
        enable_serial_number:   '{{ __("add_product.enable_serial_number") }}',
        selling_price:          '{{ __("add_product.selling_price") }}',
    };
</script>
<script src="{{ asset('js/add_product.js') }}"></script>
@endsection