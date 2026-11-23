<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->string('type', 40)->default('general')->after('priority')->index();
            $table->string('onboarding_status', 40)->nullable()->after('type');
            $table->text('current_status')->nullable()->after('onboarding_status');
        });
    }

    public function down(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->dropIndex(['type']);
            $table->dropColumn(['type', 'onboarding_status', 'current_status']);
        });
    }
};
