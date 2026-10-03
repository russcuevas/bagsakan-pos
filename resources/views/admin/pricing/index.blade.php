@extends('layouts.app')

@section('title', 'Pricing & SRP History')
@section('page_title', 'Selling Price Management & History')

@section('content')
<!-- Active Products SRP Table -->
<div class="card" style="overflow: visible !important;">
    <div class="card-header">
        <div>
            <h2 class="card-title">Authorized Product Selling Prices</h2>
            <p class="card-description">Manage standard retail SRP and wholesale package conversions</p>
        </div>
        <button type="button" class="btn btn-primary" onclick="openModal('quickAdjustmentModal')">
            <i class="bi bi-plus-circle"></i> Quick Price Adjustment
        </button>
    </div>
    <div class="card-body" style="overflow: visible !important;">
        <div class="table-responsive" style="overflow: visible !important; min-height: 180px;">
            <table class="custom-table datatable-init">
                <thead>
                    <tr>
                        <th>Product</th>
                        <th class="hide-mobile">Category</th>
                        <th class="hide-mobile">Base Unit</th>
                        <th>Current SRP</th>
                        <th style="text-align: right;">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($products as $prod)
                        @php
                            $baseUnit = $prod->units->firstWhere('conversion_factor', 1) ?? $prod->units->first();
                            $otherUnits = $prod->units->filter(function($u) use ($baseUnit) {
                                return $baseUnit ? $u->id !== $baseUnit->id : false;
                            });
                        @endphp
                        <tr>
                            <td>
                                <div style="display: flex; align-items: center; gap: 10px;">
                                    <img src="{{ $prod->image_url }}" alt="{{ $prod->name }}" style="width: 36px; height: 36px; object-fit: cover; border-radius: var(--radius-sm); border: 1px solid var(--card-border); flex-shrink: 0;">
                                    <div>
                                        <div style="font-weight: 700; color: var(--color-deep-navy); line-height: 1.2;">{{ $prod->name }}</div>
                                        <div style="font-size: 0.72rem; color: var(--text-light); margin-top: 2px;">
                                            {{ $prod->sku }}
                                            <span class="d-sm-none" style="color: var(--color-primary-blue); font-weight: 600;"> &bull; {{ $baseUnit->unit_name ?? $prod->base_unit }}</span>
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <td class="hide-mobile">
                                <span class="badge badge-navy">{{ $prod->category->name ?? 'Uncategorized' }}</span>
                            </td>
                            <td class="hide-mobile">
                                <span class="badge badge-primary">{{ $baseUnit->unit_name ?? $prod->base_unit }} (Base)</span>
                            </td>
                            <td style="white-space: nowrap;">
                                <div style="font-weight: 800; font-size: 0.95rem; color: var(--color-primary-blue);">
                                    ₱{{ number_format($baseUnit->srp ?? $prod->default_srp, 2) }}
                                </div>
                            </td>
                            <td style="text-align: right; white-space: nowrap;">
                                <div style="display: inline-flex; align-items: center; gap: 6px; justify-content: flex-end;">
                                    <!-- Base Unit Update SRP Button -->
                                    <button type="button" class="btn btn-sm btn-secondary" style="padding: 4px 8px; font-size: 0.74rem;" onclick="openUpdatePriceModal({{ $prod->id }}, {{ $baseUnit->id ?? 'null' }}, '{{ addslashes($prod->name) }}', '{{ addslashes($baseUnit->unit_name ?? $prod->base_unit) }}', {{ $baseUnit->srp ?? $prod->default_srp }})" title="Update SRP">
                                        <i class="bi bi-tag me-1"></i> Update SRP
                                    </button>

                                    <!-- Other Units Modal Trigger / Add Unit Button -->
                                    @if($otherUnits->isNotEmpty())
                                        <button type="button" class="btn btn-sm btn-outline" style="padding: 4px 8px; font-size: 0.74rem;" onclick="openManageUnitsModal({{ $prod->id }})" title="View and manage other packaging units">
                                            <i class="bi bi-layers me-1"></i> Units ({{ $otherUnits->count() }})
                                        </button>
                                    @else
                                        <button type="button" class="btn btn-sm btn-outline-primary" style="padding: 4px 8px; font-size: 0.74rem;" onclick="openAddUnitModal({{ $prod->id }}, '{{ addslashes($prod->name) }}', '{{ addslashes($prod->base_unit) }}')" title="Add wholesale or packaging unit">
                                            <i class="bi bi-plus-lg me-1"></i> Add Unit
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

