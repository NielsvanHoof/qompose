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
import { useTranslation } from '@/hooks/use-translation';
import { inlineDossierActionOptions } from '@/lib/inline-dossier-action-options';

export default function DossierWorkflowCard({
    dossier,
    canReview,
}: {
    dossier: Dossier;
    canReview: boolean;
}) {
    const { t } = useTranslation();
    const summary = dossier.review_summary;

    return (
        <Card>
            <CardHeader>
                <CardTitle>{t('Review progress')}</CardTitle>
                <CardDescription>
                    {t(
                        'Review submitted items and complete the dossier when everything is approved.',
                    )}
                </CardDescription>
            </CardHeader>
            <CardContent className="space-y-4">
                <div className="grid grid-cols-2 gap-3 text-sm">
                    <ReviewCount label={t('Waiting')} value={summary.pending} />
                    <ReviewCount
                        label={t('Ready to review')}
                        value={summary.submitted}
                    />
                    <ReviewCount
                        label={t('Changes requested')}
                        value={summary.rejected}
                    />
                    <ReviewCount
                        label={t('Approved')}
                        value={summary.accepted}
                    />
                </div>

                {dossier.status === 'completed' ? (
                    <div className="flex items-center gap-2 rounded-md border border-success-border bg-success-muted px-3 py-2 text-sm font-medium text-success-foreground">
                        <CheckCircle2 className="size-4" aria-hidden="true" />
                        {t('Dossier completed')}
                    </div>
                ) : canReview ? (
                    <div className="space-y-2">
                        <CompleteDossierDialog
                            dossierId={dossier.id}
                            ready={dossier.ready_to_complete}
                        />
                        {!dossier.ready_to_complete && (
                            <p className="text-xs text-muted-foreground">
                                {t('Every item must be approved first.')}
                            </p>
                        )}
                    </div>
                ) : (
                    <p className="text-xs text-muted-foreground">
                        {t(
                            'A reviewer will complete the dossier after all items have been approved.',
                        )}
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
    const { t } = useTranslation();

    return (
        <Dialog>
            <DialogTrigger asChild>
                <Button type="button" className="w-full" disabled={!ready}>
                    <CheckCircle2 aria-hidden="true" />
                    {t('Complete dossier')}
                </Button>
            </DialogTrigger>
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>{t('Complete this dossier?')}</DialogTitle>
                    <DialogDescription>
                        {t(
                            'This marks the dossier as finished. The client will no longer be able to submit changes.',
                        )}
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
                                        {t('Cancel')}
                                    </Button>
                                </DialogClose>
                                <Button type="submit" disabled={processing}>
                                    {t('Complete dossier')}
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
