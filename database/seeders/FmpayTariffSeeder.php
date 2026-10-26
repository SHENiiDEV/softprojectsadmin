<?php

namespace Database\Seeders;

use App\Models\Provider;
use App\Models\ProviderProcessingRate;
use App\Models\ProviderReserve;
use App\Models\ProviderServiceFee;
use Illuminate\Database\Seeder;

class FmpayTariffSeeder extends Seeder
{
    /**
     * Run the database seeds for FMPay (FM Finance Ltd) tariffs.
     * Source: Parameter Schedule 20261022 for ROWANLEA LIMITED (DBA JUMLEE).
     */
    public function run(): void
    {
        // -------------------------------------------------------------------------
        // FMPay (FM Finance Ltd - UK FCA Reg: 815137)
        // -------------------------------------------------------------------------
        $fmpay = Provider::updateOrCreate(
            ['code' => 'fmpay'],
            [
                'name' => 'FMPay',
                'type' => 'acquiring',
                'base_currency' => 'EUR',
                'website' => 'https://fmpay.me',
                'contact_person' => 'Roman Loban (Director)',
                'contact_email' => 'support@fmpay.me',
                'account_manager' => 'Technical Support (tech@fmpay.me)',
                'notes' => 'FM Finance Ltd (UK FCA FRN: 815137, Co: 11415496). Parameter Schedule for ROWANLEA LIMITED (DBA JUMLEE). Settles Visa & Mastercard every Tuesday & Friday to Moorwand Ltd (min €500). Limits: max €5,000/card/day, €15,000/card/mo, monthly limit €650,000. 3DS required.',
                'is_active' => true,
            ]
        );

        // 1. Card Processing Rates
        $rates = [
            // UK cards: 2.00% + €0.15 auth
            // EEA cards: 2.50% + €0.15 auth
            [
                'payment_method' => 'card',
                'flow_type' => 'payin',
                'target_audience' => 'all',
                'card_region' => 'eu',
                'percent_rate' => 0.0250,
                'fixed_fee' => 0.15,
                'min_fee' => 0.15,
                'notes' => 'EEA cards (Visa/Mastercard): 2.5% + €0.15 auth (UK cards: 2.0% + €0.15)',
            ],
            // Non-EU / International cards: 3.50% + €0.20 auth
            [
                'payment_method' => 'card',
                'flow_type' => 'payin',
                'target_audience' => 'all',
                'card_region' => 'non_eu',
                'percent_rate' => 0.0350,
                'fixed_fee' => 0.20,
                'min_fee' => 0.20,
                'notes' => 'Non-EU / Rest of World cards: 3.5% + €0.20 auth',
            ],
            [
                'payment_method' => 'card',
                'flow_type' => 'payin',
                'target_audience' => 'all',
                'card_region' => 'international',
                'percent_rate' => 0.0350,
                'fixed_fee' => 0.20,
                'min_fee' => 0.20,
                'notes' => 'International / Rest of World cards: 3.5% + €0.20 auth',
            ],
            [
                'payment_method' => 'card',
                'flow_type' => 'payin',
                'target_audience' => 'all',
                'card_region' => 'corporate',
                'percent_rate' => 0.0350,
                'fixed_fee' => 0.20,
                'min_fee' => 0.20,
                'notes' => 'Corporate / Rest of World cards: 3.5% + €0.20 auth',
            ],
            // Refund processing: €5 fixed
            [
                'payment_method' => 'card',
                'flow_type' => 'refund',
                'target_audience' => 'all',
                'card_region' => 'all',
                'percent_rate' => 0.0000,
                'fixed_fee' => 5.00,
                'min_fee' => 5.00,
                'notes' => 'Refund processing fee: €5.00 per refund',
            ],
        ];

        foreach ($rates as $r) {
            ProviderProcessingRate::updateOrCreate(
                [
                    'provider_id' => $fmpay->id,
                    'payment_method' => $r['payment_method'],
                    'flow_type' => $r['flow_type'],
                    'card_region' => $r['card_region'] ?? 'all',
                    'target_audience' => $r['target_audience'] ?? 'all',
                    'tier_min_volume' => $r['tier_min_volume'] ?? 0,
                ],
                array_merge($r, [
                    'provider_id' => $fmpay->id,
                    'currency' => 'EUR',
                    'is_active' => true,
                ])
            );
        }

        // 2. Service Fees
        $fees = [
            [
                'fee_code' => 'onboarding',
                'title' => 'Merchant Account opening fee',
                'fee_type' => 'fixed',
                'billing_period' => 'one_time',
                'amount' => 0.00,
                'amount_max' => null,
                'percentage' => null,
                'comment' => 'EUR 0 (Free account opening)',
            ],
            [
                'fee_code' => 'monthly_maintenance',
                'title' => 'Monthly account maintenance fee',
                'fee_type' => 'fixed',
                'billing_period' => 'monthly',
                'amount' => 100.00,
                'amount_max' => null,
                'percentage' => null,
                'comment' => 'EUR 100 per month standard maintenance',
            ],
            [
                'fee_code' => 'high_risk_supervision',
                'title' => 'High Risk Supervised fee',
                'fee_type' => 'fixed',
                'billing_period' => 'monthly',
                'amount' => 100.00,
                'amount_max' => null,
                'percentage' => null,
                'comment' => 'EUR 100 per month for supervised high-risk monitoring',
            ],
            [
                'fee_code' => 'chargeback',
                'title' => 'Commission fee for the processing of one Chargeback',
                'fee_type' => 'fixed',
                'billing_period' => 'per_event',
                'amount' => 50.00,
                'amount_max' => null,
                'percentage' => null,
                'comment' => 'EUR 50 per chargeback (ratio threshold: max 0.90% per month, max 50 chargebacks/card/mo)',
            ],
            [
                'fee_code' => 'fx_margin',
                'title' => 'Foreign exchange (FX) markup Fee',
                'fee_type' => 'percentage',
                'billing_period' => 'per_event',
                'amount' => 0.00,
                'amount_max' => null,
                'percentage' => 0.0150,
                'comment' => '1.5% markup fee on currency conversions',
            ],
            [
                'fee_code' => 'refund_fee',
                'title' => 'Commission fee for processing of one Refund',
                'fee_type' => 'fixed',
                'billing_period' => 'per_event',
                'amount' => 5.00,
                'amount_max' => null,
                'percentage' => null,
                'comment' => 'EUR 5 per refund (threshold: up to 50 refunds or 3% total volume)',
            ],
            [
                'fee_code' => 'declined_payment',
                'title' => 'Commission fee for one declined Payment',
                'fee_type' => 'fixed',
                'billing_period' => 'per_event',
                'amount' => 0.00,
                'amount_max' => null,
                'percentage' => null,
                'comment' => 'EUR 0 (Free declined transactions)',
            ],
            [
                'fee_code' => 'integration_fee',
                'title' => 'Non-standard integration fee',
                'fee_type' => 'fixed',
                'billing_period' => 'one_time',
                'amount' => 0.00,
                'amount_max' => null,
                'percentage' => null,
                'comment' => 'EUR 0 (Hosted payment page, E-invoicing link, API)',
            ],
        ];

        foreach ($fees as $f) {
            ProviderServiceFee::updateOrCreate(
                ['provider_id' => $fmpay->id, 'fee_code' => $f['fee_code']],
                array_merge($f, ['provider_id' => $fmpay->id, 'currency' => 'EUR'])
            );
        }

        // 3. Reserves Policy
        ProviderReserve::updateOrCreate(
            ['provider_id' => $fmpay->id],
            [
                'rate_percent' => 0.00,
                'hold_period_days' => 0,
                'floor_amount' => 0.00,
                'cap_amount' => 0.00,
                'currency' => 'EUR',
                'conditions' => 'No rolling reserve or fixed reserve required (0% hold). Full payouts each Tuesday & Friday to Moorwand Ltd account (min €500).',
            ]
        );
    }
}
