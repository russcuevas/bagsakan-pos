@extends('layouts.app')

@section('title', 'Purchases & Inbound Deliveries')
@section('page_title', 'Purchases & Supplier Invoices')

@section('content')
<div class="card">
    <div class="card-header">
        <div>
            <h2 class="card-title">Purchases & Inbound Receipts</h2>
            <p class="card-description">Track purchase orders, supplier delivery receipts (DR), and batch receiving</p>
        </div>
        <button type="button" class="btn btn-primary" onclick="openModal('addPurchaseModal')">
            <i class="bi bi-cart-plus me-1"></i> New Purchase Entry / PO
        </button>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="custom-table datatable-init">
                <thead>
                    <tr>
                        <th>PO Number</th>
                        <th>Supplier</th>
                        <th>DR / Invoice #</th>
                        <th>Warehouse</th>
                        <th>Purchase Date</th>
                        <th>Total Amount</th>
                        <th>Status</th>
                        <th style="text-align: right;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($purchases as $p)
                        <tr>
                            <td><strong>{{ $p->purchase_number }}</strong></td>
                            <td>{{ $p->supplier->name ?? 'N/A' }}</td>
                            <td>{{ $p->invoice_dr_number ?? 'Pending DR' }}</td>
                            <td>{{ $p->warehouse->name ?? 'Main' }}</td>
                            <td style="font-size: 0.8rem; color: var(--text-light);">{{ $p->purchase_date->format('M d, Y') }}</td>
                            <td style="font-weight: 800; color: var(--color-deep-navy);">₱{{ number_format($p->total_amount, 2) }}</td>
                            <td>
                                @if($p->status === 'received')
                                    <span class="badge badge-success"><i class="bi bi-check-circle me-1"></i> Received & Stocked</span>
                                @elseif($p->status === 'ordered')
                                    <span class="badge badge-warning"><i class="bi bi-hourglass-split me-1"></i> Awaiting Delivery</span>
                                @else
                                    <span class="badge badge-navy">{{ ucfirst($p->status) }}</span>
                                @endif
                            </td>
                            <td style="text-align: right;">
                                <div style="display: inline-flex; gap: 6px;">
                                    @if($p->status === 'ordered')
                                        <button type="button" class="btn btn-sm btn-success" onclick="openReceiveModal({{ $p->id }})" title="Receive Goods & Stock">
                                            <i class="bi bi-box-arrow-in-down me-1"></i> Receive
                                        </button>
                                    @endif
                                    <button type="button" class="btn btn-sm btn-secondary" onclick="viewPurchase({{ $p->id }})" title="View PO">
                                        <i class="bi bi-eye"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Add Purchase Modal -->
