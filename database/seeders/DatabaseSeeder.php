<?php

namespace Database\Seeders;

use App\Models\Boarding;
use App\Models\Client;
use App\Models\Credential;
use App\Models\Director;
use App\Models\Project;
use App\Models\Report;
use App\Models\Task;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // 1. Truncate all tables to allow clean re-seeding
        Schema::disableForeignKeyConstraints();
        DB::table('tasks')->truncate();
        DB::table('credentials')->truncate();
        DB::table('reports')->truncate();
        DB::table('boardings')->truncate();
        DB::table('directors')->truncate();
        DB::table('websites')->truncate();
        DB::table('projects')->truncate();
        DB::table('clients')->truncate();
        DB::table('model_has_roles')->truncate();
        DB::table('model_has_permissions')->truncate();
        DB::table('role_has_permissions')->truncate();
        DB::table('roles')->truncate();
        DB::table('permissions')->truncate();
        DB::table('users')->truncate();
        if (Schema::hasTable('activity_logs')) {
            DB::table('activity_logs')->truncate();
        }
        Schema::enableForeignKeyConstraints();

        // 2. Call Spatie Roles and Permissions Seeder
        $this->call([
            RolesAndPermissionsSeeder::class,
        ]);

        // 3. Create all specialized team members with proper roles
        $team = [
            'Nikita' => ['role' => 'curator', 'email' => 'nikita@projecthub.com'],
            'David' => ['role' => 'curator', 'email' => 'david@projecthub.com'],
            'Kevin' => ['role' => 'manager', 'email' => 'kevin@projecthub.com'],
            'Renat' => ['role' => 'manager', 'email' => 'renat@projecthub.com'],
            'Gleb' => ['role' => 'manager', 'email' => 'gleb@projecthub.com'],
            'Vito IT' => ['role' => 'manager', 'email' => 'vito@projecthub.com'],
            'Zahar' => ['role' => 'manager', 'email' => 'zahar@projecthub.com'],
            'Venkata UK' => ['role' => 'manager', 'email' => 'venkata@projecthub.com'],
            'Timur' => ['role' => 'manager', 'email' => 'timur@projecthub.com'],
            'Jacob' => ['role' => 'manager', 'email' => 'jacob@projecthub.com'],
            'Jambulat' => ['role' => 'manager', 'email' => 'jambulat@projecthub.com'],
        ];

        $users = [];
        foreach ($team as $name => $info) {
            $user = User::create([
                'name' => $name,
                'email' => $info['email'],
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ]);
            $user->assignRole($info['role']);
            $users[$name] = $user;
        }

        // 4. Create Clients
        $clientKudret = Client::create([
            'name' => 'Kudret - G',
            'hash' => Str::random(32),
        ]);

        $clientMarvli = Client::create([
            'name' => 'Marvli - R',
            'hash' => Str::random(32),
        ]);

        // 5. Seed Projects / Companies & related models

        // PROTECT YOUR SPACE LIMITED
        $p1 = Project::create([
            'name' => 'PROTECT YOUR SPACE LIMITED',
            'status' => 'active',
            'ubo' => 'Vita Vitale',
            'client_id' => $clientKudret->id,
            'manager_id' => $users['Kevin']->id ?? null,
            'phones' => ['Krisp' => '44 7426 932344', 'Zadarma' => '441174790452'],
            'emails' => [],
            'notes' => "Order Notification System: TRUE\nPayment Check: TRUE\nDD: F\nComment: PERS CODE CH\nWeb.dev: Nikita",
        ]);
        $p1->websites()->create([
            'name' => 'Main Website',
            'url' => 'https://goldenerafinds.co.uk',
            'status' => 'Live',
        ]);
        Director::create([
            'project_id' => $p1->id,
            'name' => 'Vita Vitale',
            'fee_paid_status' => 'paid',
            'managed_by' => $users['Vito IT']->id ?? null,
        ]);
        Boarding::create([
            'project_id' => $p1->id,
            'kyb_completed_at' => now(),
            'boarding_completed_at' => now(),
            'cfs_verification' => 'completed',
            'cardaq_sumsub' => 'completed',
            'bank_verification' => 'completed',
            'companies_house_verification' => 'completed',
        ]);
        Report::create([
            'project_id' => $p1->id,
            'reg_number' => '12345678',
            'accounts_due_by' => Carbon::now()->addDays(30)->toDateString(),
            'statements_due_by' => Carbon::now()->addDays(15)->toDateString(),
        ]);

        // CHANGE IT UP SERVICES LTD
        $p2 = Project::create([
            'name' => 'CHANGE IT UP SERVICES LTD',
            'status' => 'active',
            'ubo' => 'Giacomo Lo-Iacono',
            'client_id' => $clientKudret->id,
            'manager_id' => $users['Kevin']->id ?? null,
            'phones' => ['Krisp' => '44 7727 673557', 'Zadarma' => '441218190352'],
            'emails' => [],
            'notes' => "Order Notification System: FALSE\nPayment Check: TRUE\nDD: F\nComment: PERS CODE CH\nWeb.dev: Nikita",
        ]);
        $p2->websites()->create([
            'name' => 'Main Website',
            'url' => 'https://fitninja.co.uk',
            'status' => 'Live',
        ]);
        Director::create([
            'project_id' => $p2->id,
            'name' => 'Giacomo Lo-Iacono',
            'fee_paid_status' => 'paid',
            'managed_by' => $users['Vito IT']->id ?? null,
        ]);
        Boarding::create([
            'project_id' => $p2->id,
            'kyb_completed_at' => now(),
            'boarding_completed_at' => now(),
            'cfs_verification' => 'completed',
            'cardaq_sumsub' => 'completed',
            'bank_verification' => 'completed',
            'companies_house_verification' => 'completed',
        ]);
        Report::create([
            'project_id' => $p2->id,
            'accounts_due_by' => Carbon::now()->subDays(5)->toDateString(),
            'statements_due_by' => Carbon::now()->addDays(60)->toDateString(),
        ]);

        // ANY PLACE ANY TIME LTD
        $p3 = Project::create([
            'name' => 'ANY PLACE ANY TIME LTD',
            'status' => 'active',
            'ubo' => 'Giuseppe Filingeri',
            'client_id' => $clientKudret->id,
            'manager_id' => $users['Kevin']->id ?? null,
            'phones' => ['Krisp' => '44 7727 673503', 'Zadarma' => '441417210159'],
            'emails' => [],
            'notes' => "Order Notification System: FALSE\nPayment Check: TRUE\nDD: F\nComment: PERS CODE CH\nWeb.dev: Nikita",
        ]);
        $p3->websites()->create([
            'name' => 'Main Website',
            'url' => 'https://startmoto.co.uk',
            'status' => 'Live',
        ]);
        Director::create([
            'project_id' => $p3->id,
            'name' => 'Giuseppe Filingeri',
            'fee_paid_status' => 'paid',
            'managed_by' => $users['Vito IT']->id ?? null,
        ]);
        Boarding::create([
            'project_id' => $p3->id,
            'kyb_completed_at' => now(),
            'boarding_completed_at' => now(),
            'cfs_verification' => 'completed',
            'cardaq_sumsub' => 'completed',
            'bank_verification' => 'completed',
            'companies_house_verification' => 'completed',
        ]);
        Report::create([
            'project_id' => $p3->id,
            'accounts_due_by' => Carbon::now()->addDays(7)->toDateString(),
            'statements_due_by' => Carbon::now()->addDays(45)->toDateString(),
        ]);

        // RENATASTRADAS MB
        $p4 = Project::create([
            'name' => 'RENATASTRADAS MB',
            'status' => 'suspended',
            'ubo' => 'Nikita JERMOLAJEVS',
            'client_id' => $clientKudret->id,
            'manager_id' => $users['Renat']->id ?? null,
            'phones' => ['Krisp' => 'N/A', 'Zadarma' => '442045772846'],
            'emails' => [],
            'notes' => "Order Notification System: FALSE\nPayment Check: FALSE\nDD: F\nWeb.dev: David",
        ]);
        $p4->websites()->create([
            'name' => 'Main Website',
            'url' => 'https://skills-trade.com',
            'status' => 'Live',
        ]);
        Director::create([
            'project_id' => $p4->id,
            'name' => 'Nikita JERMOLAJEVS',
            'fee_paid_status' => 'paid',
            'managed_by' => $users['Zahar']->id ?? null,
        ]);
        Boarding::create([
            'project_id' => $p4->id,
            'kyb_completed_at' => now(),
            'boarding_completed_at' => now(),
            'cfs_verification' => 'completed',
            'cardaq_sumsub' => 'completed',
            'bank_verification' => 'completed',
            'companies_house_verification' => 'completed',
        ]);
        Report::create([
            'project_id' => $p4->id,
            'accounts_due_by' => Carbon::now()->subDays(20)->toDateString(),
            'statements_due_by' => Carbon::now()->subDays(10)->toDateString(),
        ]);

        // WEARWIBE LTD (with three websites)
        $p5 = Project::create([
            'name' => 'WEARWIBE LTD',
            'status' => 'suspended',
            'ubo' => 'J O A Sharpstone',
            'client_id' => $clientMarvli->id,
            'manager_id' => $users['Jacob']->id ?? null,
            'phones' => ['Krisp' => 'N/A', 'Krisp - dressora.co.uk' => '44 7414 221866', 'Krisp - waveira.co.uk' => '44 7700 156927'],
            'emails' => [],
            'notes' => "Order Notification System: FALSE\nPayment Check: FALSE\nDD: F\nWeb.dev: Nikita",
        ]);
        $p5->websites()->create([
            'name' => 'Main Website',
            'url' => 'https://wear-wibe.co.uk',
            'status' => 'Live',
        ]);
        $p5->websites()->create([
            'name' => 'dressora.co.uk',
            'url' => 'https://dressora.co.uk',
            'status' => 'Live',
        ]);
        $p5->websites()->create([
            'name' => 'waveira.co.uk',
            'url' => 'https://waveira.co.uk',
            'status' => 'Live',
        ]);
        Director::create([
            'project_id' => $p5->id,
            'name' => 'J O A Sharpstone',
            'fee_paid_status' => 'paid',
            'managed_by' => $users['Venkata UK']->id ?? null,
        ]);
        Boarding::create([
            'project_id' => $p5->id,
            'kyb_completed_at' => now(),
            'boarding_completed_at' => now(),
            'cfs_verification' => 'completed',
            'cardaq_sumsub' => 'completed',
            'bank_verification' => 'completed',
            'companies_house_verification' => 'completed',
        ]);
        Report::create(['project_id' => $p5->id]);

        // BASILDON LIMITED
        $p6 = Project::create([
            'name' => 'BASILDON LIMITED',
            'status' => 'suspended',
            'ubo' => 'Kliment Mladenov Kavaldzhiev',
            'client_id' => $clientMarvli->id,
            'manager_id' => $users['Jacob']->id ?? null,
            'phones' => ['Krisp' => 'N/A'],
            'emails' => [],
            'notes' => "Order Notification System: FALSE\nPayment Check: FALSE\nDD: F\nWeb.dev: Nikita",
        ]);
        $p6->websites()->create([
            'name' => 'Main Website',
            'url' => 'https://miavia.co.uk',
            'status' => 'Live',
        ]);
        Director::create([
            'project_id' => $p6->id,
            'name' => 'Kliment Mladenov Kavaldzhiev',
            'fee_paid_status' => 'paid',
            'managed_by' => $users['Timur']->id ?? null,
        ]);
        Boarding::create([
            'project_id' => $p6->id,
            'kyb_completed_at' => now(),
            'boarding_completed_at' => now(),
            'cfs_verification' => 'completed',
            'cardaq_sumsub' => 'completed',
            'bank_verification' => 'completed',
            'companies_house_verification' => 'need_to_complete',
        ]);
        Report::create(['project_id' => $p6->id]);

        // GREAT LEADERS LTD
        $p7 = Project::create([
            'name' => 'GREAT LEADERS LTD',
            'status' => 'suspended',
            'ubo' => 'Saverio PALAZZOLO',
            'client_id' => $clientMarvli->id,
            'manager_id' => $users['Kevin']->id ?? null,
            'phones' => ['Krisp' => '44 7476 921378', 'Hushed' => '44 7537 167462'],
            'emails' => [],
            'notes' => "Order Notification System: FALSE\nPayment Check: FALSE\nDD: F\nWeb.dev: Nikita",
        ]);
        $p7->websites()->create([
            'name' => 'Main Website',
            'url' => 'https://bordeux.co.uk',
            'status' => 'Live',
        ]);
        Director::create([
            'project_id' => $p7->id,
            'name' => 'Saverio PALAZZOLO',
            'fee_paid_status' => 'paid',
            'managed_by' => $users['Vito IT']->id ?? null,
        ]);
        Boarding::create([
            'project_id' => $p7->id,
            'kyb_completed_at' => now(),
            'boarding_completed_at' => now(),
            'cfs_verification' => 'completed',
            'cardaq_sumsub' => 'completed',
            'bank_verification' => 'completed',
            'companies_house_verification' => 'completed',
        ]);
        Report::create(['project_id' => $p7->id]);

        // PRESTLEY LIMITED (with Portal credential)
        $p8 = Project::create([
            'name' => 'PRESTLEY LIMITED',
            'status' => 'active',
            'ubo' => 'Vincenza CAMMARATA',
            'client_id' => $clientMarvli->id,
            'manager_id' => $users['Renat']->id ?? null,
            'phones' => ['Krisp' => 'N/A', 'Zadarma' => '441615540229', 'Hushed' => '+44 7441 392316'],
            'emails' => ['Corporate' => 'info@accessora.co.uk', 'Private' => 'cammarata1@proton.me'],
            'notes' => "Order Notification System: TRUE\nPayment Check: TRUE\nDD: F\nWeb.dev: Nikita\nComment: PERS CODE CH",
        ]);
        $w8 = $p8->websites()->create([
            'name' => 'Main Website',
            'url' => 'https://accessora.co.uk',
            'status' => 'Live',
        ]);
        Director::create([
            'project_id' => $p8->id,
            'name' => 'Vincenza CAMMARATA',
            'fee_paid_status' => 'paid',
            'managed_by' => $users['Vito IT']->id ?? null,
        ]);
        Boarding::create([
            'project_id' => $p8->id,
            'kyb_completed_at' => now(),
            'boarding_completed_at' => now(),
            'cfs_verification' => 'completed',
            'cardaq_sumsub' => 'completed',
            'bank_verification' => 'completed',
            'companies_house_verification' => 'completed',
        ]);
        Report::create(['project_id' => $p8->id]);
        Credential::create([
            'project_id' => $p8->id,
            'website_id' => $w8->id,
            'type' => 'Portal',
            'login' => 'info@accessora.co.uk',
            'password' => 'gjofghnuofdh76342!',
            'comments' => 'Corporate E-Mail Password',
        ]);

        // DRAYBOND LIMITED (with Portal credential)
        $p9 = Project::create([
            'name' => 'DRAYBOND LIMITED',
            'status' => 'active',
            'ubo' => 'Giuseppe BATTAGLIERO',
            'client_id' => $clientMarvli->id,
            'manager_id' => $users['Jambulat']->id ?? null,
            'phones' => ['Krisp' => 'N/A', 'Zadarma' => '442922910127', 'Hushed' => '+44 7537 183297'],
            'emails' => ['Corporate' => 'info@macix.co.uk', 'Private' => 'battaglier0@proton.me'],
            'notes' => "Order Notification System: FALSE\nPayment Check: FALSE\nDD: F\nWeb.dev: David\nComment: PERS CODE CH",
        ]);
        $w9 = $p9->websites()->create([
            'name' => 'Main Website',
            'url' => 'https://macix.co.uk',
            'status' => 'Live',
        ]);
        Director::create([
            'project_id' => $p9->id,
            'name' => 'Giuseppe BATTAGLIERO',
            'fee_paid_status' => 'paid',
            'managed_by' => $users['Vito IT']->id ?? null,
        ]);
        Boarding::create([
            'project_id' => $p9->id,
            'kyb_completed_at' => now(),
            'boarding_completed_at' => now(),
            'cfs_verification' => 'completed',
            'cardaq_sumsub' => 'completed',
            'bank_verification' => 'completed',
            'companies_house_verification' => 'completed',
        ]);
        Report::create(['project_id' => $p9->id]);
        Credential::create([
            'project_id' => $p9->id,
            'website_id' => $w9->id,
            'type' => 'Portal',
            'login' => 'info@macix.co.uk',
            'password' => 'wojrhourhw43826!!',
            'comments' => 'Corporate E-Mail Password',
        ]);

        // ROWANLEA LIMITED (with Portal credential)
        $p10 = Project::create([
            'name' => 'ROWANLEA LIMITED',
            'status' => 'active',
            'ubo' => 'Mohhamed LEBNIOURI',
            'client_id' => $clientMarvli->id,
            'manager_id' => $users['Gleb']->id ?? null,
            'phones' => ['Krisp' => '44 7426 961794', 'Zadarma' => '442045770430', 'Hushed' => '44 7537 135146'],
            'emails' => ['Corporate' => 'info@jumlee.co.uk', 'Private' => 'muhha.leee@proton.me'],
            'notes' => "Order Notification System: FALSE\nPayment Check: FALSE\nDD: F\nWeb.dev: David\nComment: PERS CODE CH",
        ]);
        $w10 = $p10->websites()->create([
            'name' => 'Main Website',
            'url' => 'https://jumlee.co.uk',
            'status' => 'Live',
        ]);
        Director::create([
            'project_id' => $p10->id,
            'name' => 'Mohhamed LEBNIOURI',
            'fee_paid_status' => 'paid',
            'managed_by' => $users['Vito IT']->id ?? null,
        ]);
        Boarding::create([
            'project_id' => $p10->id,
            'kyb_completed_at' => now(),
            'boarding_completed_at' => now(),
            'cfs_verification' => 'completed',
            'cardaq_sumsub' => 'completed',
            'bank_verification' => 'completed',
            'companies_house_verification' => 'completed',
        ]);
        Report::create(['project_id' => $p10->id]);
        // ── Seed Tasks with due_dates for Deadline Center ────────────────────
        $allProjects = [$p1, $p2, $p3, $p4, $p5, $p6, $p7, $p8, $p9, $p10];
        $allUsers = array_values($users);
        $statuses = ['todo', 'in_progress', 'review'];
        $priorities = ['low', 'medium', 'high', 'critical'];

        $taskData = [
            // Today's deadlines
            ['title' => 'Submit KYB documents', 'due' => Carbon::today(), 'priority' => 'high', 'status' => 'in_progress', 'proj' => $p1],
            ['title' => 'Review payment gateway settings', 'due' => Carbon::today(), 'priority' => 'critical', 'status' => 'todo', 'proj' => $p2],
            // This week
            ['title' => 'Update Companies House records', 'due' => Carbon::now()->addDays(2), 'priority' => 'high', 'status' => 'todo', 'proj' => $p3],
            ['title' => 'Send invoice to client', 'due' => Carbon::now()->addDays(3), 'priority' => 'medium', 'status' => 'in_progress', 'proj' => $p4],
            ['title' => 'Verify bank statements', 'due' => Carbon::now()->addDays(4), 'priority' => 'high', 'status' => 'todo', 'proj' => $p5],
            ['title' => 'CFS compliance check', 'due' => Carbon::now()->addDays(5), 'priority' => 'medium', 'status' => 'review', 'proj' => $p6],
            // This month
            ['title' => 'Annual report preparation', 'due' => Carbon::now()->addDays(14), 'priority' => 'high', 'status' => 'todo', 'proj' => $p7],
            ['title' => 'Director fee review', 'due' => Carbon::now()->addDays(20), 'priority' => 'low', 'status' => 'todo', 'proj' => $p8],
            ['title' => 'Website SEO audit', 'due' => Carbon::now()->addDays(25), 'priority' => 'low', 'status' => 'todo', 'proj' => $p9],
            // Overdue
            ['title' => 'Overdue: Update registered address', 'due' => Carbon::now()->subDays(3), 'priority' => 'critical', 'status' => 'todo', 'proj' => $p1],
            ['title' => 'Overdue: Boarding completion', 'due' => Carbon::now()->subDays(7), 'priority' => 'high', 'status' => 'in_progress', 'proj' => $p2],
            ['title' => 'Overdue: Submit accounts', 'due' => Carbon::now()->subDays(15), 'priority' => 'critical', 'status' => 'todo', 'proj' => $p4],
        ];

        foreach ($taskData as $idx => $t) {
            $assignee = $allUsers[$idx % count($allUsers)];
            Task::create([
                'project_id' => $t['proj']->id,
                'creator_id' => $allUsers[0]->id,
                'assigned_to' => $assignee->id,
                'title' => $t['title'],
                'description' => 'Auto-generated task for deadline seeding.',
                'status' => $t['status'],
                'priority' => $t['priority'],
                'due_date' => $t['due']->toDateString(),
            ]);
        }

        // ── Final credentials for Rowanlea ───────────────────────────────────
        Credential::create([
            'project_id' => $p10->id,
            'website_id' => $w10->id,
            'type' => 'Portal',
            'login' => 'info@jumlee.co.uk',
            'password' => 'glkjglirejg2807!A',
            'comments' => 'Corporate E-Mail Password',
        ]);
    }
}
