import React from "react";
import {Card, Table} from "react-bootstrap";

import {
    flexRender,
    getCoreRowModel,
    useReactTable,
} from '@tanstack/react-table'
import Pagination from "./Pagination/Pagination";

interface AdvancedTableProps {
    data: any;
    onRowClick: any;
    columns: any;
    page: any;
}

const AdvancedTable = ({data, columns, page, onRowClick}: AdvancedTableProps) => {
    const defaultData = React.useMemo(() => [], [])

    const table = useReactTable({
        data: data ?? defaultData,
        columns,
        getCoreRowModel: getCoreRowModel(),
        manualSorting: true,
        manualPagination: true,
        debugTable: true,
    })

    return (
        <Card className="mb-4">
            <Table variant="mixed" hover responsive="sm">
                <thead>
                    {table.getHeaderGroups().map(headerGroup => (
                        <tr key={headerGroup.id}>
                            {headerGroup.headers.map(header => {
                                return (
                                    <th key={header.id} colSpan={header.colSpan}>
                                        {header.isPlaceholder ? null : (
                                            <div>
                                                {flexRender(
                                                    header.column.columnDef.header,
                                                    header.getContext()
                                                )}
                                            </div>
                                        )}
                                    </th>
                                )
                            })}
                        </tr>
                    ))}
                </thead>
                <tbody>
                    {table.getRowModel().rows.map(row => {
                        return (
                            <tr key={row.id} onClick={() => onRowClick?.(row)} style={{cursor: onRowClick ? 'pointer' : 'default'}}>
                                {row.getVisibleCells().map(cell => {
                                    return (
                                        <td key={cell.id}>
                                            {flexRender(
                                                cell.column.columnDef.cell,
                                                cell.getContext()
                                            )}
                                        </td>
                                    )
                                })}
                            </tr>
                        )
                    })}
                </tbody>
            </Table>

            <Pagination page={page}/>
        </Card>
    )
}

export default AdvancedTable;
