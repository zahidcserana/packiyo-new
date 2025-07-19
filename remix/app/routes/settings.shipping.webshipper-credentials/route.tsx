import {Form, useLoaderData, useSubmit,} from "@remix-run/react";
import {Button, Modal} from "react-bootstrap";
import React, {useState} from "react";

import {ActionFunction, json} from "@remix-run/node";
import data from "./data";
import {createCredential, deleteCredential, updateCredential} from "./submitData";
import {renderField} from "../../helpers/helpers";
import SimpleTable from "../../components/Table/SimpleTable/SimpleTable";
import TextInput from "../../components/Form/Input/TextInput";
import Page from "../../components/Layout/Page/Page";
import {requireAuth} from '../../.server/services/auth.server.js';

export function meta() {
    return [
        {
            title: 'Webshipper Credentials',
            description: '',
        },
    ];
}

export const loader = async ({request}) => {
    await requireAuth({request});

    const response = await data({request});

    return {
        breadcrumbs: [
            { label: 'Settings', to: '/settings' },
            { label: 'Webshipper Credentials', to: '' }
        ],
        credentialsTable: response.credentialsTable
    }
};

export const action: ActionFunction = async ({request}) => {
    const formData = await request.formData();
    const formType = formData.get('formType');

    switch (formType) {
        case 'edit-credential-form':
            return updateCredential({request, formData});
        case 'delete-credential-form':
            return deleteCredential({request, formData});
        case 'create-credential-form':
            return createCredential({request, formData});
        default:
            return json({ message: 'Invalid form type.' });
    }
};

const WebshipperCredentials = () => {
    const {breadcrumbs, credentialsTable} = useLoaderData<typeof loader>();
    const [showEditModal, setShowEditModal] = useState(false);
    const [showCreateModal, setShowCreateModal] = useState(false);
    const [showDeleteModal, setShowDeleteModal] = useState(false);
    const [selectedItem, setSelectedItem] = useState(null);

    const submit = useSubmit();

    const handleEditClick = (item) => {
        setSelectedItem(item);
        setShowEditModal(true);
    };

    const handleEditFormSubmit = async (formData) => {
        submit(formData, { method: "post" });
        setShowEditModal(false);
    };

    const handleCreateClick = (item) => {
        setSelectedItem(item);
        setShowCreateModal(true);
    };

    const handleCreateFormSubmit = async (formData) => {
        submit(formData, { method: "post" });
        setShowCreateModal(false);
    };

    const handleDeleteClick = (item) => {
        setSelectedItem(item);
        setShowDeleteModal(true);
    };

    const handleDeleteFormSubmit = async (formData) => {
        submit(formData, { method: "post" });
        setShowDeleteModal(false);
    };

    return (
        <Page breadcrumbs={breadcrumbs} handleCreateClick={handleCreateClick} bodyClasses={'p-0'}>
            <SimpleTable {...credentialsTable} handleDeleteClick={handleDeleteClick} handleEditClick={handleEditClick} />
            <Modal show={showCreateModal} onHide={() => setShowCreateModal(false)}>
                <Form onSubmit={(event) => {
                    event.preventDefault();
                    handleCreateFormSubmit(new FormData(event.target));
                }}>
                    <input type="hidden" name="formType" value="create-credential-form" />
                    <Modal.Header closeButton>
                        <Modal.Title as="h6">Create Item</Modal.Title>
                    </Modal.Header>
                    <Modal.Body>
                        <TextInput name="api_key" type="text" title="API Key" required={true} />
                        <TextInput name="api_base_url" type="text" title="Api Base Url" required={true}/>
                        <TextInput name="order_channel_id" type="number" title="Order Channel Id" required={true} />
                    </Modal.Body>
                    <Modal.Footer>
                        <Button variant="secondary" onClick={() => setShowCreateModal(false)}>
                            Cancel
                        </Button>
                        <Button variant="primary" type="submit">
                            Save
                        </Button>
                    </Modal.Footer>
                </Form>
            </Modal>
            <Modal show={showEditModal} onHide={() => setShowEditModal(false)}>
                <Form onSubmit={(event) => {
                    event.preventDefault();
                    handleEditFormSubmit(new FormData(event.target));
                }}>
                    <input type="hidden" name="id" value={selectedItem?.id} />
                    <input type="hidden" name="formType" value="edit-credential-form" />
                    <Modal.Header closeButton>
                        <Modal.Title as="h6">Edit Item</Modal.Title>
                    </Modal.Header>
                    <Modal.Body>
                        {selectedItem?.fields?.map((field, index) => {
                            return renderField(field);
                        })}
                    </Modal.Body>
                    <Modal.Footer>
                        <Button variant="secondary" onClick={() => setShowEditModal(false)}>
                            Cancel
                        </Button>
                        <Button variant="primary" type="submit">
                            Save
                        </Button>
                    </Modal.Footer>
                </Form>
            </Modal>
            <Modal show={showDeleteModal} onHide={() => setShowDeleteModal(false)}>
                <Form onSubmit={(event) => {
                    event.preventDefault();
                    handleDeleteFormSubmit(new FormData(event.target));
                }}>
                    <input type="hidden" name="id" value={selectedItem?.id} />
                    <input type="hidden" name="formType" value="delete-credential-form" />
                    <Modal.Header closeButton>
                        <Modal.Title as="h6">Confirm Delete</Modal.Title>
                    </Modal.Header>
                    <Modal.Body>
                        Are you sure you want to delete item "#{selectedItem?.id}"?
                    </Modal.Body>
                    <Modal.Footer>
                        <Button variant="secondary" onClick={() => setShowDeleteModal(false)}>
                            Cancel
                        </Button>
                        <Button variant="danger" type="submit">
                            Delete
                        </Button>
                    </Modal.Footer>
                </Form>
            </Modal>
        </Page>
    )
}

export default WebshipperCredentials;
