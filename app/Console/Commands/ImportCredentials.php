<?php

namespace App\Console\Commands;

use App\Models\Client;
use App\Models\Credential;
use App\Models\Project;
use App\Models\Website;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ImportCredentials extends Command
{
    protected $signature = 'import:credentials {file : Path to the JSON file to import}';

    protected $description = 'Import company credentials from a JSON file';

    public function handle(): int
    {
        $filePath = $this->argument('file');

        if (! file_exists($filePath)) {
            $this->error("File not found: {$filePath}");

            return 1;
        }

        $jsonContent = file_get_contents($filePath);
        $data = json_decode($jsonContent, true);

        if (! is_array($data)) {
            $this->error('Invalid JSON format in file.');

            return 1;
        }

        $like = DB::connection()->getDriverName() === 'pgsql' ? 'ilike' : 'like';
        $imported = 0;
        $createdProjects = 0;
        $matchedProjects = 0;

        $defaultClient = Client::firstOrCreate(['name' => 'Imported Clients']);

        foreach ($data as $index => $item) {
            $projectId = $item['project_id'] ?? $item['company_id'] ?? null;
            $companyName = trim($item['company_name'] ?? $item['project_name'] ?? $item['company'] ?? $item['project'] ?? '');
            $websiteUrl = trim($item['website_url'] ?? $item['url'] ?? $item['website'] ?? '');
            $name = trim($item['name'] ?? $item['title'] ?? $item['label'] ?? 'Access Credential');
            $type = strtolower(trim($item['type'] ?? 'other'));
            $providerUrl = trim($item['provider_url'] ?? $item['login_url'] ?? $item['portal'] ?? '');
            $login = trim($item['login'] ?? $item['username'] ?? $item['email'] ?? $item['user'] ?? '');
            $password = $item['password'] ?? $item['pass'] ?? $item['secret'] ?? '';
            $comments = trim($item['comments'] ?? $item['notes'] ?? $item['description'] ?? '');
            $fields = $item['fields'] ?? null;

            if (empty($companyName) && empty($websiteUrl) && empty($projectId)) {
                $this->warn("Skipping item #{$index}: No company name, ID or website URL provided.");

                continue;
            }

            // Find or create Project
            $project = null;
            if ($projectId) {
                $project = Project::find($projectId);
            }

            if (! $project && $companyName) {
                $project = Project::where('name', $like, $companyName)->first();
            }

            if (! $project && $websiteUrl) {
                $host = parse_url($websiteUrl, PHP_URL_HOST) ?? $websiteUrl;
                $project = Project::whereHas('websites', fn ($q) => $q->where('url', $like, "%{$host}%"))->first();
            }

            if (! $project) {
                $projectName = $companyName ?: ($host ?? 'New Import Project');
                $project = Project::create([
                    'client_id' => $defaultClient->id,
                    'name' => $projectName,
                    'status' => 'active',
                    'ubo' => 'Imported',
                ]);
                $createdProjects++;
            } else {
                $matchedProjects++;
            }

            // Find or create Website
            $websiteId = null;
            if ($websiteUrl) {
                $website = Website::firstOrCreate(
                    ['project_id' => $project->id, 'url' => $websiteUrl],
                    ['name' => 'Main Website']
                );
                $websiteId = $website->id;
            }

            // Create Credential
            Credential::create([
                'project_id' => $project->id,
                'website_id' => $websiteId,
                'name' => $name,
                'type' => $type ?: 'other',
                'provider_url' => $providerUrl ?: null,
                'login' => $login ?? '',
                'password' => $password !== null ? (string) $password : '',
                'comments' => $comments ?: null,
                'fields' => is_array($fields) ? $fields : null,
            ]);

            $imported++;
            $this->info("Imported credential '{$name}' for project '{$project->name}'");
        }

        $this->info("✅ Import finished: {$imported} credentials imported ({$matchedProjects} matched projects, {$createdProjects} new projects created).");

        return 0;
    }
}
