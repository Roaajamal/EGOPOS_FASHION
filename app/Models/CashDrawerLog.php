<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CashDrawerLog extends Model
{
    use HasFactory;
    public $timestamps = false;
    protected $fillable = ['user_id', 'business_id', 'location_id', 'cash_register_id', 'ip_address'];

    public function user()     { return $this->belongsTo(User::class); }
    public function location() { return $this->belongsTo(Location::class); }
    public function cashRegister() { return $this->belongsTo(CashRegister::class); } 
}
