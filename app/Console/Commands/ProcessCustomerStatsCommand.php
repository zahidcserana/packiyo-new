<?php

namespace App\Console\Commands;

use App\Jobs\DataWarehouse\CustomerStatsJob;
use Illuminate\Console\Command;

class ProcessCustomerStatsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'process:customer-stats';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Process customer stats and import to the data warehouse';

    /**
     * Create a new command instance.
     *
     * @return void
     */

    public function handle(): void
    {
        dispatch_sync(new CustomerStatsJob());

        $this->info('Customer stats were successfully pushed to the Data warehouse pipeline!');
    }
}
