<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class BarcodeDesignController extends Controller
{
    /**
     * عرض صفحة مصمم الباركود
     */
    public function index()
    {
        Log::info('🎨 مصمم الباركود تم الوصول إليه من قبل المستخدم: ' . Auth::id());
        return view('barcode_designer.barcode-design');
    }

    /**
     * حفظ تصميم الباركود
     */
    public function saveDesign(Request $request)
    {
        Log::info('=== بدء حفظ الباركود ===');
        Log::info('بيانات الطلب:', $request->all());
        
        try {
            $businessId = Auth::check() ? Auth::user()->business_id : 1;
            Log::info('معرف العمل: ' . $businessId);

            // استقبال البيانات بالشكل الصحيح
            $requestData = $request->all();
            
            $designData = [
                'label_size' => [
                    'width' => $requestData['label_size']['width'] ?? 50,
                    'height' => $requestData['label_size']['height'] ?? 25
                ],
                'elements' => $requestData['elements'] ?? [],
                'extra_elements' => $requestData['extra_elements'] ?? [],
                'barcode_settings' => $requestData['barcode_settings'] ?? [],
                'saved_at' => now()->toDateTimeString(),
                'user_id' => Auth::id()
            ];

            Log::info('💾 بيانات التصميم المحضرة:', $designData);

            // حفظ أو تحديث التصميم
            $existing = DB::table('barcode_design_settings')
                        ->where('business_id', $businessId)
                        ->first();

            if ($existing) {
                Log::info('🔄 تحديث التصميم الموجود');
                DB::table('barcode_design_settings')
                    ->where('business_id', $businessId)
                    ->update([
                        'design' => json_encode($designData, JSON_UNESCAPED_UNICODE),
                        'updated_at' => now()
                    ]);
            } else {
                Log::info('🆕 إنشاء تصميم جديد');
                DB::table('barcode_design_settings')->insert([
                    'business_id' => $businessId,
                    'design' => json_encode($designData, JSON_UNESCAPED_UNICODE),
                    'created_at' => now(),
                    'updated_at' => now()
                ]);
            }

            Log::info('=== نجاح حفظ الباركود ===');

            return response()->json([
                'success' => true,
                'message' => '✅ تم حفظ التصميم بنجاح',
                'business_id' => $businessId
            ]);

        } catch (\Exception $e) {
            Log::error('=== خطأ في حفظ الباركود ===');
            Log::error('الخطأ: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => '❌ حدث خطأ أثناء الحفظ: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * تحميل تصميم الباركود
     */
    public function loadDesign(Request $request)
    {
        Log::info('=== بدء تحميل الباركود ===');
        
        try {
            $businessId = Auth::check() ? Auth::user()->business_id : 1;
            Log::info('جاري التحميل للعمل: ' . $businessId);

            $design = DB::table('barcode_design_settings')
                      ->where('business_id', $businessId)
                      ->first();

            if ($design && $design->design) {
                Log::info('✅ تم العثور على تصميم');
                $designData = json_decode($design->design, true);
                
                return response()->json([
                    'success' => true,
                    'design' => $designData,
                    'business_id' => $businessId
                ]);
            }

            Log::info('ℹ️ لا يوجد تصميم محفوظ');
            return response()->json([
                'success' => true,
                'message' => '⚠️ لا يوجد تصميم محفوظ',
                'design' => null
            ]);

        } catch (\Exception $e) {
            Log::error('=== خطأ في تحميل الباركود ===');
            Log::error('الخطأ: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => '❌ حدث خطأ أثناء التحميل: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * اختبار الاتصال
     */
    public function testConnection()
    {
        Log::info('🧪 اختبار اتصال الباركود');
        
        try {
            $tableExists = DB::select("SHOW TABLES LIKE 'barcode_design_settings'");
            
            return response()->json([
                'success' => true,
                'message' => '✅ النظام يعمل بشكل صحيح',
                'table_exists' => !empty($tableExists),
                'timestamp' => now()->toDateTimeString()
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => '❌ خطأ في الاتصال: ' . $e->getMessage()
            ], 500);
        }
    }
}