<?php

namespace App\Traits;

use Parental\HasChildren;

trait inheritanceHasChildren
{
    use HasChildren;
    protected static array $childrenBuilderCallbacks = [];

    /**
     * @param array $attributes
     * @param null $connection
     * @return $this
     */
    public function newFromBuilder($attributes = [], $connection = null)
    {
        $attributes = (array) $attributes;
        $childModel = static::class;

        foreach (static::$childrenBuilderCallbacks as $className => $childMatches) {
            if (!isset($attributes['type'])) {
                break;
            }

            if (method_exists($className, 'getTriggerPathByCondition')) {
                $attributes['type'] = self::getTriggerPathByCondition($attributes['type']);
            }
            if ($this->checkChildMatches($childMatches, $attributes)) {

                $childModel = $className;
                break;
            }
        }

        $model = (new $childModel)->newInstance([], true);

        $model->setConnection($connection ?: $this->getConnectionName());
        $model->setRawAttributes($attributes, true);

        return $model;
    }

    private function checkChildMatches($childMatches, $attributes) : bool
    {
        foreach($childMatches as $key => $value) {
            if ($attributes[$key] != $value) {
                return false;
            }
        }

        return true;
    }

}
