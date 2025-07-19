import {getWebshipperCredentials} from "../../.server/services/settings.js";

const data = async ({ request }) => {
    const response = await getWebshipperCredentials({ request });

    const credentialsTable = {
        title: 'Credentials',
        headers: [
            {
                key: "api_base_url",
                title: "Api Base Url",
            },
            {
                key: "api_key",
                title: "Api Key",
            },
            {
                key: "order_channel_id",
                title: "Order Channel Id",
            },
        ],
        rows: []
    }

    for (const credential of response.webshipper_credentials) {
        credentialsTable.rows.push({
            id: credential.id,
            fields: [
                {
                    name: "api_key",
                    value: credential.attributes.api_key,
                    title: "API Key",
                    description: "",
                    type: "text",
                    visible: true,
                    required: true
                },
                {
                    name: "api_base_url",
                    value: credential.attributes.api_base_url,
                    title: "Api Base Url",
                    description: "",
                    type: "text",
                    visible: true,
                    required: true
                },
                {
                    name: "order_channel_id",
                    value: credential.attributes.order_channel_id,
                    title: "Order Channel Id",
                    description: "",
                    type: "number",
                    visible: true,
                    required: true
                },
            ]
        })
    }

    return {
        credentialsTable: credentialsTable
    };
};

export default data;
