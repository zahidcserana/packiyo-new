<?php

namespace App\Models\CacheDocuments;

interface ChargeCacheDocumentInterface
{

    public function getCharges();

    public function getBillingRate();
}
