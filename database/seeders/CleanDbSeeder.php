<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

class CleanDbSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Schema::disableForeignKeyConstraints();

        // 1. Delete all business/project/task data
        DB::table('tasks')->truncate();
        DB::table('credentials')->truncate();
        DB::table('reports')->truncate();
        DB::table('boardings')->truncate();
        DB::table('directors')->truncate();
        DB::table('websites')->truncate();
        DB::table('projects')->truncate();
        DB::table('clients')->truncate();
        DB::table('comments')->truncate();

        if (Schema::hasTable('activity_logs')) {
            DB::table('activity_logs')->truncate();
        }
        if (Schema::hasTable('smm_posts')) {
            DB::table('smm_posts')->truncate();
        }
        if (Schema::hasTable('reviews')) {
            DB::table('reviews')->truncate();
        }

        // 2. Ensure roles & permissions exist
        $this->call([
            RolesAndPermissionsSeeder::class,
        ]);

        // 3. Keep or create the target user
        $targetEmail = 'mihails.horolskis@gmail.com';
        $targetUser = User::where('email', $targetEmail)->first();

        if ($targetUser) {
            // Delete all other users
            User::where('id', '!=', $targetUser->id)->delete();
        } else {
            // Delete all users first
            User::query()->delete();

            // Create the target user as Admin
            $targetUser = User::create([
                'name' => 'Mihails Horolskis',
                'email' => $targetEmail,
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ]);
        }

        // Make sure this user has the admin role
        $targetUser->syncRoles(['admin']);

        Schema::enableForeignKeyConstraints();
    }
}
