<?php

namespace App\Models\AutomationActions;

use App\Exceptions\AutomationException;
use App\Interfaces\AutomatableEvent;
use App\Interfaces\AutomationBaseObjectInterface;
use App\Traits\inheritanceHasParent;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Interfaces\AutomationActionInterface;
use App\Models\AutomationAction;
use App\Models\Automations\AppliesToMany;
use App\Models\Automations\OrderAutomation;
use App\Models\Tag;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

class AddTagsAction extends AutomationAction implements AutomationActionInterface, AutomationBaseObjectInterface
{
    use HasFactory, inheritanceHasParent, AppliesToMany;

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

    public function relationshipsForClone(AutomationActionInterface $action): void
    {
        if (!$action instanceof AddTagsAction) {
            throw new AutomationException('Wrong subtype of condition.');
        }

        $action->tags()->attach($this->tags->pluck('id')->toArray());
        $action->save();
    }

    public function run(AutomatableEvent $event): void
    {
        $operation = $event->getOperation();
        $tags = [];

        foreach ($this->tags as $tag) {
            $templatableAttributes = $this->automation::getTemplatableAttributes();
            $rendered = render_attributes_template($operation, $tag->name, placeholders: $templatableAttributes);
            $tags[] = $rendered != $tag->name ? trim(str_replace(' ', '-', $rendered)) : $tag->name;
        }

        // Part of BaseComponent, not exclusive to orders.
        app('order')->updateTags($tags, $operation);
    }

    public static function getBuilderColumns(): array
    {
        return [
            'type' => self::class,
        ];
    }

    public function getTitleAttribute(): String
    {
        return 'Add tag(s)';
    }

    public function getDescriptionAttribute(): string
    {
        return sprintf('%s: %s', $this->getTitleAttribute(), implode(',',
            $this->tags()->pluck('name')->toArray()));
    }
}

