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
        // 1. Create websites table
        Schema::create('websites', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained('projects')->cascadeOnDelete();
            $table->string('name');
            $table->string('url');
            $table->timestamps();
        });

        // 2. Add website_id to credentials
        Schema::table('credentials', function (Blueprint $table) {
            $table->foreignId('website_id')->nullable()->constrained('websites')->nullOnDelete();
        });

        // 3. Migrate existing websites data from projects.website to websites table
        $projects = DB::table('projects')->whereNotNull('website')->get();
        foreach ($projects as $project) {
            DB::table('websites')->insert([
                'project_id' => $project->id,
                'name' => 'Основной сайт',
                'url' => $project->website,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // 4. Drop website column from projects
        Schema::table('projects', function (Blueprint $table) {
            $table->dropColumn('website');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // 1. Re-add website column to projects
        Schema::table('projects', function (Blueprint $table) {
            $table->string('website')->nullable();
        });

        // 2. Restore primary website URL from websites to projects table
        $websites = DB::table('websites')->where('name', 'Основной сайт')->get();
        foreach ($websites as $web) {
            DB::table('projects')->where('id', $web->project_id)->update([
                'website' => $web->url,
            ]);
        }

        // 3. Drop website_id column from credentials
        Schema::table('credentials', function (Blueprint $table) {
            $table->dropConstrainedForeignId('website_id');
        });

        // 4. Drop websites table
        Schema::dropIfExists('websites');
    }
};
