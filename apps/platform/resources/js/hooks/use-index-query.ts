import { router, usePage } from '@inertiajs/react';
import { useMemo } from 'react';

type QueryValue = string | number | undefined | null;

type FilterBag = Record<string, QueryValue>;

/**
 * Thin helper for Spatie query-builder index pages.
 *
 * Filters/sort come from Inertia props (set by the controller).
 * Visits use router.get so Inertia serializes nested filter bags as filter[q]=…
 * Page params are omitted so filter/sort always restart at page 1.
 */
export function useIndexQuery() {
    const page = usePage();
    const filters = useMemo(
        () => normalizeFilters(page.props.filters),
        [page.props.filters],
    );
    const sort = typeof page.props.sort === 'string' ? page.props.sort : null;

    // Pathname only — full query state is sent via `data`.
    const path = page.url.split('?')[0] ?? page.url;

    function visit(nextFilters: FilterBag, nextSort: string | null): void {
        const data: {
            filter?: Record<string, string>;
            sort?: string;
        } = {};

        const compacted = compactFilters(nextFilters);

        if (Object.keys(compacted).length > 0) {
            data.filter = compacted;
        }

        if (nextSort !== null && nextSort !== '') {
            data.sort = nextSort;
        }

        router.get(path, data, {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        });
    }

    function setFilters(next: FilterBag): void {
        visit(next, sort);
    }

    function setSort(value: string | null): void {
        visit(filters, value);
    }

    return {
        filters,
        sort,
        setFilters,
        setSort,
    };
}

/**
 * Coerce controller filter props into a flat string map.
 */
function normalizeFilters(input: unknown): Record<string, string> {
    if (input === null || input === undefined || typeof input !== 'object') {
        return {};
    }

    return compactFilters(input as FilterBag);
}

/**
 * Drop empty filter values so they are omitted from the query string.
 */
function compactFilters(input: FilterBag): Record<string, string> {
    const next: Record<string, string> = {};

    for (const [key, value] of Object.entries(input)) {
        if (value === undefined || value === null || value === '') {
            continue;
        }
        next[key] = String(value);
    }

    return next;
}
