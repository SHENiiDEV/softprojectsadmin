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
        Schema::table('boardings', function (Blueprint $table) {
            $table->string('provider_name')->nullable()->default('Cardaq')->after('project_id');
            $table->date('provider_boarding_completed_at')->nullable()->after('boarding_completed_at');
            $table->string('provider_verification_status')->nullable()->default('pending')->after('cfs_verification');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('boardings', function (Blueprint $table) {
            $table->dropColumn(['provider_name', 'provider_boarding_completed_at', 'provider_verification_status']);
        });
    }
};
