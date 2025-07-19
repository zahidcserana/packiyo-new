<?php

namespace App\Events;

use App\Interfaces\AutomatableEvent;
use App\Interfaces\AutomatableOperation;
use App\Interfaces\BillableEvent;
use App\Models\Customer;
use App\Models\Warehouse;
use Carbon\Carbon;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class OccupiedLocationsCalculatedEvent implements BillableEvent, AutomatableEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Customer $client,
        public Warehouse $warehouse,
        public Carbon $calendarDate
    )
    {
    }

    public function getOperation(): AutomatableOperation
    {
        // TODO: Implement getOperation() method.
    }

    public static function getTitle(): String
    {
        return 'Occupied Locations Calculated';
    }
}
