import { useEffect, useEffectEvent, useState } from 'react';
import { useDebounceCallback } from 'usehooks-ts';
import { Input } from '@/components/ui/input';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { useIndexQuery } from '@/hooks/use-index-query';
import { useTranslation } from '@/hooks/use-translation';
import type { IndexQueryConfig } from '@/types/pagination';

const ALL_VALUE = '__all__';

/**
 * Generic filter + sort toolbar driven by controller-provided indexQuery config.
 */
export default function IndexQueryToolbar({
    config,
}: {
    config: IndexQueryConfig;
}) {
    const { t } = useTranslation();
    const { filters, sort, setFilters, setSort } = useIndexQuery();

    // Local draft for search inputs so typing doesn't fire a visit on every keystroke.
    const [drafts, setDrafts] = useState<Record<string, string>>(() => {
        const initial: Record<string, string> = {};
        for (const filter of config.filters) {
            if (filter.type === 'search') {
                initial[filter.key] = filters[filter.key] ?? '';
            }
        }
        return initial;
    });

    // Keep drafts in sync when the URL changes (back/forward, clear).
    useEffect(() => {
        setDrafts((prev) => {
            const next = { ...prev };
            let changed = false;

            for (const filter of config.filters) {
                if (filter.type === 'search') {
                    const value = filters[filter.key] ?? '';

                    if (next[filter.key] !== value) {
                        next[filter.key] = value;
                        changed = true;
                    }
                }
            }

            return changed ? next : prev;
        });
    }, [filters, config.filters]);

    // Always read latest filters/config when the debounced callback runs.
    const applySearchDrafts = useEffectEvent(
        (nextDrafts: Record<string, string>) => {
            const nextFilters: Record<string, string> = { ...filters };
            let changed = false;

            for (const filter of config.filters) {
                if (filter.type !== 'search') {
                    continue;
                }
                const draft = nextDrafts[filter.key] ?? '';
                const current = filters[filter.key] ?? '';
                if (draft !== current) {
                    if (draft === '') {
                        delete nextFilters[filter.key];
                    } else {
                        nextFilters[filter.key] = draft;
                    }
                    changed = true;
                }
            }

            if (changed) {
                setFilters(nextFilters);
            }
        },
    );

    const debouncedApplySearchDrafts = useDebounceCallback(
        (nextDrafts: Record<string, string>) => {
            applySearchDrafts(nextDrafts);
        },
        300,
    );

    function handleSearchChange(key: string, value: string): void {
        setDrafts((prev) => {
            const nextDrafts = { ...prev, [key]: value };
            debouncedApplySearchDrafts(nextDrafts);
            return nextDrafts;
        });
    }

    function handleSelectChange(key: string, value: string): void {
        const nextFilters: Record<string, string> = { ...filters };
        if (value === ALL_VALUE) {
            delete nextFilters[key];
        } else {
            nextFilters[key] = value;
        }
        setFilters(nextFilters);
    }

    const activeSort = sort ?? config.defaults.sort;

    return (
        <div className="flex flex-wrap items-end gap-3">
            {config.filters.map((filter) => {
                if (filter.type === 'search') {
                    return (
                        <div
                            key={filter.key}
                            className="flex min-w-48 flex-1 flex-col gap-1.5"
                        >
                            <label
                                htmlFor={`index-filter-${filter.key}`}
                                className="text-xs font-medium text-muted-foreground"
                            >
                                {t(filter.label)}
                            </label>
                            <Input
                                id={`index-filter-${filter.key}`}
                                type="search"
                                value={drafts[filter.key] ?? ''}
                                placeholder={t(filter.label)}
                                onChange={(event) =>
                                    handleSearchChange(
                                        filter.key,
                                        event.target.value,
                                    )
                                }
                            />
                        </div>
                    );
                }

                return (
                    <div
                        key={filter.key}
                        className="flex min-w-40 flex-col gap-1.5"
                    >
                        <span className="text-xs font-medium text-muted-foreground">
                            {t(filter.label)}
                        </span>
                        <Select
                            value={filters[filter.key] ?? ALL_VALUE}
                            onValueChange={(value) =>
                                handleSelectChange(filter.key, value)
                            }
                        >
                            <SelectTrigger className="w-full min-w-40">
                                <SelectValue placeholder={t(filter.label)} />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value={ALL_VALUE}>
                                    {t('All')}
                                </SelectItem>
                                {(filter.options ?? []).map((option) => (
                                    <SelectItem
                                        key={option.value}
                                        value={option.value}
                                    >
                                        {t(option.label)}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                    </div>
                );
            })}

            {config.sorts.length > 0 && (
                <div className="flex min-w-44 flex-col gap-1.5">
                    <span className="text-xs font-medium text-muted-foreground">
                        {t('Sort')}
                    </span>
                    <Select
                        value={activeSort}
                        onValueChange={(value) => setSort(value)}
                    >
                        <SelectTrigger className="w-full min-w-44">
                            <SelectValue placeholder={t('Sort')} />
                        </SelectTrigger>
                        <SelectContent>
                            {config.sorts.map((option) => (
                                <SelectItem key={option.key} value={option.key}>
                                    {t(option.label)}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                </div>
            )}
        </div>
    );
}
