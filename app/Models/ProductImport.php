<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\BusinessLocation;
use App\User;

class ProductImport extends Model
{
    use HasFactory;

  protected $casts = [
    'locations'     => 'array',
    'product_ids'   => 'array',
    'products_data' => 'array',
    'created_at'    => 'datetime:Y-m-d H:i',
    'updated_at'    => 'datetime:Y-m-d H:i',
];

protected $fillable = [
    'business_id',
    'created_by',
    'locations',
    'selected_location_id',
    'product_ids',
    'products_data',
    'product_count',
    'total_quantity',
    'notes'
];
    public function location()
    {
        return $this->belongsTo(BusinessLocation::class, 'location_id');
    }

    public function createdBy()
    {
        return $this->belongsTo(\App\User::class, 'created_by');
    }
}
