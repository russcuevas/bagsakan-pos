@extends('layouts.app')

@section('title', 'Warehouse Locations & Stock Transfers')
@section('page_title', 'Warehouse Locations & Stock Transfers')

@section('content')
<div class="stats-grid" style="grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); margin-bottom: 24px;">
    <div class="stat-card accent-blue">
        <div class="stat-header">
            <span class="stat-label">Active Warehouses</span>
            <div class="stat-icon"><i class="bi bi-building text-primary"></i></div>
        </div>
        <div class="stat-value">{{ $warehouses->where('is_active', true)->count() }} Locations</div>
        <div class="stat-helper">Operational hubs & cold storages</div>
    </div>

    <div class="stat-card accent-green">
        <div class="stat-header">
            <span class="stat-label">Stock Transfers Logged</span>
            <div class="stat-icon"><i class="bi bi-arrow-left-right text-success"></i></div>
        </div>
        <div class="stat-value">{{ $transfers->total() }} Transfers</div>
        <div class="stat-helper">Inter-warehouse stock movements</div>
    </div>
</div>

<!-- Warehouses Grid -->
<div class="card" style="margin-bottom: 24px;">
    <div class="card-header">
        <div>
            <h2 class="card-title"><i class="bi bi-building me-2 text-primary"></i> Warehouse Hubs & Storage Rooms</h2>
            <p class="card-description">Manage warehouse locations and view active stock batches</p>
        </div>
        <div style="display: flex; gap: 8px;">
            <button type="button" class="btn btn-outline" onclick="openTransferModal()">
                <i class="bi bi-arrow-left-right me-1"></i> Stock Transfer
            </button>
            <button type="button" class="btn btn-primary" onclick="openCreateWarehouseModal()">
                <i class="bi bi-plus-lg me-1"></i> Add Warehouse
            </button>
        </div>
    </div>
    <div class="card-body">
        <div class="row g-3" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 16px;">
            @foreach($warehouses as $wh)
                <div style="background: var(--card-bg, #fff); border: 1px solid var(--card-border, #e2e8f0); border-radius: 10px; padding: 18px; border-top: 4px solid {{ $wh->is_active ? 'var(--color-primary-blue, #2563eb)' : '#94a3b8' }};">
                    <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 8px;">
                        <div>
                            <h3 style="font-size: 1.1rem; font-weight: 800; color: var(--color-deep-navy, #0f172a); margin: 0;">{{ $wh->name }}</h3>
                            <span class="badge badge-navy" style="margin-top: 4px;">{{ $wh->code }}</span>
                        </div>
                        <span class="badge {{ $wh->is_active ? 'badge-success' : 'badge-danger' }}">
                            {{ $wh->is_active ? 'Active Hub' : 'Inactive' }}
                        </span>
                    </div>

                    <p style="font-size: 0.82rem; color: var(--text-light, #64748b); margin-bottom: 12px;">
                        <i class="bi bi-geo-alt me-1"></i> {{ $wh->location ?: 'No physical address configured' }}
                    </p>

                    <div style="background: var(--color-mist-blue, #f8fafc); padding: 12px 14px; border-radius: 8px; border: 1px solid #e2e8f0; margin-bottom: 14px;">
                        <div style="display: flex; justify-content: space-between; font-size: 0.8rem; margin-bottom: 4px;">
                            <span style="color: var(--text-muted, #64748b);">Active Batches:</span>
                            <strong style="color: var(--color-deep-navy, #0f172a);">{{ $wh->active_batches_count }} batches</strong>
                        </div>
                        <div style="display: flex; justify-content: space-between; font-size: 0.8rem;">
                            <span style="color: var(--text-muted, #64748b);">Current Stock:</span>
                            <strong style="color: var(--color-primary-blue, #2563eb);">{{ number_format((float) $wh->receivingBatches()->where('status', 'active')->sum('current_quantity'), 2) }} base units</strong>
                        </div>
                    </div>

                    <div style="display: flex; gap: 8px;">
                        <button type="button" class="btn btn-sm btn-secondary" style="flex: 1;" onclick='openEditWarehouseModal(@json($wh))'>
                            <i class="bi bi-pencil me-1"></i> Edit
                        </button>
                        <button type="button" class="btn btn-sm btn-outline" onclick="openTransferModal({{ $wh->id }})" title="Transfer stock out">
                            <i class="bi bi-arrow-right me-1"></i> Transfer
                        </button>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</div>

