import { Form, router } from '@inertiajs/react';
import { Trash2 } from 'lucide-react';
import DocumentRequestController from '@/actions/App/Http/Controllers/Dossiers/DocumentRequestController';
import UploadedDocumentController from '@/actions/App/Http/Controllers/Dossiers/UploadedDocumentController';
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
import { formatBytes } from '@/lib/format-bytes';
import { formatDateTime } from '@/lib/format-date-time';
import type { DocumentRequest } from '@/types';

/**
 * Staff questionnaire editor: edit, drag-reorder, delete, and collect answers.
 */
export default function DocumentRequestsCard({
    dossierId,
    documentRequests,
}: {
    dossierId: number;
    documentRequests: DocumentRequest[];
}) {
    const persistOrder = (orderedIds: number[]) => {
        router.post(
            DocumentRequestController.reorder.url(dossierId),
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
                                            {documentRequest.status.replaceAll(
                                                '_',
                                                ' ',
                                            )}
                                        </Badge>
                                    </div>
                                    <Form
                                        {...DocumentRequestController.destroy.form(
                                            {
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
                                </div>

                                <Form
                                    {...DocumentRequestController.update.form({
                                        dossier: dossierId,
                                        documentRequest: documentRequest.id,
                                    })}
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
                                            dossierId={dossierId}
                                            documentRequest={documentRequest}
                                        />
                                    </>
                                )}
                            </div>
                        )}
                    />
                )}
            </CardContent>
        </Card>
    );
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
