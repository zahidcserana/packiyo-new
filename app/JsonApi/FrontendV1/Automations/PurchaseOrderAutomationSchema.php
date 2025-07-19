<?php

namespace App\JsonApi\FrontendV1\Automations;

use App\Models\Automations\PurchaseOrderAutomation;

class PurchaseOrderAutomationSchema extends AutomationSchema
{
    /**
     * The model the schema corresponds to.
     *
     * @var string
     */
    public static string $model = PurchaseOrderAutomation::class;
}
