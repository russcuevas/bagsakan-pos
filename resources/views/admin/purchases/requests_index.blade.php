@extends('layouts.app')

@section('title', 'Purchase Requests & Approvals')
@section('page_title', 'Purchase Requests Management')

@section('content')
<div class="stats-grid" style="grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); margin-bottom: 24px;">
    <div class="stat-card accent-blue">
        <div class="stat-header">
            <span class="stat-label">Total PRs Filed</span>
            <div class="stat-icon"><i class="bi bi-clipboard-check text-primary"></i></div>
        </div>
        <div class="stat-value">{{ $purchaseRequests->total() }} Requests</div>
        <div class="stat-helper">Overall purchase requests</div>
    </div>

    <div class="stat-card accent-amber">
        <div class="stat-header">
            <span class="stat-label">Pending Approval</span>
            <div class="stat-icon"><i class="bi bi-hourglass-split text-warning"></i></div>
        </div>
        <div class="stat-value">{{ \App\Models\PurchaseRequest::where('status', 'submitted')->count() }}</div>
        <div class="stat-helper">Awaiting supervisor action</div>
    </div>

    <div class="stat-card accent-green">
        <div class="stat-header">
            <span class="stat-label">Approved & Ready for PO</span>
            <div class="stat-icon"><i class="bi bi-check-circle text-success"></i></div>
        </div>
        <div class="stat-value">{{ \App\Models\PurchaseRequest::where('status', 'approved')->count() }}</div>
        <div class="stat-helper">Ready to split into POs</div>
    </div>
</div>

