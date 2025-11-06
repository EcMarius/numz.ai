<?php

namespace App\Console\Commands;

use App\Models\HostingServer;
use Illuminate\Console\Command;

class TestProvisioningModule extends Command
{
    protected $signature = 'provisioning:test {server_id}';
    protected $description = 'Test provisioning module connection for a server';

    public function handle()
    {
        $serverId = $this->argument('server_id');
        $server = HostingServer::find($serverId);

        if (!$server) {
            $this->error("Server not found with ID: {$serverId}");
            return 1;
        }

        $this->info("Testing connection to server: {$server->name}");
        $this->info("Type: {$server->type}");
        $this->info("Hostname: {$server->hostname}");
        $this->line('');

        try {
            $result = $server->testConnection();

            if ($result['success']) {
                $this->info("✓ Connection successful!");
                $this->info($result['message']);
                return 0;
            } else {
                $this->error("✗ Connection failed!");
                $this->error($result['message']);
                return 1;
            }
        } catch (\Exception $e) {
            $this->error("✗ Exception occurred:");
            $this->error($e->getMessage());
            return 1;
        }
    }
}
