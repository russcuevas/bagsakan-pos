@extends('layouts.app')

@section('title', 'Report Spoilage')
@section('page_title', 'Spoilage & Damaged Goods Submissions')

@section('content')
<div class="card">
    <div class="card-header">
        <div>
            <h2 class="card-title">My Submitted Spoilage Reports</h2>
            <p class="card-description">Report overripe, bruised, or damaged goods for supervisor sign-off</p>
        </div>
        <button type="button" class="btn btn-primary" onclick="openModal('staffSpoilageModal')">
            <i class="bi bi-plus-lg me-1"></i> Submit Spoilage Entry
        </button>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="custom-table datatable-init">
                <thead>
                    <tr>
                        <th>Report Ref #</th>
                        <th>Photo</th>
                        <th>Product SKU</th>
                        <th>Warehouse</th>
                        <th>Quantity</th>
                        <th>Reason</th>
                        <th>Date Submitted</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($spoilages as $s)
                        <tr>
                            <td><strong>{{ $s->spoilage_number }}</strong></td>
                            <td>
                                @if($s->photo_path && file_exists(public_path($s->photo_path)))
                                    <a href="{{ asset($s->photo_path) }}" target="_blank">
                                        <img src="{{ asset($s->photo_path) }}" alt="Photo" style="width: 40px; height: 40px; object-fit: cover; border-radius: var(--radius-sm); border: 1px solid var(--card-border);">
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
                            <td style="font-weight: 800; color: #991B1B;">{{ number_format($s->quantity, 2) }} {{ $s->product->base_unit ?? '' }}</td>
                            <td><em>{{ $s->reason }}</em></td>
                            <td style="font-size: 0.8rem; color: var(--text-light);">{{ $s->spoilage_date->format('M d, Y') }}</td>
                            <td>
                                @if($s->status === 'approved')
                                    <span class="badge badge-success"><i class="bi bi-check-circle me-1"></i> Approved</span>
                                @elseif($s->status === 'pending')
                                    <span class="badge badge-warning"><i class="bi bi-hourglass-split me-1"></i> Pending Admin Approval</span>
                                @else
                                    <span class="badge badge-danger">Rejected: {{ $s->rejection_reason }}</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Submit Spoilage Modal for Staff -->
<div id="staffSpoilageModal" class="modal-overlay">
    <div class="modal-dialog modal-lg">
        <div class="modal-header">
            <h3 class="modal-title"><i class="bi bi-trash3 text-danger me-2"></i> Report Damaged / Spoiled Produce</h3>
            <button type="button" class="modal-close-btn" data-close-modal="staffSpoilageModal"><i class="bi bi-x-lg"></i></button>
        </div>
        <form action="{{ route('staff.spoilage.store') }}" method="POST" enctype="multipart/form-data" class="ajax-form">
            @csrf
            <div class="modal-body">
                <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 16px;">
                    <div class="form-group">
                        <label class="form-label" for="stf_spoil_prod">Product <span class="text-danger">*</span></label>
                        <select id="stf_spoil_prod" name="product_id" class="form-select select2-init" required>
                            <option value="">Select product...</option>
                            @foreach($products as $p)
                                <option value="{{ $p->id }}">{{ $p->sku }} - {{ $p->name }} (Available: {{ number_format($p->available_stock, 2) }} {{ $p->base_unit }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="stf_spoil_wh">Warehouse <span class="text-danger">*</span></label>
                        <select id="stf_spoil_wh" name="warehouse_id" class="form-select" required>
                            @foreach($warehouses as $w)
                                <option value="{{ $w->id }}">{{ $w->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                    <div class="form-group">
                        <label class="form-label" for="stf_spoil_qty">Quantity Spoiled (in Base Unit) <span class="text-danger">*</span></label>
                        <input type="number" step="0.01" id="stf_spoil_qty" name="quantity" class="form-control" placeholder="0.00" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="stf_spoil_date">Date of Inspection <span class="text-danger">*</span></label>
                        <input type="date" id="stf_spoil_date" name="spoilage_date" class="form-control" value="{{ date('Y-m-d') }}" required>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label" for="stf_spoil_photo">Evidence Photo (Max 2MB)</label>
                    <input type="file" id="stf_spoil_photo" name="photo" class="form-control" accept="image/*" data-preview="stfSpoilPhotoPreview">
                </div>

                <div style="margin-bottom: 10px;">
                    <img id="stfSpoilPhotoPreview" src="" alt="Photo Preview" style="max-height: 100px; display: none; border-radius: var(--radius-sm); border: 1px solid var(--card-border);">
                </div>

                <div class="form-group">
                    <label class="form-label" for="stf_spoil_reason">Detailed Reason <span class="text-danger">*</span></label>
                    <input type="text" id="stf_spoil_reason" name="reason" class="form-control" placeholder="e.g. Broken eggs during pallet handling, overripe tomatoes" required>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline" data-close-modal="staffSpoilageModal">Cancel</button>
                <button type="submit" class="btn btn-danger"><i class="bi bi-check-lg me-1"></i> Submit for Approval</button>
            </div>
        </form>
    </div>
</div>
@endsection
