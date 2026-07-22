import { Form, Link } from '@inertiajs/react';
import ClientController from '@/actions/App/Http/Controllers/Clients/ClientController';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import type { ClientDetails } from '@/features/clients/types';
import { useCurrentWorkspace } from '@/hooks/use-current-workspace';
import { useTranslation } from '@/hooks/use-translation';
import { show as showClient } from '@/routes/workspaces/clients';

export default function EditClientForm({
    client,
}: {
    client: Pick<ClientDetails, 'id' | 'name' | 'email'>;
}) {
    const currentWorkspace = useCurrentWorkspace();
    const { t } = useTranslation();

    return (
        <Form
            {...ClientController.update.form({
                tenant: currentWorkspace,
                client: client.id,
            })}
            className="mt-6 space-y-6"
        >
            {({ errors, processing }) => (
                <>
                    <div className="grid gap-2">
                        <Label htmlFor="name">{t('Name')}</Label>
                        <Input
                            id="name"
                            name="name"
                            required
                            autoComplete="name"
                            defaultValue={client.name}
                        />
                        <InputError message={errors.name} />
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="email">{t('Email address')}</Label>
                        <Input
                            id="email"
                            name="email"
                            type="email"
                            required
                            autoComplete="email"
                            defaultValue={client.email}
                        />
                        <InputError message={errors.email} />
                    </div>

                    <div className="flex items-center gap-3">
                        <Button disabled={processing}>
                            {t('Save changes')}
                        </Button>
                        <Button variant="ghost" asChild>
                            <Link
                                href={showClient({
                                    tenant: currentWorkspace,
                                    client: client.id,
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
