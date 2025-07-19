<?php

namespace App\Jobs;

use App\Components\CalculatesOccupiedLocations;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class CalculateOccupiedLocations implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    use CalculatesOccupiedLocations;

    public function handle(): void
    {
        $this->calculateOccupiedLocations();
    }
}
