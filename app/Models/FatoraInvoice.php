<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FatoraInvoice extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'fatora_invoices';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'transaction_id',
        'business_id',
    'location_id',
        'invoice_uuid',
        'invoice_type',
        'payment_method',
        'qr_code',
        'xml_content',
        'response_data',
        'status',
        'error_message',
        'sent_at',
        'system_invoice_number',
        'system_invoice_uuid',
        'original_transaction_id',
        'original_invoice_uuid',
        'original_invoice_amount',
        'return_reason',
        'is_credit_invoice',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'response_data' => 'array',
        'sent_at' => 'datetime',
        'is_credit_invoice' => 'boolean',
        'original_invoice_amount' => 'decimal:2',
    ];

    /**
     * Get the transaction that owns the fatora invoice
     */
    public function transaction()
    {
        return $this->belongsTo(\App\Transaction::class, 'transaction_id');
    }

    /**
     * Get the business that owns the fatora invoice
     */
    public function business()
    {
        return $this->belongsTo(\App\Business::class, 'business_id');
    }

    /**
     * Get the business location that owns the fatora invoice
     */
    public function location()
    {
        return $this->belongsTo(\App\BusinessLocation::class, 'location_id');
    }

    /**
     * Get the original transaction for credit invoices
     */
    public function originalTransaction()
    {
        return $this->belongsTo(\App\Transaction::class, 'original_transaction_id');
    }

    /**
     * Scope a query to only include sent invoices.
     */
    public function scopeSent($query)
    {
        return $query->where('status', 'sent')->orWhere('status', 'accepted');
    }

    /**
     * Scope a query to only include credit invoices.
     */
    public function scopeCreditInvoices($query)
    {
        return $query->where('is_credit_invoice', true);
    }

    /**
     * Scope a query to only include invoices for specific location.
     */
    public function scopeForLocation($query, $locationId)
    {
        return $query->where('location_id', $locationId);
    }

    /**
     * Check if invoice has QR code
     */
    public function hasQrCode()
    {
        return !empty($this->qr_code);
    }

    /**
     * Get QR code as base64 image
     */
    public function getQrCodeBase64()
    {
        if (empty($this->qr_code)) {
            return null;
        }

        // QR code is already in base64 format from JoFotara
        // Just return it ready for img src
        return 'data:image/png;base64,' . $this->qr_code;
    }
}