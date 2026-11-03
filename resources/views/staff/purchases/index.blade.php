@extends('layouts.app')

@section('title', 'Purchase Orders')
@section('page_title', 'Purchase Orders Management')

@section('content')
    <div class="card">
        <div class="card-header">
            <div>
                <h2 class="card-title">Purchase Orders List</h2>
                <p class="card-description">Create and view purchasing orders for farm suppliers (Supports Single or
                    Multi-Supplier POs)</p>
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
                            <th>Ordered / Received</th>
                            <th>Total Cost</th>
                            <th>Status</th>
                            <th style="text-align: right; min-width: 220px;">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($purchases as $p)
                            @php
                                $totalOrdered = $p->total_ordered_quantity;
                                $totalReceived = $p->total_received_quantity;
                                $deficit = $p->deficit_quantity;
                                $isPartial = $p->is_partial;
                            @endphp
                            <tr>
                                <td><strong>{{ $p->purchase_number }}</strong></td>
                                <td>
                                    @if ($p->is_multi_supplier)
                                        <span class="badge badge-navy" title="{{ $p->supplier_display }}">
                                            <i class="bi bi-people-fill me-1"></i> Multi-Supplier
                                            ({{ $p->lines->pluck('supplier_id')->filter()->unique()->count() }})
                                        </span>
                                    @else
                                        <span
                                            style="font-weight: 600;">{{ $p->supplier->name ?? (method_exists($p->lines->first() ?? null, 'supplier') ? $p->lines->first()?->supplier?->name : 'N/A') }}</span>
                                    @endif
                                </td>
                                <td>{{ $p->invoice_dr_number ?? 'Pending Delivery' }}</td>
                                <td style="font-size: 0.8rem; color: var(--text-light);">
                                    {{ $p->purchase_date->format('M d, Y') }}</td>
                                <td>
                                    <div style="font-size: 0.82rem;">
                                        <span>Ordered: <strong>{{ number_format($totalOrdered, 1) }}</strong></span>
                                    </div>
                                    <div style="font-size: 0.82rem;">
                                        <span>Received: <strong
                                                class="{{ $totalReceived > 0 ? 'text-success' : 'text-muted' }}">{{ number_format($totalReceived, 1) }}</strong></span>
                                    </div>
                                    @if ($deficit > 0 && $totalReceived > 0)
                                        <div style="font-size: 0.75rem; color: #dc2626; font-weight: 700;">
                                            Deficit: {{ number_format($deficit, 1) }} to follow
                                        </div>
                                    @endif
                                </td>
                                <td style="font-weight: 800; color: var(--color-deep-navy);">
                                    ₱{{ number_format($p->total_amount, 2) }}</td>
                                <td>
                                    @if ($p->status === 'received' && $deficit <= 0)
                                        <span class="badge badge-success"><i class="bi bi-check-circle me-1"></i>
                                            Received</span>
                                    @elseif($isPartial || $p->status === 'partial' || ($totalReceived > 0 && $deficit > 0))
                                        <span class="badge badge-warning" style="background: #f59e0b; color: #fff;">
                                            <i class="bi bi-hourglass-split me-1"></i> Received Partial
                                        </span>
                                        <div style="font-size: 0.72rem; margin-top: 3px; font-weight: 700; color: #b45309;">
                                            Deficit: {{ number_format($deficit, 1) }} left
                                        </div>
                                    @elseif($p->status === 'ordered')
                                        <span class="badge badge-navy"><i class="bi bi-clock me-1"></i> Ordered (Awaiting
                                            Delivery)</span>
                                    @else
                                        <span class="badge badge-navy">{{ ucfirst($p->status) }}</span>
                                    @endif
                                </td>
                                <td style="text-align: right;">
                                    <div
                                        style="display: inline-flex; gap: 4px; align-items: center; justify-content: flex-end; flex-wrap: wrap;">
                                        @if ($p->status !== 'received' || $deficit > 0)
                                            <button type="button" class="btn btn-sm btn-success"
                                                onclick="openStaffReceiveModal({{ $p->id }})"
                                                title="Receive Goods / Inbound Delivery">
                                                <i class="bi bi-box-arrow-in-down me-1"></i>
                                                {{ $totalReceived > 0 ? 'Receive More' : 'Receive' }}
                                            </button>
                                        @endif

                                        @if ($totalReceived > 0)
                                            <button type="button" class="btn btn-sm btn-info text-white"
                                                onclick="viewReceivingHistory({{ $p->id }}, 'staff')"
                                                title="View Receiving History & Delivery Breakdown">
                                                <i class="bi bi-clock-history me-1"></i> History
                                            </button>
                                        @endif

                                        <button type="button" class="btn btn-sm btn-secondary"
                                            onclick="viewStaffPurchase({{ $p->id }})" title="View Purchase Order">
                                            <i class="bi bi-eye"></i> View
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

    <!-- Add Purchase Modal for Staff -->
    <div id="addStaffPurchaseModal" class="modal-overlay">
        <div class="modal-dialog modal-xl">
            <div class="modal-header">
                <h3 class="modal-title"><i class="bi bi-cart-plus text-primary me-2"></i> Create Purchase Order</h3>
                <button type="button" class="modal-close-btn" data-close-modal="addStaffPurchaseModal"><i
                        class="bi bi-x-lg"></i></button>
            </div>
            <form action="{{ route('staff.purchases.store') }}" method="POST" class="ajax-form">
                @csrf
                <div class="modal-body">
                    <div style="display: grid; grid-template-columns: 2fr 1fr 1fr; gap: 16px; margin-bottom: 16px;">
                        <div class="form-group">
                            <label class="form-label" for="sp_sup">Primary / Default Supplier <span
                                    style="font-size: 0.75rem; color: var(--text-muted);">(Optional / Applies to
                                    rows)</span></label>
                            <select id="sp_sup" name="supplier_id" class="form-select select2-init"
                                onchange="syncStaffHeaderSupplier(this.value)">
                                <option value="">-- Multi-Supplier (Select Per Item) --</option>
                                @foreach ($suppliers as $s)
                                    <option value="{{ $s->id }}" data-terms="{{ $s->payment_terms }}">
                                        {{ $s->name }} ({{ $s->payment_terms }})</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="sp_wh">Destination Warehouse <span
                                    class="text-danger">*</span></label>
                            <select id="sp_wh" name="warehouse_id" class="form-select select2-init" required>
                                @foreach ($warehouses as $w)
                                    <option value="{{ $w->id }}">{{ $w->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="sp_date">Purchase Date <span
                                    class="text-danger">*</span></label>
                            <input type="date" id="sp_date" name="purchase_date" class="form-control"
                                value="{{ date('Y-m-d') }}" required>
                        </div>
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 20px;">
                        <div class="form-group">
                            <label class="form-label" for="sp_dr">Supplier Invoice / DR Reference</label>
                            <input type="text" id="sp_dr" name="invoice_dr_number" class="form-control"
                                placeholder="e.g. DR-10558">
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="sp_terms">Payment Terms</label>
                            <input type="text" id="sp_terms" name="payment_terms" class="form-control"
                                value="Cash">
                        </div>
                    </div>

                    <!-- Line items table -->
                    <div style="margin-bottom: 16px;">
                        <div
                            style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
                            <label class="form-label" style="margin-bottom: 0; font-size: 0.95rem;">
                                <i class="bi bi-list-check text-primary me-1"></i> Line Items (Supports Different Suppliers
                                per Item)
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
                                    <td colspan="5" style="text-align: right; font-weight: 800; font-size: 1rem;">Total
                                        Order Amount:</td>
                                    <td style="font-weight: 800; font-size: 1.1rem; color: var(--color-primary-blue);"
                                        id="spTotalAmountDisplay">₱0.00</td>
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
                    <button type="button" class="btn btn-outline"
                        data-close-modal="addStaffPurchaseModal">Cancel</button>
                    <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg me-1"></i> Place Purchase
                        Order</button>
                </div>
            </form>
        </div>
    </div>

    <!-- View Modal -->
    <div id="viewStaffPoModal" class="modal-overlay">
        <div class="modal-dialog modal-xl">
            <div class="modal-header">
                <h3 class="modal-title" id="viewStaffPoTitle"><i class="bi bi-file-earmark-text text-primary me-2"></i>
                    Purchase Order</h3>
                <button type="button" class="modal-close-btn" data-close-modal="viewStaffPoModal"><i
                        class="bi bi-x-lg"></i></button>
            </div>
            <div class="modal-body" id="viewStaffPoBody">
                <div style="text-align: center; padding: 40px;">
                    <div class="spinner-border text-primary"></div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary" data-close-modal="viewStaffPoModal">Close</button>
            </div>
        </div>
    </div>
    <!-- Receive Goods Modal for Staff -->
    <div id="staffReceiveGoodsModal" class="modal-overlay">
        <div class="modal-dialog modal-xl" style="max-width: 950px;">
            <div class="modal-header">
                <h3 class="modal-title"><i class="bi bi-box-arrow-in-down text-success me-2"></i> Receive Inbound Delivery
                    (Full or Partial)</h3>
                <button type="button" class="modal-close-btn" data-close-modal="staffReceiveGoodsModal"><i
                        class="bi bi-x-lg"></i></button>
            </div>
            <form id="staffReceiveGoodsForm" method="POST" onsubmit="submitStaffReceiveGoods(event)">
                @csrf
                <div class="modal-body" id="staffReceiveGoodsBody">
                    <div style="text-align: center; padding: 40px;">
                        <div class="spinner-border text-primary"></div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline"
                        data-close-modal="staffReceiveGoodsModal">Cancel</button>
                    <button type="submit" class="btn btn-success" id="staffSubmitReceiveBtn">
                        <i class="bi bi-check-lg me-1"></i> Confirm Inbound Receipt
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Receiving History & Delivery Breakdown Modal -->
    <div id="receivingHistoryModal" class="modal-overlay">
        <div class="modal-dialog modal-xl" style="max-width: 950px;">
            <div class="modal-header">
                <h3 class="modal-title" id="historyModalTitle"><i class="bi bi-clock-history text-info me-2"></i>
                    Delivery & Receiving History Breakdown</h3>
                <button type="button" class="modal-close-btn" data-close-modal="receivingHistoryModal"><i
                        class="bi bi-x-lg"></i></button>
            </div>
            <div class="modal-body" id="receivingHistoryBody">
                <div style="text-align: center; padding: 40px;">
                    <div class="spinner-border text-primary"></div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-close-modal="receivingHistoryModal">Close</button>
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
            document.getElementById('viewStaffPoBody').innerHTML =
                '<div style="text-align: center; padding: 40px;"><div class="spinner-border text-primary"></div></div>';

            fetch(`/staff/purchases/${id}`, {
                    credentials: 'same-origin',
                    headers: {
                        'Accept': 'application/json'
                    }
                })
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

        function openStaffReceiveModal(id) {
            openModal('staffReceiveGoodsModal');
            document.getElementById('staffReceiveGoodsBody').innerHTML =
                '<div style="text-align: center; padding: 40px;"><div class="spinner-border text-primary"></div></div>';
            document.getElementById('staffReceiveGoodsForm').action = `/staff/purchases/${id}/receive`;

            fetch(`/staff/purchases/${id}`, {
                    credentials: 'same-origin',
                    headers: {
                        'Accept': 'application/json'
                    }
                })
                .then(res => res.json())
                .then(res => {
                    const p = res.data;
                    let rowsHtml = '';

                    p.lines.forEach((l, i) => {
                        const ordered = parseFloat(l.quantity_ordered) || 0;
                        const prevReceived = parseFloat(l.quantity_received) || 0;
                        const deficit = Math.max(0, ordered - prevReceived);

                        rowsHtml += `
                        <tr>
                            <td style="padding: 10px 8px;">
                                <strong>${l.product ? l.product.name : 'N/A'}</strong>
                                <div style="font-size: 0.74rem; color: var(--text-muted);">${l.product ? l.product.sku : ''}</div>
                                <input type="hidden" name="items[${i}][line_id]" value="${l.id}">
                            </td>
                            <td style="padding: 10px 8px;">
                                <span class="badge badge-navy" style="font-size: 0.76rem;">
                                    <i class="bi bi-truck me-1"></i>${l.supplier ? l.supplier.name : (p.supplier ? p.supplier.name : 'N/A')}
                                </span>
                            </td>
                            <td style="padding: 10px 8px;"><span class="badge badge-navy">${l.unit_name}</span></td>
                            <td style="padding: 10px 8px; text-align: center; font-weight: 700;">${ordered.toFixed(2)}</td>
                            <td style="padding: 10px 8px; text-align: center; color: var(--color-deep-navy); font-weight: 600;">${prevReceived.toFixed(2)}</td>
                            <td style="padding: 10px 8px; text-align: center; font-weight: 700; color: ${deficit > 0 ? '#b45309' : '#16a34a'};">
                                ${deficit.toFixed(2)}
                            </td>
                            <td style="padding: 10px 8px; width: 140px;">
                                <input type="number" step="0.01" min="0" max="${deficit > 0 ? deficit : ordered}" 
                                    name="items[${i}][quantity_received]" id="receive_qty_${i}" 
                                    class="form-control form-control-sm text-center fw-bold" 
                                    value="${deficit.toFixed(2)}" 
                                    oninput="checkReceiveDeficits(${p.lines.length})" required>
                                <div id="deficit_hint_${i}" style="font-size: 0.72rem; color: var(--text-muted); margin-top: 2px; text-align: center;">
                                    Remaining: 0.00
                                </div>
                            </td>
                        </tr>
                    `;
                    });

                    document.getElementById('staffReceiveGoodsBody').innerHTML = `
                    <div style="background: var(--color-mist-blue); border: 1px solid #bfdbfe; padding: 14px 18px; border-radius: 8px; margin-bottom: 16px;">
                        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px;">
                            <div>
                                <div style="font-weight: 800; font-size: 1rem; color: var(--color-deep-navy);">Receiving for PO: ${p.purchase_number}</div>
                                <div style="font-size: 0.8rem; color: var(--text-muted);">
                                    Destination Warehouse: <strong>${p.warehouse ? p.warehouse.name : 'Main Warehouse'}</strong> | 
                                    Supplier: <strong>${p.is_multi_supplier ? 'Multi-Supplier' : (p.supplier ? p.supplier.name : 'N/A')}</strong>
                                </div>
                            </div>
                            <div id="receiveStatusPreviewBadge">
                                <span class="badge badge-success"><i class="bi bi-check-circle me-1"></i> Full Delivery</span>
                            </div>
                        </div>
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 16px;">
                        <div class="form-group">
                            <label class="form-label">Delivery Receipt (DR) / Supplier Invoice # <span class="text-danger">*</span></label>
                            <input type="text" name="invoice_dr_number" class="form-control" placeholder="e.g. DR-10558 or SI-8891" required>
                            <div style="font-size: 0.74rem; color: var(--text-muted); margin-top: 2px;">Enter the DR number for this shipment / batch.</div>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Delivery Receipt Date <span class="text-danger">*</span></label>
                            <input type="date" name="receipt_date" class="form-control" value="{{ date('Y-m-d') }}" required>
                        </div>
                    </div>

                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                        <h4 style="font-size: 0.9rem; font-weight: 700; margin-bottom: 0;">Items to Receive for this Delivery:</h4>
                        <span style="font-size: 0.76rem; color: var(--text-muted);">Deficit will auto-update based on quantity entered.</span>
                    </div>

                    <div class="table-responsive" style="border: 1px solid var(--card-border); border-radius: 8px; margin-bottom: 16px;">
                        <table class="table mb-0" style="font-size: 0.84rem; width: 100%;">
                            <thead style="background: var(--color-mist-blue);">
                                <tr>
                                    <th>Product SKU</th>
                                    <th>Supplier</th>
                                    <th>Unit</th>
                                    <th style="text-align: center;">Ordered</th>
                                    <th style="text-align: center;">Prev Received</th>
                                    <th style="text-align: center;">Deficit to Date</th>
                                    <th style="text-align: center;">Receive Now</th>
                                </tr>
                            </thead>
                            <tbody>
                                ${rowsHtml}
                            </tbody>
                        </table>
                    </div>

                    <div id="receiveDeficitNotice" style="display: none; background: #fffbeb; border: 1px solid #fde68a; color: #92400e; padding: 10px 14px; border-radius: 6px; font-size: 0.82rem; margin-bottom: 10px;">
                        <i class="bi bi-info-circle-fill me-1"></i>
                        <strong>Partial Delivery Notice:</strong> Quantity receiving now is less than the remaining ordered quantity. 
                        PO status will be <span class="badge badge-warning" style="background: #f59e0b; color: #fff;">Received Partial</span> and unreceived items will remain as deficit (to follow).
                    </div>
                `;

                    window.currentStaffPoLines = p.lines;
                    checkReceiveDeficits(p.lines.length);
                });
        }

        function checkReceiveDeficits(linesCount) {
            if (!window.currentStaffPoLines) return;
            let hasDeficitLeft = false;

            window.currentStaffPoLines.forEach((l, i) => {
                const ordered = parseFloat(l.quantity_ordered) || 0;
                const prevReceived = parseFloat(l.quantity_received) || 0;
                const nowReceive = parseFloat(document.getElementById(`receive_qty_${i}`)?.value) || 0;
                const remaining = Math.max(0, ordered - (prevReceived + nowReceive));

                const hint = document.getElementById(`deficit_hint_${i}`);
                if (hint) {
                    if (remaining > 0) {
                        hasDeficitLeft = true;
                        hint.innerHTML =
                            `<span style="color: #dc2626; font-weight: 700;">Deficit: ${remaining.toFixed(2)} to follow</span>`;
                    } else {
                        hint.innerHTML = `<span style="color: #16a34a; font-weight: 700;">Fully Received</span>`;
                    }
                }
            });

            const notice = document.getElementById('receiveDeficitNotice');
            const badge = document.getElementById('receiveStatusPreviewBadge');
            if (notice) notice.style.display = hasDeficitLeft ? 'block' : 'none';
            if (badge) {
                if (hasDeficitLeft) {
                    badge.innerHTML =
                        `<span class="badge badge-warning" style="background: #f59e0b; color: #fff;"><i class="bi bi-hourglass-split me-1"></i> Will Be: Received Partial</span>`;
                } else {
                    badge.innerHTML =
                        `<span class="badge badge-success"><i class="bi bi-check-circle me-1"></i> Will Be: Fully Received</span>`;
                }
            }
        }

        async function submitStaffReceiveGoods(e) {
            e.preventDefault();
            const btn = document.getElementById('staffSubmitReceiveBtn');
            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Processing Delivery...';

            const form = document.getElementById('staffReceiveGoodsForm');
            const formData = new FormData(form);

            try {
                const res = await fetch(form.action, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute(
                            'content'),
                        'Accept': 'application/json'
                    },
                    body: formData
                });
                const data = await res.json();
                if (data.success) {
                    closeModal('staffReceiveGoodsModal');
                    showToast('success', 'Delivery Received', data.message);
                    setTimeout(() => location.reload(), 600);
                } else {
                    showToast('error', 'Receiving Error', data.message || 'Error processing delivery.');
                    btn.disabled = false;
                    btn.innerHTML = '<i class="bi bi-check-lg me-1"></i> Confirm Inbound Receipt';
                }
            } catch (err) {
                showToast('error', 'Error', err.message);
                btn.disabled = false;
                btn.innerHTML = '<i class="bi bi-check-lg me-1"></i> Confirm Inbound Receipt';
            }
        }

        function viewReceivingHistory(id, routePrefix = 'staff') {
            openModal('receivingHistoryModal');
            document.getElementById('receivingHistoryBody').innerHTML =
                '<div style="text-align: center; padding: 40px;"><div class="spinner-border text-primary"></div></div>';

            fetch(`/${routePrefix}/purchases/${id}/receiving-history`, {
                    credentials: 'same-origin',
                    headers: {
                        'Accept': 'application/json'
                    }
                })
                .then(res => res.json())
                .then(res => {
                    const p = res.purchase;
                    const lines = res.lines;
                    const batches = res.batches;

                    document.getElementById('historyModalTitle').innerHTML =
                        `<i class="bi bi-clock-history text-info me-2"></i> Delivery Breakdown & Receiving History: <strong>${p.purchase_number}</strong>`;

                    let batchesHtml = '';
                    if (batches && batches.length > 0) {
                        batchesHtml = batches.map((b, idx) => `
                        <tr>
                            <td style="font-weight: 700;">#${idx + 1} - ${b.batch_code}</td>
                            <td>${b.receipt_date} <div style="font-size: 0.72rem; color: var(--text-muted);">${b.created_at}</div></td>
                            <td><strong>${b.invoice_dr_number || 'N/A'}</strong></td>
                            <td>
                                <strong>${b.product_name}</strong>
                                <div style="font-size: 0.72rem; color: var(--text-muted);">${b.product_sku}</div>
                            </td>
                            <td><span class="badge badge-navy">${b.supplier_name}</span></td>
                            <td style="text-align: center; font-weight: 800; color: #16a34a; font-size: 0.95rem;">
                                +${b.quantity_received.toFixed(2)}
                            </td>
                            <td>${b.warehouse_name}</td>
                            <td style="font-size: 0.8rem;">${b.received_by}</td>
                        </tr>
                    `).join('');
                    } else {
                        batchesHtml =
                            `<tr><td colspan="8" class="text-center py-4 text-muted">No receiving batches logged yet.</td></tr>`;
                    }

                    let linesSummaryHtml = lines.map(l => {
                        const pct = l.quantity_ordered > 0 ? Math.min(100, Math.round((l.quantity_received / l
                            .quantity_ordered) * 100)) : 100;
                        let badgeClass = (l.deficit <= 0) ? 'badge-success' : (l.quantity_received > 0 ?
                            'badge-warning' : 'badge-navy');
                        let badgeText = (l.deficit <= 0) ? 'Fully Received' : (l.quantity_received > 0 ?
                            `Partial (${l.deficit.toFixed(2)} deficit)` : 'Pending');

                        return `
                        <tr>
                            <td><strong>${l.product_name}</strong> (${l.product_sku})</td>
                            <td>${l.unit_name}</td>
                            <td style="text-align: center; font-weight: 700;">${l.quantity_ordered.toFixed(2)}</td>
                            <td style="text-align: center; font-weight: 700; color: #16a34a;">${l.quantity_received.toFixed(2)}</td>
                            <td style="text-align: center; font-weight: 700; color: ${l.deficit > 0 ? '#dc2626' : '#16a34a'};">
                                ${l.deficit.toFixed(2)}
                            </td>
                            <td>
                                <div style="display: flex; align-items: center; gap: 8px;">
                                    <div style="flex-grow: 1; background: #e2e8f0; height: 8px; border-radius: 4px; overflow: hidden;">
                                        <div style="width: ${pct}%; background: ${pct >= 100 ? '#16a34a' : '#f59e0b'}; height: 100%;"></div>
                                    </div>
                                    <span style="font-size: 0.74rem; font-weight: 700;">${pct}%</span>
                                </div>
                            </td>
                            <td style="text-align: center;"><span class="badge ${badgeClass}">${badgeText}</span></td>
                        </tr>
                    `;
                    }).join('');

                    document.getElementById('receivingHistoryBody').innerHTML = `
                    <div class="stats-grid" style="grid-template-columns: repeat(4, 1fr); gap: 12px; margin-bottom: 18px;">
                        <div style="background: #f1f5f9; padding: 12px; border-radius: 8px; text-align: center; border: 1px solid #cbd5e1;">
                            <div style="font-size: 0.75rem; color: var(--text-muted); text-transform: uppercase;">Total Ordered</div>
                            <div style="font-size: 1.25rem; font-weight: 800; color: var(--color-deep-navy);">${p.total_ordered.toFixed(2)}</div>
                        </div>
                        <div style="background: #f0fdf4; padding: 12px; border-radius: 8px; text-align: center; border: 1px solid #bbf7d0;">
                            <div style="font-size: 0.75rem; color: #15803d; text-transform: uppercase;">Total Received to Date</div>
                            <div style="font-size: 1.25rem; font-weight: 800; color: #16a34a;">${p.total_received.toFixed(2)}</div>
                        </div>
                        <div style="background: ${p.total_deficit > 0 ? '#fef2f2' : '#f8fafc'}; padding: 12px; border-radius: 8px; text-align: center; border: 1px solid ${p.total_deficit > 0 ? '#fecaca' : '#e2e8f0'};">
                            <div style="font-size: 0.75rem; color: ${p.total_deficit > 0 ? '#b91c1c' : '#64748b'}; text-transform: uppercase;">Current Deficit (To Follow)</div>
                            <div style="font-size: 1.25rem; font-weight: 800; color: ${p.total_deficit > 0 ? '#dc2626' : '#64748b'};">${p.total_deficit.toFixed(2)}</div>
                        </div>
                        <div style="background: #f8fafc; padding: 12px; border-radius: 8px; text-align: center; border: 1px solid #cbd5e1;">
                            <div style="font-size: 0.75rem; color: var(--text-muted); text-transform: uppercase;">PO Status</div>
                            <div style="margin-top: 4px;">
                                <span class="badge ${p.status === 'received' && p.total_deficit <= 0 ? 'badge-success' : 'badge-warning'}" style="font-size: 0.85rem;">
                                    ${p.status_label}
                                </span>
                            </div>
                        </div>
                    </div>

                    <h4 style="font-size: 0.9rem; font-weight: 700; margin-bottom: 8px;"><i class="bi bi-list-check me-1 text-primary"></i> Line Item Completion Summary</h4>
                    <div class="table-responsive" style="border: 1px solid var(--card-border); border-radius: 8px; margin-bottom: 20px;">
                        <table class="table mb-0" style="font-size: 0.82rem; width: 100%;">
                            <thead style="background: var(--color-mist-blue);">
                                <tr>
                                    <th>Product SKU</th>
                                    <th>Unit</th>
                                    <th style="text-align: center;">Ordered</th>
                                    <th style="text-align: center;">Received</th>
                                    <th style="text-align: center;">Deficit</th>
                                    <th style="width: 140px;">Delivery Progress</th>
                                    <th style="text-align: center;">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                ${linesSummaryHtml}
                            </tbody>
                        </table>
                    </div>

                    <h4 style="font-size: 0.9rem; font-weight: 700; margin-bottom: 8px;"><i class="bi bi-truck me-1 text-success"></i> Recorded Inbound Delivery Batches (${batches.length} deliveries)</h4>
                    <div class="table-responsive" style="border: 1px solid var(--card-border); border-radius: 8px;">
                        <table class="table mb-0" style="font-size: 0.82rem; width: 100%;">
                            <thead style="background: var(--color-mist-blue);">
                                <tr>
                                    <th>Batch Code</th>
                                    <th>Delivery Date</th>
                                    <th>DR / Invoice #</th>
                                    <th>Product</th>
                                    <th>Supplier</th>
                                    <th style="text-align: center;">Quantity Delivered</th>
                                    <th>Warehouse</th>
                                    <th>Received By</th>
                                </tr>
                            </thead>
                            <tbody>
                                ${batchesHtml}
                            </tbody>
                        </table>
                    </div>
                `;
                });
        }

        document.addEventListener('DOMContentLoaded', () => {
            addStaffPurchaseLineRow();
        });
    </script>
@endpush
