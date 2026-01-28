$(document).ready(function () {

    let rowCount = 0;

    // ===============================
    // 🔍 Autocomplete search product
    // ===============================
   $('#search_product').autocomplete({
    source: function (request, response) {
        $.ajax({
            url: '/quantity-entry/get-products',
            dataType: 'json',
            data: {
                term: request.term,
                location_id: $('#location_id').val()
            },
            success: function (data) {
                // التحقق إذا كان الـ Checkbox مفعل
                let isAutoSelect = $('#auto_select_products_checkbox').is(':checked');

                // إذا وجدنا نتيجة واحدة بالضبط والـ Checkbox مفعل
                if (isAutoSelect && data.length === 1) {
                    add_product_row(data[0].product_id, data[0].variation_id);
                    $('#search_product').val(''); // تفريغ حقل البحث
                    $('#search_product').autocomplete("close"); // إغلاق القائمة
                } else if (data.length === 0) {
                    toastr.error("هذا المنتج غير موجود");
                }
                
                response(data);
            }
        });
    },
    minLength: 2,
    select: function (event, ui) {
        add_product_row(ui.item.product_id, ui.item.variation_id);
        $(this).val('');
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
        let qty = parseFloat($(this).find('.quantity').val()) || 0;
        let lineTotal = parseFloat($(this).find('.line_total').val()) || 0;
        totalQty += qty;
        grandTotal += lineTotal;
    });

    // عرض المجموع بدقة كاملة
    $('#total_quantity').text(totalQty);
    $('#grand_total').text(grandTotal);
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

// 📤 Import products from Excel
    $(document).on('submit', '#import_new_quantity_products_modal form', function(e) {
        e.preventDefault();
        let formData = new FormData(this); 
        let url = $(this).attr('action');

        // نرسل الـ rowCount الحالي للسيرفر لكي يبدأ ترقيم الأسطر منه
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
            // 1. تحويل الـ HTML القادم إلى كائن jQuery مؤقت لفصل الأسطر
            let $newRows = $(result.html);

            $newRows.each(function() {
                let $currentRow = $(this);
                let variation_id = $currentRow.find('.variation_id').val();
                let new_qty = parseFloat($currentRow.find('.quantity').val()) || 0;
                let new_price = parseFloat($currentRow.find('.purchase_price').val()) || 0;

                // 2. البحث هل هذا الـ variation_id موجود أصلاً في الجدول؟
                let existingRow = $('#purchase_entry_table tbody').find('.variation_id[value="' + variation_id + '"]').closest('tr');

                if (existingRow.length > 0) {
                    // إذا وجدناه: نحدث الكمية والسعر
                    let current_qty = parseFloat(existingRow.find('.quantity').val()) || 0;
                    existingRow.find('.quantity').val(current_qty + new_qty);
                    
                    // تحديث السعر (اختياري: هل تريدين تحديث السعر لآخر سعر في الإكسل؟)
                    existingRow.find('.purchase_price').val(new_price);
                    
                    updateRowTotal(existingRow);
                } else {
                    // إذا لم نجده: نضيف السطر كاملاً
                    $('#purchase_entry_table tbody').append($currentRow);
                }
            });

            // 3. تحديث الأرقام التسلسلية والإجماليات بعد انتهاء الحلقة
            update_table_sr_number();
            recalculateAllRows();
            
            $('#import_new_quantity_products_modal').modal('hide');
            toastr.success("تم الاستيراد وتحديث الكميات بنجاح");
            $('#import_new_quantity_products_modal form')[0].reset();
        }
    } else {
        toastr.error(result.msg);
    }
},
            error: function(e) {
                btn.prop('disabled', false).html(btn_text);
                toastr.error("حدث خطأ أثناء الرفع");
            }
        });
    });

    // منع الخروج أو إعادة التحميل إذا كان الجدول يحتوي على بيانات
$(window).on('beforeunload', function() {
    if ($('#purchase_entry_table tbody tr').length > 0) {
        return "لديك تغييرات غير محفوظة، هل أنت متأكد من مغادرة الصفحة؟";
    }
});

// تعطيل التنبيه عند الضغط على زر الحفظ (أو إرسال الفورم الرئيسي)
$(document).on('submit', 'form#add_quantity_form', function() {
    $(window).off('beforeunload');
});

});
