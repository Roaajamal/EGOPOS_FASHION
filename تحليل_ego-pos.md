# تحليل برنامج EGO-POS

## نظرة عامة

**EGO-POS** هو نظام نقطة بيع (Point of Sale) مبني على إطار **Laravel 9**، ومشتق من **Ultimate POS** (الإصدار 6.9). يدعم إدارة المبيعات، المشتريات، المخزون، المطاعم، الفواتير الإلكترونية (فترة)، التقارير، والمزيد.

---

## التقنيات المستخدمة

| المكون | التقنية |
|--------|----------|
| الإطار | Laravel 9.x |
| PHP | ^8.0 |
| قاعدة البيانات | MySQL (افتراضي) |
| الواجهة | Blade + jQuery + AdminLTE + DataTables |
| المصادقة | Laravel UI + Passport (API) |
| الصلاحيات | Spatie Laravel Permission |

---

## هيكل المشروع الرئيسي

```
ego-pos/
├── app/
│   ├── Http/Controllers/    # المتحكمات (POS, مبيعات, مشتريات, تقارير، فترة، إلخ)
│   ├── Models/             # النماذج
│   ├── Utils/              # أدوات (مخزون، فواتير، ضريبة، إلخ)
│   ├── Services/           # خدمات (فترة، MyFatoorah، إلخ)
│   └── ...
├── config/                 # إعدادات التطبيق وقاعدة البيانات
├── database/migrations/    # هجرات قاعدة البيانات (313+ ملف)
├── resources/views/        # واجهات Blade (حسابات، مبيعات، مطعم، تقارير، إلخ)
├── routes/
│   ├── web.php             # روابط الويب الرئيسية
│   ├── api.php             # روابط API
│   └── install_r.php       # روابط التثبيت
├── public/                 # نقطة الدخول (index.php) والأصول الثابتة
└── Modules/Woocommerce/   # موديول ربط مع ووكومرس
```

---

## الوظائف الرئيسية

### 1. نقطة البيع (POS)
- **SellPosController** – شاشة البيع والدفع
- صناديق نقاط البيع (Cash Register)
- طابعات وباركود (Barcode، QZ)
- دفع مباشر (MPS/ECR) وكشف فواتير (EgoInvoice)

### 2. المبيعات والمشتريات
- مبيعات، مرتجعات مبيعات، أوامر مبيعات
- مشتريات، مرتجعات مشتريات، أوامر شراء، طلبات شراء
- فواتير، مخططات فواتير، تخطيطات طباعة

### 3. المخزون
- منتجات، وحدات، علامات تجارية، تصنيفات
- تعديل مخزون، نقل مخزون، رصيد افتتاحي
- إدخال كميات (Quantity Entry)، استيراد منتجات

### 4. الجهات (Contacts)
- عملاء وموردين ومجموعات عملاء
- استيراد جهات اتصال

### 5. المحاسبة والتقارير
- حسابات، أنواع حسابات، تقارير محاسبية (ميزان، trial balance، إلخ)
- تقارير مبيعات، مشتريات، مخزون، ضريبة، مصروفات، ربح/خسارة
- تقارير Ego (كشف فواتير، تصدير PDF/Excel)

### 6. الفواتير الإلكترونية (فترة)
- **FatoraController** – إرسال واستيراد فواتير فترة
- **FAWJOFatoraController** – تكامل مع خدمة الفوترة
- جداول: `settings_fatora`, `fatora_invoices`

### 7. المطعم
- طاولات، طلبات مطبخ، حجوزات، مجموعات معدّلات (Modifiers)

### 8. إعدادات الأعمال
- أعمال متعددة، فروع/مواقع، مستخدمون، أدوار وصلاحيات
- عملات، ضرائب، خصومات، عروض منتجات

### 9. مدفوعات إضافية
- MyFatoorah، PayPal، Stripe، Razorpay، Paystack، PesaPal، إلخ

---

## الإعدادات الخاصة بك

