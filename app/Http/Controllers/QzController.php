<?php

namespace App\Http\Controllers;

use App\Services\PrintService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class QzController extends Controller
{
    /**
     * توقيع الرسائل لـ QZ Tray — مسار المفتاح من خدمة الطباعة (config/qz).
     */
    public function sign(Request $request)
    {
        // 1. استقبال النص المراد توقيعه
        $toSign = $request->query('request');

        if (!$toSign) {
            return response()->json(['error' => 'No request data provided'], 400);
        }

        // 2. جلب مسار المفتاح من خدمة الطباعة (نفس مصدر الشهادة)
        $keyPath = PrintService::getQzPrivateKeyPath();

        // 3. التحقق من وجود ملف المفتاح
        if (!file_exists($keyPath)) {
            Log::error("QZ Signature Error: Private key file not found at $keyPath");
            return response()->json(['error' => 'Private key configuration missing'], 500);
        }

        try {
            $privateKeyContents = file_get_contents($keyPath);
            $signature = "";

            // 4. عملية التوقيع باستخدام خوارزمية SHA256
            // تأكد من تنصيب إضافة OpenSSL في PHP (موجودة افتراضياً في معظم السيرفرات)
            $success = openssl_sign($toSign, $signature, $privateKeyContents, OPENSSL_ALGO_SHA256);

            if (!$success) {
                return response()->json(['error' => 'Encryption failed'], 500);
            }

            // 5. إرجاع التوقيع بتنسيق Base64
            return response(base64_encode($signature));

        } catch (\Exception $e) {
            Log::error("QZ Signature Exception: " . $e->getMessage());
            return response()->json(['error' => 'Internal server error during signing'], 500);
        }
    }
}