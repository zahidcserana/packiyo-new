<?php

namespace App\Queues;

use Illuminate\Queue\DatabaseQueue;

class DistributedDatabaseQueue extends DatabaseQueue
{
    public function getQueue($queue): string
    {
        if (!empty($queue)) {
            return get_distributed_queue_name($queue);
        }

        return parent::getQueue($queue);
    }
}
