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
import { useTranslation } from '@/hooks/use-translation';
import { store } from '@/routes/onboarding/firm';

/**
 * First-run card to create the accounting firm.
 */
export default function FirmOnboardingCard() {
    const { t } = useTranslation();

    return (
        <Card className="w-full">
            <CardHeader>
                <Building2 className="size-8 text-muted-foreground" />
                <CardTitle>{t('Set up your firm')}</CardTitle>
                <CardDescription>
                    {t(
                        'Start by adding the accounting firm that will manage client dossiers.',
                    )}
                </CardDescription>
            </CardHeader>
            <CardContent>
                <Heading
                    title={t('What is your firm called?')}
                    description={t(
                        'Next, you will add your first client and create a dossier.',
                    )}
                />

                <CreateFirmForm
                    action={store.form()}
                    submitLabel={t('Continue to your first client')}
                />
            </CardContent>
        </Card>
    );
}
