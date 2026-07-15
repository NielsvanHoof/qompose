import { Head, Link } from '@inertiajs/react';
import { Plus } from 'lucide-react';
import ClientsListCard from '@/components/clients/clients-list-card';
import { Button } from '@/components/ui/button';
import {
    index as clientIndex,
    create as createClient,
} from '@/routes/workspaces/clients';
import { create as createDossier } from '@/routes/workspaces/dossiers';
import type { ClientSummary } from '@/types';

/**
 * Clients index — people and organisations for document collection.
 */
export default function ClientIndex({ clients }: { clients: ClientSummary[] }) {
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

                <ClientsListCard clients={clients} />
            </div>
        </>
    );
}

ClientIndex.layout = {
    breadcrumbs: [
        {
            title: 'Clients',
            href: clientIndex(),
        },
    ],
};
