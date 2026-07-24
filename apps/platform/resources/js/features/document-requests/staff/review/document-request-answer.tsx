import { Form } from '@inertiajs/react';
import { useState } from 'react';
import DocumentRequestReviewController from '@/actions/App/Http/Controllers/Dossiers/DocumentRequestReviewController';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import {
    Collapsible,
    CollapsibleContent,
    CollapsibleTrigger,
} from '@/components/ui/collapsible';
import { Label } from '@/components/ui/label';
import type { DocumentRequest } from '@/features/document-requests/types';
import { useCurrentWorkspace } from '@/hooks/use-current-workspace';
import { useTranslation } from '@/hooks/use-translation';
import { inlineDossierActionOptions } from '@/lib/inline-dossier-action-options';

/**
 * Optional staff fallback for answers received by email, phone, or in person.
 * The normal client portal remains the primary response path.
 */
export default function DocumentRequestAnswer({
    dossierId,
    documentRequest,
}: {
    dossierId: number;
    documentRequest: DocumentRequest;
}) {
    const { t } = useTranslation();
    const [open, setOpen] = useState(false);

    return (
        <Collapsible open={open} onOpenChange={setOpen} className="mt-2">
            <CollapsibleTrigger asChild>
                <Button
                    type="button"
                    variant="ghost"
                    size="sm"
                    className="px-0"
                >
                    {open
                        ? t('Hide staff answer')
                        : t('Answer on behalf of client')}
                </Button>
            </CollapsibleTrigger>
            <CollapsibleContent className="pt-2">
                {documentRequest.type === 'text' ? (
                    <StaffTextAnswerForm
                        dossierId={dossierId}
                        documentRequest={documentRequest}
                    />
                ) : (
                    <StaffBooleanAnswerForm
                        dossierId={dossierId}
                        documentRequest={documentRequest}
                    />
                )}
            </CollapsibleContent>
        </Collapsible>
    );
}

function StaffTextAnswerForm({
    dossierId,
    documentRequest,
}: {
    dossierId: number;
    documentRequest: DocumentRequest;
}) {
    const { t } = useTranslation();

    return (
        <Form
            {...DocumentRequestReviewController.answer.form(
                useAnswerRouteParameters(dossierId, documentRequest.id),
            )}
            options={inlineDossierActionOptions}
            className="space-y-2"
        >
            {({ errors, processing }) => (
                <>
                    <Label htmlFor={`staff-answer-${documentRequest.id}`}>
                        {t('Client answer')}
                    </Label>
                    <textarea
                        id={`staff-answer-${documentRequest.id}`}
                        name="answer_text"
                        rows={3}
                        required
                        defaultValue={documentRequest.answer_text ?? ''}
                        className="w-full rounded-md border bg-background px-3 py-2 text-sm shadow-xs outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50"
                    />
                    <InputError message={errors.answer_text} />
                    <Button type="submit" size="sm" disabled={processing}>
                        {processing ? t('Saving…') : t('Save client answer')}
                    </Button>
                </>
            )}
        </Form>
    );
}

function StaffBooleanAnswerForm({
    dossierId,
    documentRequest,
}: {
    dossierId: number;
    documentRequest: DocumentRequest;
}) {
    const { t } = useTranslation();
    const routeParameters = useAnswerRouteParameters(
        dossierId,
        documentRequest.id,
    );

    return (
        <div className="space-y-2">
            <Label>{t('Client answer')}</Label>
            <div className="flex flex-wrap gap-2">
                {[true, false].map((value) => (
                    <Form
                        key={String(value)}
                        {...DocumentRequestReviewController.answer.form(
                            routeParameters,
                        )}
                        options={inlineDossierActionOptions}
                    >
                        {({ processing }) => (
                            <>
                                <input
                                    type="hidden"
                                    name="answer_boolean"
                                    value={value ? '1' : '0'}
                                />
                                <Button
                                    type="submit"
                                    size="sm"
                                    disabled={processing}
                                    variant={
                                        documentRequest.answer_boolean === value
                                            ? 'default'
                                            : 'outline'
                                    }
                                >
                                    {value ? t('Yes') : t('No')}
                                </Button>
                            </>
                        )}
                    </Form>
                ))}
            </div>
        </div>
    );
}

function useAnswerRouteParameters(
    dossierId: number,
    documentRequestId: number,
) {
    const currentWorkspace = useCurrentWorkspace();

    return {
        tenant: currentWorkspace,
        dossier: dossierId,
        documentRequest: documentRequestId,
    };
}
