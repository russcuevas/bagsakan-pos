@extends('layouts.app')

@section('title', 'Spoilage & Losses')
@section('page_title', 'Spoilage & Inventory Loss Tracking')

@section('content')
<div class="stats-grid" style="grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); margin-bottom: 24px;">
    <div class="stat-card accent-red">
        <div class="stat-header">
            <span class="stat-label">Spoilage Loss (This Month)</span>
            <div class="stat-icon"><i class="bi bi-trash3"></i></div>
        </div>
        <div class="stat-value">₱{{ number_format($totalSpoilageCostMonth, 2) }}</div>
        <div class="stat-helper">Approved loss amount recorded</div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <div>
            <h2 class="card-title">Spoilage & Wastage Reports</h2>
            <p class="card-description">Review damaged, bruised, or overripe goods submitted by warehouse staff</p>
        </div>
        <button type="button" class="btn btn-primary" onclick="openModal('addSpoilageModal')">
            <i class="bi bi-plus-lg me-1"></i> Report Spoilage Entry
        </button>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="custom-table datatable-init">
                <thead>
                    <tr>
                        <th>Ref #</th>
                        <th>Photo</th>
                        <th>Product SKU</th>
                        <th>Warehouse</th>
                        <th>Quantity Lost</th>
                        <th>Cost of Loss</th>
                        <th>Reason</th>
                        <th>Date & Submitter</th>
                        <th>Status</th>
                        <th style="text-align: right;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($spoilages as $s)
                        <tr>
                            <td><strong>{{ $s->spoilage_number }}</strong></td>
                            <td>
                                @if($s->photo_path && file_exists(public_path($s->photo_path)))
                                    <a href="{{ asset($s->photo_path) }}" target="_blank">
                                        <img src="{{ asset($s->photo_path) }}" alt="Photo" style="width: 42px; height: 42px; object-fit: cover; border-radius: var(--radius-sm); border: 1px solid var(--card-border);">
                                    </a>
                                @else
                                    <span style="font-size: 0.75rem; color: var(--text-light);">No photo</span>
                                @endif
                            </td>
                            <td>
                                <strong>{{ $s->product->name ?? 'N/A' }}</strong>
                                <div style="font-size: 0.74rem; color: var(--text-light);">{{ $s->product->sku ?? '' }}</div>
                            </td>
                            <td><span class="badge badge-navy">{{ $s->warehouse->code ?? 'WH' }}</span></td>
                            <td style="font-weight: 800; color: #991B1B;">
                                {{ number_format($s->quantity, 2) }} {{ $s->product->base_unit ?? '' }}
                            </td>
                            <td style="font-weight: 800; color: var(--danger-red);">₱{{ number_format($s->total_cost, 2) }}</td>
                            <td><em>{{ $s->reason }}</em></td>
                            <td>
                                <div style="font-size: 0.8rem;">{{ $s->spoilage_date->format('M d, Y') }}</div>
                                <div style="font-size: 0.72rem; color: var(--text-light);">By: {{ $s->submitter->name ?? 'Staff' }}</div>
                            </td>
                            <td>
                                @if($s->status === 'approved')
                                    <span class="badge badge-success"><i class="bi bi-check-circle me-1"></i> Approved</span>
                                @elseif($s->status === 'pending')
                                    <span class="badge badge-warning"><i class="bi bi-hourglass-split me-1"></i> Pending Sign-off</span>
                                @else
                                    <span class="badge badge-danger">Rejected</span>
                                @endif
                            </td>
                            <td style="text-align: right;">
                                @if($s->status === 'pending')
                                    <div style="display: inline-flex; gap: 6px;">
                                        <form action="{{ route('admin.spoilage.approve', $s->id) }}" method="POST" class="ajax-form">
                                            @csrf
                                            <button type="submit" class="btn btn-sm btn-success" title="Approve and Deduct Stock">
                                                <i class="bi bi-check-lg"></i>
                                            </button>
                                        </form>
                                        <button type="button" class="btn btn-sm btn-danger" onclick="openRejectModal({{ $s->id }})" title="Reject">
                                            <i class="bi bi-x-lg"></i>
                                        </button>
                                    </div>
                                @else
                                    <span style="font-size: 0.75rem; color: var(--text-light);">
                                        {{ $s->approved_at ? $s->approved_at->format('M d, Y') : 'Processed' }}
                                    </span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Add Spoilage Modal -->
