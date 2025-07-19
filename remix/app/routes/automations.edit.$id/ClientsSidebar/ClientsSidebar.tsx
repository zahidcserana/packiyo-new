import React, {
    forwardRef,
    useEffect,
    useImperativeHandle,
    useRef,
    useState,
} from "react";
import { useSubmit } from "@remix-run/react";
import { isItemChecked } from "~/utilities/app";
import { Automation, Status } from "~/types/automations";
import Sidebar, { SidebarHandle } from "~/components/Sidebar/Sidebar";
import SearchField from "~/components/Field/SearchField";
import ListCheckboxItem from "~/components/Loop/ListCheckboxItem";

interface ClientsSidebarProps extends Status {
    automation: Automation;
    clients: any;
    selectedClients: any;
    setSelectedClients: any;
    currentPage: any;
    nextPage: any;
    searchQuery: any;
}

export interface ClientsSidebarRef {
    toggleSidebar: (value: boolean) => void;
}

const ClientsSidebar = forwardRef<
    ClientsSidebarRef | undefined,
    ClientsSidebarProps
>(
    (
        {
            automation,
            clients,
            selectedClients,
            setSelectedClients,
            currentPage,
            nextPage,
            searchQuery,
        },
        ref
    ) => {
        const sidebarRef = useRef<SidebarHandle>(null);
        const [selectAll, setSelectAll] = useState<boolean | undefined>();
        const [loadedClients, setLoadedClients] = useState<any>([]);
        const [isLoading, setIsLoading] = useState(false);
        const [keyword, setKeyword] = useState<string | undefined>();
        const submit = useSubmit();

        useImperativeHandle(ref, () => ({
            toggleSidebar(value: any) {
                handleToggleSidebar(value);
            },
        }));

        const handleToggleSidebar = (value: any) => {
            sidebarRef.current?.toggleSidebar(value);
        };

        const handleCheckboxClick = (id: string) => {
            let usedClients = selectedClients;

            if (selectedClients.includes(id)) {
                usedClients = selectedClients.filter(
                    (selectedClient: any) => selectedClient !== id
                );
            } else {
                usedClients = [...usedClients, id];
            }

            setSelectedClients(usedClients);
        };

        useEffect(() => {
            if (selectAll) {
                const usedClients = selectAll
                    ? loadedClients?.map((client: any) => client.id)
                    : [];
                setSelectedClients(usedClients);
            } else if (selectAll === false) {
                setSelectedClients([]);
            }
        }, [selectAll]);

        const action = (nextPage: number) => {
            setIsLoading(true);
            const formData = new FormData();
            formData.append("search", String(keyword ?? ""));
            formData.append("showClients", "true");
            formData.append("page", String(nextPage));
            submit(formData, { method: "get" });
        };

        const handleScroll = () => {
            const sidebarBody = sidebarRef.current?.getBody();

            if (sidebarBody) {
                const { scrollTop, scrollHeight, clientHeight } = sidebarBody;
                if (scrollTop + clientHeight >= scrollHeight - 10) {
                    if (!isLoading && currentPage !== nextPage) {
                        action(nextPage);
                    }
                }
            }
        };

        const checkContentHeight = () => {
            const sidebarBody = sidebarRef.current?.getBody();

            if (sidebarBody) {
                const { scrollHeight, clientHeight } = sidebarBody;

                if (
                    scrollHeight <= clientHeight &&
                    !isLoading &&
                    currentPage !== nextPage
                ) {
                    action(nextPage);
                }
            }
        };

        useEffect(() => {
            const sidebarBody = sidebarRef.current?.getBody();

            setTimeout(() => {
                if (sidebarBody) {
                    sidebarBody.addEventListener("scroll", handleScroll);
                }

                checkContentHeight();
            }, 100);

            return () => {
                if (sidebarBody) {
                    sidebarBody.removeEventListener("scroll", handleScroll);
                }
            };
        }, [loadedClients, currentPage, nextPage]);

        useEffect(() => {
            if (clients && currentPage > 1) {
                setLoadedClients((prevClients: any) => [
                    ...prevClients,
                    ...clients,
                ]);
            } else {
                let filteredClients = automation?.clients ?? [];

                if (keyword) {
                    filteredClients = filteredClients.filter(function (
                        filteredClient: any
                    ) {
                        return filteredClient?.name
                            ?.toLowerCase()
                            .includes(keyword);
                    });
                }

                setLoadedClients([...filteredClients, ...clients]);
            }

            setIsLoading(false);
        }, [clients]);

        return (
            <Sidebar
                title="Clients"
                ref={sidebarRef}
                close={false}
                placement="end"
            >
                <SearchField
                    value={keyword}
                    placeholder={"Search for clients"}
                    setData={setKeyword}
                    action={() => action(1)}
                />

                <ListCheckboxItem
                    title="Select all"
                    isChecked={selectAll}
                    action={() => setSelectAll(!selectAll)}
                />

                {loadedClients?.map((client: any) => (
                    <ListCheckboxItem
                        key={client.id}
                        name="clients"
                        title={client.name}
                        isChecked={isItemChecked(client.id, selectedClients)}
                        action={() => handleCheckboxClick(client.id)}
                    />
                ))}
            </Sidebar>
        );
    }
);

export default ClientsSidebar;
