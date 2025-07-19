<?php

namespace App\DataWarehouseRecords;

abstract class DataWarehouseRecord implements DataWarehouseRecordInterface
{
    /**
     * @param $record
     * @return bool
     */
    public function push($record = null): bool
    {
        $data = $this->getData($record);

        return app('data-warehouse')->pushBatch($data);
    }
}
