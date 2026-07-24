import { router, useHttp } from '@inertiajs/react';
import { useCallback } from 'react';
import DocumentRequestController from '@/actions/App/Http/Controllers/Dossiers/DocumentRequestController';
import type { QuestionnaireItemType } from '@/features/document-requests/types';
import { useCurrentWorkspace } from '@/hooks/use-current-workspace';
import { inlineDossierActionOptions } from '@/lib/inline-dossier-action-options';

export type BuilderSaveStatus = 'idle' | 'saving' | 'saved' | 'error';

/**
 * Shared create / update / reorder helpers for the questionnaire builder.
 */
export function useQuestionnaireBuilderActions(dossierId: number) {
    const currentWorkspace = useCurrentWorkspace();
    const { post, setData } = useHttp({
        document_request_ids: [] as number[],
    });

    const createComponent = useCallback(
        async (input: {
            type: QuestionnaireItemType;
            title: string;
            position?: number;
        }): Promise<boolean> => {
            let failed = false;

            try {
                await new Promise<void>((resolve, reject) => {
                    router.post(
                        DocumentRequestController.store.url({
                            tenant: currentWorkspace,
                            dossier: dossierId,
                        }),
                        {
                            type: input.type,
                            title: input.title,
                            ...(input.position !== undefined
                                ? { position: input.position }
                                : {}),
                        },
                        {
                            ...inlineDossierActionOptions,
                            onError: () => {
                                failed = true;
                                resolve();
                            },
                            onSuccess: () => resolve(),
                            onCancel: () => reject(new Error('cancelled')),
                        },
                    );
                });

                return !failed;
            } catch {
                return false;
            }
        },
        [currentWorkspace, dossierId],
    );

    const reorderComponents = useCallback(
        async (orderedIds: number[]): Promise<boolean> => {
            setData('document_request_ids', orderedIds);

            let failed = false;

            try {
                await post(
                    DocumentRequestController.reorder.url({
                        tenant: currentWorkspace,
                        dossier: dossierId,
                    }),
                    {
                        onError: () => {
                            failed = true;
                        },
                    },
                );

                return !failed;
            } catch {
                return false;
            }
        },
        [currentWorkspace, dossierId, post, setData],
    );

    const updateComponent = useCallback(
        async (
            documentRequestId: number,
            input: {
                type: QuestionnaireItemType;
                title: string;
                instructions: string | null;
            },
        ): Promise<boolean> => {
            let failed = false;

            try {
                await new Promise<void>((resolve, reject) => {
                    router.put(
                        DocumentRequestController.update.url({
                            tenant: currentWorkspace,
                            dossier: dossierId,
                            documentRequest: documentRequestId,
                        }),
                        {
                            type: input.type,
                            title: input.title,
                            instructions: input.instructions,
                        },
                        {
                            ...inlineDossierActionOptions,
                            onError: () => {
                                failed = true;
                                resolve();
                            },
                            onSuccess: () => resolve(),
                            onCancel: () => reject(new Error('cancelled')),
                        },
                    );
                });

                return !failed;
            } catch {
                return false;
            }
        },
        [currentWorkspace, dossierId],
    );

    return {
        createComponent,
        reorderComponents,
        updateComponent,
    };
}
