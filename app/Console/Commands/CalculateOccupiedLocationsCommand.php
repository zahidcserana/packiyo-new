<?php

namespace App\Console\Commands;

use App\Components\CalculatesOccupiedLocations;
use App\Jobs\CalculateOccupiedLocations;
use Illuminate\Console\Command;

class CalculateOccupiedLocationsCommand extends Command
{
    use CalculatesOccupiedLocations;
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'calculate:occupied-locations';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate a list of occupied locations for every client and stores it into DocDb (MongoDB).';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(): int
    {
        // We also need to think what to do with the data that was generated before the failure.
        // We also need to check if we have Mongo available to run it.
        CalculateOccupiedLocations::dispatch();

        $this->calculateOccupiedLocations();

        return 0;
    }
}
