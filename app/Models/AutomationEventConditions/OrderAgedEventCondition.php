<?php

namespace App\Models\AutomationEventConditions;

use App\Events\OrderAgedEvent;
use App\Models\Automations\AppliesToCustomers;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\AutomationEventCondition;
use App\Models\Automations\TimeUnit;
use App\Models\Order;
use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Parental\HasParent;
use RuntimeException;

class OrderAgedEventCondition extends AutomationEventCondition
{
    use HasFactory, HasParent, ExcludesOperations;

    protected $fillable = [
        'number_field_value',
        'unit_of_measure',
        'pending_only',
        'ignore_holds'
    ];

    protected $casts = [
        'unit_of_measure' => TimeUnit::class,
        'pending_only' => 'bool',
        'ignore_holds' => 'bool'
    ];

    public function findOperations(): Builder
    {
        $timestamp = $this->getTimestamp();
        $orderCustomerQuery = $this->getOrderCustomerQuery($this->automation->applies_to);
        $query = Order::whereHas('customer', $orderCustomerQuery)->where('created_at', '<=', $timestamp);

        if ($this->pending_only) {
            $query->where(['cancelled_at' => null, 'fulfilled_at' => null]);
        }

        if (!$this->ignore_holds) {
            $query->where([
                'allocation_hold' => false,
                'operator_hold' => false,
                'payment_hold' => false,
                'fraud_hold' => false
            ]);
        }

        return $query;
    }

    public static function getEventClass(): string
    {
        return OrderAgedEvent::class;
    }

    protected function getTimestamp(): Carbon
    {
        return $this->unit_of_measure == TimeUnit::BUSINESS_DAYS
            ? sub_business_days(now(), $this->number_field_value)
            : $this->getTimestampUsingCarbon();
    }

    protected function getTimestampUsingCarbon(): Carbon
    {
        $method = static::getSubTimeMethod($this->unit_of_measure);

        return now()->$method($this->number_field_value);
    }

    protected static function getSubTimeMethod(TimeUnit $timeUnit): string
    {
        return 'sub' . ucfirst($timeUnit->value);
    }

    /**
     * TODO: Move to trait.
     */
    protected function getOrderCustomerQuery(AppliesToCustomers $appliesTo): Closure
    {
        $orderCustomerQuery = null;

        if ($appliesTo == AppliesToCustomers::OWNER) {
            $orderCustomerQuery = fn (Builder $query)
                => $query->where('id', $this->automation->customer_id);
        } elseif ($appliesTo == AppliesToCustomers::ALL) {
            $orderCustomerQuery = fn (Builder $query)
                => $query->where('parent_id', $this->automation->customer_id);
        } elseif ($appliesTo == AppliesToCustomers::SOME) {
            $orderCustomerQuery = fn (Builder $query)
                => $query->whereIn('id', $this->automation->appliesToCustomers()->pluck('customer_id'));
        } else {
            throw new RuntimeException('Invalid value for argument $appliesTo.');
        }

        return $orderCustomerQuery;
    }
}
