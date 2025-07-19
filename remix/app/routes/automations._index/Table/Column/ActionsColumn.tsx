import {
    Button,
    ListGroup,
    OverlayTrigger,
    Popover,
    Stack,
} from "react-bootstrap";
import { Automation, Status } from "~/types/automations";
import Eye from "../../../../components/Icon/Eye";
import ToggleSwitch from "../../../../components/ToggleSwitch";
import { BsThreeDots } from "react-icons/bs";
import { useState } from "react";
import EditPencil from "~/components/Icon/EditPencil";
import { useNavigate } from "@remix-run/react";

interface ActionsProps extends Status {
    onStatusClick: ({ isActivated, id }: Status) => void;
    onViewClick: (id: Automation["id"]) => void;
}

const ActionsColumn = ({
    isActivated,
    id,
    onStatusClick,
    onViewClick,
}: ActionsProps) => {
    const navigate = useNavigate();

    const onActiveChange = () => onStatusClick({ isActivated, id });

    const [showPopover, setShowPopover] = useState(false);
    const handleToggle = () => setShowPopover(!showPopover);
    const handleClickOutside = () => setShowPopover(false);
    const handleViewDetailsClick = (id: string) => {
        onViewClick(id);
        handleToggle();
    };

    const handleEditDetailsClick = (id: string) => {
        navigate(`/automations/edit/${id}`);
    };

    const popover = (
        <Popover>
            <Popover.Body className="p-0 pt-1 pb-1">
                <ListGroup>
                    <ListGroup.Item
                        action
                        onClick={() => handleViewDetailsClick(id)}
                        className="border-0 d-flex gap-2"
                    >
                        <Eye /> View Details
                    </ListGroup.Item>
                    <ListGroup.Item
                        action
                        onClick={() => handleEditDetailsClick(id)}
                        className="border-0 d-flex gap-2"
                    >
                        <EditPencil /> Edit
                    </ListGroup.Item>
                </ListGroup>
            </Popover.Body>
        </Popover>
    );

    return (
        <Stack gap={1} className="align-items-end">
            <OverlayTrigger
                trigger="click"
                placement="left"
                show={showPopover}
                onToggle={handleClickOutside}
                overlay={popover}
                rootClose
                rootCloseEvent="click"
            >
                <Button
                    onClick={handleToggle}
                    className="p-0 btn-grey-purple"
                    style={{
                        borderRadius: "100px",
                        width: "32px",
                        height: "32px",
                        display: "flex",
                        justifyContent: "center",
                        alignItems: "center",
                    }}
                >
                    <BsThreeDots />
                </Button>
            </OverlayTrigger>
            <ToggleSwitch
                optionLabels={["Active", "Inactive"]}
                id={id}
                checked={isActivated}
                onChange={onActiveChange}
            />
        </Stack>
    );
};

export default ActionsColumn;
