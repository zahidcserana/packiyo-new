import {getEasypostCredentials} from "../../.server/services/settings.js";

const data = async ({ request }) => {
    const response = await getEasypostCredentials({ request });

    const credentialsTable = {
        title: 'Credentials',
        headers: [
            {
                key: "api_key",
                title: "Api Key",
            },
            {
                key: "test_api_key",
                title: "Test API Key",
            },
        ],
        rows: []
    }

    for (const credential of response.easypost_credentials) {
        credentialsTable.rows.push({
            id: credential.id,
            fields: [
                {
                    name: "api_key",
                    value: credential.attributes.api_key,
                    title: "API Key",
                    description: "",
                    type: "text",
                    required: true
                },
                {
                    name: "test_api_key",
                    value: credential.attributes.test_api_key,
                    title: "Test API Key",
                    description: "",
                    type: "text",
                    required: true
                },
                {
                    name: "commercial_invoice_signature",
                    value: credential.attributes.commercial_invoice_signature,
                    title: "Commercial invoice signature",
                    description: "",
                    type: "text",
                    required: false
                },
                {
                    name: "commercial_invoice_letterhead",
                    value: credential.attributes.commercial_invoice_letterhead,
                    title: "Commercial invoice letterhead",
                    description: "",
                    type: "text",
                    required: false
                },
                {
                    name: "endorsement",
                    value: credential.attributes.endorsement,
                    title: "Endorsement",
                    description: "",
                    options: response.endorsements,
                    type: "select",
                    required: true
                },
                {
                    name: "use_native_tracking_urls",
                    value: credential.attributes.use_native_tracking_urls,
                    title: "Use native tracking URLs",
                    description: "",
                    type: "toggle",
                    required: true
                }
            ]
        })
    }

    return {
        credentialsTable: credentialsTable,
        endorsements: response.endorsements,
    };
};

export default data;
