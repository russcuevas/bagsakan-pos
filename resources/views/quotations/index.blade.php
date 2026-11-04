@extends('layouts.app')

@section('title', 'Customer Order List')
@section('page_title', 'Customer Order List Module')

@section('content')
    <div class="stats-grid" style="grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); margin-bottom: 24px;">
        <div class="stat-card accent-blue">
            <div class="stat-header">
                <span class="stat-label">Total Quotations</span>
                <div class="stat-icon"><i class="bi bi-file-earmark-text text-primary"></i></div>
            </div>
            <div class="stat-value">{{ $quotations->total() }} Quotes</div>
            <div class="stat-helper">Customer offer records</div>
        </div>

        <div class="stat-card accent-rose">
            <div class="stat-header">
                <span class="stat-label">Needs Revision</span>
                <div class="stat-icon"><i class="bi bi-arrow-counterclockwise text-danger"></i></div>
            </div>
            <div class="stat-value">{{ \App\Models\Quotation::where('status', 'returned')->orWhere('remarks', 'like', '%[RETURNED FOR REVISION]%')->count() }}</div>
            <div class="stat-helper">Returned by Admin for adjustments</div>
        </div>

        <div class="stat-card accent-green">
            <div class="stat-header">
                <span class="stat-label">Accepted Quotes</span>
                <div class="stat-icon"><i class="bi bi-check-circle text-success"></i></div>
            </div>
            <div class="stat-value">{{ \App\Models\Quotation::where('status', 'accepted')->count() }}</div>
            <div class="stat-helper">Ready for sales order conversion</div>
        </div>

        <div class="stat-card accent-amber">
            <div class="stat-header">
                <span class="stat-label">Converted to Sales</span>
                <div class="stat-icon"><i class="bi bi-cart-check text-warning"></i></div>
            </div>
            <div class="stat-value">{{ \App\Models\Quotation::where('status', 'converted')->count() }}</div>
            <div class="stat-helper">Fulfilled and billed sales</div>
        </div>
    </div>

    <!-- Quotations List Card -->
    <div class="card">
        <div class="card-header">
            <div>
                <h2 class="card-title">Order List Registry </h2>
                <p class="card-description">Issue pricing offers based on SKU Master without inventory constraints</p>
            </div>
            <button type="button" class="btn btn-primary" onclick="openCreateQuotationModal()">
                <i class="bi bi-file-earmark-plus me-1"></i> New Quotation
            </button>
        </div>
        <div class="card-body">
            <!-- Filter Bar -->
            <div
                style="background: var(--color-mist-blue); border: 1px solid var(--card-border); border-radius: 8px; padding: 14px; margin-bottom: 20px;">
                <form method="GET"
                    action="{{ route(auth()->user()->isAdmin() ? 'admin.quotations.index' : (auth()->user()->isPurchasing() ? 'staff.quotations.index' : 'cashier.quotations.index')) }}"
                    style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)) auto; gap: 12px; align-items: center;">
                    <div>
                        <input type="text" name="search" class="form-control form-control-sm"
                            placeholder="Search QTN # or Customer..." value="{{ request('search') }}">
                    </div>
                    <div>
                        <select name="status" class="form-select form-select-sm">
                            <option value="">All Statuses</option>
                            <option value="draft" {{ request('status') === 'draft' ? 'selected' : '' }}>Draft</option>
                            <option value="sent" {{ request('status') === 'sent' ? 'selected' : '' }}>Sent</option>
                            <option value="returned" {{ request('status') === 'returned' ? 'selected' : '' }}>Needs Revision</option>
                            <option value="accepted" {{ request('status') === 'accepted' ? 'selected' : '' }}>Accepted
                            </option>
                            <option value="rejected" {{ request('status') === 'rejected' ? 'selected' : '' }}>Rejected
                            </option>
                            <option value="expired" {{ request('status') === 'expired' ? 'selected' : '' }}>Expired</option>
                            <option value="converted" {{ request('status') === 'converted' ? 'selected' : '' }}>Converted
                            </option>
                        </select>
                    </div>
                    <div>
                        <input type="date" name="date_from" class="form-control form-control-sm" title="Date From"
                            value="{{ request('date_from') }}">
                    </div>
                    <div>
                        <input type="date" name="date_to" class="form-control form-control-sm" title="Date To"
                            value="{{ request('date_to') }}">
                    </div>
                    <div style="display: flex; gap: 6px;">
                        <button type="submit" class="btn btn-sm btn-secondary"><i class="bi bi-filter"></i> Filter</button>
                        <a href="{{ route(auth()->user()->isAdmin() ? 'admin.quotations.index' : (auth()->user()->isPurchasing() ? 'staff.quotations.index' : 'cashier.quotations.index')) }}"
                            class="btn btn-sm btn-outline">Reset</a>
                    </div>
                </form>
            </div>

            <div class="table-responsive">
                <table class="custom-table datatable-init">
                    <thead>
                        <tr>
                            <th>Quotation #</th>
                            <th>Date & Expiry</th>
                            <th>Customer</th>
                            <th>Items</th>
                            <th>Total Amount</th>
                            <th>Status</th>
                            <th>Prepared By</th>
                            <th style="text-align: right;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($quotations as $qtn)
                            <tr>
                                <td>
                                    <strong>{{ $qtn->quotation_number }}</strong>
                                    @if ($qtn->remarks)
                                        <div
                                            style="font-size: 0.74rem; color: var(--text-light); max-width: 180px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                                            {{ $qtn->remarks }}</div>
                                    @endif
                                </td>
                                <td style="font-size: 0.8rem; color: var(--text-light);">
                                    <div>{{ $qtn->quotation_date->format('M d, Y') }}</div>
                                    @if ($qtn->valid_until)
                                        <div style="font-size: 0.72rem; color: #64748b;">Until:
                                            {{ $qtn->valid_until->format('M d, Y') }}</div>
                                    @endif
                                </td>
                                <td>
                                    <div style="font-weight: 700;">{{ $qtn->customer_display_name }}</div>
                                    @if ($qtn->customer_contact)
                                        <div style="font-size: 0.74rem; color: var(--text-light);"><i
                                                class="bi bi-telephone"></i> {{ $qtn->customer_contact }}</div>
                                    @endif
                                </td>
                                <td>
                                    <span class="badge badge-navy">{{ $qtn->items->count() }} item(s)</span>
                                </td>
                                <td style="font-weight: 800; color: var(--color-deep-navy);">
                                    ₱{{ number_format($qtn->total_amount, 2) }}
                                </td>
                                <td>
                                    @php
                                        $isNeedsRev = $qtn->status === 'returned' || str_contains($qtn->remarks ?? '', '[RETURNED FOR REVISION]');
                                        $badge = match ($qtn->status) {
                                            'draft' => $isNeedsRev ? 'badge-danger' : 'badge-navy',
                                            'sent' => 'badge-primary',
                                            'accepted' => 'badge-success',
                                            'rejected' => 'badge-danger',
                                            'expired' => 'badge-warning',
                                            'converted' => 'badge-success',
                                            'returned' => 'badge-danger',
                                            default => 'badge-navy',
                                        };
                                        $badgeLabel = $isNeedsRev ? 'NEEDS REVISION' : strtoupper($qtn->status);
                                    @endphp
                                    <span class="badge {{ $badge }}">{{ $badgeLabel }}</span>
                                    @if ($isNeedsRev)
                                        <div style="font-size: 0.72rem; color: #dc2626; font-weight: 600; margin-top: 2px;">
                                            <i class="bi bi-exclamation-circle me-1"></i> Action Required
                                        </div>
                                    @endif
                                    @if ($qtn->status === 'converted' && $qtn->convertedSale)
                                        <div style="font-size: 0.72rem; margin-top: 2px;">
                                            <span class="text-muted">Sale:
                                            </span><strong>{{ $qtn->convertedSale->sale_number }}</strong>
                                        </div>
                                    @endif
                                </td>
                                <td style="font-size: 0.8rem;">
                                    {{ $qtn->preparer->name ?? 'System' }}
                                </td>
                                <td style="text-align: right;">
                                    <div style="display: inline-flex; gap: 4px; align-items: center; justify-content: flex-end; flex-wrap: wrap;">
                                        <button type="button" class="btn btn-sm btn-secondary"
                                            onclick="viewQuotationDetails({{ $qtn->id }})" title="View Details">
                                            <i class="bi bi-eye"></i>
                                        </button>

                                        <!-- Edit / Revise Button for Staff -->
                                        @if ($qtn->status !== 'converted')
                                            <button type="button" class="btn btn-sm btn-warning"
                                                onclick="openEditQuotationModal({{ $qtn->id }})"
                                                title="Revise Quotation Items & Info">
                                                <i class="bi bi-pencil me-1"></i> Revise
                                            </button>
                                        @endif

                                        <!-- Print Actions -->
                                        <a href="{{ route('quotations.print', ['quotation' => $qtn->id, 'with_price' => 1]) }}"
                                            target="_blank" class="btn btn-sm btn-outline" title="Print With Price"
                                            style="padding: 4px 8px;">
                                            <i class="bi bi-printer text-success"></i> Price
                                        </a>
                                        <a href="{{ route('quotations.print', ['quotation' => $qtn->id, 'no_price' => 1]) }}"
                                            target="_blank" class="btn btn-sm btn-outline" title="Print Without Price"
                                            style="padding: 4px 8px;">
                                            <i class="bi bi-printer text-muted"></i> No Price
                                        </a>

                                        <!-- Status Update -->
                                        @if ($qtn->status !== 'converted')
                                            <select class="form-select form-select-sm"
                                                style="width: auto; font-size: 0.78rem; padding: 2px 20px 2px 6px;"
                                                onchange="updateQuotationStatus({{ $qtn->id }}, this.value)">
                                                <option value="" disabled selected>Status...</option>
                                                <option value="draft">Draft</option>
                                                <option value="sent">Sent</option>
                                                <option value="accepted">Accepted</option>
                                                <option value="rejected">Rejected</option>
                                                <option value="expired">Expired</option>
                                            </select>
                                        @endif

                                        <!-- Convert to Sale -->
                                        @if ($qtn->status !== 'converted')
                                            <button type="button" class="btn btn-sm btn-success"
                                                onclick="openConvertModal({{ $qtn->id }}, '{{ $qtn->quotation_number }}', {{ $qtn->total_amount }})"
                                                title="Convert to Sale">
                                                <i class="bi bi-cart-check"></i> Convert
                                            </button>

                                            <form action="{{ route('quotations.destroy', $qtn->id) }}" method="POST" style="display:inline;" onsubmit="return confirm('Are you sure you want to delete quotation {{ $qtn->quotation_number }}?')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete Quotation">
                                                    <i class="bi bi-trash"></i>
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

    <!-- CREATE QUOTATION MODAL -->
    <div id="createQuotationModal" class="modal-overlay">
        <div class="modal-dialog modal-xl" style="max-width: 1050px;">
            <div class="modal-header">
                <h3 class="modal-title"><i class="bi bi-file-earmark-plus me-2 text-primary"></i> Create Sales Quotation
                </h3>
                <button type="button" class="modal-close-btn" data-close-modal="createQuotationModal"><i
                        class="bi bi-x-lg"></i></button>
            </div>
            <form id="createQuotationForm" onsubmit="submitQuotation(event)">
                @csrf
                <div class="modal-body">
                    <div
                        style="background: #eff6ff; border: 1px solid #bfdbfe; color: #1e40af; padding: 10px 14px; border-radius: 6px; font-size: 0.82rem; margin-bottom: 16px;">
                        <i class="bi bi-info-circle-fill me-1"></i> <strong>Note:</strong> Quotations do not deduct or
                        reserve inventory. Stock limitations do not apply.
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 14px; margin-bottom: 14px;">
                        <div class="form-group">
                            <label class="form-label" for="quoteCustomerSelect">Select Registered Customer</label>
                            <select name="customer_id" id="quoteCustomerSelect" class="form-select"
                                onchange="onCustomerSelectChange(this, 'create')">
                                <option value="">-- Walk-in / Custom Customer --</option>
                                @foreach ($customers as $c)
                                    <option value="{{ $c->id }}" data-contact="{{ $c->contact_number }}"
                                        data-address="{{ $c->address }}">
                                        {{ $c->name }} {{ $c->business_name ? "({$c->business_name})" : '' }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="quoteCustomerName">Customer / Business Name</label>
                            <input type="text" name="customer_name" id="quoteCustomerName" class="form-control"
                                placeholder="Enter name if walk-in">
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="quoteCustomerContact">Contact Number</label>
                            <input type="text" name="customer_contact" id="quoteCustomerContact" class="form-control"
                                placeholder="Mobile / Phone">
                        </div>
                    </div>

                    <div style="display: grid; grid-template-columns: 2fr 1fr 1fr; gap: 14px; margin-bottom: 16px;">
                        <div class="form-group">
                            <label class="form-label" for="quoteCustomerAddress">Delivery / Billing Address</label>
                            <input type="text" name="customer_address" id="quoteCustomerAddress" class="form-control"
                                placeholder="Customer address">
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="quoteDate">Quotation Date <span
                                    class="text-danger">*</span></label>
                            <input type="date" name="quotation_date" id="quoteDate" class="form-control"
                                value="{{ date('Y-m-d') }}" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="quoteValidUntil">Valid Until</label>
                            <input type="date" name="valid_until" id="quoteValidUntil" class="form-control"
                                value="{{ date('Y-m-d', strtotime('+15 days')) }}">
                        </div>
                    </div>

                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                        <span style="font-weight: 700; color: var(--color-deep-navy); font-size: 0.9rem;">
                            <i class="bi bi-box-seam me-1 text-primary"></i> Quoted Line Items
                        </span>
                        <button type="button" class="btn btn-sm btn-outline" onclick="addQuotationRow('create')">
                            <i class="bi bi-plus-circle me-1"></i> Add Item Line
                        </button>
                    </div>

                    <div class="table-responsive"
                        style="border: 1px solid var(--card-border); border-radius: 8px; margin-bottom: 16px;">
                        <table class="table mb-0" style="width: 100%;">
                            <thead
                                style="background: var(--color-mist-blue); font-size: 0.8rem; text-transform: uppercase;">
                                <tr>
                                    <th style="padding: 10px; width: 35%;">SKU / Product Description</th>
                                    <th style="padding: 10px; width: 15%;">Unit</th>
                                    <th style="padding: 10px; width: 12%;">Qty</th>
                                    <th style="padding: 10px; width: 15%;">Unit Selling Price</th>
                                    <th style="padding: 10px; width: 10%;">Discount</th>
                                    <th style="padding: 10px; width: 13%;">Subtotal</th>
                                    <th style="padding: 10px; width: 5%; text-align: center;">Action</th>
                                </tr>
                            </thead>
                            <tbody id="createQuotationRowsContainer">
                                <!-- Populated dynamically -->
                            </tbody>
                        </table>
                    </div>

                    <div style="display: grid; grid-template-columns: 1.2fr 0.8fr; gap: 16px;">
                        <div class="form-group">
                            <label class="form-label" for="quoteRemarks">Terms & Remarks / Notes</label>
                            <textarea name="remarks" id="quoteRemarks" class="form-control" rows="3"
                                placeholder="e.g., Payment Terms: 50% DP upon order, 50% upon delivery. Prices subject to change without prior notice."></textarea>
                        </div>
                        <div
                            style="background: var(--color-mist-blue); border: 1px solid var(--card-border); border-radius: 8px; padding: 14px;">
                            <div
                                style="display: flex; justify-content: space-between; font-size: 0.85rem; margin-bottom: 6px;">
                                <span style="color: var(--text-muted);">Subtotal:</span>
                                <strong id="createSubtotalDisplay">₱0.00</strong>
                            </div>
                            <div
                                style="display: flex; justify-content: space-between; align-items: center; font-size: 0.85rem; margin-bottom: 6px;">
                                <span style="color: var(--text-muted);">Overall Discount:</span>
                                <input type="number" name="discount_amount" id="createDiscountInput"
                                    class="form-control form-control-sm" style="width: 110px; text-align: right;"
                                    value="0" min="0" step="0.01"
                                    oninput="calculateQuotationGrandTotal('create')">
                            </div>
                            <div
                                style="display: flex; justify-content: space-between; align-items: center; font-size: 0.85rem; margin-bottom: 8px;">
                                <span style="color: var(--text-muted);">Tax Amount:</span>
                                <input type="number" name="tax_amount" id="createTaxInput"
                                    class="form-control form-control-sm" style="width: 110px; text-align: right;"
                                    value="0" min="0" step="0.01"
                                    oninput="calculateQuotationGrandTotal('create')">
                            </div>
                            <div
                                style="display: flex; justify-content: space-between; font-size: 1.1rem; font-weight: 800; border-top: 2px solid #cbd5e1; padding-top: 8px;">
                                <span>Grand Total:</span>
                                <span style="color: var(--color-primary-blue);" id="createGrandTotalDisplay">₱0.00</span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline"
                        data-close-modal="createQuotationModal">Cancel</button>
                    <button type="submit" class="btn btn-primary" id="saveQuotationBtn">
                        <i class="bi bi-check-lg me-1"></i> Save & Generate Quotation
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- ==================== EDIT / REVISE QUOTATION MODAL ==================== -->
    <div id="editQuotationModal" class="modal-overlay">
        <div class="modal-dialog modal-xl" style="max-width: 1050px;">
            <div class="modal-header" style="background: #fffbeb; border-bottom: 1px solid #fde68a;">
                <h3 class="modal-title" style="color: #92400e; font-weight: 700;">
                    <i class="bi bi-pencil-square me-2 text-warning"></i> Revise Quotation <span id="editQuoteNumberTitle" class="text-primary"></span>
                </h3>
                <button type="button" class="modal-close-btn" data-close-modal="editQuotationModal"><i class="bi bi-x-lg"></i></button>
            </div>
            <form id="editQuotationForm" onsubmit="submitEditQuotation(event)">
                @csrf
                @method('PUT')
                <input type="hidden" id="editQuotationId" name="quotation_id">
                <div class="modal-body">
                    <!-- Revision Notice from Admin Alert -->
                    <div id="editRevisionNoticeAlert" style="display: none; background: #fffbeb; border: 1px solid #fde68a; border-radius: 8px; padding: 12px; margin-bottom: 14px; font-size: 0.85rem; color: #92400e;">
                        <div style="font-weight: 700; display: flex; align-items: center; gap: 6px; margin-bottom: 4px;">
                            <i class="bi bi-exclamation-triangle-fill text-warning"></i> Revision Instructions from Admin:
                        </div>
                        <div id="editRevisionNoticeContent" style="white-space: pre-wrap; font-size: 0.85rem; color: #78350f;"></div>
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 14px; margin-bottom: 14px;">
                        <div class="form-group">
                            <label class="form-label" for="editQuoteCustomerSelect">Select Registered Customer</label>
                            <select name="customer_id" id="editQuoteCustomerSelect" class="form-select" onchange="onCustomerSelectChange(this, 'edit')">
                                <option value="">-- Walk-in / Custom Customer --</option>
                                @foreach ($customers as $c)
                                    <option value="{{ $c->id }}" data-contact="{{ $c->contact_number }}" data-address="{{ $c->address }}">
                                        {{ $c->name }} {{ $c->business_name ? "({$c->business_name})" : '' }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="editQuoteCustomerName">Customer / Business Name</label>
                            <input type="text" name="customer_name" id="editQuoteCustomerName" class="form-control" placeholder="Enter name if walk-in">
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="editQuoteCustomerContact">Contact Number</label>
                            <input type="text" name="customer_contact" id="editQuoteCustomerContact" class="form-control" placeholder="Mobile / Phone">
                        </div>
                    </div>

                    <div style="display: grid; grid-template-columns: 2fr 1fr 1fr 1fr; gap: 14px; margin-bottom: 16px;">
                        <div class="form-group">
                            <label class="form-label" for="editQuoteCustomerAddress">Delivery / Billing Address</label>
                            <input type="text" name="customer_address" id="editQuoteCustomerAddress" class="form-control" placeholder="Customer address">
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="editQuoteDate">Quotation Date <span class="text-danger">*</span></label>
                            <input type="date" name="quotation_date" id="editQuoteDate" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="editQuoteValidUntil">Valid Until</label>
                            <input type="date" name="valid_until" id="editQuoteValidUntil" class="form-control">
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="editQuoteStatus">Status</label>
                            <select name="status" id="editQuoteStatus" class="form-select">
                                <option value="sent">Sent (Submit for Admin Approval)</option>
                                <option value="draft">Draft (Work in Progress)</option>
                            </select>
                        </div>
                    </div>

                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                        <span style="font-weight: 700; color: var(--color-deep-navy); font-size: 0.9rem;">
                            <i class="bi bi-box-seam me-1 text-primary"></i> Quoted Line Items (Add / Edit / Remove Items)
                        </span>
                        <button type="button" class="btn btn-sm btn-outline" onclick="addQuotationRow('edit')">
                            <i class="bi bi-plus-circle me-1"></i> Add Item Line
                        </button>
                    </div>

                    <div class="table-responsive" style="border: 1px solid var(--card-border); border-radius: 8px; margin-bottom: 16px;">
                        <table class="table mb-0" style="width: 100%;">
                            <thead style="background: var(--color-mist-blue); font-size: 0.8rem; text-transform: uppercase;">
                                <tr>
                                    <th style="padding: 10px; width: 35%;">SKU / Product Description</th>
                                    <th style="padding: 10px; width: 15%;">Unit</th>
                                    <th style="padding: 10px; width: 12%;">Qty</th>
                                    <th style="padding: 10px; width: 15%;">Unit Selling Price</th>
                                    <th style="padding: 10px; width: 10%;">Discount</th>
                                    <th style="padding: 10px; width: 13%;">Subtotal</th>
                                    <th style="padding: 10px; width: 5%; text-align: center;">Action</th>
                                </tr>
                            </thead>
                            <tbody id="editQuotationRowsContainer">
                                <!-- Populated dynamically -->
                            </tbody>
                        </table>
                    </div>

                    <div style="display: grid; grid-template-columns: 1.2fr 0.8fr; gap: 16px;">
                        <div class="form-group">
                            <label class="form-label" for="editQuoteRemarks">Terms & Remarks / Notes</label>
                            <textarea name="remarks" id="editQuoteRemarks" class="form-control" rows="3"
                                placeholder="Terms, revision details, or notes..."></textarea>
                        </div>
                        <div style="background: var(--color-mist-blue); border: 1px solid var(--card-border); border-radius: 8px; padding: 14px;">
                            <div style="display: flex; justify-content: space-between; font-size: 0.85rem; margin-bottom: 6px;">
                                <span style="color: var(--text-muted);">Subtotal:</span>
                                <strong id="editSubtotalDisplay">₱0.00</strong>
                            </div>
                            <div style="display: flex; justify-content: space-between; align-items: center; font-size: 0.85rem; margin-bottom: 6px;">
                                <span style="color: var(--text-muted);">Overall Discount:</span>
                                <input type="number" name="discount_amount" id="editDiscountInput"
                                    class="form-control form-control-sm" style="width: 110px; text-align: right;"
                                    value="0" min="0" step="0.01" oninput="calculateQuotationGrandTotal('edit')">
                            </div>
                            <div style="display: flex; justify-content: space-between; align-items: center; font-size: 0.85rem; margin-bottom: 8px;">
                                <span style="color: var(--text-muted);">Tax Amount:</span>
                                <input type="number" name="tax_amount" id="editTaxInput"
                                    class="form-control form-control-sm" style="width: 110px; text-align: right;"
                                    value="0" min="0" step="0.01" oninput="calculateQuotationGrandTotal('edit')">
                            </div>
                            <div style="display: flex; justify-content: space-between; font-size: 1.1rem; font-weight: 800; border-top: 2px solid #cbd5e1; padding-top: 8px;">
                                <span>Grand Total:</span>
                                <span style="color: var(--color-primary-blue);" id="editGrandTotalDisplay">₱0.00</span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline" data-close-modal="editQuotationModal">Cancel</button>
                    <button type="submit" class="btn btn-warning" id="saveEditQuotationBtn">
                        <i class="bi bi-save me-1"></i> Save Revised Quotation
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- VIEW QUOTATION MODAL -->
    <div id="viewQuotationModal" class="modal-overlay">
        <div class="modal-dialog modal-lg" style="max-width: 800px;">
            <div class="modal-header">
                <h3 class="modal-title" id="viewQuoteTitle">Quotation Details</h3>
                <button type="button" class="modal-close-btn" data-close-modal="viewQuotationModal"><i
                        class="bi bi-x-lg"></i></button>
            </div>
            <div class="modal-body" id="viewQuoteBody">
                <div style="text-align: center; padding: 40px;">
                    <div class="spinner-border text-primary" role="status"></div>
                </div>
            </div>
            <div class="modal-footer" id="viewQuoteFooter">
                <button type="button" class="btn btn-outline" data-close-modal="viewQuotationModal">Close</button>
            </div>
        </div>
    </div>

    <!-- CONVERT TO SALE MODAL -->
    <div id="convertSaleModal" class="modal-overlay">
        <div class="modal-dialog modal-lg" style="max-width: 800px;">
            <div class="modal-header">
                <h3 class="modal-title"><i class="bi bi-cart-check me-2 text-success"></i> Confirm & Convert Order to Sale</h3>
                <button type="button" class="modal-close-btn" data-close-modal="convertSaleModal"><i
                        class="bi bi-x-lg"></i></button>
            </div>
            <form id="convertSaleForm" onsubmit="submitConvertToSale(event)">
                @csrf
                <input type="hidden" id="convertQuotationId">
                <div class="modal-body">
                    <p style="margin-bottom: 14px; font-size: 0.9rem;">
                        Confirming and converting Order <strong id="convertQuotationNumberText" class="text-primary"></strong> (<span id="convertQuotationAmountText"
                            style="color: #16a34a; font-weight: 800;"></span>) into a finalized Sale and deducting inventory.
                    </p>

                    <!-- Stock Availability & Projected Balance Preview Table -->
                    <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 12px; margin-bottom: 16px;">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                            <span style="font-weight: 700; font-size: 0.85rem; color: var(--color-deep-navy);">
                                <i class="bi bi-boxes text-primary me-1"></i> Stock Availability & Projected Inventory Balance
                            </span>
                            <span style="font-size: 0.76rem; color: var(--text-light);">Auto-checked before deduction</span>
                        </div>
                        <div class="table-responsive" style="max-height: 200px; overflow-y: auto;">
                            <table class="table table-sm mb-0" style="font-size: 0.82rem; width: 100%;">
                                <thead style="background: #f1f5f9; position: sticky; top: 0;">
                                    <tr>
                                        <th style="padding: 6px 8px;">Product</th>
                                        <th style="padding: 6px 8px; text-align: center;">Order Qty</th>
                                        <th style="padding: 6px 8px; text-align: center;">Current Stock</th>
                                        <th style="padding: 6px 8px; text-align: center;">Projected Stock</th>
                                        <th style="padding: 6px 8px; text-align: center;">Status</th>
                                    </tr>
                                </thead>
                                <tbody id="convertStockPreviewBody">
                                    <tr><td colspan="5" class="text-center py-3"><div class="spinner-border spinner-border-sm text-primary"></div> Checking stock...</td></tr>
                                </tbody>
                            </table>
                        </div>
                        <div id="convertDeficitWarning" style="display: none; margin-top: 10px; background: #fef2f2; border: 1px solid #fecaca; color: #b91c1c; padding: 10px 12px; border-radius: 6px; font-size: 0.8rem; line-height: 1.4;">
                            <i class="bi bi-exclamation-triangle-fill me-1"></i>
                            <strong>Reorder Alert:</strong> One or more products have insufficient on-hand stock and will reach a <strong>negative balance</strong> upon confirmation. The order will proceed and negative stock (e.g. -1 pcs) will be recorded so you can immediately order replenishment.
                        </div>
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 14px; margin-bottom: 12px;">
                        <div class="form-group">
                            <label class="form-label" for="convertWh">Fulfilling Warehouse <span
                                    class="text-danger">*</span></label>
                            <select name="warehouse_id" id="convertWh" class="form-select" required>
                                @foreach ($warehouses as $w)
                                    <option value="{{ $w->id }}">{{ $w->name }} ({{ $w->code }})</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="convertPaymentMethod">Payment Method <span
                                    class="text-danger">*</span></label>
                            <select name="payment_method" id="convertPaymentMethod" class="form-select"
                                onchange="toggleConvertPaymentFields(this.value)" required>
                                <option value="cash">Cash</option>
                                <option value="gcash">GCash</option>
                                <option value="bank_transfer">Bank Transfer</option>
                                <option value="credit">Credit / Charge to AR</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-group mb-3">
                        <label class="form-label" for="convertAmountPaid">Amount Tendered / Paid <span class="text-danger">*</span></label>
                        <input type="number" name="amount_paid" id="convertAmountPaid" class="form-control"
                            step="0.01" min="0" required>
                    </div>

                    <div class="form-group mb-3" id="convertRefNumGroup" style="display: none;">
                        <label class="form-label" for="convertRef">Reference Number</label>
                        <input type="text" name="reference_number" id="convertRef" class="form-control"
                            placeholder="GCash / Bank Ref #">
                    </div>

                    <div class="form-group mb-3" id="convertCreditTermsGroup" style="display: none;">
                        <label class="form-label">Payment Terms / Due Date</label>
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                            <input type="text" name="payment_terms" class="form-control" placeholder="e.g. 30 Days">
                            <input type="date" name="due_date" class="form-control"
                                value="{{ date('Y-m-d', strtotime('+30 days')) }}">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline" data-close-modal="convertSaleModal">Cancel</button>
                    <button type="submit" class="btn btn-success" id="convertSubmitBtn">
                        <i class="bi bi-check-lg me-1"></i> Confirm Order & Deduct Stock
                    </button>
                </div>
            </form>
        </div>
    </div>

    @push('scripts')
        <script>
            const productsData = @json($products);
            let createRowIdx = 0;
            let editRowIdx = 0;

            function openCreateQuotationModal() {
                document.getElementById('createQuotationForm').reset();
                document.getElementById('createQuotationRowsContainer').innerHTML = '';
                createRowIdx = 0;
                addQuotationRow('create');
                calculateQuotationGrandTotal('create');
                openModal('createQuotationModal');
            }

            function onCustomerSelectChange(select, prefix = 'create') {
                const opt = select.options[select.selectedIndex];
                const nameId = prefix === 'create' ? 'quoteCustomerName' : 'editQuoteCustomerName';
                const contactId = prefix === 'create' ? 'quoteCustomerContact' : 'editQuoteCustomerContact';
                const addressId = prefix === 'create' ? 'quoteCustomerAddress' : 'editQuoteCustomerAddress';

                if (select.value) {
                    document.getElementById(nameId).value = opt.text.trim();
                    document.getElementById(contactId).value = opt.getAttribute('data-contact') || '';
                    document.getElementById(addressId).value = opt.getAttribute('data-address') || '';
                }
            }

            function addQuotationRow(prefix = 'create', existingItem = null) {
                const idx = (prefix === 'create') ? createRowIdx++ : editRowIdx++;
                const containerId = (prefix === 'create') ? 'createQuotationRowsContainer' : 'editQuotationRowsContainer';
                const container = document.getElementById(containerId);
                if (!container) return;

                const tr = document.createElement('tr');
                tr.id = `${prefix}_qrow_${idx}`;
                tr.innerHTML = `
                    <td style="padding: 8px 10px;">
                        <select class="form-select form-select-sm" id="${prefix}_p_select_${idx}" onchange="onProductSelect('${prefix}', ${idx}, this)" required>
                            <option value="">-- Choose Product SKU --</option>
                            ${productsData.map(p => `<option value="${p.id}" data-sku="${p.sku}" data-name="${p.name}" data-srp="${p.default_srp}" data-baseunit="${p.base_unit}">${p.sku} - ${p.name}</option>`).join('')}
                        </select>
                        <input type="hidden" name="items[${idx}][product_id]" id="${prefix}_p_id_${idx}">
                        <input type="hidden" name="items[${idx}][sku]" id="${prefix}_p_sku_${idx}">
                        <input type="hidden" name="items[${idx}][description]" id="${prefix}_p_desc_${idx}">
                        <input type="hidden" name="items[${idx}][conversion_factor]" id="${prefix}_p_conv_${idx}" value="1">
                    </td>
                    <td style="padding: 8px 10px;">
                        <select name="items[${idx}][unit_name]" id="${prefix}_p_unit_${idx}" class="form-select form-select-sm" onchange="onUnitChange('${prefix}', ${idx}, this)" required>
                            <option value="Base Unit">Base Unit</option>
                        </select>
                    </td>
                    <td style="padding: 8px 10px;">
                        <input type="number" name="items[${idx}][quantity]" id="${prefix}_p_qty_${idx}" class="form-control form-control-sm text-center" value="1" min="0.001" step="any" oninput="calculateRowTotal('${prefix}', ${idx})" required>
                    </td>
                    <td style="padding: 8px 10px;">
                        <input type="number" name="items[${idx}][unit_price]" id="${prefix}_p_price_${idx}" class="form-control form-control-sm text-end" value="0.00" min="0" step="0.01" oninput="calculateRowTotal('${prefix}', ${idx})" required>
                    </td>
                    <td style="padding: 8px 10px;">
                        <input type="number" name="items[${idx}][discount]" id="${prefix}_p_disc_${idx}" class="form-control form-control-sm text-end" value="0" min="0" step="0.01" oninput="calculateRowTotal('${prefix}', ${idx})">
                    </td>
                    <td style="padding: 8px 10px;">
                        <strong class="d-block text-end" id="${prefix}_p_subtotal_${idx}" data-val="0">₱0.00</strong>
                    </td>
                    <td style="padding: 8px 10px; text-align: center;">
                        <button type="button" class="btn btn-sm btn-outline-danger" onclick="removeQuotationRow('${prefix}', ${idx})" title="Remove Item">
                            <i class="bi bi-trash"></i>
                        </button>
                    </td>
                `;
                container.appendChild(tr);

                if (existingItem) {
                    const sel = document.getElementById(`${prefix}_p_select_${idx}`);
                    sel.value = existingItem.product_id;
                    onProductSelect(prefix, idx, sel);

                    document.getElementById(`${prefix}_p_unit_${idx}`).value = existingItem.unit_name;
                    document.getElementById(`${prefix}_p_conv_${idx}`).value = existingItem.conversion_factor || 1;
                    document.getElementById(`${prefix}_p_qty_${idx}`).value = existingItem.quantity;
                    document.getElementById(`${prefix}_p_price_${idx}`).value = parseFloat(existingItem.unit_price).toFixed(2);
                    document.getElementById(`${prefix}_p_disc_${idx}`).value = parseFloat(existingItem.discount || 0).toFixed(2);
                    calculateRowTotal(prefix, idx);
                }
            }

            function removeQuotationRow(prefix, idx) {
                document.getElementById(`${prefix}_qrow_${idx}`)?.remove();
                calculateQuotationGrandTotal(prefix);
            }

            function onProductSelect(prefix, idx, select) {
                const prodId = select.value;
                if (!prodId) return;
                const prod = productsData.find(p => p.id == prodId);
                if (!prod) return;

                document.getElementById(`${prefix}_p_id_${idx}`).value = prod.id;
                document.getElementById(`${prefix}_p_sku_${idx}`).value = prod.sku;
                document.getElementById(`${prefix}_p_desc_${idx}`).value = prod.name;
                document.getElementById(`${prefix}_p_price_${idx}`).value = parseFloat(prod.default_srp).toFixed(2);
                document.getElementById(`${prefix}_p_conv_${idx}`).value = 1;

                const unitSelect = document.getElementById(`${prefix}_p_unit_${idx}`);
                unitSelect.innerHTML = `<option value="${prod.base_unit}" data-factor="1" data-srp="${prod.default_srp}">${prod.base_unit} (Base)</option>`;

                if (prod.units && prod.units.length > 0) {
                    prod.units.forEach(u => {
                        unitSelect.innerHTML += `<option value="${u.unit_name}" data-factor="${u.conversion_factor}" data-srp="${u.srp}">${u.unit_name} (x${u.conversion_factor})</option>`;
                    });
                }

                calculateRowTotal(prefix, idx);
            }

            function onUnitChange(prefix, idx, select) {
                const opt = select.options[select.selectedIndex];
                const factor = opt.getAttribute('data-factor') || 1;
                const srp = opt.getAttribute('data-srp');

                document.getElementById(`${prefix}_p_conv_${idx}`).value = factor;
                if (srp && parseFloat(srp) > 0) {
                    document.getElementById(`${prefix}_p_price_${idx}`).value = parseFloat(srp).toFixed(2);
                }
                calculateRowTotal(prefix, idx);
            }

            function calculateRowTotal(prefix, idx) {
                const qty = parseFloat(document.getElementById(`${prefix}_p_qty_${idx}`)?.value || 0);
                const price = parseFloat(document.getElementById(`${prefix}_p_price_${idx}`)?.value || 0);
                const disc = parseFloat(document.getElementById(`${prefix}_p_disc_${idx}`)?.value || 0);

                const subtotal = Math.max(0, (qty * price) - disc);
                const subtotalDisplay = document.getElementById(`${prefix}_p_subtotal_${idx}`);
                if (subtotalDisplay) {
                    subtotalDisplay.innerText = '₱' + subtotal.toLocaleString('en-US', {
                        minimumFractionDigits: 2,
                        maximumFractionDigits: 2
                    });
                    subtotalDisplay.setAttribute('data-val', subtotal);
                }

                calculateQuotationGrandTotal(prefix);
            }

            function calculateQuotationGrandTotal(prefix = 'create') {
                let subtotal = 0;
                document.querySelectorAll(`[id^="${prefix}_p_subtotal_"]`).forEach(el => {
                    subtotal += parseFloat(el.getAttribute('data-val') || 0);
                });

                const subDisplayId = prefix === 'create' ? 'createSubtotalDisplay' : 'editSubtotalDisplay';
                const discInputId = prefix === 'create' ? 'createDiscountInput' : 'editDiscountInput';
                const taxInputId = prefix === 'create' ? 'createTaxInput' : 'editTaxInput';
                const grandDisplayId = prefix === 'create' ? 'createGrandTotalDisplay' : 'editGrandTotalDisplay';

                const subDisplay = document.getElementById(subDisplayId);
                if (subDisplay) {
                    subDisplay.innerText = '₱' + subtotal.toLocaleString('en-US', {
                        minimumFractionDigits: 2,
                        maximumFractionDigits: 2
                    });
                }

                const overallDiscount = parseFloat(document.getElementById(discInputId)?.value || 0);
                const overallTax = parseFloat(document.getElementById(taxInputId)?.value || 0);
                const grandTotal = Math.max(0, subtotal - overallDiscount + overallTax);

                const grandDisplay = document.getElementById(grandDisplayId);
                if (grandDisplay) {
                    grandDisplay.innerText = '₱' + grandTotal.toLocaleString('en-US', {
                        minimumFractionDigits: 2,
                        maximumFractionDigits: 2
                    });
                }
            }

            // Create Quotation Submit
            async function submitQuotation(e) {
                e.preventDefault();
                const btn = document.getElementById('saveQuotationBtn');
                btn.disabled = true;
                btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Saving...';

                const form = document.getElementById('createQuotationForm');
                const formData = new FormData(form);

                try {
                    const res = await fetch("{{ route('quotations.store') }}", {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                            'Accept': 'application/json'
                        },
                        body: formData
                    });
                    const data = await res.json();
                    if (data.success) {
                        closeModal('createQuotationModal');
                        showToast('success', 'Quotation Saved', data.message);
                        setTimeout(() => location.reload(), 600);
                    } else {
                        showToast('error', 'Error', data.message || 'Error generating quotation.');
                        btn.disabled = false;
                        btn.innerHTML = '<i class="bi bi-check-lg me-1"></i> Save & Generate Quotation';
                    }
                } catch (err) {
                    showToast('error', 'Error', err.message);
                    btn.disabled = false;
                    btn.innerHTML = '<i class="bi bi-check-lg me-1"></i> Save & Generate Quotation';
                }
            }

            // Open Edit Quotation Modal (Staff Revision)
            async function openEditQuotationModal(id) {
                try {
                    const res = await fetch(`/quotations/${id}`, {
                        headers: { 'Accept': 'application/json' }
                    });
                    const json = await res.json();
                    if (!json.success) {
                        showToast('error', 'Error', 'Failed to load quotation details.');
                        return;
                    }

                    const q = json.quotation;
                    document.getElementById('editQuotationId').value = q.id;
                    document.getElementById('editQuoteNumberTitle').innerText = `#${q.quotation_number}`;
                    document.getElementById('editQuoteCustomerSelect').value = q.customer_id || '';
                    document.getElementById('editQuoteCustomerName').value = q.customer_name || '';
                    document.getElementById('editQuoteCustomerContact').value = q.customer_contact || q.customer_phone || '';
                    document.getElementById('editQuoteCustomerAddress').value = q.customer_address || '';
                    document.getElementById('editQuoteDate').value = q.quotation_date ? q.quotation_date.substring(0, 10) : '';
                    document.getElementById('editQuoteValidUntil').value = q.valid_until ? q.valid_until.substring(0, 10) : '';
                    document.getElementById('editQuoteStatus').value = (q.status === 'returned') ? 'sent' : q.status;
                    document.getElementById('editQuoteRemarks').value = q.remarks || '';
                    document.getElementById('editDiscountInput').value = parseFloat(q.discount_amount || 0).toFixed(2);
                    document.getElementById('editTaxInput').value = parseFloat(q.tax_amount || 0).toFixed(2);

                    // If returned for revision, show notice
                    const revAlert = document.getElementById('editRevisionNoticeAlert');
                    const revContent = document.getElementById('editRevisionNoticeContent');
                    if (q.remarks && q.remarks.includes('[RETURNED FOR REVISION')) {
                        revAlert.style.display = 'block';
                        revContent.innerText = q.remarks;
                    } else {
                        revAlert.style.display = 'none';
                    }

                    // Populate line items
                    document.getElementById('editQuotationRowsContainer').innerHTML = '';
                    editRowIdx = 0;
                    if (q.items && q.items.length > 0) {
                        q.items.forEach(item => {
                            addQuotationRow('edit', item);
                        });
                    } else {
                        addQuotationRow('edit');
                    }

                    calculateQuotationGrandTotal('edit');
                    openModal('editQuotationModal');
                } catch (err) {
                    showToast('error', 'Error', err.message);
                }
            }

            // Submit Edit Quotation (Staff Revision)
            async function submitEditQuotation(e) {
                e.preventDefault();
                const id = document.getElementById('editQuotationId').value;
                const btn = document.getElementById('saveEditQuotationBtn');
                btn.disabled = true;
                btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Saving Revision...';

                const form = document.getElementById('editQuotationForm');
                const formData = new FormData(form);

                try {
                    const res = await fetch(`/quotations/${id}`, {
                        method: 'POST', // with _method: PUT inside form
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                            'Accept': 'application/json'
                        },
                        body: formData
                    });
                    const data = await res.json();
                    if (data.success) {
                        closeModal('editQuotationModal');
                        showToast('success', 'Quotation Revised', data.message || 'Quotation updated successfully!');
                        setTimeout(() => location.reload(), 600);
                    } else {
                        showToast('error', 'Error', data.message || 'Error updating quotation.');
                        btn.disabled = false;
                        btn.innerHTML = '<i class="bi bi-save me-1"></i> Save Revised Quotation';
                    }
                } catch (err) {
                    showToast('error', 'Error', err.message);
                    btn.disabled = false;
                    btn.innerHTML = '<i class="bi bi-save me-1"></i> Save Revised Quotation';
                }
            }

            async function viewQuotationDetails(id) {
                openModal('viewQuotationModal');
                document.getElementById('viewQuoteBody').innerHTML =
                    '<div style="text-align: center; padding: 40px;"><div class="spinner-border text-primary" role="status"></div></div>';

                try {
                    const res = await fetch(`/quotations/${id}`, {
                        headers: {
                            'Accept': 'application/json'
                        }
                    });
                    const json = await res.json();
                    const q = json.quotation;
                    const items = json.items_with_stock || q.items;

                    let hasDeficit = false;
                    let rowsHtml = items.map(item => {
                        const avail = parseFloat(item.available_stock || 0);
                        const ordered = parseFloat(item.quantity || 0);
                        const proj = parseFloat(item.projected_stock !== undefined ? item.projected_stock : (avail - ordered));
                        const baseUnit = item.base_unit || item.unit_name;
                        
                        let stockBadge = '';
                        if (proj < 0) {
                            hasDeficit = true;
                            stockBadge = `<span class="badge badge-danger" style="background:#dc2626; color:#fff; font-weight:700; font-size:0.75rem;">
                                ${proj.toFixed(1)} ${baseUnit} (Need Reorder)
                            </span>`;
                        } else if (proj <= 5) {
                            stockBadge = `<span class="badge badge-warning" style="font-size:0.75rem;">
                                ${proj.toFixed(1)} ${baseUnit} (Low)
                            </span>`;
                        } else {
                            stockBadge = `<span class="badge badge-success" style="font-size:0.75rem;">
                                +${proj.toFixed(1)} ${baseUnit} (In Stock)
                            </span>`;
                        }

                        return `
                        <tr>
                            <td style="padding: 8px 10px;"><strong>${item.sku}</strong></td>
                            <td style="padding: 8px 10px;">
                                <div>${item.description}</div>
                                <div style="font-size: 0.74rem; color: var(--text-light);">Avail on Hand: <strong>${avail.toFixed(1)} ${baseUnit}</strong></div>
                            </td>
                            <td style="padding: 8px 10px; text-align: center; font-weight: 700;">${ordered} ${item.unit_name}</td>
                            <td style="padding: 8px 10px; text-align: center;">${stockBadge}</td>
                            <td style="padding: 8px 10px; text-align: right;">₱${parseFloat(item.unit_price).toFixed(2)}</td>
                            <td style="padding: 8px 10px; text-align: right; font-weight: 700;">₱${parseFloat(item.total).toFixed(2)}</td>
                        </tr>
                        `;
                    }).join('');

                    const customerName = (q.customer_display_name && q.customer_display_name !== 'undefined')
                        ? q.customer_display_name
                        : ((q.customer && q.customer.name)
                            ? (q.customer.name + (q.customer.business_name ? ` (${q.customer.business_name})` : ''))
                            : (q.customer_name && q.customer_name !== 'undefined' ? q.customer_name : 'Walk-in Customer'));

                    const formatModalDate = function(val) {
                        if (!val) return 'N/A';
                        if (typeof val === 'string' && val.length >= 10) {
                            const datePart = val.substring(0, 10);
                            const parts = datePart.split('-');
                            if (parts.length === 3) {
                                const d = new Date(parts[0], parts[1] - 1, parts[2]);
                                if (!isNaN(d.getTime())) {
                                    return d.toLocaleDateString('en-US', { month: 'short', day: '2-digit', year: 'numeric' });
                                }
                            }
                        }
                        const d = new Date(val);
                        return !isNaN(d.getTime()) ? d.toLocaleDateString('en-US', { month: 'short', day: '2-digit', year: 'numeric' }) : val;
                    };

                    const formattedOrderDate = q.formatted_quotation_date || formatModalDate(q.quotation_date);
                    const formattedValidUntil = q.formatted_valid_until || (q.valid_until ? formatModalDate(q.valid_until) : 'N/A');

                    document.getElementById('viewQuoteTitle').innerText = `Customer Order #${q.quotation_number}`;
                    document.getElementById('viewQuoteBody').innerHTML = `
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 16px;">
                            <div>
                                <div style="font-size: 0.78rem; color: var(--text-muted);">Customer:</div>
                                <strong style="font-size: 1rem; color: var(--color-deep-navy);">${customerName}</strong>
                                ${q.customer_contact ? `<div style="font-size: 0.8rem; color: var(--text-light); margin-top: 2px;"><i class="bi bi-telephone"></i> ${q.customer_contact}</div>` : ''}
                                ${q.customer_address ? `<div style="font-size: 0.8rem; color: var(--text-light); margin-top: 2px;"><i class="bi bi-geo-alt"></i> ${q.customer_address}</div>` : ''}
                            </div>
                            <div style="text-align: right;">
                                <div style="font-size: 0.78rem; color: var(--text-muted);">Order Date:</div>
                                <strong style="font-size: 0.95rem; color: var(--color-deep-navy);">${formattedOrderDate}</strong>
                                <div style="font-size: 0.8rem; color: var(--text-light); margin-top: 2px;">Valid Until: <strong>${formattedValidUntil}</strong></div>
                                <div style="margin-top: 4px;"><span class="badge badge-primary">${q.status.toUpperCase()}</span></div>
                            </div>
                        </div>

                        ${hasDeficit ? `
                        <div style="background: #fef2f2; border: 1px solid #fecaca; color: #b91c1c; padding: 10px 14px; border-radius: 6px; font-size: 0.82rem; margin-bottom: 14px;">
                            <i class="bi bi-exclamation-octagon-fill me-1"></i>
                            <strong>Stock Notice:</strong> One or more items in this order exceed current warehouse stock. Confirming/converting will record negative stock balance (e.g. -1 pcs) to flag immediate replenishment.
                        </div>
                        ` : ''}

                        <div class="table-responsive" style="border: 1px solid var(--card-border); border-radius: 8px; margin-bottom: 16px;">
                            <table class="table mb-0" style="width: 100%;">
                                <thead style="background: var(--color-mist-blue); font-size: 0.8rem; text-transform: uppercase;">
                                    <tr>
                                        <th style="padding: 8px 10px;">SKU</th>
                                        <th style="padding: 8px 10px;">Description</th>
                                        <th style="padding: 8px 10px; text-align: center;">Ordered Qty</th>
                                        <th style="padding: 8px 10px; text-align: center;">Projected Stock</th>
                                        <th style="padding: 8px 10px; text-align: right;">Unit Price</th>
                                        <th style="padding: 8px 10px; text-align: right;">Total</th>
                                    </tr>
                                </thead>
                                <tbody>${rowsHtml}</tbody>
                            </table>
                        </div>
                        <div style="display: grid; grid-template-columns: 1.2fr 0.8fr; gap: 16px;">
                            <div>
                                <div style="font-size: 0.78rem; color: var(--text-muted); margin-bottom: 4px;">Remarks / Terms:</div>
                                <p style="font-size: 0.82rem; background: var(--color-mist-blue); padding: 10px; border-radius: 6px; border: 1px solid #e2e8f0; margin: 0;">${q.remarks || 'None specified.'}</p>
                            </div>
                            <div style="background: var(--color-mist-blue); border: 1px solid var(--card-border); border-radius: 8px; padding: 12px; text-align: right;">
                                <div style="display: flex; justify-content: space-between; font-size: 0.85rem; margin-bottom: 4px;"><span>Subtotal:</span><strong>₱${parseFloat(q.subtotal).toFixed(2)}</strong></div>
                                <div style="display: flex; justify-content: space-between; font-size: 0.85rem; margin-bottom: 4px;"><span>Discount:</span><span style="color: #dc2626;">-₱${parseFloat(q.discount_amount).toFixed(2)}</span></div>
                                <div style="display: flex; justify-content: space-between; font-size: 0.85rem; margin-bottom: 6px;"><span>Tax:</span><span>+₱${parseFloat(q.tax_amount).toFixed(2)}</span></div>
                                <div style="display: flex; justify-content: space-between; font-size: 1.05rem; font-weight: 800; border-top: 2px solid #cbd5e1; padding-top: 6px;"><span>Total:</span><span style="color: var(--color-primary-blue);">₱${parseFloat(q.total_amount).toFixed(2)}</span></div>
                            </div>
                        </div>
                    `;

                    let actionBtns = '';
                    if (q.status !== 'converted') {
                        actionBtns += `<button type="button" class="btn btn-warning btn-sm" onclick="closeModal('viewQuotationModal'); openEditQuotationModal(${q.id})"><i class="bi bi-pencil me-1"></i> Revise / Edit</button>`;
                        actionBtns += `<button type="button" class="btn btn-success btn-sm" onclick="closeModal('viewQuotationModal'); openConvertModal(${q.id}, '${q.quotation_number}', ${q.total_amount})"><i class="bi bi-cart-check me-1"></i> Convert to Sale</button>`;
                    }

                    document.getElementById('viewQuoteFooter').innerHTML = `
                        <a href="/quotations/${q.id}/print?with_price=1" target="_blank" class="btn btn-outline btn-sm"><i class="bi bi-printer text-success me-1"></i> Print With Price</a>
                        <a href="/quotations/${q.id}/print?no_price=1" target="_blank" class="btn btn-outline btn-sm"><i class="bi bi-printer text-muted me-1"></i> Print Without Price</a>
                        ${actionBtns}
                        <button type="button" class="btn btn-secondary btn-sm" data-close-modal="viewQuotationModal">Close</button>
                    `;
                } catch (e) {
                    document.getElementById('viewQuoteBody').innerHTML =
                        '<div class="alert alert-danger">Failed to load quotation details.</div>';
                }
            }

            async function updateQuotationStatus(id, status) {
                if (!status) return;

                try {
                    const res = await fetch(`/quotations/${id}/status`, {
                        method: 'PUT',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute(
                                'content'),
                            'Content-Type': 'application/json',
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify({
                            status
                        })
                    });
                    const data = await res.json();
                    if (data.success) {
                        showToast('success', 'Status Updated', data.message);
                        setTimeout(() => location.reload(), 500);
                    } else {
                        showToast('error', 'Error', data.message || 'Error updating status.');
                    }
                } catch (e) {
                    showToast('error', 'Error', e.message);
                }
            }

            async function openConvertModal(id, qNumber, amount) {
                document.getElementById('convertQuotationId').value = id;
                document.getElementById('convertQuotationNumberText').innerText = qNumber;
                document.getElementById('convertQuotationAmountText').innerText = '₱' + parseFloat(amount).toFixed(2);
                document.getElementById('convertAmountPaid').value = parseFloat(amount).toFixed(2);
                
                const tableBody = document.getElementById('convertStockPreviewBody');
                tableBody.innerHTML = `<tr><td colspan="5" class="text-center py-2"><div class="spinner-border spinner-border-sm text-primary"></div> Checking available stock...</td></tr>`;
                document.getElementById('convertDeficitWarning').style.display = 'none';

                openModal('convertSaleModal');

                try {
                    const res = await fetch(`/quotations/${id}`, {
                        headers: { 'Accept': 'application/json' }
                    });
                    const json = await res.json();
                    const items = json.items_with_stock || json.quotation.items;

                    let hasDeficit = false;
                    tableBody.innerHTML = items.map(item => {
                        const avail = parseFloat(item.available_stock || 0);
                        const ordered = parseFloat(item.quantity || 0);
                        const proj = parseFloat(item.projected_stock !== undefined ? item.projected_stock : (avail - ordered));
                        const baseUnit = item.base_unit || item.unit_name;

                        let statusBadge = '';
                        if (proj < 0) {
                            hasDeficit = true;
                            statusBadge = `<span class="badge badge-danger" style="background:#dc2626; color:#fff; font-weight:700;">${proj.toFixed(1)} ${baseUnit} (Need Reorder)</span>`;
                        } else {
                            statusBadge = `<span class="badge badge-success">+${proj.toFixed(1)} ${baseUnit} (OK)</span>`;
                        }

                        return `
                            <tr>
                                <td style="padding: 6px 8px;">
                                    <strong>${item.sku}</strong>
                                    <div style="font-size: 0.74rem; color: var(--text-light);">${item.description}</div>
                                </td>
                                <td style="padding: 6px 8px; text-align: center; font-weight: 700;">${ordered} ${item.unit_name}</td>
                                <td style="padding: 6px 8px; text-align: center; font-weight: 600;">${avail.toFixed(1)} ${baseUnit}</td>
                                <td style="padding: 6px 8px; text-align: center; font-weight: 700; color: ${proj < 0 ? '#dc2626' : '#16a34a'};">
                                    ${proj.toFixed(1)} ${baseUnit}
                                </td>
                                <td style="padding: 6px 8px; text-align: center;">${statusBadge}</td>
                            </tr>
                        `;
                    }).join('');

                    if (hasDeficit) {
                        document.getElementById('convertDeficitWarning').style.display = 'block';
                    }
                } catch (e) {
                    tableBody.innerHTML = `<tr><td colspan="5" class="text-center text-muted py-2">Stock preview unavailable.</td></tr>`;
                }
            }

            function toggleConvertPaymentFields(method) {
                const refGroup = document.getElementById('convertRefNumGroup');
                const termGroup = document.getElementById('convertCreditTermsGroup');
                if (refGroup) refGroup.style.display = (method === 'gcash' || method === 'bank_transfer' || method ===
                    'check') ? 'block' : 'none';
                if (termGroup) termGroup.style.display = (method === 'credit') ? 'block' : 'none';
            }

            async function submitConvertToSale(e) {
                e.preventDefault();
                const id = document.getElementById('convertQuotationId').value;
                const btn = document.getElementById('convertSubmitBtn');
                btn.disabled = true;
                btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Processing Sale...';

                const form = document.getElementById('convertSaleForm');
                const formData = new FormData(form);

                try {
                    const res = await fetch(`/quotations/${id}/convert`, {
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
                        closeModal('convertSaleModal');
                        showToast('success', 'Converted Successfully', data.message);
                        setTimeout(() => location.reload(), 600);
                    } else {
                        showToast('error', 'Conversion Error', data.message || 'Error converting to sale.');
                        btn.disabled = false;
                        btn.innerHTML = '<i class="bi bi-check-lg me-1"></i> Confirm & Deduct Stock';
                    }
                } catch (err) {
                    showToast('error', 'Error', err.message);
                    btn.disabled = false;
                    btn.innerHTML = '<i class="bi bi-check-lg me-1"></i> Confirm & Deduct Stock';
                }
            }
        </script>
    @endpush
@endsection
