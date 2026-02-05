@php
    $is_mobile = isMobile();
@endphp

<div class="row">
    <div
        class="pos-form-actions tw-rounded-tr-xl tw-rounded-tl-xl tw-shadow-[rgba(17,_17,_26,_0.1)_0px_0px_16px] tw-bg-white tw-cursor-pointer">
        <div
            class="tw-flex tw-items-center tw-justify-between tw-flex-col sm:tw-flex-row md:tw-flex-row lg:tw-flex-row xl:tw-flex-row tw-gap-2 tw-px-4 tw-py-0 tw-overflow-x-auto tw-w-full">

            <div class="md:!tw-w-none !tw-flex md:!tw-hidden !tw-flex-row !tw-items-center !tw-gap-3">
                <div class="tw-pos-total tw-flex tw-items-center tw-gap-3">
                    <div class="tw-text-black tw-font-bold tw-text-sm tw-flex tw-items-center tw-flex-col tw-leading-1">
                        <div>@lang('sale.total_payable'):</div>
                        {{-- <div>Payable:</div> --}}
                    </div>
                    <input type="hidden" name="final_total" id="final_total_input" value="0.00">
                    <span id="total_payable" class="tw-text-green-900 tw-font-bold tw-text-sm number">0.00</span>
                </div>
            </div>

<!-- الزر الظاهر -->
@can('open_cash_drawer')
<button id="open_cash_drawer"
        style="padding:10px 20px; background:#4CAF50; color:#fff; border:0; border-radius:5px; cursor:pointer;">
    فتح درج الكاش
</button>
@endcan
<button id="pay_card_full" 
        class="tw-font-bold tw-text-white tw-bg-blue-600 tw-p-2 tw-rounded-md tw-cursor-pointer tw-flex tw-items-center tw-gap-1"
        title="@lang('business.pay_by_card_tooltip')"
      <!-- مخفي بداية -->
    <i class="fas fa-credit-card"></i> @lang('business.pay_by_visa')
</button>
<!-- صندوق الأدوات (مخفي) -->
<div id="cashDrawerBox" style="display:none; margin-top:15px;">
    <h3>🔌 فتح درج الكاش</h3>

    <label>اختر الطابعة:</label>
    <select id="printerSelect"></select>

    <button id="refreshPrinters">↻ تحديث</button>

    <div id="status" style="margin-top:10px; color:#444;">الحالة: —</div>
