@extends('layouts.app')

@section('title', 'Warehouse Inventory')
@section('page_title', 'Warehouse Stock & Active Batches')

@section('content')
<div class="card">
    <div class="card-header">
        <div>
            <h2 class="card-title">Warehouse Inventory Directory</h2>
            <p class="card-description">Stock counts by base unit and location</p>
        </div>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="custom-table datatable-init">
                <thead>
                    <tr>
                        <th>Product SKU</th>
                        <th>Category</th>
                        <th>Available Stock</th>
                        <th>Reorder Threshold</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($products as $p)
                        @php $stk = $p->available_stock; @endphp
                        <tr>
                            <td>
                                <strong>{{ $p->name }}</strong>
                                <div style="font-size: 0.74rem; color: var(--text-light);">{{ $p->sku }} &bull; {{ $p->barcode ?? 'No Barcode' }}</div>
                            </td>
                            <td><span class="badge badge-navy">{{ $p->category->name ?? 'General' }}</span></td>
                            <td style="font-weight: 800; font-size: 1rem; color: var(--color-deep-navy);">
                                {{ number_format($stk, 2) }} {{ $p->base_unit }}
                            </td>
                            <td>{{ $p->low_stock_threshold }} {{ $p->base_unit }}</td>
                            <td>
                                @if($stk <= 0)
                                    <span class="badge badge-danger">Out of Stock</span>
                                @elseif($stk <= $p->low_stock_threshold)
                                    <span class="badge badge-warning">Low Stock Warning</span>
                                @else
                                    <span class="badge badge-success">In Stock</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Active Batches for Staff -->
<div class="card">
    <div class="card-header">
        <div>
            <h3 class="card-title">Stock Batches on Hand</h3>
            <p class="card-description">Active supplier lots stored in warehouse locations</p>
        </div>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="custom-table datatable-init">
                <thead>
                    <tr>
                        <th>Batch Code</th>
                        <th>Product SKU</th>
                        <th>Supplier</th>
                        <th>Warehouse</th>
                        <th>DR #</th>
                        <th>Available Quantity</th>
                        <th>Receipt Date</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($batches as $b)
                        <tr>
                            <td><strong>{{ $b->batch_code }}</strong></td>
                            <td>{{ $b->product->name ?? 'N/A' }} ({{ $b->product->sku ?? '' }})</td>
                            <td>{{ $b->supplier->name ?? 'N/A' }}</td>
                            <td><span class="badge badge-navy">{{ $b->warehouse->name ?? 'Main' }}</span></td>
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
@endsection
