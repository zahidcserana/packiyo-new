import {
    getStaticCustomerData,
    getStaticUserData,
} from "../../.server/services/settings.js";

const AccountCreateForm = async ({ request }) => {
    const {countries, timezones, locales, currencies} = await getStaticUserData({ request });
    const {weight_units, dimensions} = await getStaticCustomerData({ request });

    return {
        title: '',
        action: '',
        actionTitle: 'Create',
        encType: 'multipart/form-data',
        method: 'post',
        fields: [
            {
                name: "contact_information[name]",
                value: '',
                title: "Account Name",
                description: "",
                type: "text",
                required: true
            },
            {
                name: "contact_information[company_name]",
                value: '',
                title: "Company Name",
                description: "",
                type: "text",
                required: true
            },
            {
                name: "contact_information[company_number]",
                value: '',
                title: "Company Number",
                description: "",
                type: "text",
                required: true
            },
            {
                name: "contact_information[country_id]",
                value: '840',
                title: "Country",
                description: "",
                options: countries,
                type: "select",
                required: true
            },
            {
                name: "user_settings[timezone]",
                value: 'US/Central',
                title: "Time Zone",
                description: "",
                options: timezones,
                type: "select",
                required: true
            },
            {
                name: "customer_settings[locale]",
                value: '',
                title: "Language",
                description: "",
                options: locales,
                type: "select",
                required: true
            },
            {
                name: "customer_settings[currency]",
                value: '44',
                title: "Currency",
                description: "",
                options: currencies,
                type: "select",
                required: true
            },
            {
                name: "customer_settings[weight_unit]",
                value: 'kg',
                title: "Weight Unit",
                description: "",
                options: weight_units,
                type: "select",
                required: true
            },
            {
                name: "customer_settings[dimensions_unit]",
                value: 'cm',
                title: "Dimensions",
                description: "",
                options: dimensions,
                type: "select",
                required: true
            },
            {
                name: "allow_child_customers",
                value: '',
                title: "Allow Child Customers",
                description: "",
                type: "toggle",
                required: false
            },
        ]
    };
};

export default AccountCreateForm;
