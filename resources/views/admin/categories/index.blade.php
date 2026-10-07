@extends('layouts.app')

@section('title', 'Product Categories')
@section('page_title', 'Product Categories Management')

@section('content')
<!-- Stats Overview -->
<div class="stats-grid" style="grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); margin-bottom: 24px;">
    <div class="stat-card accent-blue">
        <div class="stat-header">
            <span class="stat-label">Total Categories</span>
            <div class="stat-icon"><i class="bi bi-tags text-primary"></i></div>
        </div>
        <div class="stat-value">{{ number_format($totalCategories) }}</div>
        <div class="stat-helper">Master product classification tags</div>
    </div>

    <div class="stat-card accent-green">
        <div class="stat-header">
            <span class="stat-label">Total Products Categorized</span>
            <div class="stat-icon"><i class="bi bi-box-seam text-success"></i></div>
        </div>
        <div class="stat-value">{{ number_format($totalProductsInCategories) }} Items</div>
        <div class="stat-helper">Products mapped to active categories</div>
    </div>

    <div class="stat-card accent-cyan">
        <div class="stat-header">
            <span class="stat-label">Top Category</span>
            <div class="stat-icon"><i class="bi bi-trophy text-info"></i></div>
        </div>
        <div class="stat-value" style="font-size: 1.35rem; text-overflow: ellipsis; overflow: hidden; white-space: nowrap;">
            {{ $topCategory ? $topCategory->name : 'None yet' }}
        </div>
        <div class="stat-helper">
            {{ $topCategory ? ($topCategory->products_count . ' product(s) registered') : 'No products yet' }}
        </div>
    </div>
</div>

<!-- Main Categories Card -->
<div class="card">
    <div class="card-header">
        <div>
            <h2 class="card-title"><i class="bi bi-tags text-primary me-2"></i> Categories Directory</h2>
            <p class="card-description">Create, edit, and organize product categories for inventory & POS grouping</p>
        </div>
        <div style="display: flex; gap: 8px;">
            <a href="{{ route('admin.products.index') }}" class="btn btn-outline">
                <i class="bi bi-box-seam me-1"></i> View Products
            </a>
            <button type="button" class="btn btn-primary" onclick="openCreateCategoryModal()">
                <i class="bi bi-plus-lg me-1"></i> Add Category
            </button>
        </div>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="custom-table datatable-init" id="categoriesTable">
                <thead>
                    <tr>
                        <th style="width: 60px;">#</th>
                        <th>Category Name</th>
                        <th>Category Code</th>
                        <th>Description</th>
                        <th style="text-align: center;">Assigned Products</th>
                        <th style="width: 140px; text-align: right;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($categories as $idx => $category)
                        <tr id="category-row-{{ $category->id }}">
                            <td style="color: var(--text-light); font-weight: 600;">
                                {{ $idx + 1 }}
                            </td>
                            <td>
                                <div style="font-weight: 700; color: var(--color-deep-navy); font-size: 0.95rem;">
                                    <i class="bi bi-folder2 text-primary me-1"></i> {{ $category->name }}
                                </div>
                            </td>
                            <td>
                                @if($category->code)
                                    <span class="badge badge-navy" style="font-family: monospace; font-size: 0.78rem; letter-spacing: 0.5px;">
                                        {{ $category->code }}
                                    </span>
                                @else
                                    <span style="color: var(--text-light); font-size: 0.8rem; font-style: italic;">No code</span>
                                @endif
                            </td>
                            <td>
                                <div style="max-width: 340px; color: var(--text-muted); font-size: 0.85rem; line-height: 1.4;">
                                    {{ $category->description ?: '—' }}
                                </div>
                            </td>
                            <td style="text-align: center;">
                                @if($category->products_count > 0)
                                    <span class="badge badge-success" style="font-size: 0.82rem; padding: 4px 10px;">
                                        <i class="bi bi-box-seam me-1"></i> {{ $category->products_count }} {{ Str::plural('Product', $category->products_count) }}
                                    </span>
                                @else
                                    <span class="badge" style="background: #f1f5f9; color: #64748b; border: 1px solid #e2e8f0; font-size: 0.8rem;">
                                        0 Products
                                    </span>
                                @endif
                            </td>
                            <td style="text-align: right;">
                                <div style="display: inline-flex; gap: 6px; justify-content: flex-end;">
                                    <button type="button" 
                                            class="btn btn-sm btn-secondary" 
                                            onclick='openEditCategoryModal(@json($category))' 
                                            title="Edit Category">
                                        <i class="bi bi-pencil-square"></i>
                                    </button>
                                    <button type="button" 
                                            class="btn btn-sm btn-outline-danger" 
                                            onclick='confirmDeleteCategory(@json($category))' 
                                            title="Delete Category">
                                        <i class="bi bi-trash3"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" style="text-align: center; padding: 40px 20px;">
                                <div style="color: var(--text-muted); font-size: 1rem;">
                                    <i class="bi bi-folder-x" style="font-size: 2.5rem; display: block; margin-bottom: 10px; color: var(--text-light);"></i>
                                    No categories found. Click <strong>"Add Category"</strong> to manually add one!
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- ==========================================
     ADD CATEGORY MODAL
