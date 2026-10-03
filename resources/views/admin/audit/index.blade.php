@extends('layouts.app')

@section('title', 'Audit Trail Log')
@section('page_title', 'System Audit Trail & Traceability')

@section('content')
@php
    $categories = $categories ?? [];
    $customers = $customers ?? [];
    $suppliers = $suppliers ?? [];
    $warehouses = $warehouses ?? [];
    $users = $users ?? [];
    $products = $products ?? [];

    $formatVal = function($k, $v) use ($categories, $customers, $suppliers, $warehouses, $users, $products) {
        if ($v === null || $v === '') return '(empty)';
        if (is_bool($v)) return $v ? 'Active' : 'Inactive';
        if ($k === 'is_active' || ($k === 'status' && is_numeric($v))) return $v ? 'Active' : 'Inactive';

        // Lookup entity names for IDs
        if ($k === 'category_id') return $categories[$v] ?? "Category #{$v}";
        if ($k === 'customer_id') return $customers[$v] ?? "Customer #{$v}";
        if ($k === 'supplier_id') return $suppliers[$v] ?? "Supplier #{$v}";
        if ($k === 'warehouse_id') return $warehouses[$v] ?? "Warehouse #{$v}";
        if ($k === 'product_id') return $products[$v] ?? "Product #{$v}";
        if (in_array($k, ['user_id', 'created_by', 'received_by', 'approved_by', 'cashier_id'])) {
            return $users[$v] ?? "User #{$v}";
        }

        if (is_numeric($v) && in_array(strtolower($k), ['srp', 'default_srp', 'price', 'cost_price', 'last_purchase_cost', 'amount', 'total_amount', 'credit_limit', 'current_balance', 'outstanding_balance'])) {
            return '₱' . number_format((float)$v, 2);
        }
        if (is_array($v)) return implode(', ', array_map(fn($item) => is_array($item) ? json_encode($item) : (string)$item, $v));
        return (string)$v;
    };

    $formatKey = function($k) {
        $labels = [
            'category_id' => 'Category',
            'customer_id' => 'Customer',
            'supplier_id' => 'Supplier',
            'warehouse_id' => 'Warehouse',
            'user_id' => 'User',
            'product_id' => 'Product',
            'created_by' => 'Created By',
            'received_by' => 'Received By',
            'approved_by' => 'Approved By',
            'cashier_id' => 'Cashier',
            'srp' => 'SRP / Price',
            'default_srp' => 'Default SRP',
            'cost_price' => 'Cost Price',
            'last_purchase_cost' => 'Purchase Cost',
            'outstanding_balance' => 'Outstanding AP',
            'current_balance' => 'Outstanding AR',
            'payment_terms_days' => 'Terms (Days)',
            'payment_terms' => 'Payment Terms',
            'is_active' => 'Status',
            'base_unit' => 'Base Unit',
            'contact_number' => 'Contact Number',
            'mobile_number' => 'Mobile Number',
            'tin' => 'TIN',
            'bank_info' => 'Bank Info',
        ];
        return $labels[$k] ?? ucwords(str_replace('_', ' ', $k));
    };

    $ignoredKeys = ['password', 'remember_token', 'updated_at', 'created_at', 'id'];
@endphp

