<?php

namespace App\Rules;

use App\Models\Customer;
use Illuminate\Contracts\Validation\Rule;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;

class BulkPrintable implements Rule
{
    private $column;

    /**
     * Create a new rule instance.
     *
     * @return void
     */
    public function __construct($column)
    {
        $this->column = $column;
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
        $modelNames = Cache::remember('modelNames', 60*60*24, static function() {
            return collect(File::allFiles(app_path('Models')))
                ->map->getFilenameWithoutExtension()
                ->toArray();
        });

        if (in_array($value, $modelNames)) {
            $model = 'App\\Models\\' . $value;

            if (method_exists($model, 'getPrintables') && in_array($this->column, app($model)->getPrintables())) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message(): string
    {
        return 'The :attribute is not a printable entity.';
    }
}
