import { Head, Link } from '@inertiajs/react';
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
import { show as showDossier } from '@/routes/workspaces/dossiers';
import { index as mediaIndex } from '@/routes/workspaces/media';

type UploadedDocument = {
    id: number;
    original_filename: string;
    size_bytes: number;
    uploaded_at: string;
};

type MediaDocument = {
    id: number;
    title: string;
    status: string;
    updated_at: string | null;
    client_name: string;
    dossier: {
        id: number;
        title: string;
        reference: string | null;
    };
    uploaded_document: UploadedDocument | null;
};

function formatBytes(bytes: number): string {
    if (bytes < 1024) {
        return `${bytes} B`;
    }

    if (bytes < 1024 * 1024) {
        return `${(bytes / 1024).toFixed(1)} KB`;
    }

    return `${(bytes / (1024 * 1024)).toFixed(1)} MB`;
}

export default function MediaLibraryIndex({
    documents,
    can_download: canDownload,
}: {
    documents: MediaDocument[];
    can_download: boolean;
}) {
    return (
        <>
            <Head title="Media Library" />

            <div className="mx-auto flex w-full max-w-5xl flex-col gap-6 p-4 md:p-8">
                <div>
                    <h1 className="text-2xl font-semibold tracking-tight">
                        Media Library
                    </h1>
                    <p className="mt-1 text-sm text-muted-foreground">
                        All document requests across dossiers, including those
                        still waiting for a file.
                    </p>
                </div>

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
                                Document requests will appear here once you add
                                them to a dossier.
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
                                                        document
                                                            .uploaded_document
                                                            .original_filename
                                                    }
                                                    {' · '}
                                                    {formatBytes(
                                                        document
                                                            .uploaded_document
                                                            .size_bytes,
                                                    )}
                                                    {' · '}
                                                    {new Date(
                                                        document.uploaded_document.uploaded_at,
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
                                                {document.status.replaceAll(
                                                    '_',
                                                    ' ',
                                                )}
                                            </Badge>

                                            <Button
                                                variant="outline"
                                                size="sm"
                                                asChild
                                            >
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
            </div>
        </>
    );
}

MediaLibraryIndex.layout = {
    breadcrumbs: [
        {
            title: 'Media Library',
            href: mediaIndex(),
        },
    ],
};