<!-- Price Change Audit History -->
<div class="card">
    <div class="card-header">
        <div>
            <h2 class="card-title"><i class="bi bi-clock-history me-1 text-primary"></i> Historical SRP Changes & Price Audit Trail</h2>
            <p class="card-description">Immutable log of pricing adjustments, timestamps, and authorized staff</p>
        </div>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="custom-table datatable-init">
                <thead>
                    <tr>
                        <th>Date & Time</th>
                        <th>Product Name</th>
                        <th>Unit</th>
                        <th>Old SRP</th>
                        <th>New SRP</th>
                        <th>Difference</th>
                        <th>Reason</th>
                        <th>Updated By</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($histories as $h)
                        @php
                            $diff = $h->new_srp - $h->old_srp;
                        @endphp
                        <tr>
                            <td style="font-size: 0.8rem; color: var(--text-light);">{{ $h->effective_date->format('M d, Y h:i A') }}</td>
                            <td>
                                <strong>{{ $h->product->name ?? 'Unknown' }}</strong>
                                <div style="font-size: 0.74rem; color: var(--text-light);">{{ $h->product->sku ?? '' }}</div>
                            </td>
                            <td><span class="badge badge-navy">{{ $h->unit_name }}</span></td>
                            <td style="color: var(--text-muted); font-weight: 600;">₱{{ number_format($h->old_srp, 2) }}</td>
                            <td style="font-weight: 800; color: var(--color-primary-blue);">₱{{ number_format($h->new_srp, 2) }}</td>
                            <td>
                                @if($diff > 0)
                                    <span class="badge badge-success">+₱{{ number_format($diff, 2) }}</span>
                                @elseif($diff < 0)
                                    <span class="badge badge-danger">-₱{{ number_format(abs($diff), 2) }}</span>
                                @else
                                    <span class="badge badge-navy">₱0.00</span>
                                @endif
                            </td>
                            <td><em>{{ $h->reason }}</em></td>
                            <td>
                                <span class="badge badge-primary">{{ $h->updater->name ?? 'System' }}</span>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal for updating individual unit price -->
<div id="unitPriceModal" class="modal-overlay">
    <div class="modal-dialog">
        <div class="modal-header">
            <h3 class="modal-title"><i class="bi bi-tag text-primary me-2"></i> Update Selling Price (SRP)</h3>
            <button type="button" class="modal-close-btn" data-close-modal="unitPriceModal"><i class="bi bi-x-lg"></i></button>
        </div>
        <form action="{{ route('admin.pricing.update') }}" method="POST" class="ajax-form">
            @csrf
            <input type="hidden" id="modal_product_id" name="product_id">
            <input type="hidden" id="modal_unit_id" name="unit_id">
            <div class="modal-body">
                <div style="background: var(--color-mist-blue); padding: 14px 16px; border-radius: var(--radius-sm); margin-bottom: 18px; border: 1px solid rgba(7, 89, 152, 0.15);">
                    <div style="font-weight: 700; font-size: 1rem; color: var(--color-deep-navy);" id="modal_product_name"></div>
                    <div style="font-size: 0.82rem; color: var(--color-primary-blue); margin-top: 2px;">Unit: <strong id="modal_unit_name"></strong> | Current SRP: <strong id="modal_current_srp"></strong></div>
                </div>

                <div class="form-group">
                    <label class="form-label" for="modal_new_srp">New SRP (₱) <span class="text-danger">*</span></label>
                    <input type="number" step="0.01" id="modal_new_srp" name="new_srp" class="form-control" placeholder="0.00" required>
                </div>

                <div class="form-group">
                    <label class="form-label" for="modal_reason">Reason for Price Update <span class="text-danger">*</span></label>
                    <input type="text" id="modal_reason" name="reason" class="form-control" placeholder="e.g. Market price adjustment, seasonal supply cost" required>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline" data-close-modal="unitPriceModal">Cancel</button>
                <button type="submit" class="btn btn-primary">Save New Price</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal for Quick Price Adjustment -->
