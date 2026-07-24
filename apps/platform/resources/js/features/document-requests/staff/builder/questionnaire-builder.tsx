import { DndContext, DragOverlay } from '@dnd-kit/core';
import {
    SortableContext,
    verticalListSortingStrategy,
} from '@dnd-kit/sortable';
import { useMemo } from 'react';
import { useMediaQuery } from 'usehooks-ts';
import QuestionnaireBuilderCanvas from '@/features/document-requests/staff/builder/questionnaire-builder-canvas';
import {
    BuilderPane,
    BuilderSheet,
    PaletteOverlay,
} from '@/features/document-requests/staff/builder/questionnaire-builder-chrome';
import QuestionnaireBuilderPalette from '@/features/document-requests/staff/builder/questionnaire-builder-palette';
import QuestionnaireBuilderSettings from '@/features/document-requests/staff/builder/questionnaire-builder-settings';
import {
    builderCollisionDetection,
    useQuestionnaireBuilderDnD,
} from '@/features/document-requests/staff/builder/use-questionnaire-builder-dnd';
import { useQuestionnaireBuilderState } from '@/features/document-requests/staff/builder/use-questionnaire-builder-state';
import type { DocumentRequest } from '@/features/document-requests/types';
import type { DossierStatus } from '@/features/dossiers/types';
import type { ApplyTemplateOption } from '@/features/questionnaires/types';
import { useTranslation } from '@/hooks/use-translation';

/** Desktop three-pane layout (matches Tailwind `xl`). */
const DESKTOP_QUERY = '(min-width: 1280px)';

/**
 * Form builder composition root: palette, canvas, and settings with shared DnD.
 */
export default function QuestionnaireBuilder({
    dossierId,
    dossierStatus,
    documentRequests,
    templates,
    canEdit,
}: {
    dossierId: number;
    dossierStatus: DossierStatus;
    documentRequests: DocumentRequest[];
    templates: ApplyTemplateOption[];
    canEdit: boolean;
}) {
    const { t } = useTranslation();
    const isDesktop = useMediaQuery(DESKTOP_QUERY);
    const state = useQuestionnaireBuilderState({
        dossierId,
        documentRequests,
        canEdit,
    });

    // Stable id list for SortableContext — only changes when items change.
    const sortableIds = useMemo(
        () => state.items.map((item) => item.id),
        [state.items],
    );

    const { activeDrag, sensors, handleDragStart, handleDragEnd } =
        useQuestionnaireBuilderDnD({
            items: state.items,
            setItems: state.setItems,
            canEdit: canEdit && !state.isInsertPending,
            onInsert: state.addAt,
            onReorder: state.handleReorder,
        });

    const palette = (
        <QuestionnaireBuilderPalette
            dossierId={dossierId}
            templates={templates}
            canEdit={
                canEdit &&
                dossierStatus !== 'completed' &&
                !state.isInsertPending
            }
            onAdd={(type) => {
                state.addAt(type);
            }}
        />
    );

    const settings = (
        <QuestionnaireBuilderSettings
            documentRequest={state.selected}
            canEdit={canEdit && !state.isInsertPending}
            saveStatus={state.saveStatus}
            onSave={state.handleSave}
        />
    );

    return (
        // Fill the viewport-locked layout; columns scroll internally.
        <div className="flex min-h-0 flex-1 flex-col gap-3">
            <div className="sr-only" aria-live="polite" aria-atomic="true">
                {state.announcement}
            </div>

            {/* One DnD context wraps palette + canvas so sheet drags still work. */}
            <DndContext
                sensors={sensors}
                collisionDetection={builderCollisionDetection}
                onDragStart={handleDragStart}
                onDragEnd={(event) => {
                    void handleDragEnd(event);
                }}
            >
                <div className="flex min-h-0 flex-1 flex-col gap-3">
                    {!isDesktop ? (
                        <div className="flex shrink-0 flex-wrap justify-end gap-2">
                            <BuilderSheet
                                open={state.paletteOpen}
                                onOpenChange={state.setPaletteOpen}
                                label={t('Components')}
                            >
                                {palette}
                            </BuilderSheet>
                            <BuilderSheet
                                open={state.settingsOpen}
                                onOpenChange={state.setSettingsOpen}
                                label={t('Field settings')}
                            >
                                {settings}
                            </BuilderSheet>
                        </div>
                    ) : null}

                    {/* grid-rows-1 so columns fill the flex-1 height (auto rows stay content-sized). */}
                    <div className="grid min-h-0 flex-1 gap-4 xl:grid-cols-[16rem_minmax(0,1fr)_20rem] xl:grid-rows-1 xl:items-stretch">
                        {isDesktop ? (
                            <BuilderPane>{palette}</BuilderPane>
                        ) : null}

                        <SortableContext
                            items={sortableIds}
                            strategy={verticalListSortingStrategy}
                        >
                            <QuestionnaireBuilderCanvas
                                dossierId={dossierId}
                                items={state.items}
                                selectedId={state.selectedId}
                                canEdit={canEdit && !state.isInsertPending}
                                insertingAt={state.insertingAt}
                                onSelect={state.setSelectedId}
                            />
                        </SortableContext>

                        {isDesktop ? (
                            <BuilderPane>{settings}</BuilderPane>
                        ) : null}
                    </div>
                </div>

                <DragOverlay>
                    {activeDrag?.kind === 'palette' ? (
                        <PaletteOverlay type={activeDrag.type} />
                    ) : null}
                </DragOverlay>
            </DndContext>
        </div>
    );
}
