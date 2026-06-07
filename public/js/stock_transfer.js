/**
 * stock_transfer.js
 * نظام هجين: المنتجات المستوردة من إكسل تُخزَّن في JS array
 * المنتجات اليدوية تُقرأ من DOM — كلاهما يُحفَظ بالـ chunks
 */

var imported_products_data = [];

$(document).ready(function () {

    // ============================================================
    // 1. Autocomplete البحث عن المنتجات
    // ============================================================
if ($('#search_product_for_srock_adjustment').length > 0) {
    $('#search_product_for_srock_adjustment')
        .autocomplete({
            delay: 1000,
            autoFocus: false,
            
            source: function (request, response) {
                $.getJSON('/products/list', {
                    location_id: $('#location_id').val(),
                    term: request.term
                }, response);
            },
            minLength: 2,
            response: function (event, ui) {
                if (ui.content.length == 1) {
                    ui.item = ui.content[0];
                    // ✅ تحقق من الكمية قبل الاختيار التلقائي
                    if (ui.item.enable_stock == 0 || ui.item.qty_available > 0) {
                        $(this).data('ui-autocomplete')._trigger('select', 'autocompleteselect', ui);
                        $(this).autocomplete('close');
                    } else {
                        swal(LANG.out_of_stock || 'هذا المنتج غير متوفر في المخزون');
                        $(this).autocomplete('close');
                    }
                } else if (ui.content.length == 0) {
                    swal(LANG.no_products_found);
                }
            },
            focus: function (event, ui) {
                // ✅ منع التحديد التلقائي للمنتج الغير متوفر
                if (ui.item.enable_stock == 1 && ui.item.qty_available <= 0) {
                    return false;
                }
                return true;
            },
            select: function (event, ui) {
                event.preventDefault();
                event.stopPropagation();
                
                // ✅ الفحص النهائي — يمنع الإضافة حتى لو تجاوز الفحوصات السابقة
                if (ui.item.enable_stock == 1 && ui.item.qty_available <= 0) {
                    swal(LANG.out_of_stock || 'هذا المنتج غير متوفر في المخزون');
                    return false;
                }
                
                $(this).val(null);
                stock_transfer_product_row(ui.item.variation_id);
                return false;
            },
        })
        .autocomplete('instance')._renderItem = function (ul, item) {
            var label       = item.name;
            if (item.type == 'variable') label += '-' + item.variation;
            
            var stock_status = '';
            var is_disabled  = '';

            if (item.enable_stock == 1 && item.qty_available <= 0) {
                // ✅ تعطيل العنصر بصرياً
                is_disabled  = 'class="ui-state-disabled"';
                stock_status = '<br><span class="help-block text-danger">' + LANG.out_of_stock + '</span>';
            } else if (item.enable_stock == 1) {
                stock_status = '<br><span class="help-block">' + LANG.quantity_available + ': ' + item.qty_available + '</span>';
            }

            return $('<li ' + is_disabled + '>')
                .append('<div>' + label + ' (' + item.sub_sku + ')' + stock_status + '</div>')
                .appendTo(ul);
        };
}

////// // منع الـ Enter في خانة البحث من عمل Submit للنموذج
$(document).on('keypress', '#search_product_for_srock_adjustment', function(e) {
    if (e.which == 13) { // 13 هو كود زر Enter
        e.preventDefault();
        return false;
    }
});
//////////// 

    // ============================================================
    // 2. تفعيل زر الإكسل وخانة البحث عند اختيار الفرع
    // ============================================================
    $(document).on('change', 'select#location_id', function () {
        var has = !!$(this).val();
        $('#import_excel_btn').prop('disabled', !has);
        $('#search_product_for_srock_adjustment').prop('disabled', !has);
    });

    // ============================================================
    // 3. تغيير الكمية / السعر / الشحن
    // ============================================================
    $(document).on('change', 'input.product_quantity, input.product_unit_price, #shipping_charges', function () {
        update_table_row($(this).closest('tr'));
    });

    $(document).on('change', 'select.sub_unit', function () {
        var tr            = $(this).closest('tr');
        var qty_available = parseFloat(tr.find('.product_quantity').data('qty_available')) || 0;
        var multiplier    = parseFloat($(this).find(':selected').data('multiplier')) || 1;
        var allow_decimal = $(this).find(':selected').data('allow_decimal');
        var max_qty       = qty_available / multiplier;
        tr.find('.product_quantity').attr('data-rule-max-value', max_qty);
        if (!allow_decimal) {
            tr.find('.product_quantity').attr('data-rule-abs_digit', true);
        } else {
            tr.find('.product_quantity').removeAttr('data-rule-abs_digit');
        }
        update_table_row(tr);
    });

    // ============================================================
    // 4. حذف صف
    // ============================================================
    $(document).on('click', '.remove_product_row', function () {
        var tr = $(this).closest('tr');
        swal({ title: LANG.sure, icon: 'warning', buttons: true, dangerMode: true })
            .then(function (willDelete) {
                if (willDelete) {
                    if (tr.data('imported') == 1) {
                        var vid = tr.find('.variation_id').val();
                        imported_products_data = imported_products_data.filter(function (p) {
                            return String(p.variation_id) !== String(vid);
                        });
                    }
                    tr.remove();
                    update_table_total();
                }
            });
    });

    // ============================================================
    // 5. التاريخ
    // ============================================================
    if ($('#transaction_date').length) {
        $('#transaction_date').datetimepicker({
            format: moment_date_format + ' ' + moment_time_format,
            ignoreReadonly: true,
        });
    }

    // ============================================================
    // 6. Validation
    // ============================================================
    jQuery.validator.addMethod('notEqual', function (value, element, param) {
        return this.optional(element) || value != param;
    }, 'Please select different location');

    if ($('form#stock_transfer_form').length) {
        $('form#stock_transfer_form').validate({
            rules: {
                transfer_location_id: {
                    notEqual: function () { return $('select#location_id').val(); }
                }
            }
        });
    }

    // ============================================================
    // 7. الحفظ — نظام الدفعات الهجين
    // ============================================================
    $(document).on('submit', 'form#stock_transfer_form', function (e) {
        e.preventDefault();
        var form = $(this);

        if (!form.valid()) {
            toastr.error('الرجاء تصحيح أخطاء الكميات قبل الحفظ');
            return false;
        }

        var fixed_ref_no = $('#ref_no').val();
        var all_products = [];

        // أ) يدوي من DOM
        $('#stock_adjustment_product_table tbody tr.product_row').each(function () {
            var row = $(this);
            if (row.data('imported') == 1) return;
            var variation_id = row.find('.variation_id').val();
            if (!variation_id) return;

            var sub_unit_select      = row.find('select.sub_unit');
            var sub_unit_id          = sub_unit_select.length ? sub_unit_select.val() : null;
            var base_unit_multiplier = sub_unit_select.length
                ? (parseFloat(sub_unit_select.find(':selected').data('multiplier')) || null)
                : null;

            all_products.push({
                product_id:           row.find('.product_id').val(),
                variation_id:         variation_id,
                quantity:             row.find('.product_quantity').val(),
                unit_price:           row.find('.product_unit_price').val(),
                product_unit_id:      row.find('input[name*="[product_unit_id]"]').val() || null,
                sub_unit_id:          sub_unit_id,
                base_unit_multiplier: base_unit_multiplier,
                lot_no_line_id:       row.find('.lot_number').val() || null,
                enable_stock:         row.find('.enable_stock').val() == '1',
            });
        });

        // ب) مستورد من إكسل (من المصفوفة)
        if (imported_products_data.length > 0) {
            all_products = all_products.concat(imported_products_data);
        }

        if (all_products.length === 0) {
            toastr.warning(LANG.no_products_added);
            return false;
        }

        var chunkSize   = 65;
        var totalChunks = Math.ceil(all_products.length / chunkSize);
        var btn         = form.find('button[type="submit"]');

        function sendChunk(index) {
            var chunk  = all_products.slice(index * chunkSize, (index + 1) * chunkSize);
            var isLast = (index + 1) === totalChunks;

            // ← أضف هذين السطرين  002
            var formMethod = form.find('input[name="_method"]').val() || 'POST';
            var ajaxUrl    = form.attr('action');

            btn.prop('disabled', true).html(
                '<i class="fa fa-spinner fa-spin"></i> جاري حفظ دفعة ' + (index + 1) + ' من ' + totalChunks
            );

            $.ajax({
                method: 'POST',
                url: form.attr('action'),
                data: {
                    _token:               $('meta[name="csrf-token"]').attr('content'),
                    _method:              formMethod,
                    location_id:          $('#location_id').val(),
                    transfer_location_id: $('#transfer_location_id').val(),
                    ref_no:               fixed_ref_no,
                    transaction_date:     $('#transaction_date').val(),
                    additional_notes:     $('#additional_notes').val(),
                    shipping_charges:     $('#shipping_charges').val() || 0,
                    final_total:          $('#total_amount').val() || 0,
                    status:               $('#status').val(),
                    products:             chunk,
                    is_last_chunk:        isLast ? 1 : 0,
                },
                success: function (result) {
                    if (result.success || result.success === 1) {
                        if (result.ref_no) {
                            fixed_ref_no = result.ref_no;
                            $('#ref_no').val(fixed_ref_no);
                        }
                        if (isLast) {
                            imported_products_data = [];
                            toastr.success(result.msg || 'تم حفظ التحويل بنجاح');
                            window.onbeforeunload = null;
                            window.location.href = '/stock-transfers';
                        } else {
                            sendChunk(index + 1);
                        }
                    } else {
                        toastr.error(result.msg || 'حدث خطأ أثناء الحفظ');
                        btn.prop('disabled', false).text('حفظ');
                    }
                },
                error: function () {
                    toastr.error('فشل الاتصال في الدفعة ' + (index + 1));
                    btn.prop('disabled', false).text('حفظ');
                }
            });
        }

        sendChunk(0);
    });

    // ============================================================
    // 8. DataTable قائمة التحويلات
    // ============================================================
   //////////// add date and location filter  003 
   // 1. تحديد البداية والنهاية لتاريخ اليوم (كقيمة افتراضية)
    var start = moment().startOf('day');
    var end = moment().endOf('day');

    // 2. تعريف الـ daterangepicker لمرة واحدة فقط وبإعدادات كاملة
    if ($('#qer_date_filter').length == 1) {
        $('#qer_date_filter').daterangepicker(
            _.extend({}, dateRangeSettings, {
                timePicker: true,
                timePicker24Hour: true,
                startDate: start,
                endDate: end,
                locale: { format: moment_date_format + ' HH:mm' }
            }),
            function(start, end) {
                // ✅ تعديل: ضبط الوقت المختار ليكون اليوم كاملاً من بدايته لنهايته
                var report_start = start.startOf('day');
                var report_end = end.endOf('day');

                // تحديث النص الظاهر في الحقل ليراه المستخدم بصيغة واضحة
                $('#qer_date_filter').val(
                    report_start.format(moment_date_format + ' HH:mm') + ' ~ ' + 
                    report_end.format(moment_date_format + ' HH:mm')
                );
                
                // ✅ تعديل جوهري: تحديث قيم الـ picker الداخلية لضمان إرسالها لـ Ajax بالوقت الجديد
                var picker = $('#qer_date_filter').data('daterangepicker');
                picker.startDate = report_start;
                picker.endDate = report_end;

                if (typeof stock_transfer_table !== 'undefined') {
                    stock_transfer_table.ajax.reload();
                }
            }
        );

        // ضبط القيمة الظاهرة في الحقل النصي عند تحميل الصفحة لأول مرة (تاريخ اليوم كاملاً)
        $('#qer_date_filter').val(
            start.format(moment_date_format + ' HH:mm') + ' ~ ' +
            end.format(moment_date_format + ' HH:mm')
        );
    }
    //////////////// 003
    var stock_transfer_table;
    if ($('#stock_transfer_table').length) {
        stock_transfer_table = $('#stock_transfer_table').DataTable({
            processing: true,
            serverSide: true,
            fixedHeader: false,
            aaSorting: [[0, 'desc']],
            ajax: {
        url: '/stock-transfers',
        data: function(d) {
            // ✅ فلتر الفرع
           d.location_id = $('#location_id_filter').val();

            // ✅ فلتر التاريخ
           var picker = $('#qer_date_filter').data('daterangepicker');
                // هنا نضمن إرسال التاريخ للسيرفر حتى لو لم يقم المستخدم بتغييره
                if (picker && $('#qer_date_filter').val() !== '') {
                    d.start_date = picker.startDate.format('YYYY-MM-DD HH:mm:ss');
                    d.end_date   = picker.endDate.format('YYYY-MM-DD HH:mm:ss');
                }
        }
    },
            columnDefs: [{ targets: 9, orderable: false, searchable: false }],
            columns: [
                { data: 'transaction_date', name: 'transaction_date' },
                { data: 'ref_no',           name: 'ref_no' },
                { data: 'location_from',    name: 'l1.name' },
                { data: 'location_to',      name: 'l2.name' },
                { data: 'added_qty',        name: 'added_qty', searchable: false },
                { data: 'status',           name: 'status' },
                { data: 'shipping_charges', name: 'shipping_charges' },
                { data: 'final_total',      name: 'final_total' },
                { data: 'additional_notes', name: 'additional_notes' },
                { data: 'action',           name: 'action' },
            ],
            fnDrawCallback: function () {
                __currency_convert_recursively($('#stock_transfer_table'));
            },
        });
    }
    // ── فلتر الفرع 003
   $(document).on('change', '#location_id_filter', function() {
    stock_transfer_table.ajax.reload();
});

    // ============================================================
    // 9. Child Rows
    // ============================================================
    var detailRows = [];
    $('#stock_transfer_table tbody').on('click', '.view_stock_transfer', function () {
        var tr  = $(this).closest('tr');
        var row = stock_transfer_table.row(tr);
        var idx = $.inArray(tr.attr('id'), detailRows);
        if (row.child.isShown()) {
            $(this).find('i').removeClass('fa-eye').addClass('fa-eye-slash');
            row.child.hide();
            detailRows.splice(idx, 1);
        } else {
            $(this).find('i').removeClass('fa-eye-slash').addClass('fa-eye');
            row.child(get_stock_transfer_details(row.data())).show();
            if (idx === -1) detailRows.push(tr.attr('id'));
        }
    });

    // ============================================================
    // 10. استيراد إكسل — النظام الهجين مع فصل الكافية / الغير كافية
    // ============================================================
    $(document).on('submit', '#export_quantity_form', function (e) {
        e.preventDefault();

        var location_id = $('#location_id').val();
        if (!location_id) {
            toastr.error('يرجى تحديد الموقع المصدر أولاً');
            return;
        }

        var btn      = $(this).find('button[type="submit"]');
        var formData = new FormData(this);
        formData.append('location_id', location_id);

        btn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> جاري المعالجة...');

        $.ajax({
            method:      'POST',
            url:         '/stock-transfers/import-products',
            data:        formData,
            dataType:    'json',
            processData: false,
            contentType: false,
            success: function (result) {
                btn.prop('disabled', false).html('استيراد');

                if (!result.success) {
                    toastr.error(result.msg || 'فشل الاستيراد');
                    return;
                }

                var insufficient = result.products_insufficient || [];
                var sufficient   = result.products_sufficient   || [];

                // -----------------------------------------------
                // لا يوجد غير كافية — أضف مباشرة
                // -----------------------------------------------
                if (insufficient.length === 0) {
                    applyImportedProducts(result);
                    return;
                }

                // -----------------------------------------------
                // يوجد غير كافية — اعرض خيارات
                // -----------------------------------------------
                var lines = insufficient.slice(0, 10).map(function (p) {
                    return p.sub_sku + ' — ' + p.product_name +
                           ' | مطلوب: ' + p.quantity + ' / متاح: ' + p.qty_available;
                }).join('\n');
                if (insufficient.length > 10) {
                    lines += '\n... و ' + (insufficient.length - 10) + ' أخرى';
                }

                swal({
                    title: 'تحذير: ' + insufficient.length + ' منتج كميته غير كافية',
                    text: lines,
                    icon: 'warning',
                    buttons: {
                        cancel: {
                            text: 'إلغاء',
                            visible: true,
                            value: null,
                            className: ''
                        },
                        export: {
                            text: '⬇ تصدير الغير كافية (CSV)',
                            value: 'export',
                            className: 'btn-warning'
                        },
                        confirm: {
                            text: '✔ متابعة بالمتوفرة فقط (' + sufficient.length + ')',
                            value: 'continue',
                            className: 'btn-success'
                        }
                    },
                    dangerMode: false,
                }).then(function (value) {

                    if (value === 'export') {
                        // تصدير الغير كافية CSV ثم تطبيق الكافية
                        exportInsufficientToCSV(insufficient);
                        if (sufficient.length > 0) {
                            applyImportedProducts(result);
                        } else {
                            toastr.warning('لا يوجد منتجات متوفرة للإضافة');
                        }

                    } else if (value === 'continue') {
                        // تطبيق الكافية فقط
                        if (sufficient.length === 0) {
                            toastr.warning('لا يوجد منتجات متوفرة للإضافة');
                            return;
                        }
                        applyImportedProducts(result);
                    }
                    // null = إلغاء — لا شيء
                });
            },
            error: function () {
                btn.prop('disabled', false).html('استيراد');
                toastr.error('فشل الاتصال بالسيرفر');
            }
        });
    });

    // ============================================================
    // 11. Update Status
    // ============================================================
    $(document).on('click', '.update_status_link', function () {
        $.ajax({
            method: 'GET', url: $(this).data('href'),
            success: function (result) {
                $('#update_status_modal').find('.modal-body').html(result);
                $('#update_status_modal').modal('show');
            }
        });
    });

    $(document).on('submit', '#update_status_form', function (e) {
        e.preventDefault();
        $.ajax({
            method: 'POST', url: $(this).attr('action'), data: $(this).serialize(),
            success: function (result) {
                if (result.success) {
                    toastr.success(result.msg);
                    $('#update_status_modal').modal('hide');
                    if (stock_transfer_table) stock_transfer_table.ajax.reload();
                } else { toastr.error(result.msg); }
            }
        });
    });

    // ============================================================
    // 12. حذف تحويل
    // ============================================================
    $(document).on('click', 'button.delete_stock_transfer', function () {
        var href = $(this).data('href');
        swal({ title: LANG.sure, icon: 'warning', buttons: true, dangerMode: true })
            .then(function (willDelete) {
                if (willDelete) {
                    $.ajax({
                        method: 'DELETE', url: href, dataType: 'json',
                        success: function (result) {
                            if (result.success) {
                                toastr.success(result.msg);
                                if (stock_transfer_table) stock_transfer_table.ajax.reload();
                            } else { toastr.error(result.msg); }
                        }
                    });
                }
            });
    });

});

