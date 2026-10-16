<?php

namespace Tests\Feature;

use App\Livewire\Projects\WebsitesSection;
use App\Models\Project;
use App\Models\User;
use App\Models\Website;
use App\Services\GatewayService;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class GatewayProvidersTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected Project $project;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);

        $this->user = User::factory()->create()->assignRole('admin');
        $this->actingAs($this->user);

        $this->project = Project::factory()->create();
    }

    /**
     * Test that Stripe is included in the available gateways by default.
     */
    public function test_stripe_is_included_in_available_gateways(): void
    {
        $gateways = GatewayService::all();

        $this->assertContains('Stripe', $gateways);
        $this->assertTrue(GatewayService::isValid('Stripe'));
        $this->assertContains('Stripe', config('gateways.available'));
        $this->assertContains('Stripe', WebsitesSection::AVAILABLE_GATEWAYS);
    }

    /**
     * Test saving a website with Stripe as a gateway provider.
     */
    public function test_can_save_website_with_stripe_gateway(): void
    {
        Livewire::test(WebsitesSection::class, ['project' => $this->project])
            ->call('openCreateForm')
            ->set('name', 'E-Shop Store')
            ->set('url', 'https://eshop-store.example.com')
            ->set('status', 'Live')
            ->set('gateways', ['Stripe', 'GuruPay'])
            ->set('visa_status', 'Working')
            ->set('mastercard_status', 'Working')
            ->call('save')
            ->assertHasNoErrors();

        $website = Website::where('project_id', $this->project->id)->first();
        $this->assertNotNull($website);
        $this->assertEquals(['Stripe', 'GuruPay'], $website->gateways);
    }

    /**
     * Test dynamically adding a new gateway provider via GatewayService.
     */
    public function test_can_dynamically_add_gateway_provider_via_service(): void
    {
        $this->assertFalse(GatewayService::isValid('CustomPayPro'));

        $added = GatewayService::add('CustomPayPro');
        $this->assertEquals('CustomPayPro', $added);

        $this->assertTrue(GatewayService::isValid('CustomPayPro'));
        $this->assertContains('CustomPayPro', GatewayService::all());
    }

    /**
     * Test adding a gateway provider via Artisan command `gateway:add`.
     */
    public function test_artisan_gateway_add_command_adds_provider(): void
    {
        $this->artisan('gateway:add', ['name' => 'AdyenGlobal'])
            ->expectsOutput("Gateway provider 'AdyenGlobal' has been added successfully.")
            ->assertExitCode(0);

        $this->assertTrue(GatewayService::isValid('AdyenGlobal'));
        $this->assertContains('AdyenGlobal', GatewayService::all());
    }

    /**
     * Test adding a new gateway provider directly via WebsitesSection Livewire UI.
     */
    public function test_can_add_gateway_via_websites_section_ui_and_auto_select(): void
    {
        Livewire::test(WebsitesSection::class, ['project' => $this->project])
            ->call('openCreateForm')
            ->set('newGatewayName', 'CheckoutGlobal')
            ->call('addNewGateway')
            ->assertHasNoErrors()
            ->assertSee('Gateway &#039;CheckoutGlobal&#039; added and selected!', false);

        $this->assertTrue(GatewayService::isValid('CheckoutGlobal'));

        // Verify that the website can be saved with the newly added gateway
        Livewire::test(WebsitesSection::class, ['project' => $this->project])
            ->call('openCreateForm')
            ->set('name', 'Global Checkout Store')
            ->set('url', 'https://global-store.example.com')
            ->set('status', 'Live')
            ->set('gateways', ['CheckoutGlobal', 'Stripe'])
            ->set('visa_status', 'Working')
            ->set('mastercard_status', 'Working')
            ->call('save')
            ->assertHasNoErrors();

        $website = Website::where('url', 'https://global-store.example.com')->first();
        $this->assertNotNull($website);
        $this->assertContains('CheckoutGlobal', $website->gateways);
        $this->assertContains('Stripe', $website->gateways);
    }

    /**
     * Test removing a gateway provider via GatewayService::remove().
     */
    public function test_can_remove_gateway_provider_via_service(): void
    {
        GatewayService::add('TemporaryGateway');
        $this->assertTrue(GatewayService::isValid('TemporaryGateway'));

        GatewayService::remove('TemporaryGateway');
        $this->assertFalse(GatewayService::isValid('TemporaryGateway'));
        $this->assertNotContains('TemporaryGateway', GatewayService::all());
    }

    /**
     * Test removing a gateway provider via Artisan command `gateway:remove`.
     */
    public function test_artisan_gateway_remove_command(): void
    {
        GatewayService::add('CliToRemove');
        $this->assertTrue(GatewayService::isValid('CliToRemove'));

        $this->artisan('gateway:remove', ['name' => 'CliToRemove'])
            ->expectsOutput("Gateway provider 'CliToRemove' has been successfully removed.")
            ->assertExitCode(0);

        $this->assertFalse(GatewayService::isValid('CliToRemove'));
    }

    /**
     * Test removing a gateway provider via WebsitesSection UI.
     */
    public function test_can_remove_gateway_via_websites_section_ui(): void
    {
        GatewayService::add('UiToRemove');

        Livewire::test(WebsitesSection::class, ['project' => $this->project])
            ->call('openCreateForm')
            ->set('gateways', ['UiToRemove', 'Stripe'])
            ->call('removeGateway', 'UiToRemove')
            ->assertSet('gateways', ['Stripe'])
            ->assertSee('Gateway &#039;UiToRemove&#039; has been removed', false);

        $this->assertFalse(GatewayService::isValid('UiToRemove'));
        $this->assertNotContains('UiToRemove', GatewayService::all());
    }
}
