<?php

namespace App\Http\Controllers;

use App\Business;
use App\Product;
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
        $business = session('business') ?: (Auth::check() ? Business::find(Auth::user()->business_id) : null);
        $raw = $business ? (is_object($business) ? ($business->custom_labels ?? null) : ($business['custom_labels'] ?? null)) : null;
        $custom_labels = is_array($raw) ? $raw : (is_string($raw) ? json_decode($raw, true) : []);
        $custom_labels = is_array($custom_labels) ? $custom_labels : [];
        $product_custom_fields = [];
        if (! empty($custom_labels['product']) && is_array($custom_labels['product'])) {
            foreach ($custom_labels['product'] as $key => $label) {
                if ($label === '' || $label === null) {
                    continue;
                }
                if (preg_match('/custom_field_(\d+)/', (string) $key, $m)) {
                    $product_custom_fields['cf' . $m[1]] = $label;
                }
            }
        }
        return view('barcode_designer.barcode-design', compact('product_custom_fields'));
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

    /**
     * بحث منتجات (متغير أو لديها توليفات لون/مقاس) لاختيارها في مصمم الباركود.
     */
    public function searchProducts(Request $request)
    {
        try {
            $businessId = Auth::check() ? Auth::user()->business_id : 1;
            $q = $request->get('q', '');

            $query = Product::where('business_id', $businessId)
                ->where(function ($qry) {
                    $qry->where('type', 'variable')
                        ->orWhere(function ($sq) {
                            $sq->whereNotNull('size_color_combinations')
                               ->where('size_color_combinations', '!=', '')
                               ->where('size_color_combinations', '!=', '[]');
                        });
                })
                ->where('type', '!=', 'modifier');

            if ($q !== '') {
                $query->where(function ($q2) use ($q) {
                    $q2->where('name', 'like', "%{$q}%")
                       ->orWhere('sku', 'like', "%{$q}%");
                });
            }

            $products = $query->orderBy('name')->limit(50)->get(['id', 'name', 'sku', 'type']);

            return response()->json(['success' => true, 'products' => $products]);
        } catch (\Exception $e) {
            Log::error('BarcodeDesignController@searchProducts: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * جلب توليفات اللون/المقاس لمنتج (مجموعة حسب اللون لطباعة كل مقاس لوحده).
     */
    public function getProductVariations($product_id)
    {
        try {
            $businessId = Auth::check() ? Auth::user()->business_id : 1;

            $product = Product::where('business_id', $businessId)->where('id', $product_id)->first();

            if (! $product) {
                return response()->json(['success' => false, 'message' => 'المنتج غير موجود'], 404);
            }

            $combinations = $product->size_color_combinations;
            $by_color = null;

            if (is_array($combinations) && ! empty($combinations['by_color'])) {
                $by_color = $combinations['by_color'];
            }

            if (empty($by_color) && $product->type == 'variable') {
                $variations = $product->variations()->with('product_variation')->get();
                $flat = [];
                foreach ($variations as $v) {
                    $flat[] = [
                        'variation_id' => $v->id,
                        'sub_sku' => $v->sub_sku,
                        'value' => $v->name,
                        'sell_price_inc_tax' => $v->sell_price_inc_tax,
                        'label' => $v->product_variation ? $v->product_variation->name . ' - ' . $v->name : $v->name,
                    ];
                }
                return response()->json([
                    'success' => true,
                    'product' => ['id' => $product->id, 'name' => $product->name, 'sku' => $product->sku],
                    'by_color' => null,
                    'combinations' => $flat,
                ]);
            }

            return response()->json([
                'success' => true,
                'product' => ['id' => $product->id, 'name' => $product->name, 'sku' => $product->sku],
                'by_color' => $by_color,
                'combinations' => null,
            ]);
        } catch (\Exception $e) {
            Log::error('BarcodeDesignController@getProductVariations: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
}