</div>

            <div class="!tw-w-full md:!tw-w-none !tw-flex md:!tw-hidden !tw-flex-row !tw-items-center !tw-gap-3">
                @if (!Gate::check('disable_pay_checkout') || auth()->user()->can('superadmin') || auth()->user()->can('admin'))
                    <button type="button"
                        class=" tw-flex tw-flex-row tw-items-center tw-justify-center tw-gap-1 tw-font-bold tw-text-white tw-cursor-pointer tw-text-xs md:tw-text-sm tw-bg-[#001F3E] tw-rounded-md tw-p-2 tw-w-[8.5rem] @if (!$is_mobile)  @endif no-print @if ($pos_settings['disable_pay_checkout'] != 0) hide @endif"
                        id="pos-finalize" title="@lang('lang_v1.tooltip_checkout_multi_pay')"><i class="fas fa-money-check-alt"
                            aria-hidden="true"></i> @lang('lang_v1.checkout_multi_pay') </button>
                @endif

                @if (!Gate::check('disable_express_checkout') || auth()->user()->can('superadmin') || auth()->user()->can('admin'))
                    <button type="button"
                        class="tw-font-bold tw-text-white tw-cursor-pointer tw-text-xs md:tw-text-sm tw-bg-[rgb(40,183,123)] tw-p-2 tw-rounded-md tw-w-[5.5rem] tw-flex tw-flex-row tw-items-center tw-justify-center tw-gap-1 @if (!$is_mobile)  @endif no-print @if ($pos_settings['disable_express_checkout'] != 0 || !array_key_exists('cash', $payment_types)) hide @endif pos-express-finalize @if ($is_mobile) col-xs-6 @endif"
                        data-pay_method="cash" title="@lang('tooltip.express_checkout')"> <i class="fas fa-money-bill-alt"
                            aria-hidden="true"></i> @lang('lang_v1.express_checkout_cash')</button>
                @endif
                @if (empty($edit))
                    <button type="button" class="tw-font-bold tw-text-white tw-cursor-pointer tw-text-xs md:tw-text-sm tw-bg-red-600 tw-p-2 tw-rounded-md tw-w-[5.5rem] tw-flex tw-flex-row tw-items-center tw-justify-center tw-gap-1" id="pos-cancel"> <i
                            class="fas fa-window-close"></i> @lang('sale.cancel')</button>
                @else
                    <button type="button" class="btn-danger tw-dw-btn hide tw-dw-btn-xs" id="pos-delete"
                        @if (!empty($only_payment)) disabled @endif> <i class="fas fa-trash-alt"></i>
                        @lang('messages.delete')</button>
                @endif
            </div>
            <div class="tw-flex tw-items-center tw-gap-4 tw-flex-row tw-overflow-x-auto">

                @if (!Gate::check('disable_draft') || auth()->user()->can('superadmin') || auth()->user()->can('admin'))
                    <button type="button"
                        class="tw-font-bold tw-text-gray-700 tw-text-xs md:tw-text-sm tw-flex tw-flex-col tw-items-center tw-justify-center tw-gap-1 @if ($pos_settings['disable_draft'] != 0) hide @endif"
                        id="pos-draft" @if (!empty($only_payment)) disabled @endif><i
                            class="fas fa-edit tw-text-[#009ce4]"></i> @lang('sale.draft')</button>
                @endif

                @if (!Gate::check('disable_quotation') || auth()->user()->can('superadmin') || auth()->user()->can('admin'))
                    <button type="button"
                        class="tw-font-bold tw-text-gray-700 tw-cursor-pointer tw-text-xs md:tw-text-sm tw-flex tw-flex-col tw-items-center tw-justify-center tw-gap-1 @if ($is_mobile) col-xs-6 @endif"
                        id="pos-quotation" @if (!empty($only_payment)) disabled @endif><i
                            class="fas fa-edit tw-text-[#E7A500]"></i> @lang('lang_v1.quotation')</button>
                @endif

                @if (!Gate::check('disable_suspend_sale') || auth()->user()->can('superadmin') || auth()->user()->can('admin'))
                    @if (empty($pos_settings['disable_suspend']))
                        <button type="button"
                            class="tw-font-bold tw-text-gray-700 tw-cursor-pointer tw-text-xs md:tw-text-sm tw-flex tw-flex-col tw-items-center tw-justify-center tw-gap-1  no-print pos-express-finalize"
                            data-pay_method="suspend" title="@lang('lang_v1.tooltip_suspend')"
                            @if (!empty($only_payment)) disabled @endif>
                            <i class="fas fa-pause tw-text-[#EF4B51]" aria-hidden="true"></i>
                            @lang('lang_v1.suspend')
                        </button>
                    @endif
                @endif

                @if (!Gate::check('disable_credit_sale') || auth()->user()->can('superadmin') || auth()->user()->can('admin'))
                    @if (empty($pos_settings['disable_credit_sale_button']))
                        <input type="hidden" name="is_credit_sale" value="0" id="is_credit_sale">
                        <button type="button"
                            class=" tw-font-bold tw-text-gray-700 tw-cursor-pointer tw-text-xs md:tw-text-sm tw-flex tw-flex-col tw-items-center tw-justify-center tw-gap-1 no-print pos-express-finalize @if ($is_mobile) col-xs-6 @endif"
                            data-pay_method="credit_sale" title="@lang('lang_v1.tooltip_credit_sale')"
                            @if (!empty($only_payment)) disabled @endif>
                            <i class="fas fa-check tw-text-[#5E5CA8]" aria-hidden="true"></i> @lang('lang_v1.credit_sale')
                        </button>
                    @endif
                @endif
                @if (!Gate::check('disable_card') || auth()->user()->can('superadmin') || auth()->user()->can('admin'))
                    <button type="button"
                        class="tw-font-bold tw-text-gray-700 tw-cursor-pointer tw-text-xs md:tw-text-sm tw-flex tw-flex-col tw-items-center tw-justify-center tw-gap-1  no-print @if (!empty($pos_settings['disable_suspend']))  @endif pos-express-finalize @if (!array_key_exists('card', $payment_types)) hide @endif @if ($is_mobile) col-xs-6 @endif"
                        data-pay_method="card" title="@lang('lang_v1.tooltip_express_checkout_card')">
                        <i class="fas fa-credit-card tw-text-[#D61B60]" aria-hidden="true"></i> @lang('lang_v1.express_checkout_card')
                    </button>
                @endif

                @if (!Gate::check('disable_pay_checkout') || auth()->user()->can('superadmin') || auth()->user()->can('admin'))
                    <button type="button"
                        class="tw-hidden md:tw-flex md:tw-flex-row md:tw-items-center md:tw-justify-center md:tw-gap-1 tw-font-bold tw-text-white tw-cursor-pointer tw-text-xs md:tw-text-sm tw-bg-[#001F3E] tw-rounded-md tw-p-2 tw-w-[8.5rem] @if (!$is_mobile)  @endif no-print @if ($pos_settings['disable_pay_checkout'] != 0) hide @endif"
                        id="pos-finalize" title="@lang('lang_v1.tooltip_checkout_multi_pay')"><i class="fas fa-money-check-alt"
                            aria-hidden="true"></i> @lang('lang_v1.checkout_multi_pay') </button>
                @endif

                @if (!Gate::check('disable_express_checkout') || auth()->user()->can('superadmin') || auth()->user()->can('admin'))
                    <button type="button"
                        class="tw-font-bold tw-text-white tw-cursor-pointer tw-text-xs md:tw-text-sm tw-bg-[rgb(40,183,123)] tw-p-2 tw-rounded-md tw-w-[8.5rem] tw-hidden md:tw-flex lg:tw-flex lg:tw-flex-row lg:tw-items-center lg:tw-justify-center lg:tw-gap-1 @if (!$is_mobile)  @endif no-print @if ($pos_settings['disable_express_checkout'] != 0 || !array_key_exists('cash', $payment_types)) hide @endif pos-express-finalize"
                        data-pay_method="cash" title="@lang('tooltip.express_checkout')"> <i class="fas fa-money-bill-alt"
                            aria-hidden="true"></i> @lang('lang_v1.express_checkout_cash')</button>
                @endif


                @if (empty($edit))
                    <button type="button"
                        class="tw-font-bold tw-text-white tw-cursor-pointer tw-text-xs md:tw-text-sm tw-bg-red-600 tw-p-2 tw-rounded-md tw-w-[8.5rem] tw-hidden md:tw-flex lg:tw-flex lg:tw-flex-row lg:tw-items-center lg:tw-justify-center lg:tw-gap-1"
                        id="pos-cancel"> <i class="fas fa-window-close"></i> @lang('sale.cancel')</button>
                @else
                    <button type="button"
                        class="tw-font-bold tw-text-white tw-cursor-pointer tw-text-xs md:tw-text-sm tw-bg-red-600 tw-p-2 tw-rounded-md tw-w-[8.5rem] tw-hidden md:tw-flex lg:tw-flex lg:tw-flex-row lg:tw-items-center lg:tw-justify-center lg:tw-gap-1 hide"
                        id="pos-delete" @if (!empty($only_payment)) disabled @endif> <i
                            class="fas fa-trash-alt"></i> @lang('messages.delete')</button>
                @endif
                
               <!-- gift fatora checkbox  -->
            <div class="col-md-2 col-sm-3 col-xs-6 p-0">
    <div style="padding: 2px;">
        <style>
            .gift-checkbox-container {
                cursor: pointer;
                background: #fdfdfd;
                color: #333;
                padding: 4px 8px; /* تقليل الحواف الداخلية */
                border-radius: 5px; /* حواف أنعم */
                width: 75%;
                text-align: center;
                display: flex; /* لترتيب العناصر بجانب بعضها */
                align-items: center;
                justify-content: center;
                border: 1px solid #5daac2; /* تقليل سمك الإطار */
                transition: all 0.2s ease;
                user-select: none;
                height: 30px; /* متوافق مع ارتفاع أزرار النظام الافتراضية */
            }

            /* حالة التفعيل */
            .gift-checkbox-container:has(#is_gift_receipt:checked) {
                background: #84c7db !important;
                color: white !important;
            }

            #is_gift_receipt {
                margin: 0 0 0 5px !important; /* مسافة بسيطة عن النص */
                cursor: pointer;
                width: 10px;
                height: 10px;
            }

            .gift-label-text {
                font-size: 11px; /* تصغير الخط */
                font-weight: bold;
                white-space: nowrap; /* منع النص من النزول لسطر جديد */
            }
        </style>

        <label class="gift-checkbox-container">
            <input type="checkbox" id="is_gift_receipt" value="1"> 
            <span class="gift-label-text">🎁 @lang('lang_v1.gift_invoice') </span>
        </label>
    </div>
