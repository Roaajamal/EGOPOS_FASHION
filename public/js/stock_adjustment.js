$(document).ready(function() {
    // 1. إعداد البحث عن المنتجات (يدعم صفحة الإضافة وصفحة التعديل)
    // تم توحيد المعرفات لتعمل على المعرفين (search_product_for_s_adj و search_product_for_srock_adjustment)
    if ($('#search_product_for_s_adj').length > 0 || $('#search_product_for_srock_adjustment').length > 0) {
    let search_field = $('#search_product_for_s_adj').length > 0 ? '#search_product_for_s_adj' : '#search_product_for_srock_adjustment';

    $(search_field).autocomplete({
        delay: 500, // تقليل التأخير لسرعة الاستجابة مع السكانر
        autoFocus: false, // مهم لمنع السكانر من اختيار أول عنصر عشوائياً

        source: function(request, response) {
            $.getJSON(
                '/products/list',
                { location_id: $('#location_id').val(), term: request.term },
                response
            );
        },
        minLength: 2,
        response: function(event, ui) {
            if (ui.content.length == 1) {
                ui.item = ui.content[0];
                if (ui.item.qty_available > 0 && ui.item.enable_stock == 1) {
                    $(this).data('ui-autocomplete')._trigger('select', 'autocompleteselect', ui);
                    $(this).autocomplete('close');
                }
            } else if (ui.content.length == 0) {
                swal(LANG.no_products_found);
            }
        },
        focus: function(event, ui) {
            // منع وضع القيمة في الحقل عند التحويم إذا كان المنتج غير متوفر
            if (ui.item.qty_available <= 0) {
                return false;
            }
        },
        select: function(event, ui) {
            // ✅ المنع الصريح للـ Enter من عمل Submit للنموذج
            event.preventDefault();
            event.stopPropagation();

            if (ui.item.qty_available > 0) {
                $(this).val(null);
                // ملاحظة: تأكدي أن اسم الدالة صحيح هنا (كانت stock_adjustment_product_row)
                stock_adjustment_product_row(ui.item.variation_id);
            } else {
                alert(LANG.out_of_stock);
            }
            return false; 
        },
    }).autocomplete('instance')._renderItem = function(ul, item) {
        var label = item.name;
        if (item.type == 'variable') label += '-' + item.variation;
        
        var stock_status = '';
        var is_disabled = '';

        if (item.enable_stock == 1 && item.qty_available <= 0) {
            is_disabled = 'class="ui-state-disabled"';
            stock_status = '<br><span class="help-block text-danger">' + LANG.out_of_stock + '</span>';
        } else if (item.enable_stock == 1) {
            stock_status = '<br><span class="help-block">' + LANG.quantity_available + ': ' + item.qty_available + '</span>';
        }

        return $('<li ' + is_disabled + '>')
            .append('<div>' + label + ' (' + item.sub_sku + ')' + stock_status + '</div>')
            .appendTo(ul);
    };

    // ✅ إضافة مستمع للأحداث لمنع زر Enter تماماً في خانة البحث
    $(document).on('keypress', search_field, function(e) {
        if (e.which == 13) { 
            e.preventDefault();
            return false;
        }
    });
}

    // 2. تحديث عداد الأسطر وتحديث الإجماليات عند تحميل الصفحة (مهم جداً لصفحة التعديل)
    if ($('#stock_adjustment_product_table tbody tr').length > 0) {
        let total_rows = $('#stock_adjustment_product_table tbody tr').length;
        if($('#product_row_index').length == 0){
             $('form').append('<input type="hidden" id="product_row_index" value="'+total_rows+'">');
        } else {
             $('#product_row_index').val(total_rows);
        }
        update_table_total();
        updateLineNumbers(); // تحديث أرقام الأسطر
    }

    // 3. تغيير الفرع/الموقع
    $('select#location_id').change(function() {
        let search_field = $('#search_product_for_s_adj').length > 0 ? '#search_product_for_s_adj' : '#search_product_for_srock_adjustment';
        if ($(this).val()) {
            $(search_field).removeAttr('disabled');
        } else {
            $(search_field).attr('disabled', 'disabled');
        }
        $('table#stock_adjustment_product_table tbody').html('');
        $('#product_row_index').val(0);
        update_table_total();
    });

    // 4. مراقبة التغييرات في الكميات والأسعار
    // مراقبة الكمية (تغيير لحظي + عند الخروج من الخانة)
$(document).on('change input', 'input.product_quantity', function() {
    update_table_row($(this).closest('tr'));
});

// مراقبة سعر الوحدة (تغيير لحظي + عند الخروج من الخانة)
$(document).on('change input', 'input.product_unit_price', function() {
    update_table_row($(this).closest('tr'));
    update_table_total();
});

// مراقبة شاملة لكل أحداث الإدخال في الجدول
$(document).on('input change keyup', 'input.product_quantity, input.product_unit_price', function() {
    // جلب السطر الذي حدث فيه التغيير
    var tr = $(this).closest('tr');
    
    // تنفيذ الحسبة للسطر (الكمية × السعر)
    update_table_row(tr);
    
    // تنفيذ الحسبة الكلية (الإجمالي + الخصم "خ")
    update_table_total();
});
    // 5. حذف سطر منتج
    $(document).on('click', '.remove_product_row', function() {
        swal({
            title: LANG.sure,
            icon: 'warning',
            buttons: true,
            dangerMode: true,
        }).then(willDelete => {
            if (willDelete) {
                $(this).closest('tr').remove();
                update_table_total();
                updateLineNumbers(); // تحديث أرقام الأسطر بعد الحذف
            }
        });
    });

    // 6. إعداد التاريخ (Datetimepicker)
    $('#transaction_date').datetimepicker({
        format: moment_date_format + ' ' + moment_time_format,
        ignoreReadonly: true,
    });

    // 7. التحقق من صحة النموذج والـ DataTable
    $('form#stock_adjustment_form').validate();
    $('form#stock_adjustment_edit_form').validate();

    stock_adjustment_table = $('#stock_adjustment_table').DataTable({
        processing: true,
        serverSide: true,
        fixedHeader: false,
        
        ajax: '/stock-adjustments',
       
        columnDefs: [
            {
                targets: 0,
                orderable: false,
                searchable: false,
            },
        ],
        aaSorting: [[1, 'desc']],
        columns: [
            { data: 'action', name: 'action' },
            { data: 'transaction_date', name: 'transaction_date' },
            { data: 'ref_no', name: 'ref_no' },
            { data: 'location_name', name: 'BL.name' },
            { data: 'adjustment_type', name: 'adjustment_type' },
            { data: 'total_qty', name: 'total_qty', searchable: false },
            { data: 'final_total', name: 'final_total' },
            { data: 'total_amount_recovered', name: 'total_amount_recovered' },
            { data: 'additional_notes', name: 'additional_notes' },
            { data: 'added_by', name: 'u.first_name' },
        ],
        fnDrawCallback: function(oSettings) {
            __currency_convert_recursively($('#stock_adjustment_table'));
        },
    });

    // 8. حذف سند التسوية من القائمة
    $(document).on('click', 'button.delete_stock_adjustment', function() {
        swal({
            title: LANG.sure,
            icon: 'warning',
            buttons: true,
            dangerMode: true,
        }).then(willDelete => {
            if (willDelete) {
                var href = $(this).data('href');
                $.ajax({
                    method: 'DELETE',
                    url: href,
                    dataType: 'json',
                    success: function(result) {
                      if (result.success) {
        // 1. تحديث الرقم المرجعي لضمان ربط الدفعات القادمة بنفس السند
        if (result.ref_no) {
            ref_no = result.ref_no;
            $('#ref_no').val(ref_no);
        }

        if (isLast) {
            // 2. عند الدفعة الأخيرة فقط: أظهري رسالة النجاح النهائية وانتقلي للصفحة
            toastr.success("تم حفظ كامل الكميات بنجاح");
            window.location.href = '/stock-adjustments';
        } else {
            // 3. إذا لم تكن الأخيرة، اطلبي الدفعة التالية تلقائياً
            sendChunk(index + 1);
        }
    } else {
        // في حال فشل أي دفعة، نظهر الخطأ ونعيد تفعيل الزر
        toastr.error(result.msg);
        btn.prop('disabled', false).text('حفظ');
    }
                    },
                });
            }
        });
    });

    /////////  007
    // 11. إضافة مستمع لحدث الحفظ لتقسيم البيانات إلى دفعات (Chunks)
$(document).on('submit', 'form#stock_adjustment_form', function(e) {
    e.preventDefault();
    var form = $(this);
    var fixed_ref_no = $('#ref_no').val();
    // تعريف الزر هنا لضمان استخدامه داخل دالة sendChunk
    var btn = form.find('button[type="submit"]');

    if (!form.valid()) {
        toastr.error("الرجاء تصحيح أخطاء الكميات في الجدول");
        return false;
    }
    
    var all_products = [];
    // تجميع كافة المنتجات من الجدول
    $('#stock_adjustment_product_table tbody tr.product_row').each(function() {
        var row = $(this);
        var p_id = row.find('input.product_id').val();
        var v_id = row.find('input.variation_id').val();
        var qty = row.find('input.product_quantity').val();
        var price = row.find('input.product_unit_price').val();

        if (p_id && v_id) {
            all_products.push({
                product_id: p_id,
                variation_id: v_id,
                quantity: qty,
                unit_price: price,
                lot_no_line_id: row.find('.lot_number').val() || null
            });
        }
    });


    if (all_products.length === 0) {
        toastr.warning("لا توجد منتجات صالحة للحفظ - تأكد من إضافة أصناف للجدول");
        return false;
    }

    var chunkSize = 50;
    var totalChunks = Math.ceil(all_products.length / chunkSize);
    var ref_no = $('#ref_no').val();

    function sendChunk(index) {
        var start = index * chunkSize;
        var chunk = all_products.slice(start, start + chunkSize);
        var isLast = (index + 1) === totalChunks;

        
        // الآن سيتم تحديث نص الزر بشكل سليم
        btn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> حفظ جزء ' + (index + 1) + ' / ' + totalChunks);

        $.ajax({
            method: 'POST',
            url: form.attr('action'),
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
            data: {
                location_id: $('#location_id').val(),
                ref_no: fixed_ref_no,
                transaction_date: $('#transaction_date').val(),
                adjustment_type: $('#adjustment_type').val(),
                total_amount_recovered: $('#total_amount_recovered').val(),
                additional_notes: $('#additional_notes').val(),
                final_total: $('#total_adjustment_value').val(),
                products: chunk,
                is_last_chunk: isLast
            },
            success: function(result) {
                if (result.success || result.success === 1) {
                    if (isLast) {
                        toastr.success("تم حفظ كامل الأصناف بنجاح");
                        window.location.href = '/stock-adjustments';
                    } else {
                        sendChunk(index + 1);
                    }
                } else {
                    toastr.error(result.msg);
                    btn.prop('disabled', false).text('حفظ');
                }
            },
            error: function() {
                toastr.error("فشل الاتصال أثناء حفظ الدفعة " + (index + 1));
                btn.prop('disabled', false).text('حفظ');
            }
        });
    }

    sendChunk(0);
});
//////// 007
});

