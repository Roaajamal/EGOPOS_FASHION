<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class VariationLocationDetails extends Model
{
    /**
     * The attributes that aren't mass assignable.
     *
     * @var array
     */
    protected $guarded = ['id'];
    
     ///////////// 005
    public function variation()
{
    return $this->belongsTo(\App\Variation::class, 'variation_id');
} 

}
