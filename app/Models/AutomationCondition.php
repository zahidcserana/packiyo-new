<?php

namespace App\Models;

use App\Interfaces\AutomatableEvent;
use App\Interfaces\AutomationConditionInterface;
use App\Traits\inheritanceHasChildren;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AutomationConditionResult
{
    public bool $result;
    public bool|null $isAlternative;

    public function __construct(bool $result, bool|null $isAlternative = null)
    {
        $this->result = $result;
        $this->isAlternative = $isAlternative;
    }

    public function link(AutomationConditionResult $prevConditionResult): self
    {
        return new self(
            $this->isAlternative
                ? $prevConditionResult->result || $this->result
                : $prevConditionResult->result && $this->result
        );
    }

    public function toBool(): bool
    {
        return $this->result;
    }
}

class AutomationCondition extends Model
{
    use inheritanceHasChildren;

    protected $table = 'automation_triggers';

    protected $fillable = [
        'id',
        'type',
        'position',
        'is_alternative'
    ];

    protected $casts = [
        'is_alternative' => 'bool'
    ];

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);

        $this->mergeFillable(parent::getFillable());
    }

    public function automation(): BelongsTo
    {
        return $this->belongsTo(Automation::class);
    }

    public static function loadForCommand(): array
    {
        return [];
    }

    public function relationshipsForClone(AutomationConditionInterface $condition): void
    {
        return;
    }

    public function matchForLink(AutomatableEvent $event): AutomationConditionResult
    {
        return new AutomationConditionResult($this->match($event), $this->is_alternative);
    }

    public static function getTriggerPathByCondition($conditionName) : string
    {
        return str_replace('Condition', 'Trigger', $conditionName);
    }

    public static function registerChildCallback($conditionClassName): void
    {
        static::$childrenBuilderCallbacks[$conditionClassName] = $conditionClassName::getBuilderColumns();
    }
}
