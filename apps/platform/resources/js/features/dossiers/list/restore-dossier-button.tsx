import { Form, Link } from '@inertiajs/react';
import { RotateCcw } from 'lucide-react';
import DossierController from '@/actions/App/Http/Controllers/Dossiers/DossierController';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { useCurrentWorkspace } from '@/hooks/use-current-workspace';
import { useTranslation } from '@/hooks/use-translation';
import { archived as archivedClients } from '@/routes/workspaces/clients';

export default function RestoreDossierButton({
    dossierId,
    clientArchived,
    canRestoreClient,
}: {
    dossierId: number;
    clientArchived: boolean;
    canRestoreClient: boolean;
}) {
    const currentWorkspace = useCurrentWorkspace();
    const { t } = useTranslation();

    if (clientArchived) {
        return (
            <div className="flex flex-col items-end gap-1">
                {canRestoreClient ? (
                    <Button size="sm" variant="outline" asChild>
                        <Link href={archivedClients(currentWorkspace)}>
                            {t('Restore client first')}
                        </Link>
                    </Button>
                ) : null}
                <p className="max-w-52 text-right text-xs text-muted-foreground">
                    {canRestoreClient
                        ? t('This dossier belongs to an archived client.')
                        : t(
                              'Ask a workspace administrator to restore the client first.',
                          )}
                </p>
            </div>
        );
    }

    return (
        <Form
            {...DossierController.restore.form({
                tenant: currentWorkspace,
                dossier: dossierId,
            })}
        >
            {({ errors, processing }) => (
                <div className="flex flex-col items-end gap-1">
                    <Button type="submit" size="sm" disabled={processing}>
                        <RotateCcw aria-hidden="true" />
                        {t('Restore dossier')}
                    </Button>
                    <InputError message={errors.dossier} />
                </div>
            )}
        </Form>
    );
}
