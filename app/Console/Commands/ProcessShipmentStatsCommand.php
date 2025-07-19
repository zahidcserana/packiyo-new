<?php

namespace App\Console\Commands;

use App\Jobs\DataWarehouse\ShipmentsJob;
use App\Models\Shipment;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Mockery\Exception;

class ProcessShipmentStatsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'process:shipment-stats';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Process shipment stats by date and import to the data warehouse';

    /**
     * Create a new command instance.
     *
     * @return void
     */

    public function handle(): void
    {
        $fromDate = $this->ask('From which date do you want to import shipments? (Date format: Y-m-d)');
        $toDate = $this->ask('What is the end date? (if left blank, it will pick today\'s date)') ?? Carbon::now();

        try {
            $fromDate = Carbon::parse($fromDate);

            if (!($toDate instanceof Carbon)) {
                $toDate = Carbon::parse($toDate);
            }
        } catch (Exception $exception) {
            $this->error($exception->getMessage());
        }

        $shipments = Shipment::whereBetween('created_at', [$fromDate, $toDate])->get();

        dispatch_sync(new ShipmentsJob($shipments));

        $this->info('Shipments were successfully pushed to the Data warehouse pipeline!');
    }
}
