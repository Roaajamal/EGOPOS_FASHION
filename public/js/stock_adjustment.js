$(document).ready(function() {
    //Add products
    if ($('#search_product_for_srock_adjustment').length > 0) {
        //Add Product
        $('#search_product_for_srock_adjustment')
            .autocomplete({
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
                            $(this)
                                .data('ui-autocomplete')
                                ._trigger('select', 'autocompleteselect', ui);
                            $(this).autocomplete('close');
                        }
                    } else if (ui.content.length == 0) {
                        swal(LANG.no_products_found);
                    }
                },
                focus: function(event, ui) {
                    if (ui.item.qty_available <= 0) {
                        return false;
                    }
                },
                select: function(event, ui) {
                    if (ui.item.qty_available > 0) {
                        $(this).val(null);
                        stock_adjustment_product_row(ui.item.variation_id);
                    } else {
                        alert(LANG.out_of_stock);
                    }
                },
            })
            .autocomplete('instance')._renderItem = function(ul, item) {
            if (item.qty_available <= 0) {
                var string = '<li class="ui-state-disabled">' + item.name;
                if (item.type == 'variable') {
                    string += '-' + item.variation;
                }
                string += ' (' + item.sub_sku + ') (Out of stock) </li>';
                return $(string).appendTo(ul);
            } else if (item.enable_stock != 1) {
                return ul;
            } else {
                var string = '<div>' + item.name;
                if (item.type == 'variable') {
                    string += '-' + item.variation;
                }
                string += ' (' + item.sub_sku + ') </div>';
                return $('<li>')
                    .append(string)
                    .appendTo(ul);
            }
        };
    }

    $('select#location_id').change(function() {
        if ($(this).val()) {
            $('#search_product_for_srock_adjustment').removeAttr('disabled');
        } else {
            $('#search_product_for_srock_adjustment').attr('disabled', 'disabled');
        }
        $('table#stock_adjustment_product_table tbody').html('');
        $('#product_row_index').val(0);
        update_table_total();
    });

    $(document).on('change', 'input.product_quantity', function() {
        update_table_row($(this).closest('tr'));
    });
    $(document).on('change', 'input.product_unit_price', function() {
        update_table_row($(this).closest('tr'));
    });

    $(document).on('click', '.remove_product_row', function() {
        swal({
            title: LANG.sure,
            icon: 'warning',
            buttons: true,
            dangerMode: true,
        }).then(willDelete => {
            if (willDelete) {
                $(this)
                    .closest('tr')
                    .remove();
                update_table_total();
            }
        });
    });

    //Date picker
    $('#transaction_date').datetimepicker({
        format: moment_date_format + ' ' + moment_time_format,
        ignoreReadonly: true,
    });

    $('form#stock_adjustment_form').validate();

    stock_adjustment_table = $('#stock_adjustment_table').DataTable({
        processing: true,
        serverSide: true,
        fixedHeader:false,
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
            { data: 'final_total', name: 'final_total' },
            { data: 'total_amount_recovered', name: 'total_amount_recovered' },
            { data: 'additional_notes', name: 'additional_notes' },
            { data: 'added_by', name: 'u.first_name' },
        ],
        fnDrawCallback: function(oSettings) {
            __currency_convert_recursively($('#stock_adjustment_table'));
        },
    });
    var detailRows = [];

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
                            toastr.success(result.msg);
                            stock_adjustment_table.ajax.reload();
                        } else {
                            toastr.error(result.msg);
                        }
                    },
                });
            }
        });
    });
});

function stock_adjustment_product_row(variation_id) {
    var row_index = parseInt($('#product_row_index').val());
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
        },
    });
}

function update_table_total() {
    var table_total = 0;
    $('table#stock_adjustment_product_table tbody tr').each(function() {
        var this_total = parseFloat(__read_number($(this).find('input.product_line_total')));
        if (this_total) {
            table_total += this_total;
        }
    });
    $('input#total_amount').val(table_total);
    $('span#total_adjustment').text(__number_f(table_total));
}

