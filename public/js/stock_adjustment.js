$(document).ready(function() {
    // إعدادات افتراضية لرسائل الخطأ لضمان ثباتها
    const errorOptions = {
        "timeOut": "0",
        "extendedTimeOut": "0",
        "closeButton": true,
        "tapToDismiss": false
    };

    // 1. إعداد البحث عن المنتجات
    if ($('#search_product_for_s_adj').length > 0 || $('#search_product_for_srock_adjustment').length > 0) {
        let search_field = $('#search_product_for_s_adj').length > 0 ? '#search_product_for_s_adj' : '#search_product_for_srock_adjustment';
        
        $(search_field).autocomplete({
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
                    // رسالة خطأ سويت أليرت (تبقى حتى يضغط المستخدم)
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
        }).autocomplete('instance')._renderItem = function(ul, item) {
            if (item.qty_available <= 0) {
                var string = '<li class="ui-state-disabled">' + item.name;
                if (item.type == 'variable') { string += '-' + item.variation; }
                string += ' (' + item.sub_sku + ') (Out of stock) </li>';
                return $(string).appendTo(ul);
            } else if (item.enable_stock != 1) {
                return ul;
            } else {
                var string = '<div>' + item.name;
                if (item.type == 'variable') { string += '-' + item.variation; }
                string += ' (' + item.sub_sku + ') </div>';
                return $('<li>').append(string).appendTo(ul);
            }
        };
    }

    // 2. تحديث عداد الأسطر
    if ($('#stock_adjustment_product_table tbody tr').length > 0) {
        let total_rows = $('#stock_adjustment_product_table tbody tr').length;
        if($('#product_row_index').length == 0){
             $('form').append('<input type="hidden" id="product_row_index" value="'+total_rows+'">');
        } else {
             $('#product_row_index').val(total_rows);
        }
        update_table_total();
    }

    // 3. تغيير الفرع
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

    // 4. مراقبة التغييرات
    $(document).on('input change keyup', 'input.product_quantity, input.product_unit_price', function() {
        var tr = $(this).closest('tr');
        update_table_row(tr);
        update_table_total();
    });

    // 5. حذف سطر
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
            }
        });
    });

    // 6. التاريخ والتحقق
    $('#transaction_date').datetimepicker({
        format: moment_date_format + ' ' + moment_time_format,
        ignoreReadonly: true,
    });
    $('form#stock_adjustment_form').validate();
    $('form#stock_adjustment_edit_form').validate();

    // 7. DataTable
    stock_adjustment_table = $('#stock_adjustment_table').DataTable({
        processing: true,
        serverSide: true,
        ajax: '/stock-adjustments',
        columnDefs: [{ targets: 0, orderable: false, searchable: false }],
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

    // 8. حذف السند
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
                            // تعديل رسالة الخطأ لتثبت
                            toastr.error(result.msg, "خطأ", errorOptions);
                        }
                    },
                });
            }
        });
    });

    // 10. استيراد الإكسل
    $(document).on('submit', '#export_quantity_products_modal form', function(e) {
        e.preventDefault();
        let formData = new FormData(this);
        formData.append('location_id', $('#location_id').val());
        formData.append('row_count', parseInt($('#product_row_index').val()) || 0);

        let btn = $(this).find('button[type="submit"]');
        let btn_text = btn.html();
        btn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i>');

        $.ajax({
            method: 'POST',
            url: $(this).attr('action'),
            data: formData,
            dataType: 'json',
            processData: false, 
            contentType: false, 
            success: function(result) {
                btn.prop('disabled', false).html(btn_text);
                if (result.success) {
                    if (result.html && result.html.trim() !== '') {
                        let $wrappedHtml = $('<table><tbody>' + result.html + '</tbody></table>');
                        let $newRows = $wrappedHtml.find('tr.product_row');
                        let currentRows = parseInt($('#product_row_index').val()) || 0;

                        $newRows.each(function() {
                            let $currentRow = $(this).clone();
                            let variation_id = $currentRow.find('.variation_id').val();
                            let new_qty = parseFloat($currentRow.find('.product_quantity').val()) || 0;
                            let existingRow = $('#stock_adjustment_product_table tbody').find('.variation_id[value="' + variation_id + '"]').closest('tr');

                            if (existingRow.length > 0) {
                                let current_qty = parseFloat(__read_number(existingRow.find('.product_quantity'))) || 0;
                                __write_number(existingRow.find('.product_quantity'), current_qty + new_qty);
                                update_table_row(existingRow);
                            } else {
                                $('#stock_adjustment_product_table tbody').append($currentRow);
                                currentRows++;
                            }
                        });
                        $('#product_row_index').val(currentRows);
                        update_table_total();
                    }
                    
                    if (result.skipped_count > 0) {
                        let downloadLink = result.download_url ? '<br><a href="' + result.download_url + '" target="_blank" style="color: #fff; font-weight: bold; text-decoration: underline;"><i class="fa fa-download"></i> تحميل ملف المرفوضات</a>' : '';
                        // تعديل تحذير المنتجات المرفوضة ليثبت
                        toastr.warning(result.msg + downloadLink, "تقرير الاستيراد", errorOptions);
                    } else {
                        toastr.success(result.msg);
                    }
                    $('#export_quantity_products_modal').modal('hide');
                    $('#export_quantity_products_modal form')[0].reset();
                } else {
                    // تعديل خطأ الاستيراد ليثبت
                    toastr.error(result.msg, "خطأ", errorOptions);
                }
            },
            error: function(e) {
                btn.prop('disabled', false).html(btn_text);
                // تعديل خطأ الرفع ليثبت
                toastr.error("حدث خطأ أثناء الرفع", "خطأ", errorOptions);
            }
        });
    });
});

// الدوال المساعدة
function stock_adjustment_product_row(variation_id) {
    var row_index = parseInt($('#product_row_index').val()) || 0;
    $.ajax({
        method: 'POST',
        url: '/stock-adjustments/get_product_row',
        data: { row_index: row_index, variation_id: variation_id, location_id: $('#location_id').val() },
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
        var row = $(this);
        var qty = parseFloat(__read_number(row.find('input.product_quantity'))) || 0;
        var unit_price = parseFloat(__read_number(row.find('input.product_unit_price'))) || 0;
        table_total += (qty * unit_price);
    });
    $('span#total_adjustment').text(__number_f(table_total));
    $('#total_adjustment_value').val(table_total);
}

function update_table_row(tr) {
    var quantity = parseFloat(__read_number(tr.find('input.product_quantity'))) || 0;
    var unit_price = parseFloat(__read_number(tr.find('input.product_unit_price'))) || 0;
    tr.find('input.product_line_total').val(__number_f(quantity * unit_price));
}

$(document).on('shown.bs.modal', '.view_modal', function() {
    __currency_convert_recursively($('.view_modal'));
});