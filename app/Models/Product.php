<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    protected $fillable = [
        'sku',
        'barcode',
        'name',
        'category_id',
        'base_unit',
        'default_srp',
        'costing_method',
        'average_cost',
        'low_stock_threshold',
        'image_path',
        'description',
        'is_active',
    ];

    protected $casts = [
        'default_srp' => 'decimal:2',
        'average_cost' => 'decimal:4',
        'low_stock_threshold' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    protected $appends = ['available_stock', 'image_url'];

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function units(): HasMany
    {
        return $this->hasMany(ProductUnit::class);
    }

    public function suppliers(): BelongsToMany
    {
        return $this->belongsToMany(Supplier::class, 'product_supplier')
            ->withPivot('supplier_product_code', 'last_purchase_cost')
            ->withTimestamps();
    }

    public function batches(): HasMany
    {
        return $this->hasMany(ReceivingBatch::class);
    }

    public function activeBatches(): HasMany
    {
        return $this->hasMany(ReceivingBatch::class)->where('status', 'active')->where('current_quantity', '>', 0);
    }

    public function priceHistories(): HasMany
    {
        return $this->hasMany(SellingPriceHistory::class)->orderBy('effective_date', 'desc');
    }

    public function movements(): HasMany
    {
        return $this->hasMany(InventoryMovement::class)->orderBy('created_at', 'desc');
    }

    public function getAvailableStockAttribute(): float
    {
        return (float) $this->activeBatches()->sum('current_quantity');
    }

    public function getStockInWarehouse(int $warehouseId): float
    {
        return (float) $this->activeBatches()->where('warehouse_id', $warehouseId)->sum('current_quantity');
    }

    public function getImageUrlAttribute(): string
    {
        if ($this->image_path && file_exists(public_path($this->image_path))) {
            return asset($this->image_path);
        }
        return asset('assets/images/placeholder-product.svg');
    }
}