// 9. الدوال المساعدة (Functions)
function stock_adjustment_product_row(variation_id) {
    var row_index = parseInt($('#product_row_index').val()) || 0;
    var location_id = $('select#location_id').val();
    $.ajax({
        method: 'POST',
        url: '/stock-adjustments/get_product_row',
        data: { row_index: row_index, variation_id: variation_id, location_id: location_id },
        dataType: 'html',
        success: function(result) {
            $('table#stock_adjustment_product_table tbody').append(result);
            update_table_total();
            $('#product_row_index').val(row_index + 1);
            updateLineNumbers(); // تحديث أرقام الأسطر بعد الإضافة
        },
    });
}

function update_table_total() {
    var table_total = 0;
    var total_diff = 0;
     var total_qty = 0; 

    $('table#stock_adjustment_product_table tbody tr').each(function() {
        var row = $(this);
        // استخدام دالة __read_number لفك تنسيق العملة
        var qty = __read_number(row.find('input.product_quantity')) || 0;
        var unit_price = __read_number(row.find('input.product_unit_price')) || 0;
        
        // قراءة السعر الأصلي مباشرة لأنه مخزن كرقم بسيط في الـ value
        var original_price = parseFloat(row.find('input.original_purchase_price').val()) || 0;

        var row_total = qty * unit_price;
        table_total += row_total;
         total_qty += qty;

        if (original_price > 0) {
            total_diff += (original_price ) * qty;
        }
    });

    $('span#total_adjustment').text(__number_f(table_total));
    $('#total_adjustment_value').val(table_total);
    $('span#total_quantities').text(__number_f(total_qty));
   
}
function update_table_row(tr) {
    var quantity = parseFloat(__read_number(tr.find('input.product_quantity'))) || 0;
    var unit_price = __read_number(tr.find('input.product_unit_price')) || 0;
    
    // حساب المجموع الفرعي للسطر
    var row_total = quantity * unit_price;
    
    // تحديث خانة المجموع في السطر (تأكد من وجود الكلاس product_line_total)
    tr.find('input.product_line_total').val(__number_f(row_total));
    
    // إذا كان لديك نص بجانب الخانة، نقوم بتحديثه أيضاً
    if(tr.find('.product_line_total_text').length > 0){
        tr.find('.product_line_total_text').text(__number_f(row_total));
    }
}

