<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductUnit extends Model
{
    protected $fillable = [
        'product_id',
        'unit_name',
        'conversion_factor',
        'srp',
        'is_default_selling',
        'is_default_purchasing',
    ];

    protected $casts = [
        'conversion_factor' => 'decimal:4',
        'srp' => 'decimal:2',
        'is_default_selling' => 'boolean',
        'is_default_purchasing' => 'boolean',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
