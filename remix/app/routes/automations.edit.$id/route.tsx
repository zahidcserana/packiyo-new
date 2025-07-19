import { useLoaderData, useSearchParams } from "@remix-run/react";
import Breadcrumbs from "../../components/Layout/Breadcrumbs/Breadcrumbs";

import { ActionFunction, LoaderFunctionArgs } from "@remix-run/node";
import { requireAuth, currentAccountId } from "~/.server/services/auth.server";
import {
    getAutomation,
    getClients,
    updateAutomationClients,
} from "~/.server/services/automations";
import React, { useEffect, useRef, useState } from "react";
import EditForm from "./Form/EditForm";
import { Automation } from "~/types/automations";
import { getSession } from "~/.server/sessions/sessions";
import ClientsSidebar, {
    ClientsSidebarRef,
} from "./ClientsSidebar/ClientsSidebar";

export const action: ActionFunction = async ({ request }) => {
    const formData = await request.formData();

    switch (formData.get("action")) {
        case "update-automation-clients": {
            return updateAutomationClients({ request, formData });
        }
        default: {
            throw new Error("Unknown action");
        }
    }
};

export const loader = async ({ request, params }: LoaderFunctionArgs) => {
    await requireAuth({ request });

    const threeplId = currentAccountId({ request });

    const id = params.id;

    const { clients, currentPage, nextPage, searchQuery } = await getClients({
        request,
        id,
        threeplId,
    });
    const { automation } = await getAutomation({ request, id });

    const session = await getSession(request.headers.get("Cookie"));
    const token = session.get("packiyoAppToken" as any);

    return {
        breadcrumbs: [
            { label: "Co-pilot Automations", to: "/automations" },
            { label: "Edit", to: "" },
        ],
        clients,
        automation,
        currentPage,
        nextPage,
        searchQuery,
    };
};

const AutomationEdit = () => {
    const {
        breadcrumbs,
        clients,
        automation,
        currentPage,
        nextPage,
        searchQuery,
    } = useLoaderData<typeof loader>();

    const clientList =
        automation?.clientsList?.[0] === "All" ? [] : automation?.clientsList;
    const [selectedClients, setSelectedClients] = useState<any>(
        clientList || []
    );
    const clientsSidebarRef = useRef<ClientsSidebarRef>(null);

    const handleShowClientsSidebar = () => {
        clientsSidebarRef.current?.toggleSidebar(true);
    };

    const [searchParams] = useSearchParams();
    const [showClients, setShowClients] = useState(
        !!searchParams.get("showClients")
    );

    useEffect(() => {
        if (showClients && automation.appliesTo !== "all") {
            handleShowClientsSidebar();
        }
    }, [showClients]);

    return (
        <>
            <Breadcrumbs breadcrumbs={breadcrumbs} large={true} />
            <EditForm
                automation={automation as Automation}
                selectedClients={selectedClients}
                sidebarAction={handleShowClientsSidebar}
                clients={clients}
            />
            <ClientsSidebar
                clients={clients}
                selectedClients={selectedClients}
                setSelectedClients={setSelectedClients}
                currentPage={currentPage}
                nextPage={nextPage}
                searchQuery={searchQuery}
                automation={automation as Automation}
                ref={clientsSidebarRef}
                isActivated
                id={""}
            />
        </>
    );
};

export default AutomationEdit;
