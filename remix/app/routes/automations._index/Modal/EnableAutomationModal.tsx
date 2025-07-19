import {Button, Modal} from "react-bootstrap";
import React, {forwardRef, useImperativeHandle, useState} from "react";
import {Status} from "~/types/automations";
import {Form, useSubmit} from "@remix-run/react";

interface EnableAutomationModalProps extends Status {
    automation: any
}

export interface EnableAutomationModalRef {
    toggleModal: (value: boolean) => void;
}

const EnableAutomationModal = forwardRef<EnableAutomationModalRef | undefined, EnableAutomationModalProps>(({automation}, ref) => {
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
                <input type="hidden" name="is_checked" value="true"/>
                <Modal.Header closeButton>
                    <Modal.Title>Activate automation</Modal.Title>
                </Modal.Header>
                <Modal.Body>
                    <p>Are you sure you want to activate?</p>
                    <p>This will make the automation start running immediately.</p>
                </Modal.Body>
                <Modal.Footer>
                    <Button variant="secondary" type="submit">
                        Yes, activate
                    </Button>
                    <Button variant="primary" onClick={() => setShowModal(false)}>
                        No, keep it inactive
                    </Button>
                </Modal.Footer>
            </Form>
        </Modal>
    );
});

export default EnableAutomationModal;
