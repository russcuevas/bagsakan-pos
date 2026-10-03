<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SellingPriceHistory extends Model
{
    protected $fillable = [
        'product_id',
        'unit_name',
        'old_srp',
        'new_srp',
        'effective_date',
        'reason',
        'updated_by',
    ];

    protected $casts = [
        'old_srp' => 'decimal:2',
        'new_srp' => 'decimal:2',
        'effective_date' => 'datetime',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
