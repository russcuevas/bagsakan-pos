@extends('layouts.app')

@section('title', 'Purchase Orders')
@section('page_title', 'Purchase Orders Management')

@section('content')
<div class="card">
    <div class="card-header">
        <div>
            <h2 class="card-title">Purchase Orders List</h2>
            <p class="card-description">Create and view purchasing orders for farm suppliers (Supports Single or Multi-Supplier POs)</p>
        </div>
        <button type="button" class="btn btn-primary" onclick="openModal('addStaffPurchaseModal')">
            <i class="bi bi-cart-plus me-1"></i> Create Purchase Order
        </button>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="custom-table datatable-init">
                <thead>
                    <tr>
                        <th>PO #</th>
                        <th>Supplier</th>
                        <th>DR / Invoice #</th>
                        <th>Purchase Date</th>
                        <th>Total Cost</th>
                        <th>Status</th>
                        <th style="text-align: right;">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($purchases as $p)
                        <tr>
                            <td><strong>{{ $p->purchase_number }}</strong></td>
                            <td>
                                @if($p->is_multi_supplier)
                                    <span class="badge badge-navy" title="{{ $p->supplier_display }}">
                                        <i class="bi bi-people-fill me-1"></i> Multi-Supplier ({{ $p->lines->pluck('supplier_id')->filter()->unique()->count() }})
                                    </span>
                                @else
                                    <span style="font-weight: 600;">{{ $p->supplier->name ?? (method_exists($p->lines->first() ?? null, 'supplier') ? $p->lines->first()?->supplier?->name : 'N/A') }}</span>
                                @endif
                            </td>
                            <td>{{ $p->invoice_dr_number ?? 'Pending Delivery' }}</td>
                            <td style="font-size: 0.8rem; color: var(--text-light);">{{ $p->purchase_date->format('M d, Y') }}</td>
                            <td style="font-weight: 800; color: var(--color-deep-navy);">₱{{ number_format($p->total_amount, 2) }}</td>
                            <td>
                                @if($p->status === 'received')
                                    <span class="badge badge-success">Received</span>
                                @elseif($p->status === 'ordered')
                                    <span class="badge badge-warning">Ordered (In Transit)</span>
                                @else
                                    <span class="badge badge-navy">{{ ucfirst($p->status) }}</span>
                                @endif
                            </td>
                            <td style="text-align: right;">
                                <button type="button" class="btn btn-sm btn-secondary" onclick="viewStaffPurchase({{ $p->id }})">
                                    <i class="bi bi-eye"></i> View
                                </button>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Add Purchase Modal for Staff -->
