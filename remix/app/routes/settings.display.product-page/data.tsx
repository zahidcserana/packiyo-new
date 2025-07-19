import {getContactInformation, getCustomerSettings,} from "../../.server/services/settings.js";
import {getIdByKey, getValueByKey} from "../../helpers/helpers";

const ProductForm = async ({ request }) => {
    const settings = await getCustomerSettings({ request });

    return {
        title: '',
        action: '',
        actionTitle: 'Save',
        encType: 'multipart/form-data',
        method: 'post',
        fields: [
            {
                name: `customer_settings[${getIdByKey(settings, 'disable_autoload_products_orders_shipped')}][disable_autoload_products_orders_shipped]`,
                value: getValueByKey(settings, "disable_autoload_products_orders_shipped"),
                title: "Show Orders Shipped",
                description: "",
                type: "toggle",
                inverted: 1,
                required: true
            },
            {
                name: `customer_settings[${getIdByKey(settings, 'disable_autoload_products_order_items')}][disable_autoload_products_order_items]`,
                value: getValueByKey(settings, "disable_autoload_products_order_items"),
                title: "Show Orders to Ship",
                description: "",
                type: "toggle",
                inverted: 1,
                required: true
            },
            {
                name: `customer_settings[${getIdByKey(settings, 'disable_autoload_products_tote_items')}][disable_autoload_products_tote_items]`,
                value: getValueByKey(settings, "disable_autoload_products_tote_items"),
                title: "Show Tote Items",
                description: "",
                type: "toggle",
                inverted: 1,
                required: true
            },
            {
                name: `customer_settings[${getIdByKey(settings, 'disable_autoload_inventory_change_log')}][disable_autoload_inventory_change_log]`,
                value: getValueByKey(settings, "disable_autoload_inventory_change_log"),
                title: "Show inventory change log",
                description: "",
                type: "toggle",
                inverted: 1,
                required: true
            },
            {
                name: `customer_settings[${getIdByKey(settings, 'lot_disable_picking_days')}][lot_disable_picking_days]`,
                value: getValueByKey(settings, "disable_autoload_products_orders_shipped"),
                title: "Days to disable picking before lot expiration",
                description: "",
                type: "text",
                required: false
            },
            {
                name: `customer_settings[${getIdByKey(settings, 'lot_notification_email')}][lot_notification_email]`,
                value: getValueByKey(settings, "lot_notification_email"),
                title: "Email address for reports for lots about to expire",
                description: "",
                type: "text",
                required: false
            },
        ]
    };
};

export default ProductForm;
