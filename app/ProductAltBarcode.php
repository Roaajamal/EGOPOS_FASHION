<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ProductAltBarcode extends Model
{
    protected $table = 'product_alt_barcodes';

    protected $fillable = [
        'business_id',
        'variation_id',
        'alt_barcode',
        'created_by',
    ];

    public function variation()
    {
        return $this->belongsTo(Variation::class);
    }
}
