import React, { useEffect, useRef, useState } from "react";
import { Col, FormControl, FormGroup, FormLabel, FormText, Row, Form, Image, Button } from "react-bootstrap";
import { useSubmit } from "@remix-run/react";

interface Props {
    name: string;
    title: string;
    value: string;
    id: string;
    description?: string;
    required?: boolean;
}

const FileInput: React.FC<Props> = ({ name, title, value, id, description , required = false}) => {
    const [source, setSource] = useState<string>(value);
    const submit = useSubmit();
    const fileInputRef = useRef<HTMLInputElement>(null);

    const handleImageDelete = async () => {
        const formData = new FormData();
        formData.append("formType", "delete-image-form");
        formData.append("image_id", id);
        submit(formData, { method: "post", action: "/submit" });
    };

    const clearInput = () => {
        fileInputRef.current.value = '';
    };

    useEffect(() => {
        setSource(value);
        clearInput();
    }, [value]);

    return (
        <FormGroup className="mb-4">
            <Row>
                <Col>
                    <FormLabel>{title}</FormLabel><br />
                    {description && <FormText muted>{description}</FormText>}
                </Col>
                <Col>
                    <FormControl
                        required={required}
                        type="file"
                        name={name}
                        ref={fileInputRef}
                    />
                    {source && (
                        <Row>
                            <Col xs={12}>
                                <Image
                                    style={{ width: '150px', height: '150px', objectFit: 'cover' }}
                                    rounded
                                    src={source}
                                    className="mt-4"
                                />
                            </Col>
                            <Col xs={12}>
                                <Button
                                    variant="danger"
                                    size="sm"
                                    className="mt-4"
                                    onClick={handleImageDelete}
                                >
                                    Delete Image
                                </Button>
                            </Col>
                        </Row>
                    )}
                </Col>
            </Row>
        </FormGroup>
    );
};

export default FileInput;
