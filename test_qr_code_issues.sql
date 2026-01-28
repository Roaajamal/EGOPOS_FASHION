-- ============================================
-- SQL للتحقق من مشاكل QR Code
-- ============================================

-- 1. تحقق من حجم QR codes في قاعدة البيانات
SELECT 
    id,
    transaction_id,
    system_invoice_number,
    LENGTH(qr_code) as qr_size,
    CASE 
        WHEN LENGTH(qr_code) < 1000 THEN 'صغير'
        WHEN LENGTH(qr_code) < 10000 THEN 'متوسط'
        WHEN LENGTH(qr_code) < 100000 THEN 'كبير'
        ELSE 'كبير جداً'
    END as size_category,
    status
FROM fatora_invoices
WHERE qr_code IS NOT NULL
ORDER BY LENGTH(qr_code) DESC
LIMIT 10;

-- 2. تحقق من وجود أحرف خاصة في QR code
SELECT 
    id,
    transaction_id,
    system_invoice_number,
    CASE 
        WHEN qr_code REGEXP '[^A-Za-z0-9+/=]' THEN 'يحتوي على أحرف خاصة'
        ELSE 'base64 نظيف'
    END as qr_validation,
    status
FROM fatora_invoices
WHERE qr_code IS NOT NULL
LIMIT 10;

-- 3. احصل على فاتورة للاختبار (أصغر QR)
SELECT 
    transaction_id,
    system_invoice_number,
    LENGTH(qr_code) as qr_size
FROM fatora_invoices
WHERE qr_code IS NOT NULL
ORDER BY LENGTH(qr_code) ASC
LIMIT 1;

-- 4. احصل على فاتورة للاختبار (أكبر QR)
SELECT 
    transaction_id,
    system_invoice_number,
    LENGTH(qr_code) as qr_size
FROM fatora_invoices
WHERE qr_code IS NOT NULL
ORDER BY LENGTH(qr_code) DESC
LIMIT 1;

-- 5. عدد الفواتير حسب حجم QR
SELECT 
    CASE 
        WHEN LENGTH(qr_code) < 1000 THEN '< 1KB'
        WHEN LENGTH(qr_code) < 10000 THEN '1-10KB'
        WHEN LENGTH(qr_code) < 100000 THEN '10-100KB'
        ELSE '> 100KB'
    END as size_range,
    COUNT(*) as count
FROM fatora_invoices
WHERE qr_code IS NOT NULL
GROUP BY size_range
ORDER BY MIN(LENGTH(qr_code));

-- 6. تحقق من وجود line breaks أو whitespace
SELECT 
    id,
    transaction_id,
    CASE 
        WHEN qr_code LIKE '%\n%' THEN 'يحتوي على line breaks'
        WHEN qr_code LIKE '% %' THEN 'يحتوي على spaces'
        ELSE 'نظيف'
    END as whitespace_check
FROM fatora_invoices
WHERE qr_code IS NOT NULL
LIMIT 10;

-- 7. مقارنة فواتير مع QR وبدون QR
SELECT 
    'مع QR' as type,
    COUNT(*) as count,
    AVG(LENGTH(qr_code)) as avg_qr_size
FROM fatora_invoices
WHERE qr_code IS NOT NULL
UNION ALL
SELECT 
    'بدون QR' as type,
    COUNT(*) as count,
    0 as avg_qr_size
FROM fatora_invoices
WHERE qr_code IS NULL;

-- ============================================
-- حلول مؤقتة (إذا لزم الأمر)
-- ============================================

-- حل مؤقت 1: حذف QR codes الكبيرة جداً (احتياطي)
-- UPDATE fatora_invoices 
-- SET qr_code = NULL 
-- WHERE LENGTH(qr_code) > 200000;

-- حل مؤقت 2: تنظيف whitespace من QR codes
-- UPDATE fatora_invoices 
-- SET qr_code = REPLACE(REPLACE(REPLACE(qr_code, '\n', ''), '\r', ''), ' ', '')
-- WHERE qr_code IS NOT NULL;

-- ============================================
-- اختبار فاتورة محددة
-- ============================================

-- استبدل TRANSACTION_ID برقم الفاتورة
-- SELECT 
--     t.id,
--     t.invoice_no,
--     fi.system_invoice_number,
--     fi.status,
--     LENGTH(fi.qr_code) as qr_size,
--     LEFT(fi.qr_code, 50) as qr_preview
-- FROM transactions t
-- LEFT JOIN fatora_invoices fi ON fi.transaction_id = t.id
-- WHERE t.id = TRANSACTION_ID;




