import { Head } from '@inertiajs/react';
import FirmPickerGrid from '@/components/firms/firm-picker-grid';
import Heading from '@/components/heading';
import { dashboard } from '@/routes';
import type { Firm } from '@/types';

type PageProps = {
    firms: Firm[];
};

/**
 * Global dashboard — pick a firm to enter its workspace.
 */
export default function Dashboard({ firms }: PageProps) {
    return (
        <>
            <Head title="Dashboard" />

            <div className="mx-auto flex w-full max-w-5xl flex-col gap-8 p-4 md:p-8">
                <Heading
                    title="Choose a firm"
                    description="Choose the firm whose client dossiers you want to manage."
                />

                <FirmPickerGrid firms={firms} />
            </div>
        </>
    );
}

Dashboard.layout = {
    breadcrumbs: [
        {
            title: 'Dashboard',
            href: dashboard(),
        },
    ],
};
