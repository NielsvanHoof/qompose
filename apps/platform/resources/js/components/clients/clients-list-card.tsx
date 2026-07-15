import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import type { ClientSummary } from '@/types';

/**
 * Client list for the clients index page.
 */
export default function ClientsListCard({
    clients,
}: {
    clients: ClientSummary[];
}) {
    return (
        <Card>
            <CardHeader>
                <CardTitle>All clients</CardTitle>
                <CardDescription>
                    {clients.length === 1
                        ? '1 client'
                        : `${clients.length} clients`}
                </CardDescription>
            </CardHeader>
            <CardContent>
                {clients.length === 0 ? (
                    <p className="text-sm text-muted-foreground">
                        Add your first client to create a dossier.
                    </p>
                ) : (
                    <div className="divide-y rounded-md border">
                        {clients.map((client) => (
                            <div
                                key={client.id}
                                className="flex flex-wrap items-center justify-between gap-3 px-4 py-3"
                            >
                                <div>
                                    <p className="font-medium">{client.name}</p>
                                    <p className="text-sm text-muted-foreground">
                                        {client.email}
                                    </p>
                                </div>
                                <p className="text-sm text-muted-foreground">
                                    {client.dossiers_count}{' '}
                                    {client.dossiers_count === 1
                                        ? 'dossier'
                                        : 'dossiers'}
                                </p>
                            </div>
                        ))}
                    </div>
                )}
            </CardContent>
        </Card>
    );
}
