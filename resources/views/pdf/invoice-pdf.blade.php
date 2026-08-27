<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Invoice {{ $invoiceNumber }}</title>
    <style>
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            color: #334155;
            font-size: 13px;
            line-height: 1.5;
            margin: 0;
            padding: 30px;
        }
        .header {
            margin-bottom: 30px;
            border-bottom: 3px solid {{ $brandColor ?: '#0ea5e9' }};
            padding-bottom: 20px;
        }
        .company-name {
            font-size: 24px;
            font-weight: bold;
            color: {{ $brandColor ?: '#0ea5e9' }};
            margin: 0 0 5px 0;
        }
        .invoice-title {
            font-size: 28px;
            font-weight: bold;
            text-transform: uppercase;
            color: #1e293b;
            text-align: right;
        }
        .meta-table {
            width: 100%;
            margin-bottom: 30px;
        }
        .meta-table td {
            vertical-align: top;
        }
        .section-title {
            font-size: 11px;
            font-weight: bold;
            text-transform: uppercase;
            color: #94a3b8;
            letter-spacing: 0.5px;
            margin-bottom: 5px;
        }
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
        }
        .items-table th {
            background-color: {{ $brandColor ?: '#0ea5e9' }};
            color: #ffffff;
            font-size: 11px;
            font-weight: bold;
            text-transform: uppercase;
            padding: 10px 12px;
            text-align: left;
        }
        .items-table td {
            padding: 12px;
            border-bottom: 1px solid #e2e8f0;
        }
        .items-table tr:nth-child(even) {
            background-color: #f8fafc;
        }
        .totals-table {
            width: 320px;
            float: right;
            margin-bottom: 30px;
        }
        .totals-table td {
            padding: 6px 12px;
        }
        .total-row {
            font-size: 16px;
            font-weight: bold;
            color: {{ $brandColor ?: '#0ea5e9' }};
            border-top: 2px solid #e2e8f0;
            border-bottom: 2px solid #e2e8f0;
        }
        .clear {
            clear: both;
        }
        .payment-box {
            background-color: #f8fafc;
            border-left: 4px solid {{ $brandColor ?: '#0ea5e9' }};
            padding: 15px;
            margin-top: 20px;
            border-radius: 4px;
        }
        .footer {
            margin-top: 40px;
            text-align: center;
            font-size: 11px;
            color: #94a3b8;
            border-top: 1px solid #e2e8f0;
            padding-top: 15px;
        }
    </style>
