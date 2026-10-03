@extends('layouts.app')

@section('title', 'Product Masterfile')
@section('page_title', 'Product Masterfile & Units')

@section('content')
<div class="card">
    <div class="card-header">
        <div>
            <h2 class="card-title">Products Directory</h2>
            <p class="card-description">Manage SKUs, wholesale/retail units, suppliers, and SRP</p>
        </div>
        <button type="button" class="btn btn-primary" onclick="openModal('addProductModal')">
            <i class="bi bi-plus-lg"></i> Add New Product
        </button>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="custom-table datatable-init">
                <thead>
                    <tr>
                        <th>Image</th>
                        <th>SKU / Barcode</th>
                        <th>Product Name</th>
                        <th>Category</th>
                        <th>Available Stock</th>
                        <th>Default SRP</th>
                        <th>Avg Buying Cost</th>
                        <th>Status</th>
                        <th style="text-align: right;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($products as $product)
                        @php
                            $stock = $product->available_stock;
                        @endphp
                        <tr>
                            <td>
                                <img src="{{ $product->image_url }}" alt="{{ $product->name }}" style="width: 44px; height: 44px; object-fit: cover; border-radius: var(--radius-sm); border: 1px solid var(--card-border);">
                            </td>
                            <td>
                                <div style="font-weight: 700; color: var(--color-deep-navy);">{{ $product->sku }}</div>
                                <div style="font-size: 0.74rem; color: var(--text-light);">{{ $product->barcode ?? 'No Barcode' }}</div>
                            </td>
                            <td>
                                <div style="font-weight: 700;">{{ $product->name }}</div>
                                <div style="font-size: 0.74rem; color: var(--text-muted);">
                                    Units: {{ $product->units->pluck('unit_name')->implode(', ') }}
                                </div>
                            </td>
                            <td><span class="badge badge-navy">{{ $product->category->name ?? 'Uncategorized' }}</span></td>
                            <td>
                                @if($stock <= 0)
                                    <span class="badge badge-danger">Out of Stock (0 {{ $product->base_unit }})</span>
                                @elseif($stock <= $product->low_stock_threshold)
                                    <span class="badge badge-warning">Low: {{ number_format($stock, 2) }} {{ $product->base_unit }}</span>
                                @else
                                    <span class="badge badge-success">{{ number_format($stock, 2) }} {{ $product->base_unit }}</span>
                                @endif
                            </td>
                            <td style="font-weight: 700; color: var(--color-primary-blue);">₱{{ number_format($product->default_srp, 2) }} / {{ $product->base_unit }}</td>
                            <td style="font-weight: 600; color: var(--text-muted);">₱{{ number_format($product->average_cost, 2) }} / {{ $product->base_unit }}</td>
                            <td>
                                <span class="badge {{ $product->is_active ? 'badge-success' : 'badge-danger' }}">
                                    {{ $product->is_active ? 'Active' : 'Inactive' }}
                                </span>
                            </td>
                            <td style="text-align: right;">
                                <div style="display: inline-flex; gap: 6px;">
                                    <button type="button" class="btn btn-sm btn-secondary" onclick="viewProduct({{ $product->id }})" title="View Details">
                                        <i class="bi bi-eye"></i>
                                    </button>
                                    <button type="button" class="btn btn-sm btn-outline" onclick="editProduct({{ $product->id }})" title="Edit">
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                    <button type="button" class="btn btn-sm btn-danger" onclick="confirmDeleteProduct({{ $product->id }}, '{{ addslashes($product->name) }}')" title="Delete Product">
                                        <i class="bi bi-trash"></i>
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

