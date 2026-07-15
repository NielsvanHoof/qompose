import { Head } from '@inertiajs/react';
import CreateClientForm from '@/components/clients/create-client-form';
import Heading from '@/components/heading';
import {
    index as clientIndex,
    create as createClient,
} from '@/routes/workspaces/clients';

/**
 * Create a new client for the current firm.
 */
export default function CreateClient() {
    return (
        <>
            <Head title="New client" />

            <div className="mx-auto w-full max-w-xl p-4 md:p-8">
                <Heading
                    title="New client"
                    description="Add the person or organisation that will provide documents."
                />

                <CreateClientForm />
            </div>
        </>
    );
}

CreateClient.layout = {
    breadcrumbs: [
        {
            title: 'Clients',
            href: clientIndex(),
        },
        {
            title: 'New client',
            href: createClient(),
        },
    ],
};
