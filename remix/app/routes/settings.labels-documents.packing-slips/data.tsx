import {getAccountImages, getCustomerSettings} from "../../.server/services/settings.js";
import {getIdByKey, getValueByKey} from "../../helpers/helpers";

const PackingSlipsForm = async ({ request }) => {
    const settings = await getCustomerSettings({ request });
    const {order_slip_logo} = await getAccountImages({ request });

    return {
        title: '',
        action: '',
        actionTitle: 'Save',
        encType: 'multipart/form-data',
        method: 'post',
        fields: [
            {
                name: `customer_settings[${getIdByKey(settings, 'show_prices_on_slips')}][show_prices_on_slips]`,
                value: getValueByKey(settings, "show_prices_on_slips"),
                title: "Show Prices",
                description: "",
                type: "toggle",
                inverted: 1,
                required: true
            },
            {
                name: `customer_settings[${getIdByKey(settings, 'order_slip_auto_print')}][order_slip_auto_print]`,
                value: getValueByKey(settings, "order_slip_auto_print"),
                title: "Auto Print",
                description: "",
                type: "toggle",
                inverted: 1,
                required: true
            },
            {
                name: `customer_settings[${getIdByKey(settings, 'document_size_width')}][document_size_width]`,
                value: getValueByKey(settings, "document_size_width"),
                title: "Document Width",
                description: "",
                type: "text",
            },
            {
                name: `customer_settings[${getIdByKey(settings, 'document_size_height')}][document_size_height]`,
                value: getValueByKey(settings, "document_size_height"),
                title: "Document Height",
                description: "",
                type: "text",
            },
            {
                name: `customer_settings[${getIdByKey(settings, 'order_slip_heading')}][order_slip_heading]`,
                value: getValueByKey(settings, "order_slip_heading"),
                title: "Header",
                description: "",
                type: "text",
            },
            {
                name: `customer_settings[${getIdByKey(settings, 'order_slip_text')}][order_slip_text]`,
                value: getValueByKey(settings, "order_slip_text"),
                title: "Note",
                description: "",
                type: "textarea",
            },
            {
                name: `customer_settings[${getIdByKey(settings, 'order_slip_footer')}][order_slip_footer]`,
                value: getValueByKey(settings, "order_slip_footer"),
                title: "Footer",
                description: "",
                type: "textarea",
            },
            {
                name: "order_slip_logo",
                value: order_slip_logo?.source,
                id: order_slip_logo?.id,
                title: "Order Slip Logo",
                description: "",
                type: "file"
            },
        ]
    };
};

export default PackingSlipsForm;
