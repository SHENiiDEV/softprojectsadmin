<?php

namespace App\Console\Commands;

use App\Services\GatewayService;
use Illuminate\Console\Command;

class AddGatewayCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'gateway:add {name : The name of the gateway provider to add (e.g. Stripe, Adyen)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Add a new gateway provider to the available gateways list';

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

        if (GatewayService::isValid($name)) {
            $this->info("Gateway provider '{$name}' is already available.");

            return self::SUCCESS;
        }

        $added = GatewayService::add($name);
        $this->info("Gateway provider '{$added}' has been added successfully.");

        return self::SUCCESS;
    }
}
