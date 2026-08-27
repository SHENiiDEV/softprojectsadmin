<?php

namespace App\Livewire\Invoices;

use App\Models\Project;
use App\Models\Website;
use Livewire\Component;

class InvoiceBuilder extends Component
{
    // Brand Selection
    public ?int $selectedProjectId = null;

    public ?int $selectedWebsiteId = null;

    // Seller Info (Auto-filled from Project/Website or editable)
    public string $sellerName = 'Soft Projects Ltd';

    public string $sellerRegNo = '';

    public string $sellerAddress = '';

    public string $sellerEmail = 'info@softprojects.co.uk';

    public string $sellerPhone = '';

    public string $sellerWebsite = '';

    public string $brandColor = '#0ea5e9'; // Hex color (sky blue, indigo, emerald, amber, slate)

    public string $templateLayout = 'standard'; // jumlee, bordeux, electro, standard

    public string $paymentMethod = 'Credit Card MASTERCARD ****8190';

    // Invoice Header
    public string $invoiceNumber = '';

    public string $issueDate = '';

    public string $dueDate = '';

    public string $currency = 'GBP'; // GBP, EUR, USD

    public string $currencySymbol = '£';

    // Client Info (Bill To)
    public string $clientName = '';

    public string $clientEmail = '';

    public string $clientAddress = '';

    public string $clientVatNo = '';

    // Dynamic Line Items
    public array $items = [];

    // Financial Adjustments
    public float $taxPercent = 0.0;

    public float $discountAmount = 0.0;

    // Payment Details
    public string $bankName = 'Barclays Bank UK';

    public string $accountName = '';

    public string $accountNumber = '';

    public string $sortCode = '';

    public string $iban = '';

    public string $swift = '';

    public string $notes = 'Thank you for your business! Please make payment within 14 days of invoice date.';

    public function mount(): void
    {
        $this->invoiceNumber = 'INV-'.date('Ymd').'-'.rand(100, 999);
        $this->issueDate = date('Y-m-d');
        $this->dueDate = date('Y-m-d', strtotime('+14 days'));

        // Default line items
        $this->items = [
            [
                'description' => 'Software Development & IT Support Services',
                'quantity' => 1,
                'unit_price' => 500.00,
            ],
        ];

        // Try pre-selecting first project
        $firstProject = Project::with(['websites', 'report'])->first();
        if ($firstProject) {
            $this->selectedProjectId = $firstProject->id;
            $this->updatedSelectedProjectId($firstProject->id);
        }
    }

    public function updatedSelectedProjectId(?int $projectId): void
    {
        if (! $projectId) {
            return;
        }

        $project = Project::with(['websites', 'report'])->find($projectId);
        if (! $project) {
            return;
        }

        $this->sellerName = $project->name;
        $this->sellerRegNo = $project->report?->reg_number ?: '';
        $this->sellerWebsite = $project->websites->first()?->url ?: '';
        $this->sellerAddress = 'United Kingdom';
        $this->accountName = $project->name;

        // Auto brand color accent based on project name hash
        $colors = ['#0ea5e9', '#6366f1', '#10b981', '#f59e0b', '#0284c7', '#8b5cf6'];
        $this->brandColor = $colors[crc32($project->name) % count($colors)];

        if ($project->websites->count() > 0) {
            $this->selectedWebsiteId = $project->websites->first()->id;
            $this->updatedSelectedWebsiteId($this->selectedWebsiteId);
        }
    }

    public function updatedSelectedWebsiteId(?int $websiteId): void
    {
        if (! $websiteId) {
            return;
        }

        $website = Website::find($websiteId);
        if ($website) {
            $this->sellerWebsite = $website->url;
            $domain = parse_url($website->url, PHP_URL_HOST) ?: $website->url;
            $domainClean = preg_replace('/^www\./i', '', $domain);
            $this->sellerEmail = "info@{$domainClean}";

            // Presets per domain
            if (str_contains($domainClean, 'jumlee')) {
                $this->templateLayout = 'jumlee';
                $this->brandColor = '#8b5cf6';
                $this->currency = 'EUR';
                $this->currencySymbol = '€';
            } elseif (str_contains($domainClean, 'bordeux')) {
                $this->templateLayout = 'bordeux';
                $this->brandColor = '#0f172a';
                $this->currency = 'GBP';
                $this->currencySymbol = '£';
            } elseif (str_contains($domainClean, 'electro')) {
                $this->templateLayout = 'electro';
                $this->brandColor = '#3b82f6';
                $this->currency = 'EUR';
                $this->currencySymbol = '€';
            }
        }
    }

    public function updatedCurrency(string $curr): void
    {
        $this->currencySymbol = match ($curr) {
            'GBP' => '£',
            'EUR' => '€',
            'USD' => '$',
            default => '£',
        };
    }

    public function addItem(): void
    {
        $this->items[] = [
            'description' => '',
            'quantity' => 1,
            'unit_price' => 0.00,
        ];
    }

    public function removeItem(int $index): void
    {
        if (count($this->items) > 1) {
            unset($this->items[$index]);
            $this->items = array_values($this->items);
        }
    }

    public function getSubtotalProperty(): float
    {
        $subtotal = 0.0;
        foreach ($this->items as $item) {
            $qty = (float) ($item['quantity'] ?? 0);
            $price = (float) ($item['unit_price'] ?? 0);
            $subtotal += ($qty * $price);
        }

        return round($subtotal, 2);
    }

    public function getTaxAmountProperty(): float
    {
        return round(($this->subtotal - $this->discountAmount) * ($this->taxPercent / 100), 2);
    }

    public function getTotalDueProperty(): float
    {
        $total = $this->subtotal - $this->discountAmount + $this->taxAmount;

        return max(0.0, round($total, 2));
    }

    public function render()
    {
        $projects = Project::select('id', 'name')->orderBy('name')->get();
        $websites = $this->selectedProjectId
            ? Website::where('project_id', $this->selectedProjectId)->get()
            : collect();

        return view('livewire.invoices.invoice-builder', compact('projects', 'websites'));
    }
}
