<?php

namespace App\Providers;

use App\Queues\DatabaseConnector;
use App\Queues\DistributedDatabaseQueue;

class QueueServiceProvider extends \Illuminate\Queue\QueueServiceProvider
{
    protected function registerDatabaseConnector($manager)
    {
        $manager->addConnector('database', fn() => new DatabaseConnector($this->app['db']));
    }
}
