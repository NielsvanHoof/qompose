import { Head, router } from '@inertiajs/react';
import { ArrowRight, Building2 } from 'lucide-react';
import Heading from '@/components/heading';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { dashboard } from '@/routes';
import { activate } from '@/routes/firms';
import type { Firm } from '@/types';

type PageProps = {
    firms: Firm[];
};

export default function Dashboard({ firms }: PageProps) {
    return (
        <>
            <Head title="Dashboard" />

            <div className="mx-auto flex w-full max-w-5xl flex-col gap-8 p-4 md:p-8">
                <Heading
                    title="Choose a firm"
                    description="Choose the firm whose client dossiers you want to manage."
                />

                <div className="grid gap-4 sm:grid-cols-2">
                    {firms.map((firm) => (
                        <button
                            key={firm.slug}
                            type="button"
                            onClick={() => router.post(activate.url(firm))}
                            className="group rounded-xl text-left outline-none focus-visible:ring-2 focus-visible:ring-ring"
                        >
                            <Card className="h-full transition-colors group-hover:bg-muted/50">
                                <CardHeader>
                                    <Building2 className="size-8 text-muted-foreground" />
                                    <CardTitle>{firm.name}</CardTitle>
                                    <CardDescription>
                                        Open dossiers
                                    </CardDescription>
                                </CardHeader>
                                <CardContent>
                                    <span className="inline-flex items-center gap-2 text-sm font-medium">
                                        Open firm
                                        <ArrowRight className="size-4" />
                                    </span>
                                </CardContent>
                            </Card>
                        </button>
                    ))}
                </div>
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
