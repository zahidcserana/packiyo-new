<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest as IlluminateFormRequest;
use Illuminate\Routing\Redirector;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Support\MessageBag;

class FormRequest extends IlluminateFormRequest
{
    /**
     * @var int|null
     */
    public static ?int $recordId = null;
    /**
     * @var FormRequest
     */
    public static $formRequest;

    /**
     * @var bool
     */
    public static $isBatchRequest = false;

    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    public static function make($record = [], int $recordId = null, $validate = true)
    {
        static::$recordId = $recordId;
        static::$isBatchRequest = false;
        $request = new static($record);
        $request->setContainer(app())->setRedirector(app()->make(Redirector::class));

        if ($validate) {
            $request->validateResolved();
        }

        return $request;
    }

    public static function validationRules()
    {
        return [];
    }

    public static function prefixedValidationRules($prefix, $isBatchRequest = false)
    {
        static::$isBatchRequest = $isBatchRequest;

        $rules = static::validationRules();

        foreach ($rules as $field => $fieldRules) {
            if (is_array($fieldRules)) {
                foreach ($fieldRules as &$fieldRule) {
                    if (is_string($fieldRule)) {
                        if (Str::startsWith($fieldRule, 'required_')) {
                            [$ruleName, $fieldsInRule] = explode(':', $fieldRule);
                            $fieldRule = $ruleName . ':' . implode(',', array_map(
                                fn (string $fieldInRule) => $prefix . $fieldInRule,
                                explode(',', $fieldsInRule)
                            ));
                        }
                    }
                }
            }

            $rules[$prefix . $field] = $fieldRules;

            unset($rules[$field]);
        }

        return $rules;
    }

    public static function getInputField($name)
    {
        if (static::$isBatchRequest) {
            $data = static::$formRequest->all();
            return Arr::get($data, '0.' . $name);
        }
        return static::$formRequest->get($name);
    }

    public function rules()
    {
        static::$formRequest = $this;

        return static::validationRules();
    }

    public static function getValidationErrors(array $record = [], int $recordId = null): MessageBag
    {
        $request = self::make($record, $recordId, false);
        $validator = $request->getValidatorInstance();

        return $validator->errors();
    }
}
