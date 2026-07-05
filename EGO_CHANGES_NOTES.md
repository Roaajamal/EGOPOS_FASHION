# 📘 ملاحظات تعديلات EGO-POS Fashion

**مكان الكود على الجهاز:** `C:\Users\NTC\Downloads\500fashion.sst-egopos.com\500fashion.sst-egopos.com`
**الفرع (Branch):** `feature/pos-new-features`
**المستودع:** https://github.com/Roaajamal/EGOPOS_FASHION
**تشغيل محلي:** `http://127.0.0.1:8000` (عبر `php artisan serve`)

> كل الإضافات مُعلَّمة داخل الكود بتعليق `🆕` وببادئة `ego_`/`ego-` دون تعديل ملفات JS الأصلية.

---

## 🗂️ ملفات Blade (العروض) وما عُدّل فيها

| الملف | ماذا عُدّل |
|------|-----------|
| `resources/views/sale_pos/create.blade.php` | شاشة نقطة البيع الرئيسية: صور الأصناف (contain داخل المربع)، أسهم تمرير أسطر البيع (عزل الجدول + تمرير الصندوق فقط)، تظليل حقل "المدفوع" بالأزرق عند التسديد، الدفع بالبطاقة مباشرةً بلا نافذة تفاصيل، ربط أزرار (درج الكاش/بحث مخزون/كشف عميل/مصاريف/مسودة) بصلاحياتها، زر الخصم مرتبط بإعداد "تعطيل الخصم" وإخفاء القديم، إرسال للفيزا بصلاحية `sell.send_to_visa`، بائع لكل سطر |
| `resources/views/sale_pos/create_classic.blade.php` | نسخة POS الكلاسيكية (محفوظة كبديل عبر إعداد `use_classic_pos`) |
| `resources/views/sale_pos/partials/pos_form.blade.php` | تعديلات جدول أسطر البيع |
| `resources/views/sale_pos/partials/pos_sidebar.blade.php` | القائمة الجانبية للـPOS |
| `resources/views/sale_pos/product_row.blade.php` | صف المنتج (بائع لكل سطر) |
| `resources/views/layouts/partials/header.blade.php` | ترتيب الرأس: الجرس بجانب "نقطة البيع" ثم الربح والتاريخ |
| `resources/views/layouts/partials/header-pos.blade.php` | صندوق الفرع، لون زر بحث الإرجاع (فيروزي) |
| `resources/views/layouts/partials/header-notifications.blade.php` | جرس الإشعارات: تصميم القائمة المنسدلة + توليد الإشعارات + **عرض آخر 24 ساعة فقط** |
| `resources/views/product_offers/index.blade.php` | صفحة العروض: استيراد Excel للعروض الخاصة والحزم، زر **فحص** بكل التبويبات، أزرار كبيرة بنصوص (فحص/تعديل/حذف)، تجميع الباركود البديل حسب المنتج مع تعديل/حذف داخل نافذة الفحص |
| `resources/views/import_sales/index.blade.php` | تبويب **"المبيعات المستوردة"** (وقت الاستيراد + عدد الفواتير + الإجمالي + فحص)، رابط القالب الجديد |
| `resources/views/import_sales/preview.blade.php` | تحسين مظهر المعاينة (بطاقات، رأس ثابت، عدّاد صفوف)، إلغاء شرط الإيميل/اسم العميل |
| `resources/views/role/create.blade.php` | صلاحية `ego.notification_bell` |
| `resources/views/role/edit.blade.php` | صلاحية `ego.notification_bell` |
| `resources/views/ego_activation/index.blade.php` | شاشة إدارة التفعيل + طلبات التجديد (أدمن) |
| `resources/views/ego_activation/expired.blade.php` | صفحة "التفعيل منتهٍ" + نموذج طلب تمديد |
| `resources/views/reports/ego_seller_commission.blade.php` | (قديم) عرض عمولة البائع — انعكست في تقرير مندوب المبيعات |
| `resources/views/business/partials/register_form.blade.php` | تفعيل تلقائي عند إنشاء بزنس (تاريخ + مدة) |
| `resources/views/business/partials/settings_pos.blade.php` | إعدادات POS الإضافية |
| `resources/views/business/partials/settings_sales.blade.php` | إعدادات المبيعات |
| `resources/views/auth/custom/new_login.blade.php` | بانر "انتهى الاشتراك" على شاشة الدخول |
| `resources/views/product/partials/quick_add_product.blade.php` | إضافة منتج سريعة |

---

## ⚙️ ملفات PHP (تحكّم / نماذج / وسطاء)