<div id="addStaffPurchaseModal" class="modal-overlay">
    <div class="modal-dialog modal-xl">
        <div class="modal-header">
            <h3 class="modal-title"><i class="bi bi-cart-plus text-primary me-2"></i> Create Purchase Order</h3>
            <button type="button" class="modal-close-btn" data-close-modal="addStaffPurchaseModal"><i class="bi bi-x-lg"></i></button>
        </div>
        <form action="{{ route('staff.purchases.store') }}" method="POST" class="ajax-form">
            @csrf
            <div class="modal-body">
                <div style="display: grid; grid-template-columns: 2fr 1fr 1fr; gap: 16px; margin-bottom: 16px;">
                    <div class="form-group">
                        <label class="form-label" for="sp_sup">Primary / Default Supplier <span style="font-size: 0.75rem; color: var(--text-muted);">(Optional / Applies to rows)</span></label>
                        <select id="sp_sup" name="supplier_id" class="form-select select2-init" onchange="syncStaffHeaderSupplier(this.value)">
                            <option value="">-- Multi-Supplier (Select Per Item) --</option>
                            @foreach($suppliers as $s)
                                <option value="{{ $s->id }}" data-terms="{{ $s->payment_terms }}">{{ $s->name }} ({{ $s->payment_terms }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="sp_wh">Destination Warehouse <span class="text-danger">*</span></label>
                        <select id="sp_wh" name="warehouse_id" class="form-select select2-init" required>
                            @foreach($warehouses as $w)
                                <option value="{{ $w->id }}">{{ $w->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="sp_date">Purchase Date <span class="text-danger">*</span></label>
                        <input type="date" id="sp_date" name="purchase_date" class="form-control" value="{{ date('Y-m-d') }}" required>
                    </div>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 20px;">
                    <div class="form-group">
                        <label class="form-label" for="sp_dr">Supplier Invoice / DR Reference</label>
                        <input type="text" id="sp_dr" name="invoice_dr_number" class="form-control" placeholder="e.g. DR-10558">
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="sp_terms">Payment Terms</label>
                        <input type="text" id="sp_terms" name="payment_terms" class="form-control" value="Cash">
                    </div>
                </div>

                <!-- Line items table -->
                <div style="margin-bottom: 16px;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
                        <label class="form-label" style="margin-bottom: 0; font-size: 0.95rem;">
                            <i class="bi bi-list-check text-primary me-1"></i> Line Items (Supports Different Suppliers per Item)
                        </label>
                        <button type="button" class="btn btn-sm btn-outline" onclick="addStaffPurchaseLineRow()">
                            <i class="bi bi-plus-lg me-1"></i> Add Item Line
                        </button>
                    </div>

                    <table class="custom-table" style="font-size: 0.84rem;">
                        <thead>
                            <tr>
                                <th style="width: 28%;">Product SKU</th>
                                <th style="width: 22%;">Supplier <span class="text-primary">*</span></th>
                                <th style="width: 14%;">Purchase Unit</th>
                                <th style="width: 11%;">Quantity</th>
                                <th style="width: 11%;">Unit Cost (₱)</th>
                                <th style="width: 10%;">Subtotal (₱)</th>
                                <th style="width: 4%;"></th>
                            </tr>
                        </thead>
                        <tbody id="spLineItemsBody">
                        </tbody>
                        <tfoot>
                            <tr>
                                <td colspan="5" style="text-align: right; font-weight: 800; font-size: 1rem;">Total Order Amount:</td>
                                <td style="font-weight: 800; font-size: 1.1rem; color: var(--color-primary-blue);" id="spTotalAmountDisplay">₱0.00</td>
                                <td></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                <div class="form-group">
                    <label class="form-label" for="sp_notes">Notes / Instructions</label>
                    <textarea id="sp_notes" name="notes" class="form-control" rows="2" placeholder="Delivery notes..."></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline" data-close-modal="addStaffPurchaseModal">Cancel</button>
                <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg me-1"></i> Place Purchase Order</button>
            </div>
        </form>
    </div>
</div>

<!-- View Modal -->
<div id="viewStaffPoModal" class="modal-overlay">
    <div class="modal-dialog modal-xl">
        <div class="modal-header">
            <h3 class="modal-title" id="viewStaffPoTitle"><i class="bi bi-file-earmark-text text-primary me-2"></i> Purchase Order</h3>
            <button type="button" class="modal-close-btn" data-close-modal="viewStaffPoModal"><i class="bi bi-x-lg"></i></button>
        </div>
        <div class="modal-body" id="viewStaffPoBody">
            <div style="text-align: center; padding: 40px;"><div class="spinner-border text-primary"></div></div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-primary" data-close-modal="viewStaffPoModal">Close</button>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    const prodList = {!! json_encode($products) !!};
    const supList = {!! json_encode($suppliers) !!};
    let sIdx = 0;

    function syncStaffHeaderSupplier(supId) {
        if (!supId) return;
        const selectedSupplier = supList.find(s => s.id == supId);
        if (selectedSupplier && selectedSupplier.payment_terms) {
            document.getElementById('sp_terms').value = selectedSupplier.payment_terms;
        }

        document.querySelectorAll('[id^="spSup_"]').forEach(selectEl => {
            if (!selectEl.value) {
                selectEl.value = supId;
            }
        });
    }

    function addStaffPurchaseLineRow() {
        sIdx++;
        const tr = document.createElement('tr');
        tr.id = `spRow_${sIdx}`;

        const headerSup = document.getElementById('sp_sup')?.value || '';

        tr.innerHTML = `
            <td>
                <select name="items[${sIdx}][product_id]" class="form-select form-select-sm" required onchange="onStaffProductSelect(${sIdx}, this.value)">
                    <option value="">Select Product...</option>
                    ${prodList.map(p => `<option value="${p.id}">${p.sku} - ${p.name}</option>`).join('')}
                </select>
            </td>
            <td>
                <select id="spSup_${sIdx}" name="items[${sIdx}][supplier_id]" class="form-select form-select-sm" required>
                    <option value="">Select Supplier...</option>
                    ${supList.map(s => `<option value="${s.id}" ${s.id == headerSup ? 'selected' : ''}>${s.name}</option>`).join('')}
                </select>
            </td>
            <td>
                <select id="spUnit_${sIdx}" name="items[${sIdx}][unit_name]" class="form-select form-select-sm" required onchange="onStaffUnitSelect(${sIdx})">
                    <option value="kg">kg</option>
                </select>
                <input type="hidden" id="spFactor_${sIdx}" name="items[${sIdx}][conversion_factor]" value="1">
            </td>
            <td>
                <input type="number" step="0.01" id="spQty_${sIdx}" name="items[${sIdx}][quantity]" class="form-control form-control-sm" placeholder="Qty" required oninput="calcStaffPoRow(${sIdx})">
            </td>
            <td>
                <input type="number" step="0.01" id="spCost_${sIdx}" name="items[${sIdx}][unit_cost]" class="form-control form-control-sm" placeholder="Cost ₱" required oninput="calcStaffPoRow(${sIdx})">
            </td>
            <td style="font-weight: 700; color: var(--color-deep-navy);" id="spSubtotal_${sIdx}">₱0.00</td>
            <td>
                <button type="button" class="btn btn-sm btn-danger" onclick="document.getElementById('spRow_${sIdx}').remove(); calcStaffPoTotal();">
                    <i class="bi bi-trash"></i>
                </button>
            </td>
        `;

        document.getElementById('spLineItemsBody').appendChild(tr);
    }

    function onStaffProductSelect(idx, productId) {
        const prod = prodList.find(x => x.id == productId);
        const unitSelect = document.getElementById(`spUnit_${idx}`);
        unitSelect.innerHTML = '';

        if (prod && prod.units && prod.units.length > 0) {
            prod.units.forEach(u => {
                const opt = document.createElement('option');
                opt.value = u.unit_name;
                opt.innerText = u.unit_name;
                opt.dataset.factor = u.conversion_factor;
                unitSelect.appendChild(opt);
            });
            document.getElementById(`spFactor_${idx}`).value = prod.units[0].conversion_factor;
        } else if (prod) {
            const opt = document.createElement('option');
            opt.value = prod.base_unit;
            opt.innerText = prod.base_unit;
            opt.dataset.factor = 1;
            unitSelect.appendChild(opt);
            document.getElementById(`spFactor_${idx}`).value = 1;
        }

        const supSelect = document.getElementById(`spSup_${idx}`);
        if (prod && prod.suppliers && prod.suppliers.length > 0 && (!supSelect.value || supSelect.value === '')) {
            supSelect.value = prod.suppliers[0].id;
        }

        calcStaffPoRow(idx);
    }

    function onStaffUnitSelect(idx) {
        const unitSelect = document.getElementById(`spUnit_${idx}`);
        const selectedOpt = unitSelect.options[unitSelect.selectedIndex];
        document.getElementById(`spFactor_${idx}`).value = selectedOpt.dataset.factor || 1;
        calcStaffPoRow(idx);
    }

    function calcStaffPoRow(idx) {
        const qty = parseFloat(document.getElementById(`spQty_${idx}`)?.value) || 0;
        const cost = parseFloat(document.getElementById(`spCost_${idx}`)?.value) || 0;
        const subtotal = qty * cost;
        const el = document.getElementById(`spSubtotal_${idx}`);
        if (el) el.innerText = '₱' + subtotal.toFixed(2);
        calcStaffPoTotal();
    }

    function calcStaffPoTotal() {
        let total = 0;
        document.querySelectorAll('[id^="spSubtotal_"]').forEach(el => {
            const val = parseFloat(el.innerText.replace('₱', '')) || 0;
            total += val;
        });
        document.getElementById('spTotalAmountDisplay').innerText = '₱' + total.toFixed(2);
    }

    function viewStaffPurchase(id) {
        openModal('viewStaffPoModal');
        document.getElementById('viewStaffPoBody').innerHTML = '<div style="text-align: center; padding: 40px;"><div class="spinner-border text-primary"></div></div>';

        fetch(`/staff/purchases/${id}`, { credentials: 'same-origin', headers: { 'Accept': 'application/json' } })
            .then(res => res.json())
            .then(res => {
                const p = res.data;
                document.getElementById('viewStaffPoTitle').innerText = `PO #${p.purchase_number}`;
                document.getElementById('viewStaffPoBody').innerHTML = `
                    <div style="background: var(--body-bg); padding: 14px; border-radius: var(--radius-sm); margin-bottom: 16px;">
                        <div>Supplier: <strong>${p.is_multi_supplier ? '<span class="badge badge-navy"><i class="bi bi-people-fill me-1"></i> Multi-Supplier PO</span>' : (p.supplier ? p.supplier.name : 'N/A')}</strong></div>
                        <div>Date: <strong>${p.formatted_purchase_date || p.purchase_date}</strong> | DR: <strong>${p.invoice_dr_number || 'Pending'}</strong></div>
                    </div>
                    <table class="custom-table" style="font-size: 0.84rem;">
                        <thead>
                            <tr>
                                <th>Product</th>
                                <th>Supplier</th>
                                <th>Unit</th>
                                <th>Qty Ordered</th>
                                <th>Unit Cost</th>
                                <th>Subtotal</th>
                            </tr>
                        </thead>
                        <tbody>
                            ${p.lines.map(l => `
                                <tr>
                                    <td><strong>${l.product ? l.product.name : 'N/A'}</strong> <span style="font-size: 0.75rem; color: var(--text-muted);">(${l.product ? l.product.sku : ''})</span></td>
                                    <td>
                                        <span class="badge badge-navy" style="font-size: 0.78rem;">
                                            <i class="bi bi-truck me-1"></i>${l.supplier ? l.supplier.name : (p.supplier ? p.supplier.name : 'N/A')}
                                        </span>
                                    </td>
                                    <td>${l.unit_name}</td>
                                    <td>${parseFloat(l.quantity_ordered).toFixed(2)}</td>
                                    <td>₱${parseFloat(l.unit_cost).toFixed(2)}</td>
                                    <td style="font-weight: 700;">₱${parseFloat(l.subtotal).toFixed(2)}</td>
                                </tr>
                            `).join('')}
                        </tbody>
                        <tfoot>
                            <tr>
                                <td colspan="5" style="text-align: right; font-weight: 800;">Total:</td>
                                <td style="font-weight: 800; font-size: 1.05rem; color: var(--color-primary-blue);">₱${parseFloat(p.total_amount).toFixed(2)}</td>
                            </tr>
                        </tfoot>
                    </table>
                `;
            });
    }

    document.addEventListener('DOMContentLoaded', () => {
        addStaffPurchaseLineRow();
    });
</script>
@endpush
