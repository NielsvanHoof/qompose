import { Form } from '@inertiajs/react';
import ClientController from '@/actions/App/Http/Controllers/Clients/ClientController';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import { useCurrentWorkspace } from '@/hooks/use-current-workspace';
import { useTranslation } from '@/hooks/use-translation';

/**
 * Archive (soft-delete) a client and cascade to all dossiers.
 */
export default function ArchiveClientButton({
    clientId,
    clientName,
    dossiersCount,
}: {
    clientId: number;
    clientName: string;
    dossiersCount: number;
}) {
    const currentWorkspace = useCurrentWorkspace();
    const { t } = useTranslation();

    return (
        <Dialog>
            <DialogTrigger asChild>
                <Button
                    type="button"
                    size="sm"
                    variant="destructive"
                    data-test={`archive-client-button-${clientId}`}
                >
                    {t('Archive')}
                </Button>
            </DialogTrigger>
            <DialogContent>
                <DialogTitle>
                    {t('Archive :name?', { name: clientName })}
                </DialogTitle>
                <DialogDescription>
                    {dossiersCount > 0
                        ? t(
                              'This client and their :count dossiers will be archived. They will no longer appear in lists.',
                              { count: dossiersCount },
                          )
                        : t(
                              'This client will be archived and will no longer appear in lists.',
                          )}
                </DialogDescription>

                <Form
                    {...ClientController.destroy.form({
                        tenant: currentWorkspace,
                        client: clientId,
                    })}
                >
                    {({ processing }) => (
                        <DialogFooter className="gap-2">
                            <DialogClose asChild>
                                <Button type="button" variant="secondary">
                                    {t('Cancel')}
                                </Button>
                            </DialogClose>
                            <Button
                                type="submit"
                                variant="destructive"
                                disabled={processing}
                                data-test={`confirm-archive-client-button-${clientId}`}
                            >
                                {t('Archive client')}
                            </Button>
                        </DialogFooter>
                    )}
                </Form>
            </DialogContent>
        </Dialog>
    );
}
