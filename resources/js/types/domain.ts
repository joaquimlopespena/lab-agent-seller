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
