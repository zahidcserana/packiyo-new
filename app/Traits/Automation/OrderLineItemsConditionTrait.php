<?php

namespace App\Traits\Automation;

use App\Exceptions\AutomationException;
use App\Models\Automations\AppliesToItemsQuantity;
use App\Models\Automations\AppliesToMany;
use App\Models\Automations\NumberComparison;
use App\Models\Automations\OrderAutomation;
use App\Interfaces\AutomatableEvent;
use App\Models\OrderItem;
use App\Traits\inheritanceHasParent;
use Illuminate\Database\Eloquent\Factories\HasFactory;

trait OrderLineItemsConditionTrait
{
    use HasFactory, inheritanceHasParent, AppliesToMany;

    public function __construct(array $attributes = [])
    {
        $this->fillable = self::getFillableColumns();
        $this->casts = self::getCastColumns();

        parent::__construct($attributes);
    }

    public static function getFillableColumns() : array
    {
        return [
            'applies_to',
            'number_field_value',
            'comparison_operator'
        ];
    }

    public static function getCastColumns() : array
    {
        return [
            'applies_to' => AppliesToItemsQuantity::class,
            'comparison_operator' => NumberComparison::class,
        ];
    }

    public static function getSupportedEvents(): array
    {
        return OrderAutomation::getSupportedEvents();
    }

    public function match(AutomatableEvent $event): bool
    {
        $order = $event->getOperation();
        $matches = false;

        if ($this->applies_to == AppliesToItemsQuantity::TOTAL) {
            $quantity = $order->orderItems->reduce(fn (int $carry, OrderItem $item) => $item->quantity + $carry, 0);
            $matches = $this->matchByComparison($quantity);
        } elseif ($this->applies_to == AppliesToItemsQuantity::ANY) {
            $matches = (bool) $order->orderItems->first(fn (OrderItem $item) => $this->matchByComparison($item->quantity));
        } elseif ($this->applies_to == AppliesToItemsQuantity::NONE) {
            $matches = !(bool) $order->orderItems->first(fn (OrderItem $item) => $this->matchByComparison($item->quantity));
        } elseif ($this->applies_to == AppliesToItemsQuantity::EACH) {
            $matches = $order->orderItems->every(fn (OrderItem $item) => $this->matchByComparison($item->quantity));
        } else {
            throw new AutomationException('Misconfigured automation - unhandled enum value.');
        }

        return $matches;
    }

    protected function matchByComparison(int $quantity): bool
    {
        $matches = false;

        if ($this->comparison_operator == NumberComparison::EQUAL) {
            $matches = $quantity == $this->number_field_value;
        } elseif ($this->comparison_operator == NumberComparison::NOT_EQUAL) {
            $matches = $quantity != $this->number_field_value;
        } elseif ($this->comparison_operator == NumberComparison::GREATER) {
            $matches = $quantity > $this->number_field_value;
        } elseif ($this->comparison_operator == NumberComparison::LESSER) {
            $matches = $quantity < $this->number_field_value;
        } elseif ($this->comparison_operator == NumberComparison::GREATER_OR_EQUAL) {
            $matches = $quantity >= $this->number_field_value;
        } elseif ($this->comparison_operator == NumberComparison::LESSER_OR_EQUAL) {
            $matches = $quantity <= $this->number_field_value;
        }

        return $matches;
    }
}
