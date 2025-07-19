import {
    createData,
    currentAccountId,
    currentUserId,
    fetchData,
    updateData,
    uploadFile
} from "./auth.server.js"

interface Setting {
    data: {
        type: string;
        id?: string;
        attributes: {
            key: string;
            value: string;
        };
        relationships?: {
            customer?: {
                data: {
                    type: string,
                    id: string
                }
            },
            user?: {
                data: {
                    type: string,
                    id: string
                }
            },
        }
    };
}

export const uploadImage = async ({ request, formData }) => {
    const accountId = await currentAccountId({ request });

    await uploadFile(request, `customers/${accountId}/upload-image`, formData);
}

export const saveUserSettings = async ({ request, formData }) => {
    const userId = await currentUserId({ request });
    const regex = /user_settings\[(\d+|undefined)\]\[(\w+)\]/;
    const settings = [];

    for (const [key, value] of formData.entries()) {
        const match = key.match(regex);

        if (match) {
            const id = match[1];
            const attributeKey = match[2];

            const setting: Setting = {
                data: {
                    type: 'user-settings',
                    ...(id !== "undefined" && { id: id }),
                    attributes: {
                        key: attributeKey,
                        value: value
                    },
                    relationships: {
                        user: {
                            data: {
                                type: "users",
                                id: `${userId}`
                            }
                        }
                    }
                }
            };

            settings.push(setting);
        }
    }

    if (settings.length > 0) {
        try {
            const response = await Promise.all(settings.map(setting => {
                if (setting?.data?.id !== undefined) {
                    return updateData(request, 'user-settings/' + setting.data.id, setting);
                } else {
                    return createData(request, 'user-settings', setting);
                }
            }));

            return response[0];
        } catch (error) {
            return error;
        }
    }

    return { success: false, message: 'No settings to update.' };
};

export const saveCustomerSettings = async ({ request, formData }) => {
    const accountId = await currentAccountId({ request });
    const regex = /customer_settings\[(\d+|undefined)\]\[(\w+)\]/;
    const settings = [];

    for (const [key, value] of formData.entries()) {
        const match = key.match(regex);

        if (match) {
            const id = match[1];
            const attributeKey = match[2];

            const setting: Setting = {
                data: {
                    type: 'customer-settings',
                    ...(id !== "undefined" && { id: id }),
                    attributes: {
                        key: attributeKey,
                        value: value
                    },
                    relationships: {
                        customer: {
                            data: {
                                type: "customers",
                                id: `${accountId}`
                            }
                        }
                    }
                }
            };

            settings.push(setting);
        }
    }
    if (settings.length > 0) {
        try {
            const response = await Promise.all(settings.map(setting => {
                if (setting?.data?.id !== undefined) {
                    return updateData(request, 'customer-settings/' + setting.data.id, setting);
                } else {
                    return createData(request, 'customer-settings', setting);
                }
            }));

            return response[0];
        } catch (error) {
            return error;
        }
    }

    return { success: false, message: 'No settings to update.' };
};

interface ContactInformation {
    data: {
        type: string;
        id: string;
        attributes: {
            name?: string,
            company_name?: string,
            company_number?: string,
            address?: string,
            address2?: string,
            city?: string,
            state?: string,
            zip?: string,
            timezone?: string,
            email?: string,
            phone?: string,
            country_id?: number,
        };
    };
}

export const saveContactInformation = async ({request, formData}) => {
    const regex = /^contact_information\[([^\]]+)\]$/;

    const contactInformation: ContactInformation = {
        data: {
            type: 'contact-informations',
            id: '',
            attributes: {

            }
        }
    };

    for (const [key, value] of formData.entries()) {
        const match = key.match(regex);

        if (match) {
            const attributeKey = match[1];

            if (attributeKey === 'id') {
                contactInformation.data.id = value;
                continue;
            }

            contactInformation.data.attributes = {
                ...contactInformation.data.attributes,
                [attributeKey]: attributeKey === 'country_id' ? parseInt(value) : value
            };
        }
    }

    return await updateData(request, 'contact-informations/' + contactInformation.data.id, contactInformation);
};

