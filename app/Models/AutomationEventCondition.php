<?php

namespace App\Models;

use App\Interfaces\AutomatableEvent;
use App\Interfaces\AutomatableOperation;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Parental\HasChildren;

class AutomationEventCondition extends Model
{
    use HasChildren;

    protected $fillable = [
        'type'
    ];

    public function automation(): BelongsTo
    {
        return $this->belongsTo(Automation::class);
    }

    public function getEvent(AutomatableOperation $operation): AutomatableEvent
    {
        return new (static::getEventClass())($this, $operation);
    }
}
