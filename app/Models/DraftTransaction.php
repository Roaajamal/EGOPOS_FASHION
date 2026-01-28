<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Traits\LogsActivity;

class DraftTransaction extends Model
{
    use LogsActivity;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'transaction_drafts';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'business_id',
        'location_id',
        'type',
        'status',
        'sub_status',
        'sub_type',
        'contact_id',
        'customer_group_id',
        'invoice_no',
        'ref_no',
        'source',
        'invoice_scheme_id',
        'transaction_date',
        'total_before_tax',
        'tax_id',
        'tax_amount',
        'discount_type',
        'discount_amount',
        'shipping_details',
        'shipping_address',
        'shipping_status',
        'delivered_to',
        'shipping_charges',
        'additional_notes',
        'staff_note',
        'final_total',
        'expense_category_id',
        'expense_for',
        'commission_agent',
        'document',
        'is_direct_sale',
        'is_quotation',
        'is_suspend',
        'exchange_rate',
        'selling_price_group_id',
        'created_by',
        'types_of_service_id',
        'packing_charge',
        'packing_charge_type',
        'service_custom_field_1',
        'service_custom_field_2',
        'service_custom_field_3',
        'service_custom_field_4',
        'is_created_from_api',
        'res_table_id',
        'res_waiter_id',
        'is_export',
        'is_recurring',
        'recur_parent_id',
        'converted_to_transaction_id',
        'is_converted',
        'converted_at',
        'converted_by',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'is_direct_sale' => 'boolean',
        'is_quotation' => 'boolean',
        'is_suspend' => 'boolean',
        'is_export' => 'boolean',
        'is_recurring' => 'boolean',
        'is_converted' => 'boolean',
        'transaction_date' => 'datetime',
        'converted_at' => 'datetime',
    ];

    /**
     * Get the business that owns the draft.
     */
    public function business()
    {
        return $this->belongsTo(\App\Business::class, 'business_id');
    }

    /**
     * Get the location that owns the draft.
     */
    public function location()
    {
        return $this->belongsTo(\App\BusinessLocation::class, 'location_id');
    }

    /**
     * Get the contact (customer) for the draft.
     */
    public function contact()
    {
        return $this->belongsTo(\App\Contact::class, 'contact_id');
    }

    /**
     * Get the sell lines for the draft.
     */
    public function sell_lines()
    {
        return $this->hasMany(\App\Models\DraftTransactionSellLine::class, 'transaction_draft_id');
    }

    /**
     * Get the user who created the draft.
     */
    public function created_by_user()
    {
        return $this->belongsTo(\App\User::class, 'created_by');
    }

    /**
     * Get the tax rate for the draft.
     */
    public function tax()
    {
        return $this->belongsTo(\App\TaxRate::class, 'tax_id');
    }

    /**
     * Get the selling price group.
     */
    public function price_group()
    {
        return $this->belongsTo(\App\SellingPriceGroup::class, 'selling_price_group_id');
    }

    /**
     * Get the table (restaurant).
     */
    public function table()
    {
        return $this->belongsTo(\App\Restaurant\ResTable::class, 'res_table_id');
    }

    /**
     * Get the service staff (restaurant).
     */
    public function service_staff()
    {
        return $this->belongsTo(\App\User::class, 'res_waiter_id');
    }

    /**
     * Get the types of service.
     */
    public function types_of_service()
    {
        return $this->belongsTo(\App\TypesOfService::class, 'types_of_service_id');
    }

    /**
     * Get the media for the draft.
     */
    public function media()
    {
        return $this->morphMany(\App\Media::class, 'model');
    }

    /**
     * Get payment lines for the draft.
     * Using the same transaction_payments table but with different type identifier
     */
    public function payment_lines()
    {
        return $this->morphMany(\App\TransactionPayment::class, 'transaction')
                    ->where('parent_id', null);
    }

    /**
     * Get the final transaction if this draft was converted.
     */
    public function converted_transaction()
    {
        return $this->belongsTo(\App\Transaction::class, 'converted_to_transaction_id');
    }

    /**
     * Get the user who converted the draft.
     */
    public function converted_by_user()
    {
        return $this->belongsTo(\App\User::class, 'converted_by');
    }

    /**
     * Scope a query to only include drafts that haven't been converted.
     */
    public function scopeNotConverted($query)
    {
        return $query->where('is_converted', 0);
    }

    /**
     * Scope a query to only include converted drafts.
     */
    public function scopeConverted($query)
    {
        return $query->where('is_converted', 1);
    }

    /**
     * Scope a query to only include quotations.
     */
    public function scopeQuotations($query)
    {
        return $query->where('sub_status', 'quotation');
    }

    /**
     * Scope a query to only include proforma invoices.
     */
    public function scopeProforma($query)
    {
        return $query->where('sub_status', 'proforma');
    }

    /**
     * Activity log settings.
     */
    public function getActivitylogOptions(): \Spatie\Activitylog\LogOptions
    {
        return \Spatie\Activitylog\LogOptions::defaults()
            ->logAll()
            ->logOnlyDirty()
            ->setDescriptionForEvent(fn(string $eventName) => "Draft transaction {$eventName}")
            ->useLogName('draft_transaction');
    }
}

