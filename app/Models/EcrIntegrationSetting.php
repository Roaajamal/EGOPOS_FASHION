<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EcrIntegrationSetting extends Model
{
    use SoftDeletes;

    /**
     * الجدول المرتبط بالموديل
     */
    protected $table = 'ecr_integration_settings';

    /**
     * الحقول المحمية من الإدخال الجماعي
     */
    protected $guarded = ['id'];

    /**
     * تحويل أنواع البيانات (Casting)
     */
    protected $casts = [
        'business_id' => 'integer',
        'business_location_id' => 'integer',
        'is_enabled' => 'boolean',
        'print_receipt' => 'boolean',
        'print_customer_copy' => 'boolean',
        'print_merchant_copy' => 'boolean',
        'enable_dcc' => 'boolean',
        'require_signature' => 'boolean',
        'timeout_seconds' => 'integer',
        'last_test_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * العلاقة مع النشاط التجاري (Business)
     */
    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class, 'business_id');
    }

    /**
     * العلاقة مع موقع النشاط (Location)
     */
    public function businessLocation(): BelongsTo
    {
        return $this->belongsTo(BusinessLocation::class, 'business_location_id');
    }

    /**
     * Scope لجلب الإعدادات المفعلة فقط
     */
    public function scopeEnabled($query)
    {
        return $query->where('is_enabled', true);
    }

    /**
     * Scope لجلب إعدادات مزود MPS فقط
     */
    public function scopeMps($query)
    {
        return $query->where('provider_type', 'mps');
    }

    /**
     * الحصول على الرابط الكامل للخدمة (Accessor)
     * يتم استدعاؤه عبر $model->full_service_url
     */
    public function getFullServiceUrlAttribute(): string
    {
        if (empty($this->service_url)) {
            return '';
        }
        return rtrim($this->service_url, '/') . '/EcrComInterface.svc';
    }

    /**
     * إظهار جزء من المفتاح السري فقط للأمان (Accessor)
     * يتم استدعاؤه عبر $model->masked_secure_key
     */
    public function getMaskedSecureKeyAttribute(): string
    {
        if (!$this->secure_key) {
            return '';
        }

        $length = strlen($this->secure_key);
        // تأمين لعدم حدوث خطأ إذا كان المفتاح قصيراً جداً
        if ($length <= 8) {
            return str_repeat('*', $length);
        }

        return substr($this->secure_key, 0, 4) . '****' . substr($this->secure_key, -4);
    }

    /**
     * التحقق مما إذا كانت الإعدادات مكتملة وجاهزة للعمل
     */
    public function isConfigured(): bool
    {
        return !empty($this->service_url) &&
               !empty($this->terminal_id) &&
               !empty($this->merchant_id) &&
               !empty($this->secure_key);
    }

    /**
     * مصفوفة الإعدادات الجاهزة لإرسالها في طلبات الـ API
     */
    public function getConfigArray(): array
    {
        return [
            'service_url'          => $this->full_service_url,
            'terminal_id'          => $this->terminal_id,
            'merchant_id'          => $this->merchant_id,
            'merchant_name'        => $this->merchant_name,
            'secure_key'           => $this->secure_key,
            'currency_code'        => $this->currency_code,
            'business_location_id' => $this->business_location_id,
            'timeout'              => $this->timeout_seconds,
            'print_settings'       => [
                'width'         => $this->print_width,
                'customer_copy' => $this->print_customer_copy,
                'merchant_copy' => $this->print_merchant_copy,
            ]
        ];
    }
}