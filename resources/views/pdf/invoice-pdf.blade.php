<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Invoice {{ $invoiceNumber }}</title>
    <style>
        @page {
            margin: 0px;
        }
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            color: #1e293b;
            font-size: 12px;
            line-height: 1.5;
            margin: 0;
            padding: 0;
            background-color: #ffffff;
        }

        .page-container {
            padding: 40px 45px;
            position: relative;
        }

        /* -------------------------------------------------------------
           1. JUMLEE LAYOUT (Vivid Purple Block & Centered Serif Header)
           ------------------------------------------------------------- */
        .layout-jumlee .serif-title {
            font-family: Georgia, 'Times New Roman', serif;
            font-size: 42px;
            letter-spacing: 2px;
            text-align: center;
            text-transform: uppercase;
            margin-bottom: 40px;
            color: #000000;
        }
        .layout-jumlee .info-grid {
            width: 100%;
            margin-bottom: 30px;
        }
        .layout-jumlee .info-grid td {
            vertical-align: top;
        }
        .layout-jumlee .section-label {
            font-size: 11px;
            font-weight: bold;
            text-transform: uppercase;
            color: #000000;
            margin-bottom: 6px;
        }
        .layout-jumlee .table-jumlee {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 25px;
        }
        .layout-jumlee .table-jumlee th {
            background-color: {{ $brandColor ?: '#8b5cf6' }};
            color: #ffffff;
            font-size: 11px;
            font-weight: bold;
            text-transform: uppercase;
            padding: 10px 14px;
            text-align: left;
        }
        .layout-jumlee .table-jumlee td {
            padding: 14px;
            background-color: #f8fafc;
            border-bottom: 2px solid #ffffff;
        }
        .layout-jumlee .total-block-jumlee {
            width: 320px;
            float: right;
            margin-bottom: 40px;
        }
        .layout-jumlee .total-block-jumlee td {
            padding: 8px 14px;
        }
        .layout-jumlee .bg-purple-total {
            background-color: {{ $brandColor ?: '#8b5cf6' }};
            color: #ffffff;
            font-size: 14px;
            font-weight: bold;
        }
        .layout-jumlee .bottom-bar {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            height: 10px;
            background-color: {{ $brandColor ?: '#8b5cf6' }};
        }

        /* -------------------------------------------------------------
           2. BORDEUX LAYOUT (Navy Minimalist Clean Lines)
           ------------------------------------------------------------- */
        .layout-bordeux .navy-title {
            font-size: 38px;
            font-weight: 900;
            color: {{ $brandColor ?: '#0f172a' }};
            text-align: right;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .layout-bordeux .hr-navy {
            border: none;
            border-top: 2px solid {{ $brandColor ?: '#0f172a' }};
            margin: 15px 0 25px 0;
        }
        .layout-bordeux .table-bordeux {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
        }
        .layout-bordeux .table-bordeux th {
            border-top: 2px solid {{ $brandColor ?: '#0f172a' }};
            border-bottom: 2px solid {{ $brandColor ?: '#0f172a' }};
            padding: 10px;
            font-size: 11px;
            font-weight: bold;
            text-transform: uppercase;
            color: #1e293b;
        }
        .layout-bordeux .table-bordeux td {
            border-bottom: 1px solid #cbd5e1;
            padding: 12px 10px;
            border-right: 1px solid #cbd5e1;
        }
        .layout-bordeux .table-bordeux td:last-child {
            border-right: none;
        }

        /* -------------------------------------------------------------
           3. ELECTRO LAYOUT (Organic Wave Art Background)
           ------------------------------------------------------------- */
        .layout-electro .wave-bg-top {
            position: absolute;
            top: 0;
            right: 0;
            width: 260px;
            z-index: 0;
        }
        .layout-electro .wave-bg-bottom {
            position: absolute;
            bottom: 0;
            left: 0;
            width: 280px;
            z-index: 0;
        }
        .layout-electro .serif-electro {
            font-family: Georgia, serif;
            font-size: 40px;
            font-weight: normal;
            color: #000000;
        }
        .layout-electro .table-electro {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 25px;
        }
        .layout-electro .table-electro th {
            border-top: 2px solid #000;
            border-bottom: 2px solid #000;
            padding: 10px 0;
            font-size: 12px;
            font-weight: bold;
            text-align: left;
        }
        .layout-electro .table-electro td {
            border-bottom: 1px solid #000;
            padding: 12px 0;
        }

        /* Standard Fallback Helpers */
        .clear { clear: both; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .font-bold { font-weight: bold; }
    </style>
</head>
<body class="layout-{{ $templateLayout ?? 'standard' }}">

@if(($templateLayout ?? 'standard') === 'jumlee')
    <!-- JUMLEE PURPLE BLOCK LAYOUT -->
    <div class="page-container">
        <div class="serif-title">INVOICE</div>

        <table class="info-grid">
            <tr>
                <td style="width: 50%;">
                    <div class="section-label">INVOICE TO:</div>
                    <div class="font-bold" style="font-size: 14px;">{{ $clientName ?: 'Client Name' }}</div>
                    @if($clientEmail)<div>{{ $clientEmail }}</div>@endif
                    @if($clientAddress)<div>{{ $clientAddress }}</div>@endif
                    @if($clientVatNo)<div>VAT: {{ $clientVatNo }}</div>@endif
                </td>
                <td style="width: 50%; text-align: right;">
                    <div class="section-label">INVOICE FROM:</div>
                    <div class="font-bold" style="font-size: 14px;">{{ $sellerName }}</div>
                    @if($sellerAddress)<div>{{ $sellerAddress }}</div>@endif
                    @if($sellerEmail)<div>{{ $sellerEmail }}</div>@endif
                    @if($sellerPhone)<div>{{ $sellerPhone }}</div>@endif
                </td>
            </tr>
        </table>

        <table class="table-jumlee">
            <thead>
                <tr>
                    <th style="width: 50%;">PRODUCT</th>
                    <th style="width: 15%; text-align: right;">PRICE</th>
                    <th style="width: 15%; text-align: center;">QTY</th>
                    <th style="width: 20%; text-align: right;">TOTAL</th>
                </tr>
            </thead>
            <tbody>
                @foreach($items as $item)
                    <tr>
                        <td class="font-bold">{{ $item['description'] }}</td>
                        <td style="text-align: right;">{{ $currencySymbol }}{{ number_format((float)$item['unit_price'], 2) }}</td>
                        <td style="text-align: center;">{{ $item['quantity'] }}</td>
                        <td style="text-align: right;" class="font-bold">{{ $currencySymbol }}{{ number_format((float)$item['quantity'] * (float)$item['unit_price'], 2) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <table class="total-block-jumlee">
            <tr>
                <td style="color: #475569; font-weight: bold;">SUB-TOTAL</td>
                <td style="text-align: right; font-weight: bold;">{{ $currencySymbol }}{{ number_format($subtotal, 2) }}</td>
            </tr>
            <tr class="bg-purple-total">
                <td>TOTAL</td>
                <td style="text-align: right;">{{ $currencySymbol }}{{ number_format($totalDue, 2) }}</td>
            </tr>
        </table>

        <div class="clear"></div>

        <div style="margin-top: 40px; font-size: 11px; font-weight: bold; line-height: 1.6;">
            <div>PAYMENT METHOD: {{ strtoupper($paymentMethod ?? 'MASTERCARD ****8190') }}</div>
            <div>DATE: {{ date('d.m.Y', strtotime($issueDate)) }}</div>
            <div>INVOICE ID: #{{ $invoiceNumber }}</div>
        </div>

        <div class="bottom-bar"></div>
    </div>

@elseif(($templateLayout ?? 'standard') === 'bordeux')
    <!-- BORDEUX NAVY MINIMALIST LINE LAYOUT -->
    <div class="page-container">
        <table style="width: 100%;">
            <tr>
                <td style="width: 50%; font-size: 12px; line-height: 1.6;">
                    <div>Date : {{ date('d M, Y', strtotime($issueDate)) }}</div>
                    <div>Invoice No. {{ $invoiceNumber }}</div>
                </td>
                <td style="width: 50%;">
                    <div class="navy-title">INVOICE</div>
                </td>
            </tr>
        </table>

        <div class="hr-navy"></div>

        <table class="info-grid" style="width: 100%; margin-bottom: 30px;">
            <tr>
                <td style="width: 50%; vertical-align: top;">
                    <div class="font-bold" style="font-size: 12px; margin-bottom: 4px;">BILLED TO:</div>
                    <div class="font-bold" style="font-size: 13px;">{{ $clientName ?: 'Client Name' }}</div>
                    @if($clientAddress)<div>{{ $clientAddress }}</div>@endif
                    @if($clientEmail)<div>{{ $clientEmail }}</div>@endif
                </td>
                <td style="width: 50%; vertical-align: top;">
                    <div class="font-bold" style="font-size: 12px; margin-bottom: 4px;">FROM:</div>
                    <div class="font-bold" style="font-size: 13px;">{{ $sellerName }}</div>
                    @if($sellerAddress)<div>{{ $sellerAddress }}</div>@endif
                    @if($sellerPhone)<div>{{ $sellerPhone }}</div>@endif
                    @if($sellerEmail)<div>{{ $sellerEmail }}</div>@endif
                </td>
            </tr>
        </table>

        <table class="table-bordeux">
            <thead>
                <tr>
                    <th style="width: 45%; text-align: left;">DESCRIPTION</th>
                    <th style="width: 15%; text-align: center;">QUANTITY</th>
                    <th style="width: 20%; text-align: right;">PRICE</th>
                    <th style="width: 20%; text-align: right;">TOTAL</th>
                </tr>
            </thead>
            <tbody>
                @foreach($items as $item)
                    <tr>
                        <td>{{ $item['description'] }}</td>
                        <td style="text-align: center;">{{ $item['quantity'] }}</td>
                        <td style="text-align: right;">{{ $currencySymbol }} {{ number_format((float)$item['unit_price'], 2) }}</td>
                        <td style="text-align: right;" class="font-bold">{{ $currencySymbol }} {{ number_format((float)$item['quantity'] * (float)$item['unit_price'], 2) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <table style="width: 280px; float: right; margin-bottom: 40px; font-size: 13px;">
            <tr>
                <td style="color: #475569;">Subtotal:</td>
                <td style="text-align: right;">{{ $currencySymbol }} {{ number_format($subtotal, 2) }}</td>
            </tr>
            <tr>
                <td class="font-bold" style="font-size: 14px; padding-top: 8px;">Grand Total:</td>
                <td style="text-align: right; font-weight: bold; font-size: 14px; padding-top: 8px;">{{ $currencySymbol }} {{ number_format($totalDue, 2) }}</td>
            </tr>
        </table>

        <div class="clear"></div>

        <table style="width: 100%; margin-top: 40px;">
            <tr>
                <td style="width: 50%; vertical-align: top;">
                    <div class="font-bold" style="font-size: 11px; text-transform: uppercase;">PAYMENT INFORMATION</div>
                    <div style="font-size: 11px; margin-top: 4px;">{{ $paymentMethod ?: 'Credit Card MASTERCARD ****4292' }}</div>
                </td>
                <td style="width: 50%; text-align: right; vertical-align: top;">
                    <div class="font-bold" style="font-size: 13px; text-transform: uppercase;">THANK YOU FOR YOUR PURCHASE!</div>
                </td>
            </tr>
        </table>
    </div>

@elseif(($templateLayout ?? 'standard') === 'electro')
    <!-- ELECTRO BASE ORGANIC WAVE ART LAYOUT -->
    <div class="page-container">
        <!-- SVG Fluid Wave Background Graphics -->
        <svg class="wave-bg-top" viewBox="0 0 500 500" preserveAspectRatio="none">
            <path d="M 0 0 C 150 100 350 0 500 150 L 500 0 Z" fill="#93c5fd" opacity="0.6"/>
            <path d="M 100 0 C 250 150 400 50 500 250 L 500 0 Z" fill="#60a5fa" opacity="0.4"/>
            <circle cx="450" cy="180" r="4" fill="#3b82f6"/>
            <circle cx="420" cy="220" r="6" fill="#3b82f6"/>
        </svg>

        <svg class="wave-bg-bottom" viewBox="0 0 500 500" preserveAspectRatio="none">
            <path d="M 0 500 C 150 400 300 500 500 350 L 500 500 Z" fill="#93c5fd" opacity="0.5"/>
            <path d="M 0 350 C 200 450 350 300 500 480 L 500 500 Z" fill="#3b82f6" opacity="0.3"/>
            <circle cx="50" cy="380" r="5" fill="#2563eb"/>
            <circle cx="80" cy="420" r="3" fill="#2563eb"/>
        </svg>

        <div style="position: relative; z-index: 10;">
            <table style="width: 100%; margin-bottom: 35px;">
                <tr>
                    <td style="width: 50%;">
                        <div class="serif-electro">Invoice</div>
                    </td>
                    <td style="width: 50%; text-align: right;">
                        <div class="font-bold" style="font-size: 13px;">Invoice No. {{ $invoiceNumber }}</div>
                        <div style="font-size: 12px; margin-top: 4px;">{{ date('d.m.Y', strtotime($issueDate)) }}</div>
                    </td>
                </tr>
            </table>

            <table class="info-grid" style="width: 100%; margin-bottom: 35px;">
                <tr>
                    <td style="width: 50%; vertical-align: top;">
                        <div class="font-bold" style="margin-bottom: 4px;">From:</div>
                        <div class="font-bold">{{ $sellerName }}</div>
                        @if($sellerAddress)<div>{{ $sellerAddress }}</div>@endif
                        @if($sellerEmail)<div>{{ $sellerEmail }}</div>@endif
                        @if($sellerPhone)<div>{{ $sellerPhone }}</div>@endif
                    </td>
                    <td style="width: 50%; vertical-align: top;">
                        <div class="font-bold" style="margin-bottom: 4px;">To:</div>
                        <div class="font-bold">{{ $clientName ?: 'Client Name' }}</div>
                        @if($clientEmail)<div>{{ $clientEmail }}</div>@endif
                        @if($clientAddress)<div>{{ $clientAddress }}</div>@endif
                    </td>
                </tr>
            </table>

            <table class="table-electro">
                <thead>
                    <tr>
                        <th style="width: 45%;">Description</th>
                        <th style="width: 15%; text-align: center;">Quantity</th>
                        <th style="width: 20%; text-align: right;">Rate</th>
                        <th style="width: 20%; text-align: right;">Amount</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($items as $item)
                        <tr>
                            <td>{{ $item['description'] }}</td>
                            <td style="text-align: center;">{{ $item['quantity'] }}</td>
                            <td style="text-align: right;">{{ $currencySymbol }}{{ number_format((float)$item['unit_price'], 2) }}</td>
                            <td style="text-align: right;" class="font-bold">{{ $currencySymbol }}{{ number_format((float)$item['quantity'] * (float)$item['unit_price'], 2) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            <table style="width: 260px; float: right; margin-bottom: 35px; font-size: 14px;">
                <tr>
                    <td class="font-bold" style="text-transform: uppercase;">TOTAL</td>
                    <td style="text-align: right;" class="font-bold">{{ $currencySymbol }}{{ number_format($totalDue, 2) }}</td>
                </tr>
            </table>

            <div class="clear"></div>

            <div style="text-align: right; margin-top: 20px;">
                <div class="font-bold" style="font-family: Georgia, serif; font-size: 14px;">Payment details</div>
                <div style="font-size: 11px; margin-top: 4px; color: #334155;">Payment was successfully completed using VISA ending in 6714</div>
            </div>
        </div>
    </div>

@else
    <!-- STANDARD CORPORATE LAYOUT -->
    <div class="page-container">
        <table style="width: 100%; margin-bottom: 30px; border-bottom: 3px solid {{ $brandColor }}; padding-bottom: 20px;">
            <tr>
                <td style="width: 60%;">
                    <div style="font-size: 24px; font-weight: bold; color: {{ $brandColor }};">{{ $sellerName }}</div>
                    @if($sellerWebsite)<div>{{ $sellerWebsite }}</div>@endif
                    @if($sellerEmail)<div>{{ $sellerEmail }}</div>@endif
                    @if($sellerRegNo)<div>Reg No: {{ $sellerRegNo }}</div>@endif
                </td>
                <td style="width: 40%; text-align: right;">
                    <div style="font-size: 28px; font-weight: bold; color: #1e293b;">INVOICE</div>
                    <div style="font-size: 14px; font-weight: bold; color: #475569;">#{{ $invoiceNumber }}</div>
                    <div>Date: {{ $issueDate }}</div>
                </td>
            </tr>
        </table>

        <table style="width: 100%; margin-bottom: 30px;">
            <tr>
                <td style="width: 50%;">
                    <div style="font-size: 11px; font-weight: bold; text-transform: uppercase; color: #94a3b8;">Billed To</div>
                    <div style="font-size: 14px; font-weight: bold;">{{ $clientName ?: 'Client Name' }}</div>
                    @if($clientEmail)<div>{{ $clientEmail }}</div>@endif
                    @if($clientAddress)<div>{{ $clientAddress }}</div>@endif
                </td>
            </tr>
        </table>

        <table style="width: 100%; border-collapse: collapse; margin-bottom: 30px;">
            <thead>
                <tr style="background-color: {{ $brandColor }}; color: #ffffff;">
                    <th style="padding: 10px; text-align: left;">Description</th>
                    <th style="padding: 10px; text-align: center;">Qty</th>
                    <th style="padding: 10px; text-align: right;">Unit Price</th>
                    <th style="padding: 10px; text-align: right;">Amount</th>
                </tr>
            </thead>
            <tbody>
                @foreach($items as $item)
                    <tr style="border-bottom: 1px solid #e2e8f0;">
                        <td style="padding: 10px;">{{ $item['description'] }}</td>
                        <td style="padding: 10px; text-align: center;">{{ $item['quantity'] }}</td>
                        <td style="padding: 10px; text-align: right;">{{ $currencySymbol }}{{ number_format((float)$item['unit_price'], 2) }}</td>
                        <td style="padding: 10px; text-align: right; font-weight: bold;">{{ $currencySymbol }}{{ number_format((float)$item['quantity'] * (float)$item['unit_price'], 2) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <table style="width: 280px; float: right; margin-bottom: 30px;">
            <tr>
                <td>Subtotal:</td>
                <td style="text-align: right; font-weight: bold;">{{ $currencySymbol }}{{ number_format($subtotal, 2) }}</td>
            </tr>
            <tr style="font-size: 16px; font-weight: bold; color: {{ $brandColor }}; border-top: 2px solid #e2e8f0;">
                <td>Total Due:</td>
                <td style="text-align: right;">{{ $currencySymbol }}{{ number_format($totalDue, 2) }}</td>
            </tr>
        </table>
    </div>
@endif

</body>
</html>
