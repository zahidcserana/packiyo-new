import {getCustomerSettings} from "../../.server/services/settings.js";
import {getIdByKey, getValueByKey} from "../../helpers/helpers";

const BarcodesForm = async ({ request }) => {
    const settings = await getCustomerSettings({ request });

    return {
        title: '',
        action: '',
        actionTitle: 'Save',
        encType: 'multipart/form-data',
        method: 'post',
        fields: [
            {
                name: `customer_settings[${getIdByKey(settings, 'barcode_size_height')}][barcode_size_height]`,
                value: getValueByKey(settings, "barcode_size_height"),
                title: "Height",
                description: "",
                type: "text",
                required: false
            },
            {
                name: `customer_settings[${getIdByKey(settings, 'barcode_size_width')}][barcode_size_width]`,
                value: getValueByKey(settings, "barcode_size_width"),
                title: "Width",
                description: "",
                type: "text",
                required: false
            }
        ]
    };
};

export default BarcodesForm;
