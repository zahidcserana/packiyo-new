import React from "react";
import {FormCheck} from "react-bootstrap";

interface ListCheckboxItemProps {
    key?: number;
    name?: string;
    title?: string;
    isChecked?: boolean;
    action?: any
}

const ListCheckboxItem: React.FC<ListCheckboxItemProps> = ({  name, title, isChecked, action = null }) => {
    return (
        <FormCheck type="checkbox" name={name} label={title} checked={isChecked} onChange={action} />
    )
}

export default ListCheckboxItem;
