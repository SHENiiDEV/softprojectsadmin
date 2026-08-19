<?php

namespace App\Console\Commands;

use App\Models\Boarding;
use App\Models\Client;
use App\Models\Project;
use App\Models\User;
use App\Models\Website;
use Illuminate\Console\Command;

class ImportAcquirers extends Command
{
    protected $signature = 'import:acquirers {file? : Path to the CSV file to import}';

    protected $description = 'Import company acquirers/gateways and compliance status from CSV';

    public function handle(): int
    {
        $filePath = $this->argument('file')
            ?: (file_exists(database_path('seeders/data/acquirers_export.csv'))
                ? database_path('seeders/data/acquirers_export.csv')
                : storage_path('app/acquirers_export.csv'));

        if (! file_exists($filePath)) {
            $this->error("File not found: {$filePath}");

            return 1;
        }

        $fileHandle = fopen($filePath, 'r');
        if ($fileHandle === false) {
            $this->error("Failed to open file: {$filePath}");

            return 1;
        }

        // Read header row
        $header = fgetcsv($fileHandle);
        if (! $header) {
            $this->error('CSV file is empty.');
            fclose($fileHandle);

            return 1;
        }

        $defaultClient = Client::firstOrCreate(['name' => 'Default Client']);
        $lastClientName = null;

        $processedCount = 0;
        $createdCompanies = 0;
        $createdWebsites = 0;
        $updatedBoardings = 0;

        while (($row = fgetcsv($fileHandle)) !== false) {
            if (count($row) < 3) {
                continue;
            }

            $rawClient = trim($row[0] ?? '');
            $rawWebsite = trim($row[1] ?? '');
            $bank = trim($row[2] ?? '');
            $agent = trim($row[3] ?? '');
            $status = trim($row[4] ?? '');

            // Skip section headers or empty bank rows
            if (empty($bank) || $rawWebsite === '-' || empty($rawWebsite)) {
                if (! empty($rawClient) && str_contains($rawClient, '-')) {
                    // Group title like "Marvli - Savely"
                    continue;
                }

                continue;
            }

            // Inherit client name if missing in current row
            if (! empty($rawClient)) {
                $lastClientName = $rawClient;
            } else {
                $rawClient = $lastClientName;
            }

            if (empty($rawClient)) {
                $rawClient = 'Imported Company';
            }

            // Normalize website domain list
            $domains = $this->parseDomains($rawWebsite);
            if (empty($domains)) {
                continue;
            }

            // Find or create Project (Company)
            $project = Project::where('name', 'like', $rawClient)->first();
            if (! $project) {
                $project = Project::create([
                    'client_id' => $defaultClient->id,
                    'name' => $rawClient,
                    'status' => 'active',
                ]);
                $createdCompanies++;
            }

            // Assign Manager / Agent if found
            if (! empty($agent) && $agent !== 'Client') {
                $user = User::where('name', 'like', "%{$agent}%")->first();
                if ($user && ! $project->manager_id) {
                    $project->update(['manager_id' => $user->id]);
                }
            }

            // Ensure Boarding record exists
            $boarding = $project->boarding ?? $project->boarding()->create([
                'provider_name' => $bank,
                'cfs_verification' => 'need_to_complete',
                'cardaq_sumsub' => 'pending',
                'bank_verification' => 'not_started',
                'companies_house_verification' => 'not_started',
            ]);

            // Update Boarding providers_data JSON for each website + bank combination
            $providersData = is_array($boarding->providers_data) ? $boarding->providers_data : [];

            $boardingStatus = match ($status) {
                'Working' => 'completed',
                'In process' => 'in_progress',
                'Stopped' => 'stopped',
                default => 'pending',
            };

            $verificationStatus = match ($status) {
                'Working' => 'boarding_complete',
                'In process' => 'pending',
                'Stopped' => 'stopped',
                default => 'pending',
            };

            // Update Website records & Gateways array & Boarding providers_data
            foreach ($domains as $domain) {
                $cleanUrl = str_starts_with($domain, 'http') ? $domain : "https://{$domain}";
                $website = Website::where('project_id', $project->id)
                    ->where(function ($q) use ($domain) {
                        $q->where('url', 'like', "%{$domain}%")
                            ->orWhere('name', 'like', "%{$domain}%");
                    })
                    ->first();

                if (! $website) {
                    $website = Website::create([
                        'project_id' => $project->id,
                        'name' => $domain,
                        'url' => $cleanUrl,
                        'status' => $status === 'Working' ? 'Live' : 'Test',
                        'gateways' => [$bank],
                    ]);
                    $createdWebsites++;
                } else {
                    $existingGateways = is_array($website->gateways) ? $website->gateways : [];
                    if (! in_array($bank, $existingGateways, true)) {
                        $existingGateways[] = $bank;
                        $website->gateways = array_values(array_unique($existingGateways));
                        if ($status === 'Working') {
                            $website->status = 'Live';
                        }
                        $website->save();
                    }
                }

                $compositeKey = "{$website->id}_{$bank}";
                $providersData[$compositeKey] = [
                    'composite_key' => $compositeKey,
                    'website_id' => $website->id,
                    'website_name' => $website->name ?: $website->url,
                    'website_url' => $website->url,
                    'name' => $bank,
                    'boarding_completed_at' => $status === 'Working' ? now()->format('Y-m-d') : ($providersData[$compositeKey]['boarding_completed_at'] ?? null),
                    'kyb_sent_at' => $providersData[$compositeKey]['kyb_sent_at'] ?? null,
                    'kyb_status' => 'sent',
                    'boarding_status' => $boardingStatus,
                    'verification_status' => $verificationStatus,
                ];

                // Legacy fallback key
                $providersData[$bank] = $providersData[$compositeKey];
            }

            $boarding->providers_data = $providersData;
            $boarding->save();
            $updatedBoardings++;

            $processedCount++;
        }

        fclose($fileHandle);

        $this->info('Import completed successfully!');
        $this->table(
            ['Metric', 'Count'],
            [
                ['Processed Rows', $processedCount],
                ['New Companies Created', $createdCompanies],
                ['New Websites Created', $createdWebsites],
                ['Boarding Profiles Updated', $updatedBoardings],
            ]
        );

        return 0;
    }

    /**
     * Clean and parse domain string into array of domain names.
     *
     * @return array<string>
     */
    private function parseDomains(string $rawWebsite): array
    {
        // Handle "+2" suffix e.g. "terracuisine.io +2" -> "terracuisine.io"
        $cleaned = preg_replace('/\s*\+\d+/', '', $rawWebsite);

        // Split multiple domains separated by "/" e.g. "buy-it.io / bplstadium.com"
        $parts = explode('/', $cleaned);
        $domains = [];

        foreach ($parts as $part) {
            $domain = trim($part);
            if (str_starts_with($domain, 'http')) {
                $domain = parse_url($domain, PHP_URL_HOST) ?? $domain;
            }
            if (! empty($domain) && $domain !== '-') {
                $domains[] = $domain;
            }
        }

        return array_values(array_unique($domains));
    }
}
