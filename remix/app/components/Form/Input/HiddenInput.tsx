import {FormControl} from "react-bootstrap";
import React from "react";

const HiddenInput = ({name,  value, required = false}) => {
    return <FormControl required={required} type="hidden" name={name} defaultValue={value ?? ''} />
}

export default HiddenInput;