<div id="quickAdjustmentModal" class="modal-overlay">
    <div class="modal-dialog">
        <div class="modal-header">
            <h3 class="modal-title"><i class="bi bi-sliders text-primary me-2"></i> Quick Price Adjustment</h3>
            <button type="button" class="modal-close-btn" data-close-modal="quickAdjustmentModal"><i class="bi bi-x-lg"></i></button>
        </div>
        <form action="{{ route('admin.pricing.update') }}" method="POST" class="ajax-form">
            @csrf
            <div class="modal-body">
                <div class="form-group">
                    <label class="form-label" for="quick_product_id">Select Product <span class="text-danger">*</span></label>
                    <select id="quick_product_id" name="product_id" class="form-select select2-init" required onchange="loadProductUnits(this.value)">
                        <option value="">Choose a product...</option>
                        @foreach($products as $p)
                            <option value="{{ $p->id }}">{{ $p->sku }} - {{ $p->name }} (Base SRP: ₱{{ number_format($p->default_srp, 2) }})</option>
                        @endforeach
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label" for="quick_unit_id">Target Unit</label>
                    <select id="quick_unit_id" name="unit_id" class="form-select">
                        <option value="">Base Unit (Default)</option>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label" for="quick_new_srp">New Selling Price (SRP ₱) <span class="text-danger">*</span></label>
                    <input type="number" step="0.01" id="quick_new_srp" name="new_srp" class="form-control" placeholder="0.00" required>
                </div>

                <div class="form-group">
                    <label class="form-label" for="quick_reason">Price Adjustment Reason <span class="text-danger">*</span></label>
                    <input type="text" id="quick_reason" name="reason" class="form-control" placeholder="e.g. Market fluctuation, supply shortage, promo price" required>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline" data-close-modal="quickAdjustmentModal">Cancel</button>
                <button type="submit" class="btn btn-primary">Authorize & Save SRP</button>
            </div>
        </form>
    </div>
</div>
<!-- Modal for Managing All Product Units & SRP -->
<div id="manageUnitsModal" class="modal-overlay">
    <div class="modal-dialog modal-lg">
        <div class="modal-header">
            <h3 class="modal-title"><i class="bi bi-layers text-primary me-2"></i> <span id="manage_units_modal_title">Product Packaging Units</span></h3>
            <button type="button" class="modal-close-btn" data-close-modal="manageUnitsModal"><i class="bi bi-x-lg"></i></button>
        </div>
        <div class="modal-body">
            <div style="background: var(--color-mist-blue); padding: 14px 16px; border-radius: var(--radius-sm); margin-bottom: 18px; border: 1px solid rgba(7, 89, 152, 0.15); display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px;">
                <div>
                    <div style="font-weight: 700; font-size: 1.05rem; color: var(--color-deep-navy);" id="manage_units_pname"></div>
                    <div style="font-size: 0.8rem; color: var(--text-light);" id="manage_units_psku"></div>
                </div>
                <div>
                    <span class="badge badge-navy" id="manage_units_pcat"></span>
                </div>
            </div>

            <h4 style="font-size: 0.9rem; font-weight: 700; color: var(--color-deep-navy); margin-bottom: 10px;">Active Packaging & Wholesale Units:</h4>
            <div id="manage_units_list" style="display: flex; flex-direction: column; gap: 10px; margin-bottom: 20px;">
                <!-- Populated via JS -->
            </div>
        </div>
        <div class="modal-footer" style="display: flex; justify-content: space-between; align-items: center;">
            <button type="button" class="btn btn-outline" data-close-modal="manageUnitsModal">Close</button>
            <button type="button" class="btn btn-primary" id="manage_units_add_btn">
                <i class="bi bi-plus-lg me-1"></i> Add Another Unit Package
            </button>
        </div>
    </div>
</div>

