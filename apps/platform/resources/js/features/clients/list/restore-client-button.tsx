import { Form } from '@inertiajs/react';
import { RotateCcw } from 'lucide-react';
import ClientController from '@/actions/App/Http/Controllers/Clients/ClientController';
import { Button } from '@/components/ui/button';
import { useCurrentWorkspace } from '@/hooks/use-current-workspace';
import { useTranslation } from '@/hooks/use-translation';

export default function RestoreClientButton({
    clientId,
}: {
    clientId: number;
}) {
    const currentWorkspace = useCurrentWorkspace();
    const { t } = useTranslation();

    return (
        <Form
            {...ClientController.restore.form({
                tenant: currentWorkspace,
                client: clientId,
            })}
        >
            {({ processing }) => (
                <Button type="submit" size="sm" disabled={processing}>
                    <RotateCcw aria-hidden="true" />
                    {t('Restore client')}
                </Button>
            )}
        </Form>
    );
}
