<?php

namespace Database\Seeders;

use App\Models\Provider;
use App\Models\ProviderProcessingRate;
use App\Models\ProviderReserve;
use App\Models\ProviderServiceFee;
use Illuminate\Database\Seeder;

class ProviderTariffsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. GuruPay
        $guruPay = Provider::updateOrCreate(
            ['code' => 'gurupay'],
            [
                'name' => 'GuruPay',
                'type' => 'hybrid',
                'base_currency' => 'EUR',
                'website' => 'https://gurupay.eu',
                'contact_person' => 'Arturs Berzins',
                'contact_email' => 'support@gurupay.eu',
                'account_manager' => 'Alexey V.',
                'notes' => 'European licensed EMI & acquiring partner. Strong for high-risk and EU/Non-EU acquiring.',
                'is_active' => true,
            ]
        );

        // GuruPay processing rates
        $guruPayRates = [
            // Card Pay-in
            ['payment_method' => 'card', 'flow_type' => 'payin', 'target_audience' => 'all', 'card_region' => 'eu', 'percent_rate' => 0.0420, 'fixed_fee' => 0.00, 'min_fee' => 0.60, 'notes' => 'EU Interchange++ cards'],
            ['payment_method' => 'card', 'flow_type' => 'payin', 'target_audience' => 'all', 'card_region' => 'non_eu', 'percent_rate' => 0.0470, 'fixed_fee' => 0.00, 'min_fee' => 0.70, 'notes' => 'Non-EU International cards'],
            ['payment_method' => 'card', 'flow_type' => 'payin', 'target_audience' => 'all', 'card_region' => 'international', 'percent_rate' => 0.0470, 'fixed_fee' => 0.00, 'min_fee' => 0.70, 'notes' => 'Global worldwide cards'],
            ['payment_method' => 'card', 'flow_type' => 'payin', 'target_audience' => 'all', 'card_region' => 'corporate', 'percent_rate' => 0.0470, 'fixed_fee' => 0.00, 'min_fee' => 0.70, 'notes' => 'Commercial & Corporate cards'],

            // SEPA Incoming (Volume Tiers)
            ['payment_method' => 'sepa', 'flow_type' => 'payin', 'target_audience' => 'all', 'card_region' => 'all', 'percent_rate' => 0.0020, 'fixed_fee' => 0.00, 'min_fee' => 5.00, 'tier_min_volume' => 0, 'tier_max_volume' => 2000000, 'notes' => 'Tier 1: €0 - €2M / mo'],
            ['payment_method' => 'sepa', 'flow_type' => 'payin', 'target_audience' => 'all', 'card_region' => 'all', 'percent_rate' => 0.0015, 'fixed_fee' => 0.00, 'min_fee' => 5.00, 'tier_min_volume' => 2000000, 'tier_max_volume' => 5000000, 'notes' => 'Tier 2: €2M - €5M / mo'],
            ['payment_method' => 'sepa', 'flow_type' => 'payin', 'target_audience' => 'all', 'card_region' => 'all', 'percent_rate' => 0.0010, 'fixed_fee' => 0.00, 'min_fee' => 5.00, 'tier_min_volume' => 5000000, 'tier_max_volume' => null, 'notes' => 'Tier 3: > €5M / mo'],

            // SEPA Outgoing
            ['payment_method' => 'sepa', 'flow_type' => 'payout', 'target_audience' => 'b2c', 'card_region' => 'all', 'percent_rate' => 0.0020, 'fixed_fee' => 0.00, 'min_fee' => 3.00, 'notes' => 'B2C Payouts'],
            ['payment_method' => 'sepa', 'flow_type' => 'payout', 'target_audience' => 'b2b', 'card_region' => 'all', 'percent_rate' => 0.0010, 'fixed_fee' => 0.00, 'min_fee' => 5.00, 'notes' => 'B2B Transfers'],

            // Internal
            ['payment_method' => 'internal', 'flow_type' => 'payout', 'target_audience' => 'all', 'card_region' => 'all', 'percent_rate' => 0.0000, 'fixed_fee' => 10.00, 'min_fee' => null, 'notes' => 'Book-to-book transfer'],

            // SWIFT
            ['payment_method' => 'swift', 'flow_type' => 'payin', 'target_audience' => 'all', 'card_region' => 'all', 'percent_rate' => 0.0035, 'fixed_fee' => 25.00, 'min_fee' => 35.00, 'notes' => 'Incoming SWIFT wire'],
            ['payment_method' => 'swift', 'flow_type' => 'payout', 'target_audience' => 'all', 'card_region' => 'all', 'percent_rate' => 0.0030, 'fixed_fee' => 35.00, 'min_fee' => 50.00, 'notes' => 'Outgoing SWIFT wire'],
        ];

        foreach ($guruPayRates as $r) {
            ProviderProcessingRate::updateOrCreate(
                [
                    'provider_id' => $guruPay->id,
                    'payment_method' => $r['payment_method'],
                    'flow_type' => $r['flow_type'],
                    'card_region' => $r['card_region'] ?? 'all',
                    'target_audience' => $r['target_audience'] ?? 'all',
                    'tier_min_volume' => $r['tier_min_volume'] ?? 0,
                ],
                array_merge($r, ['provider_id' => $guruPay->id, 'currency' => 'EUR', 'is_active' => true])
            );
        }

        // GuruPay service fees
        $guruPayFees = [
            ['fee_code' => 'onboarding', 'title' => 'Onboarding (Document review) fee', 'fee_type' => 'fixed', 'billing_period' => 'one_time', 'amount' => 1250.00, 'amount_max' => null, 'percentage' => null, 'comment' => 'One-time application & compliance setup'],
            ['fee_code' => 'api_integration', 'title' => 'API Integration Fee', 'fee_type' => 'fixed', 'billing_period' => 'one_time', 'amount' => 3000.00, 'amount_max' => null, 'percentage' => null, 'comment' => 'Gateway & webhook testing environment'],
            ['fee_code' => 'monthly_maintenance', 'title' => 'Monthly Account Maintenance', 'fee_type' => 'fixed', 'billing_period' => 'monthly', 'amount' => 200.00, 'amount_max' => null, 'percentage' => null, 'comment' => 'Base platform fee per corporate account'],
            ['fee_code' => 'monthly_iban', 'title' => 'Monthly Dedicated IBAN Fee', 'fee_type' => 'fixed', 'billing_period' => 'monthly', 'amount' => 50.00, 'amount_max' => null, 'percentage' => null, 'comment' => 'Per dedicated virtual IBAN'],
            ['fee_code' => 'chargeback', 'title' => 'Chargeback Fee', 'fee_type' => 'fixed', 'billing_period' => 'per_event', 'amount' => 25.00, 'amount_max' => null, 'percentage' => null, 'comment' => 'Per dispute notification'],
            ['fee_code' => 'fx_margin', 'title' => 'FX Margin (Cross-Currency)', 'fee_type' => 'percentage', 'billing_period' => 'per_event', 'amount' => 0.00, 'amount_max' => null, 'percentage' => 0.0200, 'comment' => '2.0% markup on ECB exchange rate'],
            ['fee_code' => 'closure_fee', 'title' => 'Account Closure Fee', 'fee_type' => 'fixed', 'billing_period' => 'one_time', 'amount' => 500.00, 'amount_max' => null, 'percentage' => null, 'comment' => 'Settlement & archive fee on closure'],
            ['fee_code' => 'audit_letter', 'title' => 'Audit Confirmation Letter', 'fee_type' => 'fixed', 'billing_period' => 'per_event', 'amount' => 150.00, 'amount_max' => null, 'percentage' => null, 'comment' => 'Official auditor confirmation request'],
        ];

        foreach ($guruPayFees as $f) {
            ProviderServiceFee::updateOrCreate(
                ['provider_id' => $guruPay->id, 'fee_code' => $f['fee_code']],
                array_merge($f, ['provider_id' => $guruPay->id, 'currency' => 'EUR'])
            );
        }

        // GuruPay Rolling Reserve
        ProviderReserve::updateOrCreate(
            ['provider_id' => $guruPay->id],
            [
                'rate_percent' => 10.00,
                'hold_period_days' => 180,
                'floor_amount' => 500.00,
                'cap_amount' => 200000.00,
                'currency' => 'EUR',
                'conditions' => 'Standard high-risk e-commerce tier. Released on 180-day rolling cycle.',
            ]
        );

        // 2. Payally
        $payally = Provider::updateOrCreate(
            ['code' => 'payally'],
            [
                'name' => 'Payally',
                'type' => 'hybrid',
                'base_currency' => 'EUR',
                'website' => 'https://payally.co.uk',
                'contact_person' => 'Rafal K.',
                'contact_email' => 'sales@payally.co.uk',
                'account_manager' => 'Elena S.',
                'notes' => 'UK/EU authorized payment institution. Competitive B2B SEPA & multicurrency accounts.',
                'is_active' => true,
            ]
        );

        // Payally processing rates
        $payallyRates = [
            // Card Pay-in
            ['payment_method' => 'card', 'flow_type' => 'payin', 'target_audience' => 'all', 'card_region' => 'eu', 'percent_rate' => 0.0350, 'fixed_fee' => 0.30, 'min_fee' => 0.50, 'notes' => 'EU Consumer cards'],
            ['payment_method' => 'card', 'flow_type' => 'payin', 'target_audience' => 'all', 'card_region' => 'non_eu', 'percent_rate' => 0.0420, 'fixed_fee' => 0.40, 'min_fee' => 0.60, 'notes' => 'Non-EU cards'],
            ['payment_method' => 'card', 'flow_type' => 'payin', 'target_audience' => 'all', 'card_region' => 'international', 'percent_rate' => 0.0420, 'fixed_fee' => 0.40, 'min_fee' => 0.60, 'notes' => 'International worldwide cards'],
            ['payment_method' => 'card', 'flow_type' => 'payin', 'target_audience' => 'all', 'card_region' => 'corporate', 'percent_rate' => 0.0440, 'fixed_fee' => 0.50, 'min_fee' => 0.70, 'notes' => 'Corporate / Business cards'],

            // SEPA Incoming (Tiers)
            ['payment_method' => 'sepa', 'flow_type' => 'payin', 'target_audience' => 'all', 'card_region' => 'all', 'percent_rate' => 0.0018, 'fixed_fee' => 0.00, 'min_fee' => 4.00, 'tier_min_volume' => 0, 'tier_max_volume' => 1000000, 'notes' => 'Tier 1: €0 - €1M / mo'],
            ['payment_method' => 'sepa', 'flow_type' => 'payin', 'target_audience' => 'all', 'card_region' => 'all', 'percent_rate' => 0.0012, 'fixed_fee' => 0.00, 'min_fee' => 4.00, 'tier_min_volume' => 1000000, 'tier_max_volume' => null, 'notes' => 'Tier 2: > €1M / mo'],

            // SEPA Outgoing
            ['payment_method' => 'sepa', 'flow_type' => 'payout', 'target_audience' => 'b2c', 'card_region' => 'all', 'percent_rate' => 0.0015, 'fixed_fee' => 0.00, 'min_fee' => 2.50, 'notes' => 'B2C SEPA payout'],
            ['payment_method' => 'sepa', 'flow_type' => 'payout', 'target_audience' => 'b2b', 'card_region' => 'all', 'percent_rate' => 0.0000, 'fixed_fee' => 50.00, 'min_fee' => null, 'notes' => 'Fixed €50 per B2B transaction'],

            // Internal
            ['payment_method' => 'internal', 'flow_type' => 'payout', 'target_audience' => 'all', 'card_region' => 'all', 'percent_rate' => 0.0000, 'fixed_fee' => 5.00, 'min_fee' => null, 'notes' => 'Internal transfer within Payally'],

            // SWIFT
            ['payment_method' => 'swift', 'flow_type' => 'payin', 'target_audience' => 'all', 'card_region' => 'all', 'percent_rate' => 0.0025, 'fixed_fee' => 20.00, 'min_fee' => 30.00, 'notes' => 'SWIFT Inbound'],
            ['payment_method' => 'swift', 'flow_type' => 'payout', 'target_audience' => 'all', 'card_region' => 'all', 'percent_rate' => 0.0025, 'fixed_fee' => 30.00, 'min_fee' => 45.00, 'notes' => 'SWIFT Outbound'],
        ];

        foreach ($payallyRates as $r) {
            ProviderProcessingRate::updateOrCreate(
                [
                    'provider_id' => $payally->id,
                    'payment_method' => $r['payment_method'],
                    'flow_type' => $r['flow_type'],
                    'card_region' => $r['card_region'] ?? 'all',
                    'target_audience' => $r['target_audience'] ?? 'all',
                    'tier_min_volume' => $r['tier_min_volume'] ?? 0,
                ],
                array_merge($r, ['provider_id' => $payally->id, 'currency' => 'EUR', 'is_active' => true])
            );
        }

        // Payally service fees
        $payallyFees = [
            ['fee_code' => 'onboarding', 'title' => 'Onboarding (Legal & KYB Review)', 'fee_type' => 'range', 'billing_period' => 'one_time', 'amount' => 800.00, 'amount_max' => 3000.00, 'percentage' => null, 'comment' => 'Depends on company jurisdiction & complexity'],
            ['fee_code' => 'monthly_maintenance', 'title' => 'Monthly Account Maintenance', 'fee_type' => 'fixed', 'billing_period' => 'monthly', 'amount' => 150.00, 'amount_max' => null, 'percentage' => null, 'comment' => 'Corporate account fee'],
            ['fee_code' => 'monthly_iban', 'title' => 'Monthly IBAN Fee', 'fee_type' => 'fixed', 'billing_period' => 'monthly', 'amount' => 35.00, 'amount_max' => null, 'percentage' => null, 'comment' => 'Per active IBAN'],
            ['fee_code' => 'chargeback', 'title' => 'Chargeback Fee', 'fee_type' => 'fixed', 'billing_period' => 'per_event', 'amount' => 30.00, 'amount_max' => null, 'percentage' => null, 'comment' => 'Per chargeback received'],
            ['fee_code' => 'fx_margin', 'title' => 'FX Margin (Cross-Currency)', 'fee_type' => 'percentage', 'billing_period' => 'per_event', 'amount' => 0.00, 'amount_max' => null, 'percentage' => 0.0075, 'comment' => '0.50% - 1.00% depending on monthly volume'],
            ['fee_code' => 'audit_letter', 'title' => 'Audit Confirmation Letter', 'fee_type' => 'fixed', 'billing_period' => 'per_event', 'amount' => 100.00, 'amount_max' => null, 'percentage' => null, 'comment' => 'Auditor balance certification'],
        ];

        foreach ($payallyFees as $f) {
            ProviderServiceFee::updateOrCreate(
                ['provider_id' => $payally->id, 'fee_code' => $f['fee_code']],
                array_merge($f, ['provider_id' => $payally->id, 'currency' => 'EUR'])
            );
        }

        // Payally Rolling Reserve
        ProviderReserve::updateOrCreate(
            ['provider_id' => $payally->id],
            [
                'rate_percent' => 8.00,
                'hold_period_days' => 180,
                'floor_amount' => 1000.00,
                'cap_amount' => 150000.00,
                'currency' => 'EUR',
                'conditions' => 'Evaluated post 3 months trading history.',
            ]
        );

        // 3. Stripe (For benchmark comparison)
        $stripe = Provider::updateOrCreate(
            ['code' => 'stripe'],
            [
                'name' => 'Stripe',
                'type' => 'acquiring',
                'base_currency' => 'EUR',
                'website' => 'https://stripe.com',
                'contact_person' => 'Online Support',
                'contact_email' => 'sales@stripe.com',
                'account_manager' => 'Self-serve / Key Account',
                'notes' => 'Global tier-1 acquiring provider for standard e-commerce with instant onboarding.',
                'is_active' => true,
            ]
        );

        $stripeRates = [
            ['payment_method' => 'card', 'flow_type' => 'payin', 'target_audience' => 'all', 'card_region' => 'eu', 'percent_rate' => 0.0150, 'fixed_fee' => 0.25, 'min_fee' => null, 'notes' => 'EEA Standard cards'],
            ['payment_method' => 'card', 'flow_type' => 'payin', 'target_audience' => 'all', 'card_region' => 'non_eu', 'percent_rate' => 0.0250, 'fixed_fee' => 0.25, 'min_fee' => null, 'notes' => 'UK / Non-EEA cards'],
            ['payment_method' => 'card', 'flow_type' => 'payin', 'target_audience' => 'all', 'card_region' => 'international', 'percent_rate' => 0.0290, 'fixed_fee' => 0.30, 'min_fee' => null, 'notes' => 'International cards'],
            ['payment_method' => 'card', 'flow_type' => 'payin', 'target_audience' => 'all', 'card_region' => 'corporate', 'percent_rate' => 0.0290, 'fixed_fee' => 0.30, 'min_fee' => null, 'notes' => 'Commercial cards'],
            ['payment_method' => 'sepa', 'flow_type' => 'payin', 'target_audience' => 'all', 'card_region' => 'all', 'percent_rate' => 0.0080, 'fixed_fee' => 0.29, 'min_fee' => null, 'max_fee' => 5.00, 'notes' => 'SEPA Direct Debit (capped at €5)'],
            ['payment_method' => 'sepa', 'flow_type' => 'payout', 'target_audience' => 'all', 'card_region' => 'all', 'percent_rate' => 0.0010, 'fixed_fee' => 0.20, 'min_fee' => null, 'notes' => 'Automated SEPA payout'],
        ];

        foreach ($stripeRates as $r) {
            ProviderProcessingRate::updateOrCreate(
                [
                    'provider_id' => $stripe->id,
                    'payment_method' => $r['payment_method'],
                    'flow_type' => $r['flow_type'],
                    'card_region' => $r['card_region'] ?? 'all',
                    'target_audience' => $r['target_audience'] ?? 'all',
                    'tier_min_volume' => 0,
                ],
                array_merge($r, ['provider_id' => $stripe->id, 'currency' => 'EUR', 'is_active' => true])
            );
        }

        $stripeFees = [
            ['fee_code' => 'onboarding', 'title' => 'Setup & Onboarding', 'fee_type' => 'fixed', 'billing_period' => 'one_time', 'amount' => 0.00, 'amount_max' => null, 'percentage' => null, 'comment' => 'Instant free setup'],
            ['fee_code' => 'monthly_maintenance', 'title' => 'Monthly Account Maintenance', 'fee_type' => 'fixed', 'billing_period' => 'monthly', 'amount' => 0.00, 'amount_max' => null, 'percentage' => null, 'comment' => 'No monthly fee for standard tier'],
            ['fee_code' => 'chargeback', 'title' => 'Chargeback / Dispute Fee', 'fee_type' => 'fixed', 'billing_period' => 'per_event', 'amount' => 15.00, 'amount_max' => null, 'percentage' => null, 'comment' => 'Refunded if dispute is won'],
            ['fee_code' => 'fx_margin', 'title' => 'FX Currency Conversion', 'fee_type' => 'percentage', 'billing_period' => 'per_event', 'amount' => 0.00, 'amount_max' => null, 'percentage' => 0.0100, 'comment' => '1.0% conversion fee'],
        ];

        foreach ($stripeFees as $f) {
            ProviderServiceFee::updateOrCreate(
                ['provider_id' => $stripe->id, 'fee_code' => $f['fee_code']],
                array_merge($f, ['provider_id' => $stripe->id, 'currency' => 'EUR'])
            );
        }

        ProviderReserve::updateOrCreate(
            ['provider_id' => $stripe->id],
            [
                'rate_percent' => 5.00,
                'hold_period_days' => 90,
                'floor_amount' => 0.00,
                'cap_amount' => 50000.00,
                'currency' => 'EUR',
                'conditions' => 'Standard risk profile. Dynamic reserve calculation.',
            ]
        );
    }
}
