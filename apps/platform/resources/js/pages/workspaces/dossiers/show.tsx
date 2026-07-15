import { Head, setLayoutProps } from '@inertiajs/react';
import AddDocumentRequestCard from '@/components/dossiers/add-document-request-card';
import ApplyTemplateCard from '@/components/dossiers/apply-template-card';
import ClientAccessCard from '@/components/dossiers/client-access-card';
import DocumentRequestsCard from '@/components/dossiers/document-requests-card';
import PortalLinkBanner from '@/components/dossiers/portal-link-banner';
import Heading from '@/components/heading';
import { Badge } from '@/components/ui/badge';
import type { ApplyTemplateOption, Dossier } from '@/types';
import {
    index as dossierIndex,
    show as showDossier,
} from '@/routes/workspaces/dossiers';

/**
 * Staff dossier detail page — composes domain section components.
 */
export default function ShowDossier({
    dossier,
    templates = [],
    access_grant_token: accessGrantToken = null,
    access_grant_portal_url: accessGrantPortalUrl = null,
}: {
    dossier: Dossier;
    templates?: ApplyTemplateOption[];
    access_grant_token?: string | null;
    access_grant_portal_url?: string | null;
}) {
    // Dynamic trail: dossier title comes from page props.
    setLayoutProps({
        breadcrumbs: [
            {
                title: 'Dossiers',
                href: dossierIndex(),
            },
            {
                title: dossier.title,
                href: showDossier(dossier.id),
            },
        ],
    });

    return (
        <>
            <Head title={dossier.title} />

            <div className="mx-auto flex w-full max-w-5xl flex-col gap-6 p-4 md:p-8">
                <div className="flex flex-wrap items-start justify-between gap-4">
                    <Heading
                        title={dossier.title}
                        description={`${dossier.client.name} · ${dossier.client.email}`}
                    />
                    <Badge variant="secondary">
                        {dossier.status.replaceAll('_', ' ')}
                    </Badge>
                </div>

                {accessGrantPortalUrl && (
                    <PortalLinkBanner
                        portalUrl={accessGrantPortalUrl}
                        token={accessGrantToken}
                    />
                )}

                <div className="grid gap-6 lg:grid-cols-[1fr_20rem]">
                    <div className="space-y-6">
                        <DocumentRequestsCard
                            dossierId={dossier.id}
                            documentRequests={dossier.document_requests}
                        />

                        <ClientAccessCard
                            dossierId={dossier.id}
                            clientName={dossier.client.name}
                            clientEmail={dossier.client.email}
                            accessGrants={dossier.access_grants}
                        />
                    </div>

                    <div className="space-y-6">
                        <ApplyTemplateCard
                            dossierId={dossier.id}
                            templates={templates}
                        />
                        <AddDocumentRequestCard dossierId={dossier.id} />
                    </div>
                </div>
            </div>
        </>
    );
}
