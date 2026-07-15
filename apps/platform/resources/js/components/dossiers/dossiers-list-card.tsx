import { Link } from '@inertiajs/react';
import { Badge } from '@/components/ui/badge';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { show } from '@/routes/workspaces/dossiers';
import type { DossierSummary } from '@/types';

/**
 * Full dossier list for the dossiers index page.
 */
export default function DossiersListCard({
    dossiers,
}: {
    dossiers: DossierSummary[];
}) {
    return (
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
    );
}
