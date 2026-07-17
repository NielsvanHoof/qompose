import { Form } from '@inertiajs/react';
import ClientPortalAnswerController from '@/actions/App/Http/Controllers/Portal/ClientPortalAnswerController';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import type { PortalDocumentRequest } from '@/features/document-requests/types';

/**
 * Portal form for a text questionnaire answer.
 */
export function PortalTextDocumentAnswer({
    token,
    documentRequest,
}: {
    token: string;
    documentRequest: PortalDocumentRequest;
}) {
    return (
        <Form
            {...ClientPortalAnswerController.store.form({
                token,
                documentRequest: documentRequest.id,
            })}
            className="space-y-3"
        >
            {({ errors, processing }) => (
                <>
                    <div className="grid gap-2">
                        <Label htmlFor={`answer-${documentRequest.id}`}>
                            Your answer
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
                            ? 'Update answer'
                            : 'Submit answer'}
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
    token,
    documentRequest,
}: {
    token: string;
    documentRequest: PortalDocumentRequest;
}) {
    return (
        <div className="flex flex-wrap gap-2">
            <BooleanAnswerButton
                token={token}
                documentRequestId={documentRequest.id}
                value={true}
                label="Yes"
                active={documentRequest.answer_boolean === true}
            />
            <BooleanAnswerButton
                token={token}
                documentRequestId={documentRequest.id}
                value={false}
                label="No"
                active={documentRequest.answer_boolean === false}
            />
        </div>
    );
}

function BooleanAnswerButton({
    token,
    documentRequestId,
    value,
    label,
    active,
}: {
    token: string;
    documentRequestId: number;
    value: boolean;
    label: string;
    active: boolean;
}) {
    return (
        <Form
            {...ClientPortalAnswerController.store.form({
                token,
                documentRequest: documentRequestId,
            })}
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
