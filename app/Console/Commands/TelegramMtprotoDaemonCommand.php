<?php

namespace App\Console\Commands;

use App\Services\TelegramMtproto\TelethonClientService;
use Illuminate\Console\Command;

class TelegramMtprotoDaemonCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'telegram:mtproto-daemon {action=status : Action to perform: start, stop, restart, or status}';

    /**
     * The console command description.
     */
    protected $description = 'Manage Python Telethon MTProto background daemon service';

    /**
     * Execute the console command.
     */
    public function handle(TelethonClientService $telethonService)
    {
        $action = $this->argument('action');

        switch ($action) {
            case 'status':
                $this->info('Checking Telethon MTProto service health...');
                $health = $telethonService->checkHealth();
                if ($health['online'] ?? false) {
                    $this->info(' Telethon Service is ONLINE and healthy.');
                    $this->line('Connected: ' . ($health['connected'] ? 'Yes' : 'No'));
                    $this->line('Monitoring: ' . ($health['monitoring'] ? 'Active' : 'Inactive'));
                    $this->line('Monitored Channels: ' . count($health['monitored_channels'] ?? []));
                } else {
                    $this->warn(' Telethon Service is OFFLINE.');
                    $this->line('Run `php artisan telegram:mtproto-daemon start` to launch, or start with Supervisor/systemd.');
                }
                break;

            case 'start':
                $this->info('Starting Telethon MTProto Python daemon...');
                $started = $telethonService->autoStartService();
                if ($started) {
                    $this->info(' Telethon daemon started successfully.');
                } else {
                    $this->error('Failed to auto-start daemon. Please run `./python_service/venv/bin/python3 python_service/server.py` or use Supervisor.');
                }
                break;

            default:
                $this->error("Unknown action: {$action}. Use status or start.");
        }
    }
}
