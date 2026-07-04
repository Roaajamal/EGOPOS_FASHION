<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ProductSpecialOffer extends Model
{
    protected $table = 'product_special_offers';

    protected $fillable = [
        'business_id',
        'location_id',
        'name',
        'offer_type',
        'buy_qty',
        'free_qty',
        'percent',
        'start_date',
        'end_date',
        'is_active',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'buy_qty' => 'decimal:4',
        'free_qty' => 'decimal:4',
        'percent' => 'decimal:2',
        'is_active' => 'boolean',
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    public function items()
    {
        return $this->hasMany(ProductSpecialOfferItem::class, 'special_offer_id');
    }

    public function location()
    {
        return $this->belongsTo(BusinessLocation::class);
    }
}
