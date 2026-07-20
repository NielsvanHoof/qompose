import { Form } from '@inertiajs/react';
import { Trash2 } from 'lucide-react';
import type { ReactNode } from 'react';
import DocumentRequestController from '@/actions/App/Http/Controllers/Dossiers/DocumentRequestController';
import InputError from '@/components/input-error';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { getQuestionnaireItemTypeDefinition } from '@/features/document-requests/questionnaire-item-type-registry';
import QuestionnaireItemTypeSelect from '@/features/document-requests/questionnaire-item-type-select';
import DocumentRequestReview from '@/features/document-requests/staff/document-request-review';
import type {
    DocumentRequest,
    DocumentRequestStatus,
} from '@/features/document-requests/types';
import type { DossierStatus } from '@/features/dossiers/types';
import { useCurrentWorkspace } from '@/hooks/use-current-workspace';
import { inlineDossierActionOptions } from '@/lib/inline-dossier-action-options';

type DocumentRequestListItemProps = {
    dossierId: number;
    dossierStatus: DossierStatus;
    documentRequest: DocumentRequest;
    canEdit: boolean;
    canReview: boolean;
    canDownload: boolean;
    DragHandle: () => ReactNode;
};

/**
 * One staff questionnaire row. The surrounding card only manages the list.
 */
export default function DocumentRequestListItem({
    dossierId,
    dossierStatus,
    documentRequest,
    canEdit,
    canReview,
    canDownload,
    DragHandle,
}: DocumentRequestListItemProps) {
    const currentWorkspace = useCurrentWorkspace();
    const typeDefinition = getQuestionnaireItemTypeDefinition(
        documentRequest.type,
    );
    const { StaffContent } = typeDefinition;

    return (
        <div className="space-y-3 px-4 py-3">
            <div className="flex flex-wrap items-start justify-between gap-3">
                <div className="flex flex-wrap items-center gap-1">
                    <DragHandle />
                    <Badge variant="outline">{typeDefinition.label}</Badge>
                    <Badge variant="secondary">
                        {statusLabel(documentRequest.status)}
                    </Badge>
                </div>
                {canEdit && (
                    <Form
                        {...DocumentRequestController.destroy.form({
                            tenant: currentWorkspace,
                            dossier: dossierId,
                            documentRequest: documentRequest.id,
                        })}
                        options={inlineDossierActionOptions}
                    >
                        {({ processing }) => (
                            <Button
                                type="submit"
                                size="icon"
                                variant="ghost"
                                disabled={processing}
                                aria-label={`Delete ${documentRequest.title}`}
                            >
                                <Trash2 aria-hidden="true" />
                            </Button>
                        )}
                    </Form>
                )}
            </div>

            {canEdit && (
                <Form
                    {...DocumentRequestController.update.form({
                        tenant: currentWorkspace,
                        dossier: dossierId,
                        documentRequest: documentRequest.id,
                    })}
                    options={inlineDossierActionOptions}
                    className="grid gap-3"
                >
                    {({ errors, processing }) => (
                        <>
                            <QuestionnaireItemTypeSelect
                                id={`type-${documentRequest.id}`}
                                defaultValue={documentRequest.type}
                                error={errors.type}
                            />
                            <div className="grid gap-2">
                                <Label htmlFor={`title-${documentRequest.id}`}>
                                    Title
                                </Label>
                                <Input
                                    id={`title-${documentRequest.id}`}
                                    name="title"
                                    required
                                    defaultValue={documentRequest.title}
                                />
                                <InputError message={errors.title} />
                            </div>
                            <div className="grid gap-2">
                                <Label
                                    htmlFor={`instructions-${documentRequest.id}`}
                                >
                                    Instructions
                                </Label>
                                <textarea
                                    id={`instructions-${documentRequest.id}`}
                                    name="instructions"
                                    rows={2}
                                    defaultValue={
                                        documentRequest.instructions ?? ''
                                    }
                                    className="rounded-md border border-input bg-background px-3 py-2 text-sm shadow-xs outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50"
                                />
                                <InputError message={errors.instructions} />
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

            <StaffContent
                dossierId={dossierId}
                dossierStatus={dossierStatus}
                documentRequest={documentRequest}
                canEdit={canEdit}
                canDownload={canDownload}
            />

            <DocumentRequestReview
                dossierId={dossierId}
                documentRequest={documentRequest}
                canReview={canReview}
            />
        </div>
    );
}

function statusLabel(status: DocumentRequestStatus): string {
    return status === 'accepted' ? 'approved' : status.replaceAll('_', ' ');
}
