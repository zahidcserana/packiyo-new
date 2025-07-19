import {getCustomerSettings} from "../../.server/services/settings.js";
import {getIdByKey, getValueByKey} from "../../helpers/helpers";

const ShippingLabelsForm = async ({ request }) => {
    const settings = await getCustomerSettings({ request });

    return {
        title: '',
        action: '',
        actionTitle: 'Save',
        encType: 'multipart/form-data',
        method: 'post',
        fields: [
            {
                name: `customer_settings[${getIdByKey(settings, 'label_size_width')}][label_size_width]`,
                value: getValueByKey(settings, "label_size_width"),
                title: "Width",
                description: "",
                type: "text",
                required: false
            },
            {
                name: `customer_settings[${getIdByKey(settings, 'label_size_height')}][label_size_height]`,
                value: getValueByKey(settings, "label_size_height"),
                title: "Height",
                description: "",
                type: "text",
                required: false
            },
            {
                name: `customer_settings[${getIdByKey(settings, 'use_zpl_labels')}][use_zpl_labels]`,
                value: getValueByKey(settings, "use_zpl_labels"),
                title: "Print as PDF",
                description: "",
                type: "toggle",
                inverted: 1,
                required: true
            },
        ]
    };
};

export default ShippingLabelsForm;
