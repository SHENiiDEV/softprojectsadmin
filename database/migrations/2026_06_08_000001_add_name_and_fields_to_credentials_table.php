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
        Schema::table('credentials', function (Blueprint $table) {
            $table->string('name')->nullable()->after('website_id');
            $table->json('fields')->nullable()->after('password');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('credentials', function (Blueprint $table) {
            $table->dropColumn(['name', 'fields']);
        });
    }
};
