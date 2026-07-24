import { Form } from '@inertiajs/react';
import ClientPortalAnswerController from '@/actions/App/Http/Controllers/Portal/ClientPortalAnswerController';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import type {
    PortalDocumentRequest,
    QuestionnaireItemType,
} from '@/features/document-requests/types';
import { useTranslation } from '@/hooks/use-translation';
import { inlinePortalActionOptions } from '@/lib/inline-portal-action-options';
import { cn } from '@/lib/utils';

/**
 * Portal form for answer_text types (text, textarea, date, number).
 */
export function PortalTextDocumentAnswer({
    documentRequest,
}: {
    documentRequest: PortalDocumentRequest;
}) {
    const { t } = useTranslation();
    const fieldId = `answer-${documentRequest.id}`;

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
                        {/* Visually quiet — the field title above already names the question. */}
                        <Label htmlFor={fieldId} className="sr-only">
                            {t('Your answer')}
                        </Label>
                        <AnswerTextControl
                            id={fieldId}
                            type={documentRequest.type}
                            defaultValue={documentRequest.answer_text ?? ''}
                        />
                        <InputError message={errors.answer_text} />
                    </div>
                    <Button
                        type="submit"
                        disabled={processing}
                        className="w-full sm:w-auto"
                    >
                        {processing
                            ? t('Saving…')
                            : documentRequest.answer_text
                              ? t('Update answer')
                              : t('Submit answer')}
                    </Button>
                </>
            )}
        </Form>
    );
}

/** Renders the correct native control for each answer_text questionnaire type. */
function AnswerTextControl({
    id,
    type,
    defaultValue,
}: {
    id: string;
    type: QuestionnaireItemType;
    defaultValue: string;
}) {
    const { t } = useTranslation();
    const controlClassName =
        'w-full rounded-md border bg-background px-3 py-2.5 text-sm shadow-xs outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50';

    if (type === 'date') {
        return (
            <Input
                id={id}
                name="answer_text"
                type="date"
                required
                defaultValue={defaultValue}
                autoComplete="off"
                className="h-11"
            />
        );
    }

    if (type === 'number') {
        return (
            <Input
                id={id}
                name="answer_text"
                type="number"
                required
                defaultValue={defaultValue}
                inputMode="decimal"
                autoComplete="off"
                placeholder={t('Enter a number…')}
                className="h-11"
            />
        );
    }

    if (type === 'textarea') {
        return (
            <textarea
                id={id}
                name="answer_text"
                rows={5}
                required
                defaultValue={defaultValue}
                className={controlClassName}
                placeholder={t('Write your answer…')}
            />
        );
    }

    return (
        <Input
            id={id}
            name="answer_text"
            type="text"
            required
            defaultValue={defaultValue}
            autoComplete="off"
            placeholder={t('Write your answer…')}
            className="h-11"
        />
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
        <div
            className="grid grid-cols-2 gap-2"
            role="group"
            aria-label={t('Your answer')}
        >
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
            className="contents"
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
                        className={cn(
                            'h-11 touch-manipulation',
                            active && 'ring-[3px] ring-ring/40',
                        )}
                    >
                        {label}
                    </Button>
                </>
            )}
        </Form>
    );
}
