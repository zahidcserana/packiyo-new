<?php

namespace App\Traits;

use Illuminate\Events\Dispatcher;
use Parental\HasParent;

trait inheritanceHasParent
{
    use HasParent;
    public static function bootHasParent()
    {
        if (static::getEventDispatcher() === null) {
            static::setEventDispatcher(new Dispatcher());
        }

        static::creating(function ($model) {
            if ($model->parentHasHasChildrenTrait()) {
                $model->forceFill(
                    [$model->getInheritanceColumn() => $model::getBuilderColumns()[$model->getInheritanceColumn()]]
                );
            }
        });

        static::addGlobalScope(function ($query) {
            $instance = new static;
            if ($instance->parentHasHasChildrenTrait()) {
                self::mutateQueryCallback($query);
            }
        });
    }

    public static function mutateQueryCallback($query)
    {
        foreach(static::getBuilderColumns() as $column => $value) {
            $query->where($column, $value);
        }
    }
}
