<?php

namespace App\JsonApi\FrontendV1\Automations;

use App\Models\Automations\OrderAutomation;

class OrderAutomationSchema extends AutomationSchema
{
    /**
     * The model the schema corresponds to.
     *
     * @var string
     */
    public static string $model = OrderAutomation::class;
}
