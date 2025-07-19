<?php

namespace App\Traits;

trait HasPrintables
{
    public function getPrintables(): array
    {
        return $this->printables ?? ['barcode'];
    }

    public function getPrintableUrls(): array
    {
        return $this->printableUrls ?? [];
    }
}
