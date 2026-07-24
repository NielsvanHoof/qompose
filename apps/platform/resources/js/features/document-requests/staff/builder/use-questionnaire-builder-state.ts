import { useEffect, useRef, useState, useTransition } from 'react';
import { getQuestionnaireItemTypeDefinition } from '@/features/document-requests/questionnaire-item-type-registry';
import {
    type BuilderSaveStatus,
    useQuestionnaireBuilderActions,
} from '@/features/document-requests/staff/builder/use-questionnaire-builder-actions';
import type {
    DocumentRequest,
    QuestionnaireItemType,
} from '@/features/document-requests/types';
import { useTranslation } from '@/hooks/use-translation';

/**
 * Local builder state: selection, autosave status, and create/reorder/update helpers.
 */
export function useQuestionnaireBuilderState({
    dossierId,
    documentRequests,
    canEdit,
}: {
    dossierId: number;
    documentRequests: DocumentRequest[];
    canEdit: boolean;
}) {
    const { t } = useTranslation();
    const { createComponent, reorderComponents, updateComponent } =
        useQuestionnaireBuilderActions(dossierId);

    const [items, setItems] = useState(documentRequests);
    const [selectedId, setSelectedId] = useState<number | null>(
        documentRequests[0]?.id ?? null,
    );
    const [saveStatus, setSaveStatus] = useState<BuilderSaveStatus>('idle');
    const [announcement, setAnnouncement] = useState('');
    const [paletteOpen, setPaletteOpen] = useState(false);
    const [settingsOpen, setSettingsOpen] = useState(false);
    // Canvas index for the insert spinner; pending flag comes from useTransition.
    const [insertingAt, setInsertingAt] = useState<number | null>(null);
    const [isInsertPending, startInsertTransition] = useTransition();

    // Survives across the Inertia refresh so we can select the new field by position.
    const pendingInsertAtRef = useRef<number | null>(null);

    // Single sync path: refresh items, then resolve selection (pending insert or stale id).
    useEffect(() => {
        setItems(documentRequests);

        if (pendingInsertAtRef.current !== null) {
            const next = documentRequests[pendingInsertAtRef.current];

            if (next) {
                setSelectedId(next.id);
            }

            return;
        }

        setSelectedId((current) => {
            if (
                current !== null &&
                !documentRequests.some((item) => item.id === current)
            ) {
                return documentRequests[0]?.id ?? null;
            }

            return current;
        });
    }, [documentRequests]);

    const addAt = (type: QuestionnaireItemType, position?: number) => {
        if (!canEdit || isInsertPending) {
            return;
        }

        const definition = getQuestionnaireItemTypeDefinition(type);
        const insertAt = position ?? items.length;
        const title = t(definition.defaultTitle);

        // Urgent: park the spinner at the drop index before the transition starts.
        pendingInsertAtRef.current = insertAt;
        setInsertingAt(insertAt);
        setSaveStatus('saving');
        setAnnouncement(t('Adding component…'));
        setPaletteOpen(false);
        setSettingsOpen(true);

        // Transition owns the async create — isInsertPending stays true until it settles.
        startInsertTransition(async () => {
            const ok = await createComponent({
                type,
                title,
                position: insertAt,
            });

            if (!ok) {
                pendingInsertAtRef.current = null;
                setInsertingAt(null);
                setSaveStatus('error');
                setAnnouncement(t('Could not add the component. Try again.'));

                return;
            }

            // Inertia usually refreshed during the await; the effect already selected the real id.
            pendingInsertAtRef.current = null;
            setInsertingAt(null);
            setSaveStatus('saved');
            setAnnouncement(t('Component added.'));
        });
    };

    const handleReorder = async (orderedIds: number[]): Promise<boolean> => {
        setSaveStatus('saving');
        const ok = await reorderComponents(orderedIds);
        setSaveStatus(ok ? 'saved' : 'error');
        setAnnouncement(
            ok ? t('Form order saved.') : t('Could not save the new order.'),
        );

        return ok;
    };

    const selected =
        items.find((item) => item.id === selectedId) ??
        documentRequests.find((item) => item.id === selectedId) ??
        null;

    const handleSave = async (input: {
        type: QuestionnaireItemType;
        title: string;
        instructions: string | null;
    }): Promise<boolean> => {
        if (!selected) {
            return false;
        }

        setSaveStatus('saving');
        const ok = await updateComponent(selected.id, input);
        setSaveStatus(ok ? 'saved' : 'error');
        setAnnouncement(
            ok
                ? t('Field settings saved.')
                : t('Could not save field settings.'),
        );

        return ok;
    };

    const selectField = (id: number) => {
        setSelectedId(id);
        setSettingsOpen(true);
    };

    return {
        items,
        setItems,
        selectedId,
        setSelectedId: selectField,
        selected,
        saveStatus,
        announcement,
        paletteOpen,
        setPaletteOpen,
        settingsOpen,
        setSettingsOpen,
        insertingAt,
        isInsertPending,
        addAt,
        handleReorder,
        handleSave,
    };
}
