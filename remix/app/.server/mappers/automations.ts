import { Automation, AutomationResponse, JsonApiData, Resource } from "~/types/automations";

const mapResources = (references: Resource[], includes: JsonApiData[]): JsonApiData[] =>
    references.map(({ type, id }) => includes.find((include: JsonApiData) => include.type === type && include.id === id)!);

const mapClients = (customers: JsonApiData[], included: JsonApiData[]) =>
    customers.map((customer) => {
        const contactInformationId = customer.relationships?.contact_information?.data?.id;
        const contactInformation = included.find(
            (item) => item.type === "contact-informations" && item.id === contactInformationId
        );

        return {
            id: customer.id,
            name: contactInformation?.attributes?.name ?? "Untitled",
        };
    });

const mapAutomation = (automation: JsonApiData, included: JsonApiData[]): Automation => {
    const conditions = mapResources(automation.relationships["conditions"]?.data ?? [], included);
    const actions = mapResources(automation.relationships["actions"]?.data ?? [], included);
    const customers = mapResources(automation.relationships["applies_to_customers"]?.data ?? [], included);

    const clients = mapClients(customers, included);
    const appliesTo = automation.attributes.applies_to;
    const clientsList = appliesTo === "all" ? ["All"] : clients.map((client) => client.name);

    return {
        id: automation.id,
        name: automation.attributes.name,
        isEnabled: !!automation.attributes.is_enabled,
        position: Number(automation.attributes.position),
        order: automation.attributes.order,
        events: [...automation.attributes.target_events],
        conditions,
        actions,
        appliesTo,
        clients,
        clientsList
    };
};

export const automationsMapper = (responseToMap: AutomationResponse): Automation[] => {
    const data = responseToMap.data ?? [];
    const included = responseToMap.included ?? [];

    return data.map((automation: JsonApiData) => mapAutomation(automation, included));
};

export const automationMapper = (responseToMap: AutomationResponse): Automation => {
    const automation = responseToMap.data!;
    const included = responseToMap.included ?? [];

    return mapAutomation(automation, included);
};

export const clientsMapper = (response: Record<string, unknown>): { id: string; name: string }[] => {
    const customers = response?.data?.filter((item: JsonApiData) => item.type === "customers") ?? [];
    const included = response?.included ?? [];

    return mapClients(customers, included);
};
