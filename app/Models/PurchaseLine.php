<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PurchaseLine extends Model
{
    protected $fillable = [
        'purchase_id',
        'product_id',
        'supplier_id',
        'unit_name',
        'conversion_factor',
        'quantity_ordered',
        'quantity_received',
        'unit_cost',
        'base_cost',
        'subtotal',
    ];

    protected $casts = [
        'conversion_factor' => 'decimal:4',
        'quantity_ordered' => 'decimal:4',
        'quantity_received' => 'decimal:4',
        'unit_cost' => 'decimal:4',
        'base_cost' => 'decimal:4',
        'subtotal' => 'decimal:2',
    ];

    public function purchase(): BelongsTo
    {
        return $this->belongsTo(Purchase::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function batches()
    {
        return $this->hasMany(ReceivingBatch::class, 'purchase_line_id');
    }

    public function getDeficitQuantityAttribute(): float
    {
        return max(0, (float) $this->quantity_ordered - (float) $this->quantity_received);
    }

    public function getIsFullyReceivedAttribute(): bool
    {
        return (float) $this->quantity_received >= (float) $this->quantity_ordered;
    }
}
