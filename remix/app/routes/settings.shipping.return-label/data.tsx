import {getCustomerSettings, getCustomerShippingMethods} from "../../.server/services/settings.js";
import {getIdByKey, getValueByKey} from "../../helpers/helpers";

const ReturnLabelForm = async ({ request }) => {
    const settings = await getCustomerSettings({ request });
    const shippingMethods = await getCustomerShippingMethods({ request });

    return {
        title: '',
        action: '',
        actionTitle: 'Save',
        encType: 'multipart/form-data',
        method: 'post',
        fields: [
            {
                name: `customer_settings[${getIdByKey(settings, 'default_return_shipping_method')}][default_return_shipping_method]`,
                value: getValueByKey(settings, "default_return_shipping_method"),
                title: "Default Return Shipping Method",
                description: "",
                options: shippingMethods,
                type: "select",
                required: true
            },
            {
                name: `customer_settings[${getIdByKey(settings, 'auto_return_label')}][auto_return_label]`,
                value: getValueByKey(settings, "auto_return_label"),
                title: "Create Return Labels When Shipping",
                description: "",
                type: "toggle",
                required: true
            },
        ]
    };
};

export default ReturnLabelForm;
