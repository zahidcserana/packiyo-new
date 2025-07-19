export interface Resource {
    id: string;
    type: string;
}

export interface Data {
    data: Resource[];
}

export interface Relationship {
    applies_to_customers: Data;
    conditions: Data;
    actions: Data;
    customer: Data;
    contact_information?: Data;
}

export interface JsonApiData extends Resource {
    attributes: Record<string, string>;
    relationships: Relationship;
}

export interface AutomationResponse {
    data: JsonApiData[];
    included: JsonApiData[];
}

export interface Automation {
    id: string;
    name: string;
    isEnabled: boolean;
    position: number;
    order: string;
    events: string[];
    conditions?: JsonApiData[];
    actions?: JsonApiData[];
    appliesTo?: string;
    clients: any[];
    clientsList: string[];
    createdBy?: string;
    lastEditted?: string;
    lastEdittedBy?: string;
    lastTriggered?: string;
}

export interface Page {
    currentPage: string;
    from: string;
    lastPage: string;
    perPage: string | number;
    to: string | number;
    total: number;
}

export interface Status {
    isActivated: boolean;
    id: string;
}