<!-- Add Product Modal -->
<div id="addProductModal" class="modal-overlay">
    <div class="modal-dialog modal-lg">
        <div class="modal-header">
            <h3 class="modal-title"><i class="bi bi-box-seam me-2 text-primary"></i> Register New Product SKU</h3>
            <button type="button" class="modal-close-btn" data-close-modal="addProductModal"><i class="bi bi-x-lg"></i></button>
        </div>
        <form action="{{ route('admin.products.store') }}" method="POST" enctype="multipart/form-data" class="ajax-form">
            @csrf
            <div class="modal-body">
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                    <div class="form-group">
                        <label class="form-label" for="add_sku">Product SKU <span class="text-danger">*</span></label>
                        <input type="text" id="add_sku" name="sku" class="form-control" placeholder="e.g. VEG-TOM-001" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="add_barcode">Barcode / Scan Code</label>
                        <input type="text" id="add_barcode" name="barcode" class="form-control" placeholder="e.g. 480001001001">
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label" for="add_name">Product Name <span class="text-danger">*</span></label>
                    <input type="text" id="add_name" name="name" class="form-control" placeholder="e.g. Fresh Red Tomatoes" required>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 16px;">
                    <div class="form-group">
                        <label class="form-label" for="add_category_id">Category <span class="text-danger">*</span></label>
                        <select id="add_category_id" name="category_id" class="form-select select2-init" required>
                            <option value="">Select Category</option>
                            @foreach($categories as $cat)
                                <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="add_base_unit">Base Inventory Unit <span class="text-danger">*</span></label>
                        <input type="text" id="add_base_unit" name="base_unit" class="form-control" placeholder="e.g. kg, pc, pack" value="kg" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="add_default_srp">Default Selling Price (SRP) <span class="text-danger">*</span></label>
                        <input type="number" step="0.01" id="add_default_srp" name="default_srp" class="form-control" placeholder="0.00" required>
                    </div>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                    <div class="form-group">
                        <label class="form-label" for="add_threshold">Low Stock Warning Threshold</label>
                        <input type="number" step="0.01" id="add_threshold" name="low_stock_threshold" class="form-control" value="10" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="add_suppliers">Suppliers for this SKU</label>
                        <select id="add_suppliers" name="supplier_ids[]" class="form-select select2-init" multiple data-placeholder="Select suppliers">
                            @foreach($suppliers as $sup)
                                <option value="{{ $sup->id }}">{{ $sup->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <!-- Additional Wholesale/Retail Conversion Units -->
                <div style="margin: 16px 0; padding: 16px; background: var(--body-bg); border-radius: var(--radius-sm); border: 1px dashed var(--card-border);">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
                        <span style="font-weight: 700; font-size: 0.88rem; color: var(--color-deep-navy);">
                            <i class="bi bi-arrows-expand me-1 text-primary"></i> Additional Wholesale & Retail Units
                        </span>
                        <button type="button" class="btn btn-sm btn-outline" onclick="addUnitRow()">+ Add Unit Option</button>
                    </div>
                    <div style="font-size: 0.76rem; color: var(--text-muted); margin-bottom: 12px;">
                        Example: If Base Unit is "pc", you can add "1 Tray" with conversion factor "30" and SRP "190".
                    </div>
                    <div id="unitRowsContainer" style="display: flex; flex-direction: column; gap: 8px;">
                        <!-- Dynamic unit rows inserted here -->
                    </div>
                </div>

                <!-- Image Upload with 2MB preview check -->
                <div class="form-group">
                    <label class="form-label" for="add_image">Product Image (Max 2MB)</label>
                    <input type="file" id="add_image" name="image" class="form-control" accept="image/*" data-preview="addImagePreview">
                    <div style="margin-top: 10px;">
                        <img id="addImagePreview" src="" alt="Preview" style="max-height: 100px; border-radius: var(--radius-sm); display: none; border: 1px solid var(--card-border);">
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label" for="add_desc">Description / Notes</label>
                    <textarea id="add_desc" name="description" class="form-control" rows="2" placeholder="Optional notes..."></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline" data-close-modal="addProductModal">Cancel</button>
                <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg"></i> Save Product</button>
            </div>
        </form>
    </div>
</div>

<!-- View Product Details Modal -->
<div id="viewProductModal" class="modal-overlay">
    <div class="modal-dialog modal-lg">
        <div class="modal-header">
            <h3 class="modal-title" id="viewModalTitle"><i class="bi bi-box-seam me-2 text-primary"></i> Product Details</h3>
            <button type="button" class="modal-close-btn" data-close-modal="viewProductModal"><i class="bi bi-x-lg"></i></button>
        </div>
        <div class="modal-body" id="viewProductBody">
            <div style="text-align: center; padding: 40px;"><div class="spinner-border text-primary" role="status"></div></div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-primary" data-close-modal="viewProductModal">Close</button>
        </div>
    </div>
</div>

<!-- Edit Product Modal -->
<div id="editProductModal" class="modal-overlay">
    <div class="modal-dialog modal-lg">
        <div class="modal-header">
            <h3 class="modal-title"><i class="bi bi-pencil-square me-2 text-primary"></i> Edit Product</h3>
            <button type="button" class="modal-close-btn" data-close-modal="editProductModal"><i class="bi bi-x-lg"></i></button>
        </div>
        <form id="editProductForm" method="POST" enctype="multipart/form-data" class="ajax-form">
            @csrf
            @method('PUT')
            <div class="modal-body" id="editProductBody">
                <div style="text-align: center; padding: 40px;"><div class="spinner-border text-primary" role="status"></div></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline" data-close-modal="editProductModal">Cancel</button>
                <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg"></i> Update Changes</button>
            </div>
        </form>
    </div>
</div>
<!-- Delete Product Confirmation Modal -->
<div id="deleteProductModal" class="modal-overlay">
    <div class="modal-dialog" style="max-width: 460px;">
        <div class="modal-header">
            <h3 class="modal-title text-danger"><i class="bi bi-exclamation-triangle me-2"></i> Delete Product</h3>
            <button type="button" class="modal-close-btn" data-close-modal="deleteProductModal"><i class="bi bi-x-lg"></i></button>
        </div>
        <form id="deleteProductForm" method="POST" class="ajax-form">
            @csrf
            @method('DELETE')
            <div class="modal-body">
                <p>Are you sure you want to delete this product SKU?</p>
                <div style="font-weight: 800; color: var(--color-deep-navy); font-size: 1.05rem; padding: 10px 14px; background: var(--body-bg); border-radius: var(--radius-sm); border: 1px solid var(--card-border);" id="deleteProductNameDisplay"></div>
                <div style="font-size: 0.8rem; color: var(--text-muted); margin-top: 10px;">
                    <i class="bi bi-info-circle text-primary me-1"></i> Note: If this product has previous inbound inventory batches or sales logs, it will be safely deactivated to protect financial history.
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline" data-close-modal="deleteProductModal">Cancel</button>
                <button type="submit" class="btn btn-danger"><i class="bi bi-trash me-1"></i> Delete Product</button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
    let unitCount = 0;
    let editUnitCount = 100;

    function addUnitRow(name = '', factor = '', srp = '') {
        unitCount++;
        const row = document.createElement('div');
        row.id = `unitRow_${unitCount}`;
        row.style.display = 'grid';
        row.style.gridTemplateColumns = '2fr 2fr 2fr auto';
        row.style.gap = '8px';
        row.style.alignItems = 'center';

        row.innerHTML = `
            <input type="text" name="units[${unitCount}][unit_name]" class="form-control form-control-sm" placeholder="Unit Name (e.g. Tray)" value="${name}" required>
            <input type="number" step="0.0001" name="units[${unitCount}][conversion_factor]" class="form-control form-control-sm" placeholder="Base Qty (e.g. 30)" value="${factor}" required>
            <input type="number" step="0.01" name="units[${unitCount}][srp]" class="form-control form-control-sm" placeholder="Unit SRP (₱)" value="${srp}" required>
            <button type="button" class="btn btn-sm btn-danger" onclick="document.getElementById('unitRow_${unitCount}').remove()"><i class="bi bi-trash"></i></button>
        `;
        document.getElementById('unitRowsContainer').appendChild(row);
    }

    function addEditUnitRow(name = '', factor = '', srp = '') {
        editUnitCount++;
        const row = document.createElement('div');
        row.id = `editUnitRowNew_${editUnitCount}`;
        row.style.display = 'grid';
        row.style.gridTemplateColumns = '2fr 2fr 2fr auto';
        row.style.gap = '8px';
        row.style.alignItems = 'center';
        row.style.marginBottom = '8px';

        row.innerHTML = `
            <input type="text" name="units[${editUnitCount}][unit_name]" class="form-control form-control-sm" placeholder="Unit Name (e.g. Box)" value="${name}" required>
            <input type="number" step="0.0001" name="units[${editUnitCount}][conversion_factor]" class="form-control form-control-sm" placeholder="Base Qty (e.g. 20)" value="${factor}" required>
            <input type="number" step="0.01" name="units[${editUnitCount}][srp]" class="form-control form-control-sm" placeholder="Unit SRP (₱)" value="${srp}" required>
            <button type="button" class="btn btn-sm btn-danger" onclick="document.getElementById('editUnitRowNew_${editUnitCount}').remove()"><i class="bi bi-trash"></i></button>
        `;
        document.getElementById('editUnitRowsContainer').appendChild(row);
    }

    function viewProduct(id) {
        openModal('viewProductModal');
        document.getElementById('viewProductBody').innerHTML = '<div style="text-align: center; padding: 40px;"><div class="spinner-border text-primary"></div></div>';

        fetch(`/admin/products/${id}`, { credentials: 'same-origin', headers: { 'Accept': 'application/json' } })
            .then(res => res.json())
            .then(res => {
                const p = res.data;
                document.getElementById('viewModalTitle').innerText = `${p.name} (${p.sku})`;

                let batchesHtml = '';
                if (p.batches && p.batches.length > 0) {
                    batchesHtml = `
                        <table class="custom-table" style="font-size: 0.8rem; margin-top: 8px;">
                            <thead>
                                <tr>
                                    <th>Batch Code</th>
                                    <th>Supplier</th>
                                    <th>DR #</th>
                                    <th>Remaining Stock</th>
                                    <th>Buying Cost</th>
                                    <th>Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                ${p.batches.map(b => `
                                    <tr>
                                        <td><strong>${b.batch_code}</strong></td>
                                        <td>${b.supplier ? b.supplier.name : 'N/A'}</td>
                                        <td>${b.invoice_dr_number || 'N/A'}</td>
                                        <td><span class="badge ${b.current_quantity > 0 ? 'badge-success' : 'badge-danger'}">${parseFloat(b.current_quantity).toFixed(2)} ${p.base_unit}</span></td>
                                        <td style="font-weight: 700; color: #065F46;">₱${parseFloat(b.unit_cost).toFixed(2)} / ${p.base_unit}</td>
                                        <td>${b.receipt_date}</td>
                                    </tr>
                                `).join('')}
                            </tbody>
                        </table>
                    `;
                } else {
                    batchesHtml = '<div style="color: var(--text-light); font-size: 0.8rem; padding: 10px 0;">No active inbound batches found.</div>';
                }

                document.getElementById('viewProductBody').innerHTML = `
                    <div style="display: flex; gap: 20px; align-items: flex-start; margin-bottom: 20px;">
                        <img src="${p.image_url}" style="width: 100px; height: 100px; object-fit: cover; border-radius: var(--radius-md); border: 1px solid var(--card-border);">
                        <div style="flex: 1;">
                            <div style="font-size: 1.2rem; font-weight: 800; color: var(--color-deep-navy);">${p.name}</div>
                            <div style="font-size: 0.82rem; color: var(--text-light); margin-bottom: 8px;">SKU: ${p.sku} | Barcode: ${p.barcode || 'N/A'}</div>
                            <div style="display: flex; gap: 12px; flex-wrap: wrap;">
                                <div class="stat-card" style="padding: 8px 14px; margin-bottom: 0;">
                                    <div class="stat-label" style="font-size: 0.7rem;">Available Stock</div>
                                    <div style="font-weight: 800; font-size: 1.1rem; color: #065F46;">${p.available_stock} ${p.base_unit}</div>
                                </div>
                                <div class="stat-card" style="padding: 8px 14px; margin-bottom: 0;">
                                    <div class="stat-label" style="font-size: 0.7rem;">Default SRP</div>
                                    <div style="font-weight: 800; font-size: 1.1rem; color: var(--color-primary-blue);">₱${parseFloat(p.default_srp).toFixed(2)}</div>
                                </div>
                                <div class="stat-card" style="padding: 8px 14px; margin-bottom: 0;">
                                    <div class="stat-label" style="font-size: 0.7rem;">Weighted Avg Cost</div>
                                    <div style="font-weight: 800; font-size: 1.1rem; color: var(--text-main);">₱${parseFloat(p.average_cost).toFixed(2)}</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <h4 style="font-size: 0.95rem; font-weight: 700; color: var(--color-deep-navy); margin-top: 16px;">
                        <i class="bi bi-boxes text-primary me-1"></i> Inbound Receiving Batches & Multi-Supplier Costs
                    </h4>
                    ${batchesHtml}
                `;
            });
    }

    function editProduct(id) {
        openModal('editProductModal');
        document.getElementById('editProductBody').innerHTML = '<div style="text-align: center; padding: 40px;"><div class="spinner-border text-primary"></div></div>';
        document.getElementById('editProductForm').action = `/admin/products/${id}`;

        fetch(`/admin/products/${id}`, { credentials: 'same-origin', headers: { 'Accept': 'application/json' } })
            .then(res => res.json())
            .then(res => {
                const p = res.data;

                // Build units table
                let unitsHtml = '';
                if (p.units && p.units.length > 0) {
                    unitsHtml = p.units.map(u => {
                        const isBase = u.conversion_factor == 1 && u.unit_name.toLowerCase() === p.base_unit.toLowerCase();
                        return `
                            <div id="unitRowItem_${u.id}" style="display: flex; justify-content: space-between; align-items: center; background: var(--body-bg); padding: 8px 12px; border-radius: var(--radius-sm); margin-bottom: 6px; border: 1px solid var(--card-border);">
                                <div>
                                    <span style="font-weight: 700; color: var(--color-deep-navy);">${u.unit_name}</span>
                                    <span style="font-size: 0.78rem; color: var(--text-muted); margin-left: 6px;">(1 ${u.unit_name} = ${parseFloat(u.conversion_factor)} ${p.base_unit} &bull; SRP: ₱${parseFloat(u.srp).toFixed(2)})</span>
                                    ${isBase ? '<span class="badge badge-primary ms-2" style="font-size: 0.65rem;">Base Unit</span>' : ''}
                                </div>
                                <div>
                                    ${!isBase ? `<button type="button" class="btn btn-sm btn-danger" style="padding: 2px 8px; font-size: 0.75rem;" onclick="deleteUnit(${u.id})" title="Delete Unit"><i class="bi bi-trash"></i></button>` : ''}
                                </div>
                            </div>
                        `;
                    }).join('');
                }

                document.getElementById('editProductBody').innerHTML = `
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                        <div class="form-group">
                            <label class="form-label">SKU <span class="text-danger">*</span></label>
                            <input type="text" name="sku" class="form-control" value="${p.sku}" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Barcode</label>
                            <input type="text" name="barcode" class="form-control" value="${p.barcode || ''}">
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Product Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control" value="${p.name}" required>
                    </div>
                    <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 16px;">
                        <div class="form-group">
                            <label class="form-label">Category <span class="text-danger">*</span></label>
                            <select name="category_id" class="form-select" required>
                                @foreach($categories as $cat)
                                    <option value="{{ $cat->id }}" ${p.category_id == {{ $cat->id }} ? 'selected' : ''}>{{ $cat->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Base Unit <span class="text-danger">*</span></label>
                            <input type="text" name="base_unit" class="form-control" value="${p.base_unit}" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Default SRP (₱) <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" name="default_srp" class="form-control" value="${p.default_srp}" required>
                        </div>
                    </div>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                        <div class="form-group">
                            <label class="form-label">Low Stock Threshold <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" name="low_stock_threshold" class="form-control" value="${p.low_stock_threshold}" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Status</label>
                            <select name="is_active" class="form-select">
                                <option value="1" ${p.is_active ? 'selected' : ''}>Active</option>
                                <option value="0" ${!p.is_active ? 'selected' : ''}>Inactive</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Update Product Photo</label>
                        <input type="file" name="image" class="form-control" accept="image/*">
                        <div class="form-text">Leave blank to keep existing photo</div>
                    </div>

                    <!-- Multi-Unit Management in Edit Modal -->
                    <div style="margin-top: 10px; border-top: 1px solid var(--card-border); padding-top: 14px;">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                            <div>
                                <span style="font-size: 0.88rem; font-weight: 700; color: var(--color-deep-navy);">Active Selling Units</span>
                                <div class="form-text">Manage or delete secondary wholesale units</div>
                            </div>
                            <button type="button" class="btn btn-sm btn-secondary" onclick="addEditUnitRow()">
                                <i class="bi bi-plus-circle"></i> Add Unit
                            </button>
                        </div>
                        <div id="existingUnitsContainer">${unitsHtml}</div>
                        <div id="editUnitRowsContainer"></div>
                    </div>

                    <div class="form-group" style="margin-top: 14px;">
                        <label class="form-label">Description / Notes</label>
                        <textarea name="description" class="form-control" rows="2">${p.description || ''}</textarea>
                    </div>
                `;
            });
    }

    function confirmDeleteProduct(id, name) {
        document.getElementById('deleteProductNameDisplay').innerText = name;
        document.getElementById('deleteProductForm').action = `/admin/products/${id}`;
        openModal('deleteProductModal');
    }

    function deleteUnit(unitId) {
        if (!confirm('Are you sure you want to delete this conversion unit?')) return;

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
                const item = document.getElementById(`unitRowItem_${unitId}`);
                if (item) item.remove();
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
