<?php

namespace App\Models\AutomationConditions;

use App\Exceptions\AutomationException;
use App\Interfaces\AutomatableEvent;
use App\Interfaces\AutomationBaseObjectInterface;
use App\Interfaces\AutomationConditionInterface;
use App\Models\AutomationCondition;
use App\Models\Automations\AppliesToMany;
use App\Models\Automations\AppliesToOperationTags;
use App\Models\Automations\OrderAutomation;
use App\Models\Tag;
use App\Traits\inheritanceHasParent;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

class OrderTagsCondition extends AutomationCondition
    implements AutomationConditionInterface, AutomationBaseObjectInterface
{
    use HasFactory, inheritanceHasParent, AppliesToMany;

    protected $fillable = [
        'applies_to',
    ];

    protected $casts = [
        'applies_to' => AppliesToOperationTags::class,
    ];

    protected $attributes = [
        'applies_to' => AppliesToOperationTags::ALL,
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

    public function relationshipsForClone(AutomationConditionInterface $condition): void
    {
        if (!$condition instanceof OrderTagsCondition) {
            throw new AutomationException('Wrong subtype of condition.');
        }

        $condition->tags()->attach($this->tags->pluck('id')->toArray());
        $condition->save();
    }

    public function match(AutomatableEvent $event): bool
    {
        $order = $event->getOperation();
        $matches = false;

        $triggerTags = $this->tags->pluck('name');
        $orderTags = $order->tags->pluck('name');

        $triggerTags = $triggerTags->map(fn (string $tag) => strtolower($tag));
        $orderTags = $orderTags->map(fn (string $tag) => strtolower($tag));

        if ($this->applies_to == AppliesToOperationTags::SOME) {
            $matches = $triggerTags->intersect($orderTags)->isNotEmpty();
        } elseif ($this->applies_to == AppliesToOperationTags::ALL) {
            $matches = $triggerTags->diff($orderTags)->isEmpty();
        } elseif ($this->applies_to == AppliesToOperationTags::NONE) {
            $matches = $triggerTags->intersect($orderTags)->isEmpty();
        }

        return $matches;
    }

    public static function getBuilderColumns(): array
    {
        return [
            'type' => self::getTriggerPathByCondition(self::class),
        ];
    }

    public function getTitleAttribute(): String
    {
        return 'Order tag(s)';
    }

    public function getDescriptionAttribute(): string
    {
        return sprintf('%s is in list (%s)', $this->getTitleAttribute(), implode(',',
            $this->tags()->pluck('name')->toArray()));
    }
}
