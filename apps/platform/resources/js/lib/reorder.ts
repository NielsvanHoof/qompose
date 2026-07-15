/**
 * Move one id to a new index and return the full ordered id list.
 */
export function reorderIds(
    ids: number[],
    activeId: number,
    overId: number,
): number[] {
    const oldIndex = ids.indexOf(activeId);
    const newIndex = ids.indexOf(overId);

    if (oldIndex < 0 || newIndex < 0 || oldIndex === newIndex) {
        return ids;
    }

    const next = [...ids];
    const [moved] = next.splice(oldIndex, 1);
    next.splice(newIndex, 0, moved);

    return next;
}

/** True when both sequences contain the same ids in the same order. */
export function sameIdOrder(a: number[], b: number[]): boolean {
    return a.length === b.length && a.every((id, index) => id === b[index]);
}

/**
 * Reorder a list of items by id using a new id sequence.
 */
export function applyIdOrder<T extends { id: number }>(
    items: T[],
    orderedIds: number[],
): T[] {
    const byId = new Map(items.map((item) => [item.id, item]));

    return orderedIds
        .map((id) => byId.get(id))
        .filter((item): item is T => item !== undefined);
}
