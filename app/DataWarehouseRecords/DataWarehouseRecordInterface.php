<?php

namespace App\DataWarehouseRecords;

interface DataWarehouseRecordInterface
{
    public function getSchema(): array;
    public function getData($record = null): array;
}
