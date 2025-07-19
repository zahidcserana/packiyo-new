import {Card} from "react-bootstrap";
import Page from "../../components/Layout/Page/Page";
import React from "react";
import {useLoaderData} from "@remix-run/react";
import {requireAuth} from '../../.server/services/auth.server.js';

export const loader = async ({request}) => {
    await requireAuth({ request });

    return {
        breadcrumbs: [
            { label: 'Welcome!', to: '' }
        ],
    }
};

const Settings = () => {
    const {breadcrumbs} = useLoaderData<typeof loader>();

    return (
        <Page breadcrumbs={breadcrumbs}>
            <Card.Text>
                Here you can adjust the settings with ease.
            </Card.Text>
        </Page>
    )
}

export default Settings;
