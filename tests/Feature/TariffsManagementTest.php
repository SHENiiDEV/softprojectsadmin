<?php

namespace Tests\Feature;

use App\Livewire\Tariffs\Index as TariffsIndex;
use App\Models\Provider;
use App\Models\ProviderProcessingRate;
use App\Models\ProviderReserve;
use App\Models\ProviderServiceFee;
use App\Models\User;
use Database\Seeders\ProviderTariffsSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class TariffsManagementTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
        $this->seed(ProviderTariffsSeeder::class);

        $this->user = User::factory()->create()->assignRole('admin');
        $this->actingAs($this->user);
    }

    /**
     * Test page access and rendering.
     */
    public function test_user_can_access_tariffs_matrix_page(): void
    {
        $response = $this->get('/tariffs');
        $response->assertStatus(200);
        $response->assertSee('Acquiring & Banking Tariffs', false);
        $response->assertSee('GuruPay');
        $response->assertSee('Payally');
    }

    /**
     * Test tabs switching.
     */
    public function test_can_switch_tabs_in_tariffs_matrix(): void
    {
        Livewire::test(TariffsIndex::class)
            ->assertSet('activeTab', 'cards')
            ->assertSee('Card Acquiring Comparison Matrix')
            ->call('setTab', 'banking')
            ->assertSet('activeTab', 'banking')
            ->assertSee('Banking Transfers')
            ->call('setTab', 'fees')
            ->assertSet('activeTab', 'fees')
            ->assertSee('Setup, Maintenance')
            ->call('setTab', 'simulator')
            ->assertSet('activeTab', 'simulator')
            ->assertSee('Transaction Parameters')
            ->call('setTab', 'providers')
            ->assertSet('activeTab', 'providers')
            ->assertSee('Add Provider');
    }

    /**
     * Test transaction cost simulation formula.
     * Formula: Fee = max(Amount * percent_rate + fixed_fee, min_fee)
     */
    public function test_cost_simulator_calculates_correct_fee_and_best_route(): void
    {
        // GuruPay EU card: 4.2% (min €0.60), fixed €0.00, reserve 10%
        // On €100: Fee = max(100 * 0.042, 0.60) = €4.20, Reserve = €10.00, Net = €85.80
        // On €10: Fee = max(10 * 0.042 = 0.42, 0.60) = €0.60 (min fee triggered!)

        $guruPay = Provider::where('code', 'gurupay')->first();
        $this->assertNotNull($guruPay);

        // Simulation for €100 EU card
        $sim100 = $guruPay->simulateTransactionCost(
            amount: 100.00,
            paymentMethod: 'card',
            flowType: 'payin',
            cardRegion: 'eu'
        );

        $this->assertTrue($sim100['is_supported']);
        $this->assertEquals(4.20, $sim100['fee']);
        $this->assertEquals(10.00, $sim100['reserve_amount']);
        $this->assertEquals(85.80, $sim100['settlement_amount']);
        $this->assertEquals(4.20, $sim100['effective_percent']);

        // Simulation for €10 (verifying min_fee trigger)
        $sim10 = $guruPay->simulateTransactionCost(
            amount: 10.00,
            paymentMethod: 'card',
            flowType: 'payin',
            cardRegion: 'eu'
        );

        $this->assertEquals(0.60, $sim10['fee']); // Triggered min_fee €0.60 instead of 0.42
        $this->assertEquals(1.00, $sim10['reserve_amount']);
        $this->assertEquals(8.40, $sim10['settlement_amount']);

        // Livewire simulator test
        Livewire::test(TariffsIndex::class)
            ->set('activeTab', 'simulator')
            ->set('simAmount', 1000.00)
            ->set('simMethod', 'card')
            ->set('simRegion', 'eu')
            ->assertSee('Best Route / Optimal Cost')
            ->assertSee('Net Settlement Amount');
    }

    /**
     * Test tiered volume rates in SEPA simulation.
     */
    public function test_tiered_sepa_volume_rates_in_simulator(): void
    {
        $guruPay = Provider::where('code', 'gurupay')->first();

        // Tier 1 (0 - 2M): 0.20% (min €5)
        $simTier1 = $guruPay->simulateTransactionCost(
            amount: 10000.00,
            paymentMethod: 'sepa',
            flowType: 'payin',
            audience: 'b2b',
            monthlyVolume: 500000.00
        );
        $this->assertEquals(20.00, $simTier1['fee']); // 10000 * 0.0020 = 20.00

        // Tier 3 (> 5M): 0.10% (min €5)
        $simTier3 = $guruPay->simulateTransactionCost(
            amount: 10000.00,
            paymentMethod: 'sepa',
            flowType: 'payin',
            audience: 'b2b',
            monthlyVolume: 6000000.00
        );
        $this->assertEquals(10.00, $simTier3['fee']); // 10000 * 0.0010 = 10.00
    }

    /**
     * Test Provider CRUD operations in Livewire component.
     */
    public function test_can_create_update_and_delete_provider(): void
    {
        Livewire::test(TariffsIndex::class)
            ->call('openCreateProviderModal')
            ->set('providerForm.name', 'NovaPay Europe')
            ->set('providerForm.code', 'novapay')
            ->set('providerForm.type', 'hybrid')
            ->set('providerForm.base_currency', 'EUR')
            ->set('providerForm.website', 'https://novapay.io')
            ->call('saveProvider')
            ->assertHasNoErrors();

        $provider = Provider::where('code', 'novapay')->first();
        $this->assertNotNull($provider);
        $this->assertEquals('NovaPay Europe', $provider->name);

        // Edit
        Livewire::test(TariffsIndex::class)
            ->call('openEditProviderModal', $provider->id)
            ->set('providerForm.name', 'NovaPay Global')
            ->call('saveProvider')
            ->assertHasNoErrors();

        $this->assertEquals('NovaPay Global', $provider->fresh()->name);

        // Delete
        Livewire::test(TariffsIndex::class)
            ->call('deleteProvider', $provider->id);

        $this->assertNull(Provider::where('code', 'novapay')->first());
    }

    /**
     * Test Processing Rate CRUD operations.
     */
    public function test_can_create_update_and_delete_processing_rate(): void
    {
        $provider = Provider::where('code', 'gurupay')->first();

        Livewire::test(TariffsIndex::class)
            ->call('openCreateRateModal', $provider->id)
            ->set('rateForm.payment_method', 'crypto')
            ->set('rateForm.flow_type', 'payin')
            ->set('rateForm.percent_rate', 1.25)
            ->set('rateForm.fixed_fee', 0.50)
            ->set('rateForm.min_fee', 1.00)
            ->call('saveRate')
            ->assertHasNoErrors();

        $rate = ProviderProcessingRate::where('provider_id', $provider->id)
            ->where('payment_method', 'crypto')
            ->first();

        $this->assertNotNull($rate);
        $this->assertEquals(0.0125, $rate->percent_rate);
        $this->assertEquals(0.50, $rate->fixed_fee);

        // Delete rate
        Livewire::test(TariffsIndex::class)
            ->call('deleteRate', $rate->id);

        $this->assertNull(ProviderProcessingRate::find($rate->id));
    }

    /**
     * Test Service Fee and Rolling Reserve configuration.
     */
    public function test_can_manage_service_fees_and_reserve(): void
    {
        $provider = Provider::where('code', 'payally')->first();

        // Add custom service fee
        Livewire::test(TariffsIndex::class)
            ->call('openCreateFeeModal', $provider->id)
            ->set('feeForm.fee_code', 'custom_integration')
            ->set('feeForm.title', 'Custom SDK Integration')
            ->set('feeForm.fee_type', 'fixed')
            ->set('feeForm.billing_period', 'one_time')
            ->set('feeForm.amount', 1500.00)
            ->call('saveFee')
            ->assertHasNoErrors();

        $fee = ProviderServiceFee::where('provider_id', $provider->id)
            ->where('fee_code', 'custom_integration')
            ->first();

        $this->assertNotNull($fee);
        $this->assertEquals(1500.00, $fee->amount);

        // Update Rolling Reserve
        Livewire::test(TariffsIndex::class)
            ->call('openEditReserveModal', $provider->id)
            ->set('reserveForm.rate_percent', 12.5)
            ->set('reserveForm.hold_period_days', 120)
            ->call('saveReserve')
            ->assertHasNoErrors();

        $reserve = ProviderReserve::where('provider_id', $provider->id)->first();
        $this->assertEquals(12.5, $reserve->rate_percent);
        $this->assertEquals(120, $reserve->hold_period_days);
    }
}
