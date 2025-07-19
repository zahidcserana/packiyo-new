import {useLoaderData, useSubmit} from "@remix-run/react";
import {
    Button,
    Card, Col, Container, Row
} from "react-bootstrap";
import data from './data.tsx'
import {ActionFunction} from "@remix-run/node";
import DefaultForm from "../../components/Form/Form";
import React from "react";
import SimpleTable from "../../components/Table/SimpleTable/SimpleTable";
import {
    createPackiyoWebhook,
    recreateOrderChannelWebhooks, removeOrderChannelWebhook, scheduleSync,
    syncInventories, syncOrderByNumber,
    syncOrdersByDate,
    syncProductById,
    syncProductBySku,
    syncProducts,
    syncShipments,
    updateSourceConfiguration
} from "../../.server/services/connections.js";
import Page from "../../components/Layout/Page/Page";
import Tabs from "../../components/Layout/Tabs/Tabs";
import {requireAuth} from '../../.server/services/auth.server.js';

export function meta() {
    return [
        {
            title: 'Edit Connection',
            description: '',
        },
    ];
}

export const loader = async ({request, params}) => {
    await requireAuth({request});

    const response = await data({request, params});

    return {
        breadcrumbs: [
            { label: 'Settings', to: '/settings' },
            { label: 'Connections', to: '/settings/connections' },
            { label: response.name, to: '' }
        ],
        syncForms: response.syncForms,
        schedulerForms: response.schedulerForms,
        configurationsForm: response.configurationsForm,
        packiyoWebhooksTable: response.packiyoWebhooksTable,
        orderChannelWebhooksTable: response.orderChannelWebhooksTable,
    }
};

export const action: ActionFunction = async ({request, params}) => {
    const formData = await request.formData();

    switch (formData.get("action")) {
        case "update-source-configuration": {
            return updateSourceConfiguration({request, params, formData});
        }
        case "recreate-order-channel-webhooks": {
            return recreateOrderChannelWebhooks({request, params, formData});
        }
        case "create-packiyo-webhook": {
            return createPackiyoWebhook({request, params, formData});
        }
        case "sync-products": {
            return syncProducts({request, params, formData});
        }
        case "sync-product-by-id": {
            return syncProductById({request, params, formData});
        }
        case "sync-product-by-sku": {
            return syncProductBySku({request, params, formData});
        }
        case "sync-inventories": {
            return syncInventories({request, params, formData});
        }
        case "sync-order-by-number": {
            return syncOrderByNumber({request, params, formData});
        }
        case "sync-orders-by-date": {
            return syncOrdersByDate({request, params, formData});
        }
        case "sync-shipments": {
            return syncShipments({request, params, formData});
        }
        case "schedule-sync": {
            return scheduleSync({request, params, formData});
        }
        case "remove-webhook": {
            return removeOrderChannelWebhook({request, params, formData});
        }
        default: {
            throw new Error("Unknown action");
        }
    }
};

const EditConnection = () => {
    const {
        breadcrumbs,
        syncForms,
        schedulerForms,
        configurationsForm,
        packiyoWebhooksTable,
        orderChannelWebhooksTable
    } = useLoaderData<typeof loader>();

    const RenderPackiyoWebhooks = () => {
        return (
            <Card className="mb-3">
                <Card.Header as="h6" className="py-3 bg-white">
                    Packiyo Webhooks
                </Card.Header>
                <Card.Body className="p-0">
                    <SimpleTable {...packiyoWebhooksTable} handleCreateClick={createPackiyoWebhook} />
                </Card.Body>
            </Card>
        )
    }

    const RenderWebhooksTable = () => {
        return (
            <Card className="mb-3">
                <Card.Header as="h6" className="py-3 bg-white">
                    Order Channel Webhooks
                </Card.Header>
                <Card.Body className="p-0">
                    <SimpleTable {...orderChannelWebhooksTable} handleDeleteClick={removeWebhook} />
                </Card.Body>
            </Card>
        )
    }

    const submit = useSubmit();

    const createPackiyoWebhook = (item) => {
        const formData = new FormData();

        formData.append("action", 'create-packiyo-webhook');
        formData.append("object_type", item.objectType);
        formData.append("operation", item.operation);

        submit(formData, { method: "post" });
    };

    const removeWebhook = (item) => {
        const formData = new FormData();

        formData.append("action", 'remove-webhook');
        formData.append("id", item.id);

        submit(formData, { method: "post" });
    };

    const tabs = [
        {
            title: 'Manage Syncs',
            content: (
                <Row>
                    {syncForms.map((form, index) => (
                        <Col key={index} lg={3} md={6}>
                            <Card style={{minHeight: '210px'}} className="mb-3">
                                <Card.Header as="h6" className="py-3 bg-white">
                                    {form.title}
                                </Card.Header>
                                <Card.Body>
                                    <DefaultForm {...{...form, ...{title: ''}}}/>
                                </Card.Body>
                            </Card>
                        </Col>
                    ))}
                </Row>
            )
        },
        {
            title: 'Manage Schedulers',
            content: (
                <>
                    <Card className="mb-3">
                        <Card.Header as="h6" className="py-3 bg-white">
                            Manage Schedulers
                        </Card.Header>
                        <Card.Body className="pb-0">
                            <p>
                                Examples how to set up CRON: <a
                                href="https://crontab.guru/examples.html"
                                target="_blaml">https://crontab.guru/examples.html</a><br/>
                                ATTN: Our cron is more flexible than the examples in the link so make
                                sure to add "0 " before the expression copied from examples - so that
                                there are 6 segments in the expression instead of 5.<br/>
                                For instance: you want to set up cron expression for every night at 2
                                AM. Crontab.guru example will say use <b>"0 2 * * *"</b> but you should
                                write <b>"0 0 2 * * *"</b><br/>
                                Expression dates are in GMT+0 time zone
                            </p>
                        </Card.Body>
                    </Card>
                    <Row>
                        {schedulerForms.map((form, index) => (
                            <Col key={index} md={12} lg={6}>
                                <Card className="mb-3">
                                    <Card.Header as="h6" className="py-3 bg-white">
                                        {form.title}
                                    </Card.Header>
                                    <Card.Body>
                                        <DefaultForm {...{...form, ...{title: ''}}}/>
                                    </Card.Body>
                                </Card>
                            </Col>
                        ))}
                    </Row>
                </>
            )
        },
        {
            title: 'Manage Webhooks',
            content: (
                <>
                    <Card className="mb-3">
                        <Card.Header as="h6" className="py-3 bg-white">
                            Order channel webhooks
                        </Card.Header>
                        <Card.Body>
                            <DefaultForm method="POST" action="recreate-order-channel-webhooks" actionTitle="Recreate All" />
                        </Card.Body>
                    </Card>
                    {orderChannelWebhooksTable.rows.length > 0 && <RenderWebhooksTable/>}
                    <RenderPackiyoWebhooks/>
                </>

            )
        }
    ];

    if (configurationsForm.fields.length) {
        tabs.push({
            title: 'Manage Configurations',
            content: (
                <Card className="mb-3">
                    <Card.Header as="h6" className="py-3 bg-white">
                        Configurations
                    </Card.Header>
                    <Card.Body>
                        <DefaultForm {...configurationsForm} />
                    </Card.Body>
                </Card>
            )
        })
    }

    return (
        <Page breadcrumbs={breadcrumbs} bodyClasses="pb-0">
            <Tabs tabs={tabs} />
        </Page>
    )
}

export default EditConnection;
