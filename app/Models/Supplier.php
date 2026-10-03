<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Supplier extends Model
{
    protected $fillable = [
        'name',
        'contact_person',
        'mobile_number',
        'email',
        'address',
        'tin',
        'payment_terms',
        'bank_info',
        'outstanding_balance',
        'is_active',
    ];

    protected $casts = [
        'outstanding_balance' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'product_supplier')
            ->withPivot('supplier_product_code', 'last_purchase_cost')
            ->withTimestamps();
    }

    public function purchases(): HasMany
    {
        return $this->hasMany(Purchase::class);
    }

    public function receivingBatches(): HasMany
    {
        return $this->hasMany(ReceivingBatch::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }
}
