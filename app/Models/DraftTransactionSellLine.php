<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DraftTransactionSellLine extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'transaction_sell_lines_drafts';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'transaction_draft_id',
        'product_id',
        'variation_id',
        'quantity',
        'quantity_returned',
        'unit_id',
        'unit_price_before_discount',
        'unit_price',
        'line_discount_type',
        'line_discount_amount',
        'unit_price_inc_tax',
        'item_tax',
        'tax_id',
        'discount_id',
        'lot_no_line_id',
        'sell_line_note',
        'sub_unit_id',
        'discount_amount',
        'res_service_staff_id',
        'parent_sell_line_id',
        'children_type',
        'so_line_id',
        'so_quantity_invoiced',
        'secondary_unit_quantity',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'quantity' => 'decimal:4',
        'unit_price' => 'decimal:4',
        'unit_price_inc_tax' => 'decimal:4',
        'item_tax' => 'decimal:4',
    ];

    /**
     * Get the draft transaction that owns the sell line.
     */
    public function draft_transaction()
    {
        return $this->belongsTo(\App\Models\DraftTransaction::class, 'transaction_draft_id');
    }

    /**
     * Get the product for this line.
     */
    public function product()
    {
        return $this->belongsTo(\App\Product::class, 'product_id');
    }

    /**
     * Get the variation for this line.
     */
    public function variations()
    {
        return $this->belongsTo(\App\Variation::class, 'variation_id');
    }

    /**
     * Get the tax rate for this line.
     */
    public function tax_rate()
    {
        return $this->belongsTo(\App\TaxRate::class, 'tax_id');
    }

    /**
     * Get the unit for this line.
     */
    public function unit()
    {
        return $this->belongsTo(\App\Unit::class, 'unit_id');
    }

    /**
     * Get the sub unit for this line.
     */
    public function sub_unit()
    {
        return $this->belongsTo(\App\Unit::class, 'sub_unit_id');
    }

    /**
     * Get the service staff for this line.
     */
    public function service_staff()
    {
        return $this->belongsTo(\App\User::class, 'res_service_staff_id');
    }

    /**
     * Get modifiers for this line.
     */
    public function modifiers()
    {
        return $this->hasMany(\App\Models\DraftTransactionSellLine::class, 'parent_sell_line_id')
                    ->where('children_type', 'modifier');
    }

    /**
     * Get combo items for this line.
     */
    public function combo_items()
    {
        return $this->hasMany(\App\Models\DraftTransactionSellLine::class, 'parent_sell_line_id')
                    ->where('children_type', 'combo');
    }

    /**
     * Get the lot details.
     */
    public function lot_details()
    {
        return $this->belongsTo(\App\PurchaseLine::class, 'lot_no_line_id');
    }

    /**
     * Get warranties for this line.
     */
    public function warranties()
    {
        return $this->hasMany(\App\TransactionSellLineWarranty::class, 'transaction_sell_line_id');
    }

    /**
     * Scope to get only parent lines (not modifiers or combo items).
     */
    public function scopeParentLines($query)
    {
        return $query->whereNull('parent_sell_line_id');
    }
}

