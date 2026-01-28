<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MepsTesterController extends Controller
{
    /**
     * الاختبار الكامل النهائي - يعمل بـ GET
     */
    public function fullTest()
    {
        Log::info('🚀 بدء الاختبار الكامل لـ MEPS');
        
        $result = [
            'success' => false,
            'message' => '',
            'timestamp' => now()->toDateTimeString(),
            'steps' => []
        ];
        
        try {
            // ====================================
            // الخطوة 1: اختبار الاتصال الأساسي
            // ====================================
            $result['steps']['connection'] = $this->testConnection();
            
            if (!$result['steps']['connection']['success']) {
                $result['message'] = 'فشل الاتصال بالسيرفر';
                return response()->json($result);
            }
            
            // ====================================
            // الخطوة 2: إرسال طلب SOAP كامل
            // ====================================
            $soapResult = $this->sendCompleteSoapRequest();
            $result['steps']['soap_request'] = $soapResult;
            
            // ====================================
            // الخطوة 3: تحليل النتيجة
            // ====================================
            if ($soapResult['success']) {
                $parsed = $this->parseSoapResponse($soapResult['raw_response']);
                $result['steps']['parsed_response'] = $parsed;
                
                $result['success'] = $parsed['is_success'] ?? false;
                $result['message'] = $parsed['message'] ?? 'تم الإرسال بنجاح';
                $result['transaction_details'] = $parsed['fields'] ?? [];
                
                if ($result['success']) {
                    Log::info('✅ MEPS Test Successful', $parsed);
                } else {
                    Log::warning('⚠️ MEPS Test Failed', $parsed);
                }
            } else {
                $result['message'] = $soapResult['error'] ?? 'فشل طلب SOAP';
                Log::error('❌ MEPS SOAP Failed', $soapResult);
            }
            
        } catch (\Exception $e) {
            $result['message'] = 'خطأ غير متوقع: ' . $e->getMessage();
            Log::error('💥 MEPS Test Exception', ['error' => $e->getMessage()]);
        }
        
        return response()->json($result);
    }
    
    /**
     * اختبار الاتصال بالسيرفر
     */
    private function testConnection()
    {
        $host = 'gprs.mepspay.com';
        $port = 6680;
        
        $start = microtime(true);
        $connection = @fsockopen($host, $port, $errno, $errstr, 5);
        $time = round((microtime(true) - $start) * 1000, 2);
        
        if (is_resource($connection)) {
            fclose($connection);
            return [
                'success' => true,
                'message' => '✅ الاتصال ناجح',
                'host' => $host,
                'port' => $port,
                'response_time_ms' => $time
            ];
        }
        
        return [
            'success' => false,
            'message' => "❌ فشل الاتصال: $errstr",
            'host' => $host,
            'port' => $port,
            'error_code' => $errno,
            'error_string' => $errstr
        ];
    }
    
    /**
     * إرسال طلب SOAP كامل
     */
    private function sendCompleteSoapRequest()
    {
        $url = "https://gprs.mepspay.com:6680/apex.smartpos.ecr/EcrComInterface.svc";
        
        // XML كامل وصحيح - بنفس البيانات التي عملت في Postman
        $xml = '<?xml version="1.0" encoding="utf-8"?>
<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:tem="http://tempuri.org/" xmlns:ns="http://schemas.datacontract.org/2004/07/">
   <soapenv:Header/>
   <soapenv:Body>
      <tem:Sale>
         <tem:webReq>
            <ns:Config>
               <ns:EcrCurrencyCode>400</ns:EcrCurrencyCode>
               <ns:EcrTillerFullName>Test User</ns:EcrTillerFullName>
               <ns:EcrTillerUserName>TEST</ns:EcrTillerUserName>
               <ns:IntegratorName>TEST_SYSTEM</ns:IntegratorName>
               <ns:MerchantSecureKey>0123456789ABCDEF0123456789ABCDEF</ns:MerchantSecureKey>
               <ns:Mid>888888880000000</ns:Mid>
               <ns:Tid>15012026</ns:Tid>
            </ns:Config>
            <ns:EcrAmount>5.00</ns:EcrAmount>
            <ns:Printer>
               <ns:EnablePrintPosReceipt>1</ns:EnablePrintPosReceipt>
               <ns:EnablePrintReceiptNote>0</ns:EnablePrintReceiptNote>
               <ns:InvoiceNumber>101050</ns:InvoiceNumber>
               <ns:PrinterWidth>40</ns:PrinterWidth>
               <ns:ReferenceNumber>REF101050</ns:ReferenceNumber>
            </ns:Printer>
         </tem:webReq>
      </tem:Sale>
   </soapenv:Body>
</soapenv:Envelope>';
        
        try {
            $startTime = microtime(true);
            
            $response = Http::withHeaders([
                'Content-Type' => 'text/xml; charset=utf-8',
                'SOAPAction' => '"http://tempuri.org/IEcrComInterface/Sale"',
            ])
            ->withOptions([
                'verify' => false, // تجاهل SSL للتجربة
                'timeout' => 30,
                'curl' => [
                    CURLOPT_SSL_VERIFYPEER => false,
                    CURLOPT_SSL_VERIFYHOST => false,
                    CURLOPT_SSLVERSION => CURL_SSLVERSION_TLSv1_2,
                ]
            ])
            ->withBody($xml, 'text/xml')
            ->post($url);
            
            $responseTime = round((microtime(true) - $startTime) * 1000, 2);
            
            return [
                'success' => $response->successful(),
                'http_status' => $response->status(),
                'response_time_ms' => $responseTime,
                'raw_response' => $response->body(),
                'url' => $url,
                'xml_sent_length' => strlen($xml)
            ];
            
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'error_type' => get_class($e),
                'url' => $url
            ];
        }
    }
    
    /**
     * تحليل رد SOAP
     */
    private function parseSoapResponse($xmlString)
    {
        $result = [
            'is_success' => false,
            'message' => 'لم يتم تحليل الرد',
            'fields' => []
        ];
        
        try {
            // بسيط للغاية - البحث عن النصوص المهمة
            $xmlString = trim($xmlString);
            
            // 1. تحقق من وجود أخطاء واضحة
            if (stripos($xmlString, '<faultstring>') !== false) {
                preg_match('/<faultstring>(.*?)<\/faultstring>/i', $xmlString, $matches);
                $result['message'] = 'خطأ في SOAP: ' . ($matches[1] ?? 'Unknown');
                return $result;
            }
            
            // 2. تحويل XML إذا كان صالحاً
            $xml = @simplexml_load_string($xmlString);
            if (!$xml) {
                // حاول إزالة namespaces للمساعدة
                $cleanXml = preg_replace('/<(\/?)(\w+):/', '<$1', $xmlString);
                $xml = @simplexml_load_string($cleanXml);
            }
            
            if (!$xml) {
                // رد غير XML - ربما خطأ من السيرفر
                $result['message'] = 'الرد ليس XML صالحاً';
                $result['raw_preview'] = substr($xmlString, 0, 200);
                return $result;
            }
            
            // 3. البحث عن أي عنصر PosRespCode (الأهم)
            $respCode = '';
            $respText = '';
            
            // طريقة مباشرة للبحث
            $allElements = $xml->xpath('//*');
            foreach ($allElements as $element) {
                $name = $element->getName();
                $value = trim((string)$element);
                
                if ($name === 'PosRespCode' && !empty($value)) {
                    $respCode = $value;
                    $result['fields']['PosRespCode'] = $value;
                }
                
                if ($name === 'PosRespText' && !empty($value)) {
                    $respText = $value;
                    $result['fields']['PosRespText'] = $value;
                }
                
                // جمع بقية الحقول المهمة
                $importantFields = ['PosAmount', 'PosAuthCode', 'PosBatchNumber', 'Rrn', 'ApprovalCode', 'WebResponseStatus'];
                if (in_array($name, $importantFields) && !empty($value)) {
                    $result['fields'][$name] = $value;
                }
            }
            
            // 4. تحديد النجاح
            if (!empty($respCode)) {
                $result['is_success'] = ($respCode === '00' || $respCode === '000' || $respCode === '0');
                $result['message'] = !empty($respText) ? $respText : ($result['is_success'] ? 'معاملة ناجحة' : 'معاملة مرفوضة');
            } elseif (!empty($respText)) {
                $result['message'] = $respText;
            }
            
            // 5. إذا لم نجد الحقول المعتادة، نبحث عن أي رسالة
            if (empty($result['fields'])) {
                // جلب كل النصوص من XML
                $allText = strip_tags($xmlString);
                $allText = preg_replace('/\s+/', ' ', $allText);
                $result['message'] = 'الرد العام: ' . substr($allText, 0, 100);
            }
            
        } catch (\Exception $e) {
            $result['message'] = 'خطأ في التحليل: ' . $e->getMessage();
            Log::warning('MEPS Parse Error', ['error' => $e->getMessage()]);
        }
        
        return $result;
    }
    
    /**
     * نسخة مختصرة للاختبار السريع
     */
    public function quickTest()
    {
        try {
            $url = "https://gprs.mepspay.com:6680/apex.smartpos.ecr/EcrComInterface.svc";
            
            $xml = '<?xml version="1.0" encoding="utf-8"?>
<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:tem="http://tempuri.org/">
   <soapenv:Header/>
   <soapenv:Body>
      <tem:Sale>
         <tem:webReq>
            <test>QUICK_TEST</test>
         </tem:webReq>
      </tem:Sale>
   </soapenv:Body>
</soapenv:Envelope>';
            
            $response = Http::withHeaders([
                'Content-Type' => 'text/xml; charset=utf-8',
                'SOAPAction' => '"http://tempuri.org/IEcrComInterface/Sale"',
            ])
            ->withOptions(['verify' => false, 'timeout' => 10])
            ->withBody($xml, 'text/xml')
            ->post($url);
            
            return response()->json([
                'success' => $response->successful(),
                'status' => $response->status(),
                'body_preview' => substr($response->body(), 0, 300)
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }
}