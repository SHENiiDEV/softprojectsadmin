<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $taskIds = DB::table('support_tickets')
            ->whereNotNull('task_id')
            ->pluck('task_id');

        if ($taskIds->isNotEmpty()) {
            DB::table('tasks')
                ->whereIn('id', $taskIds)
                ->where('status', 'todo')
                ->update(['status' => 'email_inbox']);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('tasks')
            ->where('status', 'email_inbox')
            ->update(['status' => 'todo']);
    }
};
