import { Head } from '@inertiajs/react';
import PortalDocumentRequestsCard from '@/features/document-requests/portal/portal-document-requests-card';
import type { PortalDossier } from '@/features/portal/types';

/**
 * Client portal dossier page — magic-link access, no staff layout.
 */
export default function PortalShow({
    firm,
    dossier,
}: {
    firm: { name: string };
    dossier: PortalDossier;
}) {
    return (
        <>
            <Head title={`${dossier.title} · ${firm.name}`} />

            <div className="min-h-svh bg-background">
                <header className="border-b">
                    <div className="mx-auto flex w-full max-w-3xl flex-col gap-1 px-4 py-6 md:px-8">
                        <p className="text-sm text-muted-foreground">
                            {firm.name}
                        </p>
                        <h1 className="text-2xl font-semibold tracking-tight">
                            {dossier.title}
                        </h1>
                        <p className="text-sm text-muted-foreground">
                            For {dossier.client.name}
                            {dossier.reference
                                ? ` · Ref. ${dossier.reference}`
                                : ''}
                        </p>
                        {dossier.expires_at && (
                            <p className="text-xs text-muted-foreground">
                                Access expires{' '}
                                {new Date(dossier.expires_at).toLocaleString()}
                            </p>
                        )}
                    </div>
                </header>

                <main className="mx-auto flex w-full max-w-3xl flex-col gap-6 px-4 py-8 md:px-8">
                    {dossier.status === 'completed' && (
                        <div className="rounded-md border border-emerald-500/30 bg-emerald-500/5 px-4 py-3 text-sm text-emerald-700 dark:text-emerald-300">
                            This dossier has been completed. Your submitted
                            information is now read-only.
                        </div>
                    )}

                    <PortalDocumentRequestsCard
                        firmName={firm.name}
                        documentRequests={dossier.document_requests}
                    />

                    <p className="text-center text-xs text-muted-foreground">
                        This is a secure upload page for {firm.name}. Do not
                        share this link.
                    </p>
                </main>
            </div>
        </>
    );
}
