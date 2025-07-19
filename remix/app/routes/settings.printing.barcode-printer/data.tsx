import {getCustomerPrinters, getCustomerSettings} from "../../.server/services/settings.js";
import {getIdByKey, getValueByKey} from "../../helpers/helpers";

const BarcodePrinterForm = async ({ request }) => {
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
                name: `customer_settings[${getIdByKey(settings, 'barcode_printer_id')}][barcode_printer_id]`,
                value: getValueByKey(settings, "barcode_printer_id"),
                title: "Barcode Printer",
                description: "",
                options: printers,
                type: "select",
                required: true
            }
        ]
    };
};

export default BarcodePrinterForm;