| الملف | ماذا عُدّل |
|------|-----------|
| `app/Http/Controllers/ImportSalesController.php` | حقل **طريقة الدفع (cash/card)** + إنشاء سجل دفع، **تصحيح الإجمالي = مجموع الأسطر**، قالب Excel ديناميكي، بيانات تبويب المبيعات المستوردة (عدد + إجمالي)، جعل العميل اختيارياً |
| `app/Http/Controllers/ProductOfferController.php` | استيراد Excel للعروض الخاصة والحزم + قوالب، دوال فحص (`getSpecialOfferItems`/`getBundleItems`/`getOfferItems`/`getAltItems`)، تجميع الباركود حسب المنتج + حذف الكل، أزرار إجراءات موحّدة |
| `app/Http/Controllers/HomeController.php` | تصفية الإشعارات لآخر 24 ساعة |
| `app/Http/Controllers/EgoActivationController.php` | إدارة التفعيل + طلب/اعتماد/رفض التجديد |
| `app/Http/Controllers/EgoSellerCommissionController.php` | (قديم) عمولة البائع |
| `app/EgoActivation.php` | **تصحيح `daysLeft`**: مقارنة بالتاريخ فقط وبتوقيت `Asia/Amman` (صالح طوال يوم الانتهاء) |
| `app/EgoRenewalRequest.php` | نموذج طلبات التجديد |
| `app/ProductSpecialOffer.php` / `app/ProductSpecialOfferItem.php` | نماذج العروض الخاصة |
| `app/Http/Middleware/EgoActivationCheck.php` | حظر عند الانتهاء + **استثناء مسارات طلب/اعتماد التجديد** |
| `app/Http/Middleware/AdminSidebarMenu.php` | عناصر قائمة (تفعيل النظام ضمن الإعدادات) |
| `app/Utils/EgoNotifier.php` | توليد إشعارات (انتهاء/مخزون منخفض/توصيل) |
| `app/Utils/Util.php` | `parseNotifications` — فرع `ego_generic` |
| `app/Utils/TransactionUtil.php` | حفظ `ego_seller_id` لكل سطر بيع |
| `app/Http/Controllers/{Business,Product,Report,SellPos}Controller.php` | تكاملات POS/العروض/التقارير |

---

## 🛣️ مسارات (routes/web.php) جديدة
- عروض المنتجات: استيراد/قالب/فحص للعروض الخاصة والحزم، فحص عروض الكمية والباركود البديل، حذف كل باركودات منتج.
- استيراد المبيعات: `import-sales/template` (قالب ديناميكي).
- التفعيل: `ego-activation` (فهرس/تعيين/طلب/اعتماد/رفض/منتهٍ).

## 🗃️ هجرات قاعدة البيانات (database/migrations)
- `create_product_special_offers` — العروض الخاصة وعناصرها.
- `add_ego_seller_id_to_transaction_sell_lines` — بائع لكل سطر.
- `create_ego_activations` — تفعيل النظام.
- `create_ego_renewal_requests` — طلبات التجديد.

---

## 🐞 إصلاحات هذه الجلسة (ملخص)
1. **استيراد المبيعات**: كان الإجمالي = قيمة السطر الأول → صار = **مجموع كل الأسطر** (صنفان بـ10 = 20). وأُنشئ سجل دفع فينعكس "المدفوع" وطريقة الدفع.
2. **التفعيل**: التوقيت كان `Europe/London` فيُخطئ حساب اليوم → صار بتوقيت `Asia/Amman` ومقارنة بالتاريخ. وأصبح المستخدم المنتهي يقدر يرسل طلب تمديد.
3. **الإشعارات**: تُعرض آخر 24 ساعة فقط.
4. **الباركود البديل**: يظهر بصف واحد لكل منتج (بدل التفرّق)، والفحص يعرض الباركودات مع تعديل/حذف.

---

## 🆕 أحدث التعديلات (استيراد المبيعات)
- **فحص المبيعات المستوردة**: يعرض الآن **جدولاً لكل فاتورة** فيه أصنافها (الصنف/الكمية/السعر/المجموع) مع **إجمالي الفاتورة وطريقة الدفع** في ترويسة كل فاتورة (مسار `import-sales/batch/{batch}/items` + دالة `getImportBatchItems`).
- **تصحيح الإجمالي**: إجمالي الفاتورة = **مجموع أسطرها** (صنفان بـ10 = 20).
- **طريقة الدفع + سجل الدفع**: عند الاستيراد يُنشأ سجل دفع بكامل الإجمالي فينعكس "المدفوع" وطريقة الدفع (cash/card).
- **قالب Excel ديناميكي** يشمل عمودَي طريقة الدفع والإجمالي، وزر التنزيل عاد لنص "تنزيل ملف القالب" فقط.
- **حذف عمود "إجمالي المبالغ"** من جدول تقرير المبيعات المستوردة (بقي: وقت الاستيراد + عدد الفواتير + فحص + استرجاع).

---

> ⚠️ **تنبيه أمني:** توكن GitHub الذي ظهر سابقاً يجب إلغاؤه من إعدادات حسابك.
> ⚠️ الفواتير المستوردة **قبل** هذه التعديلات مدفوعها صفر (لم يُنشأ لها سجل دفع) — استرجعها وأعد استيرادها.
