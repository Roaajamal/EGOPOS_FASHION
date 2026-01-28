<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BarcodeDesignSetting extends Model
{
    use HasFactory;

    protected $table = 'barcode_design_settings';

    protected $fillable = [
        'business_id',
        'design'
    ];

    protected $casts = [
        'design' => 'array'
    ];

    public function business()
    {
        return $this->belongsTo(Business::class);
    }
}