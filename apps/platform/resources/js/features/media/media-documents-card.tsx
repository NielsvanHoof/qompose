import { Link } from '@inertiajs/react';
import { FolderOpen } from 'lucide-react';
import UploadedDocumentController from '@/actions/App/Http/Controllers/Dossiers/UploadedDocumentController';
import EmptyState from '@/components/empty-state';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import type { MediaDocument } from '@/features/media/types';
import { useCurrentWorkspace } from '@/hooks/use-current-workspace';
import { useTranslation } from '@/hooks/use-translation';
import { formatBytes } from '@/lib/format-bytes';
import { show as showDossier } from '@/routes/workspaces/dossiers';

/**
 * Media library list of document requests across all dossiers.
 */
export default function MediaDocumentsCard({
    documents,
    canDownload,
}: {
    documents: MediaDocument[];
    canDownload: boolean;
}) {
    const currentWorkspace = useCurrentWorkspace();
    const { t } = useTranslation();

    return (
        <Card>
            <CardHeader>
                <CardTitle>{t('All documents')}</CardTitle>
                <CardDescription>
                    {documents.length === 1
                        ? t('1 document request')
                        : t(':count document requests', {
                              count: documents.length,
                          })}
                </CardDescription>
            </CardHeader>
            <CardContent>
                {documents.length === 0 ? (
                    <EmptyState
                        title={t(
                            'Document requests will appear here once you add them to a dossier.',
                        )}
                    />
                ) : (
                    <div className="divide-y rounded-md border">
                        {documents.map((document) => (
                            <div
                                key={document.id}
                                className="flex flex-wrap items-center justify-between gap-3 px-4 py-3"
                            >
                                <div className="min-w-0 flex-1">
                                    <p className="font-medium">
                                        {document.title}
                                    </p>
                                    <p className="text-sm text-muted-foreground">
                                        {document.client_name}
                                        {' · '}
                                        {document.dossier.title}
                                        {document.dossier.reference &&
                                            ` · ${document.dossier.reference}`}
                                    </p>
                                    {document.uploaded_document ? (
                                        <p className="mt-1 text-sm text-muted-foreground">
                                            {
                                                document.uploaded_document
                                                    .original_filename
                                            }
                                            {' · '}
                                            {formatBytes(
                                                document.uploaded_document
                                                    .size_bytes,
                                            )}
                                            {' · '}
                                            {new Date(
                                                document.uploaded_document
                                                    .uploaded_at,
                                            ).toLocaleString()}
                                        </p>
                                    ) : (
                                        <p className="mt-1 text-sm text-muted-foreground">
                                            {t('No file uploaded yet')}
                                        </p>
                                    )}
                                </div>

                                <div className="flex flex-wrap items-center gap-2">
                                    <Badge variant="secondary">
                                        {statusLabel(document.status, t)}
                                    </Badge>

                                    <Button variant="outline" size="sm" asChild>
                                        <Link
                                            href={showDossier({
                                                tenant: currentWorkspace,
                                                dossier: document.dossier.id,
                                            })}
                                        >
                                            <FolderOpen />
                                            {t('Dossier')}
                                        </Link>
                                    </Button>

                                    {document.uploaded_document &&
                                        canDownload && (
                                            <Button
                                                variant="outline"
                                                size="sm"
                                                asChild
                                            >
                                                <a
                                                    href={UploadedDocumentController.download.url(
                                                        {
                                                            tenant: currentWorkspace,
                                                            uploadedDocument:
                                                                document
                                                                    .uploaded_document
                                                                    .id,
                                                        },
                                                    )}
                                                >
                                                    {t('Download')}
                                                </a>
                                            </Button>
                                        )}
                                </div>
                            </div>
                        ))}
                    </div>
                )}
            </CardContent>
        </Card>
    );
}

/**
 * Human-readable labels for document request statuses shown in the media library.
 * Includes changes_requested for forward-compat even though it is not in the
 * current DocumentRequestStatus union.
 */
function statusLabel(
    status: string,
    t: (key: string, replacements?: Record<string, string | number>) => string,
): string {
    const labels: Record<string, string> = {
        pending: t('Pending'),
        submitted: t('Submitted'),
        accepted: t('Accepted'),
        rejected: t('Rejected'),
        changes_requested: t('Changes requested'),
    };

    return labels[status] ?? status;
}