function applySavedColumns() {
        $('.toggle-col').each(function() {
            var colClass = $(this).data('col');
            // جلب الحالة من ذاكرة المتصفح (الافتراضي هو true إذا لم يوجد سجل سابق)
            var isChecked = localStorage.getItem('hide_col_' + colClass) !== 'false';
            
            $(this).prop('checked', isChecked);
            if (isChecked) {
                $('.' + colClass).show();
            } else {
                $('.' + colClass).hide();
            }
        });
    }

    applySavedColumns();

    // 2. مراقبة التغيير وحفظ الحالة الجديدة
    $('.toggle-col').on('change', function() {
        var colClass = $(this).data('col');
        var isChecked = $(this).is(':checked');
        
        // تنفيذ الإخفاء أو الإظهار
        if(isChecked) {
            $('.' + colClass).show();
        } else {
            $('.' + colClass).hide();
        }
        
        // حفظ الاختيار في localStorage
        localStorage.setItem('hide_col_' + colClass, isChecked);
    });

// 10. استيراد المنتجات من إكسل
$(document).on('submit', '#export_quantity_products_modal form', function(e) {
    e.preventDefault();
    let formData = new FormData(this); 
    let url = $(this).attr('action');
    let location_id = $('#location_id').val();

    if (!location_id) {
        toastr.error("الرجاء اختيار الموقع أولاً");
        return false;
    }

    formData.append('location_id', location_id); 

    let btn = $(this).find('button[type="submit"]');
    let btn_text = btn.html();
    btn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> جاري التحليل...');

    $.ajax({
        method: 'POST',
        url: url,
        data: formData,
        dataType: 'json',
        processData: false, 
        contentType: false, 
        success: function(result) {
            if (result.success) {
                let products = result.products; // السيرفر يرسل مصفوفة بيانات JSON
                let total = products.length;

                if (total === 0) {
                    toastr.error("لا توجد بيانات صالحة.");
                    btn.prop('disabled', false).html(btn_text);
                    return;
                }

                // استخدام القالب المخفي إذا كان موجوداً
                if ($('#product_row_template').length > 0) {
                    addProductsFromTemplate(products, location_id);
                    btn.prop('disabled', false).html(btn_text);
                    $('#export_quantity_products_modal').modal('hide');
                    
                    let message = "تم استيراد " + total + " صنف بنجاح.";
                    if (result.skipped_count > 0) {
                        message += " تم تخطي " + result.skipped_count + " صنف.";
                       // ✅ أضف هنا بدل download_url
                if (result.products_insufficient && result.products_insufficient.length > 0) {
                   let confirmed = confirm("تم تخطي " + result.skipped_count + " صنف. هل تريد تنزيل ملف المنتجات المرفوضة؟");
        if (confirmed) {
            exportInsufficientToExcel(result.products_insufficient);
        }
                }
                    }
                    if (result.products_insufficient && result.products_insufficient.length > 0) {
                exportInsufficientToExcel(result.products_insufficient);
            }
                    toastr.success(message);
                } else {
                    // طلب "قالب" سطر واحد فقط من السيرفر (الطريقة القديمة)
                    $.ajax({
                        method: 'POST',
                        url: '/stock-adjustments/get_product_row',
                        data: { 
                            row_index: 0, 
                            variation_id: products[0].variation_id, 
                            location_id: location_id
                        },
                        dataType: 'html',
                        success: function(template_html) {
                            let $tableBody = $('#stock_adjustment_product_table tbody');
                            let currentRows = parseInt($('#product_row_index').val()) || 0;

                            btn.html('<i class="fa fa-rocket"></i> جاري الرسم...');

                            products.forEach(function(p) {
                                // البحث عن المنتج إذا كان موجوداً مسبقاً للدمج
                                let existingRow = $tableBody.find('.variation_id[value="' + p.variation_id + '"]').closest('tr');

                                if (existingRow.length > 0) {
                                    let current_qty = parseFloat(__read_number(existingRow.find('.product_quantity'))) || 0;
                                    __write_number(existingRow.find('.product_quantity'), current_qty + parseFloat(p.qty));
                                    update_table_row(existingRow);
                                } else {
                                    // بناء السطر الجديد باستخدام القالب وتعديل البيانات برمجياً
                                    let $newRow = $(template_html);
                                    
                                    // تحديث الفهارس (Indexes) والبيانات
                                    $newRow.find('input, select').each(function() {
                                        let name = $(this).attr('name');
                                        if (name) $(this).attr('name', name.replace('[0]', '[' + currentRows + ']'));
                                    });

                                    $newRow.find('.variation_id').val(p.variation_id);
                                    $newRow.find('.product_id').val(p.product_id);
                                    $newRow.find('td:first-child').html('<strong class="text-primary">' + p.sub_sku + '</strong>');
                                    __write_number($newRow.find('.product_quantity'), p.qty);
                                    $newRow.find('.product_unit_price').val(p.price);

                                    $tableBody.append($newRow);
                                    update_table_row($newRow);
                                    currentRows++;
                                }
                            });

                            $('#product_row_index').val(currentRows);
                            update_table_total();
                            updateLineNumbers(); // تحديث أرقام الأسطر بعد الاستيراد
                            btn.prop('disabled', false).html(btn_text);
                            $('#export_quantity_products_modal').modal('hide');
                            
                            let message = "تم استيراد " + total + " صنف بنجاح.";
                            if (result.skipped_count > 0) {
                                message += " تم تخطي " + result.skipped_count + " صنف.";
                                if (result.download_url) {
                                    toastr.info('<a href="' + result.download_url + '" target="_blank">تحميل المنتجات المرفوضة</a>', 'معلومات', { allowHtml: true });
                                }
                            }
                            toastr.success(message);
                        }
                    });
                }
            } else {
                btn.prop('disabled', false).html(btn_text);
                toastr.error(result.msg || "حدث خطأ في الاستيراد");
            }
        },
        error: function(xhr) {
            btn.prop('disabled', false).html(btn_text);
            let errorMsg = "حدث خطأ أثناء الرفع";
            if (xhr.responseJSON && xhr.responseJSON.msg) {
                errorMsg = xhr.responseJSON.msg;
            }
            toastr.error(errorMsg);
        }
    });
});

