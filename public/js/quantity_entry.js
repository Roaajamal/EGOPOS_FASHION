$(document).ready(function () {

    let rowCount = 0;

    // ===============================
    // 🔍 Autocomplete search product
    // ===============================
  $('#search_product').autocomplete({
    delay: 500, // سرعة استجابة للسكانر
    autoFocus: false, // منع الاختيار العشوائي
    source: function (request, response) {
        $.ajax({
            url: '/quantity-entry/get-products',
            dataType: 'json',
            data: {
                term: request.term,
                location_id: $('#location_id').val()
            },
            success: function (data) {
                let isAutoSelect = $('#auto_select_products_checkbox').is(':checked');

                // إذا وجدنا نتيجة واحدة والـ Checkbox مفعل (حالة السكانر المثالية)
                if (isAutoSelect && data.length === 1) {
                    add_product_row(data[0].product_id, data[0].variation_id);
                    $('#search_product').val(''); 
                    $('#search_product').autocomplete("close");
                    
                    // منع استمرار الحدث لتجنب أي Submit تلقائي
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
        // ✅ منع السلوك الافتراضي للـ Enter
        event.preventDefault();
        event.stopPropagation();

        add_product_row(ui.item.product_id, ui.item.variation_id);
        $(this).val('');
        
        return false;
    }
}).autocomplete('instance')._renderItem = function (ul, item) {
    // إذا كان السيرفر يرسل الاسم في حقل label نستخدمه، وإلا نستخدم item.name
    var itemName = item.name || item.label || "منتج بدون اسم";
    var itemSku = item.sub_sku || item.sku || "";

    var displayLine = "<div>" + itemName + (itemSku ? " (" + itemSku + ")" : "") + "</div>";

    return $("<li>")
        .append(displayLine)
        .appendTo(ul);
};

// ✅ منع زر Enter تماماً في حقل البحث لضمان عدم تفعيل كبسة الحفظ بواسطة السكانر
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
    // 1. البحث هل المنتج موجود مسبقاً في الجدول؟
    let existingRow = null;
    $('#purchase_entry_table tbody tr').each(function () {
        // نستخدم .find('.variation_id') للبحث عن الـ input المخفي الذي يحتوي على الـ ID
        let rowVariationId = $(this).find('.variation_id').val();
        if (parseInt(rowVariationId) === parseInt(variation_id)) {
            existingRow = $(this);
            return false; // توقف عن البحث (break)
        }
    });

    if (existingRow) {
        // 2. إذا وجدنا المنتج: نزيد الكمية فقط
        let qtyInput = existingRow.find('.quantity');
        let currentQty = parseFloat(qtyInput.val()) || 0;
        qtyInput.val(currentQty + 1); // زيادة حبة واحدة (أو حسب رغبتك)
        
        // تحديث الإجماليات للصف والجدول
        updateRowTotal(existingRow);
        updateGrandTotals();
        
        toastr.info("تم زيادة كمية المنتج الموجود مسبقاً");
    } else {
        // 3. إذا لم يوجد المنتج: نطلبه من السيرفر كسطر جديد
        $.ajax({
            url: '/quantity-entry/get-entry-row',
            method: 'GET',
            data: {
                product_id: product_id,
                variation_id: variation_id,
                // نمرر الـ rowCount الحالي كـ index مؤقت
                row_count: $('#purchase_entry_table tbody tr').length, 
                location_id: $('#location_id').val()
            },
            success: function (html) {
                if(html.trim() == "") {
                    toastr.error("تعذر جلب بيانات المنتج");
                    return;
                }
                let $newRow = $(html);
                $('#purchase_entry_table tbody').append($newRow);
                
                // تحديث الأرقام التسلسلية والحسابات
                update_table_sr_number();
                recalculateAllRows();
            },
            error: function() {
                toastr.error("حدث خطأ في الاتصال بالسيرفر");
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
    let qty = __read_number(qtyInput);
    __write_number(qtyInput, qty + 1); // استخدام __write_number للكتابة الصحيحة
    updateRowTotal(row);
    updateGrandTotals();
});

// نقصان الكمية
$(document).on('click', '.decrement_qty', function () {
    let row = $(this).closest('tr');
    let qtyInput = row.find('.quantity');
    let qty = __read_number(qtyInput);
    if (qty > 1) {
        __write_number(qtyInput, qty - 1);
        updateRowTotal(row);
        updateGrandTotals();
    }
});

    // ===============================
    // ❌ Remove row
    // ===============================
    $(document).on('click', '.remove_row', function() {
    $(this).closest('tr').remove();
    update_table_sr_number();
    updateGrandTotals();
});

    // ===============================
    // 🔢 Update serial numbers
    // ===============================
    function update_table_sr_number() {
        let i = 1;
        $('#purchase_entry_table tbody tr').each(function () {
            $(this).find('.sr_number').text(i);
            i++;
        });
    }

    // ===============================
    // 💰 Update row total
    // ===============================
    window.updateRowTotal = function (row) {
    let qty = parseFloat(row.find('.quantity').val()) || 0;
    // التأكد من جلب السعر بكامل أعشاره
    let price = parseFloat(row.find('.purchase_price').val()) || 0;
    
    let total = qty * price;
    
    // استخدمنا total مباشرة بدون toFixed لضمان عدم حذف أي رقم
    row.find('.row_total').text(total); 
    row.find('.line_total').val(total);
};
// ===============================
// 📊 Update grand totals
// ===============================
window.updateGrandTotals = function () {
   let totalQty = 0;
    let grandTotal = 0;

    $('#purchase_entry_table tbody tr').each(function () {
        // قراءة الكمية من الحقل (input)
        let qty = __read_number($(this).find('.quantity'));
        
        // قراءة إجمالي السطر من الحقل المخفي (input)
        // تأكدي أن الكلاس هو .line_total وليس .row_total
        let lineTotal = __read_number($(this).find('.line_total'));
        
        totalQty += qty;
        grandTotal += lineTotal;
    });

    // تحديث النصوص في أسفل الجدول بتنسيق النظام
    $('#total_quantity').text(__number_f(totalQty));
    $('#grand_total').text(__number_f(grandTotal));
    
    // تحديث الحقل المخفي النهائي لإرساله للسيرفر
    $('#grand_total_hidden').val(grandTotal);
};

    // ===============================
    // 🔁 Recalculate all rows
    // ===============================
    window.recalculateAllRows = function () {
        $('#purchase_entry_table tbody tr').each(function () {
            updateRowTotal($(this));
        });
        updateGrandTotals();
    };

    // ===============================
    // 🌟 تحديث المخزون عبر Ajax
    // ===============================
    function updateStock(product_id, variation_id, quantity) {
        $.ajax({
            url: '/quantity-entry/update-stock',
            type: 'POST',
            data: {
                _token: $('meta[name="csrf-token"]').attr('content'),
                product_id: product_id,
                variation_id: variation_id,
                location_id: $('#location_id').val(),
                quantity: quantity
            },
            success: function(response) {
                if(response.success){
                    console.log('Stock updated: ' + response.new_stock);
                }
            }
        });
    }

// ==========================================
    // 📤 5. استيراد المنتجات من الإكسل
    // ==========================================
  $(document).on('submit', '#import_new_quantity_products_modal form', function(e) {
    e.preventDefault();
    let formData = new FormData(this); 
    let url = $(this).attr('action');
    let currentRows = $('#purchase_entry_table tbody tr').length;
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
            // 1. تنظيف الـ HTML وتحويله لصفوف حصراً
            let cleanedHtml = result.html.trim();
            let $newRows = $(cleanedHtml).filter('tr');

            let tbody = $('#purchase_entry_table tbody');

            $newRows.each(function() {
                let $currentRow = $(this);
                let variation_id = $currentRow.find('.variation_id').val();
                
                // البحث عن المنتج إذا كان موجوداً مسبقاً
                let existingRow = tbody.find('.variation_id[value="' + variation_id + '"]').closest('tr');

                if (existingRow.length > 0) {
                    // إذا كان موجوداً: نجمع الكميات
                    let new_qty = __read_number($currentRow.find('.quantity'));
                    let current_qty = __read_number(existingRow.find('.quantity'));
                    
                    __write_number(existingRow.find('.quantity'), current_qty + new_qty);
                    updateRowTotal(existingRow);
                } else {
                    // إذا كان جديداً: نضيف السطر كاملاً بدون حذف أي TD
                    tbody.append($currentRow);
                    
                    // تفعيل تنسيق الأرقام والحسابات
                    if (typeof initialize_input_number == 'function') {
                        initialize_input_number($currentRow);
                    }
                    updateRowTotal($currentRow);
                }
            });

            update_table_sr_number();
            updateGrandTotals();
            
           // ✅ رسالة النجاح مع عدد المتخطين
            let message = "تم استيراد " + result.imported_count + " صنف بنجاح.";
            if (result.skipped_count > 0) {
                message += " تم تخطي " + result.skipped_count + " صنف.";
            }
            toastr.success(message);
            // ✅ أغلق الموديل وصفر الفورم قبل الـ confirm
$('#import_new_quantity_products_modal').modal('hide');
$('#import_new_quantity_products_modal form')[0].reset();

            // ✅ سؤال تنزيل المرفوضين
            if (result.products_insufficient && result.products_insufficient.length > 0) {
                let confirmed = confirm("تم تخطي " + result.skipped_count + " صنف. هل تريد تنزيل ملف المنتجات المرفوضة؟");
                if (confirmed) {
                    exportInsufficientToExcel(result.products_insufficient);
                }
            }

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
    // 💾 6. منطق الحفظ النهائي (الدفعات + التراجع)
    // ==========================================
    $(document).on('submit', 'form#add_quantity_form', function(e) {
        e.preventDefault();
        let form = $(this);
        let btn = form.find('button[type="submit"]');
        let ref_no = $('#ref_no').val() || ("QE-" + Date.now()); 

        let allProducts = [];
        $('#purchase_entry_table tbody tr').each(function() {
        let row = $(this);
    let p_id = row.find('input[name*="[product_id]"]').val();
    let v_id = row.find('.variation_id').val();
    
    // قراءة الأرقام الخام (بدون فواصل) لإرسالها للسيرفر
    let qty = __read_number(row.find('.quantity'));
    let price = __read_number(row.find('.purchase_price'));

    allProducts.push({
        product_id: p_id,
        variation_id: v_id,
        quantity: qty,
        purchase_price: price
    });
    });

        if (allProducts.length === 0) {
            toastr.error("الجدول فارغ!");
            return false;
        }

        // تقسيم البيانات إلى دفعات (Chunks)
        let chunkSize = 200;
        let chunks = [];
        for (let i = 0; i < allProducts.length; i += chunkSize) {
            chunks.push(allProducts.slice(i, i + chunkSize));
        }

        btn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> جاري الحفظ...');

        // الاحتفاظ بنسخة وتفريغ الجدول لتجنب max_input_vars
        let tableBackup = $('#purchase_entry_table tbody').html();
        $('#purchase_entry_table tbody').empty();

        let currentChunkIndex = 0;

        function sendNextChunk() {
            let isLastChunk = (currentChunkIndex === chunks.length - 1);

            $.ajax({
                method: 'POST',
                url: form.attr('action'),
                data: {
                    _token: $('meta[name="csrf-token"]').attr('content'),
                    location_id: $('#location_id').val(),
                    transaction_date: $('#transaction_date').val(),
                    ref_no: ref_no,
                    products: JSON.stringify(chunks[currentChunkIndex]),
                    is_last_chunk: isLastChunk ? 1 : 0
                },
                success: function(result) {
                    if (result.success) {
                        currentChunkIndex++;
                        if (currentChunkIndex < chunks.length) {
                            btn.html('<i class="fa fa-spinner fa-spin"></i> دفعة ' + currentChunkIndex + ' من ' + chunks.length);
                            sendNextChunk();
                        } else {
                            $(window).off('beforeunload');
                            toastr.success("تم الحفظ وتحديث المخزون بنجاح!");
                            window.location.href = '/quantity-entry';
                        }
                    } else {
                        // فشل: تراجع عن كل الدفعات السابقة
                        rollbackTransaction(ref_no, tableBackup);
                        toastr.error("فشل في الدفعة: " + result.msg);
                    }
                },
                error: function() {
                    rollbackTransaction(ref_no, tableBackup);
                    toastr.error("خطأ اتصال بالسيرفر. تم إلغاء العملية.");
                }
            });
        }

        sendNextChunk();

        function rollbackTransaction(ref_no, backup) {
            $.post('/quantity-entry/cleanup', { 
                ref_no: ref_no, 
                _token: $('meta[name="csrf-token"]').attr('content') 
            }, function() {
                $('#purchase_entry_table tbody').html(backup);
                btn.prop('disabled', false).html('حفظ');
                recalculateAllRows();
            });
        }
    });

    // ⛔ منع الخروج إذا وجد بيانات
    $(window).on('beforeunload', function() {
        if ($('#purchase_entry_table tbody tr').length > 0) {
            return "لديك تغييرات غير محفوظة، هل أنت متأكد من مغادرة الصفحة؟";
        }
    });

window.exportInsufficientToExcel = function(products) {
    let csvContent = "SKU,اسم المنتج,الكمية,السبب\n";
    
    products.forEach(function(p) {
        csvContent += [
            p.sub_sku,
            p.product_name ?? '',
            p.qty,
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

});



