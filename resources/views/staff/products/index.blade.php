@extends('layouts.app')

@section('title', 'Product Masterfile')
@section('page_title', 'Product Masterfile & Units')

@section('content')
<div class="card">
    <div class="card-header">
        <div>
            <h2 class="card-title">Products Directory</h2>
            <p class="card-description">Manage SKUs, wholesale/retail units, suppliers, and SRP (Purchasing Staff)</p>
        </div>
        <div style="display: flex; gap: 8px;">
            <button type="button" class="btn btn-primary" onclick="openModal('addProductModal')">
                <i class="bi bi-plus-lg me-1"></i> Add New Product
            </button>
        </div>
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
                                @if($stock < 0)
                                    <span class="badge badge-danger" style="background: #dc2626; color: #fff; font-weight: 700;">
                                        {{ number_format($stock, 2) }} {{ $product->base_unit }} (Reorder Needed)
                                    </span>
                                @elseif($stock == 0)
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
                                    <button type="button" class="btn btn-sm btn-outline" onclick="editProduct({{ $product->id }})" title="Edit Product">
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
        <form action="{{ route('staff.products.store') }}" method="POST" enctype="multipart/form-data" class="ajax-form">
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

                <!-- Initial / Opening Stock (Optional) -->
                <div style="margin: 16px 0; padding: 14px 16px; background: #f0fdf4; border-radius: var(--radius-sm); border: 1px solid #bbf7d0;">
                    <div style="font-weight: 700; font-size: 0.88rem; color: #166534; margin-bottom: 4px;">
                        <i class="bi bi-box-arrow-in-down me-1"></i> Initial / Opening Stock (Panimulang Imbentaryo) <small class="text-muted" style="font-weight: 400;">— Optional</small>
                    </div>
                    <div style="font-size: 0.76rem; color: #15803d; margin-bottom: 12px;">
                        Kung mayroon ka nang physical stock sa bodega, ilagay ang panimulang bilang at puhunan dito para magka-stock agad pagka-save ng produkto.
                    </div>
                    <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 14px;">
                        <div class="form-group mb-0">
                            <label class="form-label" for="add_opening_stock" style="font-size: 0.8rem; color: #166534;">Opening Quantity</label>
                            <input type="number" step="0.01" min="0" id="add_opening_stock" name="opening_stock" class="form-control" placeholder="0.00">
                        </div>
                        <div class="form-group mb-0">
                            <label class="form-label" for="add_opening_cost" style="font-size: 0.8rem; color: #166534;">Cost per Unit / Puhunan (₱)</label>
                            <input type="number" step="0.01" min="0" id="add_opening_cost" name="opening_cost" class="form-control" placeholder="0.00">
                        </div>
                        <div class="form-group mb-0">
                            <label class="form-label" for="add_warehouse_id" style="font-size: 0.8rem; color: #166534;">Target Warehouse</label>
                            <select id="add_warehouse_id" name="warehouse_id" class="form-select">
                                @if(isset($warehouses))
                                    @foreach($warehouses as $w)
                                        <option value="{{ $w->id }}">{{ $w->name }}</option>
                                    @endforeach
                                @endif
                            </select>
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label" for="add_image">Product Photo</label>
                    <input type="file" id="add_image" name="image" class="form-control" accept="image/*">
                </div>

                <!-- Wholesale / Multi-Unit Packaging Conversions -->
                <div style="border-top: 1px solid var(--card-border); padding-top: 16px; margin-top: 16px;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                        <div>
                            <span style="font-weight: 700; color: var(--color-deep-navy);">Wholesale & Retail Packaging Units</span>
                            <div class="form-text">e.g. 1 Crate = 20 kgs, 1 Sack = 50 kgs</div>
                        </div>
                        <button type="button" class="btn btn-sm btn-outline" onclick="addUnitRow()">
                            <i class="bi bi-plus-circle me-1"></i> Add Unit
                        </button>
                    </div>
                    <div id="unitRowsContainer"></div>
                </div>

                <div class="form-group" style="margin-top: 16px;">
                    <label class="form-label" for="add_desc">Product Description & Storage Instructions</label>
                    <textarea id="add_desc" name="description" class="form-control" rows="2" placeholder="Optional product description or storage specifications..."></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline" data-close-modal="addProductModal">Cancel</button>
                <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg me-1"></i> Save Product</button>
            </div>
        </form>
    </div>
