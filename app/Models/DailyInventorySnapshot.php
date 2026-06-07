<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DailyInventorySnapshot extends Model
{
    protected $fillable = [
        'snapshot_date',
        'product_id',
        'variation_id',
        'location_id',
        'product_name',
        'sku',
        'qty_available',
    ];

    protected $casts = [
        'snapshot_date' => 'date',
    ];
}