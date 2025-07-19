import {getAvailableConnections, getConnections} from "../../.server/services/settings.ts";
import {currentAccountId} from "../../.server/services/auth.server.ts";

const dataServer = async ({ request }) => {
    const response = await getAvailableConnections({ request });
    const availableConnections = response?.orderChannels;
    const credential = response?.credential;

    const accountId = await currentAccountId({ request });
    const connections = await getConnections({ request });

    const connectionsTable = {
        title: 'Connections',
        headers: [
            {
                key: "image_url",
                title: "Image",
                type: 'image'
            },
            {
                key: "name",
                title: "Name",
            },
        ],
        rows: []
    }

    for (const connection of connections) {
        connectionsTable.rows.push({
            id: connection.id,
            fields: [
                {
                    name: "customer_id",
                    value: connection.attributes.customer_id,
                    title: "Customer ID",
                    description: "",
                    type: "text",
                    required: true
                },
                {
                    name: "name",
                    value: connection.attributes.name,
                    title: "Name",
                    description: "",
                    type: "text",
                    required: true
                },
                {
                    name: "settings",
                    value: connection.attributes.settings,
                    title: "Settings",
                    description: "",
                    type: "text",
                    required: true
                },
                {
                    name: "image_url",
                    value: connection.attributes.image_url,
                    title: "Image",
                    description: "",
                    type: "text",
                    required: true
                }
            ]
        })
    }

    return {
        availableConnections: availableConnections.filter(connection => connection.active),
        connectionsTable: connectionsTable,
        accountId: accountId,
        credential: credential
    };
};

export default dataServer;