<!-- Stock Transfers History Table -->
<div class="card">
    <div class="card-header">
        <div>
            <h3 class="card-title"><i class="bi bi-clock-history text-primary me-2"></i> Inter-Warehouse Transfer Log</h3>
            <p class="card-description">History of inventory transfers between hubs and cold storage rooms</p>
        </div>
        <button type="button" class="btn btn-sm btn-primary" onclick="openTransferModal()">
            <i class="bi bi-arrow-left-right me-1"></i> New Transfer
        </button>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="custom-table datatable-init">
                <thead>
                    <tr>
                        <th>Transfer #</th>
                        <th>Transfer Date</th>
                        <th>Source Hub (From)</th>
                        <th>Destination Hub (To)</th>
                        <th>Transferred Items</th>
                        <th>Status</th>
                        <th>Initiated By</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($transfers as $trf)
                        <tr>
                            <td>
                                <strong>{{ $trf->transfer_number }}</strong>
                                @if($trf->notes)
                                    <div style="font-size: 0.74rem; color: var(--text-light);">{{ $trf->notes }}</div>
                                @endif
                            </td>
                            <td style="font-size: 0.82rem; color: var(--text-light);">{{ $trf->transfer_date->format('M d, Y') }}</td>
                            <td>
                                <span class="badge badge-danger">
                                    <i class="bi bi-box-arrow-up-right me-1"></i> {{ $trf->fromWarehouse->name ?? 'N/A' }}
                                </span>
                            </td>
                            <td>
                                <span class="badge badge-success">
                                    <i class="bi bi-box-arrow-in-down-left me-1"></i> {{ $trf->toWarehouse->name ?? 'N/A' }}
                                </span>
                            </td>
                            <td>
                                @foreach($trf->items as $tItem)
                                    <div style="font-size: 0.8rem;">
                                        <strong>{{ $tItem->product->sku ?? '' }}</strong>: {{ (float)$tItem->quantity }} {{ $tItem->unit_name }}
                                    </div>
                                @endforeach
                            </td>
                            <td>
                                <span class="badge badge-primary">{{ strtoupper($trf->status) }}</span>
                            </td>
                            <td>
                                <div style="font-size: 0.8rem;">{{ $trf->creator->name ?? 'Admin' }}</div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- CREATE / EDIT WAREHOUSE MODAL -->
<div id="warehouseModal" class="modal-overlay">
    <div class="modal-dialog">
        <div class="modal-header">
            <h3 class="modal-title" id="whModalTitle"><i class="bi bi-building me-2 text-primary"></i> Add Warehouse Location</h3>
            <button type="button" class="modal-close-btn" data-close-modal="warehouseModal"><i class="bi bi-x-lg"></i></button>
        </div>
        <form id="warehouseForm" onsubmit="submitWarehouse(event)">
            @csrf
            <input type="hidden" id="whId">
            <div class="modal-body">
                <div class="form-group">
                    <label class="form-label" for="whName">Warehouse Name <span class="text-danger">*</span></label>
                    <input type="text" name="name" id="whName" class="form-control" placeholder="e.g. Cold Storage Hub 2 / Divisoria Branch" required>
                </div>
                <div class="form-group">
                    <label class="form-label" for="whCode">Warehouse Code <span class="text-danger">*</span></label>
                    <input type="text" name="code" id="whCode" class="form-control" placeholder="e.g. WH-COLD-02" required>
                </div>
                <div class="form-group">
                    <label class="form-label" for="whLocation">Location Address</label>
                    <input type="text" name="location" id="whLocation" class="form-control" placeholder="Physical location address">
                </div>
                <div class="form-group">
                    <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                        <input type="checkbox" name="is_active" value="1" id="whIsActive" checked style="width: 18px; height: 18px;">
                        <span style="font-weight: 600; color: var(--color-deep-navy);">Active Warehouse Hub</span>
                    </label>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline" data-close-modal="warehouseModal">Cancel</button>
                <button type="submit" class="btn btn-primary" id="saveWhBtn"><i class="bi bi-check-lg me-1"></i> Save Warehouse</button>
            </div>
        </form>
    </div>
</div>