<!-- PR Registry Card -->
<div class="card">
    <div class="card-header">
        <div>
            <h2 class="card-title">Purchase Requests & Approvals</h2>
            <p class="card-description">Review, modify, approve multi-supplier PRs and split into Purchase Orders (PO)</p>
        </div>
        <button type="button" class="btn btn-primary" onclick="openCreatePRModal()">
            <i class="bi bi-plus-lg me-1"></i> New Purchase Request
        </button>
    </div>
    <div class="card-body">
        <!-- Filter Bar -->
        <div style="background: var(--color-mist-blue); border: 1px solid var(--card-border); border-radius: 8px; padding: 14px; margin-bottom: 20px;">
            <form method="GET" action="{{ route('admin.purchase-requests.index') }}" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)) auto; gap: 12px; align-items: center;">
                <div>
                    <input type="text" name="search" class="form-control form-control-sm" placeholder="Search PR # or Purpose..." value="{{ request('search') }}">
                </div>
                <div>
                    <select name="status" class="form-select form-select-sm">
                        <option value="">All PR Statuses</option>
                        <option value="draft" {{ request('status') === 'draft' ? 'selected' : '' }}>Draft</option>
                        <option value="submitted" {{ request('status') === 'submitted' ? 'selected' : '' }}>Submitted (Pending Approval)</option>
                        <option value="approved" {{ request('status') === 'approved' ? 'selected' : '' }}>Approved (Ready for PO)</option>
                        <option value="rejected" {{ request('status') === 'rejected' ? 'selected' : '' }}>Rejected</option>
                        <option value="returned" {{ request('status') === 'returned' ? 'selected' : '' }}>Returned for Revision</option>
                        <option value="converted_to_po" {{ request('status') === 'converted_to_po' ? 'selected' : '' }}>Converted to PO</option>
                    </select>
                </div>
                <div>
                    <select name="warehouse_id" class="form-select form-select-sm">
                        <option value="">All Warehouses</option>
                        @foreach($warehouses as $w)
                            <option value="{{ $w->id }}" {{ request('warehouse_id') == $w->id ? 'selected' : '' }}>{{ $w->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div style="display: flex; gap: 6px;">
                    <button type="submit" class="btn btn-sm btn-secondary"><i class="bi bi-filter"></i> Filter</button>
                    <a href="{{ route('admin.purchase-requests.index') }}" class="btn btn-sm btn-outline">Reset</a>
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
                        <th>Estimated Total</th>
                        <th>Status</th>
                        <th>Requested By</th>
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
                                <span class="badge badge-navy">{{ $pr->items->count() }} line(s)</span>
                                @if($uniqueSuppliersCount > 1)
                                    <span class="badge badge-warning" title="Multi-Supplier PR: Will be split into {{ $uniqueSuppliersCount }} separate POs upon conversion">
                                        <i class="bi bi-diagram-3 me-1"></i> {{ $uniqueSuppliersCount }} Suppliers (Split PO)
                                    </span>
                                @elseif($uniqueSuppliersCount === 1)
                                    <span class="badge badge-navy">{{ $pr->items->first()->supplier->name ?? 'Single Supplier' }}</span>
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
                                @if($pr->status === 'converted_to_po' && $pr->purchases->isNotEmpty())
                                    <div style="font-size: 0.72rem; margin-top: 4px;">
                                        @foreach($pr->purchases as $po)
                                            <a href="{{ route('admin.purchases.index', ['search' => $po->purchase_number]) }}" class="badge badge-navy text-decoration-none" style="display: inline-block; margin-right: 2px;">
                                                {{ $po->purchase_number }}
                                            </a>
                                        @endforeach
                                    </div>
                                @endif
                            </td>
                            <td style="font-size: 0.8rem;">
                                {{ $pr->requester->name ?? 'Staff' }}
                            </td>
                            <td style="text-align: right;">
                                <div style="display: inline-flex; gap: 4px; align-items: center;">
                                    <!-- View Details -->
                                    <button type="button" class="btn btn-sm btn-secondary" onclick="viewPRDetails({{ $pr->id }})" title="View PR Details">
                                        <i class="bi bi-eye"></i>
                                    </button>

                                    <!-- Review / Approval Modal -->
                                    @if(in_array($pr->status, ['submitted', 'draft', 'returned']))
                                        <button type="button" class="btn btn-sm btn-warning" onclick="openReviewPRModal({{ $pr->id }})" title="Review & Approve / Modify PR">
                                            <i class="bi bi-pencil-square me-1"></i> Review
                                        </button>
                                    @endif

                                    <!-- Convert to Split POs Button -->
                                    @if($pr->status === 'approved')
                                        <button type="button" class="btn btn-sm btn-success" onclick="convertPRToPOs({{ $pr->id }}, '{{ $pr->pr_number }}', {{ $uniqueSuppliersCount }})" title="Generate Split POs by Supplier">
                                            <i class="bi bi-cart-check me-1"></i> Generate PO(s)
                                        </button>
                                    @endif
                                </div>
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
            <h3 class="modal-title"><i class="bi bi-cart-plus me-2 text-primary"></i> Create Purchase Request (PR)</h3>
            <button type="button" class="modal-close-btn" data-close-modal="createPRModal"><i class="bi bi-x-lg"></i></button>
        </div>
        <form id="createPRForm" onsubmit="submitCreatePR(event)">
            @csrf
            <div class="modal-body">
                <div style="background: #eff6ff; border: 1px solid #bfdbfe; color: #1e40af; padding: 10px 14px; border-radius: 6px; font-size: 0.82rem; margin-bottom: 16px;">
                    <i class="bi bi-info-circle-fill me-1"></i> <strong>Multi-Supplier PR:</strong> You can choose different target suppliers for each product line. The system will automatically split them into separate Purchase Orders upon approval.
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 14px; margin-bottom: 14px;">
                    <div class="form-group">
                        <label class="form-label" for="prWarehouse">Destination Warehouse <span class="text-danger">*</span></label>
                        <select name="warehouse_id" id="prWarehouse" class="form-select" required>
                            @foreach($warehouses as $w)
                                <option value="{{ $w->id }}">{{ $w->name }} ({{ $w->code }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="prReqDate">Request Date <span class="text-danger">*</span></label>
                        <input type="date" name="request_date" id="prReqDate" class="form-control" value="{{ date('Y-m-d') }}" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="prNeededDate">Needed By Date</label>
                        <input type="date" name="needed_by_date" id="prNeededDate" class="form-control" value="{{ date('Y-m-d', strtotime('+7 days')) }}">
                    </div>
                </div>

                <div class="form-group" style="margin-bottom: 16px;">
                    <label class="form-label" for="prPurpose">Purpose / Justification</label>
                    <input type="text" name="purpose" id="prPurpose" class="form-control" placeholder="e.g. Weekly stock replenishment for wholesale clients">
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
                        <label class="form-label" for="prRemarks">Remarks / Notes</label>
                        <textarea name="remarks" id="prRemarks" class="form-control" rows="2" placeholder="Notes for supervisor review..."></textarea>
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
                    <i class="bi bi-check-lg me-1"></i> Submit Purchase Request
                </button>
            </div>
        </form>
    </div>
</div>

<!-- REVIEW & APPROVAL MODAL (Supervisor Editing) -->
<div id="reviewPRModal" class="modal-overlay">
    <div class="modal-dialog modal-xl" style="max-width: 1100px;">
        <div class="modal-header">
            <h3 class="modal-title" id="reviewPRTitle"><i class="bi bi-shield-check me-2 text-warning"></i> Review & Modify Purchase Request</h3>
            <button type="button" class="modal-close-btn" data-close-modal="reviewPRModal"><i class="bi bi-x-lg"></i></button>
        </div>
        <form id="reviewPRForm" onsubmit="submitReviewPR(event)">
            @csrf
            <input type="hidden" id="reviewPRId">
            <input type="hidden" name="action" id="reviewPRAction" value="approve">

            <div class="modal-body">
                <div style="background: #fffbeb; border: 1px solid #fde68a; color: #92400e; padding: 10px 14px; border-radius: 6px; font-size: 0.82rem; margin-bottom: 14px;">
                    <i class="bi bi-pencil-square me-1"></i> As Supervisor/Admin, you can modify quantities, change target suppliers, edit purchase costs, and add/remove items before approving.
                </div>

                <div id="reviewPRHeaderInfo" style="background: var(--color-mist-blue); padding: 12px 14px; border-radius: 8px; border: 1px solid var(--card-border); margin-bottom: 14px;"></div>

                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                    <span style="font-weight: 700; color: var(--color-deep-navy); font-size: 0.9rem;">
                        <i class="bi bi-boxes me-1 text-primary"></i> Requested Items (Editable)
                    </span>
                    <button type="button" class="btn btn-sm btn-outline" onclick="addReviewPRRow()">
                        <i class="bi bi-plus-circle me-1"></i> Add Line Item
                    </button>
                </div>

                <div class="table-responsive" style="border: 1px solid var(--card-border); border-radius: 8px; margin-bottom: 14px;">
                    <table class="table mb-0" style="width: 100%;">
                        <thead style="background: var(--color-mist-blue); font-size: 0.8rem; text-transform: uppercase;">
                            <tr>
                                <th style="padding: 10px; width: 25%;">SKU / Product</th>
                                <th style="padding: 10px; width: 25%;">Supplier</th>
                                <th style="padding: 10px; width: 12%;">Unit</th>
                                <th style="padding: 10px; width: 10%;">Qty</th>
                                <th style="padding: 10px; width: 13%;">Unit Cost</th>
                                <th style="padding: 10px; width: 10%;">Subtotal</th>
                                <th style="padding: 10px; width: 5%; text-align: center;">Action</th>
                            </tr>
                        </thead>
                        <tbody id="reviewRowsContainer">
                            <!-- Populated dynamically -->
                        </tbody>
                    </table>
                </div>

                <div style="display: grid; grid-template-columns: 1.2fr 0.8fr; gap: 16px;">
                    <div>
                        <div class="form-group" id="rejectionReasonBox" style="display: none; margin-bottom: 10px;">
                            <label class="form-label text-danger" for="prRejectionReason">Rejection Reason <span class="text-danger">*</span></label>
                            <textarea name="rejection_reason" id="prRejectionReason" class="form-control" rows="2" placeholder="Specify why this PR is rejected..."></textarea>
                        </div>
                        <div class="form-group" id="revisionNotesBox" style="display: none; margin-bottom: 10px;">
                            <label class="form-label" style="color: #0284c7;" for="prRevisionNotes">Revision Instructions <span class="text-danger">*</span></label>
                            <textarea name="revision_notes" id="prRevisionNotes" class="form-control" rows="2" placeholder="Specify changes needed from staff..."></textarea>
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="reviewRemarks">Supervisor Approval Notes (Optional)</label>
                            <textarea name="remarks" id="reviewRemarks" class="form-control" rows="2" placeholder="Notes for audit trail..."></textarea>
                        </div>
                    </div>
                    <div style="background: var(--color-mist-blue); border: 1px solid var(--card-border); border-radius: 8px; padding: 14px; text-align: right;">
                        <span style="font-size: 0.85rem; color: var(--text-muted);">Approved Estimated Total:</span>
                        <div style="font-size: 1.3rem; font-weight: 800; color: #16a34a;" id="reviewPRTotalDisplay">₱0.00</div>
                    </div>
                </div>
            </div>

            <div class="modal-footer" style="display: flex; justify-content: space-between;">
                <div style="display: flex; gap: 8px;">
                    <button type="button" class="btn btn-danger" onclick="triggerPRAction('reject')">
                        <i class="bi bi-x-circle me-1"></i> Reject PR
                    </button>
                    <button type="button" class="btn btn-secondary" onclick="triggerPRAction('return')">
                        <i class="bi bi-arrow-return-left me-1"></i> Return for Revision
                    </button>
                </div>
                <div style="display: flex; gap: 8px;">
                    <button type="button" class="btn btn-outline" data-close-modal="reviewPRModal">Cancel</button>
                    <button type="button" class="btn btn-success" onclick="triggerPRAction('approve')">
                        <i class="bi bi-check-circle me-1"></i> Approve PR
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- VIEW PR DETAILS MODAL -->
<div id="viewPRModal" class="modal-overlay">
    <div class="modal-dialog modal-lg" style="max-width: 850px;">
        <div class="modal-header">
            <h3 class="modal-title" id="viewPRModalTitle">Purchase Request Details</h3>
            <button type="button" class="modal-close-btn" data-close-modal="viewPRModal"><i class="bi bi-x-lg"></i></button>
        </div>
        <div class="modal-body" id="viewPRModalBody">
            <div style="text-align: center; padding: 40px;"><div class="spinner-border text-primary" role="status"></div></div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-outline" data-close-modal="viewPRModal">Close</button>
        </div>
    </div>
</div>

@push('scripts')
<script>
const productsData = @json($products);
const suppliersData = @json($suppliers);
let prRowIdx = 0;
let reviewRowIdx = 0;

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
            <button type="button" class="btn btn-sm btn-outline-danger" onclick="removePRRow(${idx})">
                <i class="bi bi-trash"></i>
            </button>
        </td>
    `;
    document.getElementById('prRowsContainer').appendChild(tr);
}

function removePRRow(idx) {
    document.getElementById(`pr_row_${idx}`)?.remove();
    calculatePRTotal();
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
        const res = await fetch("{{ route('admin.purchase-requests.store') }}", {
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
            showToast('success', 'PR Created', data.message);
            setTimeout(() => location.reload(), 600);
        } else {
            showToast('error', 'Error', data.message || 'Error creating PR.');
            btn.disabled = false;
            btn.innerHTML = '<i class="bi bi-check-lg me-1"></i> Submit Purchase Request';
        }
    } catch (err) {
        showToast('error', 'Error', err.message);
        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-check-lg me-1"></i> Submit Purchase Request';
    }
}

async function openReviewPRModal(id) {
    document.getElementById('reviewPRId').value = id;
    document.getElementById('reviewRowsContainer').innerHTML = '<tr><td colspan="7" class="text-center py-4"><div class="spinner-border text-primary"></div></td></tr>';
    openModal('reviewPRModal');

    try {
        const res = await fetch(`/admin/purchase-requests/${id}`, {
            headers: { 'Accept': 'application/json' }
        });
        const json = await res.json();
        const pr = json.purchase_request;

        document.getElementById('reviewPRTitle').innerHTML = `<i class="bi bi-shield-check me-2 text-warning"></i> Review PR #${pr.pr_number}`;
        document.getElementById('reviewPRHeaderInfo').innerHTML = `
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(160px, 1fr)); gap: 8px; font-size: 0.82rem;">
                <div><strong>Requested By:</strong> ${pr.requester?.name || 'Staff'}</div>
                <div><strong>Warehouse:</strong> ${pr.warehouse?.name || 'Main'}</div>
                <div><strong>Date:</strong> ${pr.request_date}</div>
                <div><strong>Needed By:</strong> ${pr.needed_by_date || 'N/A'}</div>
                <div style="grid-column: 1 / -1;"><strong>Purpose:</strong> ${pr.purpose || 'None stated.'}</div>
            </div>
        `;

        document.getElementById('reviewRowsContainer').innerHTML = '';
        reviewRowIdx = 0;

        pr.items.forEach(item => {
            renderReviewPRRow(item);
        });

        calculateReviewTotal();
    } catch (e) {
        showToast('error', 'Error', 'Failed to load PR details: ' + e.message);
    }
}

function renderReviewPRRow(item = null) {
    const idx = reviewRowIdx++;
    const tr = document.createElement('tr');
    tr.id = `rev_row_${idx}`;

    const prodId = item ? item.product_id : '';
    const suppId = item ? item.supplier_id : '';
    const qty = item ? parseFloat(item.quantity) : 1;
    const origQty = item && item.original_quantity ? parseFloat(item.original_quantity) : qty;
    const cost = item ? parseFloat(item.estimated_unit_cost) : 0;
    const origCost = item && item.original_unit_cost ? parseFloat(item.original_unit_cost) : cost;
    const itemId = item ? item.id : '';

    tr.innerHTML = `
        <td style="padding: 8px 10px;">
            <select name="items[${idx}][product_id]" class="form-select form-select-sm" required>
                ${productsData.map(p => `<option value="${p.id}" ${p.id == prodId ? 'selected' : ''}>${p.sku} - ${p.name}</option>`).join('')}
            </select>
            <input type="hidden" name="items[${idx}][id]" value="${itemId}">
            <input type="hidden" name="items[${idx}][conversion_factor]" value="${item ? item.conversion_factor : 1}">
        </td>
        <td style="padding: 8px 10px;">
            <select name="items[${idx}][supplier_id]" class="form-select form-select-sm" required>
                ${suppliersData.map(s => `<option value="${s.id}" ${s.id == suppId ? 'selected' : ''}>${s.name}</option>`).join('')}
            </select>
            ${item && item.original_supplier_id && item.original_supplier_id != suppId ? `<div style="font-size: 0.72rem; color: #64748b;">Orig: ${item.original_supplier?.name}</div>` : ''}
        </td>
        <td style="padding: 8px 10px;">
            <input type="text" name="items[${idx}][unit_name]" class="form-control form-control-sm" value="${item ? item.unit_name : 'kg'}" required>
        </td>
        <td style="padding: 8px 10px;">
            <input type="number" name="items[${idx}][quantity]" id="rev_qty_${idx}" class="form-control form-control-sm text-center" value="${qty}" min="0.01" step="any" oninput="calculateReviewRow(${idx})" required>
            ${origQty !== qty ? `<small style="font-size: 0.7rem; color: #64748b; display: block;">Orig: ${origQty}</small>` : ''}
        </td>
        <td style="padding: 8px 10px;">
            <input type="number" name="items[${idx}][estimated_unit_cost]" id="rev_cost_${idx}" class="form-control form-control-sm text-end" value="${cost.toFixed(2)}" min="0" step="0.01" oninput="calculateReviewRow(${idx})" required>
            ${origCost !== cost ? `<small style="font-size: 0.7rem; color: #64748b; display: block;">Orig: ₱${origCost.toFixed(2)}</small>` : ''}
        </td>
        <td style="padding: 8px 10px;">
            <strong class="d-block text-end" id="rev_subtotal_${idx}">₱0.00</strong>
        </td>
        <td style="padding: 8px 10px; text-align: center;">
            <button type="button" class="btn btn-sm btn-outline-danger" onclick="document.getElementById('rev_row_${idx}').remove(); calculateReviewTotal();">
                <i class="bi bi-trash"></i>
            </button>
        </td>
    `;
    document.getElementById('reviewRowsContainer').appendChild(tr);
    calculateReviewRow(idx);
}

function addReviewPRRow() {
    renderReviewPRRow(null);
}

function calculateReviewRow(idx) {
    const qty = parseFloat(document.getElementById(`rev_qty_${idx}`)?.value || 0);
    const cost = parseFloat(document.getElementById(`rev_cost_${idx}`)?.value || 0);
    const subtotal = qty * cost;
    const display = document.getElementById(`rev_subtotal_${idx}`);
    if (display) {
        display.innerText = '₱' + subtotal.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        display.setAttribute('data-val', subtotal);
    }
    calculateReviewTotal();
}

function calculateReviewTotal() {
    let total = 0;
    document.querySelectorAll('[id^="rev_subtotal_"]').forEach(el => {
        total += parseFloat(el.getAttribute('data-val') || 0);
    });
    document.getElementById('reviewPRTotalDisplay').innerText = '₱' + total.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}

function triggerPRAction(action) {
    document.getElementById('reviewPRAction').value = action;
    const rejBox = document.getElementById('rejectionReasonBox');
    const revBox = document.getElementById('revisionNotesBox');

    if (action === 'reject') {
        rejBox.style.display = 'block';
        revBox.style.display = 'none';
        const reason = document.getElementById('prRejectionReason').value.trim();
        if (!reason) {
            showToast('warning', 'Input Required', 'Please provide a reason for rejecting this PR.');
            document.getElementById('prRejectionReason').focus();
            return;
        }
    } else if (action === 'return') {
        revBox.style.display = 'block';
        rejBox.style.display = 'none';
        const notes = document.getElementById('prRevisionNotes').value.trim();
        if (!notes) {
            showToast('warning', 'Input Required', 'Please specify revision instructions for the staff.');
            document.getElementById('prRevisionNotes').focus();
            return;
        }
    } else {
        rejBox.style.display = 'none';
        revBox.style.display = 'none';
    }

    document.getElementById('reviewPRForm').requestSubmit();
}

async function submitReviewPR(e) {
    e.preventDefault();
    const id = document.getElementById('reviewPRId').value;
    const form = document.getElementById('reviewPRForm');
    const formData = new FormData(form);

    try {
        const res = await fetch(`/admin/purchase-requests/${id}/review`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Accept': 'application/json'
            },
            body: formData
        });
        const data = await res.json();
        if (data.success) {
            closeModal('reviewPRModal');
            showToast('success', 'PR Action Completed', data.message);
            setTimeout(() => location.reload(), 600);
        } else {
            showToast('error', 'Error', data.message || 'Error processing review.');
        }
    } catch (err) {
        showToast('error', 'Error', err.message);
    }
}

