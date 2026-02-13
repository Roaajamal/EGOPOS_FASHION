# تحليل صفحة نقل المخزون (Stock Transfers)

## 1. الراوتات (Routes)

**الملف:** `routes/web.php`  
**المجموعة:** داخل الـ middleware التالية:
- `setData`
- `auth`
- `SetSessionData`
- `language`
- `timezone`
- `AdminSidebarMenu`
- `CheckUserLogin`

| الرابط | الميثود | الكونترولر | الشرح |
|--------|---------|------------|--------|
| `/stock-transfers` | GET | `index` | قائمة عمليات النقل |
| `/stock-transfers/create` | GET | `create` | نموذج إضافة نقل جديد |
| `/stock-transfers` | POST | `store` | حفظ النقل |
| `/stock-transfers/{id}` | GET | `show` | عرض تفاصيل نقل |
| `/stock-transfers/{id}/edit` | GET | `edit` | تعديل نقل |
| `/stock-transfers/{id}` | PUT | `update` | تحديث النقل |
| `/stock-transfers/{id}` | DELETE | `destroy` | حذف النقل |
| `/stock-transfers/print/{id}` | GET | `printInvoice` | طباعة |
| `/stock-transfers/update-status/{id}` | POST | `updateStatus` | تحديث الحالة |
| `/stock-transfers/import` | POST | `import` | استيراد من إكسل |

---

## 2. الكونترولر (StockTransferController)

**الملف:** `app/Http/Controllers/StockTransferController.php`

### ما الذي يتحقق منه كل method؟

| Method | التحقق الأول | تحققات إضافية |
|--------|-------------|-----------------|
| **index()** | `stock_transfer.view` **أو** `stock_transfer.create` **أو** `stock_transfer.view_own` | لا يوجد. **إذا لا يملك أي واحدة → 403 Unauthorized** |
| **create()** | `stock_transfer.create` | `moduleUtil->isSubscribed($business_id)` ← إذا غير مشترك يرجّع redirect (انتهى الاشتراك) |
| **store()** | `stock_transfer.create` | نفس الاشتراك (isSubscribed) |
| **show()** | `stock_transfer.view` | — |
| **edit()** | `stock_transfer.update` | — |
| **update()** | `stock_transfer.update` | — |
| **destroy()** | `stock_transfer.delete` | — |
| **updateStatus()** | يتحقق من صلاحية | — |
| **import()** | لا يوجد تحقق صلاحيات صريح في الكود المعروض | — |

**ملاحظة:** الكونترولر **لا يتحقق أبداً** من تفعيل موديول `stock_transfers` (enabled_modules). التحقق الوحيد للوصول هو **الصلاحيات** و (في create/store) **الاشتراك**.

---

## 3. القائمة الجانبية (AdminSidebarMenu)

**الملف:** `app/Http/Middleware/AdminSidebarMenu.php` (حوالي السطر 347)

يظهر عنصر قائمة **"نقل المخزون"** فقط عندما:

1. **الموديول مفعّل:**  
   `in_array('stock_transfers', $enabled_modules)`  
   وقيمة `$enabled_modules` من:  
   `session('business.enabled_modules')`  
   وهي بدورها من جدول **`business`** عمود **`enabled_modules`** (JSON يُخزَّن كـ array في الموديل).

2. **والصلاحية موجودة:**  
   `stock_transfer.view` أو `stock_transfer.create` أو `stock_transfer.view_own`.

- إذا **الموديول غير مفعّل** → الرابط **لا يظهر** في القائمة فقط، لكن لو فتحت الرابط يدوياً (`/stock-transfers`) والصلاحيات موجودة، الصفحة **ستفتح** لأن الكونترولر لا يفحص الموديول.
- إذا **الصلاحيات ناقصة** → الكونترولر يعطي **403** عند فتح الصفحة.

---

## 4. الجلسة (Session)

**الملف:** `app/Http/Middleware/SetSessionData.php`

