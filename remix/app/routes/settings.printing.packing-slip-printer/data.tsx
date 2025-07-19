import {getCustomerPrinters, getCustomerSettings} from "../../.server/services/settings.js";
import {getIdByKey, getValueByKey} from "../../helpers/helpers";

const PackingSlipPrinterForm = async ({ request }) => {
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
                name: `customer_settings[${getIdByKey(settings, 'slip_printer_id')}][slip_printer_id]`,
                value: getValueByKey(settings, "slip_printer_id"),
                title: "Packing Slip Printer",
                description: "",
                options: printers,
                type: "select",
                required: true
            }
        ]
    };
};

export default PackingSlipPrinterForm;
