import { Form, router } from '@inertiajs/react';
import { Trash2 } from 'lucide-react';
import DocumentRequestController from '@/actions/App/Http/Controllers/Dossiers/DocumentRequestController';
import UploadedDocumentController from '@/actions/App/Http/Controllers/Dossiers/UploadedDocumentController';
import DocumentRequestReview from '@/components/dossiers/document-request-review';
import DocumentRequestUpload from '@/components/dossiers/document-request-upload';
import InputError from '@/components/input-error';
import SortableList from '@/components/sortable/sortable-list';
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
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { useCurrentWorkspace } from '@/hooks/use-current-workspace';
import { formatBytes } from '@/lib/format-bytes';
import { formatDateTime } from '@/lib/format-date-time';
import type {
    DocumentRequest,
    DocumentRequestStatus,
    DossierStatus,
} from '@/types';

/**
 * Staff questionnaire editor: edit, drag-reorder, delete, and collect answers.
 */
export default function DocumentRequestsCard({
    dossierId,
    dossierStatus,
    documentRequests,
    canManage,
    canReview,
    canDownload,
}: {
    dossierId: number;
    dossierStatus: DossierStatus;
    documentRequests: DocumentRequest[];
    canManage: boolean;
    canReview: boolean;
    canDownload: boolean;
}) {
    const currentWorkspace = useCurrentWorkspace();
    const canEdit = canManage && dossierStatus !== 'completed';

    const persistOrder = (orderedIds: number[]) => {
        router.post(
            DocumentRequestController.reorder.url({
                tenant: currentWorkspace,
                dossier: dossierId,
            }),
            { document_request_ids: orderedIds },
            { preserveScroll: true },
        );
    };

    return (
        <Card>
            <CardHeader>
                <CardTitle>Questionnaire</CardTitle>
                <CardDescription>
                    Tailor items for this dossier. Drag the grip to reorder.
                    Clients answer via the portal.
                </CardDescription>
            </CardHeader>
            <CardContent>
                {documentRequests.length === 0 ? (
                    <p className="text-sm text-muted-foreground">
                        No items yet. Apply a template or add a request.
                    </p>
                ) : (
                    <SortableList
                        items={documentRequests}
                        enabled={canEdit}
                        onReorder={persistOrder}
                        className="divide-y rounded-md border"
                        renderItem={(documentRequest, { DragHandle }) => (
                            <div className="space-y-3 px-4 py-3">
                                <div className="flex flex-wrap items-start justify-between gap-3">
                                    <div className="flex flex-wrap items-center gap-1">
                                        <DragHandle />
                                        <Badge variant="outline">
                                            {documentRequest.type}
                                        </Badge>
                                        <Badge variant="secondary">
                                            {statusLabel(
                                                documentRequest.status,
                                            )}
                                        </Badge>
                                    </div>
                                    {canEdit && (
                                        <Form
                                            {...DocumentRequestController.destroy.form(
                                                {
                                                    tenant: currentWorkspace,
                                                    dossier: dossierId,
                                                    documentRequest:
                                                        documentRequest.id,
                                                },
                                            )}
                                        >
                                            {({ processing }) => (
                                                <Button
                                                    type="submit"
                                                    size="icon"
                                                    variant="ghost"
                                                    disabled={processing}
                                                >
                                                    <Trash2 />
                                                </Button>
                                            )}
                                        </Form>
                                    )}
                                </div>

                                {canEdit && (
                                    <Form
                                        {...DocumentRequestController.update.form(
                                            {
                                                tenant: currentWorkspace,
                                                dossier: dossierId,
                                                documentRequest:
                                                    documentRequest.id,
                                            },
                                        )}
                                        className="grid gap-3"
                                    >
                                        {({ errors, processing }) => (
                                            <>
                                                <div className="grid gap-2">
                                                    <Label>Type</Label>
                                                    <Select
                                                        required
                                                        defaultValue={
                                                            documentRequest.type
                                                        }
                                                        name="type"
                                                    >
                                                        <SelectTrigger className="w-full">
                                                            <SelectValue />
                                                        </SelectTrigger>
                                                        <SelectContent className="bg-background">
                                                            <SelectItem value="file">
                                                                File upload
                                                            </SelectItem>
                                                            <SelectItem value="text">
                                                                Text answer
                                                            </SelectItem>
                                                            <SelectItem value="boolean">
                                                                Yes / no
                                                            </SelectItem>
                                                        </SelectContent>
                                                    </Select>
                                                    <InputError
                                                        message={errors.type}
                                                    />
                                                </div>
                                                <div className="grid gap-2">
                                                    <Label>Title</Label>
                                                    <Input
                                                        name="title"
                                                        required
                                                        defaultValue={
                                                            documentRequest.title
                                                        }
                                                    />
                                                    <InputError
                                                        message={errors.title}
                                                    />
                                                </div>
                                                <div className="grid gap-2">
                                                    <Label>Instructions</Label>
                                                    <textarea
                                                        name="instructions"
                                                        rows={2}
                                                        defaultValue={
                                                            documentRequest.instructions ??
                                                            ''
                                                        }
                                                        className="rounded-md border border-input bg-background px-3 py-2 text-sm shadow-xs outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50"
                                                    />
                                                    <InputError
                                                        message={
                                                            errors.instructions
                                                        }
                                                    />
                                                </div>
                                                <Button
                                                    type="submit"
                                                    size="sm"
                                                    disabled={processing}
                                                    className="w-fit"
                                                >
                                                    Save item
                                                </Button>
                                            </>
                                        )}
                                    </Form>
                                )}

                                <AnswerPreview
                                    documentRequest={documentRequest}
                                />

                                {documentRequest.type === 'file' && (
                                    <>
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
                                                        {formatDateTime(
                                                            documentRequest
                                                                .uploaded_document
                                                                .uploaded_at,
                                                        )}
                                                    </p>
                                                </div>
                                                {canDownload && (
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
                                                                        documentRequest
                                                                            .uploaded_document
                                                                            .id,
                                                                },
                                                            )}
                                                        >
                                                            Download
                                                        </a>
                                                    </Button>
                                                )}
                                            </div>
                                        )}
                                        {canEdit && (
                                            <DocumentRequestUpload
                                                dossierId={dossierId}
                                                documentRequest={
                                                    documentRequest
                                                }
                                            />
                                        )}
                                    </>
                                )}

                                <DocumentRequestReview
                                    dossierId={dossierId}
                                    documentRequest={documentRequest}
                                    canReview={
                                        canReview &&
                                        dossierStatus !== 'completed'
                                    }
                                />
                            </div>
                        )}
                    />
                )}
            </CardContent>
        </Card>
    );
}

function statusLabel(status: DocumentRequestStatus): string {
    return status === 'accepted' ? 'approved' : status.replaceAll('_', ' ');
}

function AnswerPreview({
    documentRequest,
}: {
    documentRequest: DocumentRequest;
}) {
    if (documentRequest.type === 'text' && documentRequest.answer_text) {
        return (
            <div className="rounded-md bg-muted/50 px-3 py-2 text-sm">
                <p className="text-muted-foreground">Answer</p>
                <p className="font-medium whitespace-pre-wrap">
                    {documentRequest.answer_text}
                </p>
            </div>
        );
    }

    if (
        documentRequest.type === 'boolean' &&
        documentRequest.answer_boolean !== null
    ) {
        return (
            <div className="rounded-md bg-muted/50 px-3 py-2 text-sm">
                <p className="text-muted-foreground">Answer</p>
                <p className="font-medium">
                    {documentRequest.answer_boolean ? 'Yes' : 'No'}
                </p>
            </div>
        );
    }

    return null;
}
