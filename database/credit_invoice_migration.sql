-- =====================================================
-- SQL للمرتجعات - Credit Invoice Fields
-- نفذ هذا في phpMyAdmin
-- =====================================================

-- إضافة الأعمدة الخاصة بفواتير المرتجعات
ALTER TABLE `fatora_invoices`
ADD COLUMN IF NOT EXISTS `is_credit_invoice` TINYINT(1) DEFAULT 0 COMMENT 'هل هي فاتورة مرتجعات؟',
ADD COLUMN IF NOT EXISTS `original_transaction_id` INT UNSIGNED NULL COMMENT 'معرف الفاتورة الأصلية',
ADD COLUMN IF NOT EXISTS `original_invoice_uuid` VARCHAR(100) NULL COMMENT 'UUID الفاتورة الأصلية من JoFotara',
ADD COLUMN IF NOT EXISTS `original_invoice_amount` DECIMAL(22,4) NULL COMMENT 'مبلغ الفاتورة الأصلية',
ADD COLUMN IF NOT EXISTS `return_reason` TEXT NULL COMMENT 'سبب المرتجعات';

-- التحقق من الأعمدة
SHOW COLUMNS FROM `fatora_invoices` WHERE Field LIKE '%original%' OR Field LIKE '%credit%' OR Field LIKE 'return_reason';

-- مثال: عرض فواتير المرتجعات
SELECT 
    fi.id,
    fi.transaction_id as 'رقم المرتجعات',
    fi.system_invoice_number as 'رقم النظام',
    fi.original_transaction_id as 'رقم الفاتورة الأصلية',
    fi.original_invoice_amount as 'مبلغ الأصلية',
    fi.return_reason as 'سبب الإرجاع',
    fi.status as 'الحالة',
    fi.sent_at as 'تاريخ الإرسال'
FROM fatora_invoices fi
WHERE fi.is_credit_invoice = 1
ORDER BY fi.sent_at DESC;

