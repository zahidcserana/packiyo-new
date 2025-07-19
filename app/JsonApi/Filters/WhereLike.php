<?php

namespace App\JsonApi\Filters;

use LaravelJsonApi\Core\Support\Str;
use LaravelJsonApi\Eloquent\Contracts\Filter;
use LaravelJsonApi\Eloquent\Filters\Concerns\DeserializesValue;
use LaravelJsonApi\Eloquent\Filters\Concerns\IsSingular;

class WhereLike implements Filter
{
    use DeserializesValue;
    use IsSingular;

    /**
     * @var string
     */
    private string $name;

    /**
     * @var string
     */
    private string $column;

    /**
     * @var bool
     */
    private string $partial;

    /**
     * Create a new filter.
     *
     * @param string $name
     * @param string|null $column
     * @param bool $partial
     * @return WhereLike
     */
    public static function make(string $name, string $column = null, $partial = true): self
    {
        return new static($name, $column, $partial);
    }

    /**
     * WhereLike constructor.
     *
     * @param string $name
     * @param string|null $column
     * @param bool $partial
     */
    public function __construct(string $name, string $column = null, $partial = true)
    {
        $this->name = $name;
        $this->column = $column ?: Str::underscore($name);
        $this->partial = $partial;
    }

    /**
     * Get the key for the filter.
     *
     * @return string
     */
    public function key(): string
    {
        return $this->name;
    }

    /**
     * Apply the filter to the query.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param mixed $value
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function apply($query, $value)
    {
        $columns = explode('|', $this->column);

        $query->where(function ($query) use ($columns, $value) {
            foreach ($columns as $column) {
                $fields = explode('.', $column);
                $name = array_pop($fields);
                $relation = array_pop($fields);

                if ($this->partial) {
                    $value = '%' . $value . '%';
                }

                if ($relation) {
                    $query->orWhereHas($relation, function ($query) use ($name, $value) {
                        $query->where($name, 'like', $this->deserialize($value));
                    });
                } else {
                    $query->orWhere($query->getModel()->qualifyColumn($name), 'like', $this->deserialize($value));
                }
            }
        });

        return $query;
    }
}
