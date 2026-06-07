window.addEventListener('load', function() {

    var previewData           = [];
  //  var currentDuplicateIndex = 0;
    var duplicateQueue        = [];
    var excelHeaders          = [];
    var columnMapping         = {};

     
    // ── إعدادات الـ Business ──────────────────────────────
    // يجب تعريف هذين المتغيرين في الـ blade قبل تضمين هذا الملف:
    // <script>
    //   var businessSettings = @json($business->custom_product_settings ?? []);
    //   var pLabels = @json($p_labels ?? []);
    //   var importPreviewUrl = "{{ route('import-products.preview') }}";
    //   var importGetHeadersUrl = "{{ route('import-products.get-headers') }}";
    //   var importStoreUrl = "{{ route('add-products.store') }}";
    //   var csrfToken = "{{ csrf_token() }}";
    // </script>

    // ── بناء mappingFields ────────────────────────────────
    var mappingFields = {
        'sku':          'SKU *',
        'name':         'Name *',
        'selling_price':'Selling Price *',
    };

   if (businessSettings.show_product_description == 1) {
    mappingFields['description']            = addProductLabels.description;
}
if (!businessSettings.default_unit) {
    mappingFields['unit']   = addProductLabels.unit + ' *';
}

if (businessSettings.enable_brand == 1) {
    mappingFields['brand']                  = addProductLabels.brand;
}


if (businessSettings.enable_category == 1) {
    mappingFields['category']               = addProductLabels.category;
}

if (businessSettings.show_product_type == 1) { 
    mappingFields['product_type']           = addProductLabels.product_type;
}
if (businessSettings.show_barcode_type == 1) { 
    mappingFields['barcode_type']           = addProductLabels.barcode_type;
}
if (businessSettings.show_opening_stock == 1) { 
    mappingFields['opening_stock']          = addProductLabels.opening_stock;
}
if (businessSettings.show_manage_stock == 1) { 
    mappingFields['enable_stock']           = addProductLabels.enable_stock;
}
if (businessSettings.show_profit_margin == 1) { 
    mappingFields['profit_margin']          = addProductLabels.profit_margin;
}
if (businessSettings.show_purchase_price == 1) { 
    mappingFields['dpp_inc_tax']            = addProductLabels.purchase_price_inc_tax;
}

if (businessSettings.enable_sub_category == 1) {
   mappingFields['sub_category']           = addProductLabels.sub_category;
}

if (businessSettings.enable_price_tax == 1) {
    mappingFields['tax']                    = addProductLabels.tax;
    mappingFields['tax_type']               = addProductLabels.tax_type;
}

if (businessSettings.enable_single_product !== 1) {
    mappingFields['variation_name']         = addProductLabels.variation_name;
    mappingFields['variation_values']       = addProductLabels.variation_values;
    mappingFields['variation_skus']         = addProductLabels.variation_skus;
}
 
if (businessSettings.show_alert_quantity == 1) {
    mappingFields['alert_quantity']         = addProductLabels.alert_quantity;
}
if (businessSettings.show_not_for_selling == 1) {
    mappingFields['not_for_selling']        = addProductLabels.not_for_selling;
}
if (businessSettings.enable_product_expiry == 1) {
    mappingFields['expiry_period']          = addProductLabels.expiry_period;
    mappingFields['expiry_period_type']     = addProductLabels.expiry_period_type;
    mappingFields['expiry_date']            = addProductLabels.expiry_date;
}
if (businessSettings.show_product_weight == 1) {
    mappingFields['weight']                 = addProductLabels.weight;
}
if (businessSettings.show_enable_racks == 1) {
    mappingFields['rack']                   = addProductLabels.rack;
}
if (businessSettings.show_enable_rows == 1 ) {
    mappingFields['row']                    = addProductLabels.row;
    mappingFields['position']               = addProductLabels.position;
}
if (businessSettings.show_product_image == 1) {
    mappingFields['image']                  = addProductLabels.image;
}
if (businessSettings.show_product_serial_number == 1) {
    mappingFields['enable_sr_no']           = addProductLabels.enable_serial_number;
}
if (businessSettings.show_purchase_price_exc == 1) {
    mappingFields['dpp_exc_tax']            = addProductLabels.purchase_price_exc_tax;
}

// ── الحقول المخصصة المفعلة ───────────────────────
for (var i = 1; i <= 20; i++) {
    var label = pLabels['custom_field_' + i];
    if (label) {
        // ✅ custom_field_1 و custom_field_2 فقط لو enable_product_size_color مفعل
        if (i === 1 || i === 2) {
            if (businessSettings.enable_product_size_color == 1) {
                mappingFields['custom_field_' + i] = label;
            }
        } else {
            mappingFields['custom_field_' + i] = label;
        }
    }
}
    // الحقول المطلوبة
    var requiredFields = ['name', 'sku', 'selling_price'];
    if (!businessSettings.default_unit) {
        requiredFields.push('unit');
    }

    var beforeUnloadHandler = function(e) {
    if (previewData.length > 0) {
        e.preventDefault();
        e.returnValue = '';
    }
};

    window.addEventListener('beforeunload', beforeUnloadHandler);

    // ── زر Preview ────────────────────────────────────────
    document.getElementById('preview_btn').addEventListener('click', function() {
        var fileInput = document.getElementById('products_csv');
        if (!fileInput.files.length) {
            alert('Please select a file first.');
            return;
        }

        var formData = new FormData();
        formData.append('products_csv', fileInput.files[0]);
        formData.append('_token', csrfToken);

        var btn = document.getElementById('preview_btn');
        btn.disabled  = true;
        btn.innerHTML = '<i class="fa fa-spinner fa-spin"></i> Loading...';

        fetch(importGetHeadersUrl, { method: 'POST', body: formData })
            .then(function(res) { return res.json(); })
            .then(function(response) {
                btn.disabled  = false;
                btn.innerHTML = '<i class="fa fa-eye"></i> Preview';

                if (!response.success) { alert(response.msg); return; }

                excelHeaders = response.headers;
                buildMappingModal(excelHeaders);
                $('#mapping_modal').modal('show');
            })
            .catch(function() {
                btn.disabled  = false;
                btn.innerHTML = '<i class="fa fa-eye"></i> Preview';
                alert('Something went wrong.');
            });
    });

    // ── بناء Modal الـ Mapping ────────────────────────────
    function buildMappingModal(headers) {
        var container = document.getElementById('mapping_fields_container');
        var html      = '<div class="row">';

        Object.keys(mappingFields).forEach(function(fieldKey) {
            var fieldLabel = mappingFields[fieldKey];
            var autoMatch  = '';

            headers.forEach(function(h, i) {
                var clean = fieldLabel.replace(' *', '').toLowerCase().trim();
                if (h.toLowerCase().trim() === clean) autoMatch = i;
            });

            var options = headers.map(function(h, i) {
                var selected = (String(i) === String(autoMatch)) ? ' selected' : '';
                return '<option value="' + i + '"' + selected + '>' + h + '</option>';
            });

            html += '<div class="col-md-6" style="margin-bottom:10px;">' +
                        '<label>' + fieldLabel + '</label>' +
                        '<select class="form-control mapping-select" data-field="' + fieldKey + '">' +
                            '<option value="">-- لا يوجد --</option>' +
                            options.join('') +
                        '</select>' +
                    '</div>';
        });

        html += '</div>';
        container.innerHTML = html;

        // إخفاء حقول variation افتراضياً
        var variationKeys = ['variation_name', 'variation_values', 'variation_skus'];
        variationKeys.forEach(function(key) {
            var sel = container.querySelector('[data-field="' + key + '"]');
            if (sel) sel.closest('.col-md-6').style.display = 'none';
        });

        var productTypeSelect = container.querySelector('[data-field="product_type"]');
        if (productTypeSelect) {
            productTypeSelect.addEventListener('change', function() {
                var hasValue = this.value !== '';
                variationKeys.forEach(function(key) {
                    var sel = container.querySelector('[data-field="' + key + '"]');
                    if (sel) sel.closest('.col-md-6').style.display = hasValue ? '' : 'none';
                });
            });
        }
    }

    // ── تطبيق الـ Mapping ─────────────────────────────────
    document.getElementById('apply_mapping_btn').addEventListener('click', function() {
        columnMapping = {};
        document.querySelectorAll('.mapping-select').forEach(function(select) {
            if (select.value !== '') {
                columnMapping[select.getAttribute('data-field')] = parseInt(select.value);
            }
        });

        var missing = [];
        requiredFields.forEach(function(f) {
            if (columnMapping[f] === undefined) missing.push(mappingFields[f]);
        });

        if (missing.length > 0) {
            alert('الحقول التالية مطلوبة:\n' + missing.join('\n'));
            return;
        }

        document.getElementById('column_mapping_input').value = JSON.stringify(columnMapping);
        $('#mapping_modal').modal('hide');
        $('.modal-backdrop').remove();
        $('body').removeClass('modal-open');
        doPreview();
    });

    // ── Preview بعد الـ Mapping ───────────────────────────
    function doPreview() {
        var fileInput = document.getElementById('products_csv');
        var formData  = new FormData();
        formData.append('products_csv', fileInput.files[0]);
        formData.append('column_mapping', JSON.stringify(columnMapping));
        formData.append('_token', csrfToken);

        var locationSelect = document.querySelector('select[name="location_id"]');
        if (locationSelect) formData.append('location_id', locationSelect.value);

        var btn       = document.getElementById('preview_btn');
        btn.disabled  = true;
        btn.innerHTML = '<i class="fa fa-spinner fa-spin"></i> Loading...';

        fetch(importPreviewUrl, { method: 'POST', body: formData })
            .then(function(res) { return res.json(); })
            .then(function(response) {
                btn.disabled  = false;
                btn.innerHTML = '<i class="fa fa-eye"></i> Preview';

                if (!response.success) { alert(response.msg); return; }

                previewData = response.data;
                renderTable();
                document.getElementById('preview_section').style.display = 'block';

                duplicateQueue = previewData
                    .map(function(r, i) { return r.is_duplicate ? i : -1; })
                    .filter(function(i) { return i !== -1; });

                if (duplicateQueue.length > 0) {
                    processAllDuplicates();
                    }
            })
            .catch(function() {
                btn.disabled  = false;
                btn.innerHTML = '<i class="fa fa-eye"></i> Preview';
                alert('Something went wrong.');
            });
    }

    // ── Modal المنتج المكرر ───────────────────────────────
    function processAllDuplicates() {
    if (duplicateQueue.length === 0) return;

    var allDuplicates = duplicateQueue.map(function(idx) {
        return previewData[idx];
    });

    var tableHtml = '<table class="table table-bordered table-condensed" style="font-size:12px;">' +
        '<thead><tr>' +
            '<th>#</th>' +
            '<th>SKU</th>' +
            '<th>المنتج</th>' +
            '<th>الفرع</th>' +
            '<th>الفروقات</th>' +
            '<th>القرار</th>' +
        '</tr></thead><tbody>';

    allDuplicates.forEach(function(row, i) {
        var d = row.display;

        var diffsHtml = '-';
        if (row.differences && row.differences.length > 0) {
            diffsHtml = row.differences.map(function(diff) {
                return '<small><b>' + diff.field + ':</b> ' +
                    '<span class="text-danger">' + diff.old + '</span> → ' +
                    '<span class="text-success">' + diff.new + '</span></small>';
            }).join('<br>');
        }

        var locationStatus = row.is_in_location
            ? '<span class="label label-success">معرّف بالفرع</span>'
            : '<span class="label label-warning">غير معرّف بالفرع</span>';

        var actionOptions = '';
        if (row.is_in_location) {
            actionOptions = '<select class="form-control input-sm duplicate-action" data-index="' + previewData.indexOf(row) + '">' +
                '<option value="add_qty">زيادة الكمية</option>' +
                '<option value="ignore">تجاهل</option>' +
            '</select>';
        } else {
            actionOptions = '<select class="form-control input-sm duplicate-action" data-index="' + previewData.indexOf(row) + '">' +
                '<option value="add_to_location">تعريف بالفرع وإضافة الكمية</option>' +
                '<option value="ignore">تجاهل</option>' +
            '</select>';
        }

        tableHtml += '<tr>' +
            '<td>' + (i + 1) + '</td>' +
            '<td>' + d.sku + '</td>' +
            '<td>' + d.name + '</td>' +
            '<td>' + locationStatus + '</td>' +
            '<td>' + diffsHtml + '</td>' +
            '<td>' + actionOptions + '</td>' +
        '</tr>';
    });

    tableHtml += '</tbody></table>';
    tableHtml += '<div style="margin-top:10px;">' +
        '<label><input type="checkbox" id="modal_update_info_bulk" checked> ' +
        'تحديث معلومات المنتجات بالقيم الجديدة</label>' +
    '</div>';

    document.getElementById('modal_differences_section').style.display = 'none';
    document.getElementById('modal_location_warning').style.display = 'none';
    document.getElementById('duplicate_bulk_table').innerHTML = tableHtml;

    $('.modal-backdrop').remove();
    $('body').removeClass('modal-open');
    $('#duplicate_modal').modal({ backdrop: 'static', keyboard: false });
    $('#duplicate_modal').modal('show');
}

// ── تطبيق القرارات ────────────────────────────────────
document.getElementById('modal_apply_all').addEventListener('click', function() {
    var updateInfo = document.getElementById('modal_update_info_bulk') 
        ? document.getElementById('modal_update_info_bulk').checked 
        : true;

    document.querySelectorAll('.duplicate-action').forEach(function(select) {
        var idx    = parseInt(select.getAttribute('data-index'));
        var action = select.value;

        previewData[idx].action      = action;
        previewData[idx].update_info = updateInfo;

        updateRowStyle(previewData[idx].row_no, action);
    });

    duplicateQueue = [];
    syncJsonInput();
    updateTotals();
    $('#duplicate_modal').modal('hide');
    $('.modal-backdrop').remove();
    $('body').removeClass('modal-open');
});

// ── تجاهل الكل ───────────────────────────────────────
document.getElementById('modal_ignore_all').addEventListener('click', function() {
    document.querySelectorAll('.duplicate-action').forEach(function(select) {
        var idx = parseInt(select.getAttribute('data-index'));
        previewData[idx].action = 'ignore';
        updateRowStyle(previewData[idx].row_no, 'ignore');
    });

    duplicateQueue = [];
    syncJsonInput();
    updateTotals();
    $('#duplicate_modal').modal('hide');
    $('.modal-backdrop').remove();
    $('body').removeClass('modal-open');
});

// ── closeModalAndNext غير مستخدمة بعد الآن لكن أبقيها للتوافق ──
function closeModalAndNext() {
    $('#duplicate_modal').one('hidden.bs.modal', function() {
        $('.modal-backdrop').remove();
        $('body').removeClass('modal-open');
    });
    $('#duplicate_modal').modal('hide');
}

// // ── أزرار المنتج الفردي — غير مستخدمة لكن أبقيها ─────
// document.getElementById('modal_add_qty').addEventListener('click', function() {
//     previewData[currentDuplicateIndex].update_info =
//         document.getElementById('modal_update_info') 
//             ? document.getElementById('modal_update_info').checked 
//             : true;
//     previewData[currentDuplicateIndex].action = 'add_qty';
//     updateRowStyle(previewData[currentDuplicateIndex].row_no, 'add_qty');
//     syncJsonInput();
//     closeModalAndNext();
// });

// document.getElementById('modal_ignore').addEventListener('click', function() {
//     previewData[currentDuplicateIndex].action = 'ignore';
//     updateRowStyle(previewData[currentDuplicateIndex].row_no, 'ignore');
//     syncJsonInput();
//     updateTotals();
//     closeModalAndNext();
// });

// document.getElementById('modal_add_to_location').addEventListener('click', function() {
//     previewData[currentDuplicateIndex].action = 'add_to_location';
//     updateRowStyle(previewData[currentDuplicateIndex].row_no, 'add_to_location');
//     syncJsonInput();
//     closeModalAndNext();
// });
    // ── تحديث شكل الصف ───────────────────────────────────
    function updateRowStyle(rowNo, action) {
        var tr = document.getElementById('preview_row_' + rowNo);
        if (!tr) return;

        if (action === 'add_qty') {
            tr.style.backgroundColor = '#d4edda';
            tr.querySelector('.duplicate_badge').innerHTML =
                '<span class="label label-success">سيتم زيادة الكمية</span>';
        } else if (action === 'add_to_location') {
            tr.style.backgroundColor = '#cce5ff';
            tr.querySelector('.duplicate_badge').innerHTML =
                '<span class="label label-info">سيتم تعريفه بالفرع وإضافة الكمية</span>';
        } else if (action === 'ignore') {
            tr.remove();
        }
    }

    // ── بناء الجدول ───────────────────────────────────────
    function renderTable() {
        var tbody = document.getElementById('preview_tbody');
        tbody.innerHTML = '';

        if (previewData.length === 0) {
            tbody.innerHTML = '<tr><td colspan="12" class="text-center">No data found</td></tr>';
            updateTotals();
            return;
        }

        previewData.forEach(function(row) {
            var d  = row.display;
            var tr = document.createElement('tr');
            tr.id  = 'preview_row_' + row.row_no;

            if (row.is_duplicate) tr.style.backgroundColor = '#fff3cd';
            if (row.action === 'ignore') return;

            var skuCell = row.is_duplicate
                ? d.sku + ' <span class="label label-warning">موجود</span>'
                : d.sku;

            var duplicateBadge = row.is_duplicate
                ? '<span class="duplicate_badge"><span class="label label-warning">بانتظار القرار</span></span>'
                : '';

            var customFieldsHtml = '';
            for (var i = 1; i <= 20; i++) {
                var th = document.querySelector(
                    '#add_products_table thead th[data-field="custom_field_' + i + '"]'
                );
                if (th) {
                    var fieldVal = d['custom_field_' + i] !== undefined ? d['custom_field_' + i] : '';
                    customFieldsHtml += '<td>' + fieldVal + '</td>';
                }
            }

            tr.innerHTML =
                '<td>' + row.row_no + '</td>' +
                '<td>' + skuCell + '</td>' +
                '<td>' + d.name + '</td>' +
                '<td>' + (d.unit || '-') + '</td>' +
                '<td>' + (d.category || '-') + '</td>' +
                '<td>' + (d.tax || '-') + '</td>' +
                '<td>' + (d.tax_type || '-') + '</td>' +
                '<td>' + (d.purchase_price || '-') + '</td>' +
                '<td>' + (d.selling_price || '-') + '</td>' +
                '<td>' + (d.opening_stock || '-') + '</td>' +
                customFieldsHtml +
                '<td>' +
                    duplicateBadge +
                    '<button type="button" class="btn btn-xs btn-danger remove_row" data-row="' + row.row_no + '">' +
                        '<i class="fa fa-trash"></i>' +
                    '</button>' +
                '</td>';

            tbody.appendChild(tr);
        });

        updateTotals();
        syncJsonInput();
    }

    // ── حذف صف ────────────────────────────────────────────
    document.addEventListener('click', function(e) {
        if (e.target.closest('.remove_row')) {
            var rowNo = parseInt(e.target.closest('.remove_row').getAttribute('data-row'));
            previewData = previewData.filter(function(r) { return r.row_no !== rowNo; });
            var row = document.getElementById('preview_row_' + rowNo);
            if (row) row.remove();
            syncJsonInput();
            updateTotals();
        }
    });

    // ── تحديث الإجماليات ──────────────────────────────────
    function updateTotals() {
        var activeRows = previewData.filter(function(r) {
            return r.action !== 'ignore';
        });

        document.getElementById('total_products').textContent = activeRows.length;
        document.getElementById('row_count').value            = activeRows.length;

        var totalQty = 0;
        activeRows.forEach(function(r) {
            var qty = parseFloat(r.display.opening_stock);
            if (!isNaN(qty)) totalQty += qty;
        });

        document.getElementById('total_quantity').textContent = totalQty;
    }

    // ── مزامنة الـ JSON ───────────────────────────────────
    function syncJsonInput() {
        document.getElementById('rows_json_input').value = JSON.stringify(previewData);
    }

    // ── Submit الفورم ─────────────────────────────────────
    document.querySelector('form[action]').addEventListener('submit', function() {
        window.removeEventListener('beforeunload', beforeUnloadHandler);

        var locationSelect = document.querySelector('select[name="location_id"]');
        if (locationSelect) {
            document.getElementById('form_location_id').value = locationSelect.value;
        }
        var selectAll = document.getElementById('select_all_location');
        document.getElementById('form_select_all_location').value =
            selectAll && selectAll.checked ? 1 : 0;
    });


    var mainForm = document.querySelector('form[action*="add-products/store"]');

if (mainForm) {
    mainForm.addEventListener('submit', function(e) {
        e.preventDefault();

        var locationSelect = document.querySelector('select[name="location_id"]');
        if (locationSelect) document.getElementById('form_location_id').value = locationSelect.value;
        var selectAll = document.getElementById('select_all_location');
        document.getElementById('form_select_all_location').value = selectAll && selectAll.checked ? 1 : 0;

        var btn         = document.getElementById('submit_save_btn');
        var submitIcon  = btn.querySelector('.submit-icon');
        var loadingIcon = btn.querySelector('.loading-icon');
        var btnText     = btn.querySelector('.btn-text');

        btn.disabled = true;
        if (submitIcon)  submitIcon.style.display  = 'none';
        if (loadingIcon) loadingIcon.style.display = 'inline-block';
        if (btnText)     btnText.textContent        = ' جاري الحفظ...';

        var formData = new FormData(mainForm);

        fetch(mainForm.action, {
            method: 'POST',
            body: formData,
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(function(r) { return r.json(); })
        .then(function(response) {
            if (response.success) {
                window.removeEventListener('beforeunload', beforeUnloadHandler);
                window.location.href = response.redirect || '/add-products';
            } else {
                btn.disabled = false;
                if (submitIcon)  submitIcon.style.display  = 'inline-block';
                if (loadingIcon) loadingIcon.style.display = 'none';
                if (btnText)     btnText.textContent        = ' حفظ';

                openErrorEditModal(response.msg, response.error_row);
            }
        })
        .catch(function() {
            btn.disabled = false;
            if (submitIcon)  submitIcon.style.display  = 'inline-block';
            if (loadingIcon) loadingIcon.style.display = 'none';
            if (btnText)     btnText.textContent        = ' حفظ';
            alert('حدث خطأ غير متوقع.');
        });
    });
}

function openErrorEditModal(errorMsg, errorRowNo) {
    var row = previewData.find(function(r) { return r.row_no == errorRowNo; });
    if (!row) { alert(errorMsg); return; }

    var d = row.display;

    var fieldLabels = {
        'sku':            'SKU',
        'name':           'اسم المنتج',
        'unit':           'الوحدة',
        'category':       'الفئة',
        'sub_category':   'الفئة الفرعية',
        'brand':          'الماركة',
        'tax':            'الضريبة',
        'tax_type':       'نوع الضريبة',
        'dpp_inc_tax':    'سعر الشراء شامل ضريبة',
        'dpp_exc_tax':    'سعر الشراء بدون ضريبة',
        'selling_price':  'سعر البيع',
        'opening_stock':  'الكمية',
        'profit_margin':  'هامش الربح',
        'alert_quantity': 'حد التنبيه',
        'barcode_type':   'نوع الباركود',
        'product_type':   'نوع المنتج',
        'enable_stock':   'إدارة المخزون',
        'weight':         'الوزن',
        'image':          'الصورة',
        'enable_sr_no':   'الرقم التسلسلي',
        'expiry_period':  'فترة الانتهاء',
        'expiry_period_type': 'نوع فترة الانتهاء',
        'expiry_date':    'تاريخ الانتهاء',
        'rack':           'الرف',
        'row':            'الصف',
        'position':       'الموضع',
        'not_for_selling':'غير للبيع',
        'variation_name': 'اسم التنويع',
        'variation_values':'قيم التنويع',
        'variation_skus': 'SKU التنويع',
        'description':    'الوصف',
    };

    // أضف الحقول المخصصة
    for (var i = 1; i <= 20; i++) {
        if (pLabels['custom_field_' + i]) {
            fieldLabels['custom_field_' + i] = pLabels['custom_field_' + i];
        }
    }

    // ✅ اعرض بس الحقول الموجودة في columnMapping
    var fieldsHtml = '<div class="row">';
    Object.keys(columnMapping).forEach(function(key) {
        var label = fieldLabels[key] || key;
        
        // جيب القيمة من raw مباشرة باستخدام الـ mapping
        var colIndex = columnMapping[key];
        var rawValue = (row.raw && row.raw[colIndex] !== undefined) ? row.raw[colIndex] : '';

        fieldsHtml += '<div class="form-group col-sm-6">' +
            '<label style="font-size:12px;color:#888">' + label + '</label>' +
            '<input type="text" class="form-control input-sm error-edit-field" ' +
                   'data-field="' + key + '" ' +
                   'data-col-index="' + colIndex + '" ' +
                   'value="' + rawValue + '">' +
        '</div>';
    });
    fieldsHtml += '</div>';

    document.getElementById('error_modal_message').textContent = errorMsg;
    document.getElementById('error_modal_row_no').textContent  = 'صف رقم ' + errorRowNo;
    document.getElementById('error_modal_fields').innerHTML    = fieldsHtml;
    document.getElementById('error_modal_fields').setAttribute('data-row-no', errorRowNo);

    $('#error_edit_modal').modal({ backdrop: 'static', keyboard: false });
    $('#error_edit_modal').modal('show');
}

document.getElementById('error_modal_apply').addEventListener('click', function() {
    var rowNo    = parseInt(document.getElementById('error_modal_fields').getAttribute('data-row-no'));
    var rowIndex = previewData.findIndex(function(r) { return r.row_no == rowNo; });
    if (rowIndex === -1) return;

    document.querySelectorAll('.error-edit-field').forEach(function(input) {
        var field    = input.getAttribute('data-field');
        var colIndex = parseInt(input.getAttribute('data-col-index'));
        var val      = input.value;

        // ✅ حدّث raw مباشرة بالـ index
        previewData[rowIndex].raw[colIndex] = val;

        // حدّث display أيضاً
        if (field === 'dpp_inc_tax' || field === 'dpp_exc_tax') {
            previewData[rowIndex].display['purchase_price'] = val;
        } else if (previewData[rowIndex].display[field] !== undefined) {
            previewData[rowIndex].display[field] = val;
        }
    });

    syncJsonInput();
    renderTable();

    $('#error_edit_modal').modal('hide');
    $('.modal-backdrop').remove();
    $('body').removeClass('modal-open');

    setTimeout(function() {
        document.getElementById('submit_save_btn').click();
    }, 300);
});

document.getElementById('error_modal_ignore').addEventListener('click', function() {
    var rowNo    = parseInt(document.getElementById('error_modal_fields').getAttribute('data-row-no'));
    var rowIndex = previewData.findIndex(function(r) { return r.row_no == rowNo; });
    if (rowIndex !== -1) previewData[rowIndex].action = 'ignore';

    var tr = document.getElementById('preview_row_' + rowNo);
    if (tr) tr.remove();

    syncJsonInput();
    updateTotals();

    $('#error_edit_modal').modal('hide');
    $('.modal-backdrop').remove();
    $('body').removeClass('modal-open');

    setTimeout(function() {
        document.getElementById('submit_save_btn').click();
    }, 300);
});
     
});
