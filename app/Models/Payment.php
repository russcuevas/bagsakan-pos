<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Payment extends Model
{
    protected $fillable = [
        'payment_number',
        'customer_id',
        'supplier_id',
        'type',
        'payment_date',
        'payment_method',
        'reference_number',
        'amount',
        'tax_withheld',
        'tax_type',
        'tax_doc_number',
        'notes',
        'received_by',
    ];

    protected $casts = [
        'payment_date' => 'date',
        'amount' => 'decimal:2',
        'tax_withheld' => 'decimal:2',
    ];

    public function getTotalSettledAttribute(): float
    {
        return (float) ($this->amount + $this->tax_withheld);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function receiver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'received_by');
    }

    public function allocations(): HasMany
    {
        return $this->hasMany(InvoiceAllocation::class);
    }
}
