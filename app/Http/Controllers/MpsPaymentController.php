<?php

namespace App\Http\Controllers;

use App\Models\EcrIntegrationSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MpsPaymentController extends Controller
{
    /**
     * دالة مباشرة - تستقبل المبلغ من الـ URL
     * الآن تستقبل رقم الفاتورة، business_id، و location_id أيضاً
     */
    public function directPayment($amount, Request $request)
    {
        Log::info('💰 بدء دفع مباشر', [
            'amount' => $amount,
            'invoice' => $request->input('invoice'),
            'business_id' => $request->input('business_id'),
            'location_id' => $request->input('location_id')
        ]);
        
        // تحقق من المبلغ
        if (!is_numeric($amount) || $amount < 0.01 || $amount > 999999.99) {
            Log::error('❌ المبلغ غير صالح', ['amount' => $amount]);
            return response()->json(['success' => false, 'message' => 'المبلغ غير صالح'], 400);
        }
        
        // التحقق من وجود business_id و location_id
        $businessId = $request->input('business_id');
        $locationId = $request->input('location_id');
        
        if (!$businessId || !$locationId) {
            Log::error('❌ business_id أو location_id مفقود', [
                'business_id' => $businessId,
                'location_id' => $locationId
            ]);
            return response()->json(['success' => false, 'message' => 'المعلومات المطلوبة غير مكتملة'], 400);
        }
        
        // جلب رقم الفاتورة من الـ Request أو استخدم المبلغ كمرجع
        $invoiceNumber = $request->input('invoice', 'AMT-' . $amount . '-' . time());
        
        try {
            // جلب إعدادات MEPS حسب business_id و location_id
            $settings = EcrIntegrationSetting::where('is_enabled', 1)
                ->where('provider_type', 'mps')
                ->where('business_id', $businessId)
                ->where('business_location_id', $locationId)
                ->first();
            
            if (!$settings) {
                Log::error('❌ إعدادات MEPS غير موجودة لهذا الموقع', [
                    'business_id' => $businessId,
                    'location_id' => $locationId
                ]);
                return response()->json([
                    'success' => false, 
                    'message' => 'إعدادات الدفع غير موجودة لهذا الموقع'
                ], 404);
            }
            
            if (empty($settings->merchant_id) || empty($settings->terminal_id) || empty($settings->secure_key)) {
                Log::error('❌ بيانات التاجر غير مكتملة', [
                    'business_id' => $businessId,
                    'location_id' => $locationId
                ]);
                return response()->json([
                    'success' => false, 
                    'message' => 'بيانات التاجر غير مكتملة'
                ], 400);
            }
            
            // إرسال الدفع لـ MEPS
            $result = $this->sendToMeps($amount, $invoiceNumber, $settings);
            
            return response()->json([
                'success' => $result,
                'message' => $result ? 'تمت عملية الدفع بنجاح' : 'فشلت عملية الدفع',
                'invoice' => $invoiceNumber
            ]);
            
        } catch (\Exception $e) {
            Log::error('❌ خطأ في الدفع', [
                'error' => $e->getMessage(),
                'business_id' => $businessId,
                'location_id' => $locationId,
                'amount' => $amount
            ]);
            return response()->json([
                'success' => false, 
                'message' => 'حدث خطأ أثناء عملية الدفع'
            ], 500);
        }
    }
    
    /**
     * نسخة بديلة للدالة مع معاملات مختلفة (اختياري)
     */
    public function directPaymentV2(Request $request)
    {
        // التحقق من وجود جميع المعاملات المطلوبة
        $validated = $request->validate([
            'amount' => 'required|numeric|min:0.01|max:999999.99',
            'business_id' => 'required|integer',
            'location_id' => 'required|integer',
            'invoice' => 'sometimes|string'
        ]);
        
        Log::info('💰 بدء دفع مباشر V2', $validated);
        
        $amount = $validated['amount'];
        $businessId = $validated['business_id'];
        $locationId = $validated['location_id'];
        $invoiceNumber = $validated['invoice'] ?? 'AMT-' . $amount . '-' . time();
        
        try {
            // جلب إعدادات MEPS حسب business_id و location_id
            $settings = EcrIntegrationSetting::where('is_enabled', 1)
                ->where('provider_type', 'mps')
                ->where('business_id', $businessId)
             ->where('business_location_id', $locationId)
                ->first();
            
            if (!$settings) {
                Log::error('❌ إعدادات MEPS غير موجودة', [
                    'business_id' => $businessId,
                    'location_id' => $locationId
                ]);
                return response()->json([
                    'success' => false, 
                    'message' => 'إعدادات الدفع غير موجودة'
                ], 404);
            }
            
            if (empty($settings->merchant_id) || empty($settings->terminal_id) || empty($settings->secure_key)) {
                Log::error('❌ بيانات التاجر غير مكتملة');
                return response()->json([
                    'success' => false, 
                    'message' => 'بيانات التاجر غير مكتملة'
                ], 400);
            }
            
            // إرسال الدفع لـ MEPS
            $result = $this->sendToMeps($amount, $invoiceNumber, $settings);
            
            return response()->json([
                'success' => $result,
                'message' => $result ? 'تمت عملية الدفع بنجاح' : 'فشلت عملية الدفع',
                'invoice' => $invoiceNumber,
                'transaction_time' => now()->toDateTimeString()
            ]);
            
        } catch (\Exception $e) {
            Log::error('❌ خطأ في الدفع V2', [
                'error' => $e->getMessage(),
                'business_id' => $businessId,
                'location_id' => $locationId,
                'amount' => $amount
            ]);
            return response()->json([
                'success' => false, 
                'message' => 'حدث خطأ أثناء عملية الدفع'
            ], 500);
        }
    }
    
    /**
     * إرسال المبلغ لـ MEPS
     */
private function sendToMeps($amount, $invoiceNumber, $settings)
{
    $url = $settings->service_url ?? 'https://gprs.mepspay.com:6610/v100/EcrComInterface.svc';
    
    $xml = $this->buildPaymentXml($amount, $invoiceNumber, $settings);
    
    Log::info('📤 إرسال طلب إلى MEPS', [
        'url' => $url,
        'xml' => $xml
    ]);
    
    try {
        $response = Http::timeout(120)
            ->withHeaders([
                'Content-Type' => 'text/xml; charset=utf-8',
                'SOAPAction' => 'http://tempuri.org/IEcrComInterface/Sale',
            ])
            ->withOptions(['verify' => false])
            ->withBody($xml, 'text/xml')
            ->post($url);
        
        $responseBody = $response->body();
        
        Log::info('📥 رد MEPS', [
            'status' => $response->status(),
            'body' => $responseBody
        ]);
        
        // تحليل الرد
        $parsed = $this->parseMepsResponse($responseBody);
        
        // تسجيل جميع التفاصيل
        Log::info('🔍 تحليل رد MEPS', $parsed);
        
        // تقييم النجاح بناءً على:
        // 1. WebResponseStatus = "Success"
        // 2. PosRespCode = "00" أو "000" (وليس "CC")
        // 3. PosReceipt لا يحتوي على "TRANSACTION FAILED"
        
        $isSuccess = (
            ($parsed['web_response_status'] ?? '') === 'Success' &&
            in_array($parsed['pos_resp_code'] ?? '', ['00', '000'], true) &&
            !str_contains($parsed['pos_receipt'] ?? '', 'TRANSACTION FAILED')
        );
        
        if ($isSuccess) {
            Log::info('✅ تمت العملية بنجاح', [
                'amount' => $amount,
                'invoice' => $invoiceNumber,
                'response_code' => $parsed['pos_resp_code'],
                'invoice_number' => $parsed['pos_invoice_number'] ?? '',
                'rrn' => $parsed['pos_rrn'] ?? ''
            ]);
        } else {
            Log::error('❌ فشلت العملية', [
                'amount' => $amount,
                'invoice' => $invoiceNumber,
                'response_code' => $parsed['pos_resp_code'] ?? 'غير معروف',
                'reason' => $parsed['pos_resp_text'] ?? ($parsed['web_response_error_desc'] ?? 'فشل غير معروف'),
                'receipt_preview' => substr($parsed['pos_receipt'] ?? '', 0, 200)
            ]);
        }
        
        return $isSuccess;
        
    } catch (\Illuminate\Http\Client\ConnectionException $e) {
        Log::error('⏱️ انتهت مهلة الاتصال بـ MEPS', [
            'error' => $e->getMessage()
        ]);
        return false;
        
    } catch (\Exception $e) {
        Log::error('❌ خطأ في الاتصال بـ MEPS', [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);
        return false;
    }
}
    /**
     * بناء XML للدفع
     */
    private function buildPaymentXml($amount, $invoiceNumber, $settings)
    {
        $amountFormatted = number_format($amount, 2, '.', '');
        
        return '<?xml version="1.0" encoding="utf-8"?>
<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:tem="http://tempuri.org/" xmlns:ns="http://schemas.datacontract.org/2004/07/">
   <soapenv:Header/>
   <soapenv:Body>
      <tem:Sale>
         <tem:webReq>
            <ns:Config>
               <ns:EcrCurrencyCode>' . ($settings->currency_code ?? '400') . '</ns:EcrCurrencyCode>
               <ns:EcrTillerFullName>' . ($settings->merchant_name ?? 'Merchant') . '</ns:EcrTillerFullName>
               <ns:EcrTillerUserName>POS_USER</ns:EcrTillerUserName>
               <ns:IntegratorName>POS_SYSTEM</ns:IntegratorName>
               <ns:MerchantSecureKey>' . $settings->secure_key . '</ns:MerchantSecureKey>
               <ns:Mid>' . $settings->merchant_id . '</ns:Mid>
               <ns:Tid>' . $settings->terminal_id . '</ns:Tid>
               <ns:Tenant>' . $settings->merchant_name . '</ns:Tenant>
            </ns:Config>
            <ns:EcrAmount>' . $amountFormatted . '</ns:EcrAmount>
            <ns:Printer>
               <ns:EnablePrintPosReceipt>' . ($settings->print_receipt ? '1' : '0') . '</ns:EnablePrintPosReceipt>
               <ns:EnablePrintReceiptNote>0</ns:EnablePrintReceiptNote>
               <ns:InvoiceNumber>' . $invoiceNumber . '</ns:InvoiceNumber>
               <ns:PrinterWidth>' . ($settings->print_width ?? '40') . '</ns:PrinterWidth>
               <ns:ReferenceNumber>REF-' . $amount . '</ns:ReferenceNumber>
            </ns:Printer>
         </tem:webReq>
      </tem:Sale>
   </soapenv:Body>
</soapenv:Envelope>';
    }
    
    /**
     * تحليل رد MEPS
     */
private function parseMepsResponse($xmlString)
{
    $result = [
        'pos_resp_code' => null,
        'pos_resp_text' => null,
        'pos_invoice_number' => null,
        'pos_rrn' => null,
        'pos_auth_code' => null,
        'pos_receipt' => null,
        'web_response_status' => null,
        'web_response_error_desc' => null,
        'raw_response' => $xmlString
    ];
    
    try {
        // تحميل XML
        $xml = simplexml_load_string($xmlString);
        
        if ($xml === false) {
            // إذا فشل التحليل، استخرج البيانات مباشرة من النص
            return $this->extractDataFromText($xmlString);
        }
        
        // تسجيل namespaces الموجودة فعلياً
        $namespaces = $xml->getNamespaces(true);
        Log::info('🔍 Namespaces الموجودة', $namespaces);
        
        // البحث في namespace 'a'
        if (isset($namespaces['a'])) {
            // تسجيل namespaces للاستخدام في xpath
            $xml->registerXPathNamespace('a', $namespaces['a']);
        }
        
        // البحث عن جميع الحقول المهمة
        $fieldsToSearch = [
            'PosRespCode' => 'pos_resp_code',
            'PosRespText' => 'pos_resp_text',
            'PosInvoiceNumber' => 'pos_invoice_number',
            'PosRRN' => 'pos_rrn',
            'PosAuthCode' => 'pos_auth_code',
            'PosReceipt' => 'pos_receipt',
            'WebResponseStatus' => 'web_response_status',
            'WebResponseErrorDesc' => 'web_response_error_desc'
        ];
        
        foreach ($fieldsToSearch as $fieldName => $resultKey) {
            // البحث في namespace 'a'
            $elements = $xml->xpath("//a:{$fieldName}");
            
            if (!empty($elements)) {
                $result[$resultKey] = trim((string)$elements[0]);
            } else {
                // البحث بدون namespace
                $elements = $xml->xpath("//{$fieldName}");
                if (!empty($elements)) {
                    $result[$resultKey] = trim((string)$elements[0]);
                }
            }
        }
        
        // إذا لم نجد PosRespCode، نبحث بشكل أكثر شمولاً
        if (empty($result['pos_resp_code'])) {
            $allElements = $xml->xpath('//*');
            foreach ($allElements as $element) {
                $name = $element->getName();
                if (stripos($name, 'respcode') !== false || stripos($name, 'resp_code') !== false) {
                    $result['pos_resp_code'] = trim((string)$element);
                    break;
                }
            }
        }
        
    } catch (\Exception $e) {
        Log::error('❌ خطأ في تحليل رد MEPS', [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);
        // استخراج البيانات من النص الخام كبديل
        $result = array_merge($result, $this->extractDataFromText($xmlString));
    }
    
    return $result;
}

private function extractDataFromText($text)
{
    $data = [];
    
    // استخدام regex لاستخراج البيانات
    $patterns = [
        'pos_resp_code' => '/<a:PosRespCode[^>]*>(.*?)<\/a:PosRespCode>|<PosRespCode[^>]*>(.*?)<\/PosRespCode>/i',
        'pos_invoice_number' => '/<a:PosInvoiceNumber[^>]*>(.*?)<\/a:PosInvoiceNumber>|<PosInvoiceNumber[^>]*>(.*?)<\/PosInvoiceNumber>/i',
        'pos_rrn' => '/<a:PosRRN[^>]*>(.*?)<\/a:PosRRN>|<PosRRN[^>]*>(.*?)<\/PosRRN>/i',
        'pos_receipt' => '/<a:PosReceipt[^>]*>(.*?)<\/a:PosReceipt>|<PosReceipt[^>]*>(.*?)<\/PosReceipt>/is',
        'web_response_status' => '/<a:WebResponseStatus[^>]*>(.*?)<\/a:WebResponseStatus>|<WebResponseStatus[^>]*>(.*?)<\/WebResponseStatus>/i'
    ];
    
    foreach ($patterns as $key => $pattern) {
        if (preg_match($pattern, $text, $matches)) {
            // نأخذ أول مجموعة غير فارغة
            for ($i = 1; $i < count($matches); $i++) {
                if (!empty($matches[$i])) {
                    $data[$key] = trim($matches[$i]);
                    break;
                }
            }
        }
    }
    
    // تنظيف نص الإيصال إذا كان موجوداً
    if (isset($data['pos_receipt'])) {
        $data['pos_receipt'] = html_entity_decode($data['pos_receipt']);
        $data['pos_receipt'] = preg_replace('/\s+/', ' ', $data['pos_receipt']);
    }
    
    return $data;
}

private function extractFromRawResponse($response)
{
    $result = [
        'pos_resp_code' => null,
        'approval_code' => null,
        'rrn' => null
    ];
    
    // البحث عن PosRespCode في النص الخام
    if (preg_match('/<PosRespCode[^>]*>(.*?)<\/PosRespCode>/i', $response, $matches)) {
        $result['pos_resp_code'] = trim($matches[1]);
    } elseif (preg_match('/"PosRespCode":"([^"]+)"/', $response, $matches)) {
        $result['pos_resp_code'] = trim($matches[1]);
    }
    
    // البحث عن ApprovalCode في النص الخام
    if (preg_match('/<ApprovalCode[^>]*>(.*?)<\/ApprovalCode>/i', $response, $matches)) {
        $result['approval_code'] = trim($matches[1]);
    } elseif (preg_match('/"ApprovalCode":"([^"]+)"/', $response, $matches)) {
        $result['approval_code'] = trim($matches[1]);
    }
    
    // البحث عن RRN في النص الخام
    if (preg_match('/<RRN[^>]*>(.*?)<\/RRN>/i', $response, $matches)) {
        $result['rrn'] = trim($matches[1]);
    } elseif (preg_match('/"RRN":"([^"]+)"/', $response, $matches)) {
        $result['rrn'] = trim($matches[1]);
    }
    
    return $result;
}
    }
