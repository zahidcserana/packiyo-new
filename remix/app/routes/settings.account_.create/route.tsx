import { useLoaderData} from "@remix-run/react";
import data from './data.tsx'
import {ActionFunction} from "@remix-run/node";
import DefaultForm from "../../components/Form/Form";
import React from "react";
import {createCustomer} from "../../.server/services/settings.js";
import Page from "../../components/Layout/Page/Page";
import {requireAuth} from '../../.server/services/auth.server.js';

export function meta() {
    return [
        {
            title: 'Create new Account',
            description: '',
        },
    ];
}

export const loader = async ({request}) => {
    await requireAuth({request});

    return {
        breadcrumbs: [
            { label: 'Settings', to: '/settings' },
            { label: 'Create Account', to: '' }
        ],
        accountCreateForm: await data({request})
    }
};

export const action: ActionFunction = async ({request}) => {
    const formData = await request.formData();

    return await createCustomer({request, formData});
};

const AccountCreate = () => {
    const {breadcrumbs, accountCreateForm} = useLoaderData<typeof loader>();

    return (
        <Page breadcrumbs={breadcrumbs}>
            <DefaultForm {...accountCreateForm} />
        </Page>
    )
}

export default AccountCreate;