</div>
                     <!-- gift fatora   -->

                @if (!$is_mobile)
                    {{-- <div class="bg-navy pos-total text-white ">
					<span class="text">@lang('sale.total_payable')</span>
					<input type="hidden" name="final_total" 
												id="final_total_input" value=0>
					<span id="total_payable" class="number">0</span>
					</div> --}}
                    <div class="pos-total md:tw-flex md:tw-items-center md:tw-gap-3 tw-hidden">
                        <div
                            class="tw-text-black tw-font-bold tw-text-base md:tw-text-2xl tw-flex tw-items-center tw-flex-col">
                            <div>@lang('sale.total')</div>
                            <div>@lang('lang_v1.payable'):</div>
                        </div>
                        <input type="hidden" name="final_total" id="final_total_input" value="0.00">
                        <span id="total_payable"
                            class="tw-text-green-900 tw-font-bold tw-text-base md:tw-text-2xl number">0.00</span>
                    </div>
                @endif
            </div>

            <div class="tw-w-full md:tw-w-fit tw-flex tw-flex-col tw-items-end tw-gap-3 tw-hidden md:tw-block">
                @if (!isset($pos_settings['hide_recent_trans']) || $pos_settings['hide_recent_trans'] == 0)
                    <button type="button"
                        class="tw-font-bold tw-bg-[#646EE4] hover:tw-bg-[#414aac] tw-rounded-full tw-text-white tw-w-full md:tw-w-fit tw-px-5 tw-h-11 tw-cursor-pointer tw-text-xs md:tw-text-sm"
                        data-toggle="modal" data-target="#recent_transactions_modal" id="recent-transactions"> <i
                            class="fas fa-clock"></i> @lang('lang_v1.recent_transactions')</button>
                @endif
            </div>
        </div>
    </div>