interface Customer {
    data: {
        type: string;
        attributes: {
            user_settings_data?: {
                timezone?: string
            },
            customer_settings_data?: {
                locale?: string,
                currency?: string,
                weight_unit?: string,
                dimension_unit?: string,
                allow_child_customers?: string,
            },
            contact_information_data?: {
                parent_customer_id?: string,
                name?: string,
                company_name?: string,
                company_number?: string,
                country_id?: string,
            }
        };
    };
}

export const createCustomer = async ({ request, formData }) => {
    const accountId = await currentAccountId({ request });

    const contactInformation: Customer = {
        data: {
            type: 'customers',
            attributes: {
                user_settings_data: {
                    timezone: formData.get('user_settings[timezone]')
                },
                customer_settings_data: {
                    locale: formData.get('customer_settings[locale]'),
                    currency: formData.get('customer_settings[currency]'),
                    weight_unit: formData.get('customer_settings[weight_unit]'),
                    dimension_unit: formData.get('customer_settings[dimension_unit]'),
                    allow_child_customers: formData.get('customer_settings[allow_child_customers]'),
                },
                contact_information_data: {
                    parent_customer_id: accountId,
                    name: formData.get('contact_information[name]'),
                    company_name: formData.get('contact_information[company_name]'),
                    company_number: formData.get('contact_information[company_number]'),
                    country_id: formData.get('contact_information[country_id]'),
                }
            }
        }
    };

    return await createData(request, 'customers', contactInformation);
};

interface RateCards {
    data: {
        type: string;
        id: string|undefined;
        attributes: {
            rate_cards_data?: {
                primary_rate_card_id?: string,
                secondary_rate_card_id?: string,
            }
        };
    };
}

export const saveCustomerRateCards = async ({request, formData}) => {
    const accountId:string|undefined = await currentAccountId({ request });

    const primaryRateCardId = formData.get('primary_rate_card_id');
    const secondaryRateCardId = formData.get('secondary_rate_card_id');

    if (primaryRateCardId === secondaryRateCardId) {
        return {
            success: false,
            message: "Can't assign same card to both."
        }
    }

    const rateCards: RateCards = {
        data: {
            type: 'customers',
            id: accountId,
            attributes: {
                rate_cards_data: {
                    primary_rate_card_id: primaryRateCardId,
                    secondary_rate_card_id: secondaryRateCardId,
                }
            }
        }
    };

    return await updateData(request, 'customers/' + accountId, rateCards);
};

export const getCustomerContactInformation = async ({ request }) => {
    const accountId = await currentAccountId({ request });

    const response = await fetchData(request, 'customers/' + accountId + '/?include=contact_information');

    const data = response.included?.find(data => data.type === 'contact-informations')

    return {
        id: data?.id,
        ...data?.attributes,
    };
};

export const getCustomerSettings = async ({ request }) => {
    const accountId = await currentAccountId({ request });

    const response = await fetchData(request, 'customers/' + accountId + '/?include=customer_settings');

    const data = response.included?.filter(data => data.type === 'customer-settings') ?? [];

    return data.map(setting => setting?.attributes);
};

export const getCustomerShippingMethods = async ({ request }) => {
    const accountId = await currentAccountId({ request });

    const response = await fetchData(request, 'customers/' + accountId + '/?include=shipping_methods.shipping_carrier');

    const shippingMethods = response.included?.filter(data => data.type === 'shipping-methods') ?? [];
    const shippingCarriers = response.included?.filter(data => data.type === 'shipping-carriers') ?? [];

    return Object.fromEntries(shippingMethods.map(shippingMethod => [shippingMethod?.id, (shippingCarriers?.find(shippingCarrier => shippingCarrier.id === shippingMethod.relationships.shipping_carrier.data.id)?.attributes.name + ' - ' + shippingMethod?.attributes.name)]));
};

export const getCustomerShippingBoxes = async ({ request }) => {
    const accountId = await currentAccountId({ request });

    const response = await fetchData(request, 'customers/' + accountId + '/?include=shipping_boxes');

    const data = response.included?.filter(data => data.type === 'shipping-boxes') ?? [];

    return Object.fromEntries(data.map(shippingBox => [shippingBox?.id, shippingBox?.attributes.name]));
};

