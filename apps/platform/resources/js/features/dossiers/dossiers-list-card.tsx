import { Link } from '@inertiajs/react';
import EmptyState from '@/components/empty-state';
import { Badge } from '@/components/ui/badge';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import type { DossierSummary } from '@/features/dossiers/types';
import { useCurrentWorkspace } from '@/hooks/use-current-workspace';
import { show } from '@/routes/workspaces/dossiers';

/**
 * Full dossier list for the dossiers index page.
 */
export default function DossiersListCard({
    dossiers,
}: {
    dossiers: DossierSummary[];
}) {
    const currentWorkspace = useCurrentWorkspace();

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
                    <EmptyState title="Create a dossier to start collecting documents." />
                ) : (
                    <div className="divide-y rounded-md border">
                        {dossiers.map((dossier) => (
                            <Link
                                key={dossier.id}
                                href={show({
                                    tenant: currentWorkspace,
                                    dossier: dossier.id,
                                })}
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
