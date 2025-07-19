import {FormControl, FormGroup} from "react-bootstrap";
import React, {useEffect, useState} from "react";
import {IoSearchOutline} from "react-icons/io5";

interface SearchFieldProps {
    value: string | undefined;
    name?: string;
    placeholder: string;
    setData: any;
    action?: any
}

const SearchField: React.FC<SearchFieldProps> = ({ value, name, placeholder, setData, action }) => {
    const [timer, setTimer] = useState<any>(null);

    useEffect(() => {
        if (value !== null) {
            clearTimeout(timer);
            setTimer(setTimeout(() => {
                action();
            }, 500));
        }
    }, [value]);

    return <FormGroup className="with-icon">
        <IoSearchOutline size={20} />
        <FormControl className="search-field" type="text" placeholder={placeholder} name={name} defaultValue={value ?? ''} onChange={(event: any) => setData(event.target?.value)} />
    </FormGroup>
}

export default SearchField;
