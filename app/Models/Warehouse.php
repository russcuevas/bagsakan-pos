<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Warehouse extends Model
{
    protected $fillable = ['name', 'code', 'location', 'is_active'];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function receivingBatches(): HasMany
    {
        return $this->hasMany(ReceivingBatch::class);
    }

    public function inventoryMovements(): HasMany
    {
        return $this->hasMany(InventoryMovement::class);
    }
}
