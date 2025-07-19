import { ActionFunction } from "@remix-run/node";
import data from './data.tsx'
import { useLoaderData } from "@remix-run/react";
import {
    saveContactInformation,
    saveCustomerSettings,
    saveUserSettings, uploadImage
} from "../../.server/services/settings.js";
import DefaultForm from "../../components/Form/Form";
import Page from "../../components/Layout/Page/Page";
import {requireAuth} from '../../.server/services/auth.server.js';

export function meta() {
    return [
        {
            title: 'Account Information',
            description: ''
        },
    ];
}

export const loader = async ({request}) => {
    await requireAuth({request});

    return {
        breadcrumbs: [
            { label: 'Settings', to: '/settings' },
            { label: 'Account', to: '' }
        ],
        accountForm: await data({request})
    }
};

export const action: ActionFunction = async ({request}) => {
    const formData = await request.formData();

    let userSettingsSuccess, customerSettingsSuccess, contactInformationSuccess;

    try {
        userSettingsSuccess = await saveUserSettings({ request, formData });
    } catch (error) {
        userSettingsSuccess = error;
    }

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

    const accountLogo = formData.get('account_logo');

    if (accountLogo && "size" in accountLogo && accountLogo.size > 0) {
        formData.delete('account_logo');
        formData.append('file', accountLogo);
        formData.append('image_type', 'account_logo');
        await uploadImage({ request, formData });
    }

    const accountFavicon = formData.get('account_favicon');

    if (accountFavicon && "size" in accountFavicon && accountFavicon.size > 0) {
        formData.delete('account_favicon');
        formData.append('file', accountFavicon);
        formData.append('image_type', 'account_favicon')
        await uploadImage({ request, formData });
    }

    if (!userSettingsSuccess.success) {
        return userSettingsSuccess
    }

    if (!customerSettingsSuccess.success) {
        return customerSettingsSuccess
    }

    if (!contactInformationSuccess.success) {
        return contactInformationSuccess
    }

    return userSettingsSuccess;
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
