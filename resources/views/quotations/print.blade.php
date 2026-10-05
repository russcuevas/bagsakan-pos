<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quotation_{{ $quotation->quotation_number }}</title>
    <style>
        @page {
            size: A4 portrait;
            margin: 15mm 15mm 20mm 15mm;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: Arial, Helvetica, sans-serif;
        }

        body {
            background-color: #f1f5f9;
            color: #111827;
            font-size: 13px;
            line-height: 1.4;
            padding: 20px;
        }

        .no-print {
            max-width: 800px;
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
            max-width: 800px;
            margin: 0 auto;
            background: #fff;
            padding: 40px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.06);
            border-radius: 4px;
        }

        /* Header */
        .header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            border-bottom: 2px solid #111827;
            padding-bottom: 16px;
            margin-bottom: 20px;
        }

        .company-name {
            font-size: 20px;
            font-weight: 800;
            letter-spacing: -0.3px;
            color: #0f172a;
            text-transform: uppercase;
        }

        .company-sub {
            font-size: 12px;
            color: #4b5563;
            margin-top: 3px;
        }

        .doc-title-box {
            text-align: right;
        }

        .doc-title {
            font-size: 22px;
            font-weight: 800;
            color: #0f172a;
            letter-spacing: 0.5px;
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
            color: #6b7280;
            font-weight: 600;
        }

        .doc-info-table td.value {
            font-weight: 700;
            color: #111827;
        }

        /* Customer & Info Grid */
        .info-grid {
            display: grid;
            grid-template-columns: 1.2fr 1fr;
            gap: 20px;
            margin-bottom: 22px;
            font-size: 12.5px;
        }

        .info-box {
            border: 1px solid #e5e7eb;
            border-radius: 4px;
            padding: 12px 16px;
            background: #fafafa;
        }

        .info-box-title {
            font-size: 11px;
            font-weight: 700;
            color: #6b7280;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 6px;
            border-bottom: 1px solid #e5e7eb;
            padding-bottom: 4px;
        }

        .info-box-name {
            font-size: 14px;
            font-weight: 700;
            color: #111827;
            margin-bottom: 3px;
        }

        .info-box-detail {
            color: #4b5563;
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
            background: #f3f4f6;
            color: #111827;
            font-size: 11.5px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.4px;
            padding: 9px 12px;
            border: 1px solid #d1d5db;
            text-align: left;
        }

        .items-table td {
            padding: 9px 12px;
            border: 1px solid #e5e7eb;
            font-size: 12.5px;
            color: #1f2937;
            vertical-align: top;
        }

        .items-table tbody tr:nth-child(even) {
            background: #fafafa;
        }

        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .text-left { text-align: left; }

        /* Summary Section */
        .summary-section {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 24px;
            margin-top: 10px;
        }

        .terms-box {
            flex: 1;
            font-size: 11.5px;
            color: #4b5563;
            border: 1px solid #e5e7eb;
            border-radius: 4px;
            padding: 12px 14px;
            background: #fafafa;
        }

        .terms-box strong {
            color: #111827;
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
            width: 280px;
            border-collapse: collapse;
        }

        .totals-table td {
            padding: 6px 8px;
            font-size: 13px;
        }

        .totals-table td.label {
            color: #4b5563;
            text-align: right;
            font-weight: 600;
        }

        .totals-table td.amount {
            text-align: right;
            font-weight: 700;
            color: #111827;
        }

        .totals-table tr.grand-total td {
            border-top: 2px solid #111827;
            border-bottom: 2px solid #111827;
            padding: 8px 8px;
            font-size: 15px;
            font-weight: 800;
            color: #0f172a;
        }

        /* Signatures */
        .signature-section {
            display: flex;
            justify-content: space-between;
            gap: 60px;
            margin-top: 50px;
            padding-top: 10px;
        }

        .sig-block {
            flex: 1;
            text-align: center;
        }

        .sig-line {
            border-top: 1px solid #9ca3af;
            padding-top: 6px;
            font-size: 12px;
            font-weight: 600;
            color: #1f2937;
        }

        .sig-title {
            font-size: 11px;
            color: #6b7280;
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
                border-radius: 0;
            }

            .items-table th {
                background: #f3f4f6 !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
        }
    </style>
