@extends('layouts.app')

@section('title', 'Inventory Valuation Report')
@section('page_title', 'Inventory Asset Valuation Report')

@section('content')
<div class="stats-grid" style="grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); margin-bottom: 24px;">
    <div class="stat-card accent-cyan">
        <div class="stat-header">
            <span class="stat-label">Total Asset Valuation</span>
            <div class="stat-icon"><i class="bi bi-shield-check"></i></div>
        </div>
        <div class="stat-value">₱{{ number_format($totalValuation, 2) }}</div>
        <div class="stat-helper">Based on actual active batch acquisition costs</div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <div>
            <h2 class="card-title">Inventory Valuation by Active Batches</h2>
            <p class="card-description">Detailed warehouse asset report by SKU, supplier lot, and cost</p>
        </div>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="custom-table datatable-init">
                <thead>
                    <tr>
                        <th>Batch Code</th>
                        <th>Product</th>
                        <th>Category</th>
                        <th>Supplier</th>
                        <th>Warehouse</th>
                        <th>Remaining Stock</th>
                        <th>Unit Cost</th>
                        <th>Total Asset Value</th>
                        <th>Receipt Date</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($batches as $b)
                        @php
                            $lineVal = $b->current_quantity * $b->unit_cost;
                        @endphp
                        <tr>
                            <td><strong>{{ $b->batch_code }}</strong></td>
                            <td>
                                <strong>{{ $b->product->name ?? 'N/A' }}</strong>
                                <div style="font-size: 0.74rem; color: var(--text-light);">{{ $b->product->sku ?? '' }}</div>
                            </td>
                            <td><span class="badge badge-navy">{{ $b->product->category->name ?? 'N/A' }}</span></td>
                            <td>{{ $b->supplier->name ?? 'N/A' }}</td>
                            <td><span class="badge badge-primary">{{ $b->warehouse->name ?? 'Main' }}</span></td>
                            <td style="font-weight: 800; color: #065F46;">
                                {{ number_format($b->current_quantity, 2) }} {{ $b->product->base_unit ?? '' }}
                            </td>
                            <td>₱{{ number_format($b->unit_cost, 2) }} / {{ $b->product->base_unit ?? '' }}</td>
                            <td style="font-weight: 800; color: var(--color-deep-navy); font-size: 0.95rem;">
                                ₱{{ number_format($lineVal, 2) }}
                            </td>
                            <td style="font-size: 0.8rem; color: var(--text-light);">{{ $b->receipt_date->format('M d, Y') }}</td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="7" style="text-align: right; font-weight: 800; font-size: 1rem;">Total Warehouse Valuation:</td>
                        <td colspan="2" style="font-weight: 800; font-size: 1.1rem; color: var(--color-primary-blue);">₱{{ number_format($totalValuation, 2) }}</td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>
@endsection
