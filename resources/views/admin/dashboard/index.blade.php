@extends('layouts.app')

@section('title', 'Admin Dashboard')
@section('page_title', 'Business Overview & Financial Dashboard')

@section('content')
    <!-- Primary Daily Metrics Grid -->
    <div class="stats-grid">
        <div class="stat-card accent-cyan">
            <div class="stat-header">
                <span class="stat-label">Today's Sales</span>
                <div class="stat-icon"><i class="bi bi-cart-check"></i></div>
            </div>
            <div class="stat-value">₱{{ number_format($todaySales, 2) }}</div>
            <div class="stat-helper">
                <i class="bi bi-calendar-event text-info"></i> Today's completed transactions
            </div>
        </div>

        <div class="stat-card accent-amber">
            <div class="stat-header">
                <span class="stat-label">Cost of Goods (COGS)</span>
                <div class="stat-icon"><i class="bi bi-box-seam"></i></div>
            </div>
            <div class="stat-value">₱{{ number_format($todayCogs, 2) }}</div>
            <div class="stat-helper">
                <i class="bi bi-calculator"></i> Weighted average cost applied
            </div>
        </div>

        <div class="stat-card accent-green">
            <div class="stat-header">
                <span class="stat-label">Realized Gross Profit</span>
                <div class="stat-icon" style="background: #D1FAE5; color: #065F46;"><i class="bi bi-graph-up-arrow"></i></div>
            </div>
            <div class="stat-value" style="color: #065F46;">₱{{ number_format($todayGrossProfit, 2) }}</div>
            <div class="stat-helper">
                <i class="bi bi-cash-stack text-success"></i> Sales minus COGS
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-header">
                <span class="stat-label">Total Inventory Value</span>
                <div class="stat-icon"><i class="bi bi-boxes"></i></div>
            </div>
            <div class="stat-value">₱{{ number_format($totalInventoryValue, 2) }}</div>
            <div class="stat-helper">
                <i class="bi bi-shield-check text-primary"></i> Across all active batches
            </div>
        </div>
    </div>

    <!-- Secondary Financial Balance Cards -->
    <div class="stats-grid" style="grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));">
        <div class="stat-card">
            <div class="stat-header">
                <span class="stat-label">Customer Receivables (AR)</span>
                <div class="stat-icon"><i class="bi bi-people text-primary"></i></div>
            </div>
            <div class="stat-value" style="font-size: 1.4rem;">₱{{ number_format($totalReceivables, 2) }}</div>
            <div class="stat-helper"><a href="{{ route('admin.receivables.index') }}" style="color: var(--color-primary-blue); font-weight: 600;">View customer balances &rarr;</a></div>
        </div>

        <div class="stat-card">
            <div class="stat-header">
                <span class="stat-label">Supplier Payables (AP)</span>
                <div class="stat-icon"><i class="bi bi-truck text-danger"></i></div>
            </div>
            <div class="stat-value" style="font-size: 1.4rem; color: #991B1B;">₱{{ number_format($totalPayables, 2) }}</div>
            <div class="stat-helper"><a href="{{ route('admin.suppliers.index') }}" style="color: var(--color-primary-blue); font-weight: 600;">View supplier balances &rarr;</a></div>
        </div>

        <div class="stat-card accent-red">
            <div class="stat-header">
                <span class="stat-label">Today's Spoilage Loss</span>
                <div class="stat-icon"><i class="bi bi-trash3 text-danger"></i></div>
            </div>
            <div class="stat-value" style="font-size: 1.4rem; color: var(--danger-red);">₱{{ number_format($todaySpoilageLoss, 2) }}</div>
            <div class="stat-helper"><a href="{{ route('admin.spoilage.index') }}" style="color: var(--text-muted);">Inspection log &rarr;</a></div>
        </div>

        <div class="stat-card {{ $lowStockCount > 0 ? 'accent-amber' : '' }}">
            <div class="stat-header">
                <span class="stat-label">Low Stock Alerts</span>
                <div class="stat-icon"><i class="bi bi-exclamation-triangle text-warning"></i></div>
            </div>
            <div class="stat-value" style="font-size: 1.4rem;">{{ $lowStockCount }} items</div>
            <div class="stat-helper">{{ $outOfStockCount }} currently out of stock</div>
        </div>
    </div>

    <!-- Charts & Tables Layout -->
    <div class="dashboard-charts-grid">
        <!-- 7-Day Trend Chart -->
        <div class="card" style="margin-bottom: 0;">
            <div class="card-header">
                <div>
                    <h3 class="card-title">7-Day Sales & Profit Performance</h3>
                    <p class="card-description">Daily realized revenue versus gross margin</p>
                </div>
                <span class="badge badge-primary"><i class="bi bi-clock-history me-1"></i> Real-time</span>
            </div>
            <div class="card-body">
                <div style="height: 280px; position: relative;">
                    <canvas id="salesTrendChart"></canvas>
                </div>
            </div>
        </div>

        <!-- Top Selling Products -->
        <div class="card" style="margin-bottom: 0;">
            <div class="card-header">
                <div>
                    <h3 class="card-title">Top Selling Items</h3>
                    <p class="card-description">Last 30 days by revenue</p>
                </div>
            </div>
            <div class="card-body" style="padding: 12px 20px;">
                @forelse($topSelling as $item)
                    <div style="display: flex; align-items: center; justify-content: space-between; padding: 10px 0; border-bottom: 1px solid var(--card-border);">
                        <div>
                            <div style="font-weight: 700; font-size: 0.88rem; color: var(--color-deep-navy);">{{ $item->name }}</div>
                            <div style="font-size: 0.74rem; color: var(--text-light);">{{ $item->sku }} &bull; Sold: {{ number_format($item->total_qty, 1) }} {{ $item->base_unit }}</div>
                        </div>
                        <div style="text-align: right;">
                            <div style="font-weight: 800; font-size: 0.92rem; color: var(--color-primary-blue);">₱{{ number_format($item->total_revenue, 2) }}</div>
                            <div style="font-size: 0.72rem; color: #065F46; font-weight: 600;">+₱{{ number_format($item->total_profit, 2) }} profit</div>
                        </div>
                    </div>
                @empty
                    <div style="text-align: center; color: var(--text-light); padding: 30px 0;">No sales recorded yet.</div>
                @endforelse
            </div>
        </div>
    </div>

    <!-- Recent Sales & Pending Spoilage Approvals -->
    <div class="dashboard-tables-grid">
        <!-- Recent Sales Table -->
        <div class="card">
            <div class="card-header">
                <div>
                    <h3 class="card-title">Recent POS Transactions</h3>
                    <p class="card-description">Latest sales and credit dispatches</p>
                </div>
                <a href="{{ route('admin.reports.sales') }}" class="btn btn-sm btn-outline">View All Sales</a>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="custom-table">
                        <thead>
                            <tr>
                                <th>Invoice #</th>
                                <th>Customer</th>
                                <th>Method</th>
                                <th>Amount</th>
                                <th>Time</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($recentSales as $sale)
                                <tr>
                                    <td><strong>{{ $sale->sale_number }}</strong></td>
                                    <td>{{ $sale->customer ? $sale->customer->name : 'Walk-in' }}</td>
                                    <td>
                                        <span class="badge {{ $sale->payment_method === 'credit' ? 'badge-warning' : 'badge-primary' }}">
                                            {{ strtoupper($sale->payment_method) }}
                                        </span>
                                    </td>
                                    <td style="font-weight: 700; color: var(--color-deep-navy);">₱{{ number_format($sale->total_amount, 2) }}</td>
                                    <td style="font-size: 0.76rem; color: var(--text-light);">{{ $sale->sale_date->format('M d, h:i A') }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="5" style="text-align: center; color: var(--text-light); padding: 24px;">No recent transactions.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Spoilage Approval Requests -->
        <div class="card">
            <div class="card-header">
                <div>
                    <h3 class="card-title">Pending Spoilage Approvals</h3>
                    <p class="card-description">Staff loss reports requiring sign-off</p>
                </div>
                <a href="{{ route('admin.spoilage.index') }}" class="btn btn-sm btn-outline">Manage</a>
            </div>
            <div class="card-body" style="padding: 16px 20px;">
                @forelse($pendingSpoilages as $spoil)
                    <div style="background: var(--body-bg); border-radius: var(--radius-sm); padding: 12px; margin-bottom: 12px; border: 1px solid var(--card-border);">
                        <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 6px;">
                            <div>
                                <span style="font-weight: 700; font-size: 0.88rem; color: var(--color-deep-navy);">{{ $spoil->product->name }}</span>
                                <span class="badge badge-danger ms-2">{{ number_format($spoil->quantity, 2) }} {{ $spoil->product->base_unit }}</span>
                            </div>
                            <span style="font-size: 0.72rem; color: var(--text-light);">{{ $spoil->created_at->diffForHumans() }}</span>
                        </div>
                        <div style="font-size: 0.78rem; color: var(--text-muted); margin-bottom: 8px;">
                            Reason: <em>{{ $spoil->reason }}</em> (By: {{ $spoil->submitter->name ?? 'Staff' }})
                        </div>
                        <div style="display: flex; gap: 8px; justify-content: flex-end;">
                            <form action="{{ route('admin.spoilage.approve', $spoil->id) }}" method="POST" class="ajax-form">
                                @csrf
                                <button type="submit" class="btn btn-sm btn-success" style="padding: 4px 10px; font-size: 0.75rem;">
                                    <i class="bi bi-check-lg"></i> Approve Loss
                                </button>
                            </form>
                        </div>
                    </div>
                @empty
                    <div style="text-align: center; color: var(--text-light); padding: 30px 0;">
                        <i class="bi bi-check2-circle text-success" style="font-size: 2rem; display: block; margin-bottom: 6px;"></i>
                        No pending spoilage requests.
                    </div>
                @endforelse
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const ctx = document.getElementById('salesTrendChart').getContext('2d');
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: {!! json_encode($chartDates) !!},
                datasets: [
                    {
                        label: 'Total Sales (₱)',
                        data: {!! json_encode($chartSales) !!},
                        borderColor: '#075998',
                        backgroundColor: 'rgba(7, 89, 152, 0.1)',
                        borderWidth: 3,
                        fill: true,
                        tension: 0.35,
                        pointRadius: 4,
                        pointBackgroundColor: '#075998'
                    },
                    {
                        label: 'Gross Profit (₱)',
                        data: {!! json_encode($chartProfits) !!},
                        borderColor: '#10B981',
                        backgroundColor: 'transparent',
                        borderWidth: 2.5,
                        borderDash: [5, 5],
                        tension: 0.35,
                        pointRadius: 4,
                        pointBackgroundColor: '#10B981'
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'top',
                        labels: { font: { family: 'Plus Jakarta Sans', size: 12, weight: '600' } }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: { color: '#E2EEF8' },
                        ticks: {
                            font: { family: 'Plus Jakarta Sans', size: 11 },
                            callback: function (val) { return '₱' + val.toLocaleString(); }
                        }
                    },
                    x: {
                        grid: { display: false },
                        ticks: { font: { family: 'Plus Jakarta Sans', size: 11 } }
                    }
                }
            }
        });
    });
</script>
@endpush
