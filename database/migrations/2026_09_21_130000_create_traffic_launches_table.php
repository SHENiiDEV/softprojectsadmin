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
        Schema::create('traffic_launches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained()->cascadeOnDelete();
            $table->foreignId('project_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('website_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('task_id')->nullable()->constrained()->nullOnDelete();
            $table->string('target_month')->index();
            $table->string('date_range')->nullable();
            $table->string('domain')->index();
            $table->string('plan')->nullable();
            $table->text('geo')->nullable();
            $table->string('bounce_rate')->nullable();
            $table->string('pages')->nullable();
            $table->string('time_on_page')->nullable();
            $table->string('referral_traf')->nullable();
            $table->text('referral_links')->nullable();
            $table->string('social_traf')->nullable();
            $table->text('social_links')->nullable();
            $table->string('organic_traf')->nullable();
            $table->string('direct_traf')->nullable();
            $table->text('keywords')->nullable();
            $table->text('comment')->nullable();
            $table->string('status')->default('Pending');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('traffic_launches');
    }
};
