import { Link } from '@inertiajs/react';
import { FolderOpen } from 'lucide-react';
import UploadedDocumentController from '@/actions/App/Http/Controllers/Workspace/UploadedDocumentController';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { formatBytes } from '@/lib/format-bytes';
import { show as showDossier } from '@/routes/workspaces/dossiers';
import type { MediaDocument } from '@/types';

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
    return (
        <Card>
            <CardHeader>
                <CardTitle>All documents</CardTitle>
                <CardDescription>
                    {documents.length === 1
                        ? '1 document request'
                        : `${documents.length} document requests`}
                </CardDescription>
            </CardHeader>
            <CardContent>
                {documents.length === 0 ? (
                    <p className="text-sm text-muted-foreground">
                        Document requests will appear here once you add them to
                        a dossier.
                    </p>
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
                                            No file uploaded yet
                                        </p>
                                    )}
                                </div>

                                <div className="flex flex-wrap items-center gap-2">
                                    <Badge variant="secondary">
                                        {document.status.replaceAll('_', ' ')}
                                    </Badge>

                                    <Button variant="outline" size="sm" asChild>
                                        <Link
                                            href={showDossier(
                                                document.dossier.id,
                                            )}
                                        >
                                            <FolderOpen />
                                            Dossier
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
                                                        document
                                                            .uploaded_document
                                                            .id,
                                                    )}
                                                >
                                                    Download
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
