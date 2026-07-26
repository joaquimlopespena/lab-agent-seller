export type DomainId = number | string;
export type IsoDateTime = string;

export type CompanySummary = {
    id: number;
    name: string;
};

export type StatusSummary = {
    id: number;
    name: string;
    slug: string;
};

export type PaginationLink = {
    active: boolean;
    label: string;
    url: string | null;
};

export type PaginationMeta = {
    current_page: number;
    from: number | null;
    last_page: number;
    path: string;
    per_page: number;
    to: number | null;
    total: number;
};

export type PaginatedData<T> = {
    data: T[];
    links: PaginationLink[];
    meta: PaginationMeta;
};

export type CrmSyncRunSummary = {
    id: number;
    status: StatusSummary;
    started_at: IsoDateTime | null;
    finished_at: IsoDateTime | null;
    created_at: IsoDateTime;
};

export type DashboardSummary = {
    crm_connection: {
        id: number;
        provider: string;
        authorized_at: IsoDateTime | null;
        status: StatusSummary;
    } | null;
    sync_runs_count: number;
    synced_records_count: number;
    customers_count: number;
    conversations_count: number;
};

export type PipedriveConnectionSummary = {
    id: number;
    provider: string;
    status: StatusSummary;
    authorized_at: IsoDateTime | null;
    token_expires_at: IsoDateTime | null;
    last_error: string | null;
};

export type ConversationSummary = {
    id: string;
    customer_name: string;
    customer_email: string;
    status: StatusSummary;
    title: string;
    created_at: IsoDateTime;
    updated_at: IsoDateTime;
};

export type ConversationMessageRole = 'user' | 'assistant' | 'system';

export type ConversationMessage = {
    id: string;
    role: ConversationMessageRole;
    content: string;
    created_at: IsoDateTime;
};
