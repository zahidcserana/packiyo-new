import React, {forwardRef, useEffect, useState, useRef, useImperativeHandle} from 'react';
import {Button, Card, Col, Modal, Row} from "react-bootstrap";
import {useActionData, useLoaderData, useSubmit} from "@remix-run/react";
import DefaultForm from "../../../components/Form/Form";
import {loader} from "../route";

interface AvailableConnection {
    image_url: string;
    name: string;
    type: string;
    oauth_connection: boolean;
    configuration: []
}

interface ModalProps {
    ref: React.Ref<any>;
}

interface FormRefInterface {
    submitForm(): void;
}

const ConnectionForm = (connection, accountId, credential) => {
    const { oauth_connection: oauthConnection, type, configuration: response } = connection;

    const form = {
        title: '',
        method: 'post',
        action: oauthConnection === true ? 'check-connection-name' : 'connect',
        actionTitle: 'Create',
        fields: [
            { name: 'external_integration_id', value: credential?.settings?.external_integration_id ?? '', type: 'hidden', required: true },
            { name: 'order_channel_type', value: type, type: 'hidden', required: true },
            { name: 'skipoauth', value: 'false', type: 'hidden', required: true },
            { name: 'migrate_to_order_channel_id', value: 0, type: 'hidden', required: true },
            { name: 'oauth_connection', value: oauthConnection === true, type: 'hidden', required: true },
            { name: 'customer_id', value: accountId, type: 'hidden', required: true },
            { name: 'name', value: '', title: 'Name of the Integration', type: 'text', required: true }
        ]
    };

    for (const [index, field] of response.entries()) {
        const isRequired = oauthConnection === true ? field.required_on_oauth_connection && field.setup_field : field.setup_field;

        if (isRequired) {
            form.fields.push(
                {
                    name: `configurations[${index}][field]`,
                    value: field.field,
                    type: 'hidden',
                    required: true
                },
                {
                    name: `configurations[${index}][value]`,
                    value: field.value,
                    title: field.title,
                    type: field.type.toLowerCase(),
                    required: true
                }
            );
        }
    }

    return form;
};

const CreateModal: React.ForwardRefExoticComponent<React.PropsWithoutRef<{}> & React.RefAttributes<unknown>> = forwardRef(({}, ref) => {
    const [showModal, setShowModal] = useState<boolean>(false);
    const [connection, setConnection] = useState<AvailableConnection | null>(null);
    const {accountId, availableConnections, credential} = useLoaderData<typeof loader>();
    const actionData = useActionData();
    const connectionFormRef = useRef<FormRefInterface>();
    const [connectionFrom, setNewConnectionForm] = useState(null);
    const [action, setAction] = useState('check-connection-name');

    const toggleModal = (value: boolean) => setShowModal(value);

    useImperativeHandle(ref, () => ({
        toggleModal
    }));

    useEffect(() => {
        if (connection) {
            if (!connection.oauth_connection) {
                setAction('connect');
            }

            setNewConnectionForm(ConnectionForm(connection, accountId, credential));
        }
    }, [connection]);

    useEffect(() => {
        if (action === 'connect' && connection.oauth_connection) {
            connectionFormRef.current?.submitForm();
        }
    }, [action]);

    const handleModalClose = () => {
        setShowModal(false);
        resetFormData();
    }

    const resetFormData = () => {
        setAction('check-connection-name');
        setNewConnectionForm(null);
        setConnection(null);
    };

    const redirectAndConnect = (url) => {
        window.open(url, "_blank");
        setAction('connect');
    }

    useEffect(() => {
        if (actionData?.success && !actionData?.redirectUrl) {
            handleModalClose();
        } else if (actionData?.success && actionData?.redirectUrl) {
            redirectAndConnect(actionData?.redirectUrl)
        } else {
            if (connection && connection?.oauth_connection) {
                setAction('check-connection-name');
            }
        }
    }, [actionData]);

    const renderAvailableConnections = () => (
        <Row>
            {availableConnections?.map((connection, index) => (
                <Col key={index} sm={3}>
                    <Card className="mb-3">
                        <Card.Img variant="top" src={connection.image_url} style={{ width: '100%', height: '100px', objectFit: 'contain' }} />
                        <Card.Body>
                            <Card.Title as="h6" className="text-truncate text-center mb-4">
                                {connection.name}
                            </Card.Title>
                            <div className="d-grid">
                                <Button variant="secondary" size="block" onClick={() => setConnection(connection)}>
                                    Choose
                                </Button>
                            </div>
                        </Card.Body>
                    </Card>
                </Col>
            ))}
        </Row>
    );

    return (
        <Modal show={showModal} onHide={handleModalClose} size="lg">
            <Modal.Header closeButton>
                <Modal.Title as="h6">New Connection</Modal.Title>
            </Modal.Header>
            <Modal.Body className={connection ? '' : 'pb-0'}>
                {connectionFrom ? <DefaultForm {...connectionFrom} action={action} ref={connectionFormRef} handleBackClick={resetFormData}/> : renderAvailableConnections()}
            </Modal.Body>
        </Modal>
    );
});

export default CreateModal;
