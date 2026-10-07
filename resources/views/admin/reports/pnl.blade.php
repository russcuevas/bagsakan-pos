@extends('layouts.app')

@section('title', 'Profit & Loss Statement')
@section('page_title', 'Profit & Loss (Income Statement)')

@section('content')
    <div class="card" style="margin-bottom: 20px;">
        <div class="card-body" style="padding: 16px 20px;">
            <form action="{{ route('admin.reports.pnl') }}" method="GET"
                style="display: flex; gap: 16px; align-items: flex-end;">
                <div style="flex: 1; max-width: 200px;">
                    <label class="form-label" style="font-size: 0.76rem;">Period Start</label>
                    <input type="date" name="start_date" class="form-control form-control-sm"
                        value="{{ $startDate }}">
                </div>
                <div style="flex: 1; max-width: 200px;">
                    <label class="form-label" style="font-size: 0.76rem;">Period End</label>
                    <input type="date" name="end_date" class="form-control form-control-sm" value="{{ $endDate }}">
                </div>
                <button type="submit" class="btn btn-primary btn-sm" style="height: 38px;">
                    <i class="bi bi-arrow-repeat me-1"></i> Generate P&L
                </button>
            </form>
        </div>
    </div>

    <div class="card" style="max-width: 800px; margin: 0 auto;">
        <div class="card-header" style="text-align: center; display: block; padding: 24px;">
            <h2 style="font-size: 1.3rem; font-weight: 800; color: var(--color-deep-navy);">Bagsakan Kalidad Food Products
                Trading TRADING</h2>
            <div style="font-size: 0.9rem; font-weight: 700; color: var(--color-primary-blue);">STATEMENT OF PROFIT AND LOSS
            </div>
            <div style="font-size: 0.8rem; color: var(--text-light); margin-top: 4px;">
                For the period: {{ date('F d, Y', strtotime($startDate)) }} to {{ date('F d, Y', strtotime($endDate)) }}
            </div>
        </div>
        <div class="card-body" style="padding: 30px;">
            <table style="width: 100%; border-collapse: collapse; font-size: 0.95rem;">
                <!-- Revenue Section -->
                <tr style="border-bottom: 1.5px solid var(--card-border);">
                    <td style="padding: 12px 0; font-weight: 700; color: var(--color-deep-navy);">Gross Sales Revenue</td>
                    <td style="padding: 12px 0; text-align: right; font-weight: 800;">₱{{ number_format($totalSales, 2) }}
                    </td>
                </tr>
                <tr style="color: var(--text-muted);">
                    <td style="padding: 10px 0 10px 20px;">Less: Cost of Goods Sold (COGS)</td>
                    <td style="padding: 10px 0; text-align: right; color: #991B1B;">(₱{{ number_format($totalCogs, 2) }})
                    </td>
                </tr>
                <tr style="background: var(--color-mist-blue); font-weight: 800;">
                    <td style="padding: 12px 14px; color: var(--color-deep-navy);">REALIZED GROSS PROFIT</td>
                    <td style="padding: 12px 14px; text-align: right; color: #065F46; font-size: 1.05rem;">
                        ₱{{ number_format($grossProfit, 2) }}
                    </td>
                </tr>

                <!-- Operating Expenses Section -->
                <tr>
                    <td colspan="2"
                        style="padding-top: 24px; font-weight: 700; color: var(--color-deep-navy); font-size: 0.9rem; text-transform: uppercase;">
                        Operating Expenses
                    </td>
                </tr>
                @php
                    $groupedExpenses = $expenses->groupBy('category');
                @endphp
                @forelse($groupedExpenses as $cat => $items)
                    <tr style="font-size: 0.88rem; color: var(--text-muted);">
                        <td style="padding: 6px 0 6px 20px;">{{ $cat }}</td>
                        <td style="padding: 6px 0; text-align: right;">₱{{ number_format($items->sum('amount'), 2) }}</td>
                    </tr>
                @empty
                    <tr style="font-size: 0.88rem; color: var(--text-light);">
                        <td style="padding: 6px 0 6px 20px;">No operating expenses recorded</td>
                        <td style="padding: 6px 0; text-align: right;">₱0.00</td>
                    </tr>
                @endforelse
                <tr style="border-top: 1px dashed var(--card-border); font-weight: 600;">
                    <td style="padding: 10px 0 10px 20px; color: var(--color-deep-navy);">Total Operating Expenses</td>
                    <td style="padding: 10px 0; text-align: right; color: #991B1B;">
                        (₱{{ number_format($totalExpenses, 2) }})</td>
                </tr>

                <!-- Spoilage Losses Section -->
                <tr>
                    <td colspan="2"
                        style="padding-top: 16px; font-weight: 700; color: var(--color-deep-navy); font-size: 0.9rem; text-transform: uppercase;">
                        Inventory Losses
                    </td>
                </tr>
                <tr style="font-size: 0.88rem; color: var(--text-muted);">
                    <td style="padding: 6px 0 6px 20px;">Approved Spoilage & Damaged Goods</td>
                    <td style="padding: 6px 0; text-align: right; color: #991B1B;">(₱{{ number_format($spoilageLoss, 2) }})
                    </td>
                </tr>

                <!-- Net Profit Section -->
                <tr
                    style="border-top: 3px double var(--color-deep-navy); background: #F8FBFE; font-weight: 900; font-size: 1.15rem;">
                    <td style="padding: 16px 14px; color: var(--color-deep-navy);">NET PROFIT (LOSS)</td>
                    <td
                        style="padding: 16px 14px; text-align: right; color: {{ $netProfit >= 0 ? '#065F46' : '#991B1B' }};">
                        ₱{{ number_format($netProfit, 2) }}
                    </td>
                </tr>
            </table>
        </div>
    </div>
@endsection
