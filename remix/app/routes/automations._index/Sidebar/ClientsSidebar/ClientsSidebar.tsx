import React, { forwardRef, useEffect, useImperativeHandle, useRef, useState } from "react";
import Sidebar, { SidebarHandle } from "../../../../components/Sidebar/Sidebar";
import SearchField from "../../../../components/Field/SearchField";
import {Automation, Status} from "~/types/automations";
import ListItem from "~/components/Loop/ListItem";
import EditPencil from "~/components/Icon/EditPencil";
import {Button} from "react-bootstrap";
import {useNavigate} from "@remix-run/react";

interface ClientsSidebarProps extends Status {
    automation: Automation;
    clients: any;
    selectedClients: any;
    setSelectedClients: any;
    currentPage?: any;
    nextPage?: any;
    searchQuery?: any;
}

export interface ClientsSidebarRef {
    toggleSidebar: (value: boolean) => void;
}

const ClientsSidebar = forwardRef<ClientsSidebarRef | undefined, ClientsSidebarProps>(({automation, clients, selectedClients, setSelectedClients, currentPage, nextPage, searchQuery}, ref) => {
    const sidebarRef = useRef<SidebarHandle>(null);
    const [loadedClients, setLoadedClients] = useState<any>([]);
    const [keyword, setKeyword] = useState<string | undefined>();
    const navigate = useNavigate();

    useImperativeHandle(ref, () => ({
        toggleSidebar(value: any) {
            handleToggleSidebar(value);
        },
    }));

    const handleToggleSidebar = (value: any) => {
        sidebarRef.current?.toggleSidebar(value);
    };

    useEffect(() => {
        if (automation) {
            const usedClients = automation?.clients.map(client => client.id);
            setSelectedClients(usedClients);
        }
    }, [automation]);

    useEffect(() => {
        let filteredClients = automation?.clients ?? [];

        if (keyword) {
            filteredClients = (filteredClients.filter(function (filteredClient: any) {
                return filteredClient?.name?.toLowerCase().includes(keyword);
            }));
        }

        setLoadedClients([...filteredClients, ...clients]);
    }, [clients, keyword]);

    const navigateToAutomation = () => {
        navigate(`/automations/edit/${automation.id}?showClients=true`);
    }

    return (
        <Sidebar title="Clients" ref={sidebarRef} close={false} placement="end">
            {automation?.appliesTo !== 'all' && <SearchField value={keyword} placeholder={'Search for clients'} setData={setKeyword} action={null} />}

            <div>
                <Button variant="transparent" size="sm" onClick={navigateToAutomation}>
                    Edit <EditPencil/>
                </Button>
            </div>

            {automation?.appliesTo === 'all' ? 'Applies to All Clients' : loadedClients?.map((client: any) => (
                <ListItem key={client.id} value={client?.name} />
            ))}

        </Sidebar>
    );
});

export default ClientsSidebar;
