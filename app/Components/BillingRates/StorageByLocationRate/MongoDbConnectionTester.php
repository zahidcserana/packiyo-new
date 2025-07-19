<?php

namespace App\Components\BillingRates\StorageByLocationRate;

use DB;

class MongoDbConnectionTester
{
    public function testConnection(): bool
    {
        try {
            DB::connection('mongodb')->listCollections();
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }
}
