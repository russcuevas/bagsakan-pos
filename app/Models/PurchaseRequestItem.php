<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PurchaseRequestItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'purchase_request_id',
        'product_id',
        'supplier_id',
        'unit_name',
        'conversion_factor',
        'quantity',
        'estimated_unit_cost',
        'estimated_subtotal',
        'original_quantity',
        'original_unit_cost',
        'original_supplier_id',
        'remarks',
        'status',
        'generated_purchase_id',
    ];

    protected $casts = [
        'conversion_factor' => 'decimal:4',
        'quantity' => 'decimal:4',
        'estimated_unit_cost' => 'decimal:4',
        'estimated_subtotal' => 'decimal:2',
        'original_quantity' => 'decimal:4',
        'original_unit_cost' => 'decimal:4',
    ];

    public function purchaseRequest()
    {
        return $this->belongsTo(PurchaseRequest::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    public function originalSupplier()
    {
        return $this->belongsTo(Supplier::class, 'original_supplier_id');
    }

    public function generatedPurchase()
    {
        return $this->belongsTo(Purchase::class, 'generated_purchase_id');
    }
}
