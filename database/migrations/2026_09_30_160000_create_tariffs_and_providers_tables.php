<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Providers table
        Schema::create('providers', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->string('code', 50)->unique();
            $table->enum('type', ['acquiring', 'emi', 'hybrid'])->default('hybrid');
            $table->char('base_currency', 3)->default('EUR');
            $table->string('website', 255)->nullable();
            $table->string('contact_person', 150)->nullable();
            $table->string('contact_email', 150)->nullable();
            $table->string('account_manager', 150)->nullable();
            $table->text('notes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index('is_active');
            $table->index('type');
        });

        // 2. Processing Rates table
        Schema::create('provider_processing_rates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('provider_id')->constrained('providers')->cascadeOnDelete();
            $table->enum('payment_method', ['card', 'sepa', 'swift', 'crypto', 'internal']);
            $table->enum('flow_type', ['payin', 'payout', 'refund'])->default('payin');
            $table->enum('target_audience', ['b2b', 'b2c', 'c2b', 'all'])->default('all');
            $table->enum('card_region', ['eu', 'non_eu', 'international', 'corporate', 'all'])->default('all');

            // Fee model
            $table->decimal('percent_rate', 6, 4)->default(0.0000); // 4.2% = 0.0420
            $table->decimal('fixed_fee', 10, 2)->default(0.00);     // Fixed per tx
            $table->decimal('min_fee', 10, 2)->nullable();          // Min fee
            $table->decimal('max_fee', 10, 2)->nullable();          // Max fee cap
            $table->char('currency', 3)->default('EUR');

            // Volume Tier
            $table->decimal('tier_min_volume', 15, 2)->default(0.00);
            $table->decimal('tier_max_volume', 15, 2)->nullable();

            $table->boolean('is_active')->default(true);
            $table->string('notes', 255)->nullable();
            $table->timestamps();

            $table->index(['provider_id', 'payment_method', 'flow_type', 'is_active'], 'ppr_lookup_idx');
        });

        // 3. Service Fees table
        Schema::create('provider_service_fees', function (Blueprint $table) {
            $table->id();
            $table->foreignId('provider_id')->constrained('providers')->cascadeOnDelete();
            $table->string('fee_code', 50); // onboarding, monthly_maintenance, monthly_iban, chargeback, etc.
            $table->string('title', 150);
            $table->enum('fee_type', ['fixed', 'percentage', 'range'])->default('fixed');
            $table->enum('billing_period', ['one_time', 'monthly', 'yearly', 'per_event'])->default('one_time');

            $table->decimal('amount', 10, 2)->default(0.00);
            $table->decimal('amount_max', 10, 2)->nullable();
            $table->decimal('percentage', 6, 4)->nullable();
            $table->char('currency', 3)->default('EUR');

            $table->text('comment')->nullable();
            $table->timestamps();

            $table->index(['provider_id', 'fee_code']);
        });

        // 4. Reserves table
        Schema::create('provider_reserves', function (Blueprint $table) {
            $table->id();
            $table->foreignId('provider_id')->constrained('providers')->cascadeOnDelete();
            $table->decimal('rate_percent', 5, 2)->default(10.00);
            $table->unsignedInteger('hold_period_days')->default(180);
            $table->decimal('floor_amount', 10, 2)->nullable();
            $table->decimal('cap_amount', 12, 2)->nullable();
            $table->char('currency', 3)->default('EUR');
            $table->text('conditions')->nullable();
            $table->timestamps();

            $table->index('provider_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('provider_reserves');
        Schema::dropIfExists('provider_service_fees');
        Schema::dropIfExists('provider_processing_rates');
        Schema::dropIfExists('providers');
    }
};
