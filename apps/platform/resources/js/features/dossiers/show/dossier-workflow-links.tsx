import { Link } from '@inertiajs/react';
import { ArrowRight, Check } from 'lucide-react';
import {
    type DossierWorkflowStage,
    dossierStageCompletion,
    dossierStageHint,
    dossierStageLabel,
    STAGE_ORDER,
} from '@/features/dossiers/stage/dossier-stage';
import type { Dossier } from '@/features/dossiers/types';
import { useCurrentWorkspace } from '@/hooks/use-current-workspace';
import { useTranslation } from '@/hooks/use-translation';
import { cn } from '@/lib/utils';
import {
    builder as builderDossier,
    review as reviewDossier,
} from '@/routes/workspaces/dossiers';

/**
 * Overview hub links into Build Form / Send Invite / Review.
 * Invite stays on this page (#dossier-invite); the others open dedicated routes.
 */
export default function DossierWorkflowLinks({
    dossier,
}: {
    dossier: Dossier;
}) {
    const { t } = useTranslation();
    const currentWorkspace = useCurrentWorkspace();
    const completion = dossierStageCompletion(dossier);
    const hasFormItems = dossier.document_requests.length > 0;
    const routeArgs = {
        tenant: currentWorkspace,
        dossier: dossier.id,
    };

    const hrefFor = (stage: DossierWorkflowStage): string => {
        if (stage === 'prepare') {
            return builderDossier(routeArgs).url;
        }

        if (stage === 'review') {
            return reviewDossier(routeArgs).url;
        }

        return '#dossier-invite';
    };

    return (
        <nav aria-label={t('Dossier workflow progress')}>
            <ol className="grid gap-3 md:grid-cols-3">
                {STAGE_ORDER.map((stage, index) => {
                    const done = completion[stage] === 'completed';
                    // Review needs a form; invite section still explains the empty case.
                    const disabled = stage === 'review' && !hasFormItems;
                    const href = hrefFor(stage);
                    const isAnchor = stage === 'invite';
                    const className = cn(
                        'flex min-w-0 flex-col gap-2 rounded-xl border p-4 text-left transition-colors focus-visible:ring-[3px] focus-visible:ring-ring/50 focus-visible:outline-none',
                        disabled
                            ? 'cursor-not-allowed border-border/50 opacity-60'
                            : 'border-border/70 hover:border-border hover:bg-muted/40',
                        done && 'border-success-border/60 bg-success-muted/40',
                    );

                    const body = (
                        <>
                            <div className="flex items-center gap-2">
                                <span
                                    className={cn(
                                        'flex size-6 shrink-0 items-center justify-center rounded-full border text-xs',
                                        done
                                            ? 'border-success-border bg-success-muted text-success-foreground'
                                            : 'border-border bg-background text-muted-foreground',
                                    )}
                                >
                                    {done ? (
                                        <Check
                                            className="size-3.5"
                                            aria-hidden="true"
                                        />
                                    ) : (
                                        index + 1
                                    )}
                                </span>
                                <span className="min-w-0 flex-1 truncate text-sm font-semibold">
                                    {dossierStageLabel(stage, t)}
                                </span>
                                {!disabled && !isAnchor ? (
                                    <ArrowRight
                                        className="size-4 shrink-0 text-muted-foreground"
                                        aria-hidden="true"
                                    />
                                ) : null}
                            </div>
                            <p className="text-xs text-pretty text-muted-foreground">
                                {dossierStageHint(stage, t)}
                            </p>
                        </>
                    );

                    return (
                        <li key={stage} className="min-w-0">
                            {disabled ? (
                                <div
                                    className={className}
                                    title={t(
                                        'Add at least one questionnaire item first.',
                                    )}
                                >
                                    {body}
                                </div>
                            ) : isAnchor ? (
                                <a href={href} className={className}>
                                    {body}
                                </a>
                            ) : (
                                <Link href={href} className={className}>
                                    {body}
                                </Link>
                            )}
                        </li>
                    );
                })}
            </ol>
        </nav>
    );
}
