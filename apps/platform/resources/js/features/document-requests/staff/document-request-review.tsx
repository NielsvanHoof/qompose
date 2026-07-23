import { Form } from '@inertiajs/react';
import { Check, RotateCcw } from 'lucide-react';
import DocumentRequestReviewController from '@/actions/App/Http/Controllers/Dossiers/DocumentRequestReviewController';
import InputError from '@/components/input-error';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import { Label } from '@/components/ui/label';
import type { DocumentRequest } from '@/features/document-requests/types';
import { useCurrentWorkspace } from '@/hooks/use-current-workspace';
import { useTranslation } from '@/hooks/use-translation';
import { formatDateTime } from '@/lib/format-date-time';
import { inlineDossierActionOptions } from '@/lib/inline-dossier-action-options';

export default function DocumentRequestReview({
    dossierId,
    documentRequest,
    canReview,
}: {
    dossierId: number;
    documentRequest: DocumentRequest;
    canReview: boolean;
}) {
    const currentWorkspace = useCurrentWorkspace();
    const { t, locale } = useTranslation();

    if (documentRequest.status === 'rejected') {
        return (
            <Alert variant="destructive">
                <RotateCcw aria-hidden="true" />
                <AlertTitle>{t('Changes requested')}</AlertTitle>
                <AlertDescription>
                    {/* Rejection reason is user-generated — do not translate. */}
                    <p>{documentRequest.rejection_reason}</p>
                    {documentRequest.reviewed_at && (
                        <p>
                            {documentRequest.reviewed_by_name
                                ? t('Reviewed by :name on :date.', {
                                      name: documentRequest.reviewed_by_name,
                                      date: formatDateTime(
                                          documentRequest.reviewed_at,
                                          locale,
                                      ),
                                  })
                                : t('Reviewed on :date.', {
                                      date: formatDateTime(
                                          documentRequest.reviewed_at,
                                          locale,
                                      ),
                                  })}
                        </p>
                    )}
                </AlertDescription>
            </Alert>
        );
    }

    if (documentRequest.status === 'accepted') {
        return (
            <Alert variant="success">
                <Check aria-hidden="true" />
                <AlertTitle>{t('Approved')}</AlertTitle>
                {documentRequest.reviewed_at && (
                    <AlertDescription>
                        {documentRequest.reviewed_by_name
                            ? `${documentRequest.reviewed_by_name} · `
                            : ''}
                        {formatDateTime(documentRequest.reviewed_at, locale)}
                    </AlertDescription>
                )}
            </Alert>
        );
    }

    if (documentRequest.status !== 'submitted' || !canReview) {
        return null;
    }

    const routeParameters = {
        tenant: currentWorkspace,
        dossier: dossierId,
        documentRequest: documentRequest.id,
    };

    return (
        <div className="flex flex-wrap items-center gap-2 rounded-md border bg-muted/20 px-3 py-3">
            <p className="mr-auto text-sm text-muted-foreground">
                {t('This item is ready for review.')}
            </p>

            <Form
                {...DocumentRequestReviewController.store.form(routeParameters)}
                options={inlineDossierActionOptions}
            >
                {({ processing }) => (
                    <>
                        <input type="hidden" name="decision" value="accepted" />
                        <Button type="submit" size="sm" disabled={processing}>
                            <Check aria-hidden="true" />
                            {t('Approve')}
                        </Button>
                    </>
                )}
            </Form>

            <Dialog>
                <DialogTrigger asChild>
                    <Button type="button" size="sm" variant="outline">
                        <RotateCcw aria-hidden="true" />
                        {t('Request changes')}
                    </Button>
                </DialogTrigger>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>{t('Request changes')}</DialogTitle>
                        <DialogDescription>
                            {t(
                                'Explain exactly what the client should correct or upload again.',
                            )}
                        </DialogDescription>
                    </DialogHeader>

                    <Form
                        {...DocumentRequestReviewController.store.form(
                            routeParameters,
                        )}
                        options={inlineDossierActionOptions}
                        className="space-y-4"
                    >
                        {({ errors, processing }) => (
                            <>
                                <input
                                    type="hidden"
                                    name="decision"
                                    value="rejected"
                                />
                                <div className="grid gap-2">
                                    <Label
                                        htmlFor={`rejection-${documentRequest.id}`}
                                    >
                                        {t('Feedback for the client')}
                                    </Label>
                                    <textarea
                                        id={`rejection-${documentRequest.id}`}
                                        name="rejection_reason"
                                        rows={4}
                                        required
                                        className="rounded-md border bg-background px-3 py-2 text-sm shadow-xs outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50"
                                        placeholder={t(
                                            'The document is missing page 2. Please upload the complete statement.',
                                        )}
                                    />
                                    <InputError
                                        message={errors.rejection_reason}
                                    />
                                </div>
                                <DialogFooter>
                                    <Button
                                        type="submit"
                                        variant="destructive"
                                        disabled={processing}
                                    >
                                        {t('Request changes')}
                                    </Button>
                                </DialogFooter>
                            </>
                        )}
                    </Form>
                </DialogContent>
            </Dialog>
        </div>
    );
}
