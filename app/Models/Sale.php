<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Sale extends Model
{
    protected $fillable = [
        'sale_number',
        'customer_id',
        'warehouse_id',
        'cashier_id',
        'sale_date',
        'subtotal',
        'discount_type',
        'discount_value',
        'discount_amount',
        'discount_reason',
        'total_amount',
        'total_cogs',
        'payment_method',
        'amount_paid',
        'change_amount',
        'payment_status',
        'due_date',
        'payment_terms',
        'reference_number',
        'status',
        'void_reason',
        'voided_by',
        'voided_at',
        'notes',
    ];

    protected $casts = [
        'sale_date' => 'datetime',
        'due_date' => 'date',
        'voided_at' => 'datetime',
        'subtotal' => 'decimal:2',
        'discount_value' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'total_cogs' => 'decimal:4',
        'amount_paid' => 'decimal:2',
        'change_amount' => 'decimal:2',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function cashier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cashier_id');
    }

    public function voidedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'voideded_by');
    }

    public function lines(): HasMany
    {
        return $this->hasMany(SaleLine::class);
    }

    public function allocations(): HasMany
    {
        return $this->hasMany(InvoiceAllocation::class);
    }

    public function getGrossProfitAttribute(): float
    {
        return (float) ($this->total_amount - $this->total_cogs);
    }
}
