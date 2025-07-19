import {getCustomerSettings} from "../../.server/services/settings.js";
import {getIdByKey, getValueByKey} from "../../helpers/helpers";

const SearchResultsForm = async ({ request }) => {
    const settings = await getCustomerSettings({ request });

    return {
        title: '',
        action: '',
        actionTitle: 'Save',
        encType: 'multipart/form-data',
        method: 'post',
        fields: [
            {
                name: `customer_settings[${getIdByKey(settings, 'disable_autoload_products')}][disable_autoload_products]`,
                value: getValueByKey(settings, "disable_autoload_products"),
                title: "Products Page",
                description: "",
                type: "toggle",
                inverted: 1,
                required: true
            },
            {
                name: `customer_settings[${getIdByKey(settings, 'disable_autoload_orders')}][disable_autoload_orders]`,
                value: getValueByKey(settings, "disable_autoload_orders"),
                title: "Orders Page",
                description: "",
                type: "toggle",
                inverted: 1,
                required: true
            },
            {
                name: `customer_settings[${getIdByKey(settings, 'disable_autoload_shipment_report')}][disable_autoload_shipment_report]`,
                value: getValueByKey(settings, "disable_autoload_shipment_report"),
                title: "Shipments Report Page",
                description: "",
                type: "toggle",
                inverted: 1,
                required: true
            },
            {
                name: `customer_settings[${getIdByKey(settings, 'disable_autoload_shipped_item_report')}][disable_autoload_shipped_item_report]`,
                value: getValueByKey(settings, "disable_autoload_shipped_item_report"),
                title: "Shipped Items Report Page",
                description: "",
                type: "toggle",
                inverted: 1,
                required: true
            },
            {
                name: `customer_settings[${getIdByKey(settings, 'disable_autoload_picker_report')}][disable_autoload_picker_report]`,
                value: getValueByKey(settings, "disable_autoload_picker_report"),
                title: "Picker Report Page",
                description: "",
                type: "toggle",
                inverted: 1,
                required: true
            },
            {
                name: `customer_settings[${getIdByKey(settings, 'disable_autoload_packer_report')}][disable_autoload_packer_report]`,
                value: getValueByKey(settings, "disable_autoload_packer_report"),
                title: "Pack Station Orders Page",
                description: "",
                type: "toggle",
                inverted: 1,
                required: true
            },
            {
                name: `customer_settings[${getIdByKey(settings, 'disable_autoload_allow_load_button')}][disable_autoload_allow_load_button]`,
                value: getValueByKey(settings, "disable_autoload_allow_load_button"),
                title: "Display Search Results without Search or Filter",
                description: "",
                type: "toggle",
                inverted: 1,
                required: true
            }
        ]
    };
};

export default SearchResultsForm;
