import {getCustomerSettings} from "../../.server/services/settings.js";
import {getIdByKey, getValueByKey} from "../../helpers/helpers";

const BulkShippingForm = async ({ request }) => {
    const settings = await getCustomerSettings({ request });

    return {
        title: '',
        action: '',
        actionTitle: 'Save',
        encType: 'multipart/form-data',
        method: 'post',
        fields: [
            {
                name: `customer_settings[${getIdByKey(settings, 'packing_slip_in_bulkshipping')}][packing_slip_in_bulkshipping]`,
                value: getValueByKey(settings, "packing_slip_in_bulkshipping"),
                title: "Include Packing Slips in Shipping Batches",
                description: "",
                type: "toggle",
                required: true
            }
        ]
    };
};

export default BulkShippingForm;
