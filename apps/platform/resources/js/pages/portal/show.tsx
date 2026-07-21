import { Head } from '@inertiajs/react';
import StatusMessage from '@/components/status-message';
import PortalDocumentRequestsCard from '@/features/document-requests/portal/portal-document-requests-card';
import type { PortalDossier } from '@/features/portal/types';
import PortalShell from '@/layouts/portal/portal-shell';

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
    const subtitle = [
        `For ${dossier.client.name}`,
        dossier.reference ? `Ref. ${dossier.reference}` : null,
    ]
        .filter(Boolean)
        .join(' · ');

    const meta = dossier.expires_at
        ? `Access expires ${new Date(dossier.expires_at).toLocaleString()}`
        : undefined;

    return (
        <>
            <Head title={`${dossier.title} · ${firm.name}`} />

            <PortalShell
                firmName={firm.name}
                title={dossier.title}
                subtitle={subtitle}
                meta={meta}
            >
                {dossier.status === 'completed' && (
                    <StatusMessage>
                        This dossier has been completed. Your submitted
                        information is now read-only.
                    </StatusMessage>
                )}

                <PortalDocumentRequestsCard
                    firmName={firm.name}
                    documentRequests={dossier.document_requests}
                />

                <p className="text-center text-xs text-muted-foreground">
                    This is a secure upload page for {firm.name}. Do not share
                    this link.
                </p>
            </PortalShell>
        </>
    );
}
