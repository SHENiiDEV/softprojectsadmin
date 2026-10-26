<?php

namespace Tests\Feature;

use App\Models\Provider;
use App\Models\User;
use Database\Seeders\FmpayTariffSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FmpayTariffSeederTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
        $this->seed(FmpayTariffSeeder::class);

        $this->user = User::factory()->create()->assignRole('admin');
        $this->actingAs($this->user);
    }

    /**
     * Test FMPay provider details seeded accurately.
     */
    public function test_fmpay_provider_is_seeded_with_correct_parameters(): void
    {
        $fmpay = Provider::where('code', 'fmpay')->first();

        $this->assertNotNull($fmpay);
        $this->assertEquals('FMPay', $fmpay->name);
        $this->assertEquals('acquiring', $fmpay->type);
        $this->assertEquals('EUR', $fmpay->base_currency);
        $this->assertEquals('https://fmpay.me', $fmpay->website);
        $this->assertTrue($fmpay->is_active);

        // Check rates
        $euRate = $fmpay->processingRates()->where('card_region', 'eu')->first();
        $this->assertNotNull($euRate);
        $this->assertEquals(0.0250, $euRate->percent_rate);
        $this->assertEquals(0.15, $euRate->fixed_fee);

        $nonEuRate = $fmpay->processingRates()->where('card_region', 'non_eu')->first();
        $this->assertNotNull($nonEuRate);
        $this->assertEquals(0.0350, $nonEuRate->percent_rate);
        $this->assertEquals(0.20, $nonEuRate->fixed_fee);

        // Check service fees
        $monthlyFee = $fmpay->serviceFees()->where('fee_code', 'monthly_maintenance')->first();
        $this->assertNotNull($monthlyFee);
        $this->assertEquals(100.00, $monthlyFee->amount);

        $chargebackFee = $fmpay->serviceFees()->where('fee_code', 'chargeback')->first();
        $this->assertNotNull($chargebackFee);
        $this->assertEquals(50.00, $chargebackFee->amount);

        $fxFee = $fmpay->serviceFees()->where('fee_code', 'fx_margin')->first();
        $this->assertNotNull($fxFee);
        $this->assertEquals(0.0150, $fxFee->percentage);

        // Check reserve
        $reserve = $fmpay->reserve;
        $this->assertNotNull($reserve);
        $this->assertEquals(0.00, $reserve->rate_percent);
    }

    /**
     * Test transaction simulation with FMPay tariffs.
     */
    public function test_fmpay_transaction_cost_simulation(): void
    {
        $fmpay = Provider::where('code', 'fmpay')->first();

        // 100 EUR EU card transaction: 2.5% + €0.15 = €2.65 fee, 0 reserve, €97.35 settlement
        $sim = $fmpay->simulateTransactionCost(
            amount: 100.00,
            paymentMethod: 'card',
            flowType: 'payin',
            cardRegion: 'eu'
        );

        $this->assertTrue($sim['is_supported']);
        $this->assertEquals(2.65, $sim['fee']);
        $this->assertEquals(0.00, $sim['reserve_amount']);
        $this->assertEquals(97.35, $sim['settlement_amount']);
    }

    /**
     * Test /tariffs view displays FMPay.
     */
    public function test_tariffs_page_displays_fmpay(): void
    {
        $response = $this->get('/tariffs');
        $response->assertStatus(200);
        $response->assertSee('FMPay');
    }
}
