import {getOrderChannel} from "../../.server/services/connections.js";
import {dateMinusDays, formatDate} from "../../helpers/helpers";

export const data = async ({ request, params }) => {
    const orderChannel = await getOrderChannel({ request, params });

    const configurations = orderChannel?.orderChannelDetails?.data?.source_connections[0]?.configuration ?? [];

    const configurationsForm = {
        title: '',
        method: 'post',
        action: 'update-source-configuration',
        actionTitle: 'Save',
        fields: []
    };

    for (const [index, configuration] of configurations.entries()) {
        if (configuration.editable === true) {
            configurationsForm.fields.push({
                    name: `configurations[${index}][field]`,
                    value: configuration.field,
                    type: 'hidden',
                    required: true
                },
                {
                    name: `configurations[${index}][value]`,
                    value: configuration.value,
                    title: configuration.title,
                    description: '',
                    type: configuration.type.toLowerCase(),
                    required: true
                })
        }
    }

    const schedulers = orderChannel?.orderChannelDetails?.data?.scheduler_information.schedulers;

    const productScheduler = schedulers?.find(scheduler => scheduler.data_sync_type === "PRODUCT_SYNC");

    const productSchedulerForm = {
        title: 'Schedule Product Sync',
        method: 'post',
        action: 'schedule-sync',
        actionTitle: 'Save',
        fields: [
            {
                name: 'type',
                value: 'PRODUCT_SYNC',
                title: '',
                description: '',
                type: 'hidden',
                required: true
            },
            {
                name: 'cron_expression',
                value: productScheduler?.cron_expression,
                title: 'Product Sync Cron Expression',
                description: '',
                type: 'text',
                required: true
            },
            {
                name: 'operation',
                value: productScheduler?.active,
                title: 'Enable',
                description: '',
                type: 'toggle',
                required: false
            },
            productScheduler?.active ? {
                value: `Next execution time: ${formatDate(productScheduler.next_execution_time)}`,
                type: 'description'
            } : {}
        ]
    }

    const orderScheduler = schedulers?.find(scheduler => scheduler.data_sync_type === "ORDER_SYNC");

    const orderSchedulerForm = {
        title: 'Schedule Order Sync',
        method: 'post',
        action: 'schedule-sync',
        actionTitle: 'Save',
        fields: [
            {
                name: 'type',
                value: 'ORDER_SYNC',
                title: '',
                description: '',
                type: 'hidden',
                required: true
            },
            {
                name: 'cron_expression',
                value: orderScheduler?.cron_expression,
                title: 'Order Sync Cron Expression',
                description: '',
                type: 'text',
                required: true
            },
            {
                name: 'operation',
                value: orderScheduler?.active,
                title: 'Enable',
                description: '',
                type: 'toggle',
                required: false
            },
            orderScheduler?.active ? {
                value: `Next execution time: ${formatDate(orderScheduler.next_execution_time)}`,
                type: 'description'
            } : {}
        ]
    }

    const inventoryScheduler = schedulers?.find(scheduler => scheduler.data_sync_type === "INVENTORY_SYNC");

    const inventorySchedulerForm = {
        title: 'Schedule Inventory Sync',
        method: 'post',
        action: 'schedule-sync',
        actionTitle: 'Save',
        fields: [
            {
                name: 'type',
                value: 'INVENTORY_SYNC',
                title: '',
                description: '',
                type: 'hidden',
                required: true
            },
            {
                name: 'cron_expression',
                value: inventoryScheduler?.cron_expression,
                title: 'Inventory Sync Cron Expression',
                description: '',
                type: 'text',
                required: true
            },
            {
                name: 'operation',
                value: inventoryScheduler?.active,
                title: 'Enable',
                description: '',
                type: 'toggle',
                required: false
            },
            inventoryScheduler?.active ? {
                value: `Next execution time: ${formatDate(inventoryScheduler.next_execution_time)}`,
                type: 'description'
            } : {}
        ]
    }

    const shipmentScheduler = schedulers?.find(scheduler => scheduler.data_sync_type === "SHIPMENT_SYNC");

    const shipmentSchedulerForm = {
        title: 'Schedule Shipment Sync',
        method: 'post',
        action: 'schedule-sync',
        actionTitle: 'Save',
        fields: [
            {
                name: 'type',
                value: 'SHIPMENT_SYNC',
                title: '',
                description: '',
                type: 'hidden',
                required: true
            },
            {
                name: 'cron_expression',
                value: shipmentScheduler?.cron_expression,
                title: 'Inventory Sync Cron Expression',
                description: '',
                type: 'text',
                required: true
            },
            {
                name: 'operation',
                value: shipmentScheduler?.active,
                title: 'Enable',
                description: '',
                type: 'toggle',
                required: false
            },
            shipmentScheduler?.active ? {
                value: `Next execution time: ${formatDate(shipmentScheduler.next_execution_time)}`,
                type: 'description'
            } : {}
        ]
    }

    const syncProductsForm = {
        title: 'Sync Products',
        method: 'post',
        action: 'sync-products',
        actionTitle: 'Sync',
        fields: []
    }

    const syncProductByIdForm = {
        title: 'Sync Product By Id',
        method: 'post',
        action: 'sync-product-by-id',
        actionTitle: 'Sync',
        fields: [
            {
                name: 'product_id',
                value: '',
                title: 'Product ID',
                description: '',
                type: 'number',
                required: true
            }
        ]
    }

    const syncProductBySkuForm = {
        title: 'Sync Product By Sku',
        method: 'post',
        action: 'sync-product-by-sku',
        actionTitle: 'Sync',
        fields: [
            {
                name: 'product_sku',
                value: '',
                title: 'Product Sku',
                description: '',
                type: 'text',
                required: true
            }
        ]
    }

    const syncInventoriesForm = {
        title: 'Sync Inventories',
        method: 'post',
        action: 'sync-inventories',
        actionTitle: 'Sync',
        fields: []
    }

    const syncOrderByNumberForm = {
        title: 'Sync Order By Number',
        method: 'post',
        action: 'sync-order-by-number',
        actionTitle: 'Sync',
        fields: [
            {
                name: 'order_number',
                value: '',
                title: 'Order Number',
                description: '',
                type: 'number',
                required: true
            }
        ]
    }

    const syncOrdersByDateForm = {
        title: 'Sync Orders By Date',
        method: 'post',
        action: 'sync-orders-by-date',
        actionTitle: 'Sync',
        fields: [
            {
                name: 'date_from',
                value: dateMinusDays(1),
                title: 'Date From',
                description: '',
                type: 'date',
                required: true
            }
        ]
    }

    const syncShipmentsForm = {
        title: 'Sync Shipments',
        method: 'post',
        action: 'sync-shipments',
        actionTitle: 'Sync',
        fields: [
            {
                name: 'date_from',
                value: dateMinusDays(1),
                title: 'Date From',
                description: '',
                type: 'date',
                required: true
            }
        ]
    }

    const shipmentWebhook =  orderChannel?.webhooks?.find(webhook => webhook.object_type === 'App%5CModels%5CShipment' && webhook.operation === 'Store');
    const inventoryLogWebhook = orderChannel?.webhooks?.find(webhook => webhook.object_type === 'App%5CModels%5CShipment' && webhook.operation === 'Store');

    const packiyoWebhooksTable = {
        title: 'Packiyo Webhooks',
        headers: [
            {
                key: "name",
                title: "Name",
            }
        ],
        rows: [
            {
                fields: [
                    {
                        name: "name",
                        value: 'Shipment Store'
                    }
                ],
                objectType: 'App%5CModels%5CShipment',
                operation: shipmentWebhook ? 'Destroy' : 'Store'
            },
            {
                fields: [
                    {
                        name: "name",
                        value: 'Inventory Adjustment'
                    }
                ],
                objectType: 'App%5CModels%5CInventoryLog',
                operation: inventoryLogWebhook ? 'Destroy' : 'Store'
            }
        ]
    }

    const orderChannelWebhooks = orderChannel?.orderChannelInfo?.data?.webhook_info?.webhooks;

    const orderChannelWebhooksTable = {
        title: 'Order Channel Webhooks',
        headers: [
            {
                key: "name",
                title: "Name",
            }
        ],
        rows: []
    }

    for (const orderChannelWebhook of orderChannelWebhooks) {
        orderChannelWebhooksTable.rows.push({
            fields: [
                {
                    name: "name",
                    value: orderChannelWebhook.topic
                }
            ],
            id: orderChannelWebhook.id,
        })
    }

    return {
        name: orderChannel?.orderChannel.name,
        syncForms: [
            syncProductsForm,
            syncInventoriesForm,
            syncProductByIdForm,
            syncProductBySkuForm,
            syncOrderByNumberForm,
            syncOrdersByDateForm,
            syncShipmentsForm
        ],
        schedulerForms: [
            productSchedulerForm,
            orderSchedulerForm,
            inventorySchedulerForm,
            shipmentSchedulerForm
        ],
        configurationsForm: configurationsForm,
        packiyoWebhooksTable: packiyoWebhooksTable,
        orderChannelWebhooksTable: orderChannelWebhooksTable,
    };
};

export default data;
