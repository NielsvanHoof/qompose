import { Link } from '@inertiajs/react';
import { ChevronLeftIcon, ChevronRightIcon } from 'lucide-react';
import { buttonVariants } from '@/components/ui/button';
import {
    Pagination,
    PaginationContent,
    PaginationEllipsis,
    PaginationItem,
} from '@/components/ui/pagination';
import { cn } from '@/lib/utils';
import type {
    Paginated,
    PaginationLink as PaginatorLink,
} from '@/types/pagination';

/**
 * Classic page links driven by a Laravel LengthAwarePaginator prop.
 *
 * Hrefs come from Laravel's withQueryString() links so filter/sort and
 * sibling page params (system_page / firm_page) stay intact — no client rebuild.
 */
export default function IndexPagination<T>({
    paginator,
}: {
    paginator: Paginated<T>;
}) {
    if (paginator.last_page <= 1) {
        return null;
    }

    const pageLinks = paginator.links.filter((link) =>
        isNumericLabel(link.label),
    );
    const prevUrl = paginator.prev_page_url;
    const nextUrl = paginator.next_page_url;
    const pages = visiblePages(paginator.current_page, paginator.last_page);
    const onFirst = paginator.current_page <= 1;
    const onLast = paginator.current_page >= paginator.last_page;

    return (
        <Pagination className="mt-4">
            <PaginationContent>
                <PaginationItem>
                    {prevUrl && !onFirst ? (
                        <Link
                            href={prevUrl}
                            preserveState
                            preserveScroll
                            aria-label="Go to previous page"
                            className={cn(
                                buttonVariants({
                                    variant: 'ghost',
                                    size: 'default',
                                }),
                                'gap-1 px-2.5 sm:pl-2.5',
                            )}
                        >
                            <ChevronLeftIcon />
                            <span className="hidden sm:block">Previous</span>
                        </Link>
                    ) : (
                        <span
                            aria-disabled
                            className={cn(
                                buttonVariants({
                                    variant: 'ghost',
                                    size: 'default',
                                }),
                                'pointer-events-none gap-1 px-2.5 opacity-50 sm:pl-2.5',
                            )}
                        >
                            <ChevronLeftIcon />
                            <span className="hidden sm:block">Previous</span>
                        </span>
                    )}
                </PaginationItem>

                {pages.map((page) =>
                    page === 'ellipsis-start' || page === 'ellipsis-end' ? (
                        <PaginationItem key={page}>
                            <PaginationEllipsis />
                        </PaginationItem>
                    ) : (
                        <PaginationItem key={page}>
                            <PageNumberLink
                                page={page}
                                url={urlForPage(pageLinks, page)}
                                isActive={page === paginator.current_page}
                            />
                        </PaginationItem>
                    ),
                )}

                <PaginationItem>
                    {nextUrl && !onLast ? (
                        <Link
                            href={nextUrl}
                            preserveState
                            preserveScroll
                            aria-label="Go to next page"
                            className={cn(
                                buttonVariants({
                                    variant: 'ghost',
                                    size: 'default',
                                }),
                                'gap-1 px-2.5 sm:pr-2.5',
                            )}
                        >
                            <span className="hidden sm:block">Next</span>
                            <ChevronRightIcon />
                        </Link>
                    ) : (
                        <span
                            aria-disabled
                            className={cn(
                                buttonVariants({
                                    variant: 'ghost',
                                    size: 'default',
                                }),
                                'pointer-events-none gap-1 px-2.5 opacity-50 sm:pr-2.5',
                            )}
                        >
                            <span className="hidden sm:block">Next</span>
                            <ChevronRightIcon />
                        </span>
                    )}
                </PaginationItem>
            </PaginationContent>
        </Pagination>
    );
}

/**
 * Single page number — Inertia Link styled as a pagination button.
 */
function PageNumberLink({
    page,
    url,
    isActive,
}: {
    page: number;
    url: string | null;
    isActive: boolean;
}) {
    const className = cn(
        buttonVariants({
            variant: isActive ? 'outline' : 'ghost',
            size: 'icon',
        }),
    );

    if (!url) {
        return (
            <span
                aria-current={isActive ? 'page' : undefined}
                className={className}
            >
                {page}
            </span>
        );
    }

    return (
        <Link
            href={url}
            preserveState
            preserveScroll
            aria-current={isActive ? 'page' : undefined}
            className={className}
        >
            {page}
        </Link>
    );
}

/**
 * Laravel encodes page numbers as plain labels ("1", "2", …).
 */
function isNumericLabel(label: string): boolean {
    return /^\d+$/.test(label.trim());
}

/**
 * Find the withQueryString URL for a given page number.
 */
function urlForPage(pageLinks: PaginatorLink[], page: number): string | null {
    const match = pageLinks.find((link) => Number(link.label.trim()) === page);

    return match?.url ?? null;
}

/**
 * Sliding window of page numbers with ellipsis markers.
 * Distinct ellipsis tokens give stable React keys (no array index).
 */
function visiblePages(
    current: number,
    last: number,
): Array<number | 'ellipsis-start' | 'ellipsis-end'> {
    if (last <= 7) {
        return Array.from({ length: last }, (_, i) => i + 1);
    }

    const pages: Array<number | 'ellipsis-start' | 'ellipsis-end'> = [1];

    if (current > 3) {
        pages.push('ellipsis-start');
    }

    const start = Math.max(2, current - 1);
    const end = Math.min(last - 1, current + 1);

    for (let page = start; page <= end; page++) {
        pages.push(page);
    }

    if (current < last - 2) {
        pages.push('ellipsis-end');
    }

    pages.push(last);

    return pages;
}
