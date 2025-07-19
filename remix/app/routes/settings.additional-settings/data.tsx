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
    const {lot_priorities} = await getStaticCustomerData({ request });
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
                name: "contact_information[address]",
                value: contactInformation?.address,
                title: "Address",
                description: "",
                type: "text",
                required: false
            },
            {
                name: "contact_information[address2]",
                value: contactInformation?.address2,
                title: "Address 2",
                description: "",
                type: "text",
                required: false
            },
            {
                name: "contact_information[zip]",
                value: contactInformation?.zip,
                title: "Zip",
                description: "",
                type: "text",
                required: false
            },
            {
                name: "contact_information[city]",
                value: contactInformation?.city,
                title: "City",
                description: "",
                type: "text",
                required: false
            },
            {
                name: "contact_information[state]",
                value: contactInformation?.state,
                title: "State",
                description: "",
                type: "text",
                required: false
            },
            {
                name: "contact_information[email]",
                value: contactInformation?.email,
                title: "Email",
                description: "",
                type: "text",
                required: false
            },
            {
                name: "contact_information[phone]",
                value: contactInformation?.phone,
                title: "Phone",
                description: "",
                type: "text",
                required: false
            },
            {
                name: `customer_settings[${getIdByKey(customerSettings, 'show_skus_on_slips')}][show_skus_on_slips]`,
                value: getValueByKey(customerSettings, "show_skus_on_slips"),
                title: "Show Prices on Slips",
                description: "",
                type: "toggle",
                inverted: 1,
                required: false
            },
            {
                name: `customer_settings[${getIdByKey(customerSettings, 'disable_autoload_single_order_packing')}][disable_autoload_single_order_packing]`,
                value: getValueByKey(customerSettings, "disable_autoload_single_order_packing"),
                title: "Show Single Order Packing",
                description: "",
                type: "toggle",
                inverted: 1,
                required: false
            },
            {
                name: `customer_settings[${getIdByKey(customerSettings, 'only_use_bulk_ship_pickable_locations')}][only_use_bulk_ship_pickable_locations]`,
                value: getValueByKey(customerSettings, "only_use_bulk_ship_pickable_locations"),
                title: "Only use bulk ship pickable locations",
                description: "",
                type: "toggle",
                inverted: 1,
                required: false
            },
            {
                name: `customer_settings[${getIdByKey(customerSettings, 'shipping_notifications_for_manual_orders')}][shipping_notifications_for_manual_orders]`,
                value: getValueByKey(customerSettings, "shipping_notifications_for_manual_orders"),
                title: "Send shipping notifications for manually created orders",
                description: "",
                type: "toggle",
                inverted: 1,
                required: false
            },
            {
                name: `customer_settings[${getIdByKey(customerSettings, 'lot_priority')}][lot_priority]`,
                value: getValueByKey(customerSettings, "lot_priority"),
                title: "Lot Priority",
                description: "",
                type: "select",
                options: lot_priorities,
                required: false
            },
        ]
    };
};

export default AccountForm;
