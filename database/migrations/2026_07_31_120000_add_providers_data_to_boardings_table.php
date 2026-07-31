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
            $table->json('providers_data')->nullable()->after('provider_verification_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('boardings', function (Blueprint $table) {
            $table->dropColumn('providers_data');
        });
    }
};
