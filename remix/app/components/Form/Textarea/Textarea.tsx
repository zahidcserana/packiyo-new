import {Col, FormControl, FormGroup, FormLabel, FormText, Row} from "react-bootstrap";
import React from "react";

const Textarea = ({key, name, title, value, placeholder, description, required = false}) => {
    return <FormGroup className="mb-4">
        <Row>
            <Col>
                <FormLabel>{title}</FormLabel><br/>
                {description && (<FormText muted>{description}</FormText>)}
            </Col>
            <Col>
                <FormControl required={required} as="textarea" rows={3} placeholder={placeholder} name={name} defaultValue={value ?? ''} />
            </Col>
        </Row>
    </FormGroup>
}

export default Textarea;
