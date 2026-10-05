@extends('layouts.app')

@section('title', 'Staff Purchase Requests')
@section('page_title', 'Staff Purchase Requests')

@section('content')
<div class="stats-grid" style="grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); margin-bottom: 24px;">
    <div class="stat-card accent-blue">
        <div class="stat-header">
            <span class="stat-label">My Purchase Requests</span>
            <div class="stat-icon"><i class="bi bi-cart-plus text-primary"></i></div>
        </div>
        <div class="stat-value">{{ $purchaseRequests->total() }} Filed</div>
        <div class="stat-helper">Requisition status ledger</div>
    </div>

    <div class="stat-card accent-amber">
        <div class="stat-header">
            <span class="stat-label">Pending Approval</span>
            <div class="stat-icon"><i class="bi bi-hourglass-split text-warning"></i></div>
        </div>
        <div class="stat-value">{{ \App\Models\PurchaseRequest::where('status', 'submitted')->count() }}</div>
        <div class="stat-helper">Under management review</div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <div>
            <h2 class="card-title">Purchase Requests (PR)</h2>
            <p class="card-description">Submit purchasing requisitions for supervisor review and PO conversion</p>
        </div>
        <button type="button" class="btn btn-primary" onclick="openCreatePRModal()">
            <i class="bi bi-plus-lg me-1"></i> New Purchase Request
        </button>
    </div>
    <div class="card-body">
        <div style="background: var(--color-mist-blue); border: 1px solid var(--card-border); border-radius: 8px; padding: 14px; margin-bottom: 20px;">
            <form method="GET" action="{{ route('staff.purchase-requests.index') }}" style="display: grid; grid-template-columns: 1fr auto; gap: 12px; align-items: center;">
                <div>
                    <select name="status" class="form-select form-select-sm">
                        <option value="">All Statuses</option>
                        <option value="draft" {{ request('status') === 'draft' ? 'selected' : '' }}>Draft</option>
                        <option value="submitted" {{ request('status') === 'submitted' ? 'selected' : '' }}>Submitted (Pending Approval)</option>
                        <option value="approved" {{ request('status') === 'approved' ? 'selected' : '' }}>Approved</option>
                        <option value="rejected" {{ request('status') === 'rejected' ? 'selected' : '' }}>Rejected</option>
                        <option value="returned" {{ request('status') === 'returned' ? 'selected' : '' }}>Returned for Revision</option>
                        <option value="converted_to_po" {{ request('status') === 'converted_to_po' ? 'selected' : '' }}>Converted to PO</option>
                    </select>
                </div>
                <div style="display: flex; gap: 6px;">
                    <button type="submit" class="btn btn-sm btn-secondary"><i class="bi bi-filter"></i> Filter</button>
                    <a href="{{ route('staff.purchase-requests.index') }}" class="btn btn-sm btn-outline">Reset</a>
                </div>
            </form>
        </div>

        <div class="table-responsive">
            <table class="custom-table datatable-init">
                <thead>
                    <tr>
                        <th>PR Number</th>
                        <th>Request Date</th>
                        <th>Destination Warehouse</th>
                        <th>Items & Suppliers</th>
                        <th>Est. Total</th>
                        <th>Status</th>
                        <th>Reviewer Notes</th>
                        <th style="text-align: right;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($purchaseRequests as $pr)
                        @php
                            $uniqueSuppliersCount = $pr->items->pluck('supplier_id')->unique()->filter()->count();
                        @endphp
                        <tr>
                            <td>
                                <strong>{{ $pr->pr_number }}</strong>
                                @if($pr->purpose)
                                    <div style="font-size: 0.74rem; color: var(--text-light); max-width: 180px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">{{ $pr->purpose }}</div>
                                @endif
                            </td>
                            <td style="font-size: 0.8rem; color: var(--text-light);">
                                <div>{{ $pr->request_date->format('M d, Y') }}</div>
                                @if($pr->needed_by_date)
                                    <div style="font-size: 0.72rem; color: #64748b;">Needed: {{ $pr->needed_by_date->format('M d, Y') }}</div>
                                @endif
                            </td>
                            <td>{{ $pr->warehouse->name ?? 'Default Warehouse' }}</td>
                            <td>
                                <span class="badge badge-navy">{{ $pr->items->count() }} item(s)</span>
                                @if($uniqueSuppliersCount > 1)
                                    <span class="badge badge-warning">
                                        <i class="bi bi-diagram-3 me-1"></i> {{ $uniqueSuppliersCount }} Suppliers
                                    </span>
                                @endif
                            </td>
                            <td style="font-weight: 800; color: var(--color-deep-navy);">
                                ₱{{ number_format($pr->total_estimated_amount, 2) }}
                            </td>
                            <td>
                                @php
                                    $badge = match($pr->status) {
                                        'draft' => 'badge-navy',
                                        'submitted' => 'badge-warning',
                                        'approved' => 'badge-success',
                                        'rejected' => 'badge-danger',
                                        'returned' => 'badge-primary',
                                        'converted_to_po' => 'badge-success',
                                        default => 'badge-navy'
                                    };
                                @endphp
                                <span class="badge {{ $badge }}">{{ strtoupper(str_replace('_', ' ', $pr->status)) }}</span>
                            </td>
                            <td>
                                @if($pr->rejection_reason)
                                    <div style="font-size: 0.75rem; color: #dc2626;"><i class="bi bi-exclamation-circle me-1"></i> {{ $pr->rejection_reason }}</div>
                                @elseif($pr->revision_notes)
                                    <div style="font-size: 0.75rem; color: #0284c7;"><i class="bi bi-info-circle me-1"></i> {{ $pr->revision_notes }}</div>
                                @else
                                    <span style="font-size: 0.75rem; color: var(--text-light);">None</span>
                                @endif
                            </td>
                            <td style="text-align: right;">
                                @if($pr->status === 'returned')
                                    <form action="{{ route('staff.purchase-requests.resubmit', $pr->id) }}" method="POST" style="display: inline;" onsubmit="return confirm('Resubmit this PR for approval?')">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-outline">
                                            <i class="bi bi-arrow-clockwise me-1"></i> Resubmit
                                        </button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- CREATE PR MODAL -->
