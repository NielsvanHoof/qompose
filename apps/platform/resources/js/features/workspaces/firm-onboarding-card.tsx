import { Building2 } from 'lucide-react';
import Heading from '@/components/heading';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import CreateFirmForm from '@/features/workspaces/create-firm-form';
import { store } from '@/routes/onboarding/firm';

/**
 * First-run card to create the accounting firm.
 */
export default function FirmOnboardingCard() {
    return (
        <Card className="w-full">
            <CardHeader>
                <Building2 className="size-8 text-muted-foreground" />
                <CardTitle>Set up your firm</CardTitle>
                <CardDescription>
                    Start by adding the accounting firm that will manage client
                    dossiers.
                </CardDescription>
            </CardHeader>
            <CardContent>
                <Heading
                    title="What is your firm called?"
                    description="Next, you will add your first client and create a dossier."
                />

                <CreateFirmForm
                    action={store.form()}
                    submitLabel="Continue to your first client"
                />
            </CardContent>
        </Card>
    );
}