### البورت 8521
البورت **8521** لا يُعرّف داخل كود Laravel؛ يُحدد من خلال:

1. **Apache (XAMPP)**  
   في ملف `httpd-vhosts.conf` أو إعداد Virtual Host:
   ```apache
   Listen 8521
   <VirtualHost *:8521>
       DocumentRoot "C:/xampp/htdocs/ego-pos/public"
       ServerName localhost
       <Directory "C:/xampp/htdocs/ego-pos/public">
           AllowOverride All
           Require all granted
       </Directory>
   </VirtualHost>
   ```

2. **أو تشغيل سيرفر Laravel المدمج:**
   ```bash
   php artisan serve --port=8521
   ```
   في هذه الحالة عنوان التطبيق: `http://127.0.0.1:8521`

يجب أن تتطابق قيمة **APP_URL** في ملف `.env` مع الرابط الذي تفتح به التطبيق، مثلاً:
```env
APP_URL=http://localhost:8521
```
أو
```env
APP_URL=http://127.0.0.1:8521
```

### قاعدة البيانات testv02
قاعدة البيانات تُعرّف في ملف **`.env`** في جذر المشروع:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=testv02
DB_USERNAME=root
DB_PASSWORD=
```

- **DB_DATABASE=testv02** ← اسم قاعدة البيانات عندك.
- **DB_PORT=3306** ← منفذ MySQL (ليس منفذ الويب 8521).

تأكد أن قاعدة **testv02** موجودة في MySQL وأن المستخدم لديه صلاحيات عليها، ثم نفّذ الهجرات إذا لم تُنفَّذ مسبقاً:

```bash
php artisan migrate
```

---

## ملف `.env` المطلوب

إذا لم يكن لديك ملف `.env`، انسخه من `.env.example` (إن وُجد) أو أنشئ ملفاً باسم `.env` في الجذر مع محتوى مشابه للتالي، وعدّل القيم حسب بيئتك:

```env
APP_NAME=ultimatePOS
APP_ENV=local
APP_KEY=base64:XXXX  # يُنشأ بـ: php artisan key:generate
APP_DEBUG=true
APP_URL=http://localhost:8521

LOG_CHANNEL=stack
LOG_LEVEL=debug

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=testv02
DB_USERNAME=root
DB_PASSWORD=

# إعدادات التثبيت/الترخيص (حسب واجهة التثبيت)
ENVATO_PURCHASE_CODE=
ENVATO_USERNAME=
ENVATO_EMAIL=
```

بعد أي تعديل على `.env`:
```bash
php artisan config:clear
php artisan cache:clear
```

---

## التثبيت من الصفر

1. إنشاء قاعدة بيانات **testv02** في MySQL.
2. وضع ملف `.env` (كما أعلاه) مع **DB_DATABASE=testv02** و **APP_URL** مطابق للبورت 8521.
3. تثبيت الحزم: `composer install`
4. توليد المفتاح: `php artisan key:generate`
5. تشغيل الهجرات: `php artisan migrate`
6. (اختياري) بيانات تجريبية: `php artisan db:seed`
7. تشغيل السيرفر على البورت 8521 (Artisan أو Apache كما سبق).

إذا لم يكن `.env` موجوداً، عادةً يفتح التطبيق واجهة التثبيت (`/install`) لإدخال بيانات التطبيق وقاعدة البيانات وكتابتها في `.env`.

---

## ملخص سريع

| العنصر | القيمة عندك |
|--------|-------------|
| منفذ الويب (البورت) | 8521 |
| رابط التطبيق | `http://localhost:8521` أو `http://127.0.0.1:8521` |
| اسم قاعدة البيانات | testv02 |
| منفذ MySQL | 3306 (افتراضي) |
| نقطة الدخول | `public/` (أو `public/index.php`) |

بهذا يكون تحليل البرنامج وربطه ببيئتك (بورت 8521 وقاعدة testv02) مكتملاً من ناحية الهيكل والإعدادات.
