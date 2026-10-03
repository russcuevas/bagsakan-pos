@extends('layouts.app')

@section('title', 'Supplier Purchases Report')
@section('page_title', 'Supplier Purchasing & Inbound History')

@section('content')
<!-- Filter Bar -->
<div class="card" style="margin-bottom: 20px;">
    <div class="card-body" style="padding: 16px 20px;">
        <form action="{{ route('admin.reports.suppliers') }}" method="GET" style="display: flex; gap: 16px; align-items: flex-end; flex-wrap: wrap;">
            <div style="flex: 1; min-width: 160px;">
                <label class="form-label" style="font-size: 0.76rem;">Start Date</label>
                <input type="date" name="start_date" class="form-control form-control-sm" value="{{ $startDate }}">
            </div>
            <div style="flex: 1; min-width: 160px;">
                <label class="form-label" style="font-size: 0.76rem;">End Date</label>
                <input type="date" name="end_date" class="form-control form-control-sm" value="{{ $endDate }}">
            </div>
            <div style="flex: 1.5; min-width: 220px;">
                <label class="form-label" style="font-size: 0.76rem;">Filter by Supplier</label>
                <select name="supplier_id" class="form-select form-select-sm select2-init">
                    <option value="">All Suppliers</option>
                    @foreach($suppliers as $s)
                        <option value="{{ $s->id }}" {{ request('supplier_id') == $s->id ? 'selected' : '' }}>{{ $s->name }}</option>
                    @endforeach
                </select>
            </div>
            <div style="flex: 1.5; min-width: 220px;">
                <label class="form-label" style="font-size: 0.76rem;">Filter by Product</label>
                <select name="product_id" class="form-select form-select-sm select2-init">
                    <option value="">All Products</option>
                    @foreach($products as $p)
                        <option value="{{ $p->id }}" {{ request('product_id') == $p->id ? 'selected' : '' }}>{{ $p->sku }} - {{ $p->name }}</option>
                    @endforeach
                </select>
            </div>
            <button type="submit" class="btn btn-primary btn-sm" style="height: 38px;">
                <i class="bi bi-funnel me-1"></i> Filter Purchases
            </button>
        </form>
    </div>
</div>

<div class="stats-grid" style="grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); margin-bottom: 24px;">
    <div class="stat-card accent-cyan">
        <div class="stat-header">
            <span class="stat-label">Total Purchasing Spend</span>
            <div class="stat-icon"><i class="bi bi-cart-plus"></i></div>
        </div>
        <div class="stat-value">₱{{ number_format($totalSpent, 2) }}</div>
        <div class="stat-helper">{{ $records->count() }} inbound purchase lines</div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <div>
            <h2 class="card-title">Supplier Purchases Ledger</h2>
            <p class="card-description">Track procurement spend, supplier delivery receipts, quantities, and unit buying costs</p>
        </div>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="custom-table datatable-init">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Supplier</th>
                        <th>Product SKU</th>
                        <th>PO / DR Reference</th>
                        <th>Unit</th>
                        <th>Qty Ordered</th>
                        <th>Qty Received</th>
                        <th>Unit Cost</th>
                        <th>Line Spend</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($records as $r)
                        <tr>
                            <td style="font-size: 0.8rem; color: var(--text-light);">{{ date('M d, Y', strtotime($r->purchase_date)) }}</td>
                            <td><strong>{{ $r->supplier_name }}</strong></td>
                            <td>
                                <strong>{{ $r->product_name }}</strong>
                                <div style="font-size: 0.74rem; color: var(--text-light);">{{ $r->product_sku }}</div>
                            </td>
                            <td>
                                <div><strong>{{ $r->purchase_number }}</strong></div>
                                <div style="font-size: 0.74rem; color: var(--text-muted);">{{ $r->invoice_dr_number ?? 'Pending DR' }}</div>
                            </td>
                            <td><span class="badge badge-navy">{{ $r->unit_name }}</span></td>
                            <td>{{ number_format($r->quantity_ordered, 2) }}</td>
                            <td style="font-weight: 700; color: #065F46;">{{ number_format($r->quantity_received, 2) }}</td>
                            <td>₱{{ number_format($r->unit_cost, 2) }}</td>
                            <td style="font-weight: 800; color: var(--color-deep-navy);">₱{{ number_format($r->subtotal, 2) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
