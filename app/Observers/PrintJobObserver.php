<?php

namespace App\Observers;

use App\Events\NewPrintJobEvent;
use App\Models\PrintJob;
use App\Http\Resources\PrintJobResource;

class PrintJobObserver
{
    /**
     * Handle the PrintJob "created" event.
     *
     * @param PrintJob $printJob
     * @return void
     */
    public function created(PrintJob $printJob): void
    {
        broadcast(new NewPrintJobEvent(new PrintJobResource($printJob)));
    }
}
