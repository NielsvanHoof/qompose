import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import type { DocumentRequest } from '@/features/document-requests/types';

/**
 * Invite-tab checklist: which questionnaire items still need a client response.
 */
export default function WaitingItemsSummary({
    documentRequests,
}: {
    documentRequests: DocumentRequest[];
}) {
    const waiting = documentRequests.filter(
        (request) =>
            request.status === 'pending' || request.status === 'rejected',
    );

    return (
        <Card>
            <CardHeader>
                <CardTitle>Still waiting</CardTitle>
                <CardDescription>
                    Items the client still needs to submit or update.
                </CardDescription>
            </CardHeader>
            <CardContent>
                {waiting.length === 0 ? (
                    <p className="text-sm text-muted-foreground">
                        Nothing waiting — switch to Review when uploads arrive.
                    </p>
                ) : (
                    <ul className="space-y-2 text-sm">
                        {waiting.map((request) => (
                            <li
                                key={request.id}
                                className="flex items-start justify-between gap-2 rounded-md border px-3 py-2"
                            >
                                <span className="font-medium">
                                    {request.title}
                                </span>
                                <span className="shrink-0 text-xs text-muted-foreground capitalize">
                                    {request.status === 'rejected'
                                        ? 'changes requested'
                                        : 'pending'}
                                </span>
                            </li>
                        ))}
                    </ul>
                )}
            </CardContent>
        </Card>
    );
}
