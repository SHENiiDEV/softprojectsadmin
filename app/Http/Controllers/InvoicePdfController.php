<?php

namespace App\Http\Controllers;

use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

class InvoicePdfController extends Controller
{
    /**
     * Download customized invoice as a PDF.
     */
    public function download(Request $request)
    {
        $data = $request->validate([
            'sellerName' => 'required|string',
            'sellerRegNo' => 'nullable|string',
            'sellerAddress' => 'nullable|string',
            'sellerEmail' => 'nullable|string',
            'sellerPhone' => 'nullable|string',
            'sellerWebsite' => 'nullable|string',
            'brandColor' => 'nullable|string',
            'templateLayout' => 'nullable|string',
            'paymentMethod' => 'nullable|string',

            'invoiceNumber' => 'required|string',
            'issueDate' => 'required|string',
            'dueDate' => 'nullable|string',
            'currency' => 'required|string',
            'currencySymbol' => 'required|string',

            'clientName' => 'nullable|string',
            'clientEmail' => 'nullable|string',
            'clientAddress' => 'nullable|string',
            'clientVatNo' => 'nullable|string',

            'items' => 'required|array',
            'items.*.description' => 'required|string',
            'items.*.quantity' => 'required|numeric',
            'items.*.unit_price' => 'required|numeric',

            'taxPercent' => 'nullable|numeric',
            'discountAmount' => 'nullable|numeric',

            'bankName' => 'nullable|string',
            'accountName' => 'nullable|string',
            'accountNumber' => 'nullable|string',
            'sortCode' => 'nullable|string',
            'iban' => 'nullable|string',
            'swift' => 'nullable|string',
            'notes' => 'nullable|string',
        ]);

        // Calculate totals
        $subtotal = 0.0;
        foreach ($data['items'] as $item) {
            $subtotal += ((float) $item['quantity'] * (float) $item['unit_price']);
        }
        $taxPercent = (float) ($data['taxPercent'] ?? 0);
        $discountAmount = (float) ($data['discountAmount'] ?? 0);
        $taxAmount = round(($subtotal - $discountAmount) * ($taxPercent / 100), 2);
        $totalDue = max(0.0, round($subtotal - $discountAmount + $taxAmount, 2));

        $defaults = [
            'templateLayout' => 'standard',
            'paymentMethod' => 'Credit Card MASTERCARD ****8190',
            'sellerRegNo' => '',
            'sellerAddress' => '',
            'sellerEmail' => '',
            'sellerPhone' => '',
            'sellerWebsite' => '',
            'brandColor' => '#0ea5e9',
            'dueDate' => '',
            'clientName' => '',
            'clientEmail' => '',
            'clientAddress' => '',
            'clientVatNo' => '',
            'taxPercent' => 0,
            'discountAmount' => 0,
            'bankName' => '',
            'accountName' => '',
            'accountNumber' => '',
            'sortCode' => '',
            'iban' => '',
            'swift' => '',
            'notes' => '',
        ];

        $viewData = array_merge($defaults, $data, compact('subtotal', 'taxAmount', 'totalDue'));
        $pdf = Pdf::loadView('pdf.invoice-pdf', $viewData);

        $filename = preg_replace('/[^A-Za-z0-9_-]/', '_', $data['invoiceNumber']).'.pdf';

        return $pdf->download($filename);
    }
}
