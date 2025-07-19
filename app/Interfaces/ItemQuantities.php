<?php

namespace App\Interfaces;

use Illuminate\Support\Collection;

interface ItemQuantities
{
    public function items(): Collection;
}