<div id="createPRModal" class="modal-overlay">
    <div class="modal-dialog modal-xl" style="max-width: 1050px;">
        <div class="modal-header">
            <h3 class="modal-title"><i class="bi bi-cart-plus me-2 text-primary"></i> Submit Purchase Request (PR)</h3>
            <button type="button" class="modal-close-btn" data-close-modal="createPRModal"><i class="bi bi-x-lg"></i></button>
        </div>
        <form id="createPRForm" onsubmit="submitCreatePR(event)">
            @csrf
            <div class="modal-body">
                <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 14px; margin-bottom: 14px;">
                    <div class="form-group">
                        <label class="form-label" for="staffPrWarehouse">Destination Warehouse <span class="text-danger">*</span></label>
                        <select name="warehouse_id" id="staffPrWarehouse" class="form-select" required>
                            @foreach($warehouses as $w)
                                <option value="{{ $w->id }}">{{ $w->name }} ({{ $w->code }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="staffPrDate">Request Date <span class="text-danger">*</span></label>
                        <input type="date" name="request_date" id="staffPrDate" class="form-control" value="{{ date('Y-m-d') }}" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="staffPrNeededDate">Needed By Date</label>
                        <input type="date" name="needed_by_date" id="staffPrNeededDate" class="form-control" value="{{ date('Y-m-d', strtotime('+7 days')) }}">
                    </div>
                </div>

                <div class="form-group" style="margin-bottom: 16px;">
                    <label class="form-label" for="staffPrPurpose">Purpose / Justification</label>
                    <input type="text" name="purpose" id="staffPrPurpose" class="form-control" placeholder="e.g. Stock replenishment for wholesale clients">
                </div>

                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                    <span style="font-weight: 700; color: var(--color-deep-navy); font-size: 0.9rem;">
                        <i class="bi bi-box-seam me-1 text-primary"></i> Requested Line Items
                    </span>
                    <button type="button" class="btn btn-sm btn-outline" onclick="addPRRow()">
                        <i class="bi bi-plus-circle me-1"></i> Add Line Item
                    </button>
                </div>

                <div class="table-responsive" style="border: 1px solid var(--card-border); border-radius: 8px; margin-bottom: 16px;">
                    <table class="table mb-0" style="width: 100%;">
                        <thead style="background: var(--color-mist-blue); font-size: 0.8rem; text-transform: uppercase;">
                            <tr>
                                <th style="padding: 10px; width: 25%;">SKU / Product</th>
                                <th style="padding: 10px; width: 25%;">Target Supplier <span class="text-danger">*</span></th>
                                <th style="padding: 10px; width: 12%;">Unit</th>
                                <th style="padding: 10px; width: 10%;">Quantity</th>
                                <th style="padding: 10px; width: 13%;">Est. Unit Cost</th>
                                <th style="padding: 10px; width: 10%;">Subtotal</th>
                                <th style="padding: 10px; width: 5%; text-align: center;">Action</th>
                            </tr>
                        </thead>
                        <tbody id="prRowsContainer">
                            <!-- Populated dynamically -->
                        </tbody>
                    </table>
                </div>

                <div style="display: grid; grid-template-columns: 1.2fr 0.8fr; gap: 16px;">
                    <div class="form-group">
                        <label class="form-label" for="staffPrRemarks">Remarks / Notes</label>
                        <textarea name="remarks" id="staffPrRemarks" class="form-control" rows="2" placeholder="Notes for supervisor review..."></textarea>
                    </div>
                    <div style="background: var(--color-mist-blue); border: 1px solid var(--card-border); border-radius: 8px; padding: 14px; text-align: right;">
                        <span style="font-size: 0.85rem; color: var(--text-muted);">Total Estimated Amount:</span>
                        <div style="font-size: 1.3rem; font-weight: 800; color: var(--color-primary-blue);" id="prTotalDisplay">₱0.00</div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline" data-close-modal="createPRModal">Cancel</button>
                <button type="submit" class="btn btn-primary" id="savePRBtn">
                    <i class="bi bi-check-lg me-1"></i> Submit for Approval
                </button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
const productsData = @json($products);
const suppliersData = @json($suppliers);
let prRowIdx = 0;

function openCreatePRModal() {
    document.getElementById('createPRForm').reset();
    document.getElementById('prRowsContainer').innerHTML = '';
    prRowIdx = 0;
    addPRRow();
    calculatePRTotal();
    openModal('createPRModal');
}

function addPRRow() {
    const idx = prRowIdx++;
    const tr = document.createElement('tr');
    tr.id = `pr_row_${idx}`;
    tr.innerHTML = `
        <td style="padding: 8px 10px;">
            <select name="items[${idx}][product_id]" class="form-select form-select-sm" onchange="onPRProductChange(${idx}, this)" required>
                <option value="">-- Choose Product --</option>
                ${productsData.map(p => `<option value="${p.id}" data-cost="${p.average_cost}" data-baseunit="${p.base_unit}">${p.sku} - ${p.name}</option>`).join('')}
            </select>
            <input type="hidden" name="items[${idx}][conversion_factor]" id="pr_conv_${idx}" value="1">
        </td>
        <td style="padding: 8px 10px;">
            <select name="items[${idx}][supplier_id]" id="pr_supplier_${idx}" class="form-select form-select-sm" required>
                <option value="">-- Select Supplier --</option>
                ${suppliersData.map(s => `<option value="${s.id}">${s.name}</option>`).join('')}
            </select>
        </td>
        <td style="padding: 8px 10px;">
            <select name="items[${idx}][unit_name]" id="pr_unit_${idx}" class="form-select form-select-sm" onchange="onPRUnitChange(${idx}, this)" required>
                <option value="Base Unit">Base Unit</option>
            </select>
        </td>
        <td style="padding: 8px 10px;">
            <input type="number" name="items[${idx}][quantity]" id="pr_qty_${idx}" class="form-control form-control-sm text-center" value="1" min="0.01" step="any" oninput="calculatePRRow(${idx})" required>
        </td>
        <td style="padding: 8px 10px;">
            <input type="number" name="items[${idx}][estimated_unit_cost]" id="pr_cost_${idx}" class="form-control form-control-sm text-end" value="0.00" min="0" step="0.01" oninput="calculatePRRow(${idx})" required>
        </td>
        <td style="padding: 8px 10px;">
            <strong class="d-block text-end" id="pr_subtotal_${idx}">₱0.00</strong>
        </td>
        <td style="padding: 8px 10px; text-align: center;">
            <button type="button" class="btn btn-sm btn-outline-danger" onclick="document.getElementById('pr_row_${idx}').remove(); calculatePRTotal();">
                <i class="bi bi-trash"></i>
            </button>
        </td>
    `;
    document.getElementById('prRowsContainer').appendChild(tr);
}

function onPRProductChange(idx, select) {
    const pId = select.value;
    if (!pId) return;
    const prod = productsData.find(p => p.id == pId);
    if (!prod) return;

    document.getElementById(`pr_cost_${idx}`).value = parseFloat(prod.average_cost || 0).toFixed(2);
    document.getElementById(`pr_conv_${idx}`).value = 1;

    const unitSelect = document.getElementById(`pr_unit_${idx}`);
    unitSelect.innerHTML = `<option value="${prod.base_unit}" data-factor="1">${prod.base_unit} (Base)</option>`;
    if (prod.units && prod.units.length > 0) {
        prod.units.forEach(u => {
            unitSelect.innerHTML += `<option value="${u.unit_name}" data-factor="${u.conversion_factor}">${u.unit_name}</option>`;
        });
    }

    if (prod.suppliers && prod.suppliers.length > 0) {
        document.getElementById(`pr_supplier_${idx}`).value = prod.suppliers[0].id;
    }

    calculatePRRow(idx);
}

function onPRUnitChange(idx, select) {
    const factor = select.options[select.selectedIndex]?.getAttribute('data-factor') || 1;
    document.getElementById(`pr_conv_${idx}`).value = factor;
    calculatePRRow(idx);
}

function calculatePRRow(idx) {
    const qty = parseFloat(document.getElementById(`pr_qty_${idx}`)?.value || 0);
    const cost = parseFloat(document.getElementById(`pr_cost_${idx}`)?.value || 0);
    const subtotal = qty * cost;
    const display = document.getElementById(`pr_subtotal_${idx}`);
    if (display) {
        display.innerText = '₱' + subtotal.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        display.setAttribute('data-val', subtotal);
    }
    calculatePRTotal();
}

function calculatePRTotal() {
    let total = 0;
    document.querySelectorAll('[id^="pr_subtotal_"]').forEach(el => {
        total += parseFloat(el.getAttribute('data-val') || 0);
    });
    document.getElementById('prTotalDisplay').innerText = '₱' + total.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}

async function submitCreatePR(e) {
    e.preventDefault();
    const btn = document.getElementById('savePRBtn');
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Submitting...';

    const form = document.getElementById('createPRForm');
    const formData = new FormData(form);

    try {
        const res = await fetch("{{ route('staff.purchase-requests.store') }}", {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Accept': 'application/json'
            },
            body: formData
        });
        const data = await res.json();
        if (data.success) {
            closeModal('createPRModal');
            showToast('success', 'Submitted', data.message);
            setTimeout(() => location.reload(), 600);
        } else {
            showToast('error', 'Error', data.message || 'Error creating PR.');
            btn.disabled = false;
            btn.innerHTML = '<i class="bi bi-check-lg me-1"></i> Submit for Approval';
        }
    } catch (err) {
        showToast('error', 'Error', err.message);
        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-check-lg me-1"></i> Submit for Approval';
    }
}
</script>
@endpush
@endsection
