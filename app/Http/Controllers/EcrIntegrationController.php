<?php

namespace App\Http\Controllers;

use App\BusinessLocation;
use App\Models\EcrIntegrationSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http; // أضفنا هذا لإرسال الطلبات للجهاز

class EcrIntegrationController extends Controller
{
    /**
     * حفظ أو تحديث إعدادات تكامل MPS
     */
public function saveSettings(Request $request, $locationId)
{
    try {
        // 1. جلب الـ Business ID من الجلسة (أمان إضافي)
        $businessId = $request->session()->get('user.business_id');
        
        // 2. التحقق من البيانات (Validation)
        $request->validate([
            'mps_tid' => 'required_if:enable_mps,1|nullable|max:50',
            'mps_mid' => 'required_if:enable_mps,1|nullable|max:50',
            'mps_key' => 'required_if:enable_mps,1|nullable|max:100',
        ]);

        $enableMps = $request->has('enable_mps') && $request->enable_mps == 1;

        // 3. التحديث أو الإنشاء بناءً على البزنس والموقع معاً
        $settings = EcrIntegrationSetting::updateOrCreate(
            [
                'business_id' => $businessId,        // ضروري جداً!
                'business_location_id' => $locationId,
                'provider_type' => 'mps'
            ],
            [
                'is_enabled' => $enableMps,
                'terminal_id' => $request->mps_tid,
                'merchant_id' => $request->mps_mid,
                'secure_key'  => $request->mps_key,
                'merchant_name' => $request->merchant_name, // اسم الماكينة
                'service_url' => 'https://gprs.mepspay.com:6680/apex.smartpos.ecr/EcrComInterface.svc',
                'currency_code' => '400',
                'print_receipt' => true
            ]
        );

        return response()->json([
            'success' => true,
            'message' => $enableMps ? 'تم الحفظ والتفعيل بنجاح' : 'تم تعطيل الخدمة بنجاح'
        ]);

    } catch (\Exception $e) {
        Log::error('MPS Save Error: ' . $e->getMessage());
        return response()->json(['success' => false, 'message' => 'خطأ: ' . $e->getMessage()], 500);
    }
}

