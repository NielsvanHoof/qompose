import { Head, Link } from '@inertiajs/react';
import { Plus } from 'lucide-react';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { create as createClient } from '@/routes/workspaces/clients';
import { create as createDossier } from '@/routes/workspaces/dossiers';

type Tenant = {
    slug: string;
};

type Client = {
    id: number;
    name: string;
    email: string;
    dossiers_count: number;
};

export default function ClientIndex({
    tenant,
    clients,
}: {
    tenant: Tenant;
    clients: Client[];
}) {
    return (
        <>
            <Head title="Clients" />

            <div className="mx-auto flex w-full max-w-5xl flex-col gap-6 p-4 md:p-8">
                <div className="flex flex-wrap items-start justify-between gap-4">
                    <div>
                        <h1 className="text-2xl font-semibold tracking-tight">
                            Clients
                        </h1>
                        <p className="mt-1 text-sm text-muted-foreground">
                            Manage the people and organisations you collect
                            documents from.
                        </p>
                    </div>

                    <div className="flex gap-2">
                        <Button variant="outline" asChild>
                            <Link href={createDossier()}>New dossier</Link>
                        </Button>
                        <Button asChild>
                            <Link href={createClient()}>
                                <Plus />
                                New client
                            </Link>
                        </Button>
                    </div>
                </div>

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
                                            <p className="font-medium">
                                                {client.name}
                                            </p>
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
            </div>
        </>
    );
}
