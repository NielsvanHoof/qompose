/**
 * Laravel LengthAwarePaginator shape as serialized to Inertia (flat toArray()).
 */
export type PaginationLink = {
    url: string | null;
    label: string;
    active: boolean;
};

export type Paginated<T> = {
    data: T[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    from: number | null;
    to: number | null;
    path: string;
    first_page_url: string | null;
    last_page_url: string | null;
    next_page_url: string | null;
    prev_page_url: string | null;
    links: PaginationLink[];
};

export type IndexQueryFilterOption = {
    value: string;
    label: string;
};

export type IndexQueryFilter = {
    key: string;
    type: 'search' | 'select';
    label: string;
    options?: IndexQueryFilterOption[];
};

export type IndexQuerySort = {
    key: string;
    label: string;
};

export type IndexQueryConfig = {
    filters: IndexQueryFilter[];
    sorts: IndexQuerySort[];
    defaults: {
        sort: string;
        per_page: number;
    };
};
