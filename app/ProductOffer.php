<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ProductOffer extends Model
{
    protected $table = 'product_offers';
    
    protected $fillable = [
        'business_id',
        'variation_id',
        'location_id',
        'min_quantity',
        'offer_price',
        'price_type',
        'start_date',
        'end_date',
        'is_active',
        'notes',
        'created_by'
    ];
    
    protected $casts = [
        'min_quantity' => 'decimal:4',
        'offer_price' => 'decimal:4',
        'is_active' => 'boolean',
        'start_date' => 'date',
        'end_date' => 'date'
    ];
    
    /**
     * علاقة مع المنتج (Variation)
     */
    public function variation()
    {
        return $this->belongsTo(Variation::class);
    }
    
    /**
     * علاقة مع الموقع
     */
    public function location()
    {
        return $this->belongsTo(BusinessLocation::class);
    }
    
    /**
     * علاقة مع المستخدم الذي أنشأ العرض
     */
    public function created_by_user()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
    
    /**
     * علاقة مع Business
     */
    public function business()
    {
        return $this->belongsTo(Business::class);
    }
    
    /**
     * نطاق للعروض النشطة
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', 1)
            ->where(function($q) {
                $q->whereNull('start_date')
                  ->orWhere('start_date', '<=', date('Y-m-d'));
            })
            ->where(function($q) {
                $q->whereNull('end_date')
                  ->orWhere('end_date', '>=', date('Y-m-d'));
            });
    }
    
    /**
     * نطاق للعروض حسب الموقع
     */
    public function scopeForLocation($query, $location_id)
    {
        return $query->where('location_id', $location_id);
    }
    
    /**
     * نطاق للعروض حسب المنتج
     */
    public function scopeForProduct($query, $variation_id)
    {
        return $query->where('variation_id', $variation_id);
    }
    
    /**
     * التحقق إذا كان العرض ساري المفعول
     */
    public function isValid()
    {
        if (!$this->is_active) {
            return false;
        }
        
        $today = date('Y-m-d');
        
        if (!empty($this->start_date) && $this->start_date > $today) {
            return false;
        }
        
        if (!empty($this->end_date) && $this->end_date < $today) {
            return false;
        }
        
        return true;
    }
}