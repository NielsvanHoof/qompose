import { Head, setLayoutProps } from '@inertiajs/react';
import Heading from '@/components/heading';
import CreateClientForm from '@/features/clients/create-client-form';
import { useCurrentWorkspace } from '@/hooks/use-current-workspace';
import { useTranslation } from '@/hooks/use-translation';
import {
    index as clientIndex,
    create as createClient,
} from '@/routes/workspaces/clients';

/**
 * Create a new client for the current firm.
 */
export default function CreateClient() {
    const currentWorkspace = useCurrentWorkspace();
    const { t } = useTranslation();

    setLayoutProps({
        breadcrumbs: [
            {
                title: t('Clients'),
                href: clientIndex(currentWorkspace),
            },
            {
                title: t('New client'),
                href: createClient(currentWorkspace),
            },
        ],
    });

    return (
        <>
            <Head title={t('New client')} />

            <div className="mx-auto w-full max-w-xl p-4 md:p-8">
                <Heading
                    title={t('New client')}
                    description={t(
                        'Add the person or organisation that will provide documents.',
                    )}
                />

                <CreateClientForm />
            </div>
        </>
    );
}
