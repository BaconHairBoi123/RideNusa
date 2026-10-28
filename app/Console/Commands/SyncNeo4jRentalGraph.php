<?php

namespace App\Console\Commands;

use App\Services\Neo4jGraphService;
use Illuminate\Console\Command;
use Throwable;

class SyncNeo4jRentalGraph extends Command
{
    protected $signature = 'neo4j:sync-rental-graph';

    protected $description = 'Sinkronkan users, motorcycles, dan booking berbayar ke Neo4j untuk rekomendasi motor';

    public function handle(Neo4jGraphService $graph): int
    {
        try {
            $summary = $graph->syncRentalGraph();
            $this->info("Sinkronisasi selesai: {$summary['users']} user, {$summary['motorcycles']} motor, {$summary['paid_bookings']} booking berbayar.");

            return self::SUCCESS;
        } catch (Throwable $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }
    }
}
