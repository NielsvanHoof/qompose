import { Link } from '@inertiajs/react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import type { DossierSummary } from '@/types';
import {
    index as dossierIndex,
    show as showDossier,
} from '@/routes/workspaces/dossiers';

/**
 * Recently updated dossiers list for the workspace dashboard.
 */
export default function RecentDossiersCard({
    dossiers,
}: {
    dossiers: DossierSummary[];
}) {
    return (
        <Card>
            <CardHeader className="flex flex-row items-center justify-between space-y-0">
                <div>
                    <CardTitle>Recently updated dossiers</CardTitle>
                    <CardDescription>
                        The latest activity across your firm.
                    </CardDescription>
                </div>
                <Button variant="ghost" size="sm" asChild>
                    <Link href={dossierIndex()}>View all</Link>
                </Button>
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
                                href={showDossier(dossier.id)}
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