<!-- Modal for Adding New Packaging/Conversion Unit -->
<div id="addUnitModal" class="modal-overlay">
    <div class="modal-dialog">
        <div class="modal-header">
            <h3 class="modal-title"><i class="bi bi-plus-circle text-primary me-2"></i> Add Packaging / Conversion Unit</h3>
            <button type="button" class="modal-close-btn" data-close-modal="addUnitModal"><i class="bi bi-x-lg"></i></button>
        </div>
        <form id="addUnitForm" method="POST" class="ajax-form">
            @csrf
            <div class="modal-body">
                <div style="background: var(--color-mist-blue); padding: 14px 16px; border-radius: var(--radius-sm); margin-bottom: 18px; border: 1px solid rgba(7, 89, 152, 0.15);">
                    <div style="font-weight: 700; font-size: 1rem; color: var(--color-deep-navy);" id="add_unit_product_name"></div>
                    <div style="font-size: 0.82rem; color: var(--color-primary-blue); margin-top: 2px;">Base Unit: <strong id="add_unit_base_unit"></strong></div>
                </div>

                <div class="form-group">
                    <label class="form-label" for="new_unit_name">Unit Name / Package Title <span class="text-danger">*</span></label>
                    <input type="text" id="new_unit_name" name="unit_name" class="form-control" placeholder="e.g. 1 Dozen (12 pcs), 1 Tray (30 pcs), 1 Sack (25 kg)" required>
                </div>

                <div class="form-group">
                    <label class="form-label" for="new_conversion_factor">Conversion Factor (Qty in Base Unit) <span class="text-danger">*</span></label>
                    <input type="number" step="0.0001" id="new_conversion_factor" name="conversion_factor" class="form-control" placeholder="e.g. 12, 30, 25" required>
                    <div class="form-text" id="add_unit_factor_hint">How many base units equal 1 of this package?</div>
                </div>

                <div class="form-group">
                    <label class="form-label" for="new_unit_srp">Selling Price (SRP ₱) <span class="text-danger">*</span></label>
                    <input type="number" step="0.01" id="new_unit_srp" name="srp" class="form-control" placeholder="0.00" required>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline" data-close-modal="addUnitModal">Cancel</button>
                <button type="submit" class="btn btn-primary">Save & Add Unit</button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
    const productsData = {!! json_encode($products) !!};

    function openManageUnitsModal(productId) {
        const prod = productsData.find(p => p.id == productId);
        if (!prod) return;

        document.getElementById('manage_units_modal_title').innerText = `${prod.name} • Packaging Units`;
        document.getElementById('manage_units_pname').innerText = prod.name;
        document.getElementById('manage_units_psku').innerText = `SKU: ${prod.sku} | Base Unit: ${prod.base_unit}`;
        document.getElementById('manage_units_pcat').innerText = prod.category ? prod.category.name : 'Produce';

        const listContainer = document.getElementById('manage_units_list');
        listContainer.innerHTML = '';

        const baseUnit = prod.units.find(u => u.conversion_factor == 1) || prod.units[0];
        const otherUnits = prod.units.filter(u => u.id !== (baseUnit ? baseUnit.id : null));

        // Render Base Unit
        if (baseUnit) {
            const baseRow = document.createElement('div');
            baseRow.style.cssText = 'display: flex; justify-content: space-between; align-items: center; padding: 12px 16px; background: #FFFFFF; border-radius: var(--radius-sm); border: 2px solid var(--color-mist-blue);';
            baseRow.innerHTML = `
                <div>
                    <div style="font-weight: 700; color: var(--color-deep-navy); font-size: 0.92rem;">${baseUnit.unit_name} <span class="badge badge-primary" style="font-size: 0.68rem; margin-left: 6px;">Base Retail Unit</span></div>
                    <div style="font-size: 0.76rem; color: var(--text-light); margin-top: 2px;">Standard 1 &times; ${prod.base_unit}</div>
                </div>
                <div style="display: flex; align-items: center; gap: 12px;">
                    <div style="font-weight: 800; font-size: 1.05rem; color: var(--color-primary-blue);">₱${parseFloat(baseUnit.srp).toFixed(2)}</div>
                    <button type="button" class="btn btn-sm btn-secondary" onclick="closeModal('manageUnitsModal'); openUpdatePriceModal(${prod.id}, ${baseUnit.id}, '${escapeJsString(prod.name)}', '${escapeJsString(baseUnit.unit_name)}', ${baseUnit.srp})">
                        <i class="bi bi-tag me-1"></i> Update SRP
                    </button>
                </div>
            `;
            listContainer.appendChild(baseRow);
        }

        // Render Other Units
        if (otherUnits.length === 0) {
            const emptyDiv = document.createElement('div');
            emptyDiv.style.cssText = 'text-align: center; color: var(--text-light); padding: 16px; font-size: 0.84rem; background: var(--body-bg); border-radius: var(--radius-sm);';
            emptyDiv.innerText = 'No secondary wholesale/packaging units configured yet.';
            listContainer.appendChild(emptyDiv);
        } else {
            otherUnits.forEach(ou => {
                const ouRow = document.createElement('div');
                ouRow.style.cssText = 'display: flex; justify-content: space-between; align-items: center; padding: 12px 16px; background: var(--body-bg); border-radius: var(--radius-sm); border: 1px solid var(--card-border);';
                ouRow.innerHTML = `
                    <div>
                        <div style="font-weight: 700; color: var(--color-deep-navy); font-size: 0.9rem;">${ou.unit_name}</div>
                        <div style="font-size: 0.76rem; color: var(--text-light); margin-top: 2px;">Conversion: <strong>${parseFloat(ou.conversion_factor)} &times; ${prod.base_unit}</strong></div>
                    </div>
                    <div style="display: flex; align-items: center; gap: 10px;">
                        <div style="font-weight: 800; font-size: 1rem; color: var(--color-primary-blue);">₱${parseFloat(ou.srp).toFixed(2)}</div>
                        <div style="display: flex; gap: 6px;">
                            <button type="button" class="btn btn-sm btn-secondary" onclick="closeModal('manageUnitsModal'); openUpdatePriceModal(${prod.id}, ${ou.id}, '${escapeJsString(prod.name)}', '${escapeJsString(ou.unit_name)}', ${ou.srp})">
                                <i class="bi bi-tag me-1"></i> Update SRP
                            </button>
                            <button type="button" class="btn btn-sm btn-danger" onclick="deletePricingUnit(${ou.id}, '${escapeJsString(ou.unit_name)}')" title="Delete Unit">
                                <i class="bi bi-trash"></i>
                            </button>
                        </div>
                    </div>
                `;
                listContainer.appendChild(ouRow);
            });
        }

        document.getElementById('manage_units_add_btn').onclick = function() {
            closeModal('manageUnitsModal');
            openAddUnitModal(prod.id, prod.name, prod.base_unit);
        };

        openModal('manageUnitsModal');
    }

    function escapeJsString(str) {
        return (str || '').replace(/'/g, "\\'").replace(/"/g, '&quot;');
    }

    function loadProductUnits(productId) {
        const unitSelect = document.getElementById('quick_unit_id');
        unitSelect.innerHTML = '<option value="">Base Unit (Default)</option>';
        if (!productId) return;

        const p = productsData.find(x => x.id == productId);
        if (p && p.units) {
            p.units.forEach(u => {
                const opt = document.createElement('option');
                opt.value = u.id;
                opt.innerText = `${u.unit_name} (Current: ₱${parseFloat(u.srp).toFixed(2)})`;
                unitSelect.appendChild(opt);
            });
        }
    }

    function openUpdatePriceModal(productId, unitId, productName, unitName, currentSrp) {
        document.getElementById('modal_product_id').value = productId;
        document.getElementById('modal_unit_id').value = unitId || '';
        document.getElementById('modal_product_name').innerText = productName;
        document.getElementById('modal_unit_name').innerText = unitName;
        document.getElementById('modal_current_srp').innerText = '₱' + parseFloat(currentSrp).toFixed(2);
        document.getElementById('modal_new_srp').value = currentSrp;
        document.getElementById('modal_reason').value = '';
        openModal('unitPriceModal');
    }

    function openAddUnitModal(productId, productName, baseUnit) {
        document.getElementById('addUnitForm').action = `/admin/products/${productId}/units`;
        document.getElementById('add_unit_product_name').innerText = productName;
        document.getElementById('add_unit_base_unit').innerText = baseUnit;
        document.getElementById('add_unit_factor_hint').innerText = `How many ${baseUnit} equal 1 of this package?`;
        document.getElementById('new_unit_name').value = '';
        document.getElementById('new_conversion_factor').value = '';
        document.getElementById('new_unit_srp').value = '';
        openModal('addUnitModal');
    }

    function deletePricingUnit(unitId, unitName) {
        if (!confirm(`Are you sure you want to delete unit '${unitName}'?`)) return;

        fetch(`/admin/products/units/${unitId}`, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Accept': 'application/json'
            }
        })
        .then(res => res.json())
        .then(res => {
            if (res.success) {
                showToast('success', 'Unit Deleted', res.message);
                setTimeout(() => location.reload(), 800);
            } else {
                showToast('error', 'Delete Failed', res.message);
            }
        })
        .catch(err => {
            showToast('error', 'Error', 'Failed to delete unit.');
        });
    }
</script>
@endpush

