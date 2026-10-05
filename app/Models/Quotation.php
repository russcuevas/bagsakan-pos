<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Quotation extends Model
{
    use HasFactory;

    protected $fillable = [
        'quotation_number',
        'quotation_date',
        'valid_until',
        'customer_id',
        'customer_name',
        'customer_contact',
        'customer_phone',
        'customer_email',
        'customer_address',
        'subtotal',
        'discount_type',
        'discount_value',
        'discount_amount',
        'tax_amount',
        'total_amount',
        'payment_terms',
        'notes',
        'status',
        'remarks',
        'prepared_by',
        'user_id',
        'converted_sale_id',
        'converted_at',
    ];

    protected static function booted(): void
    {
        static::saving(function (Quotation $quotation) {
            if (!$quotation->customer_phone && $quotation->customer_contact) {
                $quotation->customer_phone = $quotation->customer_contact;
            } elseif (!$quotation->customer_contact && $quotation->customer_phone) {
                $quotation->customer_contact = $quotation->customer_phone;
            }

            if (!$quotation->notes && $quotation->remarks) {
                $quotation->notes = $quotation->remarks;
            } elseif (!$quotation->remarks && $quotation->notes) {
                $quotation->remarks = $quotation->notes;
            }

            if (!$quotation->user_id && $quotation->prepared_by) {
                $quotation->user_id = $quotation->prepared_by;
            } elseif (!$quotation->prepared_by && $quotation->user_id) {
                $quotation->prepared_by = $quotation->user_id;
            }

            if (!$quotation->user_id && !$quotation->prepared_by && auth()->check()) {
                $quotation->user_id = auth()->id();
                $quotation->prepared_by = auth()->id();
            }
        });
    }

    protected $casts = [
        'quotation_date' => 'date',
        'valid_until' => 'date',
        'subtotal' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'converted_at' => 'datetime',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function preparer()
    {
        return $this->belongsTo(User::class, 'prepared_by');
    }

    public function convertedSale()
    {
        return $this->belongsTo(Sale::class, 'converted_sale_id');
    }

    public function items()
    {
        return $this->hasMany(QuotationItem::class);
    }

    public function getCustomerDisplayNameAttribute()
    {
        if ($this->customer) {
            return $this->customer->name . ($this->customer->business_name ? " ({$this->customer->business_name})" : '');
        }
        return $this->customer_name ?: 'Walk-in Customer';
    }
}
