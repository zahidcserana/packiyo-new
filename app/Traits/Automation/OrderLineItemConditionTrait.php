<?php

namespace App\Traits\Automation;

use App\Models\Automations\AppliesToLineItems;
use App\Models\Automations\AppliesToSingle;
use App\Models\Automations\NumberComparison;
use App\Models\Automations\OrderAutomation;
use App\Interfaces\AutomatableEvent;
use App\Models\OrderItem;
use App\Models\Product;
use App\Traits\inheritanceHasParent;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

trait OrderLineItemConditionTrait
{
    use HasFactory, inheritanceHasParent, AppliesToSingle;

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
            'applies_to' => AppliesToLineItems::class,
            'comparison_operator' => NumberComparison::class,
        ];
    }

    public function matchesProducts(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'automation_trigger_matches_product', 'automation_trigger_id', 'product_id');
    }

    public static function getSupportedEvents(): array
    {
        return OrderAutomation::getSupportedEvents();
    }

    public static function loadForCommand(): array
    {
        return ['matchesProducts'];
    }

    public function match(AutomatableEvent $event): bool
    {
        $order = $event->getOperation();
        $orderItems = $order->orderItems;

        if ($this->applies_to == AppliesToLineItems::SOME) {
            $productIds = $this->matchesProducts->pluck('id')->toArray();
            $orderItems = $orderItems->filter(fn (OrderItem $item) => in_array($item->product_id, $productIds));
        }

        if ($orderItems->count() > 0) {
            $quantity = $orderItems->reduce(fn (int $carry, OrderItem $item) => $item->quantity + $carry, 0);

            return $this->matchByComparison($quantity);
        } else {
            return false;
        }

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

    public function getDescriptionAttribute(): string
    {
        return sprintf(
            '%s %s %s',
            $this->getTitleAttribute(),
            NumberComparison::getReadableText($this->comparison_operator),
            $this->number_field_value
        );
    }
}
