import { BiSortAlt2, BiSortDown, BiSortUp } from "react-icons/bi";
import { useSortWithPagination } from "../useSortWithPagination";

interface HeaderSortableProps {
    resource: string;
    label: string;
    minWidth?: number;
}

const SortableColumn = ({
    label,
    resource,
    minWidth = 0,
}: HeaderSortableProps) => {
    const { handleSort, isSortableExists, sortable } = useSortWithPagination();

    const styles = {
        minWidth,
        display: "flex",
        alignItems: "center",
    };

    if (!isSortableExists(resource))
        return (
            <span style={styles}>
                {label}
                <BiSortAlt2
                    className="mx-2"
                    onClick={() => handleSort(resource)}
                    role="button"
                />
            </span>
        );
    if (sortable.includes("-"))
        return (
            <span style={styles}>
                {label}
                <BiSortUp
                    className="mx-2"
                    onClick={() => handleSort(resource)}
                    role="button"
                />
            </span>
        );
    return (
        <span style={styles}>
            {label}
            <BiSortDown
                className="mx-2"
                onClick={() => handleSort(resource)}
                role="button"
            />
        </span>
    );
};

export default SortableColumn;
