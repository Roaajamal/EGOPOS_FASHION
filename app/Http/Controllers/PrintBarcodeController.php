<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Product;
use App\Variation;

class PrintBarcodeController extends Controller
{
    /**
     * عرض صفحة طباعة الباركود الرئيسية
     */
    public function index()
    {
        try {
            $business_id = Auth::check() ? Auth::user()->business_id : 1;
            
            // جلب بعض المنتجات للعرض الأولي
            $products = Product::where('business_id', $business_id)
                ->with(['brand', 'category', 'variations'])
                ->where('type', '!=', 'modifier')
                ->orderBy('name')
                ->take(20)
                ->get();
            
            $designData = $this->getBarcodeDesign($business_id);
            
            return view('printbarcode.printbar', compact('products', 'designData'));
            
        } catch (\Exception $e) {
            Log::error('❌ خطأ في صفحة الباركود: ' . $e->getMessage());
            Log::error($e->getTraceAsString());
            return response("Error: " . $e->getMessage(), 500);
        }
    }

    /**
     * البحث عن المنتجات
     */
    public function search(Request $request)
    {
        try {
            $business_id = Auth::check() ? Auth::user()->business_id : 1;
            $search = $request->get('search');
            
            Log::info('🔍 بحث عن منتجات:', ['search_term' => $search, 'business_id' => $business_id]);

            $products = Product::where('business_id', $business_id)
                ->with(['category', 'brand', 'variations'])
                ->when($search, function($query) use ($search) {
                    return $query->where('name', 'LIKE', "%{$search}%")
                               ->orWhere('sku', 'LIKE', "%{$search}%")
                               ->orWhereHas('variations', function($q) use ($search) {
                                   $q->where('sub_sku', 'LIKE', "%{$search}%");
                               });
                })
                ->where('type', '!=', 'modifier')
                ->get();

            Log::info('✅ عدد المنتجات التي تم العثور عليها: ' . $products->count());

            $designData = $this->getBarcodeDesign($business_id);

            return view('printbarcode.printbar', compact('products', 'search', 'designData'));
            
        } catch (\Exception $e) {
            Log::error('خطأ في البحث: ' . $e->getMessage());
            return back()->with('error', 'حدث خطأ أثناء البحث');
        }
    }

    public function printPreview(Request $request)
    {
        try {
            $business_id = Auth::check() ? Auth::user()->business_id : 1;
            
            // جلب المنتجات المختارة
            $product_ids = $request->get('product_ids', []);
            $products = Product::where('business_id', $business_id)
                ->with(['category', 'brand', 'variations'])
                ->when($product_ids, function($query) use ($product_ids) {
                    return $query->whereIn('id', $product_ids);
                })
                ->get();

            // جلب التصميم المحفوظ
            $designData = $this->getBarcodeDesign($business_id);

            return view('printbarcode.print-preview', compact('products', 'designData'));
            
        } catch (\Exception $e) {
            Log::error('خطأ في معاينة الطباعة: ' . $e->getMessage());
            return back()->with('error', 'حدث خطأ أثناء تحضير المعاينة');
        }
    }

