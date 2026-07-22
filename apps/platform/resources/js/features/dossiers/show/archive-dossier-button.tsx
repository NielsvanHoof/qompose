import { Form } from '@inertiajs/react';
import DossierController from '@/actions/App/Http/Controllers/Dossiers/DossierController';
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
 * Archive (soft-delete) a dossier with confirmation.
 */
export default function ArchiveDossierButton({
    dossierId,
    dossierTitle,
}: {
    dossierId: number;
    dossierTitle: string;
}) {
    const currentWorkspace = useCurrentWorkspace();
    const { t } = useTranslation();

    return (
        <Dialog>
            <DialogTrigger asChild>
                <Button
                    type="button"
                    variant="destructive"
                    data-test="archive-dossier-button"
                >
                    {t('Archive dossier')}
                </Button>
            </DialogTrigger>
            <DialogContent>
                <DialogTitle>
                    {t('Archive :title?', { title: dossierTitle })}
                </DialogTitle>
                <DialogDescription>
                    {t(
                        'The dossier will be hidden from lists and cannot be opened again. Document requests and files are kept for audit purposes.',
                    )}
                </DialogDescription>

                <Form
                    {...DossierController.destroy.form({
                        tenant: currentWorkspace,
                        dossier: dossierId,
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
                                data-test="confirm-archive-dossier-button"
                            >
                                {t('Archive dossier')}
                            </Button>
                        </DialogFooter>
                    )}
                </Form>
            </DialogContent>
        </Dialog>
    );
}
