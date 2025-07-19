import {Button, Modal} from "react-bootstrap";
import React, {forwardRef, useImperativeHandle, useState} from "react";
import {Status} from "~/types/automations";
import {Form, useSubmit} from "@remix-run/react";

interface DisableAutomationModalProps extends Status {
    automation: any
}

export interface DisableAutomationModalRef {
    toggleModal: (value: boolean) => void;
}

const DisableAutomationModal = forwardRef<DisableAutomationModalRef | undefined, DisableAutomationModalProps>(({automation}, ref) => {
    const [showModal, setShowModal] = useState<boolean>(false);

    useImperativeHandle(ref, () => ({
        toggleModal(value: any) {
            handleToggleModal(value);
        },
    }));

    const submit = useSubmit();

    const handleFormSubmit = async (event: any) => {
        setShowModal(false);
        const formData = new FormData(event);
        submit(formData, { method: "post", action: "/automations" });
    };

    const handleToggleModal = (value: any) => {
        setShowModal(value);
    }

    return (
        <Modal show={showModal} onHide={() => setShowModal(false)} centered={true}>
            <Form onSubmit={(event) => {
                event.preventDefault();
                handleFormSubmit(event.target);
            }}>
                <input type="hidden" name="action" value="update-automation-status"/>
                <input type="hidden" name="id" value={automation?.id}/>
                <input type="hidden" name="is_checked" value="false"/>
                <Modal.Header closeButton>
                    <Modal.Title>Deactivate automation</Modal.Title>
                </Modal.Header>
                <Modal.Body>
                    <p>Are you sure you want to deactivate?</p>
                    <p>This will stop this automation from running immediately.</p>
                </Modal.Body>
                <Modal.Footer>
                    <Button variant="secondary" type="submit">
                        Yes, deactivate
                    </Button>
                    <Button variant="primary" onClick={() => setShowModal(false)}>
                        No, keep it active
                    </Button>
                </Modal.Footer>
            </Form>
        </Modal>
    );
});

export default DisableAutomationModal;
