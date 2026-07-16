import { router } from '@inertiajs/react';
import { ArrowRight, Building2 } from 'lucide-react';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { dashboard as workspaceDashboard } from '@/routes/workspaces';
import type { Firm } from '@/types';

/**
 * Firm picker grid for the global (pre-workspace) dashboard.
 */
export default function FirmPickerGrid({ firms }: { firms: Firm[] }) {
    return (
        <div className="grid gap-4 sm:grid-cols-2">
            {firms.map((firm) => (
                <button
                    key={firm.slug}
                    type="button"
                    onClick={() => router.visit(workspaceDashboard.url(firm))}
                    className="group rounded-xl text-left outline-none focus-visible:ring-2 focus-visible:ring-ring"
                >
                    <Card className="h-full transition-colors group-hover:bg-muted/50">
                        <CardHeader>
                            <Building2 className="size-8 text-muted-foreground" />
                            <CardTitle>{firm.name}</CardTitle>
                            <CardDescription>Open dossiers</CardDescription>
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
    );
}