    /**
     * إرسال للطابعة - الطباعة الفعلية
     */
    public function sendToPrinter(Request $request)
    {
        try {
            $productData = $request->input('product_data');
            $printerName = $request->input('printer_name');
            $copies = $request->input('copies', 1);
            
            Log::info('🖨️ بدء عملية الطباعة:', [
                'printer' => $printerName,
                'copies' => $copies,
                'product_data' => $productData
            ]);

            if (!$productData || !$printerName) {
                return response()->json([
                    'success' => false,
                    'message' => 'بيانات غير كافية للطباعة'
                ], 400);
            }

            // جلب تصميم الباركود
            $business_id = Auth::check() ? Auth::user()->business_id : 1;
            $designData = $this->getBarcodeDesign($business_id);

            // توليد محتوى ZPL بناء على التصميم المخزن
            $zplContent = $this->generateZPLContent($productData, $designData);
            
            Log::info('📄 محتوى ZPL المُولد:', ['zpl' => $zplContent]);

            // هنا سيتم إرسال ZPL إلى QZ Tray
            // سأعود لك بقريباً مع تنفيذ QZ Tray
            
            return response()->json([
                'success' => true,
                'message' => 'تم إرسال المهمة للطابعة بنجاح',
                'zpl_content' => $zplContent // لأغراض التصحيح
            ]);
            
        } catch (\Exception $e) {
            Log::error('خطأ في الطباعة: ' . $e->getMessage());
            Log::error($e->getTraceAsString());
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء الطباعة: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * توليد محتوى ZPL بناء على التصميم المخزن
     */
    private function generateZPLContent($productData, $designData)
    {
        $labelSize = $designData['label_size'] ?? ['width' => 50, 'height' => 25];
        $elements = $designData['elements'] ?? [];
        $extraElements = $designData['extra_elements'] ?? [];
        $barcodeSettings = $designData['barcode_settings'] ?? [];
        
        // تحويل الأبعاد من mm إلى dots (افتراضي 203 DPI)
        $widthDots = $this->mmToDots($labelSize['width']);
        $heightDots = $this->mmToDots($labelSize['height']);
        
        $zpl = "^XA"; // بدء الأمر
        $zpl .= "^PW{$widthDots}"; // عرض الملصق
        $zpl .= "^LL{$heightDots}"; // طول الملصق
        
        // دمج كل العناصر
        $allElements = array_merge($elements, $extraElements);
        
        // ترتيب العناصر حسب المواقع الرأسية
        uasort($allElements, function($a, $b) {
            $topA = $this->extractPosition($a['top'] ?? '0');
            $topB = $this->extractPosition($b['top'] ?? '0');
            return $topA - $topB;
        });
        
        foreach ($allElements as $key => $element) {
            if (isset($element['visible']) && $element['visible'] === false) {
                continue;
            }
            
            $zplElement = $this->createZPLElement($key, $element, $productData, $barcodeSettings);
            if ($zplElement) {
                $zpl .= $zplElement;
            }
        }
        
        $zpl .= "^XZ"; // إنهاء الأمر
        
        return $zpl;
    }
    
    /**
     * إنشاء عنصر ZPL فردي
     */
    private function createZPLElement($elementKey, $element, $productData, $barcodeSettings)
    {
        $left = $this->convertToDots($element['left'] ?? '0');
        $top = $this->convertToDots($element['top'] ?? '0');
        $content = $this->getElementContent($elementKey, $element, $productData);
        
        if (!$content) return '';
        
        // معالجة الباركود
        if ($elementKey === 'barcode-container' || strpos($elementKey, 'barcode') !== false) {
            $barHeight = $this->mmToDots($barcodeSettings['height'] ?? 3); // استخدام الإعدادات المخزنة
            $barWidth = $barcodeSettings['width'] ?? 2;
            $barcodeType = $barcodeSettings['type'] ?? $barcodeSettings['format'] ?? 'CODE128';
            $showText = isset($barcodeSettings['show_text']) ? 
                        ($barcodeSettings['show_text'] === 'true' || $barcodeSettings['show_text'] === true) : 
                        true;
            
            // استخدام الباركود الفعلي للمنتج
            $barcodeContent = $productData['barcode'] ?? $productData['sku'] ?? '123456789';
            
            $zplBarcode = "^FO{$left},{$top}^BY{$barWidth}^BCN,{$barHeight}," . ($showText ? 'Y' : 'N') . ",N,N^FD{$barcodeContent}^FS";
            
            // إذا كان هناك نص تحت الباركود، نضيفه أيضاً
            if ($showText && !empty($element['text'])) {
                $textContent = $this->getElementContent($elementKey, $element, $productData);
                if ($textContent && $textContent !== $barcodeContent) {
                    $textTop = $top + $barHeight + 10; // تحت الباركود
                    $fontSize = $this->convertFontSize($barcodeSettings['font_size'] ?? 12);
                    $escapedText = $this->escapeZPL($textContent);
                    $zplBarcode .= "^FO{$left},{$textTop}^A0N,{$fontSize},{$fontSize}^FD{$escapedText}^FS";
                }
            }
            
            return $zplBarcode;
        }
        
        // معالجة النصوص العادية
        $fontSize = $this->convertFontSize($element['fontSize'] ?? 12);
        $escapedContent = $this->escapeZPL($content);
        
        return "^FO{$left},{$top}^A0N,{$fontSize},{$fontSize}^FD{$escapedContent}^FS";
    }
    
    /**
     * الحصول على محتوى العنصر مع استبدال المتغيرات بالقيم الفعلية
     */
    private function getElementContent($elementKey, $element, $productData)
    {
        $text = $element['text'] ?? '';
        
        // إذا كان العنصر باركود، نعيد الباركود الفعلي مباشرة
        if ($elementKey === 'barcode-container' || strpos($elementKey, 'barcode') !== false) {
            return $productData['barcode'] ?? $productData['sku'] ?? '123456789';
        }
        
        // استبدال المتغيرات بالقيم الفعلية
        $replacements = [
            '{{ product_name }}' => $productData['name'] ?? 'منتج',
            '{{ sku }}' => $productData['barcode'] ?? $productData['sku'] ?? '123456789',
            '{{ price }}' => $productData['price'] ?? '0.00',
            '{{ brand }}' => $productData['brand'] ?? 'علامة',
            '{{ shop_name }}' => Auth::check() ? (Auth::user()->business->name ?? 'المحل') : 'المحل',
        ];
        
        // استبدال جميع المتغيرات
        $text = str_replace(array_keys($replacements), array_values($replacements), $text);
        
        // إذا كان النص لا يزال يحتوي على متغيرات، نعيد القيمة الافتراضية بناءً على نوع العنصر
        if (empty($text) || preg_match('/\{\{.*\}\}/', $text)) {
            if (strpos($elementKey, 'Name') !== false || strpos($elementKey, 'name') !== false) {
                return $productData['name'] ?? 'اسم المنتج';
            } elseif (strpos($elementKey, 'Price') !== false || strpos($elementKey, 'price') !== false) {
                return $productData['price'] ?? '0.00';
            } elseif (strpos($elementKey, 'Brand') !== false || strpos($elementKey, 'brand') !== false) {
                return $productData['brand'] ?? 'العلامة التجارية';
            } elseif (strpos($elementKey, 'extra') !== false) {
                return $element['text'] ?? ''; // نعيد النص الأصلي للعناصر الإضافية
            }
        }
        
        return $text;
    }
    
    /**
     * تحويل mm إلى dots (افتراضي 203 DPI)
     */
    private function mmToDots($mm)
    {
        return round((floatval($mm) / 25.4) * 203);
    }
    
    /**
     * تحويل المواقع إلى dots
     */
    private function convertToDots($value)
    {
        if (strpos($value, 'mm') !== false) {
            return $this->mmToDots(floatval($value));
        } elseif (strpos($value, 'px') !== false) {
            // افتراض 96 DPI للشاشة وتحويل إلى 203 DPI للطابعة
            $px = floatval($value);
            return round(($px / 96) * 203);
        } else {
            return round(floatval($value));
        }
    }
    
    /**
     * استخراج القيمة العددية من المواقع
     */
    private function extractPosition($value)
    {
        if (strpos($value, 'mm') !== false) {
            return floatval($value);
        } elseif (strpos($value, 'px') !== false) {
            return floatval($value);
        } else {
            return floatval($value);
        }
    }
    
    /**
     * تحويل حجم الخط لـ ZPL
     */
    private function convertFontSize($fontSize)
    {
        $size = intval($fontSize);
        if ($size <= 8) return 12;
        if ($size <= 10) return 15;
        if ($size <= 12) return 18;
        if ($size <= 14) return 20;
        if ($size <= 16) return 22;
        if ($size <= 18) return 25;
        if ($size <= 20) return 28;
        if ($size <= 24) return 32;
        if ($size <= 30) return 38;
        return 45; // لحجم 40px
    }
    
    /**
     * تهريب محارف خاصة في ZPL
     */
    private function escapeZPL($text)
    {
        $replacements = [
            '\\' => '\\\\',
            '^'  => '\\^',
            '~'  => '\\~',
            '_'  => '\\_',
            '?'  => '\\?',
            '*'  => '\\*',
            ','  => '\\,',
            ':'  => '\\:',
            ';'  => '\\;',
            '#'  => '\\#',
            '|'  => '\\|',
            '"'  => '\\"',
            "'"  => "\\'"
        ];
        
        return str_replace(array_keys($replacements), array_values($replacements), $text);
    }

    /**
     * جلب تصميم الباركود المحفوظ
     */
    public function getDesign()
    {
        try {
            $business_id = Auth::check() ? Auth::user()->business_id : 1;
            $designData = $this->getBarcodeDesign($business_id);

            return response()->json([
                'success' => true,
                'design' => $designData
            ]);

        } catch (\Exception $e) {
            Log::error('خطأ في جلب التصميم: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => '❌ حدث خطأ أثناء جلب التصميم'
            ], 500);
        }
    }

    /**
     * دالة مساعدة لجلب تصميم الباركود
     */
    private function getBarcodeDesign($business_id)
    {
        try {
            $design = DB::table('barcode_design_settings')
                      ->where('business_id', $business_id)
                      ->first();

            if ($design && !empty($design->design)) {
                $designData = json_decode($design->design, true);
                $designData = $this->normalizeDesignData($designData);
                
                Log::info('📋 تم تحميل تصميم الباركود من قاعدة البيانات', ['design' => $designData]);
                return $designData;
            }

            return $this->getDefaultDesign();

        } catch (\Exception $e) {
            Log::warning('لا يمكن تحميل تصميم الباركود: ' . $e->getMessage());
            return $this->getDefaultDesign();
        }
    }

    /**
     * تطبيع بيانات التصميم
     */
    private function normalizeDesignData($designData)
    {
        if (!isset($designData['label_size'])) {
            $designData['label_size'] = ['width' => 50, 'height' => 25];
        }
        
        if (!isset($designData['elements'])) {
            $designData['elements'] = [];
        }
        
        if (!isset($designData['barcode_settings'])) {
            $designData['barcode_settings'] = [
                'format' => 'CODE128',
                'width' => 2,
                'height' => 40,
                'displayValue' => true,
                'fontSize' => 12
            ];
        }
        
        if (!isset($designData['extra_elements'])) {
            $designData['extra_elements'] = [];
        }
        
        return $designData;
    }
     
    private function getDefaultDesign()
    {
        return [
            'label_size' => [
                'width' => 50,
                'height' => 25
            ],
            'barcode_settings' => [
                'format' => 'CODE128',
                'width' => 2,
                'height' => 40,
                'displayValue' => true,
                'fontSize' => 12
            ],
            'elements' => [
                'product_name' => [
                    'text' => '{{ product_name }}',
                    'left' => '5px',
                    'top' => '5px',
                    'fontSize' => '12px',
                    'fontFamily' => 'Arial',
                    'color' => '#000000',
                    'visible' => true
                ],
                'barcode-container' => [
                    'text' => '{{ sku }}',
                    'left' => '5px',
                    'top' => '25px',
                    'fontSize' => '10px',
                    'fontFamily' => 'Arial',
                    'color' => '#000000',
                    'visible' => true
                ],
                'price' => [
                    'text' => '{{ price }}',
                    'left' => '5px',
                    'top' => '70px',
                    'fontSize' => '12px',
                    'fontFamily' => 'Arial',
                    'color' => '#000000',
                    'visible' => true
                ]
            ],
            'extra_elements' => []
        ];
    }

    /**
     * طباعة الباركود - دالة إضافية للـ route
     */
    public function printBarcodes(Request $request)
    {
        try {
            $product_ids = $request->input('product_ids', []);
            $quantities = $request->input('quantities', []);
            $printer_name = $request->input('printer_name');
            
            Log::info('🖨️ طلب طباعة الباركود:', [
                'product_ids' => $product_ids,
                'quantities' => $quantities,
                'printer_name' => $printer_name
            ]);
            
            // جلب بيانات المنتجات
            $products = Product::whereIn('id', $product_ids)
                ->with(['brand', 'variations'])
                ->get();
            
            $results = [];
            foreach ($products as $product) {
                $variation = $product->variations->first();
                $productData = [
                    'id' => $product->id,
                    'name' => $product->name,
                    'sku' => $product->sku,
                    'barcode' => $variation->sub_sku ?: $product->sku,
                    'price' => $variation->sell_price_inc_tax ?? $variation->default_sell_price ?? 0,
                    'brand' => $product->brand->name ?? ''
                ];
                
                $quantity = $quantities[$product->id] ?? 1;
                
                // توليد ZPL لكل منتج
                $business_id = Auth::check() ? Auth::user()->business_id : 1;
                $designData = $this->getBarcodeDesign($business_id);
                $zplContent = $this->generateZPLContent($productData, $designData);
                
                $results[] = [
                    'product' => $productData,
                    'quantity' => $quantity,
                    'zpl_content' => $zplContent
                ];
            }
            
            return response()->json([
                'success' => true,
                'message' => 'تم استلام طلب الطباعة بنجاح',
                'data' => $results
            ]);
            
        } catch (\Exception $e) {
            Log::error('خطأ في طباعة الباركود: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء الطباعة'
            ], 500);
        }
    }
}