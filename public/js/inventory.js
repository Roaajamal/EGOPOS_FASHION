$(document).ready(function () {

    let rowCount = 0;

    // ===============================
    // 🔍 Autocomplete search product
    // ===============================
   $('#search_product').autocomplete({
    delay: 500,
    autoFocus: false,
    source: function (request, response) {
        $.ajax({
            url: '/inventory/get-products',
            dataType: 'json',
            data: {
                term: request.term,
                location_id: $('#location_id').val()
            },
            success: function (data) {
                let isAutoSelect = $('#auto_select_products_checkbox').is(':checked');

                if (isAutoSelect && data.length === 1) {
                    add_product_row(data[0].product_id, data[0].variation_id);
                    $('#search_product').val(''); 
                    $('#search_product').autocomplete("close"); 
                    response([]); 
                    return;
                } else if (data.length === 0) {
                    toastr.error("هذا المنتج غير موجود");
                }
                
                response(data);
            }
        });
    },
    minLength: 2,
    select: function (event, ui) {
        event.preventDefault();
        event.stopPropagation();

        add_product_row(ui.item.product_id, ui.item.variation_id);
        $(this).val('');
        
        return false;
    }
}).autocomplete('instance')._renderItem = function (ul, item) {
    // التحقق من الحقول المتاحة لمنع ظهور قوسين فارغين
    // 1. نحاول جلب الاسم من name، وإذا لم يوجد نأخذ النص الكامل من label أو text
    var name = item.name || item.text || item.label || "منتج غير معروف";
    
    // 2. نحاول جلب الـ SKU، وإذا كان موجوداً نضعه بين قوسين، وإذا لم يوجد نترك النص فارغاً
    var sku = item.sub_sku ? " (" + item.sub_sku + ")" : "";

    return $("<li>")
        .append("<div>" + name + sku + "</div>")
        .appendTo(ul);
};

$(document).on('keypress', '#search_product', function(e) {
    if (e.which == 13) { 
        e.preventDefault();
        return false;
    }
});
    // ===============================
    // ➕ Add product row
    // ===============================
    function add_product_row(product_id, variation_id) {
        let location_id = $('#location_id').val();
        if (!location_id) {
            toastr.error("يرجى اختيار الفرع أولاً");
            return;
        }

        let existingRow = null;
        $('#quantity_table tbody tr').each(function () {
            let rowVariationId = $(this).find('input.variation_id').val();
            
            if (rowVariationId && parseInt(rowVariationId) === parseInt(variation_id)) {
                existingRow = $(this);
                return false; 
            }
        });

        if (existingRow) {
            let qtyInput = existingRow.find('.quantity');
            let currentQty = __read_number(qtyInput);
            qtyInput.val(__number_f(currentQty + 1)).change(); 
            
            existingRow.css('background-color', '#ffff99').animate({backgroundColor: 'transparent'}, 1000);
            toastr.info("تم زيادة كمية المنتج الموجود مسبقاً");
        } else {
            $.ajax({
                url: '/inventory/get-entry-row',
                method: 'GET',
                data: {
                    product_id: product_id,
                    variation_id: variation_id,
                    row_count: $('#quantity_table tbody tr').length, 
                    location_id: location_id
                },
                success: function (html) {
                    if(html.trim() == "") {
                        toastr.error("تعذر جلب بيانات المنتج");
                        return;
                    }
                    let $newRow = $(html);
                    $('#quantity_table tbody').append($newRow);
                    
                    update_table_sr_number();
                    updateRowTotal($newRow);
                    updateGrandTotals();
                }
            });
        }
    }

    // ===============================
    // ✏️ Change quantity or price
    // ===============================
    $(document).on('input change', '.quantity, .purchase_price', function () {
        let row = $(this).closest('tr');
        updateRowTotal(row);
        updateGrandTotals();
    });

    // ===============================
    // ➕ Increase quantity
    // ===============================
    $(document).on('click', '.increment_qty', function () {
        let row = $(this).closest('tr');
        let qtyInput = row.find('.quantity');
        let qty = parseFloat(qtyInput.val()) || 0;
        qtyInput.val(qty + 1);
        updateRowTotal(row);
        updateGrandTotals();
    });

    // ===============================
    // ➖ Decrease quantity
    // ===============================
    $(document).on('click', '.decrement_qty', function () {
        let row = $(this).closest('tr');
        let qtyInput = row.find('.quantity');
        let qty = parseFloat(qtyInput.val()) || 0;
        if (qty > 1) {
            qtyInput.val(qty - 1);
            updateRowTotal(row);
            updateGrandTotals();
        }
    });

    // ===============================
    // ❌ Remove row
    // ===============================
    $(document).on('click', '.remove_row', function () {
        let row = $(this).closest('tr');
        row.remove();
        update_table_sr_number();
        updateGrandTotals();
    });

    ///////////////// prevent enter nrgative value
    $(document).on('change', '.quantity', function() {
    let val = parseFloat($(this).val());
    
    if (val < 0) {
        $(this).val(''); // مسح القيمة تماماً
        toastr.error('الكمية المدخلة لا يمكن أن تكون أقل من الصفر');
    }
});

// منع كتابة علامة الناقص (-) نهائياً من لوحة المفاتيح
$(document).on('keypress', '.quantity', function(e) {
    if (e.which == 45) { // رمز مفتاح الناقص (-)
        e.preventDefault();
        toastr.warning('غير مسموح بإدخال أرقام سالبة');
    }
});

    // ===============================
    // 🔢 Update serial numbers
    // ===============================
    function update_table_sr_number() {
        let i = 1;
        $('#quantity_table tbody tr').each(function () {
            $(this).find('.sr_number').text(i);
            i++;
        });
    }

    // ===============================
    // 💰 Update row total
    // ===============================
    window.updateRowTotal = function (row) {
        let qty = __read_number(row.find('.quantity'));
        let current_stock = __read_number(row.find('.current_stock'));
        let price = __read_number(row.find('.purchase_price'));
        
        let diff = qty - current_stock;
        let diff_element = row.find('.stock_diff');
        
        diff_element.text(__number_f(diff, false)); 

        if (diff < 0) {
            diff_element.css('color', 'red');
        } else if (diff > 0) {
            diff_element.css('color', 'green');
        } else {
            diff_element.css('color', 'black');
        }

        let total = qty * price;
        row.find('.row_total').html(__currency_trans_from_en(total, true)); 
        row.find('.line_total').val(total);
    };

    // ===============================
    // 📊 Update grand totals
    // ===============================
    window.updateGrandTotals = function () {
        let totalQty = 0;
        let grandTotal = 0;

        $('#quantity_table tbody tr').each(function () {
            let qty = __read_number($(this).find('.quantity'));
            let lineTotal = __read_number($(this).find('.line_total'));
            totalQty += qty;
            grandTotal += lineTotal;
        });

        $('#total_quantity').text(__number_f(totalQty));
        $('#grand_total').html(__currency_trans_from_en(grandTotal, true)); 
        $('#grand_total_hidden').val(grandTotal);
    };

    // ===============================
    // 🔁 Recalculate all rows
    // ===============================
    window.recalculateAllRows = function () {
        $('#quantity_table tbody tr').each(function () {
            updateRowTotal($(this));
        });
        updateGrandTotals();
    };

    // ==========================================
    // 📤 5. استيراد المنتجات من الإكسل
    // ==========================================
   $(document).on('submit', '#import_new_quantity_products_modal form', function(e) {
    e.preventDefault();
    let formData = new FormData(this); 
    let url = $(this).attr('action');
    let currentRows = $('#quantity_table tbody tr').length;
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
                if (result.html && result.html.trim() !== '') {
                    let $newRows = $(result.html);
                    let hasNegative = false;
                    let errorSkus = [];

                    // --- الفحص قبل الإضافة للجدول ---
                    $newRows.each(function() {
                        let qty = parseFloat($(this).find('.quantity').val()) || 0;
                        let sku = $(this).find('td:nth-child(2)').text().trim(); // افترضنا العمود الثاني هو SKU
                        if (qty < 0) {
                            hasNegative = true;
                            errorSkus.push(sku);
                        }
                    });

                    if (hasNegative) {
                        toastr.error("فشل الاستيراد: الملف يحتوي على قيم سالبة للمنتجات: " + errorSkus.join(', '));
                        $('#import_new_quantity_products_modal form')[0].reset();
                        return; // توقف هنا ولا تضف أي شيء للجدول
                    }
                    // --- نهاية الفحص ---

                    let tbody = $('#quantity_table tbody');
                    tbody.hide(); 

                    $newRows.each(function() {
                        let $currentRow = $(this);
                        let variation_id = $currentRow.find('.variation_id').val();
                        let existingRow = tbody.find('.variation_id[value="' + variation_id + '"]').closest('tr');

                        if (existingRow.length > 0) {
                            let new_qty = parseFloat($currentRow.find('.quantity').val()) || 0;
                            let current_qty = parseFloat(existingRow.find('.quantity').val()) || 0;
                            existingRow.find('.quantity').val(current_qty + new_qty);
                            updateRowTotal(existingRow);
                        } else {
                            tbody.append($currentRow);
                        }
                    });

                    tbody.show();
                    update_table_sr_number();
                    recalculateAllRows();
                    $('#import_new_quantity_products_modal').modal('hide');
                    toastr.success("تم الاستيراد بنجاح");
                    $('#import_new_quantity_products_modal form')[0].reset();
                }
            } else {
                toastr.error(result.msg);
            }
        },
        error: function() {
            btn.prop('disabled', false).html(btn_text);
            toastr.error("حدث خطأ أثناء الرفع");
        }
    });
});

    // ==========================================
    // 💾 6. منطق الحفظ النهائي مع المودالات والتصفير
    // ==========================================
    $(document).on('submit', 'form#inventory_form', function(e) {
    e.preventDefault();
        
        let additions = [];
        let adjustments = [];

        // 1. فرز المنتجات من الجدول
        $('#quantity_table tbody tr').each(function() {
            let row = $(this);
            let name = row.find('td:nth-child(6)').text().trim(); // ✅ تغير من 3 إلى 6 بعد إضافة موديل/حجم/لون
            let system_qty = __read_number(row.find('.current_stock'));
            let physical_qty = __read_number(row.find('.quantity'));
            let diff = physical_qty - system_qty;

            if (diff > 0) {
                additions.push({ name: name, system: system_qty, physical: physical_qty, diff: diff });
            } else if (diff < 0) {
                adjustments.push({ name: name, system: system_qty, physical: physical_qty, diff: Math.abs(diff) });
            }
        });

        // 2. منطق عرض المودالات بالتتابع
        if (additions.length > 0) {
            $('#additions_review_table tbody').empty();
            let html = '';
            additions.forEach(item => {
                html += `<tr>
                <td>${item.name}</td>
                <td>${__number_f(item.system)}</td>
                <td>${__number_f(item.physical)}</td>
                <td class="text-success text-bold">+${__number_f(item.diff)}</td>
                </tr>`;
            });
            $('#additions_review_table tbody').html(html);
            $('#review_additions_modal').modal('show');
        } else if (adjustments.length > 0) {
            showAdjustmentsModal(adjustments);
        } else if ($('#final_zero_out_checkbox').is(':checked')) {
            handleZeroOutConfirmation();
        } else {
            toastr.warning("لا يوجد فروقات في الجرد لحفظها");
        }

        // زر تأكيد الإدخالات ينتقل لمودال الإخراجات أو تأكيد التصفير
        $('#confirm_additions_btn').off('click').on('click', function() {
            $('#review_additions_modal').modal('hide');
            if (adjustments.length > 0) {
                showAdjustmentsModal(adjustments);
            } else {
                handleZeroOutConfirmation();
            }
        });

        // زر التأكيد النهائي للمخرجات ينتقل لتأكيد التصفير
        $('#confirm_final_save_btn').off('click').on('click', function() {
            $('#review_adjustments_modal').modal('hide');
            handleZeroOutConfirmation();
        });
    });

    function showAdjustmentsModal(adjustments) {
        $('#adjustments_review_table tbody').empty();
        let html = '';
        adjustments.forEach(item => {
            html += `<tr>
                <td>${item.name}</td>
                <td>${__number_f(item.system)}</td>
                <td>${__number_f(item.physical)}</td>
                <td class="text-danger text-bold">-${__number_f(item.diff)}</td>
            </tr>`;
        });
        $('#adjustments_review_table tbody').html(html);
        $('#review_adjustments_modal').modal('show');
    }

    // دالة التعامل مع تأكيد التصفير قبل الحفظ الفعلي
    function handleZeroOutConfirmation() {
        if ($('#final_zero_out_checkbox').is(':checked')) {
            Swal.fire({
                title: 'تأكيد تصفير المخزون',
                text: "تحذير: لقد قمت بتفعيل خيار تصفير باقي المنتجات. هذا سيجعل رصيد أي منتج لم تضفه للجدول يساوي صفر. هل تريد الاستمرار؟",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'نعم، صفر واحفظ',
                cancelButtonText: 'إلغاء'
            }).then((result) => {
                if (result.isConfirmed) {
                    actualSubmitProcess();
                }
            });
        } else {
            actualSubmitProcess();
        }
    }

    function actualSubmitProcess() {
        let form = $('form#inventory_form');
        let btn = form.find('button[type="submit"]');
        let url = form.attr('action');

        let allProducts = [];
        $('#quantity_table tbody tr').each(function() {
            let row = $(this);
            allProducts.push({
                product_id: row.find('.product_id').val(),
                variation_id: row.find('.variation_id').val(),
                quantity: __read_number(row.find('.quantity')),
                current_stock: __read_number(row.find('.current_stock')),
                purchase_price: __read_number(row.find('.purchase_price'))
            });
        });

        if (allProducts.length === 0 && !$('#final_zero_out_checkbox').is(':checked')) {
            toastr.error("الجدول فارغ!");
            return;
        }

        Swal.fire({
            title: 'جاري تنفيذ عمليات الجرد والتسوية...',
            allowOutsideClick: false,
            didOpen: () => { Swal.showLoading(); }
        });

        $.ajax({
            method: 'POST',
            url: url,
            data: {
                _token: $('meta[name="csrf-token"]').attr('content'),
        location_id: $('#location_id').val(),
        transaction_date: $('#transaction_date').val(),
        ref_no: $('#ref_no').val(),
        final_zero_out: $('#final_zero_out_checkbox').is(':checked') ? 1 : 0,
        products: JSON.stringify(allProducts)
            },
            dataType: 'json',
            success: function(result) {
                Swal.close();
                if (result.success) {
                    $(window).off('beforeunload');
                    Swal.fire({
                        icon: 'success',
                        title: 'تم بنجاح',
                        text: result.msg,
                    }).then(() => {
                        window.location.href = '/inventory'; 
                    });
                } else {
                    Swal.fire({ icon: 'error', title: 'خطأ', text: result.msg });
                }
            },
            error: function(e) {
                Swal.close();
                toastr.error("حدث خطأ في الخادم");
            }
        });
    }

    // ⛔ منع الخروج إذا وجد بيانات
    $(window).on('beforeunload', function() {
        if ($('#quantity_table tbody tr').length > 0) {
            return "لديك تغييرات غير محفوظة، هل أنت متأكد من مغادرة الصفحة؟";
        }
    });
});