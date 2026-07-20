import {
    closestCenter,
    DndContext,
    type DragEndEvent,
    KeyboardSensor,
    PointerSensor,
    useSensor,
    useSensors,
} from '@dnd-kit/core';
import {
    SortableContext,
    sortableKeyboardCoordinates,
    useSortable,
    verticalListSortingStrategy,
} from '@dnd-kit/sortable';
import { CSS } from '@dnd-kit/utilities';
import { GripVertical } from 'lucide-react';
import { type ReactNode, useEffect, useState } from 'react';
import { Button } from '@/components/ui/button';
import { applyIdOrder, reorderIds, sameIdOrder } from '@/lib/reorder';
import { cn } from '@/lib/utils';

type SortableListProps<T extends { id: number }> = {
    items: T[];
    enabled?: boolean;
    onReorder: (orderedIds: number[]) => void;
    className?: string;
    /** Render one row; DragHandle is only for the grip (keeps inputs editable). */
    renderItem: (
        item: T,
        controls: { DragHandle: () => ReactNode },
    ) => ReactNode;
};

/**
 * Vertical drag-and-drop list. Posts order via onReorder after a drop.
 *
 * DnD mounts only after hydration — dnd-kit accessibility IDs differ between
 * SSR and the client and would otherwise cause hydration mismatches.
 */
export default function SortableList<T extends { id: number }>({
    items,
    enabled = true,
    onReorder,
    className,
    renderItem,
}: SortableListProps<T>) {
    // Local order for snappy UI while Inertia reloads props.
    const [orderedItems, setOrderedItems] = useState(items);
    // false on SSR + first client paint so markup matches, then enable DnD.
    const [isClient, setIsClient] = useState(false);

    useEffect(() => {
        setOrderedItems(items);
    }, [items]);

    useEffect(() => {
        setIsClient(true);
    }, []);

    const sensors = useSensors(
        useSensor(PointerSensor, {
            // Avoid stealing clicks from form controls.
            activationConstraint: { distance: 6 },
        }),
        useSensor(KeyboardSensor, {
            coordinateGetter: sortableKeyboardCoordinates,
        }),
    );

    const handleDragEnd = (event: DragEndEvent) => {
        const { active, over } = event;

        if (!over || active.id === over.id) {
            return;
        }

        const currentIds = orderedItems.map((item) => item.id);
        const nextIds = reorderIds(
            currentIds,
            Number(active.id),
            Number(over.id),
        );

        if (sameIdOrder(currentIds, nextIds)) {
            return;
        }

        setOrderedItems(applyIdOrder(orderedItems, nextIds));
        onReorder(nextIds);
    };

    // Static list until the client mounts (and when reordering is disabled).
    if (!enabled || !isClient) {
        return (
            <div className={className}>
                {orderedItems.map((item) => (
                    <div key={item.id}>
                        {renderItem(item, {
                            DragHandle: () =>
                                enabled ? <StaticDragHandle /> : null,
                        })}
                    </div>
                ))}
            </div>
        );
    }

    return (
        <DndContext
            sensors={sensors}
            collisionDetection={closestCenter}
            onDragEnd={handleDragEnd}
        >
            <SortableContext
                items={orderedItems.map((item) => item.id)}
                strategy={verticalListSortingStrategy}
            >
                <div className={className}>
                    {orderedItems.map((item) => (
                        <SortableRow key={item.id} id={item.id}>
                            {(DragHandle) => renderItem(item, { DragHandle })}
                        </SortableRow>
                    ))}
                </div>
            </SortableContext>
        </DndContext>
    );
}

/** Grip shown during SSR / pre-hydration — same look, no dnd-kit attrs. */
function StaticDragHandle() {
    return (
        <Button
            type="button"
            size="icon"
            variant="ghost"
            className="pointer-events-none cursor-grab touch-none"
            aria-label="Drag to reorder"
            tabIndex={-1}
        >
            <GripVertical />
        </Button>
    );
}

function SortableRow({
    id,
    children,
}: {
    id: number;
    children: (DragHandle: () => ReactNode) => ReactNode;
}) {
    const {
        attributes,
        listeners,
        setNodeRef,
        transform,
        transition,
        isDragging,
    } = useSortable({ id });

    const prefersReducedMotion =
        typeof window !== 'undefined' &&
        window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    const style = {
        transform: CSS.Transform.toString(transform),
        transition: prefersReducedMotion ? undefined : transition,
    };

    const DragHandle = () => (
        <Button
            type="button"
            size="icon"
            variant="ghost"
            className="cursor-grab touch-none active:cursor-grabbing"
            aria-label="Drag to reorder"
            {...attributes}
            {...listeners}
        >
            <GripVertical />
        </Button>
    );

    return (
        <div
            ref={setNodeRef}
            style={style}
            className={cn(
                isDragging && 'relative z-10 bg-background shadow-md',
            )}
        >
            {children(DragHandle)}
        </div>
    );
}
