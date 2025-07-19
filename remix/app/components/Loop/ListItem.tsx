import React from "react";

interface ListItemProps {
    key?: number;
    value: any;
}

const ListItem: React.FC<ListItemProps> = ({ value }) => {
    return <span className="font-16">
        {value}
    </span>
}

export default ListItem;
