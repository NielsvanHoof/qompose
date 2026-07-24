import {
    type CollisionDetection,
    closestCenter,
    type DragEndEvent,
    type DragStartEvent,
    KeyboardSensor,
    PointerSensor,
    pointerWithin,
    rectIntersection,
    useSensor,
    useSensors,
} from '@dnd-kit/core';
import { sortableKeyboardCoordinates } from '@dnd-kit/sortable';
import { useState } from 'react';
import { BUILDER_CANVAS_DROPPABLE_ID } from '@/features/document-requests/staff/builder/questionnaire-builder-canvas';
import type {
    DocumentRequest,
    QuestionnaireItemType,
} from '@/features/document-requests/types';
import { applyIdOrder, reorderIds, sameIdOrder } from '@/lib/reorder';

export type ActiveBuilderDrag =
    | { kind: 'palette'; type: QuestionnaireItemType }
    | { kind: 'canvas'; id: number }
    | null;

/**
 * Prefer pointer hits so DragOverlay palette drags land on the canvas.
 * When both the canvas and a field are under the pointer, keep the field.
 */
export const builderCollisionDetection: CollisionDetection = (args) => {
    const pointerCollisions = pointerWithin(args);

    if (pointerCollisions.length > 0) {
        const overField = pointerCollisions.find(
            (collision) => collision.id !== BUILDER_CANVAS_DROPPABLE_ID,
        );

        return overField ? [overField] : pointerCollisions;
    }

    const intersections = rectIntersection(args);

    if (intersections.length > 0) {
        const overField = intersections.find(
            (collision) => collision.id !== BUILDER_CANVAS_DROPPABLE_ID,
        );

        return overField ? [overField] : intersections;
    }

    return closestCenter(args);
};

/**
 * Shared drag sensors and end-handlers for palette drops and canvas reorders.
 */
export function useQuestionnaireBuilderDnD({
    items,
    setItems,
    canEdit,
    onInsert,
    onReorder,
}: {
    items: DocumentRequest[];
    setItems: (items: DocumentRequest[]) => void;
    canEdit: boolean;
    onInsert: (type: QuestionnaireItemType, position: number) => void;
    onReorder: (orderedIds: number[]) => Promise<boolean>;
}) {
    const [activeDrag, setActiveDrag] = useState<ActiveBuilderDrag>(null);

    const sensors = useSensors(
        useSensor(PointerSensor, {
            // Small movement before drag so Add clicks still work on the card.
            activationConstraint: { distance: 8 },
        }),
        useSensor(KeyboardSensor, {
            coordinateGetter: sortableKeyboardCoordinates,
        }),
    );

    const handleDragStart = (event: DragStartEvent) => {
        const data = event.active.data.current;

        if (data?.source === 'palette') {
            setActiveDrag({
                kind: 'palette',
                type: data.type as QuestionnaireItemType,
            });

            return;
        }

        setActiveDrag({ kind: 'canvas', id: Number(event.active.id) });
    };

    const handleDragEnd = async (event: DragEndEvent) => {
        const { active, over } = event;
        setActiveDrag(null);

        if (!canEdit || !over) {
            return;
        }

        const activeData = active.data.current;

        if (activeData?.source === 'palette') {
            const type = activeData.type as QuestionnaireItemType;
            let position = items.length;

            // Drop on a field inserts before it; drop on empty canvas appends.
            if (over.id !== BUILDER_CANVAS_DROPPABLE_ID) {
                const overIndex = items.findIndex(
                    (item) => item.id === Number(over.id),
                );
                position = overIndex >= 0 ? overIndex : items.length;
            }

            onInsert(type, position);

            return;
        }

        if (over.id === BUILDER_CANVAS_DROPPABLE_ID || active.id === over.id) {
            return;
        }

        const currentIds = items.map((item) => item.id);
        const nextIds = reorderIds(
            currentIds,
            Number(active.id),
            Number(over.id),
        );

        if (sameIdOrder(currentIds, nextIds)) {
            return;
        }

        const previous = items;
        setItems(applyIdOrder(previous, nextIds));
        const persisted = await onReorder(nextIds);

        if (!persisted) {
            setItems(previous);
        }
    };

    return {
        activeDrag,
        sensors,
        handleDragStart,
        handleDragEnd,
    };
}
