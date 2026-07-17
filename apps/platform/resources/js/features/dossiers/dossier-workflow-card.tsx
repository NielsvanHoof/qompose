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
import type { Dossier } from '@/features/dossiers/types';
import { useCurrentWorkspace } from '@/hooks/use-current-workspace';

export default function DossierWorkflowCard({
    dossier,
    canReview,
}: {
    dossier: Dossier;
    canReview: boolean;
}) {
    const currentWorkspace = useCurrentWorkspace();
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
                    <div className="flex items-center gap-2 rounded-md border border-emerald-500/30 bg-emerald-500/5 px-3 py-2 text-sm font-medium text-emerald-700 dark:text-emerald-300">
                        <CheckCircle2 className="size-4" />
                        Dossier completed
                    </div>
                ) : canReview ? (
                    <Form
                        {...DossierCompletionController.store.form({
                            tenant: currentWorkspace,
                            dossier: dossier.id,
                        })}
                    >
                        {({ errors, processing }) => (
                            <div className="space-y-2">
                                <Button
                                    type="submit"
                                    className="w-full"
                                    disabled={
                                        processing || !dossier.ready_to_complete
                                    }
                                >
                                    <CheckCircle2 />
                                    Complete dossier
                                </Button>
                                <InputError message={errors.dossier} />
                                {!dossier.ready_to_complete && (
                                    <p className="text-xs text-muted-foreground">
                                        Every item must be approved first.
                                    </p>
                                )}
                            </div>
                        )}
                    </Form>
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

function ReviewCount({ label, value }: { label: string; value: number }) {
    return (
        <div className="rounded-md border px-3 py-2">
            <p className="text-xl font-semibold">{value}</p>
            <p className="text-xs text-muted-foreground">{label}</p>
        </div>
    );
}
