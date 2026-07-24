import { useDroppable } from '@dnd-kit/core';
import { Fragment } from 'react';
import EmptyState from '@/components/empty-state';
import { BuilderInsertPlaceholder } from '@/features/document-requests/staff/builder/questionnaire-builder-chrome';
import QuestionnaireBuilderItem from '@/features/document-requests/staff/builder/questionnaire-builder-item';
import type { DocumentRequest } from '@/features/document-requests/types';
import { useTranslation } from '@/hooks/use-translation';
import { cn } from '@/lib/utils';

export const BUILDER_CANVAS_DROPPABLE_ID = 'builder-canvas';

/**
 * Droppable canvas surface that renders sortable builder items.
 * DnD context and SortableContext live in the parent builder root.
 */
export default function QuestionnaireBuilderCanvas({
    dossierId,
    items,
    selectedId,
    canEdit,
    insertingAt = null,
    onSelect,
}: {
    dossierId: number;
    items: DocumentRequest[];
    selectedId: number | null;
    canEdit: boolean;
    /** Index of the in-flight create spinner, or null when idle. */
    insertingAt?: number | null;
    onSelect: (id: number) => void;
}) {
    const { t } = useTranslation();
    const { setNodeRef, isOver } = useDroppable({
        id: BUILDER_CANVAS_DROPPABLE_ID,
    });

    const isEmpty = items.length === 0 && insertingAt === null;

    return (
        <section
            className="flex h-full min-h-0 min-w-0 flex-col"
            aria-label={t('Client form canvas')}
        >
            {/* Sticky toolbar stays visible while the field list scrolls. */}
            <div className="sticky top-0 z-10 mb-3 space-y-1 bg-background/95 pb-2 backdrop-blur-sm">
                <div className="flex flex-wrap items-baseline justify-between gap-2">
                    <h3 className="text-sm font-semibold tracking-tight text-pretty">
                        {t('Client form')}
                    </h3>
                    <p className="font-data text-xs text-muted-foreground">
                        {t(':count fields', { count: items.length })}
                    </p>
                </div>
                <p className="text-xs text-muted-foreground">
                    {t(
                        'This is the form the client will fill in. Drag components here to build it.',
                    )}
                </p>
            </div>

            <div
                ref={setNodeRef}
                className={cn(
                    'min-h-64 flex-1 overflow-y-auto overscroll-contain rounded-2xl border border-dashed p-2 transition-colors duration-150 motion-reduce:transition-none md:p-3',
                    isOver
                        ? 'border-primary bg-primary/5'
                        : 'border-border/70 bg-muted/20',
                )}
            >
                {isEmpty ? (
                    <EmptyState
                        variant="panel"
                        title={t('Drop a component to start the form')}
                        description={t(
                            'Add a file upload, text, date, number, or yes/no question.',
                        )}
                    />
                ) : (
                    <ol className="space-y-2">
                        {items.map((item, index) => (
                            <Fragment key={item.id}>
                                {/* Spinner sits at the drop index ahead of existing fields. */}
                                {insertingAt === index ? (
                                    <BuilderInsertPlaceholder index={index} />
                                ) : null}
                                <QuestionnaireBuilderItem
                                    dossierId={dossierId}
                                    documentRequest={item}
                                    index={index}
                                    selected={selectedId === item.id}
                                    canEdit={canEdit}
                                    onSelect={onSelect}
                                />
                            </Fragment>
                        ))}
                        {insertingAt === items.length ? (
                            <BuilderInsertPlaceholder index={items.length} />
                        ) : null}
                    </ol>
                )}
            </div>
        </section>
    );
}
