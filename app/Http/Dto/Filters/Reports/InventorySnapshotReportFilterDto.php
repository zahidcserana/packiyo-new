<?php

namespace App\Http\Dto\Filters\Reports;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Collection;

class InventorySnapshotReportFilterDto implements Arrayable
{
    public function __construct(private readonly Collection $warehouses)
    {
    }

    /**
     * @return array
     */
    public function toArray(): array
    {
        return [
            'warehouses' => $this->warehouses,
        ];
    }
}
