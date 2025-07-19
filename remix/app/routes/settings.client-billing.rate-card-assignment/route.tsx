import {useLoaderData,} from "@remix-run/react";
import {ActionFunction} from "@remix-run/node";
import data from "./data";
import DefaultForm from "../../components/Form/Form";
import React from "react";
import Page from "../../components/Layout/Page/Page";
import {saveCustomerRateCards} from "./../../.server/services/settings";
import {requireAuth} from '../../.server/services/auth.server.js';

export function meta() {
    return [
        {
            title: 'Rate Card Assignment',
            description: '',
        },
    ];
}

export const loader = async ({request}) => {
    await requireAuth({request});

    return {
        breadcrumbs: [
            { label: 'Settings', to: '/settings' },
            { label: 'Rate Card Assignment', to: '' }
        ],
        rateCardForm: await data({request})
    }
};

export const action: ActionFunction = async ({request}) => {
    const formData = await request.formData();

    return await saveCustomerRateCards({request, formData});
};

const RateCardAssignment = () => {
    const {breadcrumbs, rateCardForm} = useLoaderData<typeof loader>();

    return (
        <Page breadcrumbs={breadcrumbs}>
            <DefaultForm {...rateCardForm} />
        </Page>
    )
}

export default RateCardAssignment;
