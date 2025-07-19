<?php

namespace App\Models\Automations;

/**
 * Order text fields that can be used in automations.
 * Region codes:
 *  - "002": Africa
 *  - "009": Oceania
 *  - "019": America
 *  - "142": Asia
 *  - "150": Europe
 *  - "":    Other
 */
enum OrderTextField: string
{
    case CUSTOMER_NAME = 'customer.contactInformation.name';
    case ORDER_NUMBER = 'number';
    case CHANNEL_NAME = 'orderChannel.name';
    case CHANNEL_SHIPPING_OPTION = 'shipping_method_name';
    case SHIPPING_METHOD_NAME = 'shippingMethod.name';
    case SHIPPING_CARRIER_NAME = 'shippingMethod.shippingCarrier.name';
    // Shipping address.
    case SHIPPING_CONTINENT_CODE = 'shippingContactInformation.country.region_code';
    case SHIPPING_COUNTRY_NAME = 'shippingContactInformation.country.name';
    case SHIPPING_COUNTRY_CODE = 'shippingContactInformation.country.iso_3166_2';
    case SHIPPING_STATE = 'shippingContactInformation.state';
    case SHIPPING_POSTAL_CODE = 'shippingContactInformation.zip';
    case SHIPPING_CITY = 'shippingContactInformation.city';
    case SHIPPING_ADDRESS = 'shippingContactInformation.address';
    case SHIPPING_ADDRESS_2 = 'shippingContactInformation.address2';
    case SHIPPING_CONTACT_NAME = 'shippingContactInformation.name';
    case SHIPPING_CONTACT_COMPANY = 'shippingContactInformation.company_name';
    case SHIPPING_CONTACT_EMAIL = 'shippingContactInformation.email';
    case SHIPPING_CONTACT_PHONE = 'shippingContactInformation.phone';
    // Billing address.
    case BILLING_CONTINENT_CODE = 'billingContactInformation.country.region_code';
    case BILLING_COUNTRY_NAME = 'billingContactInformation.country.name';
    case BILLING_COUNTRY_CODE = 'billingContactInformation.country.iso_3166_2';
    case BILLING_STATE = 'billingContactInformation.state';
    case BILLING_POSTAL_CODE = 'billingContactInformation.zip';
    case BILLING_CITY = 'billingContactInformation.city';
    case BILLING_ADDRESS = 'billingContactInformation.address';
    case BILLING_ADDRESS_2 = 'billingContactInformation.address2';
    case BILLING_CONTACT_NAME = 'billingContactInformation.name';
    case BILLING_CONTACT_COMPANY = 'billingContactInformation.company_name';
    case BILLING_CONTACT_EMAIL = 'billingContactInformation.email';
    case BILLING_CONTACT_PHONE = 'billingContactInformation.phone';
    case EXTERNAL_ID = 'external_id';
    case SLIP_NOTE = 'slip_note';
    case PACKING_NOTE = 'packing_note';
    case INTERNAL_NOTE = 'internal_note';
    case GIFT_NOTE = 'gift_note';
    case INCOTERMS = 'incoterms';

    public static function readable(): array
    {
        return static::cases();
    }

    public static function writable(): array
    {
        return array_filter(static::cases(), fn ($field) => strpos($field->value, '.') === false);
    }
}
