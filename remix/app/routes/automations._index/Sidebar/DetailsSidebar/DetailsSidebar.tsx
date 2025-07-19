import Sidebar, { SidebarHandle } from "../../../../components/Sidebar/Sidebar";
import React, { forwardRef, useImperativeHandle, useRef } from "react";
import { Automation, Status } from "~/types/automations";
import Summary from "./Summary";

interface DetailsSidebarProps extends Status {
    automation: Automation;
    showClientsSidebar: () => void;
}

export interface DetailsSidebarRef {
    toggleSidebar: (value: boolean) => void;
}

const DetailsSidebar = forwardRef<
    DetailsSidebarRef | undefined,
    DetailsSidebarProps
>(({ automation, showClientsSidebar }, ref) => {
    const sidebarRef = useRef<SidebarHandle>(null);

    useImperativeHandle(ref, () => ({
        toggleSidebar(value: any) {
            handleToggleSidebar(value);
        },
    }));

    const handleToggleSidebar = (value: any) => {
        sidebarRef.current?.toggleSidebar(value);
    };

    return (
        <Sidebar
            id={automation?.id}
            idTooltip="Automation ID"
            title={automation?.name}
            ref={sidebarRef}
            placement="end"
        >
            <Summary
                automation={automation}
                showClientsSidebar={showClientsSidebar}
            />
        </Sidebar>
    );
});

export default DetailsSidebar;
