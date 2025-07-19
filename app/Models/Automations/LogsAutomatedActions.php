<?php

namespace App\Models\Automations;

use App\Interfaces\AutomatableEvent;
use App\Interfaces\AutomatableOperation;
use App\Models\Customer;
use App\Models\Tag;

const AUTOMATED_OPERATION_TAG = 'Co-Pilot';

trait LogsAutomatedActions
{
    public function tagOperation(AutomatableOperation $operation)
    {
        if (method_exists($operation, 'tags')) {
            $customer = self::getStandaloneOr3pl($operation->customer);

            if (!$operation->tags()->where('name', AUTOMATED_OPERATION_TAG)->first()) {
                $tag = Tag::firstOrCreate(['customer_id' => $customer->id, 'name' => AUTOMATED_OPERATION_TAG]);
                $operation->tags()->attach($tag->id);
            }
        }
    }

    protected static function getStandaloneOr3pl(Customer $customer): Customer
    {
        return $customer->is3plChild() ? $customer->parent : $customer;
    }

    public function logAction(AutomatableOperation $operation, AutomatableEvent $event)
    {
        $this->actedOnOperations(latestRevision: true)->attach($operation->id, [
            'target_event' => $event::class,
            'original_revision_automation_id' => $this->original_revision_automation_id,
            'operation_type' => $operation::class
        ]);
    }
}
