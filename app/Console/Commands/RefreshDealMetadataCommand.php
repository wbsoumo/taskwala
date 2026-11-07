<?php

namespace App\Console\Commands;

use App\Models\Deal;
use App\Services\Deals\DealAggregatorService;
use Illuminate\Console\Command;

class RefreshDealMetadataCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'deals:refresh-metadata 
                            {--deal= : Specific deal ID to refresh} 
                            {--limit=50 : Maximum number of deals to process} 
                            {--all : Force re-scrape even if image and price exist}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Scrape e-commerce stores (Amazon, Flipkart, Myntra, Ajio) for product image URLs, clean titles, MRP, and prices';

    /**
     * Execute the console command.
     */
    public function handle(DealAggregatorService $aggregator): int
    {
        $dealId = $this->option('deal');
        $force = (bool) $this->option('all');
        $limit = (int) $this->option('limit');

        if ($dealId) {
            $deal = Deal::find($dealId);
            if (!$deal) {
                $this->error("Deal ID #{$dealId} not found.");
                return self::FAILURE;
            }

            $this->info("Refreshing metadata for deal #{$deal->id}: {$deal->title}");
            $res = $aggregator->enrichDealMetadata($deal, true);
            if ($res['success']) {
                $fresh = $res['deal'];
                $this->info("Success! Updated: " . json_encode($res['updates']));
                $this->line("Image: " . ($fresh->image_url ?: 'none'));
                $this->line("Price: ₹" . number_format($fresh->price ?? 0, 2) . " | MRP: ₹" . number_format($fresh->original_price ?? 0, 2));
            } else {
                $this->error("Failed: " . ($res['error'] ?? 'Unknown error'));
            }
            return self::SUCCESS;
        }

        // Query deals that need enrichment (missing image or missing price/MRP) unless --all is specified
        $query = Deal::query();
        if (!$force) {
            $query->where(function ($q) {
                $q->whereNull('image_url')
                  ->orWhereNull('original_price')
                  ->orWhereNull('price');
            });
        }

        $deals = $query->orderBy('posted_at', 'desc')->take($limit)->get();

        if ($deals->isEmpty()) {
            $this->comment('No deals require metadata enrichment.');
            return self::SUCCESS;
        }

        $this->info("Found {$deals->count()} deals to enrich...");
        $bar = $this->output->createProgressBar($deals->count());
        $bar->start();

        $successCount = 0;
        $failedCount = 0;

        foreach ($deals as $deal) {
            $res = $aggregator->enrichDealMetadata($deal, $force);
            if ($res['success']) {
                $successCount++;
            } else {
                $failedCount++;
            }
            $bar->advance();
            // Small polite delay between merchant requests
            usleep(250000);
        }

        $bar->finish();
        $this->newLine();
        $this->info("Metadata enrichment complete. Successfully updated: {$successCount}, Failed/Unchanged: {$failedCount}.");

        return self::SUCCESS;
    }
}
