import {Col, FormGroup, FormLabel, FormSelect, FormText, Row} from "react-bootstrap";
import React from "react";

const SelectInput = ({name, title, options, value, description, required = false}) => {
    return <FormGroup className="mb-4">
        <Row>
            <Col>
                <FormLabel>{title}</FormLabel><br/>
                {description && (<FormText muted>{description}</FormText>)}
            </Col>
            <Col>
                <FormSelect required={required} name={name} defaultValue={value ?? ''}>
                    {options && Object.keys(options).map(key => (
                        <option key={key} value={key}>
                            {options[key]}
                        </option>
                    ))}
                </FormSelect>
            </Col>
        </Row>
    </FormGroup>
}

export default SelectInput;
