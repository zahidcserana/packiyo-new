import {useLoaderData} from "@remix-run/react";
import {ActionFunction} from "@remix-run/node";
import data from "./data";
import {saveCustomerSettings} from "../../.server/services/settings.js";
import DefaultForm from "../../components/Form/Form";
import React from "react";
import Page from "../../components/Layout/Page/Page";
import {requireAuth} from '../../.server/services/auth.server.js';

export function meta() {
    return [
        {
            title: 'Customs',
            description: '',
        },
    ];
}

export const loader = async ({request}) => {
    await requireAuth({request});

    return {
        breadcrumbs: [
            { label: 'Settings', to: '/settings' },
            { label: 'Customs', to: '' }
        ],
        CustomsForm: await data({request})
    }
};

export const action: ActionFunction = async ({request}) => {
    const formData = await request.formData();

    try {
        return await saveCustomerSettings({ request, formData });
    } catch (error) {
        return error;
    }
};

const Customs = () => {
    const {breadcrumbs, CustomsForm} = useLoaderData<typeof loader>();

    return (
        <Page breadcrumbs={breadcrumbs}>
            <DefaultForm {...CustomsForm} />
        </Page>
    )
}

export default Customs;