=========================================== -->
<div id="createCategoryModal" class="modal-overlay">
    <div class="modal-dialog">
        <div class="modal-header">
            <h3 class="modal-title"><i class="bi bi-plus-circle text-primary me-2"></i> Add New Category</h3>
            <button type="button" class="modal-close-btn" data-close-modal="createCategoryModal">
                <i class="bi bi-x-lg"></i>
            </button>
        </div>
        <form id="createCategoryForm" onsubmit="submitCategoryForm(event, 'create')">
            @csrf
            <div class="modal-body">
                <div class="form-group mb-3">
                    <label class="form-label" for="add_category_name">
                        Category Name <span class="text-danger">*</span>
                    </label>
                    <input type="text" 
                           id="add_category_name" 
                           name="name" 
                           class="form-control" 
                           placeholder="e.g. Vegetables, Fresh Poultry, Frozen Seafood, Dry Goods" 
                           required 
                           autofocus>
                    <div class="form-text" style="font-size: 0.76rem; color: var(--text-light); margin-top: 4px;">
                        Enter the manual name of the category as you'd like it to appear in inventory and POS.
                    </div>
                </div>

                <div class="form-group mb-3">
                    <label class="form-label" for="add_category_code">
                        Category Code / Abbreviation <span style="color: var(--text-light); font-weight: normal;">(Optional)</span>
                    </label>
                    <input type="text" 
                           id="add_category_code" 
                           name="code" 
                           class="form-control text-uppercase" 
                           placeholder="e.g. VEG, PLT, SEA, DRY" 
                           maxlength="20">
                    <div class="form-text" style="font-size: 0.76rem; color: var(--text-light); margin-top: 4px;">
                        Short identifier or prefix for fast SKU grouping.
                    </div>
                </div>

                <div class="form-group mb-3">
                    <label class="form-label" for="add_category_description">
                        Description / Notes <span style="color: var(--text-light); font-weight: normal;">(Optional)</span>
                    </label>
                    <textarea id="add_category_description" 
                              name="description" 
                              class="form-control" 
                              rows="3" 
                              placeholder="Add brief details about what products belong to this category..."></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline" data-close-modal="createCategoryModal">Cancel</button>
                <button type="submit" id="saveCreateCategoryBtn" class="btn btn-primary">
                    <i class="bi bi-check-lg me-1"></i> Save Category
                </button>
            </div>
        </form>
    </div>
</div>

<!-- ==========================================
     EDIT CATEGORY MODAL
=========================================== -->
<div id="editCategoryModal" class="modal-overlay">
    <div class="modal-dialog">
        <div class="modal-header">
            <h3 class="modal-title"><i class="bi bi-pencil-square text-primary me-2"></i> Edit Category</h3>
            <button type="button" class="modal-close-btn" data-close-modal="editCategoryModal">
                <i class="bi bi-x-lg"></i>
            </button>
        </div>
        <form id="editCategoryForm" onsubmit="submitCategoryForm(event, 'edit')">
            @csrf
            @method('PUT')
            <input type="hidden" id="edit_category_id" name="category_id">
            <div class="modal-body">
                <div class="form-group mb-3">
                    <label class="form-label" for="edit_category_name">
                        Category Name <span class="text-danger">*</span>
                    </label>
                    <input type="text" 
                           id="edit_category_name" 
                           name="name" 
                           class="form-control" 
                           placeholder="e.g. Vegetables, Fresh Poultry" 
                           required>
                </div>

                <div class="form-group mb-3">
                    <label class="form-label" for="edit_category_code">
                        Category Code / Abbreviation <span style="color: var(--text-light); font-weight: normal;">(Optional)</span>
                    </label>
                    <input type="text" 
                           id="edit_category_code" 
                           name="code" 
                           class="form-control text-uppercase" 
                           placeholder="e.g. VEG, PLT" 
                           maxlength="20">
                </div>

                <div class="form-group mb-3">
                    <label class="form-label" for="edit_category_description">
                        Description / Notes <span style="color: var(--text-light); font-weight: normal;">(Optional)</span>
                    </label>
                    <textarea id="edit_category_description" 
                              name="description" 
                              class="form-control" 
                              rows="3" 
                              placeholder="Description / Notes..."></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline" data-close-modal="editCategoryModal">Cancel</button>
                <button type="submit" id="saveEditCategoryBtn" class="btn btn-primary">
                    <i class="bi bi-check-lg me-1"></i> Update Category
                </button>
            </div>
        </form>
    </div>
</div>

<!-- ==========================================
     DELETE CONFIRMATION MODAL
