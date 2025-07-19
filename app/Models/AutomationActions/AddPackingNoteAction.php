<?php

namespace App\Models\AutomationActions;

use App\Interfaces\AutomatableEvent;
use App\Interfaces\AutomationBaseObjectInterface;
use App\Traits\inheritanceHasParent;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Interfaces\AutomationActionInterface;
use App\Models\AutomationAction;
use App\Models\Automations\AppliesToMany;
use App\Models\Automations\InsertMethod;
use App\Models\Automations\OrderAutomation;

class AddPackingNoteAction extends AutomationAction implements AutomationActionInterface, AutomationBaseObjectInterface
{
    use HasFactory, inheritanceHasParent, AppliesToMany;

    protected const SIGNATURE = "\n- Co-Pilot";
    protected const SEPARATOR = "\n————————————\n";

    protected $fillable = [
        'text',
        'insert_method'
    ];

    protected $casts = [
        'insert_method' => InsertMethod::class
    ];

    public static function getSupportedEvents(): array
    {
        return OrderAutomation::getSupportedEvents();
    }

    public function run(AutomatableEvent $event): void
    {
        if (empty($this->text)) {
            return;
        }

        $order = $event->getOperation();
        $currentNote = (string) $order->packing_note;
        $newNote = static::signNote($this->text);

        if ($this->insert_method == InsertMethod::REPLACE) {
            $order->packing_note = $newNote;
        } elseif ($this->insert_method == InsertMethod::PREPEND) {
            $order->packing_note = static::prependNote($currentNote, $newNote);
        } elseif ($this->insert_method == InsertMethod::APPEND) {
            $order->packing_note = static::appendNote($currentNote, $newNote);
        }

        $order->save();
    }

    protected static function signNote(string $text): string
    {
        return $text . static::SIGNATURE;
    }

    protected static function prependNote(string $text, string $toPrepend): string
    {
        if (!empty(trim($text))) {
            $toPrepend .= static::SEPARATOR . $text;
        }

        return $toPrepend;
    }

    protected static function appendNote(string $text, string $toAppend): string
    {
        if (!empty(trim($text))) {
            $toAppend = $text . static::SEPARATOR . $toAppend;
        }

        return $toAppend;
    }

    public static function getBuilderColumns(): array
    {
        return [
            'type' => self::class,
        ];
    }

    public function getTitleAttribute(): String
    {
        return 'add packing note';
    }

    public function getDescriptionAttribute(): String
    {
        return sprintf('%s: "%s" (%s)', $this->getTitleAttribute(), $this->text, $this->insert_method?->value);
    }
}
