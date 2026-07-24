import { Head, setLayoutProps } from '@inertiajs/react';
import Heading from '@/components/heading';
import EditClientForm from '@/features/clients/manage/edit-client-form';
import type { ClientDetails } from '@/features/clients/types';
import { useCurrentWorkspace } from '@/hooks/use-current-workspace';
import { useTranslation } from '@/hooks/use-translation';
import {
    index as clientIndex,
    edit as editClient,
    show as showClient,
} from '@/routes/workspaces/clients';

export default function EditClient({
    client,
}: {
    client: Pick<ClientDetails, 'id' | 'name' | 'email'>;
}) {
    const currentWorkspace = useCurrentWorkspace();
    const { t } = useTranslation();

    setLayoutProps({
        breadcrumbs: [
            { title: t('Clients'), href: clientIndex(currentWorkspace) },
            {
                title: client.name,
                href: showClient({
                    tenant: currentWorkspace,
                    client: client.id,
                }),
            },
            {
                title: t('Edit'),
                href: editClient({
                    tenant: currentWorkspace,
                    client: client.id,
                }),
            },
        ],
    });

    return (
        <>
            <Head title={t('Edit :name', { name: client.name })} />

            <div className="mx-auto w-full max-w-xl p-4 md:p-8">
                <Heading
                    level={1}
                    title={t('Edit client')}
                    description={t(
                        'Keep contact details accurate before sending new portal invitations.',
                    )}
                />
                <EditClientForm client={client} />
            </div>
        </>
    );
}
