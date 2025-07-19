import {Button, Col, Row, Spinner} from "react-bootstrap";
import React, {forwardRef, useEffect, useImperativeHandle, useRef, useState} from "react";
import {renderField} from "../../helpers/helpers";
import {Form, useActionData, useNavigation} from "@remix-run/react";
import {toast} from "react-hot-toast";

interface FormProps {
    method: string,
    fields?: [],
    encType?: string,
    actionTitle?:string,
    title?:string,
    action?:string;
    handleBackClick?:void;
    handleFormSubmit?:void;
    ref?: React.Ref<any>;
}

const DefaultForm: React.ForwardRefExoticComponent<React.PropsWithoutRef<{
    readonly encType?: any;
    readonly handleBackClick?: any;
    readonly method?: any;
    readonly actionTitle?: any;
    readonly handleFormSubmit?: any;
    readonly action?: any;
    readonly fields?: any;
    readonly title?: any
}> & React.RefAttributes<unknown>> = forwardRef(({ method, fields = [], encType, actionTitle = 'Save', title, action, handleBackClick, handleFormSubmit }, ref) => {
    const formRef = useRef(null);
    const actionData = useActionData();
    const buttonRef = useRef();
    const [isSubmitting, setIsSubmitting] = useState(false);

    useEffect(() => {
        if (actionData?.ok) {
            formRef.current.reset();
        }

        if (actionData && isSubmitting) {
            if (actionData.success === true) {
                toast.success(actionData.message, { position: "top-right", duration: 3000 });
            } else if (actionData.success === false) {
                toast.error(actionData.message, { position: "top-right", duration: 5000 });
            }
        }

        setIsSubmitting(false);
    }, [actionData]);

    useImperativeHandle(ref, () => ({
        submitForm: () => {
            buttonRef.current.click()
        }
    }));

    const handleSubmit = () => {
        setIsSubmitting(true); // Set submission status to true
    };

    return (
        <Form method={method} encType={encType} ref={formRef} onSubmit={handleFormSubmit ?? handleSubmit}>
            {title && <h6 className="mb-3">{title}</h6>}
            {fields.map((field) => {
                return renderField(field);
            })}
            <Row>
                {handleBackClick && (
                    <Col>
                        <Button
                            variant="secondary"
                            size="sm"
                            onClick={handleBackClick}
                        >
                            Back
                        </Button>
                    </Col>
                )}
                <Col className="d-flex justify-content-end">
                    <Button
                        disabled={isSubmitting}
                        variant="primary"
                        type="submit"
                        className="d-flex align-items-center justify-content-between"
                        name="action"
                        value={action}
                        ref={buttonRef}
                    >
                        {isSubmitting ? <>Loading..<Spinner animation="grow" size="sm" className="ms-2" /></> : <>{actionTitle}</>}
                    </Button>
                </Col>
            </Row>
        </Form>
    )
});

export default DefaultForm;
