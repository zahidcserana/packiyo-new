<?php

namespace App\Models;

use App\Interfaces\AutomationActionInterface;
use App\Traits\inheritanceHasChildren;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AutomationAction extends Model
{
    use inheritanceHasChildren;

    protected $fillable = [
        'type',
        'position'
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

    public function relationshipsForClone(AutomationActionInterface $action): void
    {
        return;
    }

    public static function registerChildCallback($actionClassName): void
    {
        static::$childrenBuilderCallbacks[$actionClassName] = $actionClassName::getBuilderColumns();
    }
}
