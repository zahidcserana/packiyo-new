<?php

namespace App\Models\AutomationConditions;

use App\Enums\EventUser;
use App\Exceptions\AutomationException;
use App\Interfaces\AutomatableEvent;
use App\Interfaces\AutomationBaseObjectInterface;
use App\Interfaces\AutomationConditionInterface;
use App\Models\AutomationCondition;
use App\Models\Automations\AppliesToMany;
use App\Models\Automations\OrderAutomation;
use App\Models\Customer;
use App\Traits\inheritanceHasParent;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class EventCustomerTypeCondition extends AutomationCondition implements AutomationConditionInterface, AutomationBaseObjectInterface
{
    use HasFactory, inheritanceHasParent, AppliesToMany;

    protected $fillable = [
        'text_field_values',
    ];

    protected $casts = [
        'text_field_values' => 'array',
    ];

    public static function getSupportedEvents(): array
    {
        return OrderAutomation::getSupportedEvents();
    }

    /**
     * @description
     * // Use the point of reference of the operation's customer ID.
     * // Is 3PL:
     * //    Operation customer has parent (meaning it's a 3PL client) and the parent customer ID is among the user's customer IDs.
     * //  Or
     * //    Operation customer is 3PL and 3PL customer ID is among the user's customer IDs.
     * // Is 3PL client:
     * //   Operation customer has parent (meaning it's a 3PL client) and the parent customer ID is *not* among the user's customer IDs.
     * // Is error:
     * //   Operation customer is 3PL and 3PL is *not* among user's customer IDs.
     * //  Or
     * //   Neither operation customer nor parent are among user's customer IDs.
     * //  Or
     * //   Operation customer is standalone
     * @param AutomatableEvent $event
     * @return bool
     */
    public function match(AutomatableEvent $event): bool
    {
        $clientMatch = false;
        $threePLMatch = false;
        $isError = false;

        $eventUser = $event->getUser();
        $userCustomerIds = collect($eventUser->customerIds(includeDisabled: true, ignoreSession: true, useSelf: true));
        /** @var Customer $operationCustomer */
        $operationCustomer = $event->getOperation()->customer;

        if ($operationCustomer->is3plChild()) {
            $threePLInUser = $userCustomerIds->contains($operationCustomer->parent_id);
            $clientInUser = $userCustomerIds->contains($operationCustomer->id);

            $clientMatch = !$threePLInUser;
            $threePLMatch = $threePLInUser;
            $isError = !$threePLInUser && !$clientInUser;
        } elseif ($operationCustomer->is3pl()) {
            $threePLMatch = $userCustomerIds->contains($operationCustomer->id);
            $isError = !$threePLMatch;
        } elseif ($operationCustomer->isStandalone()) {
            $isError = true;
        }

        if ($isError) {
            // Don't stop automation, just log error and return false.
            throw new AutomationException(sprintf(
                '[Operation(order)id:%s][User event id: %s][Customer operation id: %s] Operation Customer not valid',
                $event->getOperation()->id,
                $eventUser->id,
                $operationCustomer->id
            ));
        }

        if (EventUser::from($this->text_field_values[0]) == EventUser::CUSTOMER_IS_3PL) {
            return $threePLMatch;
        } else {
            // Deliberately ignoring standalone accounts.
            return $clientMatch;
        }
    }

    public static function getBuilderColumns(): array
    {
        return [
            'type' => self::getTriggerPathByCondition(self::class),
        ];
    }

    public function getTitleAttribute(): String
    {
        return 'Event customer type';
    }

    public function getDescriptionAttribute(): String
    {
        return $this->getTitleAttribute();
    }
}