async function convertPRToPOs(id, prNumber, suppliersCount) {
    const confirmMsg = suppliersCount > 1
        ? `This PR contains ${suppliersCount} different suppliers. The system will automatically generate ${suppliersCount} separate Purchase Orders (POs) by supplier. Proceed?`
        : `Generate Purchase Order for PR #${prNumber}?`;

    if (!confirm(confirmMsg)) return;

    try {
        const res = await fetch(`/admin/purchase-requests/${id}/convert-to-pos`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Accept': 'application/json'
            }
        });
        const data = await res.json();
        if (data.success) {
            showToast('success', 'POs Generated', data.message);
            setTimeout(() => location.reload(), 700);
        } else {
            showToast('error', 'Error', data.message || 'Error converting PR to POs.');
        }
    } catch (e) {
        showToast('error', 'Error', e.message);
    }
}

async function viewPRDetails(id) {
    openModal('viewPRModal');
    document.getElementById('viewPRModalBody').innerHTML = '<div style="text-align: center; padding: 40px;"><div class="spinner-border text-primary"></div></div>';

    try {
        const res = await fetch(`/admin/purchase-requests/${id}`, {
            headers: { 'Accept': 'application/json' }
        });
        const json = await res.json();
        const pr = json.purchase_request;

        let rowsHtml = pr.items.map(item => `
            <tr>
                <td style="padding: 8px 10px;"><strong>${item.product?.sku}</strong> - ${item.product?.name}</td>
                <td style="padding: 8px 10px;"><span class="badge badge-navy">${item.supplier?.name || 'N/A'}</span></td>
                <td style="padding: 8px 10px; text-align: center;">${parseFloat(item.quantity)} ${item.unit_name}</td>
                <td style="padding: 8px 10px; text-align: right;">₱${parseFloat(item.estimated_unit_cost).toFixed(2)}</td>
                <td style="padding: 8px 10px; text-align: right; font-weight: 700;">₱${parseFloat(item.estimated_subtotal).toFixed(2)}</td>
            </tr>
        `).join('');

        document.getElementById('viewPRModalTitle').innerText = `Purchase Request #${pr.pr_number}`;
        document.getElementById('viewPRModalBody').innerHTML = `
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 16px;">
                <div>
                    <div style="font-size: 0.78rem; color: var(--text-muted);">Destination Warehouse:</div>
                    <strong>${pr.warehouse?.name || 'Main Warehouse'}</strong>
                    <div style="font-size: 0.8rem; color: var(--text-light); margin-top: 2px;">Requested By: <strong>${pr.requester?.name || 'Staff'}</strong></div>
                </div>
                <div style="text-align: right;">
                    <div style="font-size: 0.78rem; color: var(--text-muted);">Request Date: <strong>${pr.request_date}</strong></div>
                    <div style="font-size: 0.8rem; color: var(--text-light); margin-top: 2px;">Needed By: <strong>${pr.needed_by_date || 'N/A'}</strong></div>
                    <div style="margin-top: 4px;"><span class="badge badge-primary">${pr.status.toUpperCase().replace('_', ' ')}</span></div>
                </div>
            </div>
            ${pr.purpose ? `<div style="background: var(--color-mist-blue); padding: 10px; border-radius: 6px; border: 1px solid var(--card-border); margin-bottom: 14px; font-size: 0.82rem;"><strong>Purpose:</strong> ${pr.purpose}</div>` : ''}
            <div class="table-responsive" style="border: 1px solid var(--card-border); border-radius: 8px; margin-bottom: 14px;">
                <table class="table mb-0" style="width: 100%;">
                    <thead style="background: var(--color-mist-blue); font-size: 0.8rem; text-transform: uppercase;">
                        <tr>
                            <th style="padding: 8px 10px;">Product / SKU</th>
                            <th style="padding: 8px 10px;">Target Supplier</th>
                            <th style="padding: 8px 10px; text-align: center;">Quantity</th>
                            <th style="padding: 8px 10px; text-align: right;">Est. Unit Cost</th>
                            <th style="padding: 8px 10px; text-align: right;">Est. Subtotal</th>
                        </tr>
                    </thead>
                    <tbody>${rowsHtml}</tbody>
                </table>
            </div>
            <div style="display: flex; justify-content: space-between; align-items: center; background: var(--color-mist-blue); padding: 12px 16px; border-radius: 8px; border: 1px solid var(--card-border);">
                <span style="font-weight: 700; color: var(--color-deep-navy);">Total Estimated Cost:</span>
                <span style="font-size: 1.2rem; font-weight: 800; color: var(--color-primary-blue);">₱${parseFloat(pr.total_estimated_amount).toFixed(2)}</span>
            </div>
        `;
    } catch (e) {
        document.getElementById('viewPRModalBody').innerHTML = '<div class="alert alert-danger">Error loading PR details.</div>';
    }
}
</script>
@endpush
@endsection
