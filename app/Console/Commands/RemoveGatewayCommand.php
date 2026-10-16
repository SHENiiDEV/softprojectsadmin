<?php

namespace App\Console\Commands;

use App\Services\GatewayService;
use Illuminate\Console\Command;

class RemoveGatewayCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'gateway:remove {name : The name of the gateway provider to remove}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Remove a gateway provider from the available gateways list';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $name = trim((string) $this->argument('name'));

        if ($name === '') {
            $this->error('The gateway name cannot be empty.');

            return self::FAILURE;
        }

        if (! GatewayService::isValid($name)) {
            $this->warn("Gateway provider '{$name}' is not currently in the available gateways list.");

            return self::SUCCESS;
        }

        GatewayService::remove($name);
        $this->info("Gateway provider '{$name}' has been successfully removed.");

        return self::SUCCESS;
    }
}
