import { Form, Link } from '@inertiajs/react';
import DossierController from '@/actions/App/Http/Controllers/Dossiers/DossierController';
import EmptyState from '@/components/empty-state';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import DossierFollowUpFields from '@/features/dossiers/follow-up/dossier-follow-up-fields';
import type {
    DossierClientOption,
    ResponsibleStaffOption,
} from '@/features/dossiers/types';
import { useCurrentWorkspace } from '@/hooks/use-current-workspace';
import { useTranslation } from '@/hooks/use-translation';
import { index as clientIndex } from '@/routes/workspaces/clients';
import { index as dossierIndex } from '@/routes/workspaces/dossiers';

/**
 * Create-dossier form, or an empty-state when no clients exist yet.
 */
export default function CreateDossierForm({
    clients,
    responsibleStaff,
}: {
    clients: DossierClientOption[];
    responsibleStaff: ResponsibleStaffOption[];
}) {
    const currentWorkspace = useCurrentWorkspace();
    const { t } = useTranslation();

    if (clients.length === 0) {
        return (
            <EmptyState
                className="mt-6"
                title={t('Create a client before creating a dossier.')}
                variant="panel"
                bordered
                action={
                    <Button asChild>
                        <Link href={clientIndex(currentWorkspace)}>
                            {t('Go to clients')}
                        </Link>
                    </Button>
                }
            />
        );
    }

    return (
        <Form
            {...DossierController.store.form(currentWorkspace)}
            className="mt-6 space-y-6"
        >
            {({ errors, processing }) => (
                <>
                    <div className="grid gap-2">
                        <Label htmlFor="client_id">{t('Client')}</Label>
                        <Select required defaultValue="" name="client_id">
                            <SelectTrigger className="w-full">
                                <SelectValue
                                    placeholder={t('Select a client')}
                                />
                            </SelectTrigger>
                            <SelectContent className="space-y-1 bg-background p-1">
                                {clients.map((client) => (
                                    <SelectItem
                                        key={client.id}
                                        value={client.id.toString()}
                                        className="flex flex-col"
                                    >
                                        <span className="font-medium">
                                            {client.name}
                                        </span>
                                        <span className="text-xs text-muted-foreground">
                                            {client.email}
                                        </span>
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                        <InputError message={errors.client_id} />
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="title">{t('Dossier name')}</Label>
                        <Input
                            id="title"
                            name="title"
                            required
                            placeholder={t('2025 payroll documents')}
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
                            placeholder={t('PAY-2025-001')}
                        />
                        <InputError message={errors.reference} />
                    </div>

                    <DossierFollowUpFields
                        responsibleStaff={responsibleStaff}
                        errors={errors}
                    />

                    <div className="flex items-center gap-3">
                        <Button disabled={processing}>
                            {t('Create dossier')}
                        </Button>
                        <Button variant="ghost" asChild>
                            <Link href={dossierIndex(currentWorkspace)}>
                                {t('Cancel')}
                            </Link>
                        </Button>
                    </div>
                </>
            )}
        </Form>
    );
}
