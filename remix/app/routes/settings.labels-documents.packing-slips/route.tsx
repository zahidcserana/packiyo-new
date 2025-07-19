import {useLoaderData} from "@remix-run/react";
import {ActionFunction} from "@remix-run/node";
import data from "./data";
import {
    saveCustomerSettings,
    uploadImage
} from "../../.server/services/settings.js";
import DefaultForm from "../../components/Form/Form";
import React from "react";
import Page from "../../components/Layout/Page/Page";
import {requireAuth} from '../../.server/services/auth.server.js';

export function meta() {
    return [
        {
            title: 'Barcodes',
            description: '',
        },
    ];
}

export const loader = async ({request}) => {
    await requireAuth({request});

    return {
        breadcrumbs: [
            { label: 'Settings', to: '/settings' },
            { label: 'Packing Slips', to: '' }
        ],
        PackingSlipsForm: await data({request})
    }
};

export const action: ActionFunction = async ({request}) => {
    const formData = await request.formData();

    let customerSettingsSuccess;

    try {
        customerSettingsSuccess = await saveCustomerSettings({ request, formData });
    } catch (error) {
        customerSettingsSuccess = error;
    }

    const orderSlipLogo = formData.get('order_slip_logo');

    if (orderSlipLogo && "size" in orderSlipLogo && orderSlipLogo.size > 0) {
        formData.delete('order_slip_logo');
        formData.append('file', orderSlipLogo);
        formData.append('image_type', 'order_slip_logo');
        await uploadImage({ request, formData });
    }

    return customerSettingsSuccess;
};

const PackingSlips = () => {
    const {breadcrumbs, PackingSlipsForm} = useLoaderData<typeof loader>();

    return (
        <Page breadcrumbs={breadcrumbs}>
            <DefaultForm {...PackingSlipsForm} />
        </Page>
    )
}

export default PackingSlips;
