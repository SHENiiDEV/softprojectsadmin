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
        Schema::create('boardings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->unique()->constrained('projects')->cascadeOnDelete();
            $table->date('kyb_completed_at')->nullable();
            $table->date('boarding_completed_at')->nullable();
            $table->string('cfs_verification')->default('need_to_complete');
            $table->string('cardaq_sumsub')->default('pending');
            $table->string('bank_verification')->default('not_started');
            $table->string('companies_house_verification')->default('not_started');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('boardings');
    }
};
