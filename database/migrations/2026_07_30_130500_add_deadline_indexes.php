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
            $table->index(['project_id', 'due_date', 'status'], 'idx_tasks_proj_due_status');
        });

        Schema::table('reports', function (Blueprint $table) {
            $table->index(['project_id', 'accounts_due_by'], 'idx_reports_proj_accounts');
            $table->index(['project_id', 'statements_due_by'], 'idx_reports_proj_statements');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->dropIndex('idx_tasks_proj_due_status');
        });

        Schema::table('reports', function (Blueprint $table) {
            $table->dropIndex('idx_reports_proj_accounts');
            $table->dropIndex('idx_reports_proj_statements');
        });
    }
};
