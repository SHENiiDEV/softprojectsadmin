<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('task_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('task_id')->constrained('tasks')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->timestamps();

            $table->unique(['task_id', 'user_id']);
        });

        // Migrate existing assigned_to values into task_user pivot table
        $existing = DB::table('tasks')
            ->whereNotNull('assigned_to')
            ->select('id as task_id', 'assigned_to as user_id', 'created_at', 'updated_at')
            ->get();

        foreach ($existing as $row) {
            DB::table('task_user')->updateOrInsert(
                ['task_id' => $row->task_id, 'user_id' => $row->user_id],
                [
                    'created_at' => $row->created_at ?? now(),
                    'updated_at' => $row->updated_at ?? now(),
                ]
            );
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('task_user');
    }
};
