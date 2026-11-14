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
                                <div style="display: inline-flex; gap: 4px; justify-content: flex-end;">
                                    <button type="button" class="btn btn-sm btn-outline" onclick="viewPRDetails({{ $pr->id }})" title="View Details & Awarding">
                                        <i class="bi bi-eye"></i>
                                    </button>
                                    @if(in_array($pr->status, ['returned', 'draft']))
                                        <button type="button" class="btn btn-sm btn-warning" onclick="openEditPRModal({{ $pr->id }})" title="Edit / Revise PR">
                                            <i class="bi bi-pencil me-1"></i> Edit
                                        </button>
                                    @endif
                                    @if($pr->status === 'returned')
                                        <form action="{{ route('staff.purchase-requests.resubmit', $pr->id) }}" method="POST" style="display: inline;" onsubmit="return confirm('Resubmit this PR for approval without further edits?')">
                                            @csrf
                                            <button type="submit" class="btn btn-sm btn-outline-warning" title="Quick Resubmit to Admin">
                                                <i class="bi bi-arrow-clockwise me-1"></i> Resubmit
                                            </button>
                                        </form>
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

<!-- EDIT / REVISE PR MODAL FOR STAFF -->
<div id="editPRModal" class="modal-overlay">
    <div class="modal-dialog modal-xl" style="max-width: 1050px;">
        <div class="modal-header">
            <h3 class="modal-title"><i class="bi bi-pencil-square me-2 text-warning"></i> Edit / Revise Purchase Request <span id="editPRNumberDisplay" class="text-primary"></span></h3>
            <button type="button" class="modal-close-btn" data-close-modal="editPRModal"><i class="bi bi-x-lg"></i></button>
        </div>
        <form id="editPRForm" onsubmit="submitEditPR(event)">
            @csrf
            @method('PUT')
            <input type="hidden" id="editPRId">
            <input type="hidden" name="status" id="editPRSubmitStatus" value="submitted">

            <div class="modal-body">
                <!-- Revision Instructions Alert from Admin -->
                <div id="editPRRevisionAlert" style="display: none; background: #fffbeb; border: 1px solid #fde68a; border-radius: 8px; padding: 12px 14px; margin-bottom: 14px; color: #92400e;">
                    <div style="font-weight: 700; font-size: 0.85rem; margin-bottom: 2px;">
                        <i class="bi bi-exclamation-triangle-fill me-1 text-warning"></i> Return for Revision Note from Admin:
                    </div>
                    <div id="editPRRevisionText" style="font-size: 0.85rem; padding-left: 18px;"></div>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 14px; margin-bottom: 14px;">
                    <div class="form-group mb-0">
                        <label class="form-label" for="edit_pr_warehouse">Destination Warehouse <span class="text-danger">*</span></label>
                        <select name="warehouse_id" id="edit_pr_warehouse" class="form-select" required>
                            @foreach($warehouses as $w)
                                <option value="{{ $w->id }}">{{ $w->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group mb-0">
                        <label class="form-label" for="edit_pr_needed_by">Needed By Date</label>
                        <input type="date" name="needed_by_date" id="edit_pr_needed_by" class="form-control">
                    </div>
                    <div class="form-group mb-0">
                        <label class="form-label" for="edit_pr_purpose">Purpose / Project Reference</label>
                        <input type="text" name="purpose" id="edit_pr_purpose" class="form-control" placeholder="e.g. Weekly Kitchen Stock replenishment">
                    </div>
                </div>

                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                    <span style="font-weight: 700; color: var(--color-deep-navy); font-size: 0.9rem;">
                        <i class="bi bi-boxes me-1 text-primary"></i> Requested Items
                    </span>
                    <button type="button" class="btn btn-sm btn-outline" onclick="addEditPRRow()">
                        <i class="bi bi-plus-circle me-1"></i> Add Line Item
                    </button>
                </div>

                <div class="table-responsive" style="border: 1px solid var(--card-border); border-radius: 8px; margin-bottom: 16px;">
                    <table class="table mb-0" style="width: 100%;">
                        <thead style="background: var(--color-mist-blue); font-size: 0.8rem; text-transform: uppercase;">
                            <tr>
                                <th style="padding: 10px; width: 25%;">SKU / Product <span class="text-danger">*</span></th>
                                <th style="padding: 10px; width: 25%;">Target Supplier <span class="text-danger">*</span></th>
                                <th style="padding: 10px; width: 12%;">Unit</th>
                                <th style="padding: 10px; width: 10%;">Qty</th>
                                <th style="padding: 10px; width: 13%;">Est. Unit Cost</th>
                                <th style="padding: 10px; width: 10%;">Subtotal</th>
                                <th style="padding: 10px; width: 5%; text-align: center;">Action</th>
                            </tr>
                        </thead>
                        <tbody id="editPRRowsContainer">
                            <!-- Populated dynamically -->
                        </tbody>
                    </table>
                </div>

                <div style="display: grid; grid-template-columns: 1.2fr 0.8fr; gap: 16px;">
                    <div class="form-group mb-0">
                        <label class="form-label" for="edit_pr_remarks">Revision Notes / Remarks</label>
                        <textarea name="remarks" id="edit_pr_remarks" class="form-control" rows="2" placeholder="Explain adjustments made for admin review..."></textarea>
                    </div>
                    <div style="background: var(--color-mist-blue); border: 1px solid var(--card-border); border-radius: 8px; padding: 14px; text-align: right;">
                        <span style="font-size: 0.85rem; color: var(--text-muted);">Total Estimated Amount:</span>
                        <div style="font-size: 1.3rem; font-weight: 800; color: var(--color-primary-blue);" id="editPRTotalDisplay">₱0.00</div>
                    </div>
                </div>
            </div>

            <div class="modal-footer" style="display: flex; justify-content: space-between;">
                <button type="button" class="btn btn-outline" data-close-modal="editPRModal">Cancel</button>
                <div style="display: flex; gap: 8px;">
                    <button type="button" class="btn btn-secondary" onclick="document.getElementById('editPRSubmitStatus').value='draft'; document.getElementById('editPRForm').requestSubmit();">
                        <i class="bi bi-save me-1"></i> Save as Draft
                    </button>
                    <button type="submit" class="btn btn-success" id="editPRSubmitBtn" onclick="document.getElementById('editPRSubmitStatus').value='submitted';">
                        <i class="bi bi-send-check me-1"></i> Save & Resubmit to Admin
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- VIEW PR DETAILS & AWARDING MODAL -->
<div id="viewPRModal" class="modal-overlay">
    <div class="modal-dialog modal-xl" style="max-width: 1000px;">
        <div class="modal-header">
            <h3 class="modal-title" id="viewPRModalTitle"><i class="bi bi-eye me-2 text-primary"></i> Purchase Request Details</h3>
            <button type="button" class="modal-close-btn" data-close-modal="viewPRModal"><i class="bi bi-x-lg"></i></button>
        </div>
        <div class="modal-body" id="viewPRModalBody">
            <div style="text-align: center; padding: 40px;"><div class="spinner-border text-primary"></div></div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-close-modal="viewPRModal">Close</button>
        </div>
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

async function viewPRDetails(id) {
    openModal('viewPRModal');
    document.getElementById('viewPRModalBody').innerHTML = '<div style="text-align: center; padding: 40px;"><div class="spinner-border text-primary"></div></div>';

    try {
        const res = await fetch(`/staff/purchase-requests/${id}`, {
            headers: { 'Accept': 'application/json' }
        });
        const json = await res.json();
        const pr = json.purchase_request;

        let supplierBreakdown = {};
        let rowsHtml = pr.items.map(item => {
            const suppName = item.supplier?.name || 'N/A';
            const origSuppName = item.original_supplier?.name || null;
            const isReassigned = origSuppName && item.original_supplier_id && item.original_supplier_id != item.supplier_id;
            const subtotal = parseFloat(item.estimated_subtotal);

            if (!supplierBreakdown[suppName]) {
                supplierBreakdown[suppName] = { count: 0, total: 0, poNumber: item.generated_purchase?.purchase_number };
            }
            supplierBreakdown[suppName].count += 1;
            supplierBreakdown[suppName].total += subtotal;

            let poBadge = '';
            if (item.generated_purchase_id && item.generated_purchase) {
                poBadge = `<span class="badge badge-info" style="display: inline-block; margin-top: 3px;">
                    <i class="bi bi-file-earmark-check me-1"></i> ${item.generated_purchase.purchase_number}
                </span>`;
            }

            return `
            <tr>
                <td style="padding: 8px 10px;">
                    <strong>${item.product?.sku}</strong> - ${item.product?.name}
                    ${item.remarks ? `<div style="font-size: 0.72rem; color: var(--text-muted);"><i class="bi bi-chat-left-text me-1"></i> ${item.remarks}</div>` : ''}
                </td>
                <td style="padding: 8px 10px;">
                    <div><strong style="color: var(--color-deep-navy);">${suppName}</strong></div>
                    ${isReassigned ? `
                        <span class="badge" style="background:#e0f2fe; color:#0369a1; font-size:0.7rem; border:1px solid #bae6fd;">
                            <i class="bi bi-arrow-left-right me-1"></i> Orig: ${origSuppName}
                        </span>
                    ` : ''}
                    ${poBadge}
                </td>
                <td style="padding: 8px 10px; text-align: center;">${parseFloat(item.quantity)} ${item.unit_name}</td>
                <td style="padding: 8px 10px; text-align: right;">₱${parseFloat(item.estimated_unit_cost).toFixed(2)}</td>
                <td style="padding: 8px 10px; text-align: right; font-weight: 700;">₱${subtotal.toFixed(2)}</td>
            </tr>
            `;
        }).join('');

        let breakdownHtml = Object.keys(supplierBreakdown).map(sName => {
            const data = supplierBreakdown[sName];
            return `
                <div style="background: #fff; border: 1px solid var(--card-border); border-radius: 6px; padding: 8px 12px; font-size: 0.78rem;">
                    <div style="font-weight: 700; color: var(--color-deep-navy);">${sName}</div>
                    <div style="display: flex; justify-content: space-between; margin-top: 2px;">
                        <span style="color: var(--text-muted);">${data.count} awarded item(s)</span>
                        <strong style="color: #16a34a;">₱${data.total.toFixed(2)}</strong>
                    </div>
                    ${data.poNumber ? `<div style="margin-top: 4px;"><span class="badge badge-success"><i class="bi bi-check2-circle me-1"></i> PO: ${data.poNumber}</span></div>` : ''}
                </div>
            `;
        }).join('');

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
                            <th style="padding: 8px 10px;">Awarded Supplier</th>
                            <th style="padding: 8px 10px; text-align: center;">Quantity</th>
                            <th style="padding: 8px 10px; text-align: right;">Est. Unit Cost</th>
                            <th style="padding: 8px 10px; text-align: right;">Est. Subtotal</th>
                        </tr>
                    </thead>
                    <tbody>${rowsHtml}</tbody>
                </table>
            </div>

            <!-- Awarded Supplier Summary Cards -->
            <div style="margin-bottom: 14px;">
                <div style="font-size: 0.8rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; margin-bottom: 6px;">
                    <i class="bi bi-diagram-3 me-1 text-primary"></i> Awarding Breakdown by Supplier
                </div>
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 10px;">
                    ${breakdownHtml}
                </div>
            </div>

            <div style="display: flex; justify-content: space-between; align-items: center; background: var(--color-mist-blue); padding: 12px 16px; border-radius: 8px; border: 1px solid var(--card-border);">
                <span style="font-weight: 700; color: var(--color-deep-navy);">Total Estimated Cost:</span>
                <span style="font-size: 1.2rem; font-weight: 800; color: var(--color-primary-blue);">₱${parseFloat(pr.total_estimated_amount).toFixed(2)}</span>
            </div>
        `;
    } catch (e) {
        document.getElementById('viewPRModalBody').innerHTML = '<div class="alert alert-danger">Error loading PR details: ' + e.message + '</div>';
    }
}

let editPRRowIdx = 0;

async function openEditPRModal(id) {
    openModal('editPRModal');
    document.getElementById('editPRNumberDisplay').innerText = 'Loading...';
    document.getElementById('editPRRowsContainer').innerHTML = '<tr><td colspan="7" class="text-center py-4"><div class="spinner-border text-primary spinner-border-sm"></div> Loading PR details...</td></tr>';

    try {
        const res = await fetch(`/staff/purchase-requests/${id}`, {
            headers: { 'Accept': 'application/json' }
        });
        const json = await res.json();
        const pr = json.purchase_request;

        document.getElementById('editPRId').value = pr.id;
        document.getElementById('editPRNumberDisplay').innerText = `#${pr.pr_number}`;

        if (pr.revision_notes) {
            document.getElementById('editPRRevisionAlert').style.display = 'block';
            document.getElementById('editPRRevisionText').innerText = pr.revision_notes;
        } else {
            document.getElementById('editPRRevisionAlert').style.display = 'none';
        }

        document.getElementById('edit_pr_warehouse').value = pr.warehouse_id || '';
        document.getElementById('edit_pr_needed_by').value = pr.needed_by_date ? pr.needed_by_date.substring(0, 10) : '';
        document.getElementById('edit_pr_purpose').value = pr.purpose || '';
        document.getElementById('edit_pr_remarks').value = pr.remarks || '';

        document.getElementById('editPRRowsContainer').innerHTML = '';
        editPRRowIdx = 0;

        if (pr.items && pr.items.length > 0) {
            pr.items.forEach(item => renderEditPRRow(item));
        } else {
            addEditPRRow();
        }

        calculateEditPRTotal();
    } catch (e) {
        showToast('error', 'Error', 'Failed to load PR details: ' + e.message);
        closeModal('editPRModal');
    }
}

function renderEditPRRow(item = null) {
    const idx = editPRRowIdx++;
    const tr = document.createElement('tr');
    tr.id = `edit_pr_row_${idx}`;

    const prodId = item ? item.product_id : (productsData[0]?.id || '');
    const suppId = item ? item.supplier_id : '';
    const unitName = item ? item.unit_name : 'kg';
    const convFactor = item ? item.conversion_factor : 1;
    const qty = item ? parseFloat(item.quantity) : 1;
    const cost = item ? parseFloat(item.estimated_unit_cost) : 0;
    const itemId = item ? item.id : '';
    const remarks = item ? (item.remarks || '') : '';

    tr.innerHTML = `
        <td style="padding: 8px 10px;">
            <select name="items[${idx}][product_id]" id="edit_pr_prod_${idx}" class="form-select form-select-sm" onchange="onEditPRProductChange(${idx})" required>
                ${productsData.map(p => `<option value="${p.id}" ${p.id == prodId ? 'selected' : ''}>${p.sku} - ${p.name}</option>`).join('')}
            </select>
            <input type="hidden" name="items[${idx}][id]" value="${itemId}">
            <input type="hidden" name="items[${idx}][conversion_factor]" id="edit_pr_conv_${idx}" value="${convFactor}">
            <input type="text" name="items[${idx}][remarks]" class="form-control form-control-sm mt-1" placeholder="Item note..." value="${remarks}" style="font-size: 0.72rem;">
        </td>
        <td style="padding: 8px 10px;">
            <select name="items[${idx}][supplier_id]" id="edit_pr_supp_${idx}" class="form-select form-select-sm" required>
                <option value="">-- Select Supplier --</option>
                ${suppliersData.map(s => `<option value="${s.id}" ${s.id == suppId ? 'selected' : ''}>${s.name}</option>`).join('')}
            </select>
        </td>
        <td style="padding: 8px 10px;">
            <input type="text" name="items[${idx}][unit_name]" id="edit_pr_unit_${idx}" class="form-control form-control-sm" value="${unitName}" required>
        </td>
        <td style="padding: 8px 10px;">
            <input type="number" name="items[${idx}][quantity]" id="edit_pr_qty_${idx}" class="form-control form-control-sm text-center" value="${qty}" min="0.01" step="any" oninput="calculateEditPRRow(${idx})" required>
        </td>
        <td style="padding: 8px 10px;">
            <input type="number" name="items[${idx}][estimated_unit_cost]" id="edit_pr_cost_${idx}" class="form-control form-control-sm text-end" value="${cost.toFixed(2)}" min="0" step="0.01" oninput="calculateEditPRRow(${idx})" required>
        </td>
        <td style="padding: 8px 10px;">
            <strong class="d-block text-end" id="edit_pr_subtotal_${idx}">₱0.00</strong>
        </td>
        <td style="padding: 8px 10px; text-align: center;">
            <button type="button" class="btn btn-sm btn-outline-danger" onclick="document.getElementById('edit_pr_row_${idx}').remove(); calculateEditPRTotal();">
                <i class="bi bi-trash"></i>
            </button>
        </td>
    `;
    document.getElementById('editPRRowsContainer').appendChild(tr);
    calculateEditPRRow(idx);
}

function addEditPRRow() {
    renderEditPRRow(null);
}

function onEditPRProductChange(idx) {
    const prodId = document.getElementById(`edit_pr_prod_${idx}`)?.value;
    const prod = productsData.find(p => p.id == prodId);
    if (!prod) return;

    if (prod.base_unit) {
        document.getElementById(`edit_pr_unit_${idx}`).value = prod.base_unit;
    }
    if (prod.suppliers && prod.suppliers.length > 0) {
        document.getElementById(`edit_pr_supp_${idx}`).value = prod.suppliers[0].id;
    }
}

function calculateEditPRRow(idx) {
    const qty = parseFloat(document.getElementById(`edit_pr_qty_${idx}`)?.value || 0);
    const cost = parseFloat(document.getElementById(`edit_pr_cost_${idx}`)?.value || 0);
    const subtotal = qty * cost;
    const display = document.getElementById(`edit_pr_subtotal_${idx}`);
    if (display) {
        display.innerText = '₱' + subtotal.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        display.setAttribute('data-val', subtotal);
    }
    calculateEditPRTotal();
}

function calculateEditPRTotal() {
    let total = 0;
    document.querySelectorAll('[id^="edit_pr_subtotal_"]').forEach(el => {
        total += parseFloat(el.getAttribute('data-val') || 0);
    });
    document.getElementById('editPRTotalDisplay').innerText = '₱' + total.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}

async function submitEditPR(e) {
    e.preventDefault();
    const id = document.getElementById('editPRId').value;
    const btn = document.getElementById('editPRSubmitBtn');
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Saving & Resubmitting...';

    const form = document.getElementById('editPRForm');
    const formData = new FormData(form);

    try {
        const res = await fetch(`/staff/purchase-requests/${id}`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Accept': 'application/json'
            },
            body: formData
        });
        const data = await res.json();
        if (data.success) {
            closeModal('editPRModal');
            showToast('success', 'Updated Successfully', data.message);
            setTimeout(() => location.reload(), 600);
        } else {
            showToast('error', 'Update Error', data.message || 'Error updating PR.');
            btn.disabled = false;
            btn.innerHTML = '<i class="bi bi-send-check me-1"></i> Save & Resubmit to Admin';
        }
    } catch (err) {
        showToast('error', 'Error', err.message);
        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-send-check me-1"></i> Save & Resubmit to Admin';
    }
}
</script>
@endpush
@endsection
