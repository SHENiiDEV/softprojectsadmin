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
        // 1. Projects table indexes
        Schema::table('projects', function (Blueprint $table) {
            $table->index('status', 'idx_projects_status');
            $table->index('archived_at', 'idx_projects_archived_at');
            $table->index('name', 'idx_projects_name');
            $table->index(['archived_at', 'status'], 'idx_projects_archived_status');
        });

        // 2. Websites table indexes
        Schema::table('websites', function (Blueprint $table) {
            $table->index('status', 'idx_websites_status');
            $table->index('visa_status', 'idx_websites_visa_status');
            $table->index('mastercard_status', 'idx_websites_mc_status');
            $table->index('url', 'idx_websites_url');
        });

        // 3. Tasks table indexes
        Schema::table('tasks', function (Blueprint $table) {
            $table->index('status', 'idx_tasks_status');
            $table->index('priority', 'idx_tasks_priority');
            $table->index('due_date', 'idx_tasks_due_date');
            $table->index(['assigned_to', 'status'], 'idx_tasks_user_status');
            $table->index(['due_date', 'status'], 'idx_tasks_due_status');
        });

        // 4. Clients table indexes
        Schema::table('clients', function (Blueprint $table) {
            $table->index('name', 'idx_clients_name');
            $table->index('hash', 'idx_clients_hash');
        });

        // 5. Credentials table indexes
        Schema::table('credentials', function (Blueprint $table) {
            $table->index('type', 'idx_credentials_type');
            $table->index('name', 'idx_credentials_name');
        });

        // 6. Directors table indexes
        Schema::table('directors', function (Blueprint $table) {
            $table->index('fee_paid_status', 'idx_directors_fee_status');
        });

        // 7. Reports table indexes
        Schema::table('reports', function (Blueprint $table) {
            $table->index('accounts_due_by', 'idx_reports_accounts_due');
            $table->index('statements_due_by', 'idx_reports_statements_due');
        });

        // 8. Task Time Logs table indexes
        Schema::table('task_time_logs', function (Blueprint $table) {
            $table->index(['user_id', 'stopped_at'], 'idx_timelogs_user_stopped');
            $table->index('started_at', 'idx_timelogs_started');
        });

        // 9. Activity Logs table indexes
        if (Schema::hasTable('activity_logs')) {
            Schema::table('activity_logs', function (Blueprint $table) {
                $table->index('created_at', 'idx_activity_logs_created');
                $table->index(['user_id', 'created_at'], 'idx_activity_user_created');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->dropIndex('idx_projects_status');
            $table->dropIndex('idx_projects_archived_at');
            $table->dropIndex('idx_projects_name');
            $table->dropIndex('idx_projects_archived_status');
        });

        Schema::table('websites', function (Blueprint $table) {
            $table->dropIndex('idx_websites_status');
            $table->dropIndex('idx_websites_visa_status');
            $table->dropIndex('idx_websites_mc_status');
            $table->dropIndex('idx_websites_url');
        });

        Schema::table('tasks', function (Blueprint $table) {
            $table->dropIndex('idx_tasks_status');
            $table->dropIndex('idx_tasks_priority');
            $table->dropIndex('idx_tasks_due_date');
            $table->dropIndex('idx_tasks_user_status');
            $table->dropIndex('idx_tasks_due_status');
        });

        Schema::table('clients', function (Blueprint $table) {
            $table->dropIndex('idx_clients_name');
            $table->dropIndex('idx_clients_hash');
        });

        Schema::table('credentials', function (Blueprint $table) {
            $table->dropIndex('idx_credentials_type');
            $table->dropIndex('idx_credentials_name');
        });

        Schema::table('directors', function (Blueprint $table) {
            $table->dropIndex('idx_directors_fee_status');
        });

        Schema::table('reports', function (Blueprint $table) {
            $table->dropIndex('idx_reports_accounts_due');
            $table->dropIndex('idx_reports_statements_due');
        });

        Schema::table('task_time_logs', function (Blueprint $table) {
            $table->dropIndex('idx_timelogs_user_stopped');
            $table->dropIndex('idx_timelogs_started');
        });

        if (Schema::hasTable('activity_logs')) {
            Schema::table('activity_logs', function (Blueprint $table) {
                $table->dropIndex('idx_activity_logs_created');
                $table->dropIndex('idx_activity_user_created');
            });
        }
    }
};