<div id="addPurchaseModal" class="modal-overlay">
    <div class="modal-dialog modal-xl">
        <div class="modal-header">
            <h3 class="modal-title"><i class="bi bi-cart-plus text-primary me-2"></i> Create Inbound Purchase Order / Delivery Receipt</h3>
            <button type="button" class="modal-close-btn" data-close-modal="addPurchaseModal"><i class="bi bi-x-lg"></i></button>
        </div>
        <form action="{{ route('admin.purchases.store') }}" method="POST" class="ajax-form">
            @csrf
            <div class="modal-body">
                <div style="display: grid; grid-template-columns: 2fr 1fr 1fr; gap: 16px; margin-bottom: 16px;">
                    <div class="form-group">
                        <label class="form-label" for="po_sup">Supplier <span class="text-danger">*</span></label>
                        <select id="po_sup" name="supplier_id" class="form-select select2-init" required>
                            <option value="">Select Supplier</option>
                            @foreach($suppliers as $s)
                                <option value="{{ $s->id }}">{{ $s->name }} ({{ $s->payment_terms }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="po_wh">Destination Warehouse <span class="text-danger">*</span></label>
                        <select id="po_wh" name="warehouse_id" class="form-select select2-init" required>
                            @foreach($warehouses as $w)
                                <option value="{{ $w->id }}">{{ $w->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="po_date">Purchase Date <span class="text-danger">*</span></label>
                        <input type="date" id="po_date" name="purchase_date" class="form-control" value="{{ date('Y-m-d') }}" required>
                    </div>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 20px;">
                    <div class="form-group">
                        <label class="form-label" for="po_dr">Supplier Invoice / DR Number</label>
                        <input type="text" id="po_dr" name="invoice_dr_number" class="form-control" placeholder="e.g. DR 10558 or SI 88910">
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="po_terms">Payment Terms</label>
                        <input type="text" id="po_terms" name="payment_terms" class="form-control" placeholder="e.g. 15 Days, Cash, 30 Days" value="Cash">
                    </div>
                </div>

                <!-- Line items table -->
                <div style="margin-bottom: 16px;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
                        <label class="form-label" style="margin-bottom: 0; font-size: 0.95rem;">
                            <i class="bi bi-list-check text-primary me-1"></i> Line Items (SKU, Purchase Unit, Buying Cost)
                        </label>
                        <button type="button" class="btn btn-sm btn-outline" onclick="addPurchaseLineRow()">
                            <i class="bi bi-plus-lg me-1"></i> Add Item Line
                        </button>
                    </div>

                    <table class="custom-table" style="font-size: 0.84rem;">
                        <thead>
                            <tr>
                                <th style="width: 35%;">Product SKU</th>
                                <th style="width: 15%;">Purchase Unit</th>
                                <th style="width: 15%;">Quantity</th>
                                <th style="width: 15%;">Unit Cost (₱)</th>
                                <th style="width: 15%;">Subtotal (₱)</th>
                                <th style="width: 5%;"></th>
                            </tr>
                        </thead>
                        <tbody id="poLineItemsBody">
                            <!-- Populated dynamically via JS -->
                        </tbody>
                        <tfoot>
                            <tr>
                                <td colspan="4" style="text-align: right; font-weight: 800; font-size: 1rem;">Total Order Amount:</td>
                                <td style="font-weight: 800; font-size: 1.1rem; color: var(--color-primary-blue);" id="poTotalAmountDisplay">₱0.00</td>
                                <td></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                <div style="background: var(--color-mist-blue); border: 1.5px solid #d2eaf8; border-radius: var(--radius-sm); padding: 14px 18px; margin-bottom: 16px; display: flex; align-items: center; gap: 12px;">
                    <input type="checkbox" id="auto_receive" name="auto_receive" value="1" style="width: 18px; height: 18px; cursor: pointer;">
                    <label for="auto_receive" style="cursor: pointer; margin-bottom: 0; font-size: 0.88rem; font-weight: 600; color: var(--color-deep-navy);">
                        Confirm and Post Goods Immediately to Warehouse Inventory (Generates batches & updates weighted average cost)
                    </label>
                </div>

                <div class="form-group">
                    <label class="form-label" for="po_notes">Notes</label>
                    <textarea id="po_notes" name="notes" class="form-control" rows="2" placeholder="Driver name, plate number, inspection remarks..."></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline" data-close-modal="addPurchaseModal">Cancel</button>
                <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg me-1"></i> Submit Purchase Entry</button>
            </div>
        </form>
    </div>
</div>

<!-- Receive Goods Modal -->
<div id="receiveGoodsModal" class="modal-overlay">
    <div class="modal-dialog modal-lg">
        <div class="modal-header">
            <h3 class="modal-title"><i class="bi bi-box-arrow-in-down text-success me-2"></i> Receive Inbound Delivery</h3>
            <button type="button" class="modal-close-btn" data-close-modal="receiveGoodsModal"><i class="bi bi-x-lg"></i></button>
        </div>
        <form id="receiveGoodsForm" method="POST" class="ajax-form">
            @csrf
            <div class="modal-body" id="receiveGoodsBody">
                <div style="text-align: center; padding: 40px;"><div class="spinner-border text-primary"></div></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline" data-close-modal="receiveGoodsModal">Cancel</button>
                <button type="submit" class="btn btn-success"><i class="bi bi-check-lg me-1"></i> Confirm & Post to Inventory</button>
            </div>
        </form>
    </div>
</div>

<!-- View Purchase Details Modal -->
<div id="viewPurchaseModal" class="modal-overlay">
    <div class="modal-dialog modal-lg">
        <div class="modal-header">
            <h3 class="modal-title" id="viewPoTitle"><i class="bi bi-file-earmark-text text-primary me-2"></i> Purchase Order Details</h3>
            <button type="button" class="modal-close-btn" data-close-modal="viewPurchaseModal"><i class="bi bi-x-lg"></i></button>
        </div>
        <div class="modal-body" id="viewPurchaseBody">
            <div style="text-align: center; padding: 40px;"><div class="spinner-border text-primary"></div></div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-primary" data-close-modal="viewPurchaseModal">Close</button>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    const availableProducts = {!! json_encode($products) !!};
    let poLineIdx = 0;

    function addPurchaseLineRow() {
        poLineIdx++;
        const tr = document.createElement('tr');
        tr.id = `poRow_${poLineIdx}`;

        tr.innerHTML = `
            <td>
                <select name="items[${poLineIdx}][product_id]" class="form-select form-select-sm" required onchange="onProductSelect(${poLineIdx}, this.value)">
                    <option value="">Select Product...</option>
                    ${availableProducts.map(p => `<option value="${p.id}">${p.sku} - ${p.name}</option>`).join('')}
                </select>
            </td>
            <td>
                <select id="poUnit_${poLineIdx}" name="items[${poLineIdx}][unit_name]" class="form-select form-select-sm" required onchange="onUnitSelect(${poLineIdx})">
                    <option value="kg">kg</option>
                </select>
                <input type="hidden" id="poFactor_${poLineIdx}" name="items[${poLineIdx}][conversion_factor]" value="1">
            </td>
            <td>
                <input type="number" step="0.01" id="poQty_${poLineIdx}" name="items[${poLineIdx}][quantity]" class="form-control form-control-sm" placeholder="Qty" required oninput="calcPoRow(${poLineIdx})">
            </td>
            <td>
                <input type="number" step="0.01" id="poCost_${poLineIdx}" name="items[${poLineIdx}][unit_cost]" class="form-control form-control-sm" placeholder="Cost ₱" required oninput="calcPoRow(${poLineIdx})">
            </td>
            <td style="font-weight: 700; color: var(--color-deep-navy);" id="poSubtotal_${poLineIdx}">₱0.00</td>
            <td>
                <button type="button" class="btn btn-sm btn-danger" onclick="document.getElementById('poRow_${poLineIdx}').remove(); calcPoTotal();">
                    <i class="bi bi-trash"></i>
                </button>
            </td>
        `;

        document.getElementById('poLineItemsBody').appendChild(tr);
    }

    function onProductSelect(idx, productId) {
        const prod = availableProducts.find(x => x.id == productId);
        const unitSelect = document.getElementById(`poUnit_${idx}`);
        unitSelect.innerHTML = '';

        if (prod && prod.units && prod.units.length > 0) {
            prod.units.forEach(u => {
                const opt = document.createElement('option');
                opt.value = u.unit_name;
                opt.innerText = u.unit_name;
                opt.dataset.factor = u.conversion_factor;
                unitSelect.appendChild(opt);
            });
            document.getElementById(`poFactor_${idx}`).value = prod.units[0].conversion_factor;
        } else if (prod) {
            const opt = document.createElement('option');
            opt.value = prod.base_unit;
            opt.innerText = prod.base_unit;
            opt.dataset.factor = 1;
            unitSelect.appendChild(opt);
            document.getElementById(`poFactor_${idx}`).value = 1;
        }
        calcPoRow(idx);
    }

    function onUnitSelect(idx) {
        const unitSelect = document.getElementById(`poUnit_${idx}`);
        const selectedOpt = unitSelect.options[unitSelect.selectedIndex];
        document.getElementById(`poFactor_${idx}`).value = selectedOpt.dataset.factor || 1;
        calcPoRow(idx);
    }

    function calcPoRow(idx) {
        const qty = parseFloat(document.getElementById(`poQty_${idx}`)?.value) || 0;
        const cost = parseFloat(document.getElementById(`poCost_${idx}`)?.value) || 0;
        const subtotal = qty * cost;
        const el = document.getElementById(`poSubtotal_${idx}`);
        if (el) el.innerText = '₱' + subtotal.toFixed(2);
        calcPoTotal();
    }

    function calcPoTotal() {
        let total = 0;
        document.querySelectorAll('[id^="poSubtotal_"]').forEach(el => {
            const val = parseFloat(el.innerText.replace('₱', '')) || 0;
            total += val;
        });
        document.getElementById('poTotalAmountDisplay').innerText = '₱' + total.toFixed(2);
    }

    function viewPurchase(id) {
        openModal('viewPurchaseModal');
        document.getElementById('viewPurchaseBody').innerHTML = '<div style="text-align: center; padding: 40px;"><div class="spinner-border text-primary"></div></div>';

        fetch(`/admin/purchases/${id}`, { credentials: 'same-origin', headers: { 'Accept': 'application/json' } })
            .then(res => res.json())
            .then(res => {
                const p = res.data;
                document.getElementById('viewPoTitle').innerText = `Purchase Order ${p.purchase_number}`;

                document.getElementById('viewPurchaseBody').innerHTML = `
                    <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 16px; background: var(--body-bg); padding: 14px; border-radius: var(--radius-sm); margin-bottom: 16px;">
                        <div>
                            <div style="font-size: 0.74rem; color: var(--text-light);">Supplier</div>
                            <div style="font-weight: 700; color: var(--color-deep-navy);">${p.supplier ? p.supplier.name : 'N/A'}</div>
                        </div>
                        <div>
                            <div style="font-size: 0.74rem; color: var(--text-light);">DR / Invoice #</div>
                            <div style="font-weight: 700;">${p.invoice_dr_number || 'N/A'}</div>
                        </div>
                        <div>
                            <div style="font-size: 0.74rem; color: var(--text-light);">Purchase Date</div>
                            <div style="font-weight: 700;">${p.formatted_purchase_date || formatDateTime(p.created_at || p.purchase_date)}</div>
                        </div>
                    </div>

                    <h4 style="font-size: 0.9rem; font-weight: 700; margin-bottom: 8px;">Ordered Items</h4>
                    <table class="custom-table" style="font-size: 0.84rem;">
                        <thead>
                            <tr>
                                <th>Product</th>
                                <th>Unit</th>
                                <th>Qty Ordered</th>
                                <th>Qty Received</th>
                                <th>Unit Cost</th>
                                <th>Subtotal</th>
                            </tr>
                        </thead>
                        <tbody>
                            ${p.lines.map(l => `
                                <tr>
                                    <td><strong>${l.product ? l.product.name : 'N/A'}</strong> (${l.product ? l.product.sku : ''})</td>
                                    <td>${l.unit_name}</td>
                                    <td>${parseFloat(l.quantity_ordered).toFixed(2)}</td>
                                    <td><span class="badge ${parseFloat(l.quantity_received) >= parseFloat(l.quantity_ordered) ? 'badge-success' : 'badge-warning'}">${parseFloat(l.quantity_received).toFixed(2)}</span></td>
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

    function openReceiveModal(id) {
        openModal('receiveGoodsModal');
        document.getElementById('receiveGoodsBody').innerHTML = '<div style="text-align: center; padding: 40px;"><div class="spinner-border text-primary"></div></div>';
        document.getElementById('receiveGoodsForm').action = `/admin/purchases/${id}/receive`;

        fetch(`/admin/purchases/${id}`, { credentials: 'same-origin', headers: { 'Accept': 'application/json' } })
            .then(res => res.json())
            .then(res => {
                const p = res.data;
                document.getElementById('receiveGoodsBody').innerHTML = `
                    <div style="background: var(--color-mist-blue); padding: 12px 16px; border-radius: var(--radius-sm); margin-bottom: 16px;">
                        <div style="font-weight: 700; color: var(--color-deep-navy);">Receiving for PO: ${p.purchase_number} (${p.supplier ? p.supplier.name : ''})</div>
                        <div style="font-size: 0.8rem; color: var(--text-muted);">Confirm actual quantities delivered into ${p.warehouse ? p.warehouse.name : 'Warehouse'}</div>
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 16px;">
                        <div class="form-group">
                            <label class="form-label">Delivery Receipt (DR) / Invoice # <span class="text-danger">*</span></label>
                            <input type="text" name="invoice_dr_number" class="form-control" value="${p.invoice_dr_number || ''}" placeholder="e.g. DR-10558" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Receipt Date <span class="text-danger">*</span></label>
                            <input type="date" name="receipt_date" class="form-control" value="{{ date('Y-m-d') }}" required>
                        </div>
                    </div>

                    <h4 style="font-size: 0.9rem; font-weight: 700; margin-bottom: 8px;">Line Quantities Received</h4>
                    <table class="custom-table" style="font-size: 0.84rem;">
                        <thead>
                            <tr>
                                <th>Product SKU</th>
                                <th>Unit</th>
                                <th>Ordered</th>
                                <th>Actual Quantity to Receive</th>
                            </tr>
                        </thead>
                        <tbody>
                            ${p.lines.map((l, i) => `
                                <tr>
                                    <td>
                                        <strong>${l.product ? l.product.name : 'N/A'}</strong>
                                        <input type="hidden" name="items[${i}][line_id]" value="${l.id}">
                                    </td>
                                    <td><span class="badge badge-navy">${l.unit_name}</span></td>
                                    <td>${parseFloat(l.quantity_ordered).toFixed(2)}</td>
                                    <td>
                                        <input type="number" step="0.01" name="items[${i}][quantity_received]" class="form-control form-control-sm" value="${parseFloat(l.quantity_ordered) - parseFloat(l.quantity_received)}" required>
                                    </td>
                                </tr>
                            `).join('')}
                        </tbody>
                    </table>
                `;
            });
    }

    document.addEventListener('DOMContentLoaded', () => {
        addPurchaseLineRow();
    });
</script>
@endpush
