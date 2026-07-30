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
        Schema::table('tasks', function (Blueprint $table) {
            $table->timestamp('archived_at')->nullable()->after('due_date');

            // Performance indexes for fast archiving & company filtering
            $table->index(['archived_at', 'status', 'project_id'], 'idx_tasks_archived_status_proj');
            $table->index(['archived_at', 'assigned_to'], 'idx_tasks_archived_user');
            $table->index(['status', 'updated_at'], 'idx_tasks_status_updated');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->dropIndex('idx_tasks_archived_status_proj');
            $table->dropIndex('idx_tasks_archived_user');
            $table->dropIndex('idx_tasks_status_updated');
            $table->dropColumn('archived_at');
        });
    }
};
