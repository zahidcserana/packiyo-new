<?php

namespace App\Interfaces;

use GuzzleHttp\Psr7\Response;

interface RequestBuilderInterface
{
    public function send(): Response;
}
