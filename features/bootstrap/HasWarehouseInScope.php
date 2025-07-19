<?php

use App\Models\Warehouse;

trait HasWarehouseInScope
{
    protected ?Warehouse $warehouse = null;

    public function getWarehouseInScope(): ?Warehouse
    {
        return $this->warehouse;
    }

    public function setWarehouseInScope(?Warehouse $warehouse): void
    {
        $this->warehouse = $warehouse;
    }


}
