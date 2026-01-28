<?php

namespace App\Http\Controllers;

use App\Services\FatoraService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
class FatoraController extends Controller
{
    /**
     * Send invoice to Jordan E-Invoicing System (JoFotara)
     */
      
public function sendInvoice(Request $request)
{ 
    try {
        $transactionId = $request->input('transaction_id');
        $businessId = Auth::user()->business_id;

        if (!$transactionId) {
            return response()->json([
                'success' => false,
                'message' => 'Transaction ID is required'
            ], 400);
        }
        
        // ========== أولاً: نجيب معلومات الـ transaction ==========
        $transaction = DB::table('transactions')
            ->where('id', $transactionId)
            ->where('business_id', $businessId)
            ->first();
        
        if (!$transaction) {
            return response()->json([
                'success' => false,
                'message' => 'المعاملة غير موجودة'
            ], 404);
        }
        
        // ========== ثانياً: نجيب الـ location_id ==========
        $location_id = $transaction->location_id ?? null;
        
        if (!$location_id) {
            return response()->json([
                'success' => false,
                'message' => 'الفرع غير محدد للمعاملة'
            ], 400);
        }
        
        // ========== ثالثاً: نفحص إعدادات الفوترة للفرع ==========
        $fatoraSettings = DB::table('settings_fatora')
            ->where('business_id', $businessId)
            ->where('location_id', $location_id)
            ->where('is_active', true)
            ->first();

        // التحقق من وجود إعدادات الفوترة
        if (!$fatoraSettings) {
            return response()->json([
                'success' => false,
                'message' => 'إعدادات الفوترة غير موجودة للفرع المحدد. يرجى تهيئة الإعدادات أولاً.',
                'location_id' => $location_id
            ], 400);
        }
        
        // التحقق من اكتمال الإعدادات
        if (!$fatoraSettings->client_id || !$fatoraSettings->secret_key || !$fatoraSettings->supplier_income_source) {
            return response()->json([
                'success' => false,
                'message' => 'إعدادات الفوترة غير مكتملة للفرع المحدد.',
                'location_id' => $location_id
            ], 400);
        }

        // تحديد نوع الفاتورة
        $invoiceType = $fatoraSettings->invoice_type ?? 'tax_invoice';
        
        // ========== رابعاً: Initialize Fatora Service مع location_id ==========
        $fatoraService = new FatoraService($businessId, $location_id);
        
        // ========== باقي الكود يضل كما هو ==========
        $invoiceNo = $transaction->invoice_no;
        $statusCheck = $fatoraService->getStatusAdvancedFatora($invoiceNo);
        
        // ... باقي الكود بدون تغيير
/**
 * Force resend invoice (خاصة للفواتير المرفوضة)
 */
public function forceResendInvoice(Request $request)
{
    try {
        $transactionId = $request->input('transaction_id');
        $businessId = Auth::user()->business_id;
        $reason = $request->input('reason', 'إعادة إرسال بطلب يدوي');

        if (!$transactionId) {
            return response()->json([
                'success' => false,
                'message' => 'Transaction ID is required'
            ], 400);
        }
        
        // جلب معلومات الفاتورة
        $transaction = DB::table('transactions')
            ->where('id', $transactionId)
            ->where('business_id', $businessId)
            ->first();
        
        if (!$transaction) {
            return response()->json([
                'success' => false,
                'message' => 'المعاملة غير موجودة'
            ], 404);
        }
        
        $fatoraService = new FatoraService($businessId);
        $invoiceNo = $transaction->invoice_no;
        
        // فحص الحالة المتقدمة
        $statusCheck = $fatoraService->getStatusAdvancedFatora($invoiceNo);
        
        if (!$statusCheck['success'] || !$statusCheck['data']['invoice_exists']) {
            return response()->json([
                'success' => false,
                'message' => 'الفاتورة غير موجودة',
                'data' => $statusCheck['data'] ?? null
            ], 404);
        }
        
        $statusData = $statusCheck['data'];
        
        // التحقق إذا كانت الفاتورة مرسلة بالفعل ومقبولة
        if ($statusData['fatora_exists'] && 
            in_array($statusData['fatora_status'], ['approved', 'pending', 'submitted']) &&
            $statusData['qr_code_exists']) {
            
            // تأكيد من المستخدم للإرسال القسري
            if (!$request->input('confirm_override', false)) {
                return response()->json([
                    'success' => false,
                    'message' => 'الفاتورة مرسلة ومقبولة بالفعل. هل تريد الإرسال القسري؟',
                    'data' => $statusData,
                    'requires_confirmation' => true,
                    'confirmation_message' => 'هذه الفاتورة مرسلة بالفعل وحالتها: ' . $statusData['fatora_status'] . '. هل تريد الإرسال القسري؟'
                ]);
            }
        }
        
        // استرجاع إعدادات الفوترة
        $fatoraSettings = DB::table('settings_fatora')
            ->where('business_id', $businessId)
            ->where('is_active', true)
            ->first();

        if (!$fatoraSettings) {
            return response()->json([
                'success' => false,
                'message' => 'إعدادات الفوترة غير موجودة'
            ], 400);
        }

        $invoiceType = $fatoraSettings->invoice_type ?? 'tax_invoice';
        
        // إرسال الفاتورة مع فلاج الإرسال القسري
        $options = [
            'invoice_type' => $invoiceType,
            'payment_method' => 'cash',
            'force_resend' => true,
            'resend_reason' => $reason,
            'update_existing' => $statusData['fatora_exists'] // تحديث السجل الموجود
        ];

        $result = $fatoraService->sendInvoice($transactionId, $options);
        
        if ($result['success']) {
            return response()->json([
                'success' => true,
                'message' => $result['message'] . ' (إعادة إرسال)',
                'data' => $result['data'],
                'qr_code' => $result['qr_code'] ?? null,
                'action' => 'forced_resend',
                'previous_status' => $statusData['fatora_status'] ?? null
            ]);
        } else {
            return response()->json([
                'success' => false,
                'message' => $result['message'],
                'error' => $result['error'] ?? null,
                'previous_status' => $statusData['fatora_status'] ?? null
            ], 400);
        }

    } catch (Exception $e) {
        Log::error('Force Resend Invoice Error: '.$e->getMessage());
        return response()->json([
            'success' => false,
            'message' => $e->getMessage()
        ], 500);
    }
}
    /**
     * Get invoice status
     */
    public function getInvoiceStatus(Request $request)
    {
        try {
            $transactionId = $request->input('transaction_id');
            $businessId = Auth::user()->business_id;
           // dd($transactionId);
            $fatoraService = new FatoraService($businessId);
            $status = $fatoraService->getInvoiceStatus($transactionId);
          // dd($request->all(), $request->query());

            if ($status) {
                return response()->json([
                    'success' => true,
                    'data' => $status
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'الفاتورة غير مرسلة بعد'
                ]);
            }
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
        dd([
    
]);

    }
    
    
      public function getStatusAdvancedFatora(Request $request)
    {
        try {
            $invoiceNo = $request->input('invoice_no');
            $businessId = Auth::user()->business_id;

            if (!$invoiceNo) {
                return response()->json([
                    'success' => false,
                    'message' => 'رقم الفاتورة مطلوب'
                ], 400);
            }

            $fatoraService = new FatoraService($businessId);
            $result = $fatoraService->getStatusAdvancedFatora($invoiceNo);

            return response()->json($result);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء فحص حالة الفاتورة',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Send Credit Invoice (فاتورة مرتجعات) to JoFotara
     */
    public function sendCreditInvoice(Request $request)
    {

        
        try {
            $returnTransactionId = $request->input('return_transaction_id');
            $returnReason = $request->input('return_reason', 'إرجاع بضاعة');
            $businessId = Auth::user()->business_id;

            if (!$returnTransactionId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Return Transaction ID is required'
                ], 400);
            }
              $fatoraSettings = DB::table('settings_fatora')
            ->where('business_id', $businessId)
            ->where('is_active', true)
            ->first();
      $invoiceType = $fatoraSettings ->invoice_type ?? 'tax_invoice';
        
  
            // Initialize Fatora Service
            $fatoraService = new FatoraService($businessId);

            // Check if already sent
            if ($fatoraService->isInvoiceSent($returnTransactionId)) {
                return response()->json([
                    'success' => false,
                    'message' => 'فاتورة المرتجعات مرسلة مسبقاً إلى نظام الفوترة',
                    'data' => $fatoraService->getInvoiceStatus($returnTransactionId)
                ]);
            }

            // Send credit invoice
            $result = $fatoraService->sendCreditInvoice($returnTransactionId, $returnReason,$invoiceType);

            if ($result['success']) {
                return response()->json([
                    'success' => true,
                    'message' => $result['message'],
                    'data' => $result['data'],
                    'qr_code' => $result['qr_code'] ?? null
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => $result['message'],
                    'error' => $result['error'] ?? null
                ], 400);
            }

        } catch (Exception $e) {
            dd($e);
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء إرسال فاتورة المرتجعات',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * TEST FUNCTION - Send Credit Invoice with hardcoded values
     */
    public function testCreditInvoice()
    {
        $businessId = Auth::user()->business_id;
        $fatoraService = new FatoraService($businessId);
        
        // This will dd() the result
        $fatoraService->sendCreditInvoiceForTest();
    }

    /**
     * Get invoice details from JoFotara by UUID
     */
    public function getInvoiceFromJoFotara(Request $request)
    {
        try {
            $invoiceUuid = $request->input('invoice_uuid');
            $businessId = Auth::user()->business_id;

            if (!$invoiceUuid) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invoice UUID is required'
                ], 400);
            }

            $fatoraService = new FatoraService($businessId);
            $invoice = $fatoraService->getInvoiceFromJoFotara($invoiceUuid);

            if ($invoice) {
                return response()->json([
                    'success' => true,
                    'data' => $invoice
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'لم يتم العثور على الفاتورة في نظام JoFotara'
                ], 404);
            }

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء جلب الفاتورة',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get list of invoices from JoFotara with filters
     */
    public function getInvoicesListFromJoFotara(Request $request)
    {
      
        try {
        //    dd(Auth::user());
            $businessId = Auth::user()->business_id;
            $filters = [];
            
            // Get filters from request
            if ($request->has('from_date')) {
                $filters['from_date'] = $request->input('from_date');
            }
            if ($request->has('to_date')) {
                $filters['to_date'] = $request->input('to_date');
            }
            if ($request->has('status')) {
                $filters['status'] = $request->input('status');
            }
            if ($request->has('page')) {
                $filters['page'] = $request->input('page');
            }
            if ($request->has('limit')) {
                $filters['limit'] = $request->input('limit');
            }

            $fatoraService = new FatoraService($businessId);
            $invoices = $fatoraService->getInvoicesListFromJoFotara($filters);
          
            if ($invoices) {
                return response()->json([
                    'success' => true,
                    'data' => $invoices
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'فشل جلب قائمة الفواتير من نظام JoFotara'
                ], 404);
            }

        } catch (Exception $e) {
            dd($e);
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء جلب قائمة الفواتير',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Sync invoice status from JoFotara to local database
     */
    public function syncInvoiceStatus(Request $request)
    {
        try {
            $transactionId = $request->input('transaction_id');
            $businessId = Auth::user()->business_id;

            if (!$transactionId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Transaction ID is required'
                ], 400);
            }

            $fatoraService = new FatoraService($businessId);
            $synced = $fatoraService->syncInvoiceStatus($transactionId);

            if ($synced) {
                return response()->json([
                    'success' => true,
                    'message' => 'تم مزامنة حالة الفاتورة بنجاح'
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'فشلت عملية المزامنة'
                ], 400);
            }

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء المزامنة',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get local invoices with filters
     */
    public function getLocalInvoices(Request $request)
    {
        try {
            $businessId = Auth::user()->business_id;
            
            $filters = [];
            
            // Get filters from request
            if ($request->has('status')) {
                $filters['status'] = $request->input('status');
            }
            if ($request->has('from_date')) {
                $filters['from_date'] = $request->input('from_date');
            }
            if ($request->has('to_date')) {
                $filters['to_date'] = $request->input('to_date');
            }
            if ($request->has('invoice_type')) {
                $filters['invoice_type'] = $request->input('invoice_type');
            }
            if ($request->has('is_credit_invoice')) {
                $filters['is_credit_invoice'] = $request->input('is_credit_invoice');
            }

            $fatoraService = new FatoraService($businessId);
            $invoices = $fatoraService->getLocalInvoices($filters);

            return response()->json([
                'success' => true,
                'data' => $invoices,
                'count' => $invoices->count()
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء جلب الفواتير المحلية',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Import missing invoice from JoFotara to local database
     */
    public function importInvoice(Request $request)
    {
        try {
            $transactionId = $request->input('transaction_id');
            $businessId = Auth::user()->business_id;

            if (!$transactionId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Transaction ID is required'
                ], 400);
            }

            $fatoraService = new FatoraService($businessId);
            $result = $fatoraService->importInvoiceFromJoFotara($transactionId);

            if ($result['success']) {
                return response()->json($result);
            } else {
                return response()->json($result, 400);
            }

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء الاستيراد',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Bulk sync invoices from a date range
     */
    public function bulkSyncInvoices(Request $request)
    {
        try {
            $fromDate = $request->input('from_date');
            $toDate = $request->input('to_date');
            $businessId = Auth::user()->business_id;

            if (!$fromDate || !$toDate) {
                return response()->json([
                    'success' => false,
                    'message' => 'تواريخ البداية والنهاية مطلوبة'
                ], 400);
            }

            $fatoraService = new FatoraService($businessId);
            $result = $fatoraService->bulkSyncInvoices($fromDate, $toDate);

            return response()->json($result);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء المزامنة الشاملة',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Find missing invoices
     */
    public function findMissingInvoices(Request $request)
    {
        try {
            $fromDate = $request->input('from_date');
            $toDate = $request->input('to_date');
            $businessId = Auth::user()->business_id;

            if (!$fromDate || !$toDate) {
                return response()->json([
                    'success' => false,
                    'message' => 'تواريخ البداية والنهاية مطلوبة'
                ], 400);
            }

            $fatoraService = new FatoraService($businessId);
            $result = $fatoraService->findMissingInvoices($fromDate, $toDate);

            return response()->json($result);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء البحث عن الفواتير الناقصة',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Send credit invoice with auto-import of original invoice if missing
     */
    public function sendCreditInvoiceWithAutoImport(Request $request)
    {
        try {
            $returnTransactionId = $request->input('return_transaction_id');
            $returnReason = $request->input('return_reason', 'إرجاع بضاعة');
            $businessId = Auth::user()->business_id;

            if (!$returnTransactionId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Return Transaction ID is required'
                ], 400);
            }

            $fatoraService = new FatoraService($businessId);
            $result = $fatoraService->sendCreditInvoiceWithAutoImport($returnTransactionId, $returnReason);

            if ($result['success']) {
                return response()->json($result);
            } else {
                return response()->json($result, 400);
            }

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء إرسال فاتورة المرتجعات',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
