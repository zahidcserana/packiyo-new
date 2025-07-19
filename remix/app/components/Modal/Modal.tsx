import {Button, Modal, Spinner} from "react-bootstrap";
import {useRef, useState} from "react";
import {Form} from "@remix-run/react";

const SimpleModal = ({ data, onSubmit, onDismiss, title, children }) => {
    const [isOpen, setIsOpen] = useState(false);
    const formRef = useRef(null);
    const modalRef = useRef(null);

    const handleOpen = () => setIsOpen(true);

    const handleClose = () => setIsOpen(false);

    const handleSubmit = (e) => {
        e.preventDefault();
        const formData = new FormData(formRef.current);
        onSubmit(formData); // Pass form data to onSubmit handler
        handleClose();
    };

    return <Modal ref={modalRef} show={isOpen} onHide={() => setIsOpen(false)}>
        <Modal.Header closeButton>
            <Modal.Title as="h6">{title}</Modal.Title>
        </Modal.Header>
        <Modal.Body>
            {data && <p>Data: {data}</p>}

            <Form ref={formRef} onSubmit={handleSubmit}>
                {children}
            </Form>
        </Modal.Body>
        <Modal.Footer>
            <Button variant="secondary" onClick={() => handleClose()}>
                Cancel
            </Button>
            <Button variant="primary" type="submit">
                Save
            </Button>
        </Modal.Footer>
    </Modal>
}

export default SimpleModal;