</div><script>






document.addEventListener('keydown', function (e) {




if (e.key === 'F1') {
    e.preventDefault();
    
    const DRAFT_LOCATION_ID = '32'; // فرع الدرفت
    let locationField = document.querySelector('#location_id, input[name="location_id"]');
    let draftButton = document.getElementById('pos-draft');
    
    if (locationField && draftButton) {
        // حفظ القيمة الأصلية للفرع
        const originalLocation = locationField.value;
        
        // تغيير الفرع إلى 20
        locationField.value = DRAFT_LOCATION_ID;
        
        // النقر على زر الدرفت
        draftButton.click();
        
        // إرجاع الفرع الأصلي فوراً
        setTimeout(() => {
            locationField.value = originalLocation;
        }, 50);
    }
}
  

    // CANCEL — F9
    if (e.key === 'F9') {
        e.preventDefault();
        document.getElementById('pos-cancel')?.click();
    }

    // DELETE — Del
    if (e.key === 'Delete') {
        e.preventDefault();
        document.getElementById('pos-delete')?.click();
    }

    // RECENT TRANSACTIONS — Esc
    if (e.key === 'Escape') {
        e.preventDefault();
        document.getElementById('recent-transactions')?.click();
    }

});
let ORIGINAL_LOCATION = null;

// حفظ الأصلي عند التحميل
document.addEventListener('DOMContentLoaded', function() {
    let locationField = document.querySelector('#location_id, input[name="location_id"]');
    if (locationField) {
        ORIGINAL_LOCATION = locationField.value;
    }
});

document.addEventListener('keydown', function (e) {
    if (e.key === 'F3') {
        e.preventDefault();
        
        const FIXED_LOCATION_ID = '29';
        let locationField = document.querySelector('#location_id, input[name="location_id"]');
        
        if (locationField) {
            // حفظ الأصلي إذا أول مرة
            if (ORIGINAL_LOCATION === null) {
                ORIGINAL_LOCATION = locationField.value;
            }
            
            // تغيير إلى 20
            locationField.value = FIXED_LOCATION_ID;
            
            // فوري بعد النقر، ارجع للأصلي
            setTimeout(() => {
                document.querySelector('[data-pay_method="cash"]')?.click();
                
                // إرجاع فوري للأصلي
                setTimeout(() => {
                    locationField.value = ORIGINAL_LOCATION;
                }, 50);
            }, 50);
        }
    }
    
    if (e.key === 'F8') {
        e.preventDefault();
        document.querySelector('[data-pay_method="cash"]')?.click();
    }
});

