import { useEffect, useState } from 'react';
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
    const [pendingSelectPosition, setPendingSelectPosition] = useState<
        number | null
    >(null);
    const [paletteOpen, setPaletteOpen] = useState(false);
    const [settingsOpen, setSettingsOpen] = useState(false);

    useEffect(() => {
        setItems(documentRequests);

        if (pendingSelectPosition !== null) {
            const next = documentRequests[pendingSelectPosition];

            if (next) {
                setSelectedId(next.id);
            }

            setPendingSelectPosition(null);

            return;
        }

        if (
            selectedId !== null &&
            !documentRequests.some((item) => item.id === selectedId)
        ) {
            setSelectedId(documentRequests[0]?.id ?? null);
        }
    }, [documentRequests, pendingSelectPosition, selectedId]);

    const addAt = async (type: QuestionnaireItemType, position?: number) => {
        if (!canEdit) {
            return;
        }

        const definition = getQuestionnaireItemTypeDefinition(type);
        const insertAt = position ?? items.length;
        setSaveStatus('saving');
        setAnnouncement(t('Adding component…'));

        const ok = await createComponent({
            type,
            title: t(definition.defaultTitle),
            position: insertAt,
        });

        if (!ok) {
            setSaveStatus('error');
            setAnnouncement(t('Could not add the component. Try again.'));

            return;
        }

        setPendingSelectPosition(insertAt);
        setSaveStatus('saved');
        setAnnouncement(t('Component added.'));
        setPaletteOpen(false);
        setSettingsOpen(true);
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
        addAt,
        handleReorder,
        handleSave,
    };
}
