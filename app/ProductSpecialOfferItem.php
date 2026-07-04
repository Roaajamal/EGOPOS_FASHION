<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ProductSpecialOfferItem extends Model
{
    protected $table = 'product_special_offer_items';

    protected $fillable = [
        'special_offer_id',
        'variation_id',
    ];

    public function specialOffer()
    {
        return $this->belongsTo(ProductSpecialOffer::class, 'special_offer_id');
    }

    public function variation()
    {
        return $this->belongsTo(Variation::class);
    }
}
