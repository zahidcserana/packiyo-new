<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;
use Illuminate\Support\Facades\DB;

class ExistsOrStaticValues implements Rule
{
    private $table;
    private $column;
    private $values;

    /**
     * Create a new rule instance.
     *
     * @return void
     */
    public function __construct($table, $column, array $values)
    {
        $this->table = $table;
        $this->column = $column;
        $this->values = $values;
    }

    /**
     * Determine if the validation rule passes.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @return bool
     */
    public function passes($attribute, $value): bool
    {
        if (in_array($value, $this->values)) {
            return true;
        }

        return DB::table($this->table)
            ->where($this->column, $value)
            ->exists();
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message(): string
    {
        return 'Invalid :attribute';
    }
}