<div id="addSpoilageModal" class="modal-overlay">
    <div class="modal-dialog modal-lg">
        <div class="modal-header">
            <h3 class="modal-title"><i class="bi bi-trash3 text-danger me-2"></i> Report Damaged / Spoiled Stock</h3>
            <button type="button" class="modal-close-btn" data-close-modal="addSpoilageModal"><i class="bi bi-x-lg"></i></button>
        </div>
        <form action="{{ route('admin.spoilage.store') }}" method="POST" enctype="multipart/form-data" class="ajax-form">
            @csrf
            <div class="modal-body">
                <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 16px;">
                    <div class="form-group">
                        <label class="form-label" for="spoil_product">Select Product SKU <span class="text-danger">*</span></label>
                        <select id="spoil_product" name="product_id" class="form-select select2-init" required onchange="loadProductBatches(this.value)">
                            <option value="">Select product...</option>
                            @foreach($products as $p)
                                <option value="{{ $p->id }}">{{ $p->sku }} - {{ $p->name }} (Available: {{ number_format($p->available_stock, 2) }} {{ $p->base_unit }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="spoil_wh">Warehouse <span class="text-danger">*</span></label>
                        <select id="spoil_wh" name="warehouse_id" class="form-select" required>
                            @foreach($warehouses as $w)
                                <option value="{{ $w->id }}">{{ $w->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                    <div class="form-group">
                        <label class="form-label" for="spoil_batch">Target Batch / Supplier Lot (Optional)</label>
                        <select id="spoil_batch" name="batch_id" class="form-select">
                            <option value="">Auto-Deduct (FIFO Oldest Active Batch)</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="spoil_qty">Quantity Spoiled (in Base Unit) <span class="text-danger">*</span></label>
                        <input type="number" step="0.01" id="spoil_qty" name="quantity" class="form-control" placeholder="0.00" required>
                    </div>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                    <div class="form-group">
                        <label class="form-label" for="spoil_date">Inspection Date <span class="text-danger">*</span></label>
                        <input type="date" id="spoil_date" name="spoilage_date" class="form-control" value="{{ date('Y-m-d') }}" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="spoil_photo">Evidence Photo (Max 2MB)</label>
                        <input type="file" id="spoil_photo" name="photo" class="form-control" accept="image/*" data-preview="spoilPhotoPreview">
                    </div>
                </div>

                <div style="margin-bottom: 10px;">
                    <img id="spoilPhotoPreview" src="" alt="Photo Preview" style="max-height: 100px; display: none; border-radius: var(--radius-sm); border: 1px solid var(--card-border);">
                </div>

                <div class="form-group">
                    <label class="form-label" for="spoil_reason">Reason for Spoilage / Damage <span class="text-danger">*</span></label>
                    <input type="text" id="spoil_reason" name="reason" class="form-control" placeholder="e.g. Overripeness, transit bruise, pest damage" required>
                </div>

                <div style="background: var(--color-mist-blue); border: 1.5px solid #d2eaf8; border-radius: var(--radius-sm); padding: 12px 16px; margin-bottom: 16px;">
                    <input type="checkbox" id="auto_approve_spoil" name="auto_approve" value="1" checked style="width: 16px; height: 16px;">
                    <label for="auto_approve_spoil" style="cursor: pointer; margin-bottom: 0; font-size: 0.85rem; font-weight: 600; color: var(--color-deep-navy);">
                        Approve and deduct stock loss immediately (Admin Sign-off)
                    </label>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline" data-close-modal="addSpoilageModal">Cancel</button>
                <button type="submit" class="btn btn-danger"><i class="bi bi-trash3 me-1"></i> Post Spoilage</button>
            </div>
        </form>
    </div>
</div>

<!-- Reject Spoilage Modal -->
<div id="rejectSpoilageModal" class="modal-overlay">
    <div class="modal-dialog">
        <div class="modal-header">
            <h3 class="modal-title text-danger"><i class="bi bi-x-octagon me-2"></i> Reject Spoilage Entry</h3>
            <button type="button" class="modal-close-btn" data-close-modal="rejectSpoilageModal"><i class="bi bi-x-lg"></i></button>
        </div>
        <form id="rejectSpoilageForm" method="POST" class="ajax-form">
            @csrf
            <div class="modal-body">
                <div class="form-group">
                    <label class="form-label">Rejection Justification <span class="text-danger">*</span></label>
                    <textarea name="rejection_reason" class="form-control" rows="3" placeholder="Explain why this spoilage claim was rejected..." required></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline" data-close-modal="rejectSpoilageModal">Cancel</button>
                <button type="submit" class="btn btn-danger">Confirm Rejection</button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
    const productsList = {!! json_encode($products) !!};

    function loadProductBatches(productId) {
        const batchSelect = document.getElementById('spoil_batch');
        batchSelect.innerHTML = '<option value="">Auto-Deduct (FIFO Oldest Active Batch)</option>';
        if (!productId) return;

        const prod = productsList.find(x => x.id == productId);
        if (prod && prod.active_batches) {
            prod.active_batches.forEach(b => {
                const opt = document.createElement('option');
                opt.value = b.id;
                opt.innerText = `${b.batch_code} (Avail: ${parseFloat(b.current_quantity).toFixed(2)} ${prod.base_unit} @ ₱${parseFloat(b.unit_cost).toFixed(2)})`;
                batchSelect.appendChild(opt);
            });
        }
    }

    function openRejectModal(id) {
        document.getElementById('rejectSpoilageForm').action = `/admin/spoilage/${id}/reject`;
        openModal('rejectSpoilageModal');
    }
</script>
@endpush
