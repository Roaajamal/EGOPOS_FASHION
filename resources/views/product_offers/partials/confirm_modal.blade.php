<!-- مودال التأكيد لحذف العرض -->
<div class="modal fade" id="confirm_modal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
                <h4 class="modal-title" id="modal_title">
                    <i class="fa fa-exclamation-triangle text-warning"></i>
                    @lang('messages.are_you_sure')
                </h4>
            </div>
            <div class="modal-body">
                <div class="alert alert-warning">
                    <i class="fa fa-info-circle"></i>
                    <span id="modal_body">@lang('lang_v1.delete_confirmation')</span>
                </div>
                <p class="text-muted small" id="modal_details"></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">
                    <i class="fa fa-times"></i> @lang('messages.cancel')
                </button>
                <button type="button" class="btn btn-danger" id="confirm_action">
                    <i class="fa fa-check"></i> @lang('messages.ok')
                </button>
            </div>
        </div>
    </div>
</div>

<script type="text/javascript">
    /**
     * دالة مودال التأكيد الخاصة بعروض المنتجات
     * @param {object} options - إعدادات المودال
     * @param {string} options.title - عنوان المودال
     * @param {string} options.body - نص المودال
     * @param {function} options.action - الدالة التي تنفذ عند التأكيد
     * @param {string} options.confirmText - نص زر التأكيد
     * @param {string} options.cancelText - نص زر الإلغاء
     * @param {string} options.details - تفاصيل إضافية
     */
    function confirmModal(options) {
        var defaults = {
            title: "@lang('messages.are_you_sure')",
            body: "@lang('lang_v1.delete_confirmation')",
            action: function() {},
            confirmText: "@lang('messages.ok')",
            cancelText: "@lang('messages.cancel')",
            details: ''
        };
        
        var settings = $.extend({}, defaults, options);
        
        // تحديث النصوص
        $('#modal_title').html('<i class="fa fa-exclamation-triangle text-warning"></i> ' + settings.title);
        $('#modal_body').text(settings.body);
        $('#confirm_action').html('<i class="fa fa-check"></i> ' + settings.confirmText);
        $('.btn-default').html('<i class="fa fa-times"></i> ' + settings.cancelText);
        
        // إضافة تفاصيل إذا وجدت
        if (settings.details) {
            $('#modal_details').html('<i class="fa fa-info-circle"></i> ' + settings.details).show();
        } else {
            $('#modal_details').hide();
        }
        
        // إظهار المودال
        $('#confirm_modal').modal('show');
        
        // إزالة المعالجات القديمة وإضافة الجديدة
        $('#confirm_action').off('click').on('click', function() {
            settings.action();
            $('#confirm_modal').modal('hide');
        });
        
        // تنظيف عند إغلاق المودال
        $('#confirm_modal').off('hidden.bs.modal').on('hidden.bs.modal', function() {
            $('#modal_details').empty().hide();
        });
    }
    
    /**
     * دالة تأكيد حذف عرض
     * @param {string} href - رابط الحذف
     * @param {string} productName - اسم المنتج (اختياري)
     */
    function confirmDeleteOffer(href, productName) {
        var options = {
            title: "@lang('messages.are_you_sure')",
            body: "@lang('lang_v1.delete_offer_confirmation')",
            confirmText: "@lang('messages.delete')",
            cancelText: "@lang('messages.cancel')",
            action: function() {
                // إرسال طلب الحذف
                $.ajax({
                    url: href,
                    method: 'DELETE',
                    success: function(response) {
                        if (response.success) {
                            toastr.success(response.msg);
                            // إعادة تحميل الجدول
                            if (typeof offers_table !== 'undefined') {
                                offers_table.ajax.reload();
                            }
                        } else {
                            toastr.error(response.msg);
                        }
                    },
                    error: function(xhr) {
                        toastr.error("@lang('messages.something_went_wrong')");
                    }
                });
            }
        };
        
        // إضافة اسم المنتج إذا وجد
        if (productName) {
            options.details = "@lang('lang_v1.product'): " + productName;
        }
        
        confirmModal(options);
    }
</script>