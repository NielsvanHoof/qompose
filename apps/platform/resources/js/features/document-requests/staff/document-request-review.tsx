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
import { formatDateTime } from '@/lib/format-date-time';

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

    if (documentRequest.status === 'rejected') {
        return (
            <Alert variant="destructive">
                <RotateCcw />
                <AlertTitle>Changes requested</AlertTitle>
                <AlertDescription>
                    <p>{documentRequest.rejection_reason}</p>
                    {documentRequest.reviewed_at && (
                        <p>
                            Reviewed
                            {documentRequest.reviewed_by_name
                                ? ` by ${documentRequest.reviewed_by_name}`
                                : ''}{' '}
                            on {formatDateTime(documentRequest.reviewed_at)}.
                        </p>
                    )}
                </AlertDescription>
            </Alert>
        );
    }

    if (documentRequest.status === 'accepted') {
        return (
            <div className="flex items-start gap-2 rounded-md border border-emerald-500/30 bg-emerald-500/5 px-3 py-2 text-sm">
                <Check className="mt-0.5 size-4 text-emerald-600" />
                <div>
                    <p className="font-medium text-emerald-700">Approved</p>
                    {documentRequest.reviewed_at && (
                        <p className="text-muted-foreground">
                            {documentRequest.reviewed_by_name
                                ? `${documentRequest.reviewed_by_name} · `
                                : ''}
                            {formatDateTime(documentRequest.reviewed_at)}
                        </p>
                    )}
                </div>
            </div>
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
                This item is ready for review.
            </p>

            <Form
                {...DocumentRequestReviewController.store.form(routeParameters)}
            >
                {({ processing }) => (
                    <>
                        <input type="hidden" name="decision" value="accepted" />
                        <Button type="submit" size="sm" disabled={processing}>
                            <Check />
                            Approve
                        </Button>
                    </>
                )}
            </Form>

            <Dialog>
                <DialogTrigger asChild>
                    <Button type="button" size="sm" variant="outline">
                        <RotateCcw />
                        Request changes
                    </Button>
                </DialogTrigger>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>Request changes</DialogTitle>
                        <DialogDescription>
                            Explain exactly what the client should correct or
                            upload again.
                        </DialogDescription>
                    </DialogHeader>

                    <Form
                        {...DocumentRequestReviewController.store.form(
                            routeParameters,
                        )}
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
                                        Feedback for the client
                                    </Label>
                                    <textarea
                                        id={`rejection-${documentRequest.id}`}
                                        name="rejection_reason"
                                        rows={4}
                                        required
                                        className="rounded-md border bg-background px-3 py-2 text-sm shadow-xs outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50"
                                        placeholder="The document is missing page 2. Please upload the complete statement."
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
                                        Request changes
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