</head>
<body>

    <!-- Header Section -->
    <table class="meta-table">
        <tr>
            <td style="width: 60%;">
                <div class="company-name">{{ $sellerName }}</div>
                @if($sellerWebsite)<div><strong>Web:</strong> {{ $sellerWebsite }}</div>@endif
                @if($sellerEmail)<div><strong>Email:</strong> {{ $sellerEmail }}</div>@endif
                @if($sellerPhone)<div><strong>Tel:</strong> {{ $sellerPhone }}</div>@endif
                @if($sellerRegNo)<div><strong>Reg No:</strong> {{ $sellerRegNo }}</div>@endif
                @if($sellerAddress)<div>{{ $sellerAddress }}</div>@endif
            </td>
            <td style="width: 40%; text-align: right;">
                <div class="invoice-title">INVOICE</div>
                <div style="font-size: 14px; font-weight: bold; color: #475569; margin-top: 5px;">#{{ $invoiceNumber }}</div>
                <div style="margin-top: 10px;"><strong>Date:</strong> {{ $issueDate }}</div>
                @if($dueDate)<div><strong>Due Date:</strong> {{ $dueDate }}</div>@endif
            </td>
        </tr>
    </table>

    <!-- Bill To Section -->
    <table class="meta-table" style="margin-bottom: 25px;">
        <tr>
            <td style="width: 50%;">
                <div class="section-title">Billed To</div>
                <div style="font-size: 14px; font-weight: bold; color: #1e293b;">{{ $clientName ?: 'Client / Company Name' }}</div>
                @if($clientEmail)<div>{{ $clientEmail }}</div>@endif
                @if($clientAddress)<div style="white-space: pre-line;">{{ $clientAddress }}</div>@endif
                @if($clientVatNo)<div><strong>VAT ID:</strong> {{ $clientVatNo }}</div>@endif
            </td>
        </tr>
    </table>

    <!-- Line Items Table -->
    <table class="items-table">
        <thead>
            <tr>
                <th style="width: 55%;">Description</th>
                <th style="width: 15%; text-align: center;">Qty</th>
                <th style="width: 15%; text-align: right;">Unit Price</th>
                <th style="width: 15%; text-align: right;">Amount</th>
            </tr>
        </thead>
        <tbody>
            @foreach($items as $item)
                <tr>
                    <td>{{ $item['description'] }}</td>
                    <td style="text-align: center;">{{ $item['quantity'] }}</td>
                    <td style="text-align: right;">{{ $currencySymbol }}{{ number_format((float)$item['unit_price'], 2) }}</td>
                    <td style="text-align: right; font-weight: bold;">{{ $currencySymbol }}{{ number_format((float)$item['quantity'] * (float)$item['unit_price'], 2) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <!-- Totals Table -->
    <table class="totals-table">
        <tr>
            <td style="color: #64748b;">Subtotal:</td>
            <td style="text-align: right; font-weight: bold;">{{ $currencySymbol }}{{ number_format($subtotal, 2) }}</td>
        </tr>
        @if($discountAmount > 0)
            <tr>
                <td style="color: #10b981;">Discount:</td>
                <td style="text-align: right; font-weight: bold; color: #10b981;">-{{ $currencySymbol }}{{ number_format($discountAmount, 2) }}</td>
            </tr>
        @endif
        @if($taxPercent > 0)
            <tr>
                <td style="color: #64748b;">Tax ({{ $taxPercent }}%):</td>
                <td style="text-align: right; font-weight: bold;">{{ $currencySymbol }}{{ number_format($taxAmount, 2) }}</td>
            </tr>
        @endif
        <tr class="total-row">
            <td>Total Due:</td>
            <td style="text-align: right;">{{ $currencySymbol }}{{ number_format($totalDue, 2) }} {{ $currency }}</td>
        </tr>
    </table>

    <div class="clear"></div>

    <!-- Payment & Bank Info -->
    @if($bankName || $accountNumber || $iban)
        <div class="payment-box">
            <div class="section-title" style="color: {{ $brandColor ?: '#0ea5e9' }}; font-size: 12px; margin-bottom: 8px;">Payment Instructions</div>
            <table style="width: 100%; font-size: 12px;">
                <tr>
                    @if($bankName)<td style="padding-right: 20px;"><strong>Bank Name:</strong> {{ $bankName }}</td>@endif
                    @if($accountName)<td style="padding-right: 20px;"><strong>Account Name:</strong> {{ $accountName }}</td>@endif
                    @if($accountNumber)<td><strong>Account / Sort Code:</strong> {{ $accountNumber }} {{ $sortCode ? "($sortCode)" : '' }}</td>@endif
                </tr>
                @if($iban || $swift)
                    <tr>
                        @if($iban)<td style="padding-right: 20px; padding-top: 5px;"><strong>IBAN:</strong> {{ $iban }}</td>@endif
                        @if($swift)<td style="padding-top: 5px;"><strong>SWIFT/BIC:</strong> {{ $swift }}</td>@endif
                    </tr>
                @endif
            </table>
        </div>
    @endif

    @if($notes)
        <div style="margin-top: 20px; font-size: 12px; color: #64748b;">
            <strong>Notes:</strong> {{ $notes }}
        </div>
    @endif

    <div class="footer">
        {{ $sellerName }} &bull; {{ $sellerWebsite ?: $sellerEmail }} &bull; Generated via Soft Projects CRM
    </div>

</body>
</html>
