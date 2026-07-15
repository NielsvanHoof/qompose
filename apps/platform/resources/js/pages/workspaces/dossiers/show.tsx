import { Form, Head, Link, router, useForm } from '@inertiajs/react';
import { useState, type FormEvent } from 'react';
import ClientAccessGrantController from '@/actions/App/Http/Controllers/Workspace/ClientAccessGrantController';
import DocumentRequestController from '@/actions/App/Http/Controllers/Workspace/DocumentRequestController';
import UploadedDocumentController from '@/actions/App/Http/Controllers/Workspace/UploadedDocumentController';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { index as dossierIndex } from '@/routes/workspaces/dossiers';

type UploadedDocument = {
    id: number;
    original_filename: string;
    size_bytes: number;
    uploaded_at: string;
};

type DocumentRequest = {
    id: number;
    title: string;
    instructions: string | null;
    status: string;
    uploaded_document: UploadedDocument | null;
};

type AccessGrant = {
    id: number;
    expires_at: string;
    revoked_at: string | null;
    last_used_at: string | null;
    is_valid: boolean;
};

type Dossier = {
    id: number;
    title: string;
    reference: string | null;
    status: string;
    client: {
        name: string;
        email: string;
    };
    document_requests: DocumentRequest[];
    access_grants: AccessGrant[];
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

function DocumentRequestUpload({
    dossierId,
    documentRequest,
}: {
    dossierId: number;
    documentRequest: DocumentRequest;
}) {
    const form = useForm<{ document: File | null }>({
        document: null,
    });

    const submit = (event: FormEvent) => {
        event.preventDefault();

        form.post(
            UploadedDocumentController.store.url({
                dossier: dossierId,
                documentRequest: documentRequest.id,
            }),
            {
                forceFormData: true,
                preserveScroll: true,
                onSuccess: () => form.reset('document'),
            },
        );
    };

    return (
        <form onSubmit={submit} className="mt-3 space-y-2">
            <div className="flex flex-wrap items-end gap-2">
                <div className="grid min-w-48 flex-1 gap-1">
                    <Label htmlFor={`document-${documentRequest.id}`}>
                        {documentRequest.uploaded_document
                            ? 'Replace file'
                            : 'Upload file'}
                    </Label>
                    <Input
                        id={`document-${documentRequest.id}`}
                        type="file"
                        accept=".pdf,.jpg,.jpeg,.png,.webp,.doc,.docx,.xls,.xlsx"
                        onChange={(event) =>
                            form.setData(
                                'document',
                                event.target.files?.[0] ?? null,
                            )
                        }
                    />
                </div>
                <Button
                    type="submit"
                    size="sm"
                    disabled={form.processing || !form.data.document}
                >
                    {form.processing ? 'Uploading…' : 'Upload'}
                </Button>
            </div>
            {form.progress && (
                <progress
                    value={form.progress.percentage}
                    max={100}
                    className="h-1.5 w-full"
                />
            )}
            <InputError message={form.errors.document} />
        </form>
    );
}

export default function ShowDossier({
    dossier,
    access_grant_token: accessGrantToken = null,
}: {
    dossier: Dossier;
    access_grant_token?: string | null;
}) {
    const [copied, setCopied] = useState(false);

    const copyToken = async () => {
        if (!accessGrantToken) {
            return;
        }

        await navigator.clipboard.writeText(accessGrantToken);
        setCopied(true);
    };

    return (
        <>
            <Head title={dossier.title} />

            <div className="mx-auto flex w-full max-w-5xl flex-col gap-6 p-4 md:p-8">
                <div className="flex flex-wrap items-start justify-between gap-4">
                    <div>
                        <Link
                            href={dossierIndex()}
                            className="text-sm text-muted-foreground hover:text-foreground"
                        >
                            Dossiers
                        </Link>
                        <Heading
                            title={dossier.title}
                            description={`${dossier.client.name} · ${dossier.client.email}`}
                        />
                    </div>
                    <Badge variant="secondary">
                        {dossier.status.replaceAll('_', ' ')}
                    </Badge>
                </div>

                {accessGrantToken && (
                    <Card className="border-primary/40">
                        <CardHeader>
                            <CardTitle>New client access token</CardTitle>
                            <CardDescription>
                                Copy this token now. It is shown once and cannot
                                be retrieved again.
                            </CardDescription>
                        </CardHeader>
                        <CardContent className="flex flex-wrap items-center gap-3">
                            <code className="bg-muted max-w-full overflow-x-auto rounded-md px-3 py-2 text-sm">
                                {accessGrantToken}
                            </code>
                            <Button
                                type="button"
                                variant="outline"
                                onClick={copyToken}
                            >
                                {copied ? 'Copied' : 'Copy token'}
                            </Button>
                        </CardContent>
                    </Card>
                )}

                <div className="grid gap-6 lg:grid-cols-[1fr_20rem]">
                    <div className="space-y-6">
                        <Card>
                            <CardHeader>
                                <CardTitle>Document requests</CardTitle>
                                <CardDescription>
                                    Upload files received from the client, or
                                    wait for portal uploads.
                                </CardDescription>
                            </CardHeader>
                            <CardContent>
                                {dossier.document_requests.length === 0 ? (
                                    <p className="text-sm text-muted-foreground">
                                        No documents requested yet.
                                    </p>
                                ) : (
                                    <div className="divide-y rounded-md border">
                                        {dossier.document_requests.map(
                                            (documentRequest) => (
                                                <div
                                                    key={documentRequest.id}
                                                    className="space-y-2 px-4 py-3"
                                                >
                                                    <div className="flex flex-wrap items-start justify-between gap-3">
                                                        <div>
                                                            <p className="font-medium">
                                                                {
                                                                    documentRequest.title
                                                                }
                                                            </p>
                                                            {documentRequest.instructions && (
                                                                <p className="mt-1 text-sm text-muted-foreground">
                                                                    {
                                                                        documentRequest.instructions
                                                                    }
                                                                </p>
                                                            )}
                                                        </div>
                                                        <Badge variant="outline">
                                                            {documentRequest.status.replaceAll(
                                                                '_',
                                                                ' ',
                                                            )}
                                                        </Badge>
                                                    </div>

                                                    {documentRequest.uploaded_document && (
                                                        <div className="flex flex-wrap items-center justify-between gap-2 rounded-md bg-muted/50 px-3 py-2 text-sm">
                                                            <div>
                                                                <p className="font-medium">
                                                                    {
                                                                        documentRequest
                                                                            .uploaded_document
                                                                            .original_filename
                                                                    }
                                                                </p>
                                                                <p className="text-muted-foreground">
                                                                    {formatBytes(
                                                                        documentRequest
                                                                            .uploaded_document
                                                                            .size_bytes,
                                                                    )}{' '}
                                                                    ·{' '}
                                                                    {new Date(
                                                                        documentRequest.uploaded_document.uploaded_at,
                                                                    ).toLocaleString()}
                                                                </p>
                                                            </div>
                                                            <Button
                                                                variant="outline"
                                                                size="sm"
                                                                asChild
                                                            >
                                                                <a
                                                                    href={UploadedDocumentController.download.url(
                                                                        documentRequest
                                                                            .uploaded_document
                                                                            .id,
                                                                    )}
                                                                >
                                                                    Download
                                                                </a>
                                                            </Button>
                                                        </div>
                                                    )}

                                                    <DocumentRequestUpload
                                                        dossierId={dossier.id}
                                                        documentRequest={
                                                            documentRequest
                                                        }
                                                    />
                                                </div>
                                            ),
                                        )}
                                    </div>
                                )}
                            </CardContent>
                        </Card>

                        <Card>
                            <CardHeader>
                                <CardTitle>Client access grants</CardTitle>
                                <CardDescription>
                                    Issue a temporary token so the client can
                                    open this dossier in the portal.
                                </CardDescription>
                            </CardHeader>
                            <CardContent className="space-y-4">
                                {dossier.access_grants.length === 0 ? (
                                    <p className="text-sm text-muted-foreground">
                                        No access grants yet.
                                    </p>
                                ) : (
                                    <div className="divide-y rounded-md border">
                                        {dossier.access_grants.map((grant) => (
                                            <div
                                                key={grant.id}
                                                className="flex flex-wrap items-center justify-between gap-3 px-4 py-3"
                                            >
                                                <div className="text-sm">
                                                    <p>
                                                        Expires{' '}
                                                        {new Date(
                                                            grant.expires_at,
                                                        ).toLocaleString()}
                                                    </p>
                                                    <p className="text-muted-foreground">
                                                        {grant.is_valid
                                                            ? 'Active'
                                                            : grant.revoked_at
                                                              ? 'Revoked'
                                                              : 'Expired'}
                                                    </p>
                                                </div>
                                                {grant.is_valid && (
                                                    <Button
                                                        type="button"
                                                        variant="outline"
                                                        size="sm"
                                                        onClick={() =>
                                                            router.delete(
                                                                ClientAccessGrantController.destroy.url(
                                                                    grant.id,
                                                                ),
                                                                {
                                                                    preserveScroll: true,
                                                                },
                                                            )
                                                        }
                                                    >
                                                        Revoke
                                                    </Button>
                                                )}
                                            </div>
                                        ))}
                                    </div>
                                )}

                                <Form
                                    {...ClientAccessGrantController.store.form(
                                        dossier.id,
                                    )}
                                    className="space-y-3"
                                >
                                    {({ processing }) => (
                                        <>
                                            <input
                                                type="hidden"
                                                name="expires_in_days"
                                                value="7"
                                            />
                                            <Button
                                                disabled={processing}
                                                variant="secondary"
                                            >
                                                Create 7-day access token
                                            </Button>
                                        </>
                                    )}
                                </Form>
                            </CardContent>
                        </Card>
                    </div>

                    <Card>
                        <CardHeader>
                            <CardTitle>Add request</CardTitle>
                            <CardDescription>
                                Ask the client for one document at a time.
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            <Form
                                {...DocumentRequestController.store.form(
                                    dossier.id,
                                )}
                                resetOnSuccess
                                className="space-y-4"
                            >
                                {({ errors, processing }) => (
                                    <>
                                        <div className="grid gap-2">
                                            <Label htmlFor="title">
                                                Document name
                                            </Label>
                                            <Input
                                                id="title"
                                                name="title"
                                                required
                                                placeholder="Payslip January 2025"
                                            />
                                            <InputError
                                                message={errors.title}
                                            />
                                        </div>

                                        <div className="grid gap-2">
                                            <Label htmlFor="instructions">
                                                Instructions (optional)
                                            </Label>
                                            <textarea
                                                id="instructions"
                                                name="instructions"
                                                rows={4}
                                                className="border-input bg-background focus-visible:border-ring focus-visible:ring-ring/50 rounded-md border px-3 py-2 text-sm shadow-xs outline-none focus-visible:ring-[3px]"
                                                placeholder="Upload the PDF you received from your employer."
                                            />
                                            <InputError
                                                message={errors.instructions}
                                            />
                                        </div>

                                        <Button
                                            disabled={processing}
                                            className="w-full"
                                        >
                                            Add request
                                        </Button>
                                    </>
                                )}
                            </Form>
                        </CardContent>
                    </Card>
                </div>
            </div>
        </>
    );
}
