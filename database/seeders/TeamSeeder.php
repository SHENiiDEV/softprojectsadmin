<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

class TeamSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Schema::disableForeignKeyConstraints();
        DB::table('users')->truncate();
        Schema::enableForeignKeyConstraints();

        // Call Roles and Permissions Seeder to ensure Spatie roles exist
        $this->call([
            RolesAndPermissionsSeeder::class,
        ]);

        // Create all team members
        $team = [
            'Nikita' => ['role' => 'curator', 'email' => 'nikita@projecthub.com'],
            'David' => ['role' => 'curator', 'email' => 'david@projecthub.com'],
            'Mikita' => ['role' => 'curator', 'email' => 'mikita@projecthub.com'],
            'Igor' => ['role' => 'curator', 'email' => 'igor@projecthub.com'],
            'Vito IT' => ['role' => 'manager', 'email' => 'vito@projecthub.com'],
            'Zahar' => ['role' => 'manager', 'email' => 'zahar@projecthub.com'],
            'Jet' => ['role' => 'manager', 'email' => 'jet@projecthub.com'],
            'Romanians' => ['role' => 'manager', 'email' => 'romanians@projecthub.com'],
            'Timur' => ['role' => 'manager', 'email' => 'timur@projecthub.com'],
            'Mila UK' => ['role' => 'manager', 'email' => 'mila@projecthub.com'],
            'Vika' => ['role' => 'manager', 'email' => 'vika@projecthub.com'],
            'Venkata UK' => ['role' => 'manager', 'email' => 'venkata@projecthub.com'],
            'Zaks' => ['role' => 'manager', 'email' => 'zaks@projecthub.com'],
            'Daniel Est' => ['role' => 'manager', 'email' => 'daniel.est@projecthub.com'],
            'Kevin' => ['role' => 'manager', 'email' => 'kevin@projecthub.com'],
            'Renat' => ['role' => 'manager', 'email' => 'renat@projecthub.com'],
            'Gleb' => ['role' => 'manager', 'email' => 'gleb@projecthub.com'],
            'Elizabeth' => ['role' => 'manager', 'email' => 'elizabeth@projecthub.com'],
            'Jambulat' => ['role' => 'manager', 'email' => 'jambulat@projecthub.com'],
            'Jacob' => ['role' => 'manager', 'email' => 'jacob@projecthub.com'],
            'Admin' => ['role' => 'admin', 'email' => 'admin@projecthub.com'],
        ];

        foreach ($team as $name => $info) {
            $user = User::create([
                'name' => $name,
                'email' => $info['email'],
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ]);
            $user->assignRole($info['role']);
        }
    }
}
