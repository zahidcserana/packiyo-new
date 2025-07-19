<?php

namespace App\Models\AutomationConditions;

use App\Interfaces\AutomatableEvent;
use App\Interfaces\AutomatableOperation;
use App\Interfaces\AutomationBaseObjectInterface;
use App\Interfaces\AutomationConditionInterface;
use App\Models\AutomationCondition;
use App\Models\Automations\AppliesToItemsTags;
use App\Models\Automations\AppliesToSingle;
use App\Models\Automations\OrderAutomation;
use App\Models\OrderItem;
use App\Models\Tag;
use App\Traits\inheritanceHasParent;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

class OrderItemTagsCondition extends AutomationCondition
    implements AutomationConditionInterface, AutomationBaseObjectInterface
{
    use HasFactory, inheritanceHasParent, AppliesToSingle;

    protected $fillable = [
        'applies_to'
    ];

    protected $casts = [
        'applies_to' => AppliesToItemsTags::class
    ];

    protected $attributes = [
        'applies_to' => AppliesToItemsTags::SOME
    ];

    public function tags(): MorphToMany
    {
        return $this->morphToMany(Tag::class, 'taggable');
    }

    public static function getSupportedEvents(): array
    {
        return OrderAutomation::getSupportedEvents();
    }

    public static function loadForCommand(): array
    {
        return ['tags'];
    }

    public function match(AutomatableEvent $event): bool
    {
        $order = $event->getOperation();
        $matches = false;

        if ($this->applies_to == AppliesToItemsTags::SOME) {
            $matches = $this->matchesSomeItems($order);
        } elseif ($this->applies_to == AppliesToItemsTags::ALL) {
            $matches = $this->matchesAllItems($order);
        } elseif ($this->applies_to == AppliesToItemsTags::NONE) {
            $matches = $this->matchesNoItems($order);
        }

        return $matches;
    }

    protected function matchesSomeItems(AutomatableOperation $order): bool
    {
        foreach ($order->orderItems as $orderItem) {
            if ($this->orderItemHasTags($orderItem)) {
                return true;
            }
        }

        return false;
    }

    protected function matchesAllItems(AutomatableOperation $order): bool
    {
        foreach ($order->orderItems as $orderItem) {
            if (!$this->orderItemHasTags($orderItem)) {
                return false;
            }
        }

        return true;
    }

    protected function matchesNoItems(AutomatableOperation $order): bool
    {
        foreach ($order->orderItems as $orderItem) {
            if ($this->orderItemHasTags($orderItem)) {
                return false;
            }
        }

        return true;
    }

    protected function orderItemHasTags(OrderItem $orderItem): bool
    {
        if (!$orderItem->product) {
            return false;
        }

        $triggerTags = $this->tags->pluck('name')->map(fn (string $tag) => strtolower($tag));
        $orderItemTags = $orderItem->product->tags->pluck('name')->map(fn (string $tag) => strtolower($tag));

        return $triggerTags->diff($orderItemTags)->isEmpty();
    }

    public static function getBuilderColumns(): array
    {
        return [
            'type' => self::getTriggerPathByCondition(self::class),
        ];
    }

    public function getTitleAttribute(): String
    {
        return 'Order Item Tags';
    }

    public function getDescriptionAttribute(): String
    {
        return $this->getTitleAttribute();
    }
}
