<?php

namespace Database\Seeders;

use App\Models\Client;
use App\Models\Project;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Throwable;

class FakeCompaniesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('🚀 Creating 100 Clients with 10 Companies each (1,000 total companies)...');

        // Fix PostgreSQL sequences if explicit IDs were used previously
        if (DB::connection()->getDriverName() === 'pgsql') {
            DB::statement("SELECT setval('clients_id_seq', COALESCE((SELECT MAX(id) FROM clients), 1));");
            DB::statement("SELECT setval('projects_id_seq', COALESCE((SELECT MAX(id) FROM projects), 1));");
        }

        try {
            $users = User::pluck('id')->toArray();
            if (empty($users)) {
                $this->call([TeamSeeder::class]);
                $users = User::pluck('id')->toArray();
            }

            // Pools for fast random picks
            $companySuffixes = ['LTD', 'LIMITED', 'OU', 'MB', 'GLOBAL', 'SOLUTIONS', 'ENTERPRISES', 'MEDIA', 'HOLDINGS', 'SYSTEMS'];
            $companyPrefixes = ['ALPHA', 'NEXUS', 'APEX', 'VORTEX', 'SOLAR', 'QUANTUM', 'ORION', 'ZENITH', 'BEACON', 'SUMMIT', 'PRIME', 'STELLAR', 'NOVA', 'ECHO', 'PULSE', 'HORIZON', 'SYNERGY', 'DYNAMIC', 'MATRIX', 'CYBER'];
            $firstNames = ['John', 'David', 'Michael', 'Alex', 'Emma', 'Sarah', 'Daniel', 'James', 'Robert', 'Elena', 'Marco', 'Giuseppe', 'Viktoria', 'Nikita', 'Igor', 'Dmitry', 'Anna', 'Laura', 'Sofia', 'Lucas'];
            $lastNames = ['Smith', 'Johnson', 'Williams', 'Brown', 'Jones', 'Garcia', 'Miller', 'Davis', 'Rodriguez', 'Martinez', 'Hernandez', 'Lopez', 'Gonzalez', 'Wilson', 'Anderson', 'Thomas', 'Taylor', 'Moore', 'Jackson', 'Martin'];
            $tlds = ['co.uk', 'com', 'io', 'net', 'eu', 'org', 'app'];
            $visaMcStatuses = ['Working', 'In Progress', 'Stopped'];
            $verifications = ['completed', 'need_to_complete'];

            $now = Carbon::now();
            $totalCount = 0;

            for ($c = 1; $c <= 100; $c++) {
                $prefName = $companyPrefixes[($c - 1) % count($companyPrefixes)];
                $suffName = $companyPrefixes[($c + 4) % count($companyPrefixes)];
                $clientName = "{$prefName} {$suffName} Group #{$c}";

                // 1. Create Client
                $client = Client::create([
                    'name' => $clientName,
                    'hash' => Str::random(32),
                ]);

                $directorsData = [];
                $websitesData = [];
                $boardingsData = [];
                $reportsData = [];
                $tasksData = [];
                $credentialsData = [];

                // 2. Create 10 Companies for this Client
                for ($k = 1; $k <= 10; $k++) {
                    $totalCount++;

                    $compPref = $companyPrefixes[rand(0, count($companyPrefixes) - 1)];
                    $compSuff = $companySuffixes[rand(0, count($companySuffixes) - 1)];
                    $companyName = "{$compPref} {$compSuff} {$k}";
                    $directorName = $firstNames[rand(0, count($firstNames) - 1)].' '.$lastNames[rand(0, count($lastNames) - 1)];
                    $managerId = $users[rand(0, count($users) - 1)];
                    $isArchived = ($totalCount % 10 === 0);
                    $isCompleted = ($totalCount % 3 !== 0);

                    // Create Project
                    $project = Project::create([
                        'name' => $companyName,
                        'status' => 'active',
                        'ubo' => $directorName,
                        'client_id' => $client->id,
                        'manager_id' => $managerId,
                        'notes' => 'Seeded test company record #'.$totalCount,
                        'archived_at' => $isArchived ? $now->copy()->subDays(rand(1, 100)) : null,
                    ]);

                    $projectId = $project->id;

                    // Directors
                    $directorsData[] = [
                        'project_id' => $projectId,
                        'name' => $directorName,
                        'fee_paid_status' => ($totalCount % 2 === 0) ? 'paid' : 'need_to_pay',
                        'managed_by' => $users[rand(0, count($users) - 1)],
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];

                    // Websites (1-2 per company)
                    $siteCount = ($totalCount % 2 === 0) ? 2 : 1;
                    for ($w = 1; $w <= $siteCount; $w++) {
                        $slug = strtolower(str_replace(' ', '-', $companyName)).($w > 1 ? "-site{$w}" : '');
                        $tld = $tlds[rand(0, count($tlds) - 1)];
                        $websitesData[] = [
                            'project_id' => $projectId,
                            'name' => $w === 1 ? 'Main Website' : "Website {$w}",
                            'url' => "https://{$slug}.{$tld}",
                            'status' => ($totalCount % 3 === 0) ? 'Live' : (($totalCount % 2 === 0) ? 'Test' : 'No integration'),
                            'visa_status' => $visaMcStatuses[rand(0, count($visaMcStatuses) - 1)],
                            'mastercard_status' => $visaMcStatuses[rand(0, count($visaMcStatuses) - 1)],
                            'gateways' => json_encode([]),
                            'created_at' => $now,
                            'updated_at' => $now,
                        ];
                    }

                    // Boardings
                    $boardingsData[] = [
                        'project_id' => $projectId,
                        'kyb_completed_at' => $isCompleted ? $now->copy()->subDays(rand(5, 60)) : null,
                        'boarding_completed_at' => $isCompleted ? $now->copy()->subDays(rand(1, 50)) : null,
                        'cfs_verification' => $isCompleted ? 'completed' : $verifications[rand(0, 1)],
                        'cardaq_sumsub' => $isCompleted ? 'completed' : $verifications[rand(0, 1)],
                        'bank_verification' => $isCompleted ? 'completed' : $verifications[rand(0, 1)],
                        'companies_house_verification' => $isCompleted ? 'completed' : $verifications[rand(0, 1)],
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];

                    // Reports
                    $reportsData[] = [
                        'project_id' => $projectId,
                        'accounts_due_by' => $now->copy()->addMonths(rand(1, 12))->toDateString(),
                        'statements_due_by' => $now->copy()->addMonths(rand(1, 6))->toDateString(),
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];

                    // Tasks
                    $tasksData[] = [
                        'project_id' => $projectId,
                        'title' => "Perform KYB Verification for {$companyName}",
                        'description' => "Complete corporate documentation and banking verification for {$companyName}.",
                        'assigned_to' => $users[rand(0, count($users) - 1)],
                        'creator_id' => $users[rand(0, count($users) - 1)],
                        'status' => ['todo', 'in_progress', 'review', 'done'][rand(0, 3)],
                        'priority' => ['low', 'medium', 'high', 'critical'][rand(0, 3)],
                        'due_date' => $now->copy()->addDays(rand(-5, 20))->toDateString(),
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];

                    // Credentials
                    $credentialsData[] = [
                        'project_id' => $projectId,
                        'name' => 'CMS Admin Panel',
                        'type' => 'cms',
                        'login' => 'admin_'.strtolower($compPref),
                        'password' => 'secret_pass_'.rand(1000, 9999),
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                }

                // Insert sub-records for this client's 10 companies in bulk
                DB::table('directors')->insert($directorsData);
                DB::table('websites')->insert($websitesData);
                DB::table('boardings')->insert($boardingsData);
                DB::table('reports')->insert($reportsData);
                DB::table('tasks')->insert($tasksData);
                DB::table('credentials')->insert($credentialsData);

                $this->command->info("  [Client {$c}/100] '{$clientName}' created -> 10 companies added (Total Companies: {$totalCount})");
            }

            $this->command->info('✅ Successfully created 100 Clients with 10 Companies each (1,000 total companies)!');
        } catch (Throwable $e) {
            $this->command->error('❌ Error during seeding: '.$e->getMessage());
            throw $e;
        }
    }
}