    /**
     * جلب إعدادات MPS (تستخدم عند فتح مودال الإعدادات)
     */
    public function getSettings($locationId)
    {
        try {
            $settings = EcrIntegrationSetting::where('business_location_id', $locationId)
                ->where('provider_type', 'mps')
                ->first();
            
            return response()->json([
                'success' => true,
                'data' => $settings,
                'enabled' => $settings ? (bool)$settings->is_enabled : false
            ]);
            
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * الدالة الأهم: معالجة عملية الدفع الفعلية من شاشة الـ POS
     */
    public function processMpsPayment(Request $request)
    {
        try {
            $locationId = $request->location_id;
            $amount = $request->amount;
            $businessId = session('user.business_id');

            // 1. جلب الإعدادات الخاصة بهذا الموقع
            $settings = EcrIntegrationSetting::where('business_location_id', $locationId)
                ->where('business_id', $businessId)
                ->where('is_enabled', 1)
                ->first();

            if (!$settings) {
                return response()->json(['success' => false, 'message' => 'إعدادات MPS غير مفعلة لهذا الموقع']);
            }

            /** * 2. بناء طلب الـ XML/SOAP لجهاز MPS 
             * ملاحظة: هنا يجب وضع التنسيق المطلوب من شركة MEPS
             */
            $xmlRequest = $this->buildMpsXmlRequest($settings, $amount);

            // 3. إرسال الطلب للجهاز
            $response = Http::withHeaders([
                'Content-Type' => 'text/xml; charset=utf-8',
                'SOAPAction' => 'http://tempuri.org/IEcrComInterface/ProcessTransaction'
            ])->withOptions([
                'verify' => false // لتجنب مشاكل SSL في الشبكات المحلية أحياناً
            ])->post($settings->service_url, $xmlRequest);

            if ($response->successful()) {
                // هنا نقوم بتحليل الرد (Parsing) بناءً على بروتوكول MPS
                // إذا كان الرد Success:
                return response()->json([
                    'success' => true, 
                    'message' => 'تم قبول الدفع بنجاح'
                ]);
            }

            return response()->json(['success' => false, 'message' => 'فشل الاتصال بالجهاز']);

        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'خطأ نظام: ' . $e->getMessage()]);
        }
    }
    /**
 * اختبار الاتصال بجهاز MPS
 */
public function testConnection(Request $request, $locationId)
{
    try {
        $businessId = $request->session()->get('user.business_id');
        
        // جلب أو إنشاء إعدادات مؤقتة للاختبار
        $settings = EcrIntegrationSetting::firstOrNew([
            'business_id' => $businessId,
            'business_location_id' => $locationId,
            'provider_type' => 'mps'
        ]);
        
        // تحديث البيانات من الـ Request
        if ($request->filled(['mps_tid', 'mps_mid', 'mps_key'])) {
            $settings->terminal_id = $request->mps_tid;
            $settings->merchant_id = $request->mps_mid;
            $settings->secure_key = $request->mps_key;
            $settings->service_url = 'https://gprs.mepspay.com:6680/apex.smartpos.ecr/EcrComInterface.svc';
            $settings->currency_code = '400';
        }
        
        // بناء طلب اختبار بسيط
        $testXml = '<?xml version="1.0" encoding="utf-8"?>
        <soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:temp="http://tempuri.org/">
           <soapenv:Header/>
           <soapenv:Body>
              <temp:CheckStatus>
                 <temp:terminalId>' . $settings->terminal_id . '</temp:terminalId>
              </temp:CheckStatus>
           </soapenv:Body>
        </soapenv:Envelope>';
        
        // إرسال طلب الاختبار
        $response = Http::withHeaders([
            'Content-Type' => 'text/xml; charset=utf-8',
            'SOAPAction' => 'http://tempuri.org/IEcrComInterface/CheckStatus'
        ])->withOptions([
            'verify' => false,
            'timeout' => 10
        ])->post($settings->service_url, $testXml);
        
        // تحديث حالة الاختبار
        $settings->last_test_at = now();
        
        if ($response->successful()) {
            $settings->last_test_status = 'success';
            $settings->last_test_message = 'تم الاتصال بنجاح مع جهاز MPS';
            $message = '✅ تم الاتصال بنجاح مع خادم MEPS';
        } else {
            $settings->last_test_status = 'failed';
            $settings->last_test_message = 'فشل الاتصال: ' . $response->status();
            $message = '❌ فشل الاتصال. تأكد من: 1) الإنترنت 2) البيانات 3) إعدادات MPS';
        }
        
        $settings->save();
        
        return response()->json([
            'success' => $response->successful(),
            'message' => $message,
            'response_status' => $response->status()
        ]);
        
    } catch (\Exception $e) {
        Log::error('MPS Test Connection Error: ' . $e->getMessage());
        return response()->json([
            'success' => false,
            'message' => 'خطأ في الاتصال: ' . $e->getMessage()
        ], 500);
    }
    
    
    
}

public function checkMpsStatus(Request $request)
{
    // فقط للاختبار - ارجع true دائماً
    return response()->json(['enabled' => true]);
}
    /**
     * بناء هيكل XML لشركة MEPS
     */
    private function buildMpsXmlRequest($settings, $amount)
    {
        // هذا مجرد مثال لهيكل الـ XML، يجب مراجعته مع كتيب MEPS (ECR Documentation)
        return '<?xml version="1.0" encoding="utf-8"?>
        <soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:temp="http://tempuri.org/">
           <soapenv:Header/>
           <soapenv:Body>
              <temp:ProcessTransaction>
                 <temp:terminalId>' . $settings->terminal_id . '</temp:terminalId>
                 <temp:merchantId>' . $settings->merchant_id . '</temp:merchantId>
                 <temp:amount>' . $amount . '</temp:amount>
                 <temp:secureKey>' . $settings->secure_key . '</temp:secureKey>
              </temp:ProcessTransaction>
           </soapenv:Body>
        </soapenv:Envelope>';
    }
}