import { Head, Link, usePage } from '@inertiajs/react';
import { ClipboardList, FileClock, FolderOpen, Users } from 'lucide-react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { create as createClient } from '@/routes/workspaces/clients';
import {
    create as createDossier,
    index as dossierIndex,
    show as showDossier,
} from '@/routes/workspaces/dossiers';

type Metrics = {
    clients: number;
    open_dossiers: number;
    awaiting_client: number;
    in_review: number;
    outstanding_document_requests: number;
};

type RecentDossier = {
    id: number;
    title: string;
    reference: string | null;
    status: string;
    client_name: string;
    updated_at: string;
};

export default function WorkspaceDashboard({
    metrics,
    recent_dossiers: recentDossiers,
}: {
    metrics: Metrics;
    recent_dossiers: RecentDossier[];
}) {
    const { current_firm: currentFirm } = usePage().props;

    const metricCards = [
        {
            label: 'Clients',
            value: metrics.clients,
            description: 'People and organisations you support',
            icon: Users,
        },
        {
            label: 'Open dossiers',
            value: metrics.open_dossiers,
            description: 'Dossiers that still need attention',
            icon: FolderOpen,
        },
        {
            label: 'Awaiting client',
            value: metrics.awaiting_client,
            description: 'Waiting for requested information',
            icon: FileClock,
        },
        {
            label: 'Outstanding requests',
            value: metrics.outstanding_document_requests,
            description: 'Documents still missing or rejected',
            icon: ClipboardList,
        },
    ];

    return (
        <>
            <Head title="Dashboard" />

            <div className="mx-auto flex w-full max-w-6xl flex-col gap-6 p-4 md:p-8">
                <div className="flex flex-wrap items-start justify-between gap-4">
                    <div>
                        {currentFirm && (
                            <p className="text-sm font-medium text-muted-foreground">
                                {currentFirm.name}
                            </p>
                        )}
                        <h1 className="text-2xl font-semibold tracking-tight">
                            Dossier overview
                        </h1>
                        <p className="mt-1 text-sm text-muted-foreground">
                            Keep track of client requests and review work.
                        </p>
                    </div>

                    <div className="flex gap-2">
                        <Button variant="outline" asChild>
                            <Link href={createClient()}>New client</Link>
                        </Button>
                        <Button asChild>
                            <Link href={createDossier()}>New dossier</Link>
                        </Button>
                    </div>
                </div>

                <div className="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                    {metricCards.map((metric) => (
                        <Card key={metric.label}>
                            <CardHeader className="flex flex-row items-start justify-between space-y-0">
                                <div>
                                    <CardDescription>{metric.label}</CardDescription>
                                    <CardTitle className="mt-2 text-3xl">
                                        {metric.value}
                                    </CardTitle>
                                </div>
                                <metric.icon className="size-5 text-muted-foreground" />
                            </CardHeader>
                            <CardContent>
                                <p className="text-sm text-muted-foreground">
                                    {metric.description}
                                </p>
                            </CardContent>
                        </Card>
                    ))}
                </div>

                <div className="grid gap-4 lg:grid-cols-[1.5fr_1fr]">
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
                            {recentDossiers.length === 0 ? (
                                <p className="text-sm text-muted-foreground">
                                    Create a dossier to start collecting
                                    documents.
                                </p>
                            ) : (
                                <div className="divide-y rounded-md border">
                                    {recentDossiers.map((dossier) => (
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

                    <Card>
                        <CardHeader>
                            <CardTitle>Review queue</CardTitle>
                            <CardDescription>
                                Work that needs your attention now.
                            </CardDescription>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            <div>
                                <p className="text-3xl font-semibold">
                                    {metrics.in_review}
                                </p>
                                <p className="text-sm text-muted-foreground">
                                    dossiers currently in review
                                </p>
                            </div>
                            <div>
                                <p className="text-3xl font-semibold">
                                    {metrics.outstanding_document_requests}
                                </p>
                                <p className="text-sm text-muted-foreground">
                                    document requests to follow up on
                                </p>
                            </div>
                        </CardContent>
                    </Card>
                </div>
            </div>
        </>
    );
}