<div class="card">
    <div class="card-header">
        <div>
            <h2 class="card-title">System Audit Log</h2>
            <p class="card-description">Immutable chronological log of all sensitive business operations, pricing edits, and approvals</p>
        </div>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="custom-table datatable-init">
                <thead>
                    <tr>
                        <th>Timestamp</th>
                        <th>User</th>
                        <th>Action</th>
                        <th>Module / Target</th>
                        <th>Changes (Before & After)</th>
                        <th>Reason / Justification</th>
                        <th>IP Address</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($logs as $log)
                        @php
                            $before = is_array($log->before_values) ? array_diff_key($log->before_values, array_flip($ignoredKeys)) : [];
                            $after = is_array($log->after_values) ? array_diff_key($log->after_values, array_flip($ignoredKeys)) : [];
                            $allKeys = array_unique(array_merge(array_keys($before), array_keys($after)));
                        @endphp
                        <tr>
                            <td style="font-size: 0.8rem; color: var(--text-light); white-space: nowrap;">{{ $log->created_at->format('M d, Y h:i:s A') }}</td>
                            <td>
                                <strong>{{ $log->user->name ?? 'System' }}</strong>
                                <div style="font-size: 0.72rem; color: var(--color-wave-cyan); text-transform: uppercase;">{{ $log->user->role ?? '' }}</div>
                            </td>
                            <td>
                                <span class="badge badge-navy">{{ strtoupper(str_replace('_', ' ', $log->action)) }}</span>
                            </td>
                            <td style="font-size: 0.8rem;">
                                @php
                                    $modelBase = class_basename($log->model_type);
                                    $targetName = null;
                                    if ($modelBase === 'Product') $targetName = $products[$log->model_id] ?? null;
                                    elseif ($modelBase === 'Customer') $targetName = $customers[$log->model_id] ?? null;
                                    elseif ($modelBase === 'Supplier') $targetName = $suppliers[$log->model_id] ?? null;
                                    elseif ($modelBase === 'Category') $targetName = $categories[$log->model_id] ?? null;
                                    elseif ($modelBase === 'Warehouse') $targetName = $warehouses[$log->model_id] ?? null;
                                    elseif ($modelBase === 'User') $targetName = $users[$log->model_id] ?? null;
                                @endphp
                                <strong style="color: var(--color-deep-navy);">{{ $modelBase }} #{{ $log->model_id }}</strong>
                                @if($targetName)
                                    <div style="font-size: 0.74rem; color: var(--color-ocean-blue); font-weight: 600;">{{ $targetName }}</div>
                                @endif
                            </td>
                            <td style="font-size: 0.8rem; min-width: 220px;">
                                @if(!empty($allKeys))
                                    <div style="display: flex; flex-direction: column; gap: 4px;">
                                        @foreach($allKeys as $k)
                                            @php
                                                $hasBefore = array_key_exists($k, $before);
                                                $hasAfter = array_key_exists($k, $after);
                                                $oldVal = $hasBefore ? $before[$k] : null;
                                                $newVal = $hasAfter ? $after[$k] : null;
                                            @endphp

                                            @if($hasBefore && $hasAfter)
                                                @if($oldVal != $newVal)
                                                    <div style="font-size: 0.78rem; line-height: 1.35;">
                                                        <span style="font-weight: 700; color: var(--color-deep-navy);">{{ $formatKey($k) }}:</span>
                                                        <span style="background: #FEE2E2; color: #991B1B; padding: 1px 6px; border-radius: 4px; text-decoration: line-through; font-size: 0.74rem;">{{ $formatVal($k, $oldVal) }}</span>
                                                        <i class="bi bi-arrow-right text-muted mx-1" style="font-size: 0.7rem;"></i>
                                                        <span style="background: #D1FAE5; color: #065F46; padding: 1px 6px; border-radius: 4px; font-weight: 700; font-size: 0.74rem;">{{ $formatVal($k, $newVal) }}</span>
                                                    </div>
                                                @else
                                                    <div style="font-size: 0.78rem; line-height: 1.35;">
                                                        <span style="font-weight: 700; color: var(--color-deep-navy);">{{ $formatKey($k) }}:</span>
                                                        <span style="color: var(--text-main); font-weight: 500;">{{ $formatVal($k, $newVal) }}</span>
                                                    </div>
                                                @endif
                                            @elseif($hasAfter)
                                                <div style="font-size: 0.78rem; line-height: 1.35;">
                                                    <span style="font-weight: 700; color: var(--color-deep-navy);">{{ $formatKey($k) }}:</span>
                                                    <span style="background: #E0F2FE; color: #0284C7; padding: 1px 6px; border-radius: 4px; font-weight: 600; font-size: 0.74rem;">{{ $formatVal($k, $newVal) }}</span>
                                                </div>
                                            @elseif($hasBefore)
                                                <div style="font-size: 0.78rem; line-height: 1.35;">
                                                    <span style="font-weight: 700; color: var(--color-deep-navy);">{{ $formatKey($k) }}:</span>
                                                    <span style="background: #FEE2E2; color: #991B1B; padding: 1px 6px; border-radius: 4px; font-size: 0.74rem;">{{ $formatVal($k, $oldVal) }} <span style="font-size: 0.68rem;">(Removed)</span></span>
                                                </div>
                                            @endif
                                        @endforeach
                                    </div>
                                @else
                                    <span style="color: var(--text-light); font-size: 0.75rem; font-style: italic;">No value changes</span>
                                @endif
                            </td>
                            <td><em>{{ $log->reason ?? 'None specified' }}</em></td>
                            <td style="font-size: 0.75rem; color: var(--text-light);">{{ $log->ip_address }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
