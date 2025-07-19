import type { ActionFunctionArgs, LoaderFunctionArgs } from "@remix-run/node";
import { deleteData, fetchData, updateData } from "./auth.server";
import {
    automationMapper,
    automationsMapper,
    clientsMapper,
} from "../mappers/automations";
import { Automation, Page } from "~/types/automations";
import { PAGE_SIZE } from "~/.server/constants/app";

interface GetAutomations {
    request: LoaderFunctionArgs["request"];
    currentPage: string | null;
    sort: string;
}

export const getAutomations = async ({
    request,
    currentPage,
    sort,
}: GetAutomations): Promise<{ automations: Automation[]; page: Page }> => {
    const sortable = sort ? `sort=${sort}&` : "";
    const pagination = `&page[number]=${currentPage}&page[size]=${PAGE_SIZE}`;

    const response = await fetchData(
        request,
        `order-automations?${sortable}include=applies_to_customers.contact_information,conditions,actions,customer${pagination}`
    );

    return {
        automations: automationsMapper(response),
        page: response?.meta?.page,
    };
};

export const getAutomation = async ({
    request,
    id,
}): Promise<{ automation: Automation }> => {
    const response = await fetchData(
        request,
        `automations/${id}?include=applies_to_customers.contact_information,conditions,actions,customer`
    );

    return {
        automation: automationMapper(response),
    };
};

export const updateAutomationClients = async ({
    request,
    formData,
}: {
    request: ActionFunctionArgs["request"];
    formData: any;
}) => {
    const id = formData.get("id");
    const customers = JSON.parse(formData.get("customers"));
    const selectedCustomers = JSON.parse(formData.get("selected_customers"));
    const appliesAll = formData.get("applies_all");
    const name = formData.get("name");

    const updateAutomation = async () => {
        return await updateData(request, `automations/${id}`, {
            data: {
                type: "automations",
                id: id,
                attributes: {
                    applies_to: appliesAll ? "all" : "some",
                    name: name,
                },
            },
        });
    };

    const updateOrDeleteCustomers = async (
        url: string,
        customers: Array<{ id: string }>,
        method: "update" | "delete"
    ) => {
        if (customers.length > 0) {
            const payload = {
                data: customers.map((customer) => ({
                    type: "customers",
                    id: customer.id,
                })),
            };

            return method === "update"
                ? await updateData(request, url, payload)
                : await deleteData(request, url, payload);
        }
        return null;
    };

    await updateAutomation();

    if (selectedCustomers.length > 0) {
        return await updateOrDeleteCustomers(
            `automations/${id}/relationships/applies-to-customers`,
            selectedCustomers.map((id: string) => ({ id })),
            "update"
        );
    } else if (customers.length > 0) {
        return await updateOrDeleteCustomers(
            `automations/${id}/relationships/applies-to-customers`,
            customers,
            "delete"
        );
    }

    return { success: true, message: "Update successful" };
};

export const updateAutomationStatus = async ({
    request,
    formData,
}: {
    request: ActionFunctionArgs["request"];
    formData: any;
}) => {
    const id = formData.get("id");
    const isChecked = formData.get("is_checked");

    const payload = {
        data: {
            type: "automations",
            id: id,
            attributes: {
                is_enabled: JSON.parse(isChecked),
            },
        },
    };

    return await updateData(request, `automations/${id}`, payload);
};

export const updateAutomationPosition = async ({
    request,
    formData,
}: {
    request: ActionFunctionArgs["request"];
    formData: any;
}) => {
    const id = formData.get("id");

    const payload = {
        data: {
            type: "automations",
            id: id,
            attributes: {
                position: parseInt(formData.get("position")),
            },
        },
    };

    return await updateData(request, `automations/${id}`, payload, {});
};

export const getClients = async ({
    request,
    id,
    threeplId,
}): Promise<{
    clients: any[];
    nextPage: number | string;
    searchQuery: string;
    currentPage: number;
}> => {
    const url = new URL(request.url);

    const searchQuery = url.searchParams.get("search") || "";
    const page = Number(url.searchParams.get("page") || 1);

    const response = await fetchData(
        request,
        `/customers?filter[name]=${searchQuery}&filter[no-automation]=${id}&filter[parent-id]=${threeplId}&page[number]=${page}&page[size]=${PAGE_SIZE}`
    );

    return {
        clients: clientsMapper(response),
        currentPage: page,
        nextPage: response?.links?.next ? page + 1 : page,
        searchQuery: searchQuery,
    };
};
