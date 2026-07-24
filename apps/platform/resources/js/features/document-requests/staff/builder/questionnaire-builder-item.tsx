import { useSortable } from '@dnd-kit/sortable';
import { CSS } from '@dnd-kit/utilities';
import { GripVertical, Trash2 } from 'lucide-react';
import { useMediaQuery } from 'usehooks-ts';
import DocumentRequestController from '@/actions/App/Http/Controllers/Dossiers/DocumentRequestController';
import ConfirmDestroyDialog from '@/components/confirm-destroy-dialog';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { getQuestionnaireItemTypeDefinition } from '@/features/document-requests/questionnaire-item-type-registry';
import type { DocumentRequest } from '@/features/document-requests/types';
import { useCurrentWorkspace } from '@/hooks/use-current-workspace';
import { useTranslation } from '@/hooks/use-translation';
import { inlineDossierActionOptions } from '@/lib/inline-dossier-action-options';
import { cn } from '@/lib/utils';

const REDUCED_MOTION_QUERY = '(prefers-reduced-motion: reduce)';

/**
 * Compact sortable canvas row. Only the selected field expands its client preview.
 */
export default function QuestionnaireBuilderItem({
    dossierId,
    documentRequest,
    index,
    selected,
    canEdit,
    onSelect,
}: {
    dossierId: number;
    documentRequest: DocumentRequest;
    index: number;
    selected: boolean;
    canEdit: boolean;
    onSelect: (id: number) => void;
}) {
    const { t } = useTranslation();
    const currentWorkspace = useCurrentWorkspace();
    const prefersReducedMotion = useMediaQuery(REDUCED_MOTION_QUERY);
    const definition = getQuestionnaireItemTypeDefinition(documentRequest.type);
    const { BuilderPreview } = definition;
    const {
        attributes,
        listeners,
        setNodeRef,
        transform,
        transition,
        isDragging,
    } = useSortable({
        id: documentRequest.id,
        disabled: !canEdit,
        data: { source: 'canvas', id: documentRequest.id },
    });

    return (
        <li
            ref={setNodeRef}
            style={{
                transform: CSS.Transform.toString(transform),
                transition: prefersReducedMotion ? undefined : transition,
            }}
            className={cn(
                'list-none rounded-xl border bg-card shadow-xs transition-colors duration-150 motion-reduce:transition-none',
                selected
                    ? 'border-primary ring-[3px] ring-ring/40'
                    : 'border-border/70 hover:border-border',
                isDragging && 'relative z-10 opacity-80 shadow-md',
            )}
        >
            <div className="flex items-center gap-1.5 px-2 py-1.5">
                {canEdit ? (
                    <Button
                        type="button"
                        size="icon"
                        variant="ghost"
                        className="size-8 shrink-0 cursor-grab touch-manipulation touch-none active:cursor-grabbing"
                        aria-label={t('Drag to reorder')}
                        {...attributes}
                        {...listeners}
                    >
                        <GripVertical aria-hidden="true" />
                    </Button>
                ) : null}

                <button
                    type="button"
                    className="flex min-w-0 flex-1 items-center gap-2 rounded-lg px-1 py-1 text-left focus-visible:ring-[3px] focus-visible:ring-ring/50 focus-visible:outline-none"
                    aria-pressed={selected}
                    aria-label={documentRequest.title}
                    onClick={() => onSelect(documentRequest.id)}
                >
                    <span className="w-5 shrink-0 font-data text-xs text-muted-foreground tabular-nums">
                        {index + 1}
                    </span>
                    <span className="min-w-0 flex-1 truncate text-sm font-medium">
                        {documentRequest.title}
                    </span>
                    <Badge variant="outline" className="shrink-0">
                        {t(definition.label)}
                    </Badge>
                </button>

                {canEdit ? (
                    <ConfirmDestroyDialog
                        title={t('Delete request?')}
                        description={t(
                            'Remove “:title” from this dossier. Uploaded files for this item are kept for audit purposes.',
                            { title: documentRequest.title },
                        )}
                        confirmLabel={t('Delete')}
                        form={DocumentRequestController.destroy.form({
                            tenant: currentWorkspace,
                            dossier: dossierId,
                            documentRequest: documentRequest.id,
                        })}
                        options={inlineDossierActionOptions}
                        trigger={
                            <Button
                                type="button"
                                size="icon"
                                variant="ghost"
                                className="size-8 shrink-0"
                                aria-label={t('Delete :title', {
                                    title: documentRequest.title,
                                })}
                            >
                                <Trash2 aria-hidden="true" />
                            </Button>
                        }
                    />
                ) : null}
            </div>

            {selected ? (
                <div className="pointer-events-none border-t border-border/60 px-3 py-3">
                    <BuilderPreview
                        title={documentRequest.title}
                        instructions={documentRequest.instructions}
                    />
                </div>
            ) : null}
        </li>
    );
}
