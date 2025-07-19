<?php

namespace App\Http\Requests\Packing;

use App\Http\Requests\ContactInformation\StoreRequest as ContactInformationStoreRequest;
use App\Http\Requests\FormRequest;
use App\Http\Requests\Shipment\ShipItemRequest;
use App\Models\Printer;
use App\Models\ShippingMethodMapping;
use App\Rules\BelongsToCustomer;
use App\Rules\ExistsOrStaticValues;
use App\Rules\HasDropPoint;
use App\Rules\OrderHasEnoughInventory;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Support\Facades\Log;

class StoreRequest extends FormRequest
{
    public static function validationRules(): array
    {
        $customerId = static::getInputField('customer_id');
        $dropPoint = static::getInputField('drop_point_id');
        $shippingMethodId = static::getInputField('shipping_method_id');

        $contactInformationRules = ContactInformationStoreRequest::prefixedValidationRules('shipping_contact_information.');

        if ($shippingMethodId !== 'generic') {
            foreach ($contactInformationRules as $attribute => $rule) {
                if (str_contains($attribute, 'zip') || str_contains($attribute, 'country_id')) {
                    $contactInformationRules[$attribute] = [
                        'required'
                    ];
                }
            }
        }

        return array_merge(
            [
                'shipping_method_id' => [
                    'required',
                    new ExistsOrStaticValues('shipping_methods', 'id', array_merge(array_keys(ShippingMethodMapping::CHEAPEST_SHIPPING_METHODS), ['generic'])),
                    new HasDropPoint($dropPoint)
                ],
                'packing_state' => [
                    'required',
                    'string',
                    new OrderHasEnoughInventory()
                ],
                'printer_id' => [
                    'nullable',
                    'exists:printers,id,deleted_at,NULL',
                    new BelongsToCustomer(Printer::class, $customerId)
                ],
                'drop_point_id' => [
                    'nullable'
                ],
                'rate' => [
                    'sometimes'
                ],
                'rate_id' => [
                    'sometimes'
                ]
            ],
            ShipItemRequest::prefixedValidationRules('order_items.*.'),
            $contactInformationRules
        );
    }

    public function messages(): array
    {
        return [
            'shipping_contact_information.country_id.required' => 'The country field is required for the shipping contact information',
            'shipping_contact_information.zip.required' => 'ZIP code is required for the shipping contact information',
            'drop_point_id.*' => 'Shipping method requires a drop point'
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        Log::info(__CLASS__, [request()->all(), $validator->errors()->all()]);

        parent::failedValidation($validator);
    }
}
