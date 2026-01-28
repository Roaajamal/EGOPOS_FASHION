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
        $url = $settings->service_url ?? 'https://gprs.mepspay.com:6680/apex.smartpos.ecr/EcrComInterface.svc';
        
        $xml = $this->buildPaymentXml($amount, $invoiceNumber, $settings);
        
        try {
            // ⏱️ timeout 10 ثواني فقط!
            $response = Http::timeout(120) // ← هنا 10 ثواني
                ->withHeaders([
                    'Content-Type' => 'text/xml; charset=utf-8',
                    'SOAPAction' => '"http://tempuri.org/IEcrComInterface/Sale"',
                ])
                ->withOptions(['verify' => false])
                ->withBody($xml, 'text/xml')
                ->post($url);
            
            $parsed = $this->parseMepsResponse($response->body());
            
            // النتيجة: true/false فقط
            $isSuccess = ($parsed['pos_resp_code'] === '00' || $parsed['pos_resp_code'] === '000');
            
            Log::info($isSuccess ? '✅ تمت العملية بنجاح' : '❌ فشلت العملية', [
                'amount' => $amount,
                'invoice' => $invoiceNumber,
                'business_id' => $settings->business_id,
                'location_id' => $settings->location_id,
                'response_code' => $parsed['pos_resp_code']
            ]);
            
            return $isSuccess;
            
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            // ⏱️ timeout حدث بعد 10 ثواني
            Log::error('⏱️ انتهت مهلة 10 ثواني للاتصال بـ MEPS', [
                'business_id' => $settings->business_id,
                'location_id' => $settings->location_id
            ]);
            return false;
            
        } catch (\Exception $e) {
            Log::error('❌ خطأ في الاتصال بـ MEPS', [
                'error' => $e->getMessage(),
                'business_id' => $settings->business_id,
                'location_id' => $settings->location_id
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
        $result = ['pos_resp_code' => null];
        
        try {
            $xml = @simplexml_load_string($xmlString);
            if (!$xml) return $result;
            
            $allElements = $xml->xpath('//*');
            foreach ($allElements as $element) {
                if ($element->getName() === 'PosRespCode') {
                    $result['pos_resp_code'] = trim((string)$element);
                    break;
                }
            }
        } catch (\Exception $e) {
            // تجاهل خطأ التحليل
        }
        
        return $result;
    }
}