<!-- INTER-WAREHOUSE STOCK TRANSFER MODAL -->
<div id="transferStockModal" class="modal-overlay">
    <div class="modal-dialog modal-lg" style="max-width: 780px;">
        <div class="modal-header">
            <h3 class="modal-title"><i class="bi bi-arrow-left-right me-2 text-primary"></i> Inter-Warehouse Stock Transfer</h3>
            <button type="button" class="modal-close-btn" data-close-modal="transferStockModal"><i class="bi bi-x-lg"></i></button>
        </div>
        <form id="transferStockForm" onsubmit="submitStockTransfer(event)">
            @csrf
            <div class="modal-body">
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 16px;">
                    <div class="form-group">
                        <label class="form-label" for="trfFromWh">Source Warehouse (From) <span class="text-danger">*</span></label>
                        <select name="from_warehouse_id" id="trfFromWh" class="form-select" required>
                            <option value="">Select source warehouse...</option>
                            @foreach($warehouses as $w)
                                <option value="{{ $w->id }}">{{ $w->name }} ({{ $w->code }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="trfToWh">Destination Warehouse (To) <span class="text-danger">*</span></label>
                        <select name="to_warehouse_id" id="trfToWh" class="form-select" required>
                            <option value="">Select destination warehouse...</option>
                            @foreach($warehouses as $w)
                                <option value="{{ $w->id }}">{{ $w->name }} ({{ $w->code }})</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 16px;">
                    <div class="form-group">
                        <label class="form-label" for="trfDate">Transfer Date <span class="text-danger">*</span></label>
                        <input type="date" name="transfer_date" id="trfDate" class="form-control" value="{{ date('Y-m-d') }}" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="trfNotes">Transfer Notes / DR Ref</label>
                        <input type="text" name="notes" id="trfNotes" class="form-control" placeholder="e.g. Stock balancing for Branch B">
                    </div>
                </div>

                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                    <span style="font-weight: 700; color: var(--color-deep-navy); font-size: 0.9rem;">
                        <i class="bi bi-boxes me-1 text-primary"></i> Line Items to Transfer
                    </span>
                    <button type="button" class="btn btn-sm btn-outline" onclick="addTransferRow()">
                        <i class="bi bi-plus-circle me-1"></i> Add Line Item
                    </button>
                </div>

                <div class="table-responsive" style="border: 1px solid var(--card-border); border-radius: 8px; margin-bottom: 16px;">
                    <table class="table mb-0" style="width: 100%;">
                        <thead style="background: var(--color-mist-blue); font-size: 0.8rem; text-transform: uppercase;">
                            <tr>
                                <th style="padding: 10px; width: 45%;">Product SKU</th>
                                <th style="padding: 10px; width: 25%;">Unit</th>
                                <th style="padding: 10px; width: 20%;">Qty to Move</th>
                                <th style="padding: 10px; width: 10%; text-align: center;">Action</th>
                            </tr>
                        </thead>
                        <tbody id="transferRowsContainer">
                            <!-- Transfer item rows -->
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline" data-close-modal="transferStockModal">Cancel</button>
                <button type="submit" class="btn btn-primary" id="submitTrfBtn">
                    <i class="bi bi-check-lg me-1"></i> Execute Stock Transfer
                </button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
const productsData = @json($products);
let trfRowIdx = 0;

function openCreateWarehouseModal() {
    document.getElementById('warehouseForm').reset();
    document.getElementById('whId').value = '';
    document.getElementById('whModalTitle').innerHTML = '<i class="bi bi-building me-2 text-primary"></i> Add Warehouse Location';
    openModal('warehouseModal');
}

function openEditWarehouseModal(wh) {
    document.getElementById('warehouseForm').reset();
    document.getElementById('whId').value = wh.id;
    document.getElementById('whName').value = wh.name;
    document.getElementById('whCode').value = wh.code;
    document.getElementById('whLocation').value = wh.location || '';
    document.getElementById('whIsActive').checked = wh.is_active;
    document.getElementById('whModalTitle').innerHTML = '<i class="bi bi-pencil me-2 text-primary"></i> Edit Warehouse: ' + wh.name;
    openModal('warehouseModal');
}

async function submitWarehouse(e) {
    e.preventDefault();
    const id = document.getElementById('whId').value;
    const url = id ? `/admin/warehouses/${id}` : `/admin/warehouses`;
    const method = id ? 'PUT' : 'POST';

    const form = document.getElementById('warehouseForm');
    const formData = new FormData(form);
    const dataObj = Object.fromEntries(formData.entries());
    dataObj.is_active = document.getElementById('whIsActive').checked ? 1 : 0;

    const btn = document.getElementById('saveWhBtn');
    btn.disabled = true;

    try {
        const res = await fetch(url, {
            method: method,
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            },
            body: JSON.stringify(dataObj)
        });
        const data = await res.json();
        if (data.success) {
            closeModal('warehouseModal');
            showToast('success', 'Success', data.message);
            setTimeout(() => location.reload(), 600);
        } else {
            showToast('error', 'Error', data.message || 'Error saving warehouse.');
            btn.disabled = false;
        }
    } catch (err) {
        showToast('error', 'Error', err.message);
        btn.disabled = false;
    }
}

function openTransferModal(fromWhId = null) {
    document.getElementById('transferStockForm').reset();
    document.getElementById('transferRowsContainer').innerHTML = '';
    trfRowIdx = 0;
    if (fromWhId) {
        document.getElementById('trfFromWh').value = fromWhId;
    }
    addTransferRow();
    openModal('transferStockModal');
}

function addTransferRow() {
    const idx = trfRowIdx++;
    const tr = document.createElement('tr');
    tr.id = `trf_row_${idx}`;
    tr.innerHTML = `
        <td style="padding: 8px 10px;">
            <select name="items[${idx}][product_id]" class="form-select form-select-sm" onchange="onTrfProductChange(${idx}, this)" required>
                <option value="">-- Select Product --</option>
                ${productsData.map(p => `<option value="${p.id}" data-baseunit="${p.base_unit}">${p.sku} - ${p.name}</option>`).join('')}
            </select>
            <input type="hidden" name="items[${idx}][conversion_factor]" id="trf_conv_${idx}" value="1">
        </td>
        <td style="padding: 8px 10px;">
            <select name="items[${idx}][unit_name]" id="trf_unit_${idx}" class="form-select form-select-sm" onchange="onTrfUnitChange(${idx}, this)" required>
                <option value="Base Unit">Base Unit</option>
            </select>
        </td>
        <td style="padding: 8px 10px;">
            <input type="number" name="items[${idx}][quantity]" class="form-control form-control-sm text-center" value="1" min="0.01" step="any" required>
        </td>
        <td style="padding: 8px 10px; text-align: center;">
            <button type="button" class="btn btn-sm btn-outline-danger" onclick="document.getElementById('trf_row_${idx}').remove()">
                <i class="bi bi-trash"></i>
            </button>
        </td>
    `;
    document.getElementById('transferRowsContainer').appendChild(tr);
}

function onTrfProductChange(idx, select) {
    const pId = select.value;
    if (!pId) return;
    const prod = productsData.find(p => p.id == pId);
    if (!prod) return;

    document.getElementById(`trf_conv_${idx}`).value = 1;
    const unitSelect = document.getElementById(`trf_unit_${idx}`);
    unitSelect.innerHTML = `<option value="${prod.base_unit}" data-factor="1">${prod.base_unit} (Base)</option>`;
    if (prod.units && prod.units.length > 0) {
        prod.units.forEach(u => {
            unitSelect.innerHTML += `<option value="${u.unit_name}" data-factor="${u.conversion_factor}">${u.unit_name}</option>`;
        });
    }
}

function onTrfUnitChange(idx, select) {
    const factor = select.options[select.selectedIndex]?.getAttribute('data-factor') || 1;
    document.getElementById(`trf_conv_${idx}`).value = factor;
}

async function submitStockTransfer(e) {
    e.preventDefault();
    const fromWh = document.getElementById('trfFromWh').value;
    const toWh = document.getElementById('trfToWh').value;
    if (fromWh === toWh) {
        showToast('error', 'Validation Error', 'Source and destination warehouse cannot be the same!');
        return;
    }

    const btn = document.getElementById('submitTrfBtn');
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Processing...';

    const form = document.getElementById('transferStockForm');
    const formData = new FormData(form);

    try {
        const res = await fetch("{{ route('admin.warehouses.transfer') }}", {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Accept': 'application/json'
            },
            body: formData
        });
        const data = await res.json();
        if (data.success) {
            closeModal('transferStockModal');
            showToast('success', 'Transfer Completed', data.message);
            setTimeout(() => location.reload(), 600);
        } else {
            showToast('error', 'Transfer Failed', data.message || 'Error processing transfer.');
            btn.disabled = false;
            btn.innerHTML = '<i class="bi bi-check-lg me-1"></i> Execute Stock Transfer';
        }
    } catch (err) {
        showToast('error', 'Error', err.message);
        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-check-lg me-1"></i> Execute Stock Transfer';
    }
}
</script>
@endpush
@endsection
