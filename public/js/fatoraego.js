/**
 * FatoraEGO - إدارة الفواتير الإلكترونية
 */
(function($) {
    "use strict";
    
    var FatoraEgo = {
        /**
         * تهيئة الأزرار والفلاتر
         */
        init: function() {
            console.log('FatoraEgo initialized');
            this.bindEvents();
            this.initFilters(); // <-- تمت الإضافة هنا
        },
        
        /**
         * تهيئة فلاتر الفوترة
         */
        initFilters: function() {
            var self = this;
            
            console.log('Initializing fatora filters...');
            
            // فلتر حالة الفوترة
            $(document).on('change', '#fatora_status_filter', function() {
                var value = $(this).val();
                console.log('Fatora filter changed to:', value);
                
                // تحديث DataTable إذا كان موجوداً
                if (typeof window.sell_table !== 'undefined' && window.sell_table.ajax) {
                    console.log('Reloading sell_table with fatora_status:', value);
                    window.sell_table.ajax.reload();
                } else if (typeof sell_table !== 'undefined') {
                    console.log('Reloading sell_table (local variable)...');
                    sell_table.ajax.reload();
                } else {
                    console.error('sell_table not found');
                }
            });
            
            // للتأكد من العمل مع select2
            $(document).on('select2:select select2:unselect', '#fatora_status_filter', function() {
                var value = $(this).val();
                console.log('Select2 fatora filter changed to:', value);
                
                setTimeout(function() {
                    if (typeof window.sell_table !== 'undefined') {
                        window.sell_table.ajax.reload();
                    } else if (typeof sell_table !== 'undefined') {
                        sell_table.ajax.reload();
                    }
                }, 1);
            });
            
            // تحقق من وجود الفلتر عند التحميل
            setTimeout(function() {
                console.log('=== FATORA FILTER CHECK ===');
                console.log('Fatora filter exists:', $('#fatora_status_filter').length > 0);
                console.log('Current fatora filter value:', $('#fatora_status_filter').val());
                console.log('sell_table (global):', typeof window.sell_table !== 'undefined');
                console.log('sell_table (local):', typeof sell_table !== 'undefined');
            }, 1);
        },
        
        /**
         * ربط الأحداث
         */
        bindEvents: function() {
            var self = this;
            
            console.log('Binding fatora button events...');
            
            // زر إرسال الفاتورة العادية
            $(document).on('click', '.fatora-send-btn', function(e) {
                console.log('fatora-send-btn clicked');
                e.preventDefault();
                e.stopPropagation();
                self.sendInvoice($(this));
            });
            
            // زر إعادة إرسال الفاتورة العادية
            $(document).on('click', '.fatora-resend-btn', function(e) {
                console.log('fatora-resend-btn clicked');
                e.preventDefault();
                e.stopPropagation();
                self.sendInvoice($(this));
            });
            
            // زر عرض التفاصيل
            $(document).on('click', '.fatora-view-btn', function(e) {
                console.log('fatora-view-btn clicked');
                e.preventDefault();
                e.stopPropagation();
                self.viewDetails($(this));
            });
            
            // زر إرسال فاتورة المرتجعات
            $(document).on('click', '.fatora-send-credit-btn', function(e) {
                console.log('fatora-send-credit-btn clicked');
                e.preventDefault();
                e.stopPropagation();
                self.sendCreditInvoice($(this));
            });
        },
        
        /**
         * إرسال الفاتورة العادية
         */
        sendInvoice: function($btn) {
            console.log('sendInvoice called');
            
            var transactionId = $btn.data('id');
            var invoiceNo = $btn.data('invoice');
            
            console.log('Transaction ID:', transactionId, 'Invoice:', invoiceNo);
            
            if (!confirm('إرسال الفاتورة ' + invoiceNo + ' إلى نظام الفوترة الأردنية؟')) {
                return;
            }
            
            this.performAction($btn, transactionId, 'send-invoice');
        },
        
        /**
         * إعادة إرسال الفاتورة العادية
         */
        resendInvoice: function($btn) {
            console.log('resendInvoice called');
            
            var transactionId = $btn.data('id');
            var invoiceNo = $btn.data('invoice');
            
            if (!confirm('إعادة إرسال الفاتورة ' + invoiceNo + '؟')) {
                return;
            }
            
            this.performAction($btn, transactionId, 'send-invoice');
        },
        
        /**
         * إرسال فاتورة المرتجعات
         */
        sendCreditInvoice: function($btn) {
            console.log('sendCreditInvoice called');
            
            var returnTransactionId = $btn.data('return-id');
            var invoiceNo = $btn.data('invoice');
            var returnReason = $btn.data('reason') || 'إرجاع بضاعة';
            
            if (!confirm('إرسال فاتورة المرتجعات ' + invoiceNo + '؟')) {
                return;
            }
            
            this.performCreditAction($btn, returnTransactionId, returnReason);
        },
        
        /**
         * تنفيذ إرسال الفاتورة العادية
         */
        performAction: function($btn, transactionId, actionType) {
            var self = this;
            var originalHtml = $btn.html();
            
            console.log('performAction called for:', actionType, 'ID:', transactionId);
            
            // تغيير حالة الزر
            $btn.html('<i class="fas fa-spinner fa-spin"></i>');
            $btn.prop('disabled', true);
            
            // الحصول على CSRF token
            var csrfToken = $('meta[name="csrf-token"]').attr('content');
            console.log('CSRF Token:', csrfToken ? 'Found' : 'Not found');
            
            $.ajax({
                url: '/fatora/send-invoice',
                method: 'POST',
                data: {
                    _token: csrfToken,
                    transaction_id: transactionId
                },
                success: function(response) {
                    console.log('AJAX Success:', response);
                    
                    if (response.success) {
                        toastr.success(response.message || 'تم الإرسال بنجاح');
                        
                        // إعادة تحميل الجدول بعد 2 ثانية
                        setTimeout(function() {
                            if (typeof window.sell_table !== 'undefined') {
                                console.log('Reloading sell_table...');
                                window.sell_table.ajax.reload();
                            } else if (typeof sell_table !== 'undefined') {
                                sell_table.ajax.reload();
                            }
                        }, 2);
                    } else {
                        toastr.error(response.message || 'فشل الإرسال');
                        $btn.html(originalHtml);
                        $btn.prop('disabled', false);
                    }
                },
                error: function(xhr, status, error) {
                    console.error('AJAX Error:', status, error, xhr.responseText);
                    toastr.error('حدث خطأ أثناء الإرسال: ' + error);
                    $btn.html(originalHtml);
                    $btn.prop('disabled', false);
                }
            });
        },
        
        /**
         * تنفيذ إرسال فاتورة المرتجعات
         */
        performCreditAction: function($btn, returnTransactionId, returnReason) {
            var self = this;
            var originalHtml = $btn.html();
            
            console.log('performCreditAction called');
            
            // تغيير حالة الزر
            $btn.html('<i class="fas fa-spinner fa-spin"></i>');
            $btn.prop('disabled', true);
            
            // الحصول على CSRF token
            var csrfToken = $('meta[name="csrf-token"]').attr('content');
            
            $.ajax({
                url: '/fatora/send-credit-invoice',
                method: 'POST',
                data: {
                    _token: csrfToken,
                    return_transaction_id: returnTransactionId,
                    return_reason: returnReason
                },
                success: function(response) {
                    console.log('Credit AJAX Success:', response);
                    
                    if (response.success) {
                        toastr.success(response.message || 'تم إرسال فاتورة المرتجعات بنجاح');
                        
                        setTimeout(function() {
                            if (typeof window.sell_table !== 'undefined') {
                                window.sell_table.ajax.reload();
                            } else if (typeof sell_table !== 'undefined') {
                                sell_table.ajax.reload();
                            }
                        }, 20);
                    } else {
                        toastr.error(response.message || 'فشل إرسال فاتورة المرتجعات');
                        $btn.html(originalHtml);
                        $btn.prop('disabled', false);
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Credit AJAX Error:', status, error);
                    toastr.error('حدث خطأ أثناء إرسال فاتورة المرتجعات');
                    $btn.html(originalHtml);
                    $btn.prop('disabled', false);
                }
            });
        },
        
        /**
         * عرض التفاصيل
         */
        viewDetails: function($btn) {
            var transactionId = $btn.data('id');
            console.log('Opening details for transaction:', transactionId);
            
            window.open('/fatora/invoice-details/' + transactionId, '_blank');
        }
    };
    
    // تهيئة عند تحميل الصفحة
    $(document).ready(function() {
        console.log('Document ready, initializing FatoraEgo...');
        FatoraEgo.init();
        
        // تحقق من وجود الأزرار والفلاتر
        setTimeout(function() {
            console.log('=== FATORA INITIALIZATION CHECK ===');
            console.log('Number of fatora-send-btn:', $('.fatora-send-btn').length);
            console.log('Number of fatora-resend-btn:', $('.fatora-resend-btn').length);
            console.log('Number of fatora-view-btn:', $('.fatora-view-btn').length);
            console.log('Fatora filter element exists:', $('#fatora_status_filter').length > 0);
            
            // تحديث يدوي إضافي للفلتر (كنسخة احتياطية)
            $('#fatora_status_filter').on('change', function() {
                console.log('Manual backup filter change detected:', $(this).val());
            });
        }, 1);
    });
    
    // جعل الكائن متاحاً بشكل عام
    window.FatoraEgo = FatoraEgo;
    
})(jQuery);