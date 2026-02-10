<?php

namespace App\Services;

use App\Business;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;

/**
 * خدمة مركزية للطباعة — نقطة واحدة لجميع عمليات الطباعة (باركود، فواتير، إلخ).
 * استدعاؤها من أي كونترولر يلغي تكرار المنطق ويمنع الاختلاط بين الصفحات.
 * تصميم الباركود يُحمّل ويُحفظ من هنا فقط لضمان عدم تخريب الديزاين عند كل طباعة.
 * شهادة QZ Tray للطابعة تُستدعى من هنا أيضاً.
 */
class PrintService
{
    protected $businessId;

    public function __construct($businessId = null)
    {
        $this->businessId = $businessId ?? (Auth::check() ? Auth::user()->business_id : null);
    }

    /** اسم الطابعة الثابتة للباركود — تُستخدم مباشرة عند عدم تحديد أخرى. */
    public const DEFAULT_PRINTER_NAME = 'egoprint';

    /**
     * شهادة QZ Tray للطابعة — من config/qz.php (أو env QZ_CERTIFICATE).
     * استخدمها في صفحة الطباعة لتهيئة qz.security.
     */
    public static function getQzCertificate(): string
    {
        return (string) Config::get('qz.certificate', '');
    }

    /**
     * مسار المفتاح الخاص لـ QZ (للتوقيع) — من config/qz.php.
     */
    public static function getQzPrivateKeyPath(): string
    {
        return (string) Config::get('qz.private_key_path', '');
    }

    /**
     * الحصول على الطابعة الافتراضية للباركود من إعدادات النشاط.
     * إن لم تُحفظ طابعة في الإعدادات يُعاد الاسم الثابت "egoprint" للطباعة المباشرة.
     */
    public function getDefaultBarcodePrinter(): string
    {
        if (! $this->businessId) {
            return self::DEFAULT_PRINTER_NAME;
        }
        $business = Business::find($this->businessId);
        $common = $business && $business->common_settings ? $business->common_settings : [];
        $saved = (string) ($common['default_barcode_printer'] ?? '');

        return $saved !== '' ? $saved : self::DEFAULT_PRINTER_NAME;
    }

    /**
     * بناء رابط صفحة طباعة الباركود (للمنتج أو قائمة منتجات).
     * يُستخدم لفتح نافذة الطباعة أو إعادة التوجيه دون الخلط مع الصفحة الحالية.
     *
     * @param int $productId معرف المنتج
     * @param array $options [ 'print_copies' => 1, 'print_send_mode' => 'one_by_one', 'auto_print' => true, 'default_printer' => '' ]
     * @return string الرابط الكامل لصفحة الباركود
     */
    public function getBarcodePrintUrl(int $productId, array $options = []): string
    {
        $print_copies = (int) ($options['print_copies'] ?? 1);
        $print_send_mode = (string) ($options['print_send_mode'] ?? 'one_by_one');
        $auto_print = isset($options['auto_print']) ? (bool) $options['auto_print'] : true;
        $default_printer = $options['default_printer'] ?? $this->getDefaultBarcodePrinter();

        if ($print_copies < 1) {
            $print_copies = 1;
        }
        if ($print_copies > 999) {
            $print_copies = 999;
        }

        $params = [
            'product_id'       => $productId,
            'print_all'        => 1,
            'print_copies'     => $print_copies,
            'print_send_mode'  => $print_send_mode,
            'auto_print'       => $auto_print ? 1 : 0,
            'default_printer'  => $default_printer,
        ];
        $product_ids = $options['product_ids'] ?? [];
        if (! empty($product_ids) && is_array($product_ids)) {
            $params['product_ids'] = implode(',', array_map('intval', $product_ids));
        }

        $query = http_build_query($params);

        return url('print-barcode?' . $query);
    }

    /**
     * نفس الرابط ولكن كـ query string للمرور إلى صفحة أخرى (مثل قائمة المنتجات).
     * مفيد عند الرغبة في redirect ثم فتح نافذة الطباعة من الصفحة المستهدفة.
     */
    public function getBarcodePrintQueryString(int $productId, array $options = []): string
    {
        $print_copies = (int) ($options['print_copies'] ?? 1);
        $print_send_mode = (string) ($options['print_send_mode'] ?? 'one_by_one');
        $default_printer = $options['default_printer'] ?? $this->getDefaultBarcodePrinter();

        if ($print_copies < 1) {
            $print_copies = 1;
        }
        if ($print_copies > 999) {
            $print_copies = 999;
        }

        $params = [
            'print_product_id' => $productId,
            'print_all'        => 1,
            'print_copies'     => $print_copies,
            'print_send_mode'  => $print_send_mode,
            'auto_print'       => 1,
            'default_printer'  => $default_printer,
        ];
        $product_ids = $options['product_ids'] ?? [];
        if (! empty($product_ids) && is_array($product_ids)) {
            $params['product_ids'] = implode(',', array_map('intval', $product_ids));
        }

        return http_build_query($params);
    }

    /**
     * إنشاء نسخة من الخدمة لـ business_id محدد (مثلاً من الطلب).
     */
    public static function forBusiness(int $businessId): self
    {
        return new self($businessId);
    }

    // ───── تصميم الباركود (مصدر واحد — تحميل وحفظ) ─────

    /**
     * التصميم الافتراضي للباركود عند عدم وجود تصميم محفوظ.
     */
    public static function getDefaultBarcodeDesign(): array
    {
        return [
            'label_size' => [
                'width'  => 50,
                'height' => 25,
            ],
            'barcode_settings' => [
                'format'       => 'CODE128',
                'width'        => 2,
                'height'       => 40,
                'displayValue' => true,
                'show_text'    => true,
                'fontSize'     => 12,
                'type'         => 'CODE128',
            ],
            'elements' => [
                'product_name' => [
                    'text'      => '{{ product_name }}',
                    'left'      => '5px',
                    'top'       => '5px',
                    'fontSize'  => '12px',
                    'fontFamily'=> 'Arial',
                    'color'     => '#000000',
                    'visible'   => true,
                ],
                'barcode-container' => [
                    'text'      => '{{ sku }}',
                    'left'      => '5px',
                    'top'       => '25px',
                    'fontSize'  => '10px',
                    'fontFamily'=> 'Arial',
                    'color'     => '#000000',
                    'visible'   => true,
                ],
                'price' => [
                    'text'      => '{{ price }}',
                    'left'      => '5px',
                    'top'       => '70px',
                    'fontSize'  => '12px',
                    'fontFamily'=> 'Arial',
                    'color'     => '#000000',
                    'visible'   => true,
                ],
            ],
            'extra_elements' => [],
        ];
    }

    /**
     * تطبيع بيانات التصميم لضمان وجود كل المفاتيح المطلوبة وعدم تخريب الديزاين.
     */
    public static function normalizeBarcodeDesign(array $designData): array
    {
        $default = self::getDefaultBarcodeDesign();
        if (! isset($designData['label_size']) || ! is_array($designData['label_size'])) {
            $designData['label_size'] = $default['label_size'];
        } else {
            $designData['label_size'] = [
                'width'  => (int) ($designData['label_size']['width'] ?? $default['label_size']['width']),
                'height' => (int) ($designData['label_size']['height'] ?? $default['label_size']['height']),
            ];
        }
        if (! isset($designData['elements']) || ! is_array($designData['elements'])) {
            $designData['elements'] = $default['elements'];
        }
        if (! isset($designData['barcode_settings']) || ! is_array($designData['barcode_settings'])) {
            $designData['barcode_settings'] = $default['barcode_settings'];
        } else {
            $designData['barcode_settings'] = array_merge($default['barcode_settings'], $designData['barcode_settings']);
        }
        if (! isset($designData['extra_elements']) || ! is_array($designData['extra_elements'])) {
            $designData['extra_elements'] = [];
        }
        return $designData;
    }

    /**
     * جلب تصميم الباركود المحفوظ (مطبّع). إن لم يوجد محفوظ يُعاد التصميم الافتراضي.
     * استخدم هذا في صفحة الطباعة وكل مكان يحتاج تصميماً جاهزاً للطباعة.
     */
    public function getBarcodeDesign(): array
    {
        if (! $this->businessId) {
            return self::normalizeBarcodeDesign(self::getDefaultBarcodeDesign());
        }
        try {
            $row = DB::table('barcode_design_settings')
                ->where('business_id', $this->businessId)
                ->first();
            if ($row && ! empty($row->design)) {
                $decoded = json_decode($row->design, true);
                if (is_array($decoded)) {
                    return self::normalizeBarcodeDesign($decoded);
                }
            }
        } catch (\Throwable $e) {
            // عند أي خطأ نعيد الافتراضي حتى لا تتوقف الطباعة
        }
        return self::normalizeBarcodeDesign(self::getDefaultBarcodeDesign());
    }

    /**
     * جلب تصميم الباركود كما هو محفوظ فقط (بدون افتراضي). للاستخدام في مصمم الباركود لمعرفة إن وُجد حفظ.
     */
    public function getBarcodeDesignRaw(): ?array
    {
        if (! $this->businessId) {
            return null;
        }
        try {
            $row = DB::table('barcode_design_settings')
                ->where('business_id', $this->businessId)
                ->first();
            if ($row && ! empty($row->design)) {
                $decoded = json_decode($row->design, true);
                return is_array($decoded) ? self::normalizeBarcodeDesign($decoded) : null;
            }
        } catch (\Throwable $e) {
            return null;
        }
        return null;
    }

    /**
     * حفظ تصميم الباركود. يُطبّع قبل الحفظ لضمان عدم تخريب الديزاين لاحقاً.
     */
    public function saveBarcodeDesign(array $designData): bool
    {
        if (! $this->businessId) {
            return false;
        }
        $designData = self::normalizeBarcodeDesign($designData);
        $json = json_encode($designData, JSON_UNESCAPED_UNICODE);
        try {
            $exists = DB::table('barcode_design_settings')
                ->where('business_id', $this->businessId)
                ->exists();
            if ($exists) {
                DB::table('barcode_design_settings')
                    ->where('business_id', $this->businessId)
                    ->update([
                        'design'      => $json,
                        'updated_at'  => now(),
                    ]);
            } else {
                DB::table('barcode_design_settings')->insert([
                    'business_id' => $this->businessId,
                    'design'      => $json,
                    'created_at'  => now(),
                    'updated_at'  => now(),
                ]);
            }
            return true;
        } catch (\Throwable $e) {
            return false;
        }
    }
}
