-- =====================================================
-- FINAL FIX - نظام الفوترة الأردني
-- نفّذ هذا الملف كاملاً في phpMyAdmin
-- =====================================================

-- 1. إضافة الأعمدة المفقودة لجدول settings_fatora
-- =====================================================
ALTER TABLE `settings_fatora`
ADD COLUMN IF NOT EXISTS `supplier_income_source` VARCHAR(50) NULL COMMENT 'تسلسل مصدر الدخل - Required',
ADD COLUMN IF NOT EXISTS `tin` VARCHAR(50) NULL COMMENT 'الرقم الضريبي - Tax Identification Number',
ADD COLUMN IF NOT EXISTS `city_code` VARCHAR(10) NULL COMMENT 'e.g., JO-AM for Amman',
ADD COLUMN IF NOT EXISTS `postal_code` VARCHAR(20) NULL,
ADD COLUMN IF NOT EXISTS `is_active` TINYINT(1) DEFAULT 1;

-- 2. إضافة الأعمدة المفقودة لجدول fatora_invoices
-- =====================================================
ALTER TABLE `fatora_invoices`
ADD COLUMN IF NOT EXISTS `invoice_type` VARCHAR(50) DEFAULT 'general_sales' COMMENT 'نوع الفاتورة',
ADD COLUMN IF NOT EXISTS `payment_method` VARCHAR(20) DEFAULT 'cash' COMMENT 'طريقة الدفع',
ADD COLUMN IF NOT EXISTS `system_invoice_number` VARCHAR(50) NULL COMMENT 'رقم الفاتورة من JoFotara (EINV_NUM)',
ADD COLUMN IF NOT EXISTS `system_invoice_uuid` VARCHAR(100) NULL COMMENT 'UUID من JoFotara (EINV_INV_UUID)',
ADD COLUMN IF NOT EXISTS `qr_code` TEXT NULL COMMENT 'QR Code (EINV_QR)',
ADD COLUMN IF NOT EXISTS `xml_content` LONGTEXT NULL COMMENT 'XML الموقع (EINV_SINGED_INVOICE)',
ADD COLUMN IF NOT EXISTS `response_data` JSON NULL COMMENT 'Response كامل من API',
ADD COLUMN IF NOT EXISTS `status` VARCHAR(50) DEFAULT 'pending' COMMENT 'pending, sent, rejected',
ADD COLUMN IF NOT EXISTS `error_message` TEXT NULL COMMENT 'رسالة الخطأ إن وجدت',
ADD COLUMN IF NOT EXISTS `sent_at` TIMESTAMP NULL COMMENT 'تاريخ الإرسال';

-- 3. تحديث بيانات الاتصال (غيّر business_id حسب نظامك)
-- =====================================================
UPDATE `settings_fatora` 
SET 
    `client_id` = '4312198e-eaa6-4d2e-9bb2-9a7b1431f9c1',
    `secret_key` = 'Gj5nS9wyYHRadaVffz5VKB4v4wlVWyPhcJvrTD4NHtM0YfHMwojMwFtc9m9hOHS3H2k22OnEP5UEnyeZsaKhyu96hFU+l1ugYmCM5vaBANRXx4gr81NsXVaix88eh6hKcm5PFhvrwfFx6nuOjoPkSSImO7l/N9PrGGxQXwN1OCycSZFBbofkhvgpxOu4ON6O+cA9D7yG4Di/diVq4Mbjt6Ep/19fSuO+RdPPEVdsrb1ytPLycvT9x96nyN4VZWlwlSn4EII5Z+nXLLG7YpUX8g==',
    `supplier_income_source` = '18745024',
    `is_active` = 1
WHERE `business_id` = 6;

-- 4. التحقق من البيانات
-- =====================================================
SELECT 
    'settings_fatora' as table_name,
    business_id,
    client_id,
    supplier_income_source,
    CHAR_LENGTH(supplier_income_source) as income_source_length,
    tin,
    registration_name,
    is_active
FROM settings_fatora
WHERE business_id = 6;

-- 5. عرض هيكل الجداول للتأكد
-- =====================================================
SHOW COLUMNS FROM `settings_fatora`;
SHOW COLUMNS FROM `fatora_invoices`;

-- =====================================================
-- ✅ بعد التنفيذ:
-- =====================================================
-- 1. تأكد أن supplier_income_source = '18745024' (8 أرقام)
-- 2. أضف TIN و Registration Name من الواجهة: /fawjo-settings
-- 3. جرّب إرسال فاتورة من صفحة المبيعات
-- 4. يجب أن تظهر رسالة نجاح وBadge أخضر "تم الإرسال"
-- =====================================================



