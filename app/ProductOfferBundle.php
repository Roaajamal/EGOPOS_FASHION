<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ProductOfferBundle extends Model
{
    protected $table = 'product_offer_bundles';

    protected $fillable = [
        'business_id',
        'location_id',
        'name',
        'bundle_price',
        'start_date',
        'end_date',
        'is_active',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'bundle_price' => 'decimal:4',
        'is_active' => 'boolean',
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    public function items()
    {
        return $this->hasMany(ProductOfferBundleItem::class, 'bundle_id');
    }

    public function location()
    {
        return $this->belongsTo(BusinessLocation::class);
    }
}
