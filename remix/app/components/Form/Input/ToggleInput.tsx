import {Col, FormCheck, FormGroup, FormLabel, FormText, Row} from "react-bootstrap";
import React, {useState} from "react";

const ToggleInput = ({name, title, initialValue, description, inverted, required = false}) => {
    const [value, setValue] = useState<number>(initialValue ? initialValue : 0);

    const handleChange = ({ target: { checked } }) => {
        setValue(inverted ? checked ? 0 : 1 : checked ? 1 : 0);
    };

    return <FormGroup className="mb-4">
        <Row>
            <Col>
                <FormLabel>{title}</FormLabel><br/>
                {description && (<FormText muted>{description}</FormText>)}
            </Col>
            <Col>
                <input type="hidden" name={name} defaultValue={value}/>
                <FormCheck
                    checked={inverted ? value != 1 : value == 1}
                    onChange={handleChange}
                    type="switch"
                />
            </Col>
        </Row>
    </FormGroup>
}

export default ToggleInput;
