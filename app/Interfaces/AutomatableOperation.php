<?php

namespace App\Interfaces;

interface AutomatableOperation
{
    public function refresh();

    public function customer();

    public function save(array $options = []); // No return type hint to match implementation.
}
