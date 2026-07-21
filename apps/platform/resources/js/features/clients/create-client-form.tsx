import { Form, Link } from '@inertiajs/react';
import ClientController from '@/actions/App/Http/Controllers/Clients/ClientController';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useCurrentWorkspace } from '@/hooks/use-current-workspace';
import { useTranslation } from '@/hooks/use-translation';
import { index as clientIndex } from '@/routes/workspaces/clients';

/**
 * Form to create a new client (name + email).
 */
export default function CreateClientForm() {
    const currentWorkspace = useCurrentWorkspace();
    const { t } = useTranslation();

    return (
        <Form
            {...ClientController.store.form(currentWorkspace)}
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
                            placeholder={t('Jane Client')}
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
                            placeholder={t('jane@example.com')}
                        />
                        <InputError message={errors.email} />
                    </div>

                    <div className="flex items-center gap-3">
                        <Button disabled={processing}>
                            {t('Create client')}
                        </Button>
                        <Button variant="ghost" asChild>
                            <Link href={clientIndex(currentWorkspace)}>
                                {t('Cancel')}
                            </Link>
                        </Button>
                    </div>
                </>
            )}
        </Form>
    );
}
