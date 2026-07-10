<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

// This migration is intentionally empty.
// Companies are managed via the `projects` table (Project model).
// The separate `companies` table is not needed.
return new class extends Migration
{
    public function up(): void
    {
        // No-op: companies are stored in the projects table.
    }

    public function down(): void
    {
        Schema::dropIfExists('companies');
    }
};
