<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class StockAdjustmentLine extends Model
{
    /**
     * The attributes that aren't mass assignable.
     *
     * @var array
     */
    protected $guarded = ['id'];

    public function variation()
    {
        return $this->belongsTo(\App\Variation::class, 'variation_id');
    }

    public function lot_details()
    {
        return $this->belongsTo(\App\PurchaseLine::class, 'lot_no_line_id');
    }
    
     /**
 * علاقة سطر التسوية بالمنتج
 */  //// 005
public function product()
{
    return $this->belongsTo(\App\Product::class, 'product_id');
}

/**
 * علاقة سطر التسوية بالنوع (Variation)
 */  //// 005
public function variations()
{
    return $this->belongsTo(\App\Variation::class, 'variation_id');
}

}
