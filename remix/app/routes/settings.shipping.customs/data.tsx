import {getCustomerSettings} from "../../.server/services/settings.js";
import {getIdByKey, getValueByKey} from "../../helpers/helpers";

const CustomsForm = async ({ request }) => {
    const settings = await getCustomerSettings({ request });

    return {
        title: '',
        action: '',
        actionTitle: 'Save',
        encType: 'multipart/form-data',
        method: 'post',
        fields: [
            {
                name: `customer_settings[${getIdByKey(settings, 'customs_description')}][customs_description]`,
                value: getValueByKey(settings, "customs_description"),
                title: "Customs Description",
                description: "",
                type: "text"
            },
            {
                name: `customer_settings[${getIdByKey(settings, 'customs_signer')}][customs_signer]`,
                value: getValueByKey(settings, "customs_signer"),
                title: "Customs Signer",
                description: "",
                type: "text"
            },
            {
                name: `customer_settings[${getIdByKey(settings, 'eel_pfc')}][eel_pfc]`,
                value: getValueByKey(settings, "eel_pfc"),
                title: "EEL/PFC",
                description: "",
                type: "text"
            },
            {
                name: `customer_settings[${getIdByKey(settings, 'contents_type')}][contents_type]`,
                value: getValueByKey(settings, "contents_type"),
                title: "Contents Type",
                description: "",
                type: "text"
            },
        ]
    };
};

export default CustomsForm;
