import { Head, Link } from '@inertiajs/react';
import { Plus } from 'lucide-react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { index as clientIndex } from '@/routes/workspaces/clients';
import {
    create,
    show,
} from '@/routes/workspaces/dossiers';

type Dossier = {
    id: number;
    client_name: string;
    title: string;
    reference: string | null;
    status: string;
};

export default function DossierIndex({ dossiers }: { dossiers: Dossier[] }) {
    return (
        <>
            <Head title="Dossiers" />

            <div className="mx-auto flex w-full max-w-5xl flex-col gap-6 p-4 md:p-8">
                <div className="flex flex-wrap items-start justify-between gap-4">
                    <div>
                        <h1 className="text-2xl font-semibold tracking-tight">
                            Dossiers
                        </h1>
                        <p className="mt-1 text-sm text-muted-foreground">
                            Request and review documents for each client.
                        </p>
                    </div>

                    <div className="flex gap-2">
                        <Button variant="outline" asChild>
                            <Link href={clientIndex()}>Clients</Link>
                        </Button>
                        <Button asChild>
                            <Link href={create()}>
                                <Plus />
                                New dossier
                            </Link>
                        </Button>
                    </div>
                </div>

                <Card>
                    <CardHeader>
                        <CardTitle>All dossiers</CardTitle>
                        <CardDescription>
                            {dossiers.length === 1
                                ? '1 dossier'
                                : `${dossiers.length} dossiers`}
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        {dossiers.length === 0 ? (
                            <p className="text-sm text-muted-foreground">
                                Create a dossier to start collecting documents.
                            </p>
                        ) : (
                            <div className="divide-y rounded-md border">
                                {dossiers.map((dossier) => (
                                    <Link
                                        key={dossier.id}
                                        href={show(dossier.id)}
                                        className="flex flex-wrap items-center justify-between gap-3 px-4 py-3 transition-colors hover:bg-muted/50"
                                    >
                                        <div>
                                            <p className="font-medium">
                                                {dossier.title}
                                            </p>
                                            <p className="text-sm text-muted-foreground">
                                                {dossier.client_name}
                                                {dossier.reference &&
                                                    ` · ${dossier.reference}`}
                                            </p>
                                        </div>
                                        <Badge variant="secondary">
                                            {dossier.status.replace('_', ' ')}
                                        </Badge>
                                    </Link>
                                ))}
                            </div>
                        )}
                    </CardContent>
                </Card>
            </div>
        </>
    );
}
