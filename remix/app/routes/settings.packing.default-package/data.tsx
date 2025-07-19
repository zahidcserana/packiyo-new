import {getIdByKey, getValueByKey} from "../../helpers/helpers";
import {getCustomerSettings, getCustomerShippingBoxes} from "../../.server/services/settings.js";

const DefaultPackageForm = async ({ request }) => {
    const settings = await getCustomerSettings({ request });
    const shippingBoxes = await getCustomerShippingBoxes({ request });

    return {
        title: '',
        action: '',
        actionTitle: 'Save',
        encType: 'multipart/form-data',
        method: 'post',
        fields: [
            {
                name: `customer_settings[${getIdByKey(settings, 'shipping_box_id')}][shipping_box_id]`,
                value: getValueByKey(settings, "shipping_box_id"),
                title: "Default Package",
                description: "",
                options: shippingBoxes,
                type: "select",
                required: true
            }
        ]
    };
};

export default DefaultPackageForm;
