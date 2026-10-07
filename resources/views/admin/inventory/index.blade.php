@extends('layouts.app')

@section('title', 'Warehouse Inventory')
@section('page_title', 'Warehouse Inventory & Batch Ledger')

@section('content')
<!-- Inventory Metrics -->
<div class="stats-grid" style="grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); margin-bottom: 24px;">
    <div class="stat-card accent-cyan">
        <div class="stat-header">
            <span class="stat-label">Total Inventory Valuation</span>
            <div class="stat-icon"><i class="bi bi-shield-check"></i></div>
        </div>
        <div class="stat-value">₱{{ number_format($totalValuation, 2) }}</div>
        <div class="stat-helper">Sum of all batch quantities &times; cost</div>
    </div>

    <div class="stat-card">
        <div class="stat-header">
            <span class="stat-label">Active Receiving Batches</span>
            <div class="stat-icon"><i class="bi bi-boxes"></i></div>
        </div>
        <div class="stat-value">{{ $activeBatches->count() }} batches</div>
        <div class="stat-helper">With available stock on hand</div>
    </div>

    <div class="stat-card">
        <div class="stat-header">
            <span class="stat-label">Tracked SKUs</span>
            <div class="stat-icon"><i class="bi bi-box-seam"></i></div>
        </div>
        <div class="stat-value">{{ $products->count() }} items</div>
        <div class="stat-helper">Across active categories</div>
    </div>
</div>

<!-- Warehouse Stock per Product -->
<div class="card">
    <div class="card-header">
        <div>
            <h3 class="card-title">Stock Status by Product</h3>
            <p class="card-description">Aggregated stock in base units, average cost, and reorder levels</p>
        </div>
        <button type="button" class="btn btn-secondary" onclick="openModal('stockAdjustModal')">
            <i class="bi bi-sliders me-1"></i> Stock Adjustment
        </button>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="custom-table datatable-init">
                <thead>
                    <tr>
                        <th>Product SKU</th>
                        <th>Category</th>
                        <th>Base Unit</th>
                        <th>Current Stock</th>
                        <th>Weighted Avg Cost</th>
                        <th>Estimated Valuation</th>
                        <th>Threshold</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($products as $p)
                        @php
                            $stk = $p->available_stock;
                            $val = $stk * $p->average_cost;
                        @endphp
                        <tr>
                            <td>
                                <strong>{{ $p->name }}</strong>
                                <div style="font-size: 0.74rem; color: var(--text-light);">{{ $p->sku }}</div>
                            </td>
                            <td><span class="badge badge-navy">{{ $p->category->name ?? 'General' }}</span></td>
                            <td><span class="badge badge-primary">{{ $p->base_unit }}</span></td>
                            <td style="font-weight: 800; font-size: 0.95rem; color: {{ $stk < 0 ? '#dc2626' : 'var(--color-deep-navy)' }};">
                                {{ number_format($stk, 2) }} {{ $p->base_unit }}
                            </td>
                            <td style="font-weight: 600;">₱{{ number_format($p->average_cost, 2) }} / {{ $p->base_unit }}</td>
                            <td style="font-weight: 700; color: {{ $val < 0 ? '#dc2626' : '#065F46' }};">₱{{ number_format($val, 2) }}</td>
                            <td>{{ $p->low_stock_threshold }} {{ $p->base_unit }}</td>
                            <td>
                                @if($stk < 0)
                                    <span class="badge badge-danger" style="background: #dc2626; color: #fff; font-weight: 700;">Reorder Needed</span>
                                @elseif($stk == 0)
                                    <span class="badge badge-danger">Out of Stock</span>
                                @elseif($stk <= $p->low_stock_threshold)
                                    <span class="badge badge-warning">Low Stock</span>
                                @else
                                    <span class="badge badge-success">Sufficient</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Inbound Batches & Lots Detail Table -->
