import { Automation } from "~/types/automations";
import { Button } from "react-bootstrap";
import React from "react";
import Tooltip from "~/components/Tooltip/Tooltip";

interface ClientActionProps {
    onClientClick: () => void;
    clientList: Automation["clients"];
}

interface AutomationNameCellProps {
    name: string;
    createdBy?: string;
    onClientClick: () => void;
    id: Automation["id"];
    clientList: Automation["clients"];
    singleLine?: boolean;
}

export const ClientAction = ({
    onClientClick,
    clientList,
}: ClientActionProps) => {
    return (
        <Button variant="badge-green" onClick={onClientClick}>
            {`${clientList?.at(0)}`}
            {clientList.length > 1 ? ` +${clientList.length - 1}` : ``}
        </Button>
    );
};

const NameColumn = ({
    clientList,
    name,
    onClientClick,
    id,
    createdBy,
    singleLine,
}: AutomationNameCellProps) => {
    return (
        <div className="d-flex flex-column align-items-start gap-2">
            <div className="flex items-center font-semibold text-[16px] gap-2">
                <Tooltip onlyWrapper bgDark hideArrow tooltipText={name}>
                    <div className="overflow-hidden text-ellipsis max-w-[160px] xl:max-w-[200px] 2xl:max-w-[300px]">
                        <span className="whitespace-nowrap">{name}</span>
                    </div>
                </Tooltip>
                <Tooltip
                    onlyWrapper
                    bgDark
                    hideArrow
                    placement="right"
                    tooltipText="ID"
                >
                    <span style={{ color: "#5e39cc" }}>#{id}</span>
                </Tooltip>
            </div>
            {(clientList.length > 0 || createdBy) && (
                <div className="d-flex align-items-center gap-2">
                    {clientList.length > 0 && (
                        <div className="d-flex align-items-center gap-1">
                            <ClientAction
                                onClientClick={onClientClick}
                                clientList={clientList}
                            />
                        </div>
                    )}
                    {createdBy && (
                        <span style={{ fontSize: "12px" }}>
                            Created by {createdBy}
                        </span>
                    )}
                </div>
            )}
        </div>
    );
};

export default NameColumn;