function update_table_row(tr) {
    var quantity = parseFloat(__read_number(tr.find('input.product_quantity')));
    var unit_price = parseFloat(__read_number(tr.find('input.product_unit_price')));
    var row_total = 0;
    if (quantity && unit_price) {
        row_total = quantity * unit_price;
    }
    tr.find('input.product_line_total').val(__number_f(row_total));
    update_table_total();
}

///////////////////// 📤 استيراد المنتجات من ملف إكسل 001
$(document).on('submit', '#export_quantity_products_modal form', function(e) {
    e.preventDefault();
    let formData = new FormData(this); 
    let url = $(this).attr('action');

    // جلب العداد الحالي لضمان عدم تداخل المعرفات (IDs)
    let currentRows = parseInt($('#product_row_index').val()) || 0;
    formData.append('location_id', $('#location_id').val()); 
    formData.append('row_count', currentRows); 

    let btn = $(this).find('button[type="submit"]');
    let btn_text = btn.html();
    btn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i>');

    $.ajax({
        method: 'POST',
        url: url,
        data: formData,
        dataType: 'json',
        processData: false, 
        contentType: false, 
        success: function(result) {
            btn.prop('disabled', false).html(btn_text);
            
            if (result.success) {
                // 1. إضافة الأسطر الناجحة إلى الجدول إذا وجدت
                if (result.html && result.html.trim() !== '') {
                    let $wrappedHtml = $('<table><tbody>' + result.html + '</tbody></table>');
                    let $newRows = $wrappedHtml.find('tr.product_row');

                    $newRows.each(function() {
                        let $currentRow = $(this).clone(); 
                        let variation_id = $currentRow.find('.variation_id').val();
                        let new_qty = parseFloat($currentRow.find('.product_quantity').val()) || 0;

                        // البحث عن المنتج في الجدول الحالي للدمج
                        let existingRow = $('#stock_adjustment_product_table tbody')
                                            .find('.variation_id[value="' + variation_id + '"]')
                                            .closest('tr');

                        if (existingRow.length > 0) {
                            let current_qty = parseFloat(__read_number(existingRow.find('.product_quantity'))) || 0;
                            let total_qty = current_qty + new_qty;
                            
                            __write_number(existingRow.find('.product_quantity'), total_qty);
                            update_table_row(existingRow);
                        } else {
                            $('#stock_adjustment_product_table tbody').append($currentRow);
                            currentRows++;
                        }
                    });

                    // تحديث العداد المخفي والإجمالي
                    $('#product_row_index').val(currentRows);
                    update_table_total();
                }

                // 2. معالجة التنبيهات والرسائل (المقبول والمرفوض)
                if (result.skipped_count > 0) {
                    // بناء رسالة تحتوي على رابط تحميل ملف المرفوضات
                    let downloadLink = '';
                    if (result.download_url) {
                        downloadLink = '<br><a href="' + result.download_url + '" target="_blank" style="color: #fff; font-weight: bold; text-decoration: underline;">' +
                                       '<i class="fa fa-download"></i> تحميل ملف المنتجات المرفوضة</a>';
                    }

                    toastr.warning(result.msg + downloadLink, "تقرير الاستيراد", {
                        "timeOut": 0,            // تجعل الرسالة لا تختفي تلقائياً
                        "extendedTimeOut": 0,    // لضمان بقائها حتى يراها المستخدم
                        "closeButton": true,     // إضافة زر إغلاق يدوي
                        "tapToDismiss": false    // تمنع اختفاء الرسالة عند الضغط العشوائي
                    });
                } else {
                    // إظهار رسالة نجاح خضراء عادية إذا تمت إضافة كل شيء
                    toastr.success(result.msg);
                }

                $('#export_quantity_products_modal').modal('hide');
                $('#export_quantity_products_modal form')[0].reset();

            } else {
                // عرض رسائل الخطأ المنطقية (مثل ملف فارغ)
                toastr.error(result.msg);
            }
        },
        error: function(e) {
            btn.prop('disabled', false).html(btn_text);
            toastr.error("حدث خطأ أثناء الرفع، تأكد من إعدادات السيرفر وصيغة الملف");
        }
    });
});


$(document).on('shown.bs.modal', '.view_modal', function() {
    __currency_convert_recursively($('.view_modal'));
});