</div>

<!-- Edit Product Modal -->
<div id="editProductModal" class="modal-overlay">
    <div class="modal-dialog modal-lg">
        <div class="modal-header">
            <h3 class="modal-title"><i class="bi bi-pencil-square text-primary me-2"></i> Edit Product Masterfile</h3>
            <button type="button" class="modal-close-btn" data-close-modal="editProductModal"><i class="bi bi-x-lg"></i></button>
        </div>
        <form id="editProductForm" method="POST" enctype="multipart/form-data" class="ajax-form">
            @csrf
            @method('PUT')
            <div class="modal-body" id="editProductBody">
                <div style="text-align: center; padding: 40px;"><div class="spinner-border text-primary"></div></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline" data-close-modal="editProductModal">Cancel</button>
                <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg me-1"></i> Update Product</button>
            </div>
        </form>
    </div>
</div>

<!-- View Product Details Modal -->
<div id="viewProductModal" class="modal-overlay">
    <div class="modal-dialog modal-xl">
        <div class="modal-header">
            <h3 class="modal-title"><i class="bi bi-info-circle text-primary me-2"></i> Product Comprehensive Ledger</h3>
            <button type="button" class="modal-close-btn" data-close-modal="viewProductModal"><i class="bi bi-x-lg"></i></button>
        </div>
        <div class="modal-body" id="viewProductBody">
            <div style="text-align: center; padding: 40px;"><div class="spinner-border text-primary"></div></div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-outline" data-close-modal="viewProductModal">Close</button>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div id="deleteProductModal" class="modal-overlay">
    <div class="modal-dialog">
        <div class="modal-header">
            <h3 class="modal-title text-danger"><i class="bi bi-exclamation-triangle-fill me-2"></i> Confirm Deletion</h3>
            <button type="button" class="modal-close-btn" data-close-modal="deleteProductModal"><i class="bi bi-x-lg"></i></button>
        </div>
        <form id="deleteProductForm" method="POST" class="ajax-form">
            @csrf
            @method('DELETE')
            <div class="modal-body">
                <p>Are you sure you want to delete <strong id="deleteProductNameDisplay"></strong>?</p>
                <div class="alert alert-warning" style="font-size: 0.85rem;">
                    <i class="bi bi-info-circle me-1"></i> If this product has historical batches or movements, it will be safely <strong>deactivated</strong> instead of deleted to protect ledger integrity.
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
    const categoriesData = @json($categories);
    const suppliersData = @json($suppliers);
    let unitCount = 0;
    let editUnitCount = 0;

    function addUnitRow() {
        const idx = unitCount++;
        const container = document.getElementById('unitRowsContainer');
        const row = document.createElement('div');
        row.id = `unitRow_${idx}`;
        row.style = 'display: grid; grid-template-columns: 2fr 2fr 2fr auto; gap: 12px; align-items: flex-end; margin-bottom: 10px; background: var(--color-mist-blue); padding: 10px; border-radius: var(--radius-sm);';
        row.innerHTML = `
            <div>
                <label class="form-label" style="font-size: 0.78rem;">Unit Name</label>
                <input type="text" name="units[${idx}][unit_name]" class="form-control form-control-sm" placeholder="e.g. Crate, Sack, Pack" required>
            </div>
            <div>
                <label class="form-label" style="font-size: 0.78rem;">Base Unit Multiplier</label>
                <input type="number" step="0.0001" name="units[${idx}][conversion_factor]" class="form-control form-control-sm" placeholder="e.g. 20 (kg)" required>
            </div>
            <div>
                <label class="form-label" style="font-size: 0.78rem;">Wholesale SRP (₱)</label>
                <input type="number" step="0.01" name="units[${idx}][srp]" class="form-control form-control-sm" placeholder="0.00" required>
            </div>
            <button type="button" class="btn btn-sm btn-outline-danger" onclick="document.getElementById('unitRow_${idx}').remove()">
                <i class="bi bi-x-lg"></i>
            </button>
        `;
        container.appendChild(row);
    }

    function addEditUnitRow() {
        const idx = 'new_' + (editUnitCount++);
        const container = document.getElementById('editUnitRowsContainer');
        const row = document.createElement('div');
        row.id = `editUnitRow_${idx}`;
        row.style = 'display: grid; grid-template-columns: 2fr 2fr 2fr auto; gap: 12px; align-items: flex-end; margin-bottom: 10px; background: var(--color-mist-blue); padding: 10px; border-radius: var(--radius-sm);';
        row.innerHTML = `
            <div>
                <label class="form-label" style="font-size: 0.78rem;">Unit Name</label>
                <input type="text" name="units[${idx}][unit_name]" class="form-control form-control-sm" placeholder="e.g. Crate, Sack" required>
            </div>
            <div>
                <label class="form-label" style="font-size: 0.78rem;">Base Unit Multiplier</label>
                <input type="number" step="0.0001" name="units[${idx}][conversion_factor]" class="form-control form-control-sm" placeholder="e.g. 25" required>
            </div>
            <div>
                <label class="form-label" style="font-size: 0.78rem;">Wholesale SRP (₱)</label>
                <input type="number" step="0.01" name="units[${idx}][srp]" class="form-control form-control-sm" placeholder="0.00" required>
            </div>
            <button type="button" class="btn btn-sm btn-outline-danger" onclick="document.getElementById('editUnitRow_${idx}').remove()">
                <i class="bi bi-x-lg"></i>
            </button>
        `;
        container.appendChild(row);
    }

    function viewProduct(id) {
        openModal('viewProductModal');
        document.getElementById('viewProductBody').innerHTML = '<div style="text-align: center; padding: 40px;"><div class="spinner-border text-primary"></div></div>';

        fetch(`/staff/products/${id}`, {
            credentials: 'same-origin',
            headers: { 'Accept': 'application/json' }
        })
        .then(res => res.json())
        .then(res => {
            const p = res.data;
            let unitsList = p.units.map(u => `<span class="badge badge-navy">${u.unit_name} (x${parseFloat(u.conversion_factor)} = ₱${parseFloat(u.srp).toFixed(2)})</span>`).join(' ');
            let suppliersList = p.suppliers.map(s => `<span class="badge badge-primary">${s.name}</span>`).join(' ');

            let batchesHtml = '';
            if (p.batches && p.batches.length > 0) {
                batchesHtml = p.batches.map(b => `
                    <tr>
                        <td><strong>${b.batch_code}</strong></td>
                        <td>${b.warehouse ? b.warehouse.name : 'N/A'}</td>
                        <td>${b.supplier ? b.supplier.name : 'N/A'}</td>
                        <td>${b.receipt_date}</td>
                        <td style="font-weight: 700;">${parseFloat(b.current_quantity).toFixed(2)} ${p.base_unit}</td>
                        <td>₱${parseFloat(b.unit_cost).toFixed(2)}</td>
                        <td><span class="badge ${b.status === 'active' ? 'badge-success' : 'badge-danger'}">${b.status}</span></td>
                    </tr>
                `).join('');
            } else {
                batchesHtml = '<tr><td colspan="7" class="text-center text-muted">No batches recorded for this product yet.</td></tr>';
            }

            document.getElementById('viewProductBody').innerHTML = `
                <div style="display: grid; grid-template-columns: 240px 1fr; gap: 24px; margin-bottom: 24px;">
                    <div>
                        <img src="${p.image_path ? '/' + p.image_path : '/assets/images/placeholder-product.png'}" alt="${p.name}" style="width: 100%; border-radius: var(--radius-md); border: 1px solid var(--card-border); object-fit: cover; max-height: 240px;">
                    </div>
                    <div>
                        <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                            <div>
                                <span class="badge badge-navy mb-2">${p.category ? p.category.name : 'General'}</span>
                                <h2 style="font-size: 1.4rem; font-weight: 800; color: var(--color-deep-navy); margin-bottom: 4px;">${p.name}</h2>
                                <div style="font-size: 0.88rem; color: var(--text-light); margin-bottom: 12px;">SKU: <strong>${p.sku}</strong> | Barcode: ${p.barcode || 'None'}</div>
                            </div>
                            <span class="badge ${p.is_active ? 'badge-success' : 'badge-danger'}" style="font-size: 0.85rem; padding: 6px 12px;">
                                ${p.is_active ? 'Active Masterfile' : 'Inactive'}
                            </span>
                        </div>

                        <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 12px; margin-bottom: 16px;">
                            <div style="background: var(--body-bg); padding: 12px; border-radius: var(--radius-sm); border: 1px solid var(--card-border);">
                                <div style="font-size: 0.74rem; color: var(--text-light); text-transform: uppercase;">Available Stock</div>
                                <div style="font-size: 1.25rem; font-weight: 800; color: var(--color-deep-navy);">${parseFloat(p.available_stock || 0).toFixed(2)} ${p.base_unit}</div>
                            </div>
                            <div style="background: var(--body-bg); padding: 12px; border-radius: var(--radius-sm); border: 1px solid var(--card-border);">
                                <div style="font-size: 0.74rem; color: var(--text-light); text-transform: uppercase;">Selling Price (SRP)</div>
                                <div style="font-size: 1.25rem; font-weight: 800; color: var(--color-primary-blue);">₱${parseFloat(p.default_srp).toFixed(2)}</div>
                            </div>
                            <div style="background: var(--body-bg); padding: 12px; border-radius: var(--radius-sm); border: 1px solid var(--card-border);">
                                <div style="font-size: 0.74rem; color: var(--text-light); text-transform: uppercase;">Weighted Avg Cost</div>
                                <div style="font-size: 1.25rem; font-weight: 800; color: var(--text-muted);">₱${parseFloat(p.average_cost).toFixed(2)}</div>
                            </div>
                        </div>

                        <div style="margin-bottom: 12px;">
                            <div style="font-size: 0.78rem; font-weight: 700; color: var(--color-deep-navy); margin-bottom: 4px;">Selling Units:</div>
                            ${unitsList || '<span class="text-muted">Base unit only</span>'}
                        </div>

                        <div>
                            <div style="font-size: 0.78rem; font-weight: 700; color: var(--color-deep-navy); margin-bottom: 4px;">Assigned Suppliers:</div>
                            ${suppliersList || '<span class="text-muted">None specified</span>'}
                        </div>
                    </div>
                </div>

                <h4 style="font-size: 1rem; font-weight: 700; color: var(--color-deep-navy); margin-bottom: 10px;">
                    <i class="bi bi-boxes me-1 text-primary"></i> Inventory Batches on Hand
                </h4>
                <div class="table-responsive">
                    <table class="custom-table" style="font-size: 0.82rem;">
                        <thead>
                            <tr>
                                <th>Batch Code</th>
                                <th>Warehouse</th>
                                <th>Supplier</th>
                                <th>Receipt Date</th>
                                <th>Current Qty</th>
                                <th>Batch Cost</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>${batchesHtml}</tbody>
                    </table>
                </div>
            `;
        })
        .catch(err => {
            console.error(err);
            document.getElementById('viewProductBody').innerHTML = '<div class="alert alert-danger">Failed to load product details.</div>';
        });
    }

    function editProduct(id) {
        openModal('editProductModal');
        document.getElementById('editProductBody').innerHTML = '<div style="text-align: center; padding: 40px;"><div class="spinner-border text-primary"></div></div>';
        document.getElementById('editProductForm').action = `/staff/products/${id}`;

        fetch(`/staff/products/${id}`, {
            credentials: 'same-origin',
            headers: { 'Accept': 'application/json' }
        })
        .then(res => res.json())
        .then(res => {
            const p = res.data;
            const currentCatId = p.category_id;
            const assignedSuppliers = p.suppliers.map(s => s.id);

            let catOptions = categoriesData.map(c =>
                `<option value="${c.id}" ${c.id === currentCatId ? 'selected' : ''}>${c.name}</option>`
            ).join('');

            let supOptions = suppliersData.map(s =>
                `<option value="${s.id}" ${assignedSuppliers.includes(s.id) ? 'selected' : ''}>${s.name}</option>`
            ).join('');

            // Secondary units for edit
            let secondaryUnits = p.units.filter(u => parseFloat(u.conversion_factor) !== 1 || u.unit_name.toLowerCase() !== p.base_unit.toLowerCase());
            let unitsHtml = '';
            if (secondaryUnits.length > 0) {
                unitsHtml = secondaryUnits.map((u, i) => `
                    <div id="unitRowItem_${u.id}" style="display: grid; grid-template-columns: 2fr 2fr 2fr auto; gap: 12px; align-items: flex-end; margin-bottom: 8px; background: var(--color-mist-blue); padding: 8px 12px; border-radius: var(--radius-sm);">
                        <input type="hidden" name="units[${i}][id]" value="${u.id}">
                        <div>
                            <label class="form-label" style="font-size: 0.74rem;">Unit Name</label>
                            <input type="text" name="units[${i}][unit_name]" class="form-control form-control-sm" value="${u.unit_name}" required>
                        </div>
                        <div>
                            <label class="form-label" style="font-size: 0.74rem;">Multiplier (x${p.base_unit})</label>
                            <input type="number" step="0.0001" name="units[${i}][conversion_factor]" class="form-control form-control-sm" value="${u.conversion_factor}" required>
                        </div>
                        <div>
                            <label class="form-label" style="font-size: 0.74rem;">Wholesale SRP (₱)</label>
                            <input type="number" step="0.01" name="units[${i}][srp]" class="form-control form-control-sm" value="${u.srp}" required>
                        </div>
                        <button type="button" class="btn btn-sm btn-outline-danger" onclick="deleteUnit(${u.id})">
                            <i class="bi bi-trash"></i>
                        </button>
                    </div>
                `).join('');
            } else {
                unitsHtml = '<div class="text-muted" style="font-size: 0.8rem; margin-bottom: 8px;" id="noSecondaryUnitsMsg">No secondary conversion units configured.</div>';
            }

            document.getElementById('editProductBody').innerHTML = `
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                    <div class="form-group">
                        <label class="form-label">Product SKU <span class="text-danger">*</span></label>
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
                        <select name="category_id" class="form-select" required>${catOptions}</select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Base Unit <span class="text-danger">*</span></label>
                        <input type="text" name="base_unit" class="form-control" value="${p.base_unit}" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Selling SRP (₱) <span class="text-danger">*</span></label>
                        <input type="number" step="0.01" name="default_srp" class="form-control" value="${p.default_srp}" required>
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">Suppliers</label>
                    <select name="supplier_ids[]" class="form-select" multiple style="height: 90px;">${supOptions}</select>
                    <div class="form-text">Hold Ctrl / Cmd to select multiple suppliers</div>
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
                            <i class="bi bi-plus-circle me-1"></i> Add Unit
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
        document.getElementById('deleteProductForm').action = `/staff/products/${id}`;
        openModal('deleteProductModal');
    }

    function deleteUnit(unitId) {
        if (!confirm('Are you sure you want to delete this conversion unit?')) return;

        fetch(`/staff/products/units/${unitId}`, {
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