=========================================== -->
<div id="deleteCategoryModal" class="modal-overlay">
    <div class="modal-dialog modal-dialog-sm">
        <div class="modal-header">
            <h3 class="modal-title text-danger"><i class="bi bi-exclamation-triangle-fill me-2"></i> Delete Category</h3>
            <button type="button" class="modal-close-btn" data-close-modal="deleteCategoryModal">
                <i class="bi bi-x-lg"></i>
            </button>
        </div>
        <form id="deleteCategoryForm" onsubmit="submitDeleteCategory(event)">
            @csrf
            @method('DELETE')
            <input type="hidden" id="delete_category_id">
            <div class="modal-body">
                <p style="font-size: 0.95rem; color: var(--text-main); margin-bottom: 12px;">
                    Are you sure you want to permanently delete the category: 
                    <strong id="delete_category_name_text" style="color: var(--color-deep-navy);"></strong>?
                </p>
                <div id="delete_category_warning" style="display: none; background: #fee2e2; border: 1px solid #fca5a5; color: #b91c1c; padding: 10px 14px; border-radius: 6px; font-size: 0.82rem;">
                    <i class="bi bi-info-circle-fill me-1"></i>
                    <strong>Notice:</strong> This category has <span id="delete_category_product_count">0</span> linked product(s). You must reassign or remove those products before deleting this category.
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline" data-close-modal="deleteCategoryModal">Cancel</button>
                <button type="submit" id="confirmDeleteBtn" class="btn btn-danger">
                    <i class="bi bi-trash3 me-1"></i> Delete Category
                </button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
function openCreateCategoryModal() {
    const form = document.getElementById('createCategoryForm');
    form.reset();
    openModal('createCategoryModal');
    setTimeout(() => {
        document.getElementById('add_category_name')?.focus();
    }, 150);
}

function openEditCategoryModal(cat) {
    document.getElementById('edit_category_id').value = cat.id;
    document.getElementById('edit_category_name').value = cat.name || '';
    document.getElementById('edit_category_code').value = cat.code || '';
    document.getElementById('edit_category_description').value = cat.description || '';
    openModal('editCategoryModal');
    setTimeout(() => {
        document.getElementById('edit_category_name')?.focus();
    }, 150);
}

function confirmDeleteCategory(cat) {
    document.getElementById('delete_category_id').value = cat.id;
    document.getElementById('delete_category_name_text').innerText = cat.name;

    const prodCount = cat.products_count || 0;
    const warnBox = document.getElementById('delete_category_warning');
    const deleteBtn = document.getElementById('confirmDeleteBtn');

    if (prodCount > 0) {
        document.getElementById('delete_category_product_count').innerText = prodCount;
        warnBox.style.display = 'block';
        deleteBtn.disabled = true;
        deleteBtn.title = "Cannot delete category with linked products.";
    } else {
        warnBox.style.display = 'none';
        deleteBtn.disabled = false;
        deleteBtn.removeAttribute('title');
    }

    openModal('deleteCategoryModal');
}

async function submitCategoryForm(e, mode) {
    e.preventDefault();
    const isCreate = mode === 'create';
    const btn = document.getElementById(isCreate ? 'saveCreateCategoryBtn' : 'saveEditCategoryBtn');
    const form = document.getElementById(isCreate ? 'createCategoryForm' : 'editCategoryForm');
    const catId = isCreate ? null : document.getElementById('edit_category_id').value;

    const url = isCreate 
        ? "{{ route('admin.categories.store') }}" 
        : "{{ url('admin/categories') }}/" + catId;

    const originalBtnHtml = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Saving...';

    const formData = new FormData(form);

    try {
        const res = await fetch(url, {
            method: 'POST', // With _method=PUT inside formData for edit
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Accept': 'application/json'
            },
            body: formData
        });

        const data = await res.json();

        if (res.ok && data.success) {
            closeModal(isCreate ? 'createCategoryModal' : 'editCategoryModal');
            showToast('success', 'Success', data.message);
            setTimeout(() => location.reload(), 600);
        } else {
            let errorMsg = data.message || 'Validation error.';
            if (data.errors) {
                const errList = Object.values(data.errors).flat().join('<br>');
                errorMsg = errList;
            }
            showToast('error', 'Error', errorMsg);
            btn.disabled = false;
            btn.innerHTML = originalBtnHtml;
        }
    } catch (err) {
        showToast('error', 'Error', err.message || 'Something went wrong.');
        btn.disabled = false;
        btn.innerHTML = originalBtnHtml;
    }
}

async function submitDeleteCategory(e) {
    e.preventDefault();
    const catId = document.getElementById('delete_category_id').value;
    const btn = document.getElementById('confirmDeleteBtn');
    const url = "{{ url('admin/categories') }}/" + catId;

    const originalBtnHtml = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Deleting...';

    try {
        const res = await fetch(url, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Accept': 'application/json',
                'Content-Type': 'application/json'
            }
        });

        const data = await res.json();

        if (res.ok && data.success) {
            closeModal('deleteCategoryModal');
            showToast('success', 'Deleted', data.message);
            setTimeout(() => location.reload(), 600);
        } else {
            showToast('error', 'Delete Failed', data.message || 'Could not delete category.');
            btn.disabled = false;
            btn.innerHTML = originalBtnHtml;
        }
    } catch (err) {
        showToast('error', 'Error', err.message || 'Something went wrong.');
        btn.disabled = false;
        btn.innerHTML = originalBtnHtml;
    }
}
</script>
@endpush
@endsection
