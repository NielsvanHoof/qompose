import { router } from '@inertiajs/react';
import DocumentRequestController from '@/actions/App/Http/Controllers/Dossiers/DocumentRequestController';
import SortableList from '@/components/sortable/sortable-list';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import DocumentRequestListItem from '@/features/document-requests/staff/document-request-list-item';
import type { DocumentRequest } from '@/features/document-requests/types';
import type { DossierStatus } from '@/features/dossiers/types';
import { useCurrentWorkspace } from '@/hooks/use-current-workspace';

/**
 * Staff questionnaire editor: edit, drag-reorder, delete, and collect answers.
 */
export default function DocumentRequestsCard({
    dossierId,
    dossierStatus,
    documentRequests,
    canManage,
    canReview,
    canDownload,
}: {
    dossierId: number;
    dossierStatus: DossierStatus;
    documentRequests: DocumentRequest[];
    canManage: boolean;
    canReview: boolean;
    canDownload: boolean;
}) {
    const currentWorkspace = useCurrentWorkspace();
    const canEdit = canManage && dossierStatus !== 'completed';

    const persistOrder = (orderedIds: number[]) => {
        router.post(
            DocumentRequestController.reorder.url({
                tenant: currentWorkspace,
                dossier: dossierId,
            }),
            { document_request_ids: orderedIds },
            { preserveScroll: true },
        );
    };

    return (
        <Card>
            <CardHeader>
                <CardTitle>Questionnaire</CardTitle>
                <CardDescription>
                    Tailor items for this dossier. Drag the grip to reorder.
                    Clients upload via the portal. Staff can upload on behalf of
                    a client when needed (for example email or walk-in).
                </CardDescription>
            </CardHeader>
            <CardContent>
                {documentRequests.length === 0 ? (
                    <p className="text-sm text-muted-foreground">
                        No items yet. Apply a template or add a request.
                    </p>
                ) : (
                    <SortableList
                        items={documentRequests}
                        enabled={canEdit}
                        onReorder={persistOrder}
                        className="divide-y rounded-md border"
                        renderItem={(documentRequest, { DragHandle }) => (
                            <DocumentRequestListItem
                                dossierId={dossierId}
                                dossierStatus={dossierStatus}
                                documentRequest={documentRequest}
                                canEdit={canEdit}
                                canReview={
                                    canReview && dossierStatus !== 'completed'
                                }
                                canDownload={canDownload}
                                DragHandle={DragHandle}
                            />
                        )}
                    />
                )}
            </CardContent>
        </Card>
    );
}