- يضع في الجلسة: `user`, `business` (كائن النشاط كامل), `currency`, `financial_year`.
- `session('business.enabled_modules')` = قيمة من جدول **business** عمود **enabled_modules** (الموديل يلقيها `array`).

---

## 5. الواجهات (Views)

**المجلد:** `resources/views/stock_transfer/`

| الملف | الاستخدام |
|-------|-----------|
| `index.blade.php` | قائمة النقل + جدول DataTables |
| `create.blade.php` | نموذج إضافة نقل |
| `edit.blade.php` | نموذج تعديل نقل |
| `show.blade.php` | عرض تفاصيل نقل |
| `print.blade.php` | طباعة |
| `partials/details.blade.php` | تفاصيل (مودال/جزء) |
| `partials/product_table_row.blade.php` | سطر منتج في الجدول |
| `partials/update_status_modal.blade.php` | مودال تحديث الحالة |
| `partials/export_transfer_products_modal.blade.php` | مودال تصدير/استيراد |

---

## 6. الجافاسكربت

**الملف:** `public/js/stock_transfer.js`

- أوتوكومبليت بحث منتجات (مصدر: `/products/list` مع `location_id`).
- إدارة صفوف المنتجات، تغيير الفرع، استيراد إكسل، تحديث الحالة، حذف، طباعة، إلخ.

---

## 7. سبب ظهور "غير مصرح به" (403)

الكونترولر يعطي **403** فقط عند فشل التحقق من الصلاحيات:

- في **index**: يجب أن يملك المستخدم **واحدة على الأقل** من:
  - `stock_transfer.view`
  - `stock_transfer.create`
  - `stock_transfer.view_own`

إذا **لا يملك أي واحدة** → `abort(403, 'Unauthorized action.');` ← هذا مصدر رسالة "غير مصرح به".

---

## 8. ماذا لو لم تكن المشكلة صلاحيات؟

1. **الرابط لا يظهر في القائمة:**  
   تحقق من أن **النشاط** يملك في جدول **`business`** عمود **`enabled_modules`** القيمة تحتوي على **`stock_transfers`** (مثال: `["purchases","add_sale","pos_sale","stock_transfers",...]`). إذا غير موجود، أضف `stock_transfers` ثم أعد تسجيل الدخول أو حدّث الجلسة.

2. **رسالة انتهاء اشتراك (ليست 403):**  
   في **create** أو **store** يتحقق من `moduleUtil->isSubscribed($business_id)`. إذا أرجع "انتهى الاشتراك" فالمشكلة من إعداد الاشتراك وليست من صلاحيات نقل المخزون.

3. **تأكيد الصلاحيات في قاعدة البيانات:**  
   - جدول **`permissions`**: وجود الصلاحيات `stock_transfer.view`, `stock_transfer.create`, `stock_transfer.view_own` (وإن شئت `update`, `delete`).  
   - جدول **`role_has_permissions`**: ربط هذه الصلاحيات **بالدور** الذي يستخدمه المستخدم.  
   - يمكن تنفيذ الـ migration الذي يضيف صلاحيات نقل المخزون ويمنحها للأدوار التي لديها `purchase.view` إذا لم يكن قد نُفّذ بعد.

---

## 9. ملخص سريع

| العنصر | المربوط بصفحة stock-transfers |
|--------|-------------------------------|
| **الراوت** | `Route::resource('stock-transfers', StockTransferController::class)` + روابط print, update-status, import |
| **الكونترولر** | صلاحيات `stock_transfer.*` فقط (لا فحص enabled_modules في الكونترولر) |
| **القائمة الجانبية** | `enabled_modules` + نفس صلاحيات stock_transfer |
| **الجلسة** | `business` (ومنها `enabled_modules`) من SetSessionData |
| **الـ 403** | ناتج فقط عن عدم وجود إحدى صلاحيات: view / create / view_own |

إذا استمر ظهور "غير مصرح به" بعد منح الصلاحيات، تأكد من أن المستخدم مسجّل دخول بدور مرتبط بهذه الصلاحيات في `role_has_permissions`، ثم جرّب تسجيل خروج ودخول لتحديث الكاش والجلسة.
