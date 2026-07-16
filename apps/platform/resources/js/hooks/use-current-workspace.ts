import { usePage } from '@inertiajs/react';
import type { Firm } from '@/types';

export function useCurrentWorkspace(): Firm {
    const { current_firm: currentWorkspace } = usePage().props;

    if (!currentWorkspace) {
        throw new Error('A current workspace is required for this page.');
    }

    return currentWorkspace;
}
