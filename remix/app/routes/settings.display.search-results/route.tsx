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
            title: 'Display Search Results',
            description: 'This is the account page meta description.',
        },
    ];
}

export const loader = async ({request}) => {
    await requireAuth({request});

    return {
        breadcrumbs: [
            { label: 'Settings', to: '/settings' },
            { label: 'Default Search Results', to: '' }
        ],
        SearchResultsForm: await data({request})
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

const SearchResults = () => {
    const {breadcrumbs, SearchResultsForm} = useLoaderData<typeof loader>();

    return (
        <Page breadcrumbs={breadcrumbs}>
            <DefaultForm {...SearchResultsForm} />
        </Page>
    )
}

export default SearchResults;