// ============================================================
// تطبيق المنتجات الكافية على الجدول والمصفوفة
// ============================================================
function applyImportedProducts(result) {
    var sufficient = result.products_sufficient || [];

    if (sufficient.length === 0) return;

    // أضف للمصفوفة العالمية
    imported_products_data = imported_products_data.concat(sufficient);

    // أضف HTML للجدول
    if (result.html_sufficient) {
        $('table#stock_adjustment_product_table tbody').append(result.html_sufficient);
    }

    if (result.new_row_index !== undefined) {
        $('#product_row_index').val(result.new_row_index);
    }

    update_table_total();
    $('#export_transfer_products_modal').modal('hide');

    var msg = 'تم إضافة ' + sufficient.length + ' صنف';
    if (result.skipped > 0) msg += ' — تم تخطي ' + result.skipped + ' غير موجود';
    if ((result.products_insufficient || []).length > 0) {
        msg += ' — ' + result.products_insufficient.length + ' صنف كميته غير كافية لم يُضَف';
    }
    toastr.success(msg);
}

// ============================================================
// تصدير المنتجات الغير كافية إلى CSV
// ============================================================
function exportInsufficientToCSV(products) {
    var csv = '\uFEFF'; // BOM لدعم العربية في Excel
    csv += 'SKU,اسم المنتج,الكمية المطلوبة,الكمية المتاحة\n';

    products.forEach(function (p) {
        var name = '"' + (p.product_name || '').replace(/"/g, '""') + '"';
        csv += p.sub_sku + ',' + name + ',' + p.quantity + ',' + p.qty_available + '\n';
    });

    var blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
    var url  = URL.createObjectURL(blob);
    var a    = document.createElement('a');
    var date = new Date().toISOString().slice(0, 10);

    a.href     = url;
    a.download = 'insufficient_stock_' + date + '.csv';
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
    URL.revokeObjectURL(url);

    toastr.info('تم تصدير ' + products.length + ' منتج غير متوفر إلى CSV');
}

// ============================================================
// وظائف مساعدة
// ============================================================
function stock_transfer_product_row(variation_id) {
    var row_index   = parseInt($('#product_row_index').val()) || 0;
    var location_id = $('select#location_id').val();
    $.ajax({
        method: 'POST',
        url: '/stock-adjustments/get_product_row',
        data: {
            _token:       $('meta[name="csrf-token"]').attr('content'),
            row_index:    row_index,
            variation_id: variation_id,
            location_id:  location_id,
            type:         'stock_transfer',
        },
        dataType: 'html',
        success: function (result) {
            $('table#stock_adjustment_product_table tbody').append(result);
            update_table_total();
            $('#product_row_index').val(row_index + 1);
        },
        error: function () { toastr.error('تعذر جلب بيانات المنتج'); }
    });
}

function update_table_row(tr) {
    var quantity = parseFloat(__read_number(tr.find('input.product_quantity'))) || 0;
    var price = parseFloat(__read_number(tr.find('input.product_unit_price'))) || 0;
    var total = quantity * price;
    tr.find('input.product_line_total').val(__number_f(total));
    update_table_total();
}

function update_table_total() {
    var table_total = 0;
    $('table#stock_adjustment_product_table tbody tr').each(function () {
        var v = parseFloat(__read_number($(this).find('input.product_line_total')));
        if (!isNaN(v)) table_total += v;
    });
    $('span#total_adjustment').text(__number_f(table_total));
    var shipping = parseFloat(__read_number($('input#shipping_charges'))) || 0;
    var final_total = table_total + shipping;
    $('span#final_total_text').text(__number_f(final_total));
    $('input#total_amount').val(final_total);
}

function update_table_row(tr) {
    var qty        = parseFloat(__read_number(tr.find('input.product_quantity'))) || 0;
    var multiplier = 1;
    var sub_unit   = tr.find('select.sub_unit');
    if (sub_unit.length) multiplier = parseFloat(sub_unit.find(':selected').data('multiplier')) || 1;
    var unit_price = parseFloat(tr.find('input.hidden_base_unit_price').val()) || 0;
    var row_total  = (qty * multiplier) * unit_price;
    tr.find('input.product_line_total').val(__number_f(row_total));
    tr.find('input.product_unit_price').val(__number_f(unit_price * multiplier));
    update_table_total();
}

function get_stock_transfer_details(rowData) {
    return '<div class="text-center p-10"><i class="fa fa-spinner fa-spin"></i></div>';
}
