<?php

namespace Tests\Feature;

use App\Livewire\Invoices\InvoiceBuilder;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class InvoiceBuilderTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_render_invoice_builder_and_autofill_company(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create(['name' => 'Sivora Tech Ltd']);

        Livewire::actingAs($user)
            ->test(InvoiceBuilder::class)
            ->set('selectedProjectId', $project->id)
            ->assertSet('sellerName', 'Sivora Tech Ltd');
    }

    public function test_invoice_builder_calculates_subtotal_and_total(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(InvoiceBuilder::class)
            ->set('items', [
                ['description' => 'Item 1', 'quantity' => 2, 'unit_price' => 100.00],
                ['description' => 'Item 2', 'quantity' => 1, 'unit_price' => 50.00],
            ])
            ->set('taxPercent', 10.0)
            ->set('discountAmount', 20.00)
            ->assertSet('subtotal', 250.00)
            ->assertSet('taxAmount', 23.00)
            ->assertSet('totalDue', 253.00);
    }

    public function test_can_download_pdf_invoice(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('invoices.download-pdf'), [
            'sellerName' => 'Sivora Limited',
            'sellerEmail' => 'info@sivora.co.uk',
            'invoiceNumber' => 'INV-2026-001',
            'issueDate' => '2026-08-27',
            'currency' => 'GBP',
            'currencySymbol' => '£',
            'items' => [
                ['description' => 'Web Development', 'quantity' => 1, 'unit_price' => 500.00],
            ],
        ]);

        $response->assertStatus(200);
        $response->assertHeader('content-type', 'application/pdf');
    }
}
