-- =====================================================
-- SQL Script to Fix Fatora Tables
-- نظام الفوترة الأردني - JoFotara Integration
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
ADD COLUMN IF NOT EXISTS `invoice_type` VARCHAR(50) DEFAULT 'general_sales',
ADD COLUMN IF NOT EXISTS `payment_method` VARCHAR(20) DEFAULT 'cash',
ADD COLUMN IF NOT EXISTS `system_invoice_number` VARCHAR(50) NULL COMMENT 'رقم الفاتورة من نظام JoFotara',
ADD COLUMN IF NOT EXISTS `system_invoice_uuid` VARCHAR(100) NULL COMMENT 'UUID الفاتورة من نظام JoFotara',
ADD COLUMN IF NOT EXISTS `qr_code` TEXT NULL,
ADD COLUMN IF NOT EXISTS `xml_content` LONGTEXT NULL,
ADD COLUMN IF NOT EXISTS `response_data` JSON NULL,
ADD COLUMN IF NOT EXISTS `status` VARCHAR(50) DEFAULT 'pending',
ADD COLUMN IF NOT EXISTS `error_message` TEXT NULL,
ADD COLUMN IF NOT EXISTS `sent_at` TIMESTAMP NULL;

-- 3. تحديث بيانات الاتصال (اختر business_id المناسب)
-- =====================================================

-- استبدل business_id = 6 برقم الـ business_id الخاص بك إذا كان مختلفاً

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
    id,
    business_id,
    client_id,
    SUBSTRING(secret_key, 1, 30) as secret_key_preview,
    supplier_income_source,
    tin,
    registration_name,
    is_active
FROM settings_fatora;

-- 5. التحقق من هيكل الجداول
-- =====================================================

SHOW COLUMNS FROM settings_fatora;
SHOW COLUMNS FROM fatora_invoices;

-- =====================================================
-- ملاحظات مهمة:
-- =====================================================
-- 1. تأكد من إضافة TIN (الرقم الضريبي) يدوياً من الواجهة
-- 2. تأكد من إضافة Registration Name (اسم الشركة) من الواجهة
-- 3. supplier_income_source يجب أن يكون بالضبط: 18745024
-- 4. جميع معرفات العملاء يجب أن تكون أرقام فقط
-- =====================================================

