import { Head, setLayoutProps } from '@inertiajs/react';
import Heading from '@/components/heading';
import IndexPagination from '@/components/index-query/index-pagination';
import IndexQueryToolbar from '@/components/index-query/index-query-toolbar';
import MediaDocumentsCard from '@/features/media/media-documents-card';
import type { MediaDocument } from '@/features/media/types';
import { useCurrentWorkspace } from '@/hooks/use-current-workspace';
import { useTranslation } from '@/hooks/use-translation';
import { index as mediaIndex } from '@/routes/workspaces/media';
import type { IndexQueryConfig, Paginated } from '@/types/pagination';

/**
 * Media library index — all document requests across dossiers.
 */
export default function MediaLibraryIndex({
    documents,
    can_download: canDownload,
    indexQuery,
}: {
    documents: Paginated<MediaDocument>;
    can_download: boolean;
    indexQuery: IndexQueryConfig;
    /** Current Spatie filter bag — consumed by useIndexQuery via usePage(). */
    filters?: Record<string, string>;
    sort?: string | null;
}) {
    const currentWorkspace = useCurrentWorkspace();
    const { t } = useTranslation();

    setLayoutProps({
        breadcrumbs: [
            {
                title: t('Media Library'),
                href: mediaIndex(currentWorkspace),
            },
        ],
    });

    return (
        <>
            <Head title={t('Media Library')} />

            <div className="mx-auto flex w-full max-w-5xl flex-col gap-6 p-4 md:p-8">
                <Heading
                    level={1}
                    className="mb-0"
                    title={t('Media Library')}
                    description={t(
                        'All document requests across dossiers, including those still waiting for a file.',
                    )}
                />

                <IndexQueryToolbar config={indexQuery} />
                <MediaDocumentsCard
                    documents={documents}
                    canDownload={canDownload}
                />
                <IndexPagination paginator={documents} />
            </div>
        </>
    );
}
