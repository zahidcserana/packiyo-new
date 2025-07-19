import {useLoaderData, useNavigate} from "@remix-run/react";
import dataServer from './data.server.tsx'
import {ActionFunction,} from "@remix-run/node";
import React, {useRef} from "react";
import SimpleTable from "../../components/Table/SimpleTable/SimpleTable";
import {checkConnectionName, connect} from "../../.server/services/connections.js";
import Page from "../../components/Layout/Page/Page";
import CreateModal from "./Modal/CreateModal";
import {requireAuth} from '../../.server/services/auth.server.js';

export function meta() {
    return [
        {
            title: 'Connections',
            description: '',
        },
    ];
}

export const loader = async ({request}) => {
    await requireAuth({request});

    const response = await dataServer({request})

    return {
        breadcrumbs: [
            { label: 'Settings', to: '/settings' },
            { label: 'Connections', to: '' }
        ],
        availableConnections: response.availableConnections,
        connectionsTable: response.connectionsTable,
        accountId: response.accountId,
        credential: response.credential,
    }
};

export const action: ActionFunction = async ({request, params}) => {
    const formData = await request.formData();
    const action = formData.get('action');

    switch (action) {
        case 'check-connection-name':
            return checkConnectionName({request, params, formData});
        case 'connect':
            return connect({request, params, formData});
        default:
            throw new Error("Unknown action");
    }
};

interface CreateModalRefInterface {
    toggleModal(value:boolean): void;
}

const Connections = () => {
    const {breadcrumbs, connectionsTable} = useLoaderData<typeof loader>();

    const createModalRef = useRef<CreateModalRefInterface>();
    const navigate = useNavigate();

    const handleEditClick = (item) => {
        navigate(`/settings/connections/edit/${item.id}`);
    };

    const handleCreateClick = () => {
        createModalRef.current?.toggleModal(true)
    };

    return (
        <Page breadcrumbs={breadcrumbs} handleCreateClick={handleCreateClick} bodyClasses={'p-0'}>
            <SimpleTable {...connectionsTable} handleEditClick={handleEditClick} />
            <CreateModal ref={createModalRef} />
        </Page>
    )
}

export default Connections;