<div class="card">
    <div class="card-header">
        <div>
            <h3 class="card-title"><i class="bi bi-layers text-primary me-1"></i> Active Receiving Batches & Multi-Supplier Costs</h3>
            <p class="card-description">Exact trace of each supplier batch, receipt date, buying cost, and remaining quantity</p>
        </div>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="custom-table datatable-init">
                <thead>
                    <tr>
                        <th>Batch Code</th>
                        <th>Product</th>
                        <th>Supplier</th>
                        <th>Warehouse</th>
                        <th>DR / Invoice #</th>
                        <th>Remaining Qty</th>
                        <th>Initial Qty</th>
                        <th>Buying Cost</th>
                        <th>Batch Value</th>
                        <th>Receipt Date</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($activeBatches as $batch)
                        <tr>
                            <td><strong>{{ $batch->batch_code }}</strong></td>
                            <td>{{ $batch->product->name ?? 'N/A' }}</td>
                            <td>{{ $batch->supplier->name ?? 'N/A' }}</td>
                            <td><span class="badge badge-navy">{{ $batch->warehouse->code ?? 'WH' }}</span></td>
                            <td>{{ $batch->invoice_dr_number ?? 'N/A' }}</td>
                            <td style="font-weight: 800; color: #065F46;">
                                {{ number_format($batch->current_quantity, 2) }} {{ $batch->product->base_unit ?? '' }}
                            </td>
                            <td style="color: var(--text-muted);">
                                {{ number_format($batch->initial_quantity, 2) }} {{ $batch->product->base_unit ?? '' }}
                            </td>
                            <td style="font-weight: 600;">₱{{ number_format($batch->unit_cost, 2) }} / {{ $batch->product->base_unit ?? '' }}</td>
                            <td style="font-weight: 700; color: var(--color-primary-blue);">₱{{ number_format($batch->current_quantity * $batch->unit_cost, 2) }}</td>
                            <td style="font-size: 0.8rem; color: var(--text-light);">{{ $batch->receipt_date->format('M d, Y') }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Stock Movement Traceability Ledger -->
<div class="card">
    <div class="card-header">
        <div>
            <h3 class="card-title"><i class="bi bi-clock-history text-primary me-1"></i> Inventory Movement Audit Trail</h3>
            <p class="card-description">Immutable log of receipts, sales deductions, spoilages, and adjustments</p>
        </div>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="custom-table datatable-init">
                <thead>
                    <tr>
                        <th>Timestamp</th>
                        <th>Product</th>
                        <th>Movement Type</th>
                        <th>Quantity Change</th>
                        <th>Stock Before</th>
                        <th>Stock After</th>
                        <th>Unit Cost</th>
                        <th>Notes / Reason</th>
                        <th>Staff</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($movements as $m)
                        <tr>
                            <td style="font-size: 0.78rem; color: var(--text-light);">{{ $m->created_at->format('M d, Y h:i A') }}</td>
                            <td><strong>{{ $m->product->name ?? 'N/A' }}</strong></td>
                            <td>
                                @if($m->movement_type === 'purchase_receipt')
                                    <span class="badge badge-success"><i class="bi bi-arrow-down-left me-1"></i> Receipt</span>
                                @elseif($m->movement_type === 'pos_sale')
                                    <span class="badge badge-primary"><i class="bi bi-cart-check me-1"></i> POS Sale</span>
                                @elseif($m->movement_type === 'spoilage')
                                    <span class="badge badge-danger"><i class="bi bi-trash3 me-1"></i> Spoilage</span>
                                @elseif($m->movement_type === 'sale_void')
                                    <span class="badge badge-warning"><i class="bi bi-arrow-counterclockwise me-1"></i> Sale Void</span>
                                @else
                                    <span class="badge badge-navy">{{ ucfirst($m->movement_type) }}</span>
                                @endif
                            </td>
                            <td style="font-weight: 800; color: {{ $m->quantity_change > 0 ? '#065F46' : '#991B1B' }};">
                                {{ $m->quantity_change > 0 ? '+' : '' }}{{ number_format($m->quantity_change, 2) }} {{ $m->product->base_unit ?? '' }}
                            </td>
                            <td>{{ number_format($m->stock_before, 2) }}</td>
                            <td>{{ number_format($m->stock_after, 2) }}</td>
                            <td>₱{{ number_format($m->unit_cost, 2) }}</td>
                            <td><em>{{ $m->notes }}</em></td>
                            <td><span class="badge badge-navy">{{ $m->user->name ?? 'System' }}</span></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Stock Adjustment Modal -->
<div id="stockAdjustModal" class="modal-overlay">
    <div class="modal-dialog">
        <div class="modal-header">
            <h3 class="modal-title"><i class="bi bi-sliders text-primary me-2"></i> Manual Stock Adjustment</h3>
            <button type="button" class="modal-close-btn" data-close-modal="stockAdjustModal"><i class="bi bi-x-lg"></i></button>
        </div>
        <form action="{{ route('admin.inventory.adjust') }}" method="POST" class="ajax-form">
            @csrf
            <div class="modal-body">
                <div class="form-group">
                    <label class="form-label" for="adj_product">Product <span class="text-danger">*</span></label>
                    <select id="adj_product" name="product_id" class="form-select select2-init" required>
                        <option value="">Select product...</option>
                        @foreach($products as $p)
                            <option value="{{ $p->id }}">{{ $p->sku }} - {{ $p->name }} (Stock: {{ number_format($p->available_stock, 2) }} {{ $p->base_unit }})</option>
                        @endforeach
                    </select>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                    <div class="form-group">
                        <label class="form-label" for="adj_wh">Warehouse <span class="text-danger">*</span></label>
                        <select id="adj_wh" name="warehouse_id" class="form-select" required>
                            @foreach($warehouses as $w)
                                <option value="{{ $w->id }}">{{ $w->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="adj_type">Adjustment Type <span class="text-danger">*</span></label>
                        <select id="adj_type" name="type" class="form-select" required>
                            <option value="increase">Increase (+ Stock In)</option>
                            <option value="decrease">Decrease (- Stock Out)</option>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label" for="adj_qty">Quantity (in Base Unit) <span class="text-danger">*</span></label>
                    <input type="number" step="0.01" id="adj_qty" name="quantity" class="form-control" placeholder="0.00" required>
                </div>

                <div class="form-group">
                    <label class="form-label" for="adj_reason">Adjustment Reason <span class="text-danger">*</span></label>
                    <input type="text" id="adj_reason" name="reason" class="form-control" placeholder="e.g. Physical inventory discrepancy, recount adjustment" required>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline" data-close-modal="stockAdjustModal">Cancel</button>
                <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg me-1"></i> Post Adjustment</button>
            </div>
        </form>
    </div>
</div>
@endsection
