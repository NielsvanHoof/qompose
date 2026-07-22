import { Form, Link } from '@inertiajs/react';
import DossierController from '@/actions/App/Http/Controllers/Dossiers/DossierController';
import InputError from '@/components/input-error';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import DossierFollowUpFields from '@/features/dossiers/follow-up/dossier-follow-up-fields';
import type {
    EditableDossierWithResponsibility,
    ResponsibleStaffOption,
} from '@/features/dossiers/types';
import { useCurrentWorkspace } from '@/hooks/use-current-workspace';
import { useTranslation } from '@/hooks/use-translation';
import { show as showDossier } from '@/routes/workspaces/dossiers';

export default function EditDossierForm({
    dossier,
    responsibleStaff,
}: {
    dossier: EditableDossierWithResponsibility;
    responsibleStaff: ResponsibleStaffOption[];
}) {
    const currentWorkspace = useCurrentWorkspace();
    const { t } = useTranslation();

    return (
        <Form
            {...DossierController.update.form({
                tenant: currentWorkspace,
                dossier: dossier.id,
            })}
            className="mt-6 space-y-6"
        >
            {({ errors, processing }) => (
                <>
                    <Alert>
                        <AlertTitle>{t('Client')}</AlertTitle>
                        <AlertDescription>
                            <p className="font-medium text-foreground">
                                {dossier.client.name}
                            </p>
                            <p>{dossier.client.email}</p>
                            <p>
                                {t(
                                    'The client cannot be changed after a dossier is created.',
                                )}
                            </p>
                        </AlertDescription>
                    </Alert>

                    <div className="grid gap-2">
                        <Label htmlFor="title">{t('Dossier name')}</Label>
                        <Input
                            id="title"
                            name="title"
                            required
                            defaultValue={dossier.title}
                        />
                        <InputError message={errors.title} />
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="reference">
                            {t('Reference (optional)')}
                        </Label>
                        <Input
                            id="reference"
                            name="reference"
                            defaultValue={dossier.reference ?? ''}
                        />
                        <InputError message={errors.reference} />
                    </div>

                    <DossierFollowUpFields
                        responsibleStaff={responsibleStaff}
                        defaultDueDate={dossier.due_date ?? ''}
                        defaultResponsibleUserId={dossier.responsible_user_id}
                        defaultReminderIntervalDays={
                            dossier.reminder_interval_days
                        }
                        errors={errors}
                    />

                    <div className="flex items-center gap-3">
                        <Button disabled={processing}>
                            {t('Save changes')}
                        </Button>
                        <Button variant="ghost" asChild>
                            <Link
                                href={showDossier({
                                    tenant: currentWorkspace,
                                    dossier: dossier.id,
                                })}
                            >
                                {t('Cancel')}
                            </Link>
                        </Button>
                    </div>
                </>
            )}
        </Form>
    );
}
