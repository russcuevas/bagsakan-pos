@extends('layouts.app')

@section('title', 'Purchasing & Warehouse Dashboard')
@section('page_title', 'Purchasing & Inbound Operations')

@section('content')
<div class="stats-grid" style="grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); margin-bottom: 24px;">
    <div class="stat-card accent-amber">
        <div class="stat-header">
            <span class="stat-label">Pending Inbound Shipments</span>
            <div class="stat-icon"><i class="bi bi-truck"></i></div>
        </div>
        <div class="stat-value">{{ $pendingPurchasesCount }} orders</div>
        <div class="stat-helper"><a href="{{ route('staff.receiving.index') }}" style="color: var(--color-primary-blue); font-weight: 600;">Go to Receiving &rarr;</a></div>
    </div>

    <div class="stat-card accent-cyan">
        <div class="stat-header">
            <span class="stat-label">Active Warehouse Batches</span>
            <div class="stat-icon"><i class="bi bi-boxes"></i></div>
        </div>
        <div class="stat-value">{{ $totalActiveBatches }} batches</div>
        <div class="stat-helper">Currently stocked</div>
    </div>

    <div class="stat-card {{ $lowStockProducts->count() > 0 ? 'accent-red' : '' }}">
        <div class="stat-header">
            <span class="stat-label">Low Stock Products</span>
            <div class="stat-icon"><i class="bi bi-exclamation-triangle"></i></div>
        </div>
        <div class="stat-value">{{ $lowStockProducts->count() }} items</div>
        <div class="stat-helper">Require PO reorder</div>
    </div>
</div>

<div class="dashboard-charts-grid">
    <!-- Recent Inbound Batches Table -->
    <div class="card">
        <div class="card-header">
            <div>
                <h3 class="card-title">Recent Inbound Receiving Batches</h3>
                <p class="card-description">Stock received into warehouse</p>
            </div>
            <a href="{{ route('staff.receiving.index') }}" class="btn btn-sm btn-outline">Receive Goods</a>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="custom-table datatable-init">
                    <thead>
                        <tr>
                            <th>Batch #</th>
                            <th>Product</th>
                            <th>Supplier</th>
                            <th>DR #</th>
                            <th>Remaining Qty</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($recentBatches as $b)
                            <tr>
                                <td><strong>{{ $b->batch_code }}</strong></td>
                                <td>
                                    <strong>{{ $b->product->name ?? 'N/A' }}</strong>
                                    <div style="font-size: 0.74rem; color: var(--text-light);">{{ $b->product->sku ?? '' }}</div>
                                </td>
                                <td>{{ $b->supplier->name ?? 'N/A' }}</td>
                                <td>{{ $b->invoice_dr_number ?? 'N/A' }}</td>
                                <td style="font-weight: 800; color: #065F46;">{{ number_format($b->current_quantity, 2) }} {{ $b->product->base_unit ?? '' }}</td>
                                <td style="font-size: 0.8rem; color: var(--text-light);">{{ $b->receipt_date->format('M d, Y') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- My Spoilage Submissions -->
    <div class="card">
        <div class="card-header">
            <div>
                <h3 class="card-title">My Spoilage Reports</h3>
                <p class="card-description">Status of submitted damage reports</p>
            </div>
            <a href="{{ route('staff.spoilage.index') }}" class="btn btn-sm btn-outline">New Report</a>
        </div>
        <div class="card-body" style="padding: 16px 20px;">
            @forelse($mySpoilages as $sp)
                <div style="background: var(--body-bg); border-radius: var(--radius-sm); padding: 12px; margin-bottom: 12px; border: 1px solid var(--card-border);">
                    <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 4px;">
                        <span style="font-weight: 700; font-size: 0.88rem;">{{ $sp->product->name ?? 'N/A' }}</span>
                        @if($sp->status === 'approved')
                            <span class="badge badge-success">Approved</span>
                        @elseif($sp->status === 'pending')
                            <span class="badge badge-warning">Pending</span>
                        @else
                            <span class="badge badge-danger">Rejected</span>
                        @endif
                    </div>
                    <div style="font-size: 0.76rem; color: var(--text-muted);">
                        Qty: {{ number_format($sp->quantity, 2) }} {{ $sp->product->base_unit ?? '' }} &bull; {{ $sp->reason }}
                    </div>
                </div>
            @empty
                <div style="text-align: center; color: var(--text-light); padding: 30px 0;">No spoilage reports submitted.</div>
            @endforelse
        </div>
    </div>
</div>
@endsection
