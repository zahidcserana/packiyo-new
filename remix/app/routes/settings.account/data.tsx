import {getValueByKey, getIdByKey} from "../../helpers/helpers";
import {
    getAccountImages,
    getCustomerContactInformation,
    getCustomerSettings, getStaticCustomerData,
    getStaticUserData,
    getUserSettings
} from "../../.server/services/settings.js";

const AccountForm = async ({ request }) => {
    const customerSettings = await getCustomerSettings({ request });
    const userSettings = await getUserSettings({ request });
    const contactInformation = await getCustomerContactInformation({ request });
    const {countries, timezones, locales, currencies} = await getStaticUserData({ request });
    const {weight_units, dimensions} = await getStaticCustomerData({ request });
    const {account_favicon, account_logo} = await getAccountImages({ request });

    return {
        title: '',
        action: '',
        actionTitle: 'Save',
        encType: 'multipart/form-data',
        method: 'post',
        fields: [
            {
                name: "contact_information[id]",
                value: contactInformation?.id,
                type: "hidden",
                required: true
            },
            {
                name: "contact_information[name]",
                value: contactInformation?.name,
                title: "Account Name",
                description: "",
                type: "text",
                required: true
            },
            {
                name: "contact_information[company_name]",
                value: contactInformation?.company_name,
                title: "Company Name",
                description: "",
                type: "text",
                required: false
            },
            {
                name: "contact_information[company_number]",
                value: contactInformation?.company_number,
                title: "Company Number",
                description: "",
                type: "text",
                required: false
            },
            {
                name: "contact_information[country_id]",
                value: contactInformation?.country_id,
                title: "Country",
                description: "",
                options: countries,
                type: "select",
                required: true
            },
            {
                name: `user_settings[${getIdByKey(userSettings, 'timezone')}][timezone]`,
                value: getValueByKey(userSettings, 'timezone'),
                title: "Timezone",
                description: "",
                options: timezones,
                type: "select",
                required: false
            },
            {
                name: `customer_settings[${getIdByKey(customerSettings, 'locale')}][locale]`,
                value: getValueByKey(customerSettings, 'locale'),
                title: "Language",
                description: "",
                options: locales,
                type: "select",
                required: true
            },
            {
                name: `customer_settings[${getIdByKey(customerSettings, 'currency')}][currency]`,
                value: getValueByKey(customerSettings, 'currency'),
                title: "Currency",
                description: "",
                options: currencies,
                type: "select",
                required: true
            },
            {
                name: `customer_settings[${getIdByKey(customerSettings, 'weight_unit')}][weight_unit]`,
                value: getValueByKey(customerSettings, 'weight_unit'),
                title: "Weight Unit",
                description: "",
                options: weight_units,
                type: "select",
                required: true
            },
            {
                name: `customer_settings[${getIdByKey(customerSettings, 'dimensions_unit')}][dimensions_unit]`,
                value: getValueByKey(customerSettings, 'dimensions_unit'),
                title: "Dimensions",
                description: "",
                options: dimensions,
                type: "select",
                required: true
            },
            {
                name: "account_logo",
                value: account_logo?.source,
                id: account_logo?.id,
                title: "Account Logo",
                description: "",
                type: "file"
            },
            {
                name: "account_favicon",
                value: account_favicon?.source,
                id: account_favicon?.id,
                title: "Account Favicon",
                description: "",
                type: "file"
            },
        ]
    };
};

export default AccountForm;
