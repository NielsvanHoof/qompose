import { ArrowDown, CheckCircle2, Clock3 } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import type { PortalDossier } from '@/features/portal/types';
import { useTranslation } from '@/hooks/use-translation';

export default function PortalProgressOverview({
    dossier,
    firmName,
}: {
    dossier: PortalDossier;
    firmName: string;
}) {
    const { t } = useTranslation();
    const progress = dossier.progress;

    if (progress.total === 0) {
        return null;
    }

    const percentage = Math.round((progress.completed / progress.total) * 100);
    const isComplete = progress.total > 0 && progress.remaining === 0;

    return (
        <Card
            className={
                isComplete
                    ? 'border-success-border bg-success-muted'
                    : 'border-primary/15'
            }
        >
            <CardContent className="grid gap-5 p-5 sm:grid-cols-[1fr_auto] sm:items-center">
                <div className="min-w-0 space-y-3">
                    <div className="flex items-start gap-3">
                        <span
                            className={`flex size-9 shrink-0 items-center justify-center rounded-full ${
                                isComplete
                                    ? 'bg-success-muted text-success-foreground'
                                    : 'bg-primary/10 text-primary'
                            }`}
                        >
                            {isComplete ? (
                                <CheckCircle2
                                    className="size-5"
                                    aria-hidden="true"
                                />
                            ) : (
                                <Clock3 className="size-5" aria-hidden="true" />
                            )}
                        </span>
                        <div>
                            <h2 className="font-semibold">
                                {isComplete
                                    ? t('Everything has been submitted')
                                    : t(':count items remaining', {
                                          count: progress.remaining,
                                      })}
                            </h2>
                            <p className="text-sm text-muted-foreground">
                                {isComplete
                                    ? t(
                                          ':firm will review your information. You can return here if changes are requested.',
                                          { firm: firmName },
                                      )
                                    : dossier.due_date
                                      ? t('Please finish by :date.', {
                                            date: new Date(
                                                `${dossier.due_date}T00:00:00`,
                                            ).toLocaleDateString(),
                                        })
                                      : t(
                                            'Complete the next item below, then continue through the list.',
                                        )}
                            </p>
                        </div>
                    </div>

                    <div
                        role="progressbar"
                        aria-valuemin={0}
                        aria-valuemax={progress.total}
                        aria-valuenow={progress.completed}
                        aria-label={t('Dossier progress')}
                        className="h-2 overflow-hidden rounded-full bg-muted"
                    >
                        <div
                            className="h-full rounded-full bg-primary transition-[width] motion-reduce:transition-none"
                            style={{ width: `${percentage}%` }}
                        />
                    </div>
                    <p className="text-xs text-muted-foreground">
                        {t(
                            ':completed of :total complete · :approved approved',
                            {
                                completed: progress.completed,
                                total: progress.total,
                                approved: progress.approved,
                            },
                        )}
                    </p>
                </div>

                {progress.next_incomplete ? (
                    <Button asChild>
                        <a href={`#request-${progress.next_incomplete.id}`}>
                            {t('Next: :item', {
                                item: progress.next_incomplete.title,
                            })}
                            <ArrowDown aria-hidden="true" />
                        </a>
                    </Button>
                ) : null}
            </CardContent>
        </Card>
    );
}