document.addEventListener('DOMContentLoaded', function() {

  const printerSelect = document.getElementById('printerSelect');
  const refreshBtn = document.getElementById('refreshPrinters');
  const openBtn = document.getElementById('open_cash_drawer');
  const statusDiv = document.getElementById('status');

  // -----------------------------
  // الصلاحية (Local QZ Certificate)
  // -----------------------------
  try {
    // QZ Tray يستخدم الشهادة والمفتاح المحلي المثبت على الجهاز
    qz.security.setCertificatePromise(resolve => {
      // اتركه فارغ، QZ Tray يقرأ الشهادة المحلية تلقائياً
      resolve("");
    });

    qz.security.setSignaturePromise((toSign) => (resolve, reject) => {
      // التوقيع يتم داخلياً بواسطة QZ Tray المحلي
      resolve(toSign);
    });

  } catch(e){
    // تجاهل أي خطأ، لن يظهر للمستخدم
  }

  // -----------------------------
  // الاتصال
  // -----------------------------
  function connectQZ() {
    return new Promise((resolve) => {
      if (qz.websocket.isActive()) return resolve();
      qz.websocket.connect().then(()=>resolve()).catch(()=>resolve()); // تجاهل الأخطاء
    });
  }

  // -----------------------------
  // تحميل الطابعات + Auto Select
  // -----------------------------
  function loadPrinters() {
    connectQZ()
      .then(() => Promise.all([
        qz.printers.getDefault().catch(() => null),
        qz.printers.find()
      ]))
      .then(([defaultPrinter, printers]) => {

        printerSelect.innerHTML = '';

        if (!printers || printers.length === 0) {
          printerSelect.innerHTML = `<option value="">لا توجد طابعات</option>`;
          return;
        }

        printers.forEach(p => {
          const opt = document.createElement('option');
          opt.value = p;
          opt.textContent = p;
          printerSelect.appendChild(opt);
        });

        // اختيار الطابعة الافتراضية إذا موجودة
        let selectedPrinter = defaultPrinter && printers.includes(defaultPrinter) ? defaultPrinter : printers[0];
        printerSelect.value = selectedPrinter;
      })
      .catch(()=>{}); // تجاهل أي خطأ
  }

  // -----------------------------
  // فتح درج الكاش
  // -----------------------------
  function openCashDrawer() {
    connectQZ().then(() => {
      const printerName = printerSelect.value;
      const config = qz.configs.create(printerName);
      const data = [{ type: 'raw', format: 'hex', data: '1B700019FA' }];
      return qz.print(config, data);
    });
  }

  // -----------------------------
  // الاختصار F10
  // -----------------------------
  document.addEventListener('keydown', function(e) {
    if (e.code === 'F10') {
      e.preventDefault();
      openCashDrawer();
    }
  });

  // -----------------------------
  // روابط الأزرار
  // -----------------------------
  refreshBtn.addEventListener('click', loadPrinters);
  openBtn.addEventListener('click', openCashDrawer);

  // بدء التحميل
  loadPrinters();

});
</script>
<script>
$(document).ready(function() {
    // دالة للتحقق من إعدادات MPS عبر API
    function checkMpsSettings() {
       const location_id = document.getElementById('location_id')?.value;
        
        if (!location_id) {
            $('#pay_card_full').hide();
            return;
        }
        
        // جلب إعدادات MPS من السيرفر
        $.ajax({
            url: '/ecr/settings/' + location_id,
            type: 'GET',
            success: function(response) {
                if (response.success && response.enabled) {
                    $('#pay_card_full').show();
                } else {
                    $('#pay_card_full').hide();
                }
            },
            error: function() {
                $('#pay_card_full').hide();
            }
        });
    }
    
    // تشغيل عند تحميل الصفحة
    checkMpsSettings();
    
    // عند تغيير الموقع
    $('#location_id').change(function() {
        checkMpsSettings();
    });
    
    // إذا كان هناك زر إعادة تحميل
    $(document).on('locationChanged', function() {
        checkMpsSettings();
    });
});
</script>
<script>
// فنكشن واحد فقط يأخذ البيانات من الجلسة ويرسل 5 للميبس
async function testSend5ToController() {
    try {
        // أخذ business_id و location_id من الجلسة مباشرة
        const business_id = @json(session('business.id', 1));
        const location_id = document.getElementById('location_id')?.value;
        const total_amount = __read_number($('#final_total_input'));
        
        // البيانات الأساسية للميبس
        const testData = {
            amount: total_amount,
            invoice: 'TEST-5-' + Date.now(),
            business_id,
            location_id
        };

        // إظهار رسالة تحميل فقط
        swal({
            title: 'جاري الاتصال بالميبس...',
            text: 'يرجى الانتظار',
            icon: 'info',
            buttons: false,
            closeOnClickOutside: false,
            closeOnEsc: false
        });

        // إرسال الطلب POST للميبس
        const response = await fetch('/direct-payment', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
            },
            body: JSON.stringify(testData)
        });

        // قراءة النتيجة
        let result;
        try {
            result = await response.json();
        } catch {
            const text = await response.text();
            result = { success: false, message: 'استجابة غير متوقعة من الميبس', raw: text };
        }

        // إغلاق رسالة التحميل
        swal.close();

        // ✅ التحقق من النتيجة ومعالجتها
        if (result.success === true) {
            // 1️⃣ الميبس وافق - نستدعي دالة إنهاء البيع كبطاقة
            // استدعاء دالة directCardPaymentAndSave()
            if (typeof directCardPaymentAndSave === 'function') {
                directCardPaymentAndSave();
            } else {
                // إذا الدالة غير موجودة، ننفذ العملية يدوياً
                completeSaleAsCardManually();
            }
            
            // رسالة نجاح
            toastr.success('✅ تمت الموافقة من الميبس وجاري إنهاء البيع');
            
        } else {
            // 2️⃣ الميبس رفض - نعرض رسالة خطأ
            swal({
                title: '❌ فشل الدفع',
                text: result.message || 'رفض الميبس عملية الدفع',
                icon: 'error',
                buttons: {
                    retry: {
                        text: 'إعادة المحاولة',
                        value: 'retry'
                    },
                    cancel: 'إلغاء'
                }
            }).then((value) => {
                if (value === 'retry') {
                    // إعادة المحاولة
                    testSend5ToController();
                }
            });
        }

        return result;

    } catch (error) {
        console.error('🔥 خطأ في الاتصال بالميبس:', error);
        
        // إغلاق رسالة التحميل
        swal.close();
        
        // إظهار رسالة خطأ الاتصال
        swal({
            title: '🔥 خطأ في الاتصال',
            text: `حدث خطأ في الاتصال بجهاز الميبس:
            
${error.message}

يرجى التحقق من:
1. أن جهاز الميبس متصل
2. أن الرابط /direct-payment صحيح
3. أن الإنترنت يعمل`,
            icon: 'error'
        });
        
        return { success: false, error: error.message };
    }
}

