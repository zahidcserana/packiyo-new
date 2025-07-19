<?php

namespace App\JsonApi\Filters;

use App\Exceptions\AutomationException;
use App\Models\Automation;
use LaravelJsonApi\Eloquent\Contracts\Filter;
use LaravelJsonApi\Eloquent\Filters\Concerns\IsSingular;

class WhereNotAssignedToAutomation implements Filter
{
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
     * Create a new filter.
     *
     * @param string $name
     * @param string $column
     * @return WhereNotAssignedToAutomation
     */
    public static function make(string $name, string $column): self
    {
        return new static($name, $column);
    }

    /**
     * WhereLike constructor.
     *
     * @param string $name
     * @param string $column
     */
    public function __construct(string $name, string $column)
    {
        $this->name = $name;
        $this->column = $column;
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
     * @throws AutomationException
     */
    public function apply($query, $value)
    {
        $automation = Automation::find($value);

        if (!$automation instanceof Automation) {
            throw new AutomationException('Automation ' . $value . ' not found');
        }
        $alreadyAssigned = $automation->appliesToCustomers->pluck('id')->toArray();
        $query->whereNotIn($this->column,$alreadyAssigned);
        return $query;
    }
}
