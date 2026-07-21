import { Form } from '@inertiajs/react';
import ClientPortalAnswerController from '@/actions/App/Http/Controllers/Portal/ClientPortalAnswerController';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import type { PortalDocumentRequest } from '@/features/document-requests/types';
import { useTranslation } from '@/hooks/use-translation';
import { inlinePortalActionOptions } from '@/lib/inline-portal-action-options';

/**
 * Portal form for a text questionnaire answer.
 */
export function PortalTextDocumentAnswer({
    documentRequest,
}: {
    documentRequest: PortalDocumentRequest;
}) {
    const { t } = useTranslation();

    return (
        <Form
            {...ClientPortalAnswerController.store.form({
                documentRequest: documentRequest.id,
            })}
            options={inlinePortalActionOptions}
            className="space-y-3"
        >
            {({ errors, processing }) => (
                <>
                    <div className="grid gap-2">
                        <Label htmlFor={`answer-${documentRequest.id}`}>
                            {t('Your answer')}
                        </Label>
                        <textarea
                            id={`answer-${documentRequest.id}`}
                            name="answer_text"
                            rows={3}
                            required
                            defaultValue={documentRequest.answer_text ?? ''}
                            className="w-full rounded-md border bg-background px-3 py-2 text-sm shadow-xs outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50"
                        />
                        <InputError message={errors.answer_text} />
                    </div>
                    <Button type="submit" disabled={processing}>
                        {documentRequest.answer_text
                            ? t('Update answer')
                            : t('Submit answer')}
                    </Button>
                </>
            )}
        </Form>
    );
}

/**
 * Portal form for a yes or no questionnaire answer.
 */
export function PortalBooleanDocumentAnswer({
    documentRequest,
}: {
    documentRequest: PortalDocumentRequest;
}) {
    const { t } = useTranslation();

    return (
        <div className="flex flex-wrap gap-2">
            <BooleanAnswerButton
                documentRequestId={documentRequest.id}
                value={true}
                label={t('Yes')}
                active={documentRequest.answer_boolean === true}
            />
            <BooleanAnswerButton
                documentRequestId={documentRequest.id}
                value={false}
                label={t('No')}
                active={documentRequest.answer_boolean === false}
            />
        </div>
    );
}

function BooleanAnswerButton({
    documentRequestId,
    value,
    label,
    active,
}: {
    documentRequestId: number;
    value: boolean;
    label: string;
    active: boolean;
}) {
    return (
        <Form
            {...ClientPortalAnswerController.store.form({
                documentRequest: documentRequestId,
            })}
            options={inlinePortalActionOptions}
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
                        disabled={processing}
                        variant={active ? 'default' : 'outline'}
                    >
                        {label}
                    </Button>
                </>
            )}
        </Form>
    );
}
