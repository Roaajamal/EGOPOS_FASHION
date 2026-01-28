<?php

namespace App\Services;

use JBadarneh\JoFotara\JoFotaraService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Exception;

class FatoraService
{
    protected $settings;
    protected $businessId;
protected $locationId;
    public function __construct($businessId,$locationId = null)
    {
        $this->businessId = $businessId;
           $this->locationId = $locationId;
        $this->settings = $this->getSettings();
    }

    /**
     * Get Fatora settings for business
     */
protected function getSettings()
{
    // إذا في location_id، نبحث عن إعدادات للفرع
    if ($this->locationId) {
        $settings = DB::table('settings_fatora')
            ->where('business_id', $this->businessId)
            ->where('location_id', $this->locationId)
            ->where('is_active', true)
            ->first();
            
        if ($settings) {
            if (!$settings->client_id || !$settings->secret_key || !$settings->supplier_income_source) {
                throw new Exception('إعدادات الفاتورة للفرع غير مكتملة.');
            }
            return $settings;
        }
        throw new Exception('إعدادات الفاتورة غير موجودة للفرع المحدد.');
    }
    
    // إذا ما في location_id، نستخدم الطريقة القديمة
    $settings = DB::table('settings_fatora')
        ->where('business_id', $this->businessId)
        ->where('is_active', true)
        ->first();

    if (!$settings) {
        throw new Exception('إعدادات الفاتورة غير موجودة. يرجى إضافة الإعدادات أولاً.');
    }

    if (!$settings->client_id || !$settings->secret_key || !$settings->supplier_income_source) {
        throw new Exception('إعدادات الفاتورة غير مكتملة.');
    }

    return $settings;
}
    /**
     * Send invoice to JoFotara system
     *
     * @param int $transactionId
     * @param array $options - invoice_type, payment_method
     * @return array
     */
    public function sendInvoice($transactionId, array $options = [])
    {
        // Set locale to C to ensure dot as decimal separator
        $previousLocale = setlocale(LC_NUMERIC, 0);
        setlocale(LC_NUMERIC, 'C');
        
        try {
    // Check if already sent
    $existingInvoice = DB::table('fatora_invoices')
        ->where('transaction_id', $transactionId)
        ->where('business_id', $this->businessId)
        ->first();

    if ($existingInvoice) {
        $hasQr = !empty($existingInvoice->qr_code);
        $status = $existingInvoice->status;
        
        // فقط الحالتين المسموح بهما: rejected أو sending بدون QR
        if (($status === 'rejected' || $status === 'sending') && !$hasQr) {
            // تحديث الحالة والمتابعة
            DB::table('fatora_invoices')
                 ->where('business_id', $this->businessId)
                ->where('transaction_id', $transactionId)
                ->update(['status' => 'resending']);
            
            Log::info('إعادة إرسال فاتورة ' . $status, [
                'transaction_id' => $transactionId
            ]);
            
            // أكمل العملية
        } else {
            // أي حالة أخرى - مانع
            setlocale(LC_NUMERIC, $previousLocale);
            return [
                'success' => false,
                'message' => 'الفاتورة مرسلة مسبقاً',
                'data' => $existingInvoice,
                'previous_status' => $status,
                'has_qr' => $hasQr
            ];
        }
    }

    // Get transaction data
    $transaction = $this->getTransaction($transactionId);
    if (!$transaction) {
        throw new Exception('Transaction not found');
    }
            // Initialize JoFotara Service
            $invoice = new JoFotaraService(
                $this->settings->client_id,
                $this->settings->secret_key
            );

            // Set basic information
            $uuid = Str::uuid()->toString();
            // استخدم 'income' كنوع افتراضي بدلاً من 'general_sales'
            $invoiceType = $options['invoice_type'] ?? 'income';
            $paymentMethod = $options['payment_method'] ?? 'cash';

            $invoice->basicInformation()
                ->setInvoiceId($transaction->invoice_no)
                ->setUuid($uuid)
                ->setIssueDate(date('d-m-Y', strtotime($transaction->transaction_date)))
                ->setInvoiceType($invoiceType);

            // Set payment method
            if ($paymentMethod === 'cash') {
                $invoice->basicInformation()->cash();
            } else {
                $invoice->basicInformation()->receivable();
            }

            // Set seller information
            $sellerTin = !empty($this->settings->tin) 
                ? preg_replace('/[^0-9]/', '', $this->settings->tin) 
                : '';
            
            $invoice->sellerInformation()
                ->setName($this->settings->registration_name ?? 'Default Company')
                ->setTin($sellerTin);

            // Set customer information
            $customer = $this->getCustomer($transaction->contact_id);
            
            // Get customer ID - must be numeric only (9 digits minimum)
            $customerId = '999999999'; // Default for walk-in customers
            $customerName = 'Walk-in Customer';
            
            if ($customer) {
                $customerName = $customer->name ?? 'Walk-in Customer';
                
                // Priority 1: tax_number (الرقم الضريبي)
                if (!empty($customer->tax_number)) {
                    $cleanId = preg_replace('/[^0-9]/', '', $customer->tax_number);
                    if (strlen($cleanId) >= 6) {
                        $customerId = $cleanId;
                    }
                }
                
                // Priority 2: If tax_number not valid, try ID from contact_id column
                if ($customerId == '999999999' && !empty($customer->contact_id)) {
                    $cleanId = preg_replace('/[^0-9]/', '', $customer->contact_id);
                    if (strlen($cleanId) >= 6) {
                        $customerId = $cleanId;
                    }
                }
                
                // Priority 3: Use the primary key id as last resort
                if ($customerId == '999999999' && !empty($customer->id)) {
                    // Pad with zeros to make it 9 digits
                    $customerId = str_pad($customer->id, 9, '0', STR_PAD_LEFT);
                }
            }

            $invoice->customerInformation()
                ->setId($customerId, 'TIN')
                ->setName($customerName);

            // Add phone if available (remove non-numeric characters)
            if ($customer && !empty($customer->mobile)) {
                $phone = preg_replace('/[^0-9]/', '', $customer->mobile);
                if (strlen($phone) >= 9) {
                    $invoice->customerInformation()->setPhone($phone);
                }
            }
            
            // Add city code (default Amman)
            $invoice->customerInformation()->setCityCode('JO-AM');

            // Set supplier income source (REQUIRED)
            $invoice->supplierIncomeSource($this->settings->supplier_income_source);

            // Add invoice items
            $items = $this->getTransactionItems($transactionId);
            $itemCounter = 1;

            foreach ($items as $item) {
                try {
                    // Calculate tax percentage if not available - CLEAN all values
                    $taxPercent = 0;
                    if (!empty($item->tax_percent)) {
                        $taxPercent = $this->cleanNumericValue($item->tax_percent);
                    } elseif (!empty($item->item_tax) && !empty($item->unit_price_before_discount) && $item->unit_price_before_discount > 0) {
                        $itemTax = $this->cleanNumericValue($item->item_tax);
                        $priceBeforeDiscount = $this->cleanNumericValue($item->unit_price_before_discount);
                        if ($priceBeforeDiscount > 0) {
                            $taxPercent = ($itemTax / $priceBeforeDiscount) * 100;
                        }
                    }

                    // Get unit price (without tax) - CLEAN
                    $unitPrice = $this->cleanNumericValue($item->unit_price_before_discount ?? $item->unit_price ?? 0);
                    if ($unitPrice <= 0) {
                        $unitPrice = 1.0;
                    }
                    
                    // Clean quantity
                    $quantity = $this->cleanNumericValue($item->quantity);
                    if ($quantity <= 0) {
                        continue; // Skip zero quantity items
                    }
                    
                    // Clean product name COMPLETELY - remove ALL special chars
                    $itemName = $item->product_name ?? 'Product';
                    // Remove ALL quotes, commas, semicolons, and other special XML chars
                    $itemName = preg_replace('/["\',;&#<>]/', '', $itemName);
                    // Remove multiple spaces
                    $itemName = preg_replace('/\s+/', ' ', $itemName);
                    $itemName = trim($itemName);
                    
                    if (empty($itemName)) {
                        $itemName = 'Product';
                    }
                    
                    if (!empty($item->variation_name) && $item->variation_name != 'DUMMY') {
                        $varName = preg_replace('/["\',;&#<>]/', '', $item->variation_name);
                        $varName = preg_replace('/\s+/', ' ', trim($varName));
                        if (!empty($varName)) {
                            $itemName .= ' - ' . $varName;
                        }
                    }
                    
                    // Limit length to prevent issues
                    if (strlen($itemName) > 100) {
                        $itemName = substr($itemName, 0, 100);
                    }
                    
                    // Format all values using sprintf
                    $quantity = (float)sprintf('%.4f', $quantity);
                    $unitPrice = (float)sprintf('%.4f', $unitPrice);
                    $taxPercent = (float)sprintf('%.2f', $taxPercent);

                    // Add item with basic info
                    $invoiceItem = $invoice->items()
                        ->addItem((string)$itemCounter)
                        ->setQuantity($quantity)
                        ->setUnitPrice($unitPrice)
                        ->setDescription($itemName);

                    // Add tax
                    if ($taxPercent > 0) {
                        $invoiceItem->tax($taxPercent);
                    } else {
                        // For zero tax, use zeroTax() method
                        try {
                            $invoiceItem->zeroTax();
                        } catch (\Exception $e) {
                            // If zeroTax doesn't work, try taxExempted
                            try {
                                $invoiceItem->taxExempted();
                            } catch (\Exception $e2) {
                                // SDK doesn't support zero tax - skip tax completely
                                // Don't call any tax method
                            }
                        }
                    }

                    // Add discount if exists - CLEAN
                    $discount = $this->cleanNumericValue($item->line_discount_amount ?? $item->item_discount ?? 0);
                    $discount = (float)sprintf('%.4f', $discount);
                    
                    if ($discount > 0) {
                        $invoiceItem->setDiscount($discount);
                    }

                } catch (\Exception $itemException) {
                    // Log the error but continue with other items
                    Log::warning('Error adding invoice item: ' . $itemException->getMessage(), [
                        'item_id' => $item->id ?? null,
                        'product_id' => $item->product_id ?? null
                    ]);
                }

                $itemCounter++;
            }

            // Calculate totals automatically
            $invoice->invoiceTotals();

            // Send to JoFotara
            $response = $invoice->send();
           
            // Use the response object's methods to extract data properly
            // The JoFotaraResponse class has getter methods for all data
            $isSuccessful = $response->isSuccess();
            $qrCode = $response->getQrCode();
            $xmlContent = $response->getSubmittedInvoice(); // Base64 encoded XML
            $invoiceNumber = $response->getInvoiceNumber();
            $invoiceUuid = $response->getInvoiceUuid();
            $rawData = $response->getRawResponse();
            $responseData = json_encode($rawData);
            
            // Get error message if any
            $errorMessage = null;
            if ($response->hasErrors()) {
                $errorMessage = $response->getErrorSummary();
            }


          


       // Save to database - إما insert جديد أو update موجود
$exists = DB::table('fatora_invoices')
    ->where('transaction_id', $transactionId)
    ->where('business_id', $this->businessId)
    ->exists();

$invoiceData = [
    'transaction_id' => $transactionId,
    'business_id' => $this->businessId,
      'location_id' => $this->locationId, 
    'invoice_uuid' => $uuid,
    'invoice_type' => $invoiceType,
    'payment_method' => $paymentMethod,
    'qr_code' => $qrCode,
    'xml_content' => $xmlContent,
    'response_data' => $responseData,
    'status' => $isSuccessful ? 'sent' : 'rejected',
    'error_message' => $errorMessage,
    'sent_at' => now(),
    'updated_at' => now(),
];

// Add system numbers
if (Schema::hasColumn('fatora_invoices', 'system_invoice_number')) {
    $invoiceData['system_invoice_number'] = $invoiceNumber;
}
if (Schema::hasColumn('fatora_invoices', 'system_invoice_uuid')) {
    $invoiceData['system_invoice_uuid'] = $invoiceUuid;
}

if ($exists) {
    // تحديث السجل الموجود
    DB::table('fatora_invoices')
        ->where('transaction_id', $transactionId)
        ->where('business_id', $this->businessId)
        ->update($invoiceData);
} else {
    // إضافة سجل جديد مع created_at
    $invoiceData['created_at'] = now();
    DB::table('fatora_invoices')->insert($invoiceData);
}     // Restore locale before returning
            setlocale(LC_NUMERIC, $previousLocale);

            return [
                'success' => $isSuccessful,
                'message' => $isSuccessful ? 'تم إرسال الفاتورة بنجاح إلى نظام الفوترة الأردني' : 'فشل إرسال الفاتورة',
                'data' => [
                    'invoice_number' => $invoiceNumber,
                    'invoice_uuid' => $invoiceUuid,
                    'status' => $isSuccessful ? 'SUBMITTED' : 'FAILED',
                    'raw_response' => $responseData
                ],
                'qr_code' => $qrCode,
                'invoice_number' => $invoiceNumber,
                'system_uuid' => $invoiceUuid
            ];

        } catch (Exception $e) {
            // Restore locale before returning
            setlocale(LC_NUMERIC, $previousLocale);
            
            Log::error('Fatora Invoice Error: ' . $e->getMessage(), [
                'transaction_id' => $transactionId,
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'message' => $e->getMessage(),
                'error' => $e->getMessage()
            ];
        }
    }
private function calculateInvoiceTotals($items): array
{
    $taxExclusive = 0;
    $discountTotal = 0;
    $taxTotal = 0;
    
    foreach ($items as $item) {
        $unitPrice = $this->cleanNumericValue($item->unit_price_before_discount ?? $item->unit_price ?? 0);
        $quantity = $this->cleanNumericValue($item->quantity);
        $discount = $this->cleanNumericValue($item->line_discount_amount ?? $item->item_discount ?? 0);
        $itemTax = $this->cleanNumericValue($item->item_tax ?? 0);
        
        $itemSubtotal = $unitPrice * $quantity;
        
        $taxExclusive += $itemSubtotal;
        $discountTotal += $discount;
        $taxTotal += $itemTax;
    }
    
    $taxInclusive = $taxExclusive - $discountTotal + $taxTotal;
    
    return [
        'taxExclusive' => $taxExclusive,
        'discountTotal' => $discountTotal,
        'taxTotal' => $taxTotal,
        'taxInclusive' => $taxInclusive,
        'payable' => $taxInclusive, // أو حسب منطق إضافي
    ];
}
    /**
     * Get transaction details
     */
    protected function getTransaction($transactionId)
    {
        return DB::table('transactions')
            ->where('id', $transactionId)
            ->first();
    }

    /**
     * Get customer details
     */
    protected function getCustomer($contactId)
    {
        if (!$contactId) {
            return null;
        }

        return DB::table('contacts')
            ->where('id', $contactId)
            ->first();
    }

    /**
     * Get transaction items
     */
    protected function getTransactionItems($transactionId)
    {
        return DB::table('transaction_sell_lines as tsl')
            ->leftJoin('products as p', 'tsl.product_id', '=', 'p.id')
            ->leftJoin('variations as v', 'tsl.variation_id', '=', 'v.id')
            ->leftJoin('tax_rates as tr', 'tsl.tax_id', '=', 'tr.id')
            ->where('tsl.transaction_id', $transactionId)
            ->select([
                'tsl.*',
                'p.name as product_name',
                'v.name as variation_name',
                'tr.amount as tax_percent'
            ])
            ->get();
    }
      public function getStatusAdvancedFatora($invoiceNo)
    {
        try {
            $result = [
                'invoice_exists' => false,
                'fatora_exists' => false,
                'transaction_status' => null,
                'fatora_status' => null,
                'qr_code_exists' => false,
                'response_data_exists' => false,
                'response_status' => null,
                'errors' => [],
                'warnings' => [],
                'suggestions' => []
            ];

            // ========== الخطوة 1: البحث عن الفاتورة في جدول transactions ==========
            $transaction = DB::table('transactions')
                ->where('invoice_no', $invoiceNo)
                ->where('business_id', $this->businessId)
                ->first();

            if ($transaction) {
                $result['invoice_exists'] = true;
                $result['transaction_id'] = $transaction->id;
                $result['transaction_status'] = $transaction->status;
                $result['transaction_type'] = $transaction->type;
                $result['transaction_date'] = $transaction->transaction_date;
                $result['final_total'] = $transaction->final_total;
                
                // ========== الخطوة 2: البحث في جدول fatora_invoices ==========
                $fatoraInvoice = DB::table('fatora_invoices')
                    ->where('transaction_id', $transaction->id)
                    ->where('business_id', $this->businessId)
                    ->first();

                if ($fatoraInvoice) {
                    $result['fatora_exists'] = true;
                    $result['fatora_status'] = $fatoraInvoice->status;
                    $result['fatora_record_id'] = $fatoraInvoice->id;
                    $result['sent_at'] = $fatoraInvoice->sent_at;
                    $result['invoice_uuid'] = $fatoraInvoice->invoice_uuid;
                    $result['system_invoice_uuid'] = $fatoraInvoice->system_invoice_uuid ?? null;
                    $result['system_invoice_number'] = $fatoraInvoice->system_invoice_number ?? null;
                    
                    // ========== الخطوة 3: فحص QR Code ==========
                    if (!empty($fatoraInvoice->qr_code)) {
                        $result['qr_code_exists'] = true;
                        $result['qr_code_preview'] = substr($fatoraInvoice->qr_code, 0, 50) . '...';
                    } else {
                        $result['warnings'][] = 'الفاتورة لا تحتوي على QR Code';
                        $result['suggestions'][] = 'قد تحتاج إلى إعادة إرسال الفاتورة أو مزامنة الحالة';
                    }
                    
                    // ========== الخطوة 4: فحص Response Data ==========
                    if (!empty($fatoraInvoice->response_data)) {
                        $result['response_data_exists'] = true;
                        
                        // محاولة فك تشفير JSON
                        try {
                            $responseData = json_decode($fatoraInvoice->response_data, true);
                            if ($responseData && is_array($responseData)) {
                                $result['response_status'] = $responseData['status'] ?? $responseData['EINV_STATUS'] ?? 'UNKNOWN';
                                
                                // استخراج معلومات مهمة من الرد
                                $result['response_summary'] = [
                                    'invoice_number_system' => $responseData['invoiceNumber'] ?? $responseData['EINV_NUM'] ?? null,
                                    'invoice_uuid_system' => $responseData['invoiceUuid'] ?? $responseData['EINV_INV_UUID'] ?? null,
                                    'submission_date' => $responseData['createdAt'] ?? $responseData['EINV_CREATED_AT'] ?? null,
                                    'has_errors' => $responseData['hasErrors'] ?? false,
                                    'error_summary' => $responseData['errorSummary'] ?? $responseData['EINV_ERROR'] ?? null
                                ];
                            }
                        } catch (\Exception $e) {
                            $result['warnings'][] = 'تعذر تحليل بيانات الرد: ' . $e->getMessage();
                        }
                    } else {
                        $result['warnings'][] = 'لا توجد بيانات استجابة مخزنة';
                    }
                    
                    // ========== الخطوة 5: فحص Error Message ==========
                    if (!empty($fatoraInvoice->error_message)) {
                        $result['errors'][] = 'رسالة خطأ: ' . $fatoraInvoice->error_message;
                        $result['suggestions'][] = 'يجب معالجة الخطأ قبل إعادة المحاولة';
                    }
                    
                    // ========== الخطوة 6: فحص XML Content ==========
                    if (!empty($fatoraInvoice->xml_content)) {
                        $result['xml_exists'] = true;
                        $result['xml_size'] = strlen($fatoraInvoice->xml_content);
                    }
                    
                    // ========== الخطوة 7: التحقق من حالة Credit Invoice ==========
                    if ($fatoraInvoice->is_credit_invoice) {
                        $result['is_credit_invoice'] = true;
                        $result['original_transaction_id'] = $fatoraInvoice->original_transaction_id;
                        $result['return_reason'] = $fatoraInvoice->return_reason;
                        
                        // التحقق من وجود الفاتورة الأصلية
                        $originalFatora = DB::table('fatora_invoices')
                            ->where('transaction_id', $fatoraInvoice->original_transaction_id)
                            ->first();
                        
                        if ($originalFatora) {
                            $result['original_invoice_exists'] = true;
                            $result['original_invoice_status'] = $originalFatora->status;
                        } else {
                            $result['warnings'][] = 'الفاتورة الأصلية غير موجودة في سجلات الفوترة';
                        }
                    }
                    
                    // ========== الخطوة 8: إنشاء حالة موجزة ==========
                    $result['summary_status'] = $this->generateSummaryStatus($result);
                    
                } else {
                    $result['warnings'][] = 'الفاتورة موجودة في المعاملات ولكن لم يتم إرسالها لنظام الفوترة';
                    $result['suggestions'][] = 'يجب إرسال الفاتورة باستخدام sendInvoice()';
                    $result['summary_status'] = 'NOT_SENT_TO_FATORA';
                }
                
            } else {
                $result['errors'][] = 'رقم الفاتورة غير موجود في قاعدة البيانات';
                $result['suggestions'][] = 'تأكد من رقم الفاتورة أو تحقق من قاعدة البيانات';
                $result['summary_status'] = 'INVOICE_NOT_FOUND';
            }
            
            // ========== الخطوة 9: فحص إضافي إذا كانت الفاتورة مرسلة ==========
            if ($result['fatora_exists'] && $result['fatora_status'] == 'sent') {
                // محاولة سحب الحالة المحدثة من JoFotara
                if (!empty($result['system_invoice_uuid'])) {
                    $remoteStatus = $this->getInvoiceFromJoFotara($result['system_invoice_uuid']);
                    if ($remoteStatus) {
                        $result['remote_status'] = $remoteStatus['EINV_STATUS'] ?? 'UNKNOWN';
                        $result['last_verified'] = now()->toDateTimeString();
                        
                        // مقارنة الحالة المحلية مع الجهة
                        if ($result['remote_status'] !== 'SUBMITTED' && $result['fatora_status'] == 'sent') {
                            $result['warnings'][] = 'حالة الفاتورة في نظام JoFotara مختلفة عن الحالة المحلية';
                            $result['suggestions'][] = 'يجب مزامنة الحالة باستخدام syncInvoiceStatus()';
                        }
                    }
                }
            }
            
            // ========== الخطوة 10: إضافة معلومات التصحيح ==========
            $result['checked_at'] = now()->toDateTimeString();
            $result['business_id'] = $this->businessId;
            $result['invoice_no'] = $invoiceNo;
            
            return [
                'success' => true,
                'message' => 'تم فحص حالة الفاتورة بنجاح',
                'data' => $result
            ];
            
        } catch (Exception $e) {
            Log::error('Advanced Fatora Status Check Error: ' . $e->getMessage(), [
                'invoice_no' => $invoiceNo,
                'business_id' => $this->businessId
            ]);
            
            return [
                'success' => false,
                'message' => 'حدث خطأ أثناء فحص حالة الفاتورة',
                'error' => $e->getMessage(),
                'invoice_no' => $invoiceNo
            ];
        }
    }
    
    /**
     * توليد حالة موجزة بناءً على الفحص
     */
    protected function generateSummaryStatus($resultData)
    {
        if (!$resultData['invoice_exists']) {
            return 'INVOICE_NOT_FOUND';
        }
        
        if (!$resultData['fatora_exists']) {
            return 'NOT_SENT_TO_FATORA';
        }
        
        if (!empty($resultData['errors'])) {
            return 'HAS_ERRORS';
        }
        
        if ($resultData['fatora_status'] == 'sent' && $resultData['qr_code_exists']) {
            return 'SUCCESS_SENT_WITH_QR';
        }
        
        if ($resultData['fatora_status'] == 'sent') {
            return 'SENT_NO_QR';
        }
        
        if ($resultData['fatora_status'] == 'rejected') {
            return 'REJECTED_BY_FATORA';
        }
        
        if ($resultData['fatora_status'] == 'pending') {
            return 'PENDING_SUBMISSION';
        }
        
        return 'UNKNOWN_STATUS';
    }


    /**
     * Get invoice status
     */
    public function getInvoiceStatus($transactionId)
    {
        return DB::table('fatora_invoices')
            ->where('transaction_id', $transactionId)
            ->first();
    }

    /**
     * Check if invoice was sent
     */
    public function isInvoiceSent($transactionId)
    {
        return DB::table('fatora_invoices')
            ->where('transaction_id', $transactionId)
            ->exists();
    }

    /**
     * Send Credit Invoice (فاتورة مرتجعات) to JoFotara
     *
     * @param int $returnTransactionId - معرف فاتورة المرتجعات
     * @param string $returnReason - سبب الإرجاع
     * @return array
     */
    public function sendCreditInvoice($returnTransactionId, $returnReason = 'إرجاع بضاعة',$invoiceType)
    {
            
        
  
        // Set locale to C to ensure dot as decimal separator
        $previousLocale = setlocale(LC_NUMERIC, 0);
        setlocale(LC_NUMERIC, 'C');
        
        try {
            // 1. Check if already sent
            if ($this->isInvoiceSent($returnTransactionId)) {
                setlocale(LC_NUMERIC, $previousLocale);
                return [
                    'success' => false,
                    'message' => 'فاتورة المرتجعات مرسلة مسبقاً',
                ];
            }

            // 2. Get return transaction (sell_return)
            $returnTransaction = DB::table('transactions')
                ->where('id', $returnTransactionId)
                ->where('type', 'sell_return')
                ->first();

            if (!$returnTransaction) {
                throw new Exception('فاتورة المرتجعات غير موجودة');
            }

            // 3. Get original transaction
            $originalTransactionId = $returnTransaction->return_parent_id;
            if (!$originalTransactionId) {
                throw new Exception('الفاتورة الأصلية غير محددة');
            }

            // 4. Get original invoice from fatora_invoices
            $originalFatoraInvoice = DB::table('fatora_invoices')
                ->where('transaction_id', $originalTransactionId)
                ->whereIn('status', ['sent', 'accepted'])
                ->first();

            if (!$originalFatoraInvoice) {
                throw new Exception('الفاتورة الأصلية غير مرسلة لنظام الفوترة. يجب إرسال الفاتورة الأصلية أولاً.');
            }

            // 5. Get original transaction details for amount
            $originalTransaction = DB::table('transactions')
                ->where('id', $originalTransactionId)
                ->first();

            // 6. Initialize JoFotara Service
            $invoice = new JoFotaraService(
                $this->settings->client_id,
                $this->settings->secret_key
            );

            // 7. Set basic information as CREDIT INVOICE
            $uuid = Str::uuid()->toString();
            
            // IMPORTANT: Use system_invoice_uuid from JoFotara, NOT the local invoice_uuid
            $originalUuidForJoFotara = $originalFatoraInvoice->system_invoice_uuid ?? $originalFatoraInvoice->invoice_uuid;

            $invoice->basicInformation()
                ->setInvoiceId($returnTransaction->invoice_no)
                ->setUuid($uuid)
                ->setIssueDate(date('d-m-Y', strtotime($returnTransaction->transaction_date)))
                ->setInvoiceType($invoiceType)
                ->cash()
                ->asCreditInvoice(
                    originalInvoiceId: $originalTransaction->invoice_no,
                    originalInvoiceUuid: $originalUuidForJoFotara,
                    originalFullAmount: $this->cleanNumericValue($originalTransaction->final_total)
                );

            // Set return reason
            $invoice->setReasonForReturn($returnReason);

            // 8. Set seller information
            $sellerTin = !empty($this->settings->tin) 
                ? preg_replace('/[^0-9]/', '', $this->settings->tin) 
                : '';
            
            $invoice->sellerInformation()
                ->setName($this->settings->registration_name ?? 'Default Company')
                ->setTin($sellerTin);

            // 9. Set customer information (same as original)
            $customer = $this->getCustomer($returnTransaction->contact_id);
            
            $customerId = '999999999';
            $customerName = 'Walk-in Customer';
            
            if ($customer) {
                $customerName = $customer->name ?? 'Walk-in Customer';
                
                if (!empty($customer->tax_number)) {
                    $cleanId = preg_replace('/[^0-9]/', '', $customer->tax_number);
                    if (strlen($cleanId) >= 6) {
                        $customerId = $cleanId;
                    }
                }
                
                if ($customerId == '999999999' && !empty($customer->contact_id)) {
                    $cleanId = preg_replace('/[^0-9]/', '', $customer->contact_id);
                    if (strlen($cleanId) >= 6) {
                        $customerId = $cleanId;
                    }
                }
                
                if ($customerId == '999999999' && !empty($customer->id)) {
                    $customerId = str_pad($customer->id, 9, '0', STR_PAD_LEFT);
                }
            }

            $invoice->customerInformation()
                ->setId($customerId, 'TIN')
                ->setName($customerName);

            if ($customer && !empty($customer->mobile)) {
                $phone = preg_replace('/[^0-9]/', '', $customer->mobile);
                if (strlen($phone) >= 9) {
                    $invoice->customerInformation()->setPhone($phone);
                }
            }
            
            $invoice->customerInformation()->setCityCode('JO-AM');

            // 10. Set supplier income source
            $invoice->supplierIncomeSource($this->settings->supplier_income_source);

            // 11. Add return items
            $items = $this->getReturnItems($returnTransactionId);
            
            if ($items->isEmpty()) {
                throw new Exception('لا توجد أصناف في فاتورة المرتجعات');
            }
            
            $itemCounter = 1;

            foreach ($items as $item) {
                try {
                    // Clean all numeric values to ensure NO commas
                    $taxPercent = 0;
                    if (!empty($item->tax_percent)) {
                        $taxPercent = $this->cleanNumericValue($item->tax_percent);
                    } elseif (!empty($item->item_tax) && !empty($item->unit_price_before_discount) && $item->unit_price_before_discount > 0) {
                        $itemTax = $this->cleanNumericValue($item->item_tax);
                        $priceBeforeDiscount = $this->cleanNumericValue($item->unit_price_before_discount);
                        if ($priceBeforeDiscount > 0) {
                            $taxPercent = ($itemTax / $priceBeforeDiscount) * 100;
                        }
                    }

                    $unitPrice = $this->cleanNumericValue($item->unit_price_before_discount ?? $item->unit_price ?? 0);
                    if ($unitPrice <= 0) {
                        $unitPrice = 1.0;
                    }
                    
                    // For returns, use quantity_returned if available
                    $quantity = $this->cleanNumericValue($item->quantity_returned ?? $item->quantity ?? 0);
                    if ($quantity <= 0) {
                        continue; // Skip items with zero quantity
                    }
                    
                    // Clean product name COMPLETELY - remove ALL special chars
                    $itemName = $item->product_name ?? 'Product';
                    // Remove ALL quotes, commas, semicolons, and other special XML chars
                    $itemName = preg_replace('/["\',;&#<>]/', '', $itemName);
                    // Remove multiple spaces
                    $itemName = preg_replace('/\s+/', ' ', $itemName);
                    $itemName = trim($itemName);
                    
                    if (empty($itemName)) {
                        $itemName = 'Product';
                    }
                    
                    if (!empty($item->variation_name) && $item->variation_name != 'DUMMY') {
                        $varName = preg_replace('/["\',;&#<>]/', '', $item->variation_name);
                        $varName = preg_replace('/\s+/', ' ', trim($varName));
                        if (!empty($varName)) {
                            $itemName .= ' - ' . $varName;
                        }
                    }
                    
                    // Limit length to prevent issues
                    if (strlen($itemName) > 100) {
                        $itemName = substr($itemName, 0, 100);
                    }
                    
                    // Ensure all values are proper floats WITHOUT formatting using sprintf
                    $quantity = (float)sprintf('%.4f', $quantity);
                    $unitPrice = (float)sprintf('%.4f', $unitPrice);
                    $taxPercent = (float)sprintf('%.2f', $taxPercent);

                    $invoiceItem = $invoice->items()
                        ->addItem((string)$itemCounter)
                        ->setQuantity($quantity)
                        ->setUnitPrice($unitPrice)
                        ->setDescription($itemName . ' (مُرتجع)');

                    if ($taxPercent > 0) {
                        $invoiceItem->tax($taxPercent);
                    } else {
                        // For zero tax, use zeroTax() method
                        try {
                            $invoiceItem->zeroTax();
                        } catch (\Exception $e) {
                            // If zeroTax doesn't work, try taxExempted
                            try {
                                $invoiceItem->taxExempted();
                            } catch (\Exception $e2) {
                                // SDK doesn't support zero tax - skip tax completely
                                // Don't call any tax method
                            }
                        }
                    }

                    $discount = $this->cleanNumericValue($item->line_discount_amount ?? $item->item_discount ?? 0);
                    $discount = (float)sprintf('%.4f', $discount);
                    
                    if ($discount > 0) {
                        $invoiceItem->setDiscount($discount);
                    }

                } catch (\Exception $itemException) {
                    Log::warning('Error adding credit invoice item: ' . $itemException->getMessage());
                    continue;
                }

                $itemCounter++;
            }

            // 12. Calculate totals automatically
            $invoice->invoiceTotals();

            // Send to JoFotara
            $response = $invoice->send();
            
            // Restore locale
            setlocale(LC_NUMERIC, $previousLocale);
            
            // Use the response object's methods to extract data properly
            $isSuccessful = $response->isSuccess();
            $qrCode = $response->getQrCode();
            $xmlContent = $response->getSubmittedInvoice(); // Base64 encoded XML
            $invoiceNumber = $response->getInvoiceNumber();
            $invoiceUuid = $response->getInvoiceUuid();
            $rawData = $response->getRawResponse();
            $responseData = json_encode($rawData);
            
            // Get error message if any
            $errorMessage = null;
            if ($response->hasErrors()) {
                $errorMessage = $response->getErrorSummary();
            }

            // 14. Save to database
            $invoiceData = [
                'transaction_id' => $returnTransactionId,
                'business_id' => $this->businessId,
                   'location_id' => $this->locationId, 
                'invoice_uuid' => $uuid,
                'invoice_type' => $invoiceType,
                'payment_method' => 'cash',
                'is_credit_invoice' => true,
                'original_transaction_id' => $originalTransactionId,
                'original_invoice_uuid' => $originalFatoraInvoice->invoice_uuid,
                'original_invoice_amount' => $originalTransaction->final_total,
                'return_reason' => $returnReason,
                'qr_code' => $qrCode,
                'xml_content' => $xmlContent,
                'response_data' => $responseData,
                'status' => $isSuccessful ? 'sent' : 'rejected',
                'error_message' => $errorMessage,
                'sent_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ];
            
            if (Schema::hasColumn('fatora_invoices', 'system_invoice_number')) {
                $invoiceData['system_invoice_number'] = $invoiceNumber;
            }
            if (Schema::hasColumn('fatora_invoices', 'system_invoice_uuid')) {
                $invoiceData['system_invoice_uuid'] = $invoiceUuid;
            }

            DB::table('fatora_invoices')->insert($invoiceData);

            return [
                'success' => $isSuccessful,
                'message' => $isSuccessful ? 'تم إرسال فاتورة المرتجعات بنجاح إلى نظام الفوترة الأردني' : 'فشل إرسال فاتورة المرتجعات',
                'data' => [
                    'invoice_number' => $invoiceNumber,
                    'invoice_uuid' => $invoiceUuid,
                    'status' => $isSuccessful ? 'SUBMITTED' : 'FAILED',
                    'original_invoice' => $originalTransaction->invoice_no,
                    'raw_response' => $responseData
                ],
                'qr_code' => $qrCode,
                'invoice_number' => $invoiceNumber,
                'system_uuid' => $invoiceUuid
            ];

        } catch (Exception $e) {
            // Restore locale before returning
            setlocale(LC_NUMERIC, $previousLocale);
            
            Log::error('Fatora Credit Invoice Error: ' . $e->getMessage(), [
                'return_transaction_id' => $returnTransactionId,
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'message' => $e->getMessage(),
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * TEST FUNCTION - Send Credit Invoice using REAL data from transaction 2109
     * للاختبار - استخدام البيانات الفعلية
     */
    public function sendCreditInvoiceForTest()
    {
        // Set locale
        $previousLocale = setlocale(LC_NUMERIC, 0);
        setlocale(LC_NUMERIC, 'C');
        
        try {
            // Get REAL return transaction (2110)
            $returnTransactionId = 2110;
            $returnTransaction = DB::table('transactions')->find($returnTransactionId);
            
            // Get original transaction (2096)
            $originalTransactionId = $returnTransaction->return_parent_id;
            $originalTransaction = DB::table('transactions')->find($originalTransactionId);
            
            // Get original fatora invoice
            $originalFatoraInvoice = DB::table('fatora_invoices')
                ->where('transaction_id', $originalTransactionId)
                ->first();

            // dd($originalFatoraInvoice);
            // Get return items
            $items = $this->getReturnItems($returnTransactionId);
            
            // Check if original invoice was actually sent successfully
            if (!$originalFatoraInvoice || !$originalFatoraInvoice->system_invoice_uuid) {
                dd([
                    'error' => 'الفاتورة الأصلية غير مرسلة بنجاح لنظام JoFotara',
                    'solution' => 'يجب إرسال الفاتورة الأصلية (ID: ' . $originalTransactionId . ') أولاً',
                    'original_transaction_id' => $originalTransactionId,
                    'original_invoice_no' => $originalTransaction->invoice_no,
                    'fatora_record_exists' => $originalFatoraInvoice ? 'نعم' : 'لا',
                    'system_uuid' => $originalFatoraInvoice->system_invoice_uuid ?? 'فارغ',
                    'system_number' => $originalFatoraInvoice->system_invoice_number ?? 'فارغ',
                    'status' => $originalFatoraInvoice->status ?? 'غير معروف',
                    'sent_at' => $originalFatoraInvoice->sent_at ?? 'لم يُرسل',
                    'instructions' => [
                        '1. افتح الفاتورة الأصلية رقم: ' . $originalTransaction->invoice_no,
                        '2. اضغط "إرسال للفوترة الأردنية"',
                        '3. انتظر حتى تنجح',
                        '4. ثم أرسل المرتجعات'
                    ]
                ]);
            }
            
            dd([
                'test_stage' => 'Data Retrieved',
                'return_transaction' => [
                    'id' => $returnTransaction->id,
                    'invoice_no' => $returnTransaction->invoice_no,
                    'final_total' => $returnTransaction->final_total
                ],
                'original_transaction' => [
                    'id' => $originalTransaction->id,
                    'invoice_no' => $originalTransaction->invoice_no,
                    'final_total' => $originalTransaction->final_total
                ],
                'original_fatora' => [
                    'invoice_uuid' => $originalFatoraInvoice->invoice_uuid ?? null,
                    'system_invoice_uuid' => $originalFatoraInvoice->system_invoice_uuid ?? null,
                    'system_invoice_number' => $originalFatoraInvoice->system_invoice_number ?? null
                ],
                'items_count' => $items->count(),
                'items_details' => $items->map(function($item) {
                    return [
                        'product_name' => $item->product_name,
                        'quantity_returned' => $item->quantity_returned ?? $item->quantity,
                        'unit_price' => $item->unit_price,
                        'unit_price_before_discount' => $item->unit_price_before_discount,
                        'item_tax' => $item->item_tax
                    ];
                })->toArray()
            ]);

        } catch (Exception $e) {
            setlocale(LC_NUMERIC, $previousLocale);
            
            dd([
                'test' => 'Credit Invoice Test - Data Retrieval',
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }

    /**
     * Clean numeric value - remove commas and ensure it's a valid float
     * 
     * @param mixed $value
     * @return float
     */
    protected function cleanNumericValue($value)
    {
        if (is_null($value) || $value === '') {
            return 0.0;
        }
        
        // Convert to string first
        $strValue = (string)$value;
        
        // Replace comma with dot (in case of European format)
        $strValue = str_replace(',', '.', $strValue);
        
        // Remove any character that is not digit, dot, or minus
        $cleaned = preg_replace('/[^0-9.\-]/', '', $strValue);
        
        // Handle multiple dots (keep only first one)
        $parts = explode('.', $cleaned);
        if (count($parts) > 2) {
            $cleaned = $parts[0] . '.' . implode('', array_slice($parts, 1));
        }
        
        return (float)$cleaned;
    }

    /**
     * Get return transaction items
     * 
     * Note: For sell_return transactions, items might be stored differently
     * Try both methods: from return transaction itself OR from original with quantity_returned
     */
    protected function getReturnItems($returnTransactionId)
    {
        // Method 1: Get items directly from return transaction
        $items = DB::table('transaction_sell_lines as tsl')
            ->leftJoin('products as p', 'tsl.product_id', '=', 'p.id')
            ->leftJoin('variations as v', 'tsl.variation_id', '=', 'v.id')
            ->leftJoin('tax_rates as tr', 'tsl.tax_id', '=', 'tr.id')
            ->where('tsl.transaction_id', $returnTransactionId)
            ->where('tsl.quantity', '>', 0)
            ->select([
                'tsl.*',
                'p.name as product_name',
                'v.name as variation_name',
                'tr.amount as tax_percent'
            ])
            ->get();
        
        // If no items found, try Method 2: Get from original transaction using quantity_returned
        if ($items->isEmpty()) {
            // Get return transaction to find parent
            $returnTransaction = DB::table('transactions')
                ->where('id', $returnTransactionId)
                ->where('type', 'sell_return')
                ->first();
            
            if ($returnTransaction && $returnTransaction->return_parent_id) {
                $items = DB::table('transaction_sell_lines as tsl')
                    ->leftJoin('products as p', 'tsl.product_id', '=', 'p.id')
                    ->leftJoin('variations as v', 'tsl.variation_id', '=', 'v.id')
                    ->leftJoin('tax_rates as tr', 'tsl.tax_id', '=', 'tr.id')
                    ->where('tsl.transaction_id', $returnTransaction->return_parent_id)
                    ->where('tsl.quantity_returned', '>', 0)
                    ->select([
                        'tsl.*',
                        'p.name as product_name',
                        'v.name as variation_name',
                        'tr.amount as tax_percent',
                        DB::raw('tsl.quantity_returned as quantity') // Use quantity_returned as quantity
                    ])
                    ->get();
            }
        }
        
        return $items;
    }

    /**
     * Get invoice from JoFotara system by UUID
     * 
     * @param string $invoiceUuid The UUID of the invoice in JoFotara system
     * @return array|null
     */
    public function getInvoiceFromJoFotara($invoiceUuid)
    {
        try {
            $url = "https://backend.jofotara.gov.jo/core/invoices/{$invoiceUuid}";
            
            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HTTPHEADER => [
                    'Client-Id: ' . $this->settings->client_id,
                    'Secret-Key: ' . $this->settings->secret_key,
                    'Content-Type: application/json',
                ],
            ]);
            
            $response = curl_exec($ch);
            $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error = curl_error($ch);
            curl_close($ch);
            
            if ($error) {
                Log::error('Error fetching invoice from JoFotara: ' . $error);
                return null;
            }
            
            if ($statusCode !== 200) {
                Log::warning('Failed to fetch invoice from JoFotara', [
                    'status_code' => $statusCode,
                    'response' => $response
                ]);
                return null;
            }
            
            $result = json_decode($response, true);
            
            if (!$result) {
                Log::error('Invalid JSON response from JoFotara');
                return null;
            }
            
            return $result;
            
        } catch (\Exception $e) {
            Log::error('Exception fetching invoice from JoFotara: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Get list of invoices from JoFotara system with filters
     * 
     * @param array $filters Filters: ['from_date' => 'Y-m-d', 'to_date' => 'Y-m-d', 'status' => 'SUBMITTED', 'page' => 1, 'limit' => 50]
     * @return array|null
     */
    public function getInvoicesListFromJoFotara($filters = [])
    {
        
        try {
            $queryParams = [];
            
            // Add filters if provided
            if (isset($filters['from_date'])) {
                $queryParams['fromDate'] = $filters['from_date'];
            }
            if (isset($filters['to_date'])) {
                $queryParams['toDate'] = $filters['to_date'];
            }
            if (isset($filters['status'])) {
                $queryParams['status'] = $filters['status']; // SUBMITTED, REJECTED, etc.
            }
            if (isset($filters['page'])) {
                $queryParams['page'] = $filters['page'];
            }
            if (isset($filters['limit'])) {
                $queryParams['limit'] = $filters['limit'];
            }
            
            $url = "https://backend.jofotara.gov.jo/core/invoices";
            if (!empty($queryParams)) {
                $url .= '?' . http_build_query($queryParams);
            }
            
            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HTTPHEADER => [
                    'Client-Id: ' . $this->settings->client_id,
                    'Secret-Key: ' . $this->settings->secret_key,
                    'Content-Type: application/json',
                ],
            ]);
            
            $response = curl_exec($ch);
           
            $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error = curl_error($ch);
            curl_close($ch);
            
            if ($error) {
                Log::error('Error fetching invoices list from JoFotara: ' . $error);
                return null;
            }
            
            if ($statusCode !== 200) {
                Log::warning('Failed to fetch invoices list from JoFotara', [
                    'status_code' => $statusCode,
                    'response' => $response
                ]);
                return null;
            }
            
            $result = json_decode($response, true);
            dd($result);
            if (!$result) {
                Log::error('Invalid JSON response from JoFotara');
                return null;
            }
            
            return $result;
            
        } catch (\Exception $e) {
            dd($e);
            Log::error('Exception fetching invoices list from JoFotara: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Sync invoice status from JoFotara to local database
     * 
     * @param int $transactionId
     * @return bool
     */
    public function syncInvoiceStatus($transactionId)
    {
        try {
            // Get local invoice record
            $localInvoice = DB::table('fatora_invoices')
                ->where('transaction_id', $transactionId)
                ->where('business_id', $this->businessId)
                ->first();
            
            if (!$localInvoice) {
                Log::warning('Local invoice not found for transaction: ' . $transactionId);
                return false;
            }
            
            // Get UUID from JoFotara (system_invoice_uuid or invoice_uuid)
            $invoiceUuid = $localInvoice->system_invoice_uuid ?? $localInvoice->invoice_uuid;
            
            if (!$invoiceUuid) {
                Log::warning('No UUID found for invoice');
                return false;
            }
            
            // Fetch from JoFotara
            $remoteInvoice = $this->getInvoiceFromJoFotara($invoiceUuid);
            
            if (!$remoteInvoice) {
                return false;
            }
            
            // Update local status
            $updateData = [];
            
            if (isset($remoteInvoice['EINV_STATUS'])) {
                $newStatus = strtolower($remoteInvoice['EINV_STATUS']);
                if ($newStatus === 'submitted') {
                    $updateData['status'] = 'sent';
                } elseif ($newStatus === 'rejected') {
                    $updateData['status'] = 'rejected';
                }
            }
            
            if (isset($remoteInvoice['EINV_QR']) && empty($localInvoice->qr_code)) {
                $updateData['qr_code'] = $remoteInvoice['EINV_QR'];
            }
            
            if (!empty($updateData)) {
                $updateData['updated_at'] = now();
                
                DB::table('fatora_invoices')
                    ->where('id', $localInvoice->id)
                    ->update($updateData);
                
                Log::info('Invoice status synced successfully', [
                    'transaction_id' => $transactionId,
                    'updates' => $updateData
                ]);
                
                return true;
            }
            
            return true;
            
        } catch (\Exception $e) {
            Log::error('Exception syncing invoice status: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Get all invoices from local database with optional filters
     * 
     * @param array $filters ['status' => 'sent', 'from_date' => 'Y-m-d', 'to_date' => 'Y-m-d']
     * @return \Illuminate\Support\Collection
     */
    public function getLocalInvoices($filters = [])
    {
        $query = DB::table('fatora_invoices as fi')
            ->leftJoin('transactions as t', 'fi.transaction_id', '=', 't.id')
            ->where('fi.business_id', $this->businessId)
            ->select([
                'fi.*',
                't.invoice_no',
                't.transaction_date',
                't.final_total',
                't.type as transaction_type'
            ]);
        
        // Apply filters
        if (isset($filters['status'])) {
            $query->where('fi.status', $filters['status']);
        }
        
        if (isset($filters['from_date'])) {
            $query->whereDate('fi.sent_at', '>=', $filters['from_date']);
        }
        
        if (isset($filters['to_date'])) {
            $query->whereDate('fi.sent_at', '<=', $filters['to_date']);
        }
        
        if (isset($filters['invoice_type'])) {
            $query->where('fi.invoice_type', $filters['invoice_type']);
        }
        
        if (isset($filters['is_credit_invoice'])) {
            $query->where('fi.is_credit_invoice', $filters['is_credit_invoice']);
        }
        
        return $query->orderBy('fi.sent_at', 'desc')->get();
    }

    /**
     * Import missing invoice from JoFotara to local database
     * Useful when invoice exists in JoFotara but not in local DB
     * 
     * @param int $transactionId Transaction ID to import invoice for
     * @return array Result with success status and message
     */
    public function importInvoiceFromJoFotara($transactionId)
    {
        try {
            // Check if already exists locally
            $existing = DB::table('fatora_invoices')
                ->where('transaction_id', $transactionId)
                ->where('business_id', $this->businessId)
                ->first();
            
            if ($existing) {
                return [
                    'success' => false,
                    'message' => 'الفاتورة موجودة محلياً بالفعل',
                    'data' => $existing
                ];
            }
            
            // Get transaction details
            $transaction = DB::table('transactions')
                ->where('id', $transactionId)
                ->where('business_id', $this->businessId)
                ->first();
            
            if (!$transaction) {
                return [
                    'success' => false,
                    'message' => 'المعاملة غير موجودة'
                ];
            }
            
            // Try to find invoice in JoFotara by invoice number
            // First, we need to search by date range and invoice number
            $transactionDate = date('Y-m-d', strtotime($transaction->transaction_date));
            
            $filters = [
                'from_date' => $transactionDate,
                'to_date' => $transactionDate,
                'limit' => 100
            ];
            
            $remoteInvoices = $this->getInvoicesListFromJoFotara($filters);
            
            if (!$remoteInvoices) {
                return [
                    'success' => false,
                    'message' => 'فشل الاتصال بـ JoFotara'
                ];
            }
            
            // Search for matching invoice by invoice number
            $matchedInvoice = null;
            foreach ($remoteInvoices as $remoteInv) {
                // Match by invoice number or transaction total
                if (isset($remoteInv['EINV_NUM']) && $remoteInv['EINV_NUM'] == $transaction->invoice_no) {
                    $matchedInvoice = $remoteInv;
                    break;
                }
            }
            
            if (!$matchedInvoice) {
                return [
                    'success' => false,
                    'message' => 'الفاتورة غير موجودة في نظام JoFotara أو رقم الفاتورة غير متطابق'
                ];
            }
            
            // Create local record
            $invoiceData = [
                'transaction_id' => $transactionId,
                'business_id' => $this->businessId,
                   'location_id' => $this->locationId, 
                'invoice_uuid' => $matchedInvoice['EINV_INV_UUID'] ?? null,
                'invoice_type' => 'income',
                'payment_method' => 'cash',
                'qr_code' => $matchedInvoice['EINV_QR'] ?? null,
                'xml_content' => $matchedInvoice['EINV_SINGED_INVOICE'] ?? null,
                'response_data' => json_encode($matchedInvoice),
                'status' => ($matchedInvoice['EINV_STATUS'] == 'SUBMITTED') ? 'sent' : 'rejected',
                'system_invoice_number' => $matchedInvoice['EINV_NUM'] ?? null,
                'system_invoice_uuid' => $matchedInvoice['EINV_INV_UUID'] ?? null,
                'sent_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ];
            
            $insertedId = DB::table('fatora_invoices')->insertGetId($invoiceData);
            
            Log::info('Invoice imported from JoFotara', [
                'transaction_id' => $transactionId,
                'invoice_id' => $insertedId,
                'system_uuid' => $matchedInvoice['EINV_INV_UUID']
            ]);
            
            return [
                'success' => true,
                'message' => 'تم استيراد الفاتورة من JoFotara بنجاح',
                'data' => [
                    'local_id' => $insertedId,
                    'system_invoice_number' => $matchedInvoice['EINV_NUM'],
                    'system_invoice_uuid' => $matchedInvoice['EINV_INV_UUID'],
                    'qr_code' => $matchedInvoice['EINV_QR']
                ]
            ];
            
        } catch (\Exception $e) {
            Log::error('Error importing invoice from JoFotara: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'خطأ: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Bulk sync - Import all missing invoices from a date range
     * 
     * @param string $fromDate Start date (Y-m-d)
     * @param string $toDate End date (Y-m-d)
     * @return array Statistics about synced invoices
     */
    public function bulkSyncInvoices($fromDate, $toDate)
    {
        try {
            $stats = [
                'total_checked' => 0,
                'imported' => 0,
                'already_exist' => 0,
                'failed' => 0,
                'details' => []
            ];
            
            // Get all transactions in date range that might need syncing
            $transactions = DB::table('transactions')
                ->where('business_id', $this->businessId)
                ->whereIn('type', ['sell', 'sell_return'])
                ->whereDate('transaction_date', '>=', $fromDate)
                ->whereDate('transaction_date', '<=', $toDate)
                ->get();
            
            $stats['total_checked'] = $transactions->count();
            
            foreach ($transactions as $transaction) {
                // Check if already in local DB
                $existsLocally = DB::table('fatora_invoices')
                    ->where('transaction_id', $transaction->id)
                    ->where('business_id', $this->businessId)
                    ->exists();
                
                if ($existsLocally) {
                    $stats['already_exist']++;
                    continue;
                }
                
                // Try to import
                $result = $this->importInvoiceFromJoFotara($transaction->id);
                
                if ($result['success']) {
                    $stats['imported']++;
                    $stats['details'][] = [
                        'transaction_id' => $transaction->id,
                        'invoice_no' => $transaction->invoice_no,
                        'status' => 'imported',
                        'message' => $result['message']
                    ];
                } else {
                    $stats['failed']++;
                    $stats['details'][] = [
                        'transaction_id' => $transaction->id,
                        'invoice_no' => $transaction->invoice_no,
                        'status' => 'failed',
                        'message' => $result['message']
                    ];
                }
                
                // Add small delay to avoid overwhelming the API
                usleep(500000); // 0.5 second delay
            }
            
            return [
                'success' => true,
                'message' => "تمت المزامنة: {$stats['imported']} فاتورة مستوردة، {$stats['already_exist']} موجودة مسبقاً، {$stats['failed']} فشلت",
                'stats' => $stats
            ];
            
        } catch (\Exception $e) {
            Log::error('Error in bulk sync: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'خطأ في المزامنة الشاملة: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Find missing local invoices by comparing with JoFotara
     * Returns list of transactions that exist in JoFotara but not locally
     * 
     * @param string $fromDate
     * @param string $toDate
     * @return array
     */
    public function findMissingInvoices($fromDate, $toDate)
    {
        try {
            $missing = [];
            
            // Get remote invoices from JoFotara
            $remoteInvoices = $this->getInvoicesListFromJoFotara([
                'from_date' => $fromDate,
                'to_date' => $toDate,
                'limit' => 500
            ]);
            
            if (!$remoteInvoices) {
                return [
                    'success' => false,
                    'message' => 'فشل جلب الفواتير من JoFotara'
                ];
            }
            
            foreach ($remoteInvoices as $remoteInv) {
                $invoiceUuid = $remoteInv['EINV_INV_UUID'] ?? null;
                
                if (!$invoiceUuid) {
                    continue;
                }
                
                // Check if exists locally
                $existsLocally = DB::table('fatora_invoices')
                    ->where('business_id', $this->businessId)
                    ->where(function($q) use ($invoiceUuid) {
                        $q->where('system_invoice_uuid', $invoiceUuid)
                          ->orWhere('invoice_uuid', $invoiceUuid);
                    })
                    ->exists();
                
                if (!$existsLocally) {
                    $missing[] = [
                        'system_invoice_number' => $remoteInv['EINV_NUM'] ?? 'N/A',
                        'system_invoice_uuid' => $invoiceUuid,
                        'status' => $remoteInv['EINV_STATUS'] ?? 'UNKNOWN',
                        'qr_code' => isset($remoteInv['EINV_QR']) ? 'يوجد' : 'لا يوجد'
                    ];
                }
            }
            
            return [
                'success' => true,
                'missing_count' => count($missing),
                'missing_invoices' => $missing,
                'message' => 'تم العثور على ' . count($missing) . ' فاتورة ناقصة'
            ];
            
        } catch (\Exception $e) {
            Log::error('Error finding missing invoices: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'خطأ: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Enhanced sendCreditInvoice with auto-import if original invoice is missing
     * This is a wrapper that tries to import missing invoice before sending credit invoice
     */
    public function sendCreditInvoiceWithAutoImport($returnTransactionId, $returnReason = 'إرجاع بضاعة')
    {
        try {
            // Get return transaction
            $returnTransaction = DB::table('transactions')
                ->where('id', $returnTransactionId)
                ->where('type', 'sell_return')
                ->first();
            
            if (!$returnTransaction) {
                return [
                    'success' => false,
                    'message' => 'فاتورة المرتجعات غير موجودة'
                ];
            }
            
            $originalTransactionId = $returnTransaction->return_parent_id;
            
            if (!$originalTransactionId) {
                return [
                    'success' => false,
                    'message' => 'الفاتورة الأصلية غير محددة'
                ];
            }
            
            // Check if original invoice exists locally
            $originalExists = DB::table('fatora_invoices')
                ->where('transaction_id', $originalTransactionId)
                ->whereIn('status', ['sent', 'accepted'])
                ->exists();
            
            // If not exists, try to import it
            if (!$originalExists) {
                Log::info('Original invoice not found locally, attempting to import from JoFotara', [
                    'original_transaction_id' => $originalTransactionId
                ]);
                
                $importResult = $this->importInvoiceFromJoFotara($originalTransactionId);
                
                if (!$importResult['success']) {
                    return [
                        'success' => false,
                        'message' => 'الفاتورة الأصلية غير موجودة محلياً ولم نتمكن من استيرادها من JoFotara: ' . $importResult['message'],
                        'suggestion' => 'يرجى التأكد من أن الفاتورة الأصلية مرسلة لنظام JoFotara أولاً'
                    ];
                }
                
                Log::info('Original invoice imported successfully, proceeding with credit invoice');
            }
            
            // Now send credit invoice normally
            return $this->sendCreditInvoice($returnTransactionId, $returnReason);
            
        } catch (\Exception $e) {
            Log::error('Error in sendCreditInvoiceWithAutoImport: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'خطأ: ' . $e->getMessage()
            ];
        }
    }
}

