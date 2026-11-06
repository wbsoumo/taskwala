<?php

namespace App\Console\Commands;

use App\Models\Setting;
use App\Services\Deals\DealAggregatorService;
use Illuminate\Console\Command;

class SyncDealsCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'deals:sync 
                            {--channel= : Specific Telegram channel to sync} 
                            {--limit=50 : Maximum number of messages to fetch}';

    /**
     * The console command description.
     */
    protected $description = 'Synchronize and aggregate deals from Telegram channels and convert links via EarnKaro';

    /**
     * Execute the console command.
     */
    public function handle(DealAggregatorService $aggregator): int
    {
        $this->info('Starting Deals aggregation & synchronization...');

        $channel = $this->option('channel');
        $limit = (int) $this->option('limit');

        $result = $aggregator->syncAll($channel, $limit);

        if (isset($result['error'])) {
            $this->error('Sync encountered an error: ' . $result['error']);
        }

        $this->table(
            ['Metric', 'Count'],
            [
                ['Deals Found', $result['total_found']],
                ['New Deals Added', $result['total_added']],
                ['Duplicates Skipped', $result['total_skipped']],
                ['Failed', $result['total_failed']],
            ]
        );

        $this->info('Deals synchronization completed successfully.');
        return Command::SUCCESS;
    }
}
