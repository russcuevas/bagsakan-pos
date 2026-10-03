<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Purchase extends Model
{
    protected $fillable = [
        'purchase_number',
        'supplier_id',
        'warehouse_id',
        'invoice_dr_number',
        'purchase_date',
        'payment_terms',
        'payment_status',
        'status',
        'total_amount',
        'paid_amount',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'purchase_date' => 'date',
        'total_amount' => 'decimal:2',
        'paid_amount' => 'decimal:2',
    ];

    protected $appends = [
        'formatted_purchase_date',
        'formatted_created_at',
    ];

    public function getFormattedPurchaseDateAttribute(): string
    {
        if (!$this->purchase_date) return 'N/A';
        $time = $this->created_at ? ' / ' . $this->created_at->format('h:i A') : '';
        return \Carbon\Carbon::parse($this->purchase_date)->format('Y-m-d') . $time;
    }

    public function getFormattedCreatedAtAttribute(): string
    {
        return $this->created_at ? $this->created_at->format('Y-m-d / h:i A') : 'N/A';
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function lines(): HasMany
    {
        return $this->hasMany(PurchaseLine::class);
    }

    public function batches(): HasMany
    {
        return $this->hasMany(ReceivingBatch::class);
    }

    public function allocations(): HasMany
    {
        return $this->hasMany(InvoiceAllocation::class);
    }
}
