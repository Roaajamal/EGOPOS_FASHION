$(document).ready(function() {
    //For edit pos form
    if ($('form#sell_return_form').length > 0) {
        pos_form_obj = $('form#sell_return_form');
    } else {
        pos_form_obj = $('form#add_pos_sell_form');
    }
    if ($('form#sell_return_form').length > 0 || $('form#add_pos_sell_form').length > 0) {
        initialize_printer();
    }

    //Date picker
    $('#transaction_date').datetimepicker({
        format: moment_date_format + ' ' + moment_time_format,
        ignoreReadonly: true,
    });

    pos_form_validator = pos_form_obj.validate({
        submitHandler: function(form) {
            var cnf = true;

            if (cnf) {
                var data = $(form).serialize();
                var url = $(form).attr('action');
                $.ajax({
                    method: 'POST',
                    url: url,
                    data: data,
                    dataType: 'json',
                    success: function(result) {
                        if (result.success == 1) {
                            toastr.success(result.msg);
                            
                            //Check if enabled or not
                            if (result.receipt && result.receipt.is_enabled) { // ✅ أضفنا التحقق من وجود receipt
                                 console.log("الفاتورة:", result.receipt);
            
            // تأخير الطباعة قليلاً
            setTimeout(function() {
                if (result.receipt.is_enabled) {
                    pos_print(result.receipt);
                } else if (result.receipt.html_content) {
                    pos_print({
                        print_type: 'browser',
                        html_content: result.receipt.html_content,
                        print_title: result.receipt.print_title || 'فاتورة مرتجع'
                    });
                }
            }, 500);
        }
                            
                            // إظهار موديل الدفع بعد الحفظ
                            if (result.return_id) {
                                setTimeout(function() {
                                    showPaymentModal(result.return_id);
                                }, 1000);
                            }
                            
                        } else {
                            toastr.error(result.msg);
                        }
                    },
                    error: function(xhr, status, error) {
                        console.log("حدث خطأ في الطلب:");
                        console.log(xhr.responseText);
                        toastr.error('حدث خطأ أثناء حفظ المرتجع');
                    }
                });
            }
            return false;
        }
    }); // ✅ تم إغلاق validate() بشكل صحيح هنا
    
    // دالة إظهار موديل الدفع
 function showPaymentModal(transactionId) {
    $.ajax({
        url: '/payments/add_payment/' + transactionId,
        method: 'GET',
        dataType: 'json', // تغيير إلى json لأن الكونترولر يرجع json
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        },
        success: function(response) {
            console.log("الاستجابة:", response); // للتأكد من محتوى الاستجابة
            
            // التحقق من حالة الدفع
            if (response.status == 'paid') {
                toastr.error(response.msg || 'الفاتورة مدفوعة بالفعل');
                return;
            }
            
            // إذا كان هناك view في الاستجابة
            if (response.view) {
                // إزالة أي موديل سابق
                $('#payment_modal').remove();
                
                // إنشاء الموديل
                var modal = $('<div class="modal fade" id="payment_modal" tabindex="-1" role="dialog"></div>');
                modal.html(response.view);
                $('body').append(modal);
                
                // إظهار الموديل
                $('#payment_modal').modal('show');
                
                // تهيئة الموديل
                initializePaymentModal();
            } else {
                toastr.error('لا يمكن إضافة دفعة لهذه الفاتورة');
            }
        },
        error: function(xhr, status, error) {
            console.log("خطأ في تحميل بيانات الدفع:");
            console.log("URL: /payments/add_payment/" + transactionId);
            console.log("Status:", status);
            console.log("Error:", error);
            console.log("Response:", xhr.responseText);
            
            toastr.error('حدث خطأ في تحميل بيانات الدفع');
        }
    });
}



    function initialize_printer() {
        if ($('input#location_id').data('receipt_printer_type') == 'printer') {
            initializeSocket();
        }
    }

    function pos_print(receipt) {
        //If printer type then connect with websocket
        if (receipt.print_type == 'printer') {
            var content = receipt;
            content.type = 'print-receipt';

            //Check if ready or not, then print.
            if (socket.readyState != 1) {
                initializeSocket();
                setTimeout(function() {
                    socket.send(JSON.stringify(content));
                }, 700);
            } else {
                socket.send(JSON.stringify(content));
            }
        } else if (receipt.html_content != '') {
            var title = document.title;
            if (typeof receipt.print_title != 'undefined') {
                document.title = receipt.print_title;
            }

            //If printer type browser then print content
            $('#receipt_section').html(receipt.html_content);
            __currency_convert_recursively($('#receipt_section'));
            setTimeout(function() {
                window.print();
                document.title = title;
            }, 1000);
        }
    }
});

// //Set the location and initialize printer
// function set_location(){
// 	if($('input#location_id').length == 1){
// 	       $('input#location_id').val($('select#select_location_id').val());
// 	       //$('input#location_id').data('receipt_printer_type', $('select#select_location_id').find(':selected').data('receipt_printer_ty
// 	}

// 	if($('input#location_id').val()){
// 	       $('input#search_product').prop( "disabled", false ).focus();
// 	} else {
// 	       $('input#search_product').prop( "disabled", true );
// 	}

// 	initialize_printer();
// }