// دالة بديلة إذا لم تكن directCardPaymentAndSave موجودة
function completeSaleAsCardManually() {
    // 1. تغيير طريقة الدفع لبطاقة
    $('#payment_rows_div .payment_types_dropdown').first().val('card').change();
    
    // 2. تعبئة مبلغ الدفع
    var total_payable = __read_number($('input#final_total_input'));
    $('#payment_rows_div .payment-amount').first().val(total_payable).change();
    
    // 3. فتح نافذة تفاصيل البطاقة
    setTimeout(() => {
        $('#card_details_modal').modal('show');
    }, 300);
}

// إضافة الحدث لزر الفيزا
document.addEventListener('DOMContentLoaded', function() {
    const payCardBtn = document.getElementById('pay_card_full');

    if (payCardBtn) {
        payCardBtn.addEventListener('click', async function(e) {
            e.preventDefault();

       
        

            // تغيير حالة الزر أثناء المعالجة
            const originalText = this.innerHTML;
            this.innerHTML = '<i class="fas fa-spinner fa-spin"></i> جاري الاتصال بالميبس...';
            this.disabled = true;

            // استدعاء الفنكشن
            await testSend5ToController();

            // إعادة حالة الزر
            this.innerHTML = originalText;
            this.disabled = false;
        });
    }
});
</script>

@if (isset($transaction))
    @include('sale_pos.partials.edit_discount_modal', [
        'sales_discount' => $transaction->discount_amount,
        'discount_type' => $transaction->discount_type,
        'rp_redeemed' => $transaction->rp_redeemed,
        'rp_redeemed_amount' => $transaction->rp_redeemed_amount,
        'max_available' => !empty($redeem_details['points']) ? $redeem_details['points'] : 0,
    ])
@else
    @include('sale_pos.partials.edit_discount_modal', [
        'sales_discount' => $business_details->default_sales_discount,
        'discount_type' => 'percentage',
        'rp_redeemed' => 0,
        'rp_redeemed_amount' => 0,
        'max_available' => 0,
    ])
@endif

@if (isset($transaction))
    @include('sale_pos.partials.edit_order_tax_modal', ['selected_tax' => $transaction->tax_id])
@else
    @include('sale_pos.partials.edit_order_tax_modal', [
        'selected_tax' => $business_details->default_sales_tax,
    ])
@endif

@include('sale_pos.partials.edit_shipping_modal')