</head>
<body>

    <!-- Print Action Bar -->
    <div class="no-print">
        <div>
            @if($withPrice)
                <a href="{{ route('quotations.print', ['quotation' => $quotation->id, 'no_price' => 1]) }}" class="btn">
                    Hide Prices (Quantity Only)
                </a>
            @else
                <a href="{{ route('quotations.print', ['quotation' => $quotation->id, 'with_price' => 1]) }}" class="btn">
                    Show Prices
                </a>
            @endif
        </div>
        <div style="display: flex; gap: 8px;">
            <a href="{{ route('quotations.index') }}" class="btn">
                &larr; Back to Quotations
            </a>
            <button onclick="window.print()" class="btn btn-primary">
                Print Quotation
            </button>
        </div>
    </div>

    <!-- Quotation Page -->
    <div class="page-container">

        <!-- Header -->
        <div class="header">
            <div>
                <div class="company-name">WORTHY ACOSTA TRADING</div>
                <div class="company-sub">Bagsakan Wholesale & Retail Distribution</div>
                <div class="company-sub">TIN: 000-000-000-000 | Contact: 0900-000-0000</div>
            </div>

            <div class="doc-title-box">
                <div class="doc-title">PRICE QUOTATION</div>
                <table class="doc-info-table">
                    <tr>
                        <td class="label">Quotation No:</td>
                        <td class="value">{{ $quotation->quotation_number }}</td>
                    </tr>
                    <tr>
                        <td class="label">Date:</td>
                        <td class="value">{{ $quotation->quotation_date->format('M d, Y') }}</td>
                    </tr>
                    @if($quotation->valid_until)
                    <tr>
                        <td class="label">Valid Until:</td>
                        <td class="value">{{ $quotation->valid_until->format('M d, Y') }}</td>
                    </tr>
                    @endif
                </table>
            </div>
        </div>

        <!-- Info Grid -->
        <div class="info-grid">
            <div class="info-box">
                <div class="info-box-title">Quotation For / Customer</div>
                <div class="info-box-name">{{ $quotation->customer_display_name }}</div>
                @if($quotation->customer_contact || $quotation->customer_phone)
                    <div class="info-box-detail"><strong>Contact:</strong> {{ $quotation->customer_contact ?? $quotation->customer_phone }}</div>
                @endif
                @if($quotation->customer_address)
                    <div class="info-box-detail"><strong>Address:</strong> {{ $quotation->customer_address }}</div>
                @endif
            </div>

            <div class="info-box">
                <div class="info-box-title">Quotation Details</div>
                <div class="info-box-detail"><strong>Prepared By:</strong> {{ $quotation->preparer->name ?? 'Sales Staff' }}</div>
                <div class="info-box-detail"><strong>Status:</strong> <span style="text-transform: uppercase;">{{ $quotation->status }}</span></div>
                @if($quotation->payment_terms)
                    <div class="info-box-detail"><strong>Payment Terms:</strong> {{ $quotation->payment_terms }}</div>
                @endif
            </div>
        </div>

        <!-- Line Items Table -->
        <table class="items-table">
            <thead>
                <tr>
                    <th style="width: 6%;" class="text-center">#</th>
                    <th style="width: 22%;">SKU / Code</th>
                    <th style="width: {{ $withPrice ? '38%' : '52%' }};">Product Description</th>
                    <th style="width: 14%;" class="text-center">Quantity</th>
                    <th style="width: 10%;" class="text-center">Unit</th>
                    @if($withPrice)
                        <th style="width: 14%;" class="text-right">Unit Price</th>
                        <th style="width: 16%;" class="text-right">Total (₱)</th>
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
                                <div style="font-size: 11px; color: #6b7280; margin-top: 2px;">{{ $item->notes }}</div>
                            @endif
                        </td>
                        <td class="text-center" style="font-weight: 600;">{{ (float)$item->quantity }}</td>
                        <td class="text-center">{{ $item->unit_name }}</td>
                        @if($withPrice)
                            <td class="text-right">₱{{ number_format($item->unit_price, 2) }}</td>
                            <td class="text-right" style="font-weight: 700;">₱{{ number_format($item->total, 2) }}</td>
                        @endif
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ $withPrice ? '7' : '5' }}" class="text-center" style="padding: 20px; color: #9ca3af;">
                            No items found in this quotation.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        <!-- Summary & Terms -->
        <div class="summary-section">
            <div class="terms-box">
                <strong>Terms & Notes:</strong>
                @if($quotation->remarks || $quotation->notes)
                    <p>{{ $quotation->remarks ?? $quotation->notes }}</p>
                @else
                    <p>1. Prices are valid until the stated validity date.</p>
                    <p>2. Subject to stock availability upon confirmation.</p>
                    <p>3. Standard payment and delivery terms apply upon approval.</p>
                @endif
            </div>

            @if($withPrice)
                <div>
                    <table class="totals-table">
                        <tr>
                            <td class="label">Subtotal:</td>
                            <td class="amount">₱{{ number_format($quotation->subtotal, 2) }}</td>
                        </tr>
                        @if($quotation->discount_amount > 0)
                        <tr>
                            <td class="label">Discount:</td>
                            <td class="amount" style="color: #dc2626;">-₱{{ number_format($quotation->discount_amount, 2) }}</td>
                        </tr>
                        @endif
                        @if($quotation->tax_amount > 0)
                        <tr>
                            <td class="label">Tax:</td>
                            <td class="amount">+₱{{ number_format($quotation->tax_amount, 2) }}</td>
                        </tr>
                        @endif
                        <tr class="grand-total">
                            <td class="label">Total Amount:</td>
                            <td class="amount">₱{{ number_format($quotation->total_amount, 2) }}</td>
                        </tr>
                    </table>
                </div>
            @endif
        </div>

        <!-- Signatures -->
        <div class="signature-section">
            <div class="sig-block">
                <div style="height: 35px;"></div>
                <div class="sig-line">{{ $quotation->preparer->name ?? 'Authorized Signature' }}</div>
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
