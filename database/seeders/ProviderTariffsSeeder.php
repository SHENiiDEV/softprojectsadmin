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
     * Run the database seeds with accurate tariffs from official provider sheets.
     */
    public function run(): void
    {
        // -------------------------------------------------------------------------
        // 1. GURUPAY (European Licensed EMI & Acquiring Institution)
        // -------------------------------------------------------------------------
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
                'notes' => 'EU-licensed Electronic Money Institution. Direct acquiring into dedicated merchant IBAN with no third-party hops.',
                'is_active' => true,
            ]
        );

        // GuruPay: Card Processing & Banking Rates
        $guruPayRates = [
            // Card Acquiring (Visa / Mastercard)
            [
                'payment_method' => 'card',
                'flow_type' => 'payin',
                'target_audience' => 'all',
                'card_region' => 'eu',
                'percent_rate' => 0.0420,
                'fixed_fee' => 0.00,
                'min_fee' => 0.60,
                'notes' => 'Card processing, EU Visa / Mastercard (min €0.60/tx)',
            ],
            [
                'payment_method' => 'card',
                'flow_type' => 'payin',
                'target_audience' => 'all',
                'card_region' => 'non_eu',
                'percent_rate' => 0.0470,
                'fixed_fee' => 0.00,
                'min_fee' => 0.70,
                'notes' => 'Card processing, non-EU International cards Visa / Mastercard (min €0.70/tx)',
            ],
            [
                'payment_method' => 'card',
                'flow_type' => 'payin',
                'target_audience' => 'all',
                'card_region' => 'international',
                'percent_rate' => 0.0470,
                'fixed_fee' => 0.00,
                'min_fee' => 0.70,
                'notes' => 'Card processing, non-EU International cards Visa / Mastercard (min €0.70/tx)',
            ],
            [
                'payment_method' => 'card',
                'flow_type' => 'payin',
                'target_audience' => 'all',
                'card_region' => 'corporate',
                'percent_rate' => 0.0470,
                'fixed_fee' => 0.00,
                'min_fee' => 0.70,
                'notes' => 'Corporate / Premium Cards Visa / Mastercard (min €0.70/tx)',
            ],

            // SEPA C2B & B2C
            [
                'payment_method' => 'sepa',
                'flow_type' => 'payin',
                'target_audience' => 'c2b',
                'card_region' => 'all',
                'percent_rate' => 0.0025,
                'fixed_fee' => 0.00,
                'min_fee' => 1.00,
                'tier_min_volume' => 0,
                'tier_max_volume' => null,
                'notes' => 'C2B Incoming SEPA payments (0.25%, min €1.00)',
            ],
            [
                'payment_method' => 'sepa',
                'flow_type' => 'payout',
                'target_audience' => 'b2c',
                'card_region' => 'all',
                'percent_rate' => 0.0020,
                'fixed_fee' => 0.00,
                'min_fee' => 3.00,
                'tier_min_volume' => 0,
                'tier_max_volume' => null,
                'notes' => 'B2C Outgoing SEPA payments (0.20%, min €3.00)',
            ],

            // SEPA B2B Incoming (Volume Tiers: 0-2M, 2-5M, >5M with min €5)
            [
                'payment_method' => 'sepa',
                'flow_type' => 'payin',
                'target_audience' => 'b2b',
                'card_region' => 'all',
                'percent_rate' => 0.0020,
                'fixed_fee' => 0.00,
                'min_fee' => 5.00,
                'tier_min_volume' => 0,
                'tier_max_volume' => 2000000,
                'notes' => 'B2B Incoming SEPA (Tier 0 – 2M: 0.20%, min €5.00)',
            ],
            [
                'payment_method' => 'sepa',
                'flow_type' => 'payin',
                'target_audience' => 'b2b',
                'card_region' => 'all',
                'percent_rate' => 0.0015,
                'fixed_fee' => 0.00,
                'min_fee' => 5.00,
                'tier_min_volume' => 2000000,
                'tier_max_volume' => 5000000,
                'notes' => 'B2B Incoming SEPA (Tier 2M – 5M: 0.15%, min €5.00)',
            ],
            [
                'payment_method' => 'sepa',
                'flow_type' => 'payin',
                'target_audience' => 'b2b',
                'card_region' => 'all',
                'percent_rate' => 0.0010,
                'fixed_fee' => 0.00,
                'min_fee' => 5.00,
                'tier_min_volume' => 5000000,
                'tier_max_volume' => null,
                'notes' => 'B2B Incoming SEPA (Tier > 5M: 0.10%, min €5.00)',
            ],

            // SEPA B2B Outgoing
            [
                'payment_method' => 'sepa',
                'flow_type' => 'payout',
                'target_audience' => 'b2b',
                'card_region' => 'all',
                'percent_rate' => 0.0010,
                'fixed_fee' => 0.00,
                'min_fee' => 5.00,
                'tier_min_volume' => 0,
                'tier_max_volume' => null,
                'notes' => 'B2B Outgoing SEPA payments (0.10%, min €5.00)',
            ],

            // Internal transfers between own accounts
            [
                'payment_method' => 'internal',
                'flow_type' => 'payout',
                'target_audience' => 'all',
                'card_region' => 'all',
                'percent_rate' => 0.0000,
                'fixed_fee' => 10.00,
                'min_fee' => null,
                'notes' => 'Internal transfers between own accounts (Fixed €10.00/tx)',
            ],
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

        // GuruPay Service & Ancillary Fees (Matching official sheet)
        $guruPayFees = [
            ['fee_code' => 'onboarding', 'title' => 'Onboarding (Document review) fee', 'fee_type' => 'fixed', 'billing_period' => 'one_time', 'amount' => 1250.00, 'amount_max' => null, 'percentage' => null, 'comment' => 'One-time onboarding and document review'],
            ['fee_code' => 'api_integration', 'title' => 'API integration and support fee', 'fee_type' => 'fixed', 'billing_period' => 'one_time', 'amount' => 3000.00, 'amount_max' => null, 'percentage' => null, 'comment' => 'Gateway integration, webhook setup and technical support'],
            ['fee_code' => 'monthly_iban', 'title' => 'Monthly fee for each IBAN', 'fee_type' => 'fixed', 'billing_period' => 'monthly', 'amount' => 200.00, 'amount_max' => null, 'percentage' => null, 'comment' => 'Dedicated merchant IBAN maintenance with same-entity settlement'],
            ['fee_code' => 'fx_margin', 'title' => 'FX margin (Non-settlement currencies)', 'fee_type' => 'percentage', 'billing_period' => 'per_event', 'amount' => 0.00, 'amount_max' => null, 'percentage' => 0.0200, 'comment' => 'Applies when settlement currency differs from transaction currency'],
            ['fee_code' => 'chargeback', 'title' => 'Chargeback fee', 'fee_type' => 'fixed', 'billing_period' => 'per_event', 'amount' => 25.00, 'amount_max' => null, 'percentage' => null, 'comment' => 'Per disputed transaction, charged regardless of outcome (representment: €25-100)'],
            ['fee_code' => 'holding_funds', 'title' => 'Yearly fee for holding funds (balance >= €30,000)', 'fee_type' => 'percentage', 'billing_period' => 'yearly', 'amount' => 0.00, 'amount_max' => null, 'percentage' => 0.0070, 'comment' => '0.7% yearly fee on balances equivalent or higher than €30,000'],
            ['fee_code' => 'closure_fee', 'title' => 'Closing a Current Account', 'fee_type' => 'fixed', 'billing_period' => 'one_time', 'amount' => 150.00, 'amount_max' => null, 'percentage' => null, 'comment' => 'Account closing and archive fee'],
            ['fee_code' => 'file_update', 'title' => 'Updates to client file (UBO / authorized rep change)', 'fee_type' => 'fixed', 'billing_period' => 'per_event', 'amount' => 250.00, 'amount_max' => null, 'percentage' => null, 'comment' => 'Legal and compliance re-verification'],
            ['fee_code' => 'bank_letter', 'title' => 'Bank letters (Reference / Auditor letters)', 'fee_type' => 'fixed', 'billing_period' => 'per_event', 'amount' => 30.00, 'amount_max' => null, 'percentage' => null, 'comment' => 'Standard reference letters and letters to auditors'],
            ['fee_code' => 'bank_letter_apostille', 'title' => 'Bank letters under Apostille', 'fee_type' => 'fixed', 'billing_period' => 'per_event', 'amount' => 150.00, 'amount_max' => null, 'percentage' => null, 'comment' => 'Official notarized & apostilled document'],
            ['fee_code' => 'account_statement', 'title' => 'Account statement (Stamped)', 'fee_type' => 'fixed', 'billing_period' => 'per_event', 'amount' => 5.00, 'amount_max' => null, 'percentage' => null, 'comment' => 'Official stamped PDF/paper bank statement'],
            ['fee_code' => 'deadline_penalty', 'title' => 'Penalty for failing to provide documentation by deadline', 'fee_type' => 'fixed', 'billing_period' => 'per_event', 'amount' => 150.00, 'amount_max' => null, 'percentage' => null, 'comment' => 'Applied when requested KYC/KYB documents are not submitted in time'],
        ];

        foreach ($guruPayFees as $f) {
            ProviderServiceFee::updateOrCreate(
                ['provider_id' => $guruPay->id, 'fee_code' => $f['fee_code']],
                array_merge($f, ['provider_id' => $guruPay->id, 'currency' => 'EUR'])
            );
        }

        // GuruPay: Rolling Reserve
        ProviderReserve::updateOrCreate(
            ['provider_id' => $guruPay->id],
            [
                'rate_percent' => 10.00,
                'hold_period_days' => 180,
                'floor_amount' => 500.00,
                'cap_amount' => 200000.00,
                'currency' => 'EUR',
                'conditions' => 'Held for 180 days. Subject to €500 floor and €200,000 cap. Applied per merchant risk tier.',
            ]
        );

        // -------------------------------------------------------------------------
        // 2. PAYALLY GLOBAL (UK/EU Payment Institution & Business Banking)
        // -------------------------------------------------------------------------
        $payally = Provider::updateOrCreate(
            ['code' => 'payally'],
            [
                'name' => 'Payally',
                'type' => 'hybrid',
                'base_currency' => 'EUR',
                'website' => 'https://payally.com',
                'contact_person' => 'Relationship Manager',
                'contact_email' => 'info@payally.com',
                'account_manager' => 'Rafal K.',
                'notes' => 'Payally Limited (London, UK). Multi-currency business accounts, local GBP/PLN rails, SEPA, SWIFT, crypto digital exchange and corporate Mastercard.',
                'is_active' => true,
            ]
        );

        // Payally Rates (Matching official sheet page 1 & 2)
        $payallyRates = [
            // Card top up / processing
            [
                'payment_method' => 'card',
                'flow_type' => 'payin',
                'target_audience' => 'all',
                'card_region' => 'eu',
                'percent_rate' => 0.0050,
                'fixed_fee' => 0.00,
                'min_fee' => null,
                'notes' => 'Account top up by card fee (0.5%)',
            ],
            [
                'payment_method' => 'card',
                'flow_type' => 'payin',
                'target_audience' => 'all',
                'card_region' => 'non_eu',
                'percent_rate' => 0.0050,
                'fixed_fee' => 0.00,
                'min_fee' => null,
                'notes' => 'Account top up by card fee (0.5%)',
            ],

            // Incoming payments
            [
                'payment_method' => 'sepa',
                'flow_type' => 'payin',
                'target_audience' => 'all',
                'card_region' => 'all',
                'percent_rate' => 0.0025, // 0 - 0.5% (avg 0.25%)
                'fixed_fee' => 20.00,
                'min_fee' => 20.00,
                'notes' => 'External fiat per transaction (€20 + 0 - 0.5%)',
            ],
            [
                'payment_method' => 'internal',
                'flow_type' => 'payin',
                'target_audience' => 'all',
                'card_region' => 'all',
                'percent_rate' => 0.0025,
                'fixed_fee' => 0.00,
                'min_fee' => null,
                'notes' => 'Internal fiat per transaction (0 - 0.5%)',
            ],
            [
                'payment_method' => 'crypto',
                'flow_type' => 'payin',
                'target_audience' => 'all',
                'card_region' => 'all',
                'percent_rate' => 0.0050, // 0 - 1%
                'fixed_fee' => 0.00,
                'min_fee' => null,
                'notes' => 'External USDT / USDC per transaction (0 - 1%)',
            ],

            // Outgoing payments
            [
                'payment_method' => 'sepa',
                'flow_type' => 'payout',
                'target_audience' => 'b2b',
                'card_region' => 'all',
                'percent_rate' => 0.0000,
                'fixed_fee' => 50.00,
                'min_fee' => null,
                'notes' => 'Outgoing SEPA transfer (Fixed €50.00)',
            ],
            [
                'payment_method' => 'swift',
                'flow_type' => 'payout',
                'target_audience' => 'b2b',
                'card_region' => 'all',
                'percent_rate' => 0.0000,
                'fixed_fee' => 80.00,
                'min_fee' => null,
                'notes' => 'Outgoing SWIFT transfer (Fixed €80.00)',
            ],
            [
                'payment_method' => 'internal',
                'flow_type' => 'payout',
                'target_audience' => 'all',
                'card_region' => 'all',
                'percent_rate' => 0.0000,
                'fixed_fee' => 5.00,
                'min_fee' => null,
                'notes' => 'Fiat internal transfer (Fixed €5.00)',
            ],
            [
                'payment_method' => 'crypto',
                'flow_type' => 'payout',
                'target_audience' => 'all',
                'card_region' => 'all',
                'percent_rate' => 0.0050, // 0 - 1%
                'fixed_fee' => 0.00,
                'min_fee' => null,
                'notes' => 'Digital external USDC / USDT payout (0 - 1%)',
            ],
        ];

        foreach ($payallyRates as $r) {
            ProviderProcessingRate::updateOrCreate(
                [
                    'provider_id' => $payally->id,
                    'payment_method' => $r['payment_method'],
                    'flow_type' => $r['flow_type'],
                    'card_region' => $r['card_region'] ?? 'all',
                    'target_audience' => $r['target_audience'] ?? 'all',
                    'tier_min_volume' => 0,
                ],
                array_merge($r, ['provider_id' => $payally->id, 'currency' => 'EUR', 'is_active' => true])
            );
        }

        // Payally Service Fees (Exact values from sheet)
        $payallyFees = [
            // Admittance & Maintenance
            ['fee_code' => 'onboarding', 'title' => 'Account opening (Admittance)', 'fee_type' => 'range', 'billing_period' => 'one_time', 'amount' => 800.00, 'amount_max' => 3000.00, 'percentage' => null, 'comment' => '€800 – €3,000 EUR based on due diligence complexity'],
            ['fee_code' => 'monthly_maintenance', 'title' => 'Monthly maintenance fee', 'fee_type' => 'range', 'billing_period' => 'monthly', 'amount' => 0.00, 'amount_max' => 10000.00, 'percentage' => null, 'comment' => '€0 – €10,000 EUR tailored based on business volume'],

            // Currency & Digital exchange
            ['fee_code' => 'fx_main', 'title' => 'FX Commission (EUR, USD, GBP)', 'fee_type' => 'percentage', 'billing_period' => 'per_event', 'amount' => 0.00, 'amount_max' => null, 'percentage' => 0.0075, 'comment' => '0.5% – 1% commission on major currencies'],
            ['fee_code' => 'fx_other', 'title' => 'FX Commission (Other currencies)', 'fee_type' => 'percentage', 'billing_period' => 'per_event', 'amount' => 0.00, 'amount_max' => null, 'percentage' => 0.0150, 'comment' => '0.5% – 3% commission on exotic currency pairs'],
            ['fee_code' => 'fx_delayed', 'title' => 'Delayed FX rate booking fee', 'fee_type' => 'percentage', 'billing_period' => 'per_event', 'amount' => 0.00, 'amount_max' => null, 'percentage' => 0.0500, 'comment' => '5% fixed booking fee for forward/delayed FX rates'],
            ['fee_code' => 'crypto_exchange', 'title' => 'Digital exchange commission (Crypto)', 'fee_type' => 'percentage', 'billing_period' => 'per_event', 'amount' => 0.00, 'amount_max' => null, 'percentage' => 0.0070, 'comment' => '0.4% – 1% for crypto-to-fiat digital exchange'],

            // Other service charges
            ['fee_code' => 'account_closing', 'title' => 'Account closing fee', 'fee_type' => 'fixed', 'billing_period' => 'one_time', 'amount' => 200.00, 'amount_max' => null, 'percentage' => null, 'comment' => '€200 EUR per closed business account'],
            ['fee_code' => 'account_unblock', 'title' => 'Account unblock fee', 'fee_type' => 'fixed', 'billing_period' => 'per_event', 'amount' => 100.00, 'amount_max' => null, 'percentage' => null, 'comment' => '100 EUR / 100 GBP per unblock request'],
            ['fee_code' => 'auditor_letter', 'title' => 'Auditor letter', 'fee_type' => 'fixed', 'billing_period' => 'per_event', 'amount' => 115.00, 'amount_max' => null, 'percentage' => null, 'comment' => '€115 EUR per audit confirmation letter'],
            ['fee_code' => 'swift_investigation', 'title' => 'Investigation of dispatched SWIFT payment orders', 'fee_type' => 'fixed', 'billing_period' => 'per_event', 'amount' => 80.00, 'amount_max' => null, 'percentage' => null, 'comment' => '€80 EUR for trace / amendment / recall of dispatched wire'],
            ['fee_code' => 'dormant_fee', 'title' => 'Dormant account fee', 'fee_type' => 'fixed', 'billing_period' => 'monthly', 'amount' => 300.00, 'amount_max' => null, 'percentage' => null, 'comment' => '€300 EUR per month on inactive / dormant accounts'],
            ['fee_code' => 'fast_track', 'title' => 'FastTrack EUR services per request', 'fee_type' => 'fixed', 'billing_period' => 'per_event', 'amount' => 200.00, 'amount_max' => null, 'percentage' => null, 'comment' => '200 EUR / 200 GBP for expedited processing'],
            ['fee_code' => 'ref_letter_tailored', 'title' => 'Reference letter (Tailored)', 'fee_type' => 'fixed', 'billing_period' => 'per_event', 'amount' => 80.00, 'amount_max' => null, 'percentage' => null, 'comment' => '80 EUR tailored reference letter (50 EUR standard)'],
            ['fee_code' => 'swift_tracker', 'title' => 'SWIFT Tracker / Certificate', 'fee_type' => 'fixed', 'billing_period' => 'per_event', 'amount' => 30.00, 'amount_max' => null, 'percentage' => null, 'comment' => '30 EUR tracker / 50 EUR confirmation certificate'],

            // Debit Mastercard (Plastic & Virtual)
            ['fee_code' => 'card_plastic_order', 'title' => 'Debit Mastercard Plastic (First Card Order)', 'fee_type' => 'fixed', 'billing_period' => 'one_time', 'amount' => 5.00, 'amount_max' => null, 'percentage' => null, 'comment' => '5 EUR / 5 GBP / 5 USD. Monthly fee: Free (EUR/GBP)'],
            ['fee_code' => 'card_virtual_order', 'title' => 'Debit Mastercard Virtual (Order fee)', 'fee_type' => 'fixed', 'billing_period' => 'one_time', 'amount' => 1.00, 'amount_max' => null, 'percentage' => null, 'comment' => '1 EUR / 1 GBP / 1 USD. Monthly fee: Free (EUR/GBP)'],
            ['fee_code' => 'card_atm_withdrawal', 'title' => 'Mastercard Cash withdrawal (ATM)', 'fee_type' => 'percentage', 'billing_period' => 'per_event', 'amount' => 2.00, 'amount_max' => null, 'percentage' => 0.0200, 'comment' => '2%, min 2 EUR / GBP'],
        ];

        foreach ($payallyFees as $f) {
            ProviderServiceFee::updateOrCreate(
                ['provider_id' => $payally->id, 'fee_code' => $f['fee_code']],
                array_merge($f, ['provider_id' => $payally->id, 'currency' => 'EUR'])
            );
        }

        // Payally Reserve
        ProviderReserve::updateOrCreate(
            ['provider_id' => $payally->id],
            [
                'rate_percent' => 5.00,
                'hold_period_days' => 180,
                'floor_amount' => 1000.00,
                'cap_amount' => 100000.00,
                'currency' => 'EUR',
                'conditions' => 'Evaluated individually upon completed due diligence & risk assessment.',
            ]
        );
    }
}
