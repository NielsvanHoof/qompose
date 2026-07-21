import { Check } from 'lucide-react';
import {
    type DossierStageTab,
    dossierStageLabel,
    dossierStageProgress,
    STAGE_ORDER,
} from '@/features/dossiers/stage/dossier-stage';
import type { Dossier } from '@/features/dossiers/types';
import { useTranslation } from '@/hooks/use-translation';
import { cn } from '@/lib/utils';

export default function DossierStageProgress({
    dossier,
    activeTab,
    onStageSelect,
}: {
    dossier: Dossier;
    activeTab: DossierStageTab;
    onStageSelect: (stage: DossierStageTab) => void;
}) {
    const { t } = useTranslation();
    const states = dossierStageProgress(dossier, activeTab);

    return (
        <nav aria-label={t('Dossier workflow progress')}>
            <ol className="flex items-center gap-2">
                {STAGE_ORDER.map((stage, index) => {
                    const state = states[stage];
                    const isLast = index === STAGE_ORDER.length - 1;

                    return (
                        <li
                            key={stage}
                            className={cn(
                                'flex min-w-0 flex-1 items-center gap-2',
                                !isLast &&
                                    'after:h-px after:flex-1 after:bg-border',
                            )}
                        >
                            <button
                                type="button"
                                onClick={() => onStageSelect(stage)}
                                aria-current={
                                    state === 'current' ? 'step' : undefined
                                }
                                className={cn(
                                    'flex min-w-0 items-center gap-2 rounded-md px-1 py-0.5 text-left text-sm transition-colors hover:bg-muted/60 focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 focus-visible:outline-none',
                                    state === 'current' &&
                                        'font-medium text-foreground',
                                    state === 'upcoming' &&
                                        'text-muted-foreground',
                                )}
                            >
                                <span
                                    className={cn(
                                        'flex size-6 shrink-0 items-center justify-center rounded-full border text-xs',
                                        state === 'completed' &&
                                            'border-success-border bg-success-muted text-success-foreground',
                                        state === 'current' &&
                                            'border-primary bg-primary text-primary-foreground',
                                        state === 'upcoming' &&
                                            'border-border bg-background text-muted-foreground',
                                    )}
                                >
                                    {state === 'completed' ? (
                                        <Check
                                            className="size-3.5"
                                            aria-hidden="true"
                                        />
                                    ) : (
                                        index + 1
                                    )}
                                </span>
                                <span className="truncate">
                                    {dossierStageLabel(stage, t)}
                                </span>
                            </button>
                        </li>
                    );
                })}
            </ol>
        </nav>
    );
}
