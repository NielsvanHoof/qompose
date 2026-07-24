import { Head } from '@inertiajs/react';
import StatusMessage from '@/components/status-message';
import PortalDocumentRequestsCard from '@/features/document-requests/portal/portal-document-requests-card';
import PortalProgressOverview from '@/features/portal/portal-progress-overview';
import type { PortalDossier } from '@/features/portal/types';
import { useTranslation } from '@/hooks/use-translation';
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
    const { t, locale } = useTranslation();

    const subtitle = [
        t('For :name', { name: dossier.client.name }),
        dossier.reference
            ? t('Ref. :reference', { reference: dossier.reference })
            : null,
    ]
        .filter(Boolean)
        .join(' · ');

    const meta = dossier.expires_at
        ? t('Access expires :date', {
              date: new Date(dossier.expires_at).toLocaleString(locale),
          })
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
                        {t(
                            'This dossier has been completed. Your submitted information is now read-only.',
                        )}
                    </StatusMessage>
                )}

                <PortalProgressOverview
                    dossier={dossier}
                    firmName={firm.name}
                />

                <PortalDocumentRequestsCard
                    firmName={firm.name}
                    documentRequests={dossier.document_requests}
                    nextIncompleteRequestId={
                        dossier.progress.next_incomplete?.id ?? null
                    }
                />

                <p className="text-center text-xs text-muted-foreground text-pretty">
                    {t(
                        'This is a secure upload page for :firm. Do not share this link.',
                        { firm: firm.name },
                    )}
                </p>
            </PortalShell>
        </>
    );
}
