import { useMemo } from "react";
import { Button, Image, Table } from "react-bootstrap";

interface Header {
    key: string;
    title: string;
    type?: string;
}

interface Field {
    name: string;
    value: string | any;
}

interface Row {
    fields: Field[];
    objectType: string;
    operation: string;
}

interface SimpleTableProps {
    headers: Header[];
    rows: Row[];
    handleCreateClick?: (row: Row) => void;
    handleEditClick?: (row: Row) => void;
    handleDeleteClick?: (row: Row) => void;
}

const checkAndDisplay = (value?: Field['value'], fieldType?: Header['type']) => {
    if (fieldType === "image") {
        return <Image style={{ width: '80px', height: '80px', objectFit: 'cover' }} rounded src={value} alt="Image" />;
    } else {
        return value;
    }
}


const SimpleTable = ({headers, rows, handleEditClick, handleDeleteClick, handleCreateClick}: SimpleTableProps) => {
    const tableBody = useMemo(() => {
        if (!rows || !headers) return null;

        return rows.map((row, index) => (
            <tr key={index} className="align-middle">
                {headers.map((header: Header) => {
                    const value = row.fields.find?.((field: Field) => field?.name === header.key)?.value;
                    return(
                        <td key={header.key}>
                            {checkAndDisplay(value, header?.type)}
                        </td>
                    )
                })}

                {(handleEditClick || handleDeleteClick || handleCreateClick) &&
                    <td>
                        <div className="table-actions">
                            {handleEditClick && <Button variant="secondary" size="sm" onClick={() => handleEditClick(row)}>Edit</Button>}
                            {handleDeleteClick && <Button variant="secondary" size="sm" onClick={() => handleDeleteClick(row)}>Delete</Button>}
                            {handleCreateClick && <Button variant="secondary" size="sm" onClick={() => handleCreateClick(row)}>Create</Button>}
                        </div>
                    </td>
                }
            </tr>
        ));
    }, [rows, headers]);

    return (
        <>
            <Table striped hover responsive>
                <thead>
                    <tr>
                        {headers.map((header) => (
                            <th key={header.key} className="align-middle">
                                {header.title}
                            </th>
                        ))}
                        {(handleEditClick || handleDeleteClick || handleCreateClick) && <th>Actions</th>}
                    </tr>
                </thead>
                <tbody>
                    {tableBody}
                </tbody>
            </Table>
            {tableBody?.length ? '' : <p className="text-center p-3 m-0">No data to show.</p>}
        </>
    )
}

export default SimpleTable;
