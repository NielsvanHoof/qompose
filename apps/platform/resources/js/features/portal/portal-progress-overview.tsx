import { ArrowDown, CheckCircle2, Clock3 } from 'lucide-react';
import { Button } from '@/components/ui/button';
import type { PortalDossier } from '@/features/portal/types';
import { useTranslation } from '@/hooks/use-translation';
import { cn } from '@/lib/utils';

/**
 * Top-of-portal progress cue — one job: show what’s left and jump to the next item.
 */
export default function PortalProgressOverview({
    dossier,
    firmName,
}: {
    dossier: PortalDossier;
    firmName: string;
}) {
    const { t, locale } = useTranslation();
    const progress = dossier.progress;

    if (progress.total === 0) {
        return null;
    }

    const percentage = Math.round((progress.completed / progress.total) * 100);
    const isComplete = progress.remaining === 0;

    return (
        <section
            className={cn(
                'rounded-2xl border px-4 py-4 md:px-5 md:py-5',
                isComplete
                    ? 'border-success-border bg-success-muted'
                    : 'border-border/70 bg-card shadow-xs',
            )}
            aria-label={t('Dossier progress')}
        >
            <div className="grid gap-4 sm:grid-cols-[1fr_auto] sm:items-center">
                <div className="min-w-0 space-y-3">
                    <div className="flex items-start gap-3">
                        <span
                            className={cn(
                                'flex size-9 shrink-0 items-center justify-center rounded-full',
                                isComplete
                                    ? 'bg-success-muted text-success-foreground'
                                    : 'bg-primary/10 text-primary',
                            )}
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
                        <div className="min-w-0 space-y-1">
                            <h2 className="text-sm font-semibold tracking-tight text-pretty">
                                {isComplete
                                    ? t('Everything has been submitted')
                                    : t(':count items remaining', {
                                          count: progress.remaining,
                                      })}
                            </h2>
                            <p className="text-sm text-muted-foreground text-pretty">
                                {isComplete
                                    ? t(
                                          ':firm will review your information. You can return here if changes are requested.',
                                          { firm: firmName },
                                      )
                                    : dossier.due_date
                                      ? t('Please finish by :date.', {
                                            date: new Date(
                                                `${dossier.due_date}T00:00:00`,
                                            ).toLocaleDateString(locale),
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
                            className="h-full rounded-full bg-primary transition-[width] duration-300 motion-reduce:transition-none"
                            style={{ width: `${percentage}%` }}
                        />
                    </div>
                    <p className="font-data text-xs text-muted-foreground tabular-nums">
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
                    <Button asChild className="w-full sm:w-auto">
                        <a href={`#request-${progress.next_incomplete.id}`}>
                            {t('Continue')}
                            <ArrowDown aria-hidden="true" />
                        </a>
                    </Button>
                ) : null}
            </div>
        </section>
    );
}
