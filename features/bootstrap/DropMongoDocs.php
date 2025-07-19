<?php

use Illuminate\Support\Facades\DB;

trait DropMongoDocs
{
    public function dropMongoDocs(): void
    {
        try {
            // TODO Check if we should improve this. Solution found at: https://github.com/mongodb/laravel-mongodb/issues/1475#issuecomment-452824396
            $mongo = DB::connection('mongodb');

            foreach ($mongo->listCollections() as $collection) {
                $name = $collection->getName();

                if (! str_starts_with($name, 'system')) {
                    (new \Jenssegers\Mongodb\Schema\Blueprint($mongo, $name))->drop();
                }
            }
        } catch (\Exception $e) {
            return;
        }
    }
}
