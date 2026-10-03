@extends('layouts.app')

@section('title', 'Sales & COGS Report')
@section('page_title', 'Sales, COGS & Margin Analysis')

@section('content')
<!-- Filter Bar -->
<div class="card" style="margin-bottom: 20px;">
    <div class="card-body" style="padding: 16px 20px;">
        <form action="{{ route('admin.reports.sales') }}" method="GET" style="display: flex; gap: 16px; align-items: flex-end; flex-wrap: wrap;">
            <div style="flex: 1; min-width: 160px;">
                <label class="form-label" style="font-size: 0.76rem;">Start Date</label>
                <input type="date" name="start_date" class="form-control form-control-sm" value="{{ $startDate }}">
            </div>
            <div style="flex: 1; min-width: 160px;">
                <label class="form-label" style="font-size: 0.76rem;">End Date</label>
                <input type="date" name="end_date" class="form-control form-control-sm" value="{{ $endDate }}">
            </div>
            <div style="flex: 1.5; min-width: 200px;">
                <label class="form-label" style="font-size: 0.76rem;">Customer</label>
                <select name="customer_id" class="form-select form-select-sm select2-init">
                    <option value="">All Customers</option>
                    @foreach($customers as $c)
                        <option value="{{ $c->id }}" {{ request('customer_id') == $c->id ? 'selected' : '' }}>{{ $c->name }}</option>
                    @endforeach
                </select>
            </div>
            <div style="flex: 1; min-width: 140px;">
                <label class="form-label" style="font-size: 0.76rem;">Payment Method</label>
                <select name="payment_method" class="form-select form-select-sm">
                    <option value="">All Methods</option>
                    <option value="cash" {{ request('payment_method') == 'cash' ? 'selected' : '' }}>Cash</option>
                    <option value="gcash" {{ request('payment_method') == 'gcash' ? 'selected' : '' }}>GCash</option>
                    <option value="bank_transfer" {{ request('payment_method') == 'bank_transfer' ? 'selected' : '' }}>Bank Transfer</option>
                    <option value="credit" {{ request('payment_method') == 'credit' ? 'selected' : '' }}>Credit</option>
                </select>
            </div>
            <button type="submit" class="btn btn-primary btn-sm" style="height: 38px;">
                <i class="bi bi-funnel me-1"></i> Filter Report
            </button>
        </form>
    </div>
</div>

<!-- Summary Metrics -->
<div class="stats-grid" style="grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); margin-bottom: 24px;">
    <div class="stat-card accent-cyan">
        <div class="stat-header">
            <span class="stat-label">Total Realized Sales</span>
            <div class="stat-icon"><i class="bi bi-cart-check"></i></div>
        </div>
        <div class="stat-value">₱{{ number_format($totalSales, 2) }}</div>
        <div class="stat-helper">{{ $sales->count() }} completed orders</div>
    </div>

    <div class="stat-card accent-amber">
        <div class="stat-header">
            <span class="stat-label">Cost of Goods Sold (COGS)</span>
            <div class="stat-icon"><i class="bi bi-calculator"></i></div>
        </div>
        <div class="stat-value">₱{{ number_format($totalCogs, 2) }}</div>
        <div class="stat-helper">Actual inventory batch cost</div>
    </div>

    <div class="stat-card accent-green">
        <div class="stat-header">
            <span class="stat-label">Realized Gross Profit</span>
            <div class="stat-icon"><i class="bi bi-cash-stack"></i></div>
        </div>
        <div class="stat-value" style="color: #065F46;">₱{{ number_format($totalGrossProfit, 2) }}</div>
        <div class="stat-helper"><strong class="text-success">{{ number_format($profitMargin, 1) }}%</strong> gross profit margin</div>
    </div>
</div>

<!-- Detailed Sales Table -->
<div class="card">
    <div class="card-header">
        <div>
            <h2 class="card-title">Itemized Sales & Profit Breakdown</h2>
            <p class="card-description">Individual transactions, realized selling prices, applied costs, and margins</p>
        </div>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="custom-table datatable-init">
                <thead>
                    <tr>
                        <th>Invoice #</th>
                        <th>Date & Time</th>
                        <th>Customer</th>
                        <th>Items Sold</th>
                        <th>Revenue</th>
                        <th>COGS</th>
                        <th>Gross Profit</th>
                        <th>Margin</th>
                        <th>Cashier</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($sales as $s)
                        @php
                            $profit = $s->total_amount - $s->total_cogs;
                            $margin = $s->total_amount > 0 ? (($profit / $s->total_amount) * 100) : 0;
                        @endphp
                        <tr>
                            <td><strong>{{ $s->sale_number }}</strong></td>
                            <td style="font-size: 0.8rem; color: var(--text-light);">{{ $s->sale_date->format('M d, Y h:i A') }}</td>
                            <td>{{ $s->customer->name ?? 'Walk-in Customer' }}</td>
                            <td style="font-size: 0.8rem;">
                                {{ $s->lines->map(fn($l) => "{$l->quantity} {$l->unit_name} {$l->product->name}")->implode(', ') }}
                            </td>
                            <td style="font-weight: 700;">₱{{ number_format($s->total_amount, 2) }}</td>
                            <td style="color: var(--text-muted);">₱{{ number_format($s->total_cogs, 2) }}</td>
                            <td style="font-weight: 800; color: #065F46;">₱{{ number_format($profit, 2) }}</td>
                            <td><span class="badge badge-success">{{ number_format($margin, 1) }}%</span></td>
                            <td><span class="badge badge-navy">{{ $s->cashier->name ?? 'POS' }}</span></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