// دالة جديدة لإضافة المنتجات من القالب المخفي
function addProductsFromTemplate(products, location_id) {
    let $tableBody = $('#stock_adjustment_product_table tbody');
    let currentRows = parseInt($('#product_row_index').val()) || 0;
    let template = $('#product_row_template').html();

    products.forEach(function(product) {
        // البحث عن المنتج إذا كان موجوداً مسبقاً
        let existingRow = $tableBody.find('.variation_id[value="' + product.variation_id + '"]').closest('tr');

        if (existingRow.length > 0) {
            // دمج مع المنتج الموجود
            let current_qty = parseFloat(__read_number(existingRow.find('.product_quantity'))) || 0;
            let new_qty = current_qty + parseFloat(product.qty);
            __write_number(existingRow.find('.product_quantity'), new_qty);
            update_table_row(existingRow);
        } else {
            // إنشاء صف جديد من القالب
            let $newRow = $(template);
            
            // تحديث الفهارس
            $newRow.find('input, select').each(function() {
                let name = $(this).attr('name');
                if (name) {
                    $(this).attr('name', name.replace('[0]', '[' + currentRows + ']'));
                }
            });

            // تعبئة البيانات
            $newRow.find('.variation_id').val(product.variation_id);
            $newRow.find('.product_id').val(product.product_id);
            $newRow.find('.sku-column strong').text(product.sub_sku); 
            $newRow.find('.custom-field-1').text(product.custom_field_1 || '-');
$newRow.find('.custom-field-2').text(product.custom_field_2 || '-');
$newRow.find('.custom-field-3').text(product.custom_field_3 || '-');
            __write_number($newRow.find('.product_quantity'), product.qty);
            __write_number($newRow.find('.product_unit_price'), product.price || 0);
            
            // تعيين اسم المنتج إذا كان متوفراً
            if (product.product_name) {
                $newRow.find('.product-name-column strong').text(product.product_name);
            }

            $tableBody.append($newRow);
            update_table_row($newRow);
            currentRows++;
        }
    });

    $('#product_row_index').val(currentRows);
    update_table_total();
    updateLineNumbers(); // تحديث أرقام الأسطر
}

// دالة تحديث أرقام الأسطر
function updateLineNumbers() {
    $('#stock_adjustment_product_table tbody tr').each(function(index) {
        let row = $(this);
        let lineNumber = index + 1;
        
        // تحديث رقم السطر
        if (row.find('.line-number').length > 0) {
            row.find('.line-number').text(lineNumber);
        }
        
        // تحديث data attribute
        row.attr('data-row-index', index);
    });
}

$(document).on('shown.bs.modal', '.view_modal', function() {
    __currency_convert_recursively($('.view_modal'));
});
function exportInsufficientToExcel(products) {
    let csvContent = "SKU,اسم المنتج,الكمية المطلوبة,الكمية المتوفرة,السبب\n";
    
    products.forEach(function(p) {
        csvContent += [
            p.sub_sku,
            p.product_name ?? '',
            p.qty,
            p.qty_available,
            p.reason
        ].join(',') + "\n";
    });

    let blob = new Blob(["\uFEFF" + csvContent], { type: 'text/csv;charset=utf-8;' });
    let url = URL.createObjectURL(blob);
    let link = document.createElement('a');
    link.href = url;
    link.download = 'skipped_products_' + Date.now() + '.csv';
    link.click();
    URL.revokeObjectURL(url);
}
 