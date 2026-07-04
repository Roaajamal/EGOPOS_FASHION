<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ProductOfferBundleItem extends Model
{
    protected $table = 'product_offer_bundle_items';

    protected $fillable = [
        'bundle_id',
        'variation_id',
        'quantity',
    ];

    protected $casts = [
        'quantity' => 'decimal:4',
    ];

    public function bundle()
    {
        return $this->belongsTo(ProductOfferBundle::class, 'bundle_id');
    }

    public function variation()
    {
        return $this->belongsTo(Variation::class);
    }
}
