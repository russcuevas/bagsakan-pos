<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $withPrice ? 'Quotation' : 'Order_List' }}_{{ $quotation->quotation_number }}</title>
    <style>
        @page {
            size: A4 portrait;
            margin: 12mm 15mm 15mm 15mm;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
        }

        body {
            background-color: #f1f5f9;
            color: #0f172a;
            font-size: 13px;
            line-height: 1.45;
            padding: 24px;
        }

        .no-print {
            max-width: 820px;
            margin: 0 auto 16px auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 8px 16px;
            font-size: 13px;
            font-weight: 600;
            border-radius: 6px;
            cursor: pointer;
            text-decoration: none;
            border: 1px solid #cbd5e1;
            background: #fff;
            color: #334155;
            transition: all 0.2s ease;
        }

        .btn-primary {
            background: #0f172a;
            color: #fff;
            border-color: #0f172a;
        }

        .btn:hover {
            opacity: 0.9;
        }

        .page-container {
            max-width: 820px;
            margin: 0 auto;
            background: #fff;
            padding: 36px 40px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.06);
            border-radius: 8px;
            border: 1px solid #e2e8f0;
        }

        /* Header */
        .header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            border-bottom: 2px solid #0f172a;
            padding-bottom: 16px;
            margin-bottom: 20px;
        }

        .company-name {
            font-size: 22px;
            font-weight: 800;
            letter-spacing: -0.3px;
            color: #0f172a;
            text-transform: uppercase;
        }

        .company-sub {
            font-size: 12px;
            color: #475569;
            margin-top: 2px;
        }

        .doc-title-box {
            text-align: right;
        }

        .doc-title {
            font-size: 18px;
            font-weight: 800;
            color: #0f172a;
            letter-spacing: 0.5px;
            text-transform: uppercase;
        }

        .doc-type-badge {
            display: inline-block;
            font-size: 10.5px;
            font-weight: 700;
            padding: 2px 8px;
            border-radius: 4px;
            background: {{ $withPrice ? '#e0f2fe' : '#f1f5f9' }};
            color: {{ $withPrice ? '#0369a1' : '#475569' }};
            margin-top: 2px;
            text-transform: uppercase;
        }

        .doc-info-table {
            margin-top: 6px;
            font-size: 12px;
            margin-left: auto;
        }

        .doc-info-table td {
            padding: 2px 0 2px 10px;
            text-align: right;
        }

        .doc-info-table td.label {
            color: #64748b;
            font-weight: 600;
        }

        .doc-info-table td.value {
            font-weight: 700;
            color: #0f172a;
        }

        /* Customer & Info Grid */
        .info-grid {
            display: grid;
            grid-template-columns: 1.3fr 1fr;
            gap: 16px;
            margin-bottom: 20px;
            font-size: 12.5px;
        }

        .info-box {
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            padding: 12px 14px;
            background: #f8fafc;
        }

        .info-box-title {
            font-size: 11px;
            font-weight: 700;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 6px;
            border-bottom: 1px solid #e2e8f0;
            padding-bottom: 4px;
        }

        .info-box-name {
            font-size: 14px;
            font-weight: 700;
            color: #0f172a;
            margin-bottom: 3px;
        }

        .info-box-detail {
            color: #475569;
            font-size: 12px;
            line-height: 1.4;
        }

        /* Table */
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }

        .items-table th {
            background: #f1f5f9;
            color: #0f172a;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.4px;
            padding: 8px 10px;
            border: 1px solid #cbd5e1;
            text-align: left;
        }

        .items-table td {
            padding: 8px 10px;
            border: 1px solid #e2e8f0;
            font-size: 12px;
            color: #1e293b;
            vertical-align: middle;
        }

        .items-table tbody tr:nth-child(even) {
            background: #f8fafc;
        }

        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .text-left { text-align: left; }

        /* Summary Section */
        .summary-section {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 20px;
            margin-top: 10px;
        }

        .terms-box {
            flex: 1;
            font-size: 11.5px;
            color: #475569;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            padding: 12px 14px;
            background: #f8fafc;
        }

        .terms-box strong {
            color: #0f172a;
            display: block;
            margin-bottom: 4px;
            text-transform: uppercase;
            font-size: 11px;
            letter-spacing: 0.3px;
        }

        .terms-box p {
            margin-bottom: 3px;
            line-height: 1.4;
        }

        .totals-table {
            width: 290px;
            border-collapse: collapse;
        }

        .totals-table td {
            padding: 5px 8px;
            font-size: 12.5px;
        }

        .totals-table td.label {
            color: #64748b;
            text-align: right;
            font-weight: 600;
        }

        .totals-table td.amount {
            text-align: right;
            font-weight: 700;
            color: #0f172a;
        }

        .totals-table tr.grand-total td {
            border-top: 2px solid #0f172a;
            border-bottom: 2px solid #0f172a;
            padding: 8px 8px;
            font-size: 14px;
            font-weight: 800;
            color: #0f172a;
        }

        /* Signatures */
        .signature-section {
            display: flex;
            justify-content: space-between;
            gap: 60px;
            margin-top: 40px;
            padding-top: 10px;
        }

        .sig-block {
            flex: 1;
            text-align: center;
        }

        .sig-line {
            border-top: 1px solid #94a3b8;
            padding-top: 6px;
            font-size: 12px;
            font-weight: 600;
            color: #0f172a;
        }

        .sig-title {
            font-size: 11px;
            color: #64748b;
            margin-top: 2px;
        }

        @media print {
            body {
                background: #fff;
                padding: 0;
            }

            .no-print {
                display: none !important;
            }

            .page-container {
                box-shadow: none;
                padding: 0;
                max-width: 100%;
                border: none;
                border-radius: 0;
            }

            .items-table th {
                background: #f1f5f9 !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
        }
    </style>
</head>
<body>

    <!-- Print Action Bar -->
    <div class="no-print">
        <div style="display: flex; gap: 8px;">
            @if($withPrice)
                <a href="{{ route('quotations.print', ['quotation' => $quotation->id, 'no_price' => 1]) }}" class="btn">
                    📄 Hide Prices (Quantity / Dispatch Only)
                </a>
            @else
                <a href="{{ route('quotations.print', ['quotation' => $quotation->id, 'with_price' => 1]) }}" class="btn">
                    💵 Show Prices (Full Financial Breakdown)
                </a>
            @endif
        </div>
        <div style="display: flex; gap: 8px;">
            <a href="{{ route(auth()->check() && auth()->user()->isAdmin() ? 'admin.quotations.index' : (auth()->check() && auth()->user()->isPurchasing() ? 'staff.quotations.index' : 'cashier.quotations.index')) }}" class="btn">
                &larr; Back to Order List
            </a>
            <button onclick="window.print()" class="btn btn-primary">
                🖨️ Print Document
            </button>
        </div>
    </div>

    <!-- Quotation Page -->
    <div class="page-container">

        <!-- Header -->
        <div class="header">
            <div>
                <div class="company-name">BAGSAKAN POS & INVENTORY</div>
                <div class="company-sub">Wholesale & Retail Trading Distribution</div>
                <div class="company-sub">Official Order & Quotation Document</div>
            </div>

            <div class="doc-title-box">
                <div class="doc-title">{{ $withPrice ? 'CUSTOMER ORDER / PRICE QUOTATION' : 'CUSTOMER ORDER LIST (DISPATCH SLIP)' }}</div>
                <div class="doc-type-badge">{{ $withPrice ? 'Pricing Copy' : 'Quantity Only / Warehouse Copy' }}</div>
                <table class="doc-info-table">
                    <tr>
                        <td class="label">Reference #:</td>
                        <td class="value">{{ $quotation->quotation_number }}</td>
                    </tr>
                    <tr>
                        <td class="label">Order Date:</td>
                        <td class="value">{{ $quotation->quotation_date ? \Carbon\Carbon::parse($quotation->quotation_date)->format('M d, Y') : date('M d, Y') }}</td>
                    </tr>
                    @if($quotation->valid_until)
                    <tr>
                        <td class="label">Valid Until:</td>
                        <td class="value">{{ \Carbon\Carbon::parse($quotation->valid_until)->format('M d, Y') }}</td>
                    </tr>
                    @endif
                </table>
            </div>
        </div>

        <!-- Info Grid -->
        <div class="info-grid">
            <div class="info-box">
                <div class="info-box-title">Customer Information</div>
                <div class="info-box-name">{{ $quotation->customer_display_name }}</div>
                @if($quotation->customer_contact || $quotation->customer_phone)
                    <div class="info-box-detail"><strong>Contact:</strong> {{ $quotation->customer_contact ?: $quotation->customer_phone }}</div>
                @endif
                @if($quotation->customer_address)
                    <div class="info-box-detail"><strong>Address:</strong> {{ $quotation->customer_address }}</div>
                @endif
            </div>

            <div class="info-box">
                <div class="info-box-title">Order Overview</div>
                <div class="info-box-detail"><strong>Prepared By:</strong> {{ $quotation->preparer->name ?? 'System Staff' }}</div>
                <div class="info-box-detail"><strong>Status:</strong> <span style="text-transform: uppercase; font-weight: 700;">{{ $quotation->status }}</span></div>
                @if($quotation->payment_terms)
                    <div class="info-box-detail"><strong>Terms:</strong> {{ $quotation->payment_terms }}</div>
                @endif
            </div>
        </div>

        <!-- Line Items Table -->
        <table class="items-table">
            <thead>
                <tr>
                    <th style="width: 5%;" class="text-center">#</th>
                    <th style="width: 20%;">SKU / Code</th>
                    <th style="width: {{ $withPrice ? '35%' : '55%' }};">Product Description</th>
                    <th style="width: 15%;" class="text-center">Ordered Qty</th>
                    <th style="width: 10%;" class="text-center">Unit</th>
                    @if($withPrice)
                        <th style="width: 15%;" class="text-right">Unit Price</th>
                        <th style="width: 15%;" class="text-right">Subtotal</th>
                    @endif
                </tr>
            </thead>
            <tbody>
                @forelse($quotation->items as $idx => $item)
                    <tr>
                        <td class="text-center">{{ $idx + 1 }}</td>
                        <td><strong>{{ $item->sku }}</strong></td>
                        <td>
                            <div style="font-weight: 600;">{{ $item->description }}</div>
                            @if($item->notes)
                                <div style="font-size: 11px; color: #64748b; margin-top: 2px;">{{ $item->notes }}</div>
                            @endif
                        </td>
                        <td class="text-center" style="font-weight: 700;">
                            {{ number_format((float)$item->quantity, 2) }}
                        </td>
                        <td class="text-center">{{ $item->unit_name }}</td>
                        @if($withPrice)
                            <td class="text-right">₱{{ number_format((float)$item->unit_price, 2) }}</td>
                            <td class="text-right" style="font-weight: 700;">₱{{ number_format((float)$item->total, 2) }}</td>
                        @endif
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ $withPrice ? '7' : '5' }}" class="text-center" style="padding: 20px; color: #94a3b8;">
                            No items found in this order list.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        <!-- Summary & Terms -->
        <div class="summary-section">
            <div class="terms-box">
                <strong>Terms & Remarks / Notes:</strong>
                @if($quotation->remarks || $quotation->notes)
                    <p style="white-space: pre-line;">{{ $quotation->remarks ?: $quotation->notes }}</p>
                @else
                    <p>1. Prices and availability are verified upon customer confirmation.</p>
                    <p>2. Products are subject to weight and quality inspection during dispatch.</p>
                    <p>3. Standard payment and handling terms apply.</p>
                @endif
            </div>

            @if($withPrice)
                <div>
                    <table class="totals-table">
                        <tr>
                            <td class="label">Subtotal:</td>
                            <td class="amount">₱{{ number_format((float)$quotation->subtotal, 2) }}</td>
                        </tr>
                        @if((float)$quotation->discount_amount > 0)
                        <tr>
                            <td class="label">Discount:</td>
                            <td class="amount" style="color: #dc2626;">-₱{{ number_format((float)$quotation->discount_amount, 2) }}</td>
                        </tr>
                        @endif
                        @if((float)$quotation->tax_amount > 0)
                        <tr>
                            <td class="label">Tax:</td>
                            <td class="amount">+₱{{ number_format((float)$quotation->tax_amount, 2) }}</td>
                        </tr>
                        @endif
                        <tr class="grand-total">
                            <td class="label">Grand Total:</td>
                            <td class="amount">₱{{ number_format((float)$quotation->total_amount, 2) }}</td>
                        </tr>
                    </table>
                </div>
            @else
                <div>
                    <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 6px; padding: 12px 16px; width: 250px; font-size: 12px;">
                        <div style="display: flex; justify-content: space-between; margin-bottom: 4px;">
                            <span style="color: #64748b;">Total Line Items:</span>
                            <strong style="color: #0f172a;">{{ $quotation->items->count() }} item(s)</strong>
                        </div>
                        <div style="display: flex; justify-content: space-between;">
                            <span style="color: #64748b;">Total Quantity:</span>
                            <strong style="color: #0f172a;">{{ number_format((float)$quotation->items->sum('quantity'), 2) }}</strong>
                        </div>
                    </div>
                </div>
            @endif
        </div>

        <!-- Signatures -->
        <div class="signature-section">
            <div class="sig-block">
                <div style="height: 35px;"></div>
                <div class="sig-line">{{ $quotation->preparer->name ?? 'Authorized Representative' }}</div>
                <div class="sig-title">Prepared & Verified By</div>
            </div>

            <div class="sig-block">
                <div style="height: 35px;"></div>
                <div class="sig-line">Customer Signature / Date</div>
                <div class="sig-title">Conforme & Acceptance</div>
            </div>
        </div>

    </div>

</body>
</html>
