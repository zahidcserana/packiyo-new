import React, { useEffect, useMemo, useRef, useState } from "react";
import AdvancedTable from "../../../components/Table/AdvancedTable/AdvancedTable";
import { ColumnDef, Row } from "@tanstack/react-table";
import { Automation, Page, Status } from "~/types/automations";
import TooltipColumn from "../../../components/Table/AdvancedTable/Column/TooltipColumn";
import { useRootLoaderData } from "~/root";
import ActionsColumn from "./Column/ActionsColumn";
import NameColumn from "./Column/NameColumn";
import HeaderSortable from "../../../components/Table/AdvancedTable/Column/SortableColumn";
import Column from "../../../components/Table/AdvancedTable/Column/Column";
import ClientsSidebar, {
    ClientsSidebarRef,
} from "../Sidebar/ClientsSidebar/ClientsSidebar";
import DetailsSidebar, {
    DetailsSidebarRef,
} from "../Sidebar/DetailsSidebar/DetailsSidebar";
import EnableAutomationModal, {
    EnableAutomationModalRef,
} from "../Modal/EnableAutomationModal";
import DisableAutomationModal, {
    DisableAutomationModalRef,
} from "../Modal/DisableAutomationModal";
import TooltipHeader from "~/components/Table/AdvancedTable/Header/TooltipHeader";

interface AutomationsTableProps {
    automations: Automation[];
    page: Page;
}

const AutomationsTable: React.FC<AutomationsTableProps> = ({
    automations,
    page,
}: AutomationsTableProps) => {
    const user = useRootLoaderData();
    const clientsSidebarRef = useRef<ClientsSidebarRef>(null);
    const detailsSidebarRef = useRef<DetailsSidebarRef>(null);
    const enableAutomationModalRef = useRef<EnableAutomationModalRef>(null);
    const disableAutomationModalRef = useRef<DisableAutomationModalRef>(null);

    const [currentAutomation, setCurrentAutomation] = useState<
        Automation | undefined
    >(undefined);

    const handleShowDetailsSidebar = (id: string) => {
        handleCurrentAutomation(id);
        detailsSidebarRef.current?.toggleSidebar(true);
    };

    const handleShowClientsSidebar = (id: string | undefined) => {
        handleCurrentAutomation(id || "");
        detailsSidebarRef.current?.toggleSidebar(true);
        clientsSidebarRef.current?.toggleSidebar(true);
    };

    const onStatusClick = ({ isActivated, id }: Status) => {
        handleCurrentAutomation(id);

        if (isActivated) {
            disableAutomationModalRef.current?.toggleModal(true);
        } else {
            enableAutomationModalRef.current?.toggleModal(true);
        }
    };

    const handleCurrentAutomation = (id: string) => {
        const automation = automations.find(
            (automation) => automation.id === id
        );
        if (automation !== currentAutomation) {
            // Only update if different
            setCurrentAutomation(automation);
        }
    };

    const handleRowClick = (row: Row<Automation>) => {
        handleShowDetailsSidebar(row.original.id);
    };

    const columnModel: ColumnDef<Automation>[] = useMemo(
        () => [
            {
                id: "position",
                header: () => (
                    <TooltipHeader
                        label="Position"
                        tooltipText="The automation position determines the order in which automations will be executed. If an order meets the conditions of two or more automations, the last one will override the previous ones."
                        tooltipPlacement="top"
                        center={true}
                    />
                ),
                cell: ({ row }) => (
                    <Column
                        label={row.original.order}
                        center={true}
                        addStyle={{
                            fontSize: "16px",
                            fontWeight: "600",
                            color: "#898989",
                        }}
                    />
                ),
            },
            {
                id: "name",
                header: () => (
                    <HeaderSortable label="Automation" resource="name" />
                ),
                cell: ({ row }) => (
                    <NameColumn
                        clientList={row.original.clientsList}
                        name={row.original.name}
                        id={row.original.id}
                        singleLine
                        onClientClick={() =>
                            handleShowClientsSidebar(row.original.id)
                        }
                    />
                ),
            },
            {
                id: "events",
                header: () => (
                    <TooltipHeader
                        label="Events"
                        tooltipText="When one of set events occurs, it can trigger an automation."
                        tooltipPlacement="top"
                    />
                ),
                cell: ({ row }) => <TooltipColumn list={row.original.events} />,
            },
            {
                id: "trigger",
                header: () => (
                    <TooltipHeader
                        label="Conditions"
                        tooltipText="Specific conditions set to determine if the automation should be executed."
                        tooltipPlacement="top"
                    />
                ),
                cell: ({ row }) => (
                    <TooltipColumn
                        list={(row.original.conditions ?? []).map(
                            (trigger) => trigger?.attributes?.title ?? ""
                        )}
                    />
                ),
            },
            {
                id: "actions",
                header: () => (
                    <TooltipHeader
                        label="Actions"
                        tooltipText="The tasks that the automation will perform when the corresponding events and conditions occur."
                        tooltipPlacement="top"
                    />
                ),
                cell: ({ row }) => (
                    <TooltipColumn
                        list={(row.original.actions ?? []).map(
                            (action) => action?.attributes?.title ?? ""
                        )}
                    />
                ),
            },
            {
                id: "lastTriggered",
                header: () => (
                    <TooltipHeader
                        label="Last triggered"
                        tooltipText="Indicates the most recent time the automation was run."
                        tooltipPlacement="top"
                    />
                ),
                cell: ({ row }) => <TooltipColumn list={[""]} />,
            },
            {
                id: "isEnabled",
                header: "",
                cell: ({ row }) => (
                    <div onClick={(event) => event.stopPropagation()}>
                        <ActionsColumn
                            isActivated={row.original.isEnabled}
                            id={row.original.id}
                            onStatusClick={onStatusClick}
                            onViewClick={handleShowDetailsSidebar}
                        />
                    </div>
                ),
            },
        ],
        []
    );

    const columns = useMemo(() => {
        const account = user?.accounts?.find(
            (account) => account?.id === user?.current_account_id
        ) as
            | {
                  isStandalone: boolean;
              }
            | undefined;

        return columnModel.filter(
            (column) =>
                !(
                    account?.isStandalone &&
                    (column.id === "appliesTo" || column.id === "client")
                )
        );
    }, [user?.current_account_id, columnModel]);

    useEffect(() => {
        if (automations.length > 0 && currentAutomation) {
            handleCurrentAutomation(currentAutomation.id);
        }
    }, [automations, currentAutomation]);

    automations.sort((a, b) => a.position - b.position);

    console.log("automations", automations);

    return (
        <>
            <AdvancedTable
                data={automations}
                columns={columns}
                page={page}
                onRowClick={handleRowClick}
            />
            <DetailsSidebar
                automation={currentAutomation as Automation}
                showClientsSidebar={() =>
                    handleShowClientsSidebar(currentAutomation?.id)
                }
                isActivated
                id={currentAutomation?.id || ""}
                ref={detailsSidebarRef}
            />
            <ClientsSidebar
                clients={[]}
                selectedClients={[]}
                setSelectedClients={() => {}}
                automation={currentAutomation as Automation}
                ref={clientsSidebarRef}
                isActivated
                id={currentAutomation?.id || ""}
            />
            <EnableAutomationModal
                automation={currentAutomation}
                ref={enableAutomationModalRef}
                isActivated
                id={currentAutomation?.id || ""}
            />
            <DisableAutomationModal
                automation={currentAutomation}
                ref={disableAutomationModalRef}
                isActivated
                id={currentAutomation?.id || ""}
            />
        </>
    );
};

export default AutomationsTable;
