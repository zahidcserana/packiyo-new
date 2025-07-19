import {getCustomerPrinters, getCustomerSettings} from "../../.server/services/settings.js";
import {getIdByKey, getValueByKey} from "../../helpers/helpers";

const ShippingLabelPrinterForm = async ({ request }) => {
    const settings = await getCustomerSettings({ request });
    const printers = await getCustomerPrinters({ request });

    return {
        title: '',
        action: '',
        actionTitle: 'Save',
        encType: 'multipart/form-data',
        method: 'post',
        fields: [
            {
                name: `customer_settings[${getIdByKey(settings, 'label_printer_id')}][label_printer_id]`,
                value: getValueByKey(settings, "label_printer_id"),
                title: "Shipping Label Printer",
                description: "",
                options: printers,
                type: "select",
                required: true
            }
        ]
    };
};

export default ShippingLabelPrinterForm;
