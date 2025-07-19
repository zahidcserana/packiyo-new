import {Col, FormControl, FormGroup, FormLabel, FormText, Row} from "react-bootstrap";
import React from "react";

const TextInput = ({ name, type, title, value, placeholder, description, required = false}) => {
    return <FormGroup className={type === 'hidden' ? 'd-none' : 'mb-4'}>
        <Row>
            <Col>
                <FormLabel>{title}</FormLabel><br/>
                {description && (<FormText muted>{description}</FormText>)}
            </Col>
            <Col>
                <FormControl required={required} type={type} placeholder={placeholder} name={name} defaultValue={value ?? ''} />
            </Col>
        </Row>
    </FormGroup>
}

export default TextInput;
