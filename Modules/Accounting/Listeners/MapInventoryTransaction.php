<?php

namespace Modules\Accounting\Listeners;

use Modules\Accounting\Utils\AccountingUtil;

class MapInventoryTransaction
{
    protected $accountingUtil;

    public function __construct(AccountingUtil $accountingUtil)
    {
        $this->accountingUtil = $accountingUtil;
    }

    public function handle($event)
    {
        $transaction = $event->transaction;
        
  
    if (is_null($transaction)) {
        return;
    }
    $allowed_types = ['purchase', 'opening_stock', 'sell_return']; 
    
    if (!in_array($transaction->type, $allowed_types)) {
        return; 
    }
        $business_id = $transaction->business_id;

        // جلب إعدادات الربط من الفرع
        $location = \App\BusinessLocation::find($transaction->location_id);
        if (!$location || empty($location->accounting_default_map)) {
            return;
        }

        $default_map = json_decode($location->accounting_default_map, true);

        $deposit_to = null;
        $payment_account = null;

        // التحقق من الأنواع بناءً على ما ذكرت
        if ($transaction->type == 'opening_stock') {
            $deposit_to = $default_map['opening_stock']['deposit_to'] ?? null;
            $payment_account = $default_map['opening_stock']['payment_account'] ?? null;
        } 
        // هنا التعديل بناءً على ملاحظتك (add_quantity)
        elseif ($transaction->type == 'add_quantity') {
            $deposit_to = $default_map['add_quantity']['deposit_to'] ?? null;
            $payment_account = $default_map['add_quantity']['payment_account'] ?? null;
        }

        // إذا وجدت الحسابات، يتم إنشاء القيد فوراً
        if ($deposit_to && $payment_account) {
            $this->accountingUtil->saveMap(
                $transaction->type, 
                $transaction->id, 
                auth()->user()->id ?? $transaction->created_by, 
                $business_id, 
                $deposit_to, 
                $payment_account,
                "Auto-mapped on creation"
            );
        }
    }
}