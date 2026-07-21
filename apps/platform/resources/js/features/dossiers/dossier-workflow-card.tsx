import { Form } from '@inertiajs/react';
import { CheckCircle2 } from 'lucide-react';
import DossierCompletionController from '@/actions/App/Http/Controllers/Dossiers/DossierCompletionController';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import type { Dossier } from '@/features/dossiers/types';
import { useCurrentWorkspace } from '@/hooks/use-current-workspace';
import { inlineDossierActionOptions } from '@/lib/inline-dossier-action-options';

export default function DossierWorkflowCard({
    dossier,
    canReview,
}: {
    dossier: Dossier;
    canReview: boolean;
}) {
    const summary = dossier.review_summary;

    return (
        <Card>
            <CardHeader>
                <CardTitle>Review progress</CardTitle>
                <CardDescription>
                    Review submitted items and complete the dossier when
                    everything is approved.
                </CardDescription>
            </CardHeader>
            <CardContent className="space-y-4">
                <div className="grid grid-cols-2 gap-3 text-sm">
                    <ReviewCount label="Waiting" value={summary.pending} />
                    <ReviewCount
                        label="Ready to review"
                        value={summary.submitted}
                    />
                    <ReviewCount
                        label="Changes requested"
                        value={summary.rejected}
                    />
                    <ReviewCount label="Approved" value={summary.accepted} />
                </div>

                {dossier.status === 'completed' ? (
                    <div className="flex items-center gap-2 rounded-md border border-success-border bg-success-muted px-3 py-2 text-sm font-medium text-success-foreground">
                        <CheckCircle2 className="size-4" aria-hidden="true" />
                        Dossier completed
                    </div>
                ) : canReview ? (
                    <div className="space-y-2">
                        <CompleteDossierDialog
                            dossierId={dossier.id}
                            ready={dossier.ready_to_complete}
                        />
                        {!dossier.ready_to_complete && (
                            <p className="text-xs text-muted-foreground">
                                Every item must be approved first.
                            </p>
                        )}
                    </div>
                ) : (
                    <p className="text-xs text-muted-foreground">
                        A reviewer will complete the dossier after all items
                        have been approved.
                    </p>
                )}
            </CardContent>
        </Card>
    );
}

function CompleteDossierDialog({
    dossierId,
    ready,
}: {
    dossierId: number;
    ready: boolean;
}) {
    const currentWorkspace = useCurrentWorkspace();

    return (
        <Dialog>
            <DialogTrigger asChild>
                <Button type="button" className="w-full" disabled={!ready}>
                    <CheckCircle2 aria-hidden="true" />
                    Complete dossier
                </Button>
            </DialogTrigger>
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>Complete this dossier?</DialogTitle>
                    <DialogDescription>
                        This marks the dossier as finished. The client will no
                        longer be able to submit changes.
                    </DialogDescription>
                </DialogHeader>
                <Form
                    {...DossierCompletionController.store.form({
                        tenant: currentWorkspace,
                        dossier: dossierId,
                    })}
                    options={inlineDossierActionOptions}
                >
                    {({ errors, processing }) => (
                        <>
                            <InputError message={errors.dossier} />
                            <DialogFooter className="gap-2">
                                <DialogClose asChild>
                                    <Button type="button" variant="secondary">
                                        Cancel
                                    </Button>
                                </DialogClose>
                                <Button type="submit" disabled={processing}>
                                    Complete dossier
                                </Button>
                            </DialogFooter>
                        </>
                    )}
                </Form>
            </DialogContent>
        </Dialog>
    );
}

function ReviewCount({ label, value }: { label: string; value: number }) {
    return (
        <div className="rounded-md border px-3 py-2">
            <p className="text-xl font-semibold tabular-nums">{value}</p>
            <p className="text-xs text-muted-foreground">{label}</p>
        </div>
    );
}