export const getCustomerPrinters = async ({ request }) => {
    const accountId = await currentAccountId({ request });

    const response = await fetchData(request, 'customers/' + accountId + '/?include=printers');

    const data = response.included?.filter(data => data.type === 'printers') ?? [];

    return Object.fromEntries([[' ', 'PDF'], ...data.map(printer => [printer?.id, printer?.attributes.name])]);
};

export const getUserSettings = async ({ request }) => {
    const userId = await currentUserId({ request });

    const response = await fetchData(request, `users/${userId}/?include=user_settings`);

    const data = response.included?.filter(data => data.type === 'user-settings') ?? [];

    return data.map(setting => setting?.attributes);
};

export const getAccountImages = async ({ request }) => {
    const accountId = await currentAccountId({ request });

    const response = await fetchData(request, 'customers/' + accountId + '/?include=images');

    const images = response.included?.filter(data => data.type === 'images') ?? [];
    const accountLogo = images.find(image => image?.attributes?.image_type === 'account_logo');
    const accountFavicon = images.find(image => image?.attributes?.image_type === 'account_favicon');
    const orderSlipLogo = images.find(image => image?.attributes?.image_type === 'order_slip_logo');

    return {
        account_logo: {
            id: accountLogo?.id,
            source: accountLogo?.attributes?.source
        },
        account_favicon: {
            id: accountFavicon?.id,
            source: accountFavicon?.attributes?.source
        },
        order_slip_logo: {
            id: orderSlipLogo?.id,
            source: orderSlipLogo?.attributes?.source
        },
    };
};

export const getStaticUserData = async ({ request }) => {
    const userId = await currentUserId({ request });

    const response = await fetchData(request, `users/${userId}/`);

    return {
        countries: response.data.attributes?.countries ?? [],
        timezones: response.data.attributes?.timezones ?? [],
        locales: response.data.attributes?.locales ?? '',
        currencies: response.data.attributes?.currencies ?? [],
    };
};

export const getStaticCustomerData = async ({ request }) => {
    const accountId = await currentAccountId({ request });

    const response = await fetchData(request, `customers/${accountId}/`);

    return {
        weight_units: response.data.attributes?.weight_units ?? [],
        dimensions: response.data.attributes?.dimensions ?? [],
        picking_route_strategies: response.data.attributes?.picking_route_strategies ?? [],
        picking_route_strategy: response.data.attributes?.picking_route_strategy ?? '',
        return_shipping_methods: response.data.attributes?.return_shipping_methods ?? [],
        lot_priorities: response.data.attributes?.lot_priorities ?? [],
    };
};

export const getRateCards = async ({ request }) => {
    const accountId = await currentAccountId({ request });

    const response = await fetchData(request, 'customers/' + accountId + '/?include=rate_cards');

    return {
        primary_rate_card: response.data.attributes?.primary_rate_card,
        secondary_rate_card: response.data.attributes?.secondary_rate_card,
        rate_cards: response.data.attributes?.available_rate_cards
    };
};

export const getEasypostCredentials = async ({ request }) => {
    const accountId = await currentAccountId({ request });

    const response = await fetchData(request, '/customers/' + accountId + '/?include=easypost_credentials');

    const easypostCredentials = response.included?.filter(data => data.type === 'easypost-credentials') ?? [];

    return {
        'easypost_credentials': easypostCredentials,
        'endorsements': response.data.attributes.endorsements
    };
};

export const getWebshipperCredentials = async ({ request }) => {
    const accountId = await currentAccountId({ request });

    const response = await fetchData(request, '/customers/' + accountId + '/?include=webshipper_credentials');

    const webshipperCredentials = response.included?.filter(data => data.type === 'webshipper-credentials') ?? [];

    return {
        'webshipper_credentials': webshipperCredentials,
    };
};

export const getConnections = async ({ request }) => {
    const accountId = await currentAccountId({ request });

    const response = await fetchData(request, '/customers/' + accountId + '/?include=order_channels');

    return response.included?.filter(data => data.type === 'order-channels') ?? [];
};

export const getAvailableConnections = async ({ request }) => {
    const response = await fetchData(request, 'order-channels/available-connections/');

    return response.data;
};

