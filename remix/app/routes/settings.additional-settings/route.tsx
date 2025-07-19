import {useLoaderData} from "@remix-run/react";
import data from './data.tsx'
import {ActionFunction} from "@remix-run/node";
import {
    saveContactInformation,
    saveCustomerSettings,
    saveUserSettings, uploadImage
} from "../../.server/services/settings.js";
import DefaultForm from "../../components/Form/Form";
import React from "react";
import Page from "../../components/Layout/Page/Page";
import {requireAuth} from '../../.server/services/auth.server.js';

export function meta() {
    return [
        {
            title: 'Additional Settings',
            description: ''
        },
    ];
}

export const loader = async ({request}) => {
    await requireAuth({request});

    return {
        breadcrumbs: [
            { label: 'Settings', to: '/settings' },
            { label: 'Additional Settings', to: '' }
        ],
        accountForm: await data({request})
    }
};

export const action: ActionFunction = async ({request}) => {
    const formData = await request.formData();

    let userSettingsSuccess, customerSettingsSuccess, contactInformationSuccess;

    // try {
    //     userSettingsSuccess = await saveUserSettings({ request, formData });
    // } catch (error) {
    //     userSettingsSuccess = error;
    // }

    try {
        customerSettingsSuccess = await saveCustomerSettings({ request, formData });
    } catch (error) {
        customerSettingsSuccess = error;
    }

    try {
        contactInformationSuccess = await saveContactInformation({ request, formData });
    } catch (error) {
        contactInformationSuccess = error;
    }

    // if (!userSettingsSuccess.success) {
    //     return userSettingsSuccess
    // }

    if (!customerSettingsSuccess.success) {
        return customerSettingsSuccess
    }

    if (!contactInformationSuccess.success) {
        return contactInformationSuccess
    }

    return customerSettingsSuccess;
};

const Account = () => {
    const {breadcrumbs, accountForm} = useLoaderData<typeof loader>();

    return (
        <Page breadcrumbs={breadcrumbs}>
            <DefaultForm {...accountForm} />
        </Page>
    )
}

export default Account;
