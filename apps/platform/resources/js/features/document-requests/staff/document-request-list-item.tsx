import { Form } from '@inertiajs/react';
import { Trash2 } from 'lucide-react';
import type { ReactNode } from 'react';
import DocumentRequestController from '@/actions/App/Http/Controllers/Dossiers/DocumentRequestController';
import InputError from '@/components/input-error';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { documentRequestStatusLabel } from '@/features/document-requests/document-request-status';
import { getQuestionnaireItemTypeDefinition } from '@/features/document-requests/questionnaire-item-type-registry';
import QuestionnaireItemTypeSelect from '@/features/document-requests/questionnaire-item-type-select';
import DocumentRequestReview from '@/features/document-requests/staff/document-request-review';
import type { DocumentRequest } from '@/features/document-requests/types';
import { useDossierPermissions } from '@/features/dossiers/dossier-permissions-context';
import type { DossierStatus } from '@/features/dossiers/types';
import { useCurrentWorkspace } from '@/hooks/use-current-workspace';
import { useTranslation } from '@/hooks/use-translation';
import { inlineDossierActionOptions } from '@/lib/inline-dossier-action-options';

type DocumentRequestListItemProps = {
    dossierId: number;
    dossierStatus: DossierStatus;
    documentRequest: DocumentRequest;
    canEdit: boolean;
    DragHandle: () => ReactNode;
};

/**
 * One staff questionnaire row. The surrounding card only manages the list.
 * Review/download flags come from DossierPermissionsProvider.
 */
export default function DocumentRequestListItem({
    dossierId,
    dossierStatus,
    documentRequest,
    canEdit,
    DragHandle,
}: DocumentRequestListItemProps) {
    const currentWorkspace = useCurrentWorkspace();
    const { t } = useTranslation();
    const { canReview } = useDossierPermissions();
    const typeDefinition = getQuestionnaireItemTypeDefinition(
        documentRequest.type,
    );
    const { StaffContent } = typeDefinition;
    const reviewAllowed = canReview && dossierStatus !== 'completed';

    return (
        <div className="space-y-3 px-4 py-3">
            <div className="flex flex-wrap items-start justify-between gap-3">
                <div className="flex flex-wrap items-center gap-1">
                    <DragHandle />
                    <Badge variant="outline">{t(typeDefinition.label)}</Badge>
                    <Badge variant="secondary">
                        {documentRequestStatusLabel(documentRequest.status, t)}
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
                                aria-label={t('Delete :title', {
                                    title: documentRequest.title,
                                })}
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
                                    {t('Title')}
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
                                    {t('Instructions')}
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
                                {t('Save item')}
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
            />

            <DocumentRequestReview
                dossierId={dossierId}
                documentRequest={documentRequest}
                canReview={reviewAllowed}
            />
        </div>
    );
}
