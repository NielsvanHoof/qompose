import { Form } from '@inertiajs/react';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import type { RouteFormDefinition } from '@/wayfinder';

/**
 * Firm name form shared by first-run onboarding and the
 * in-app "create a new firm" page. The submit target and
 * button label differ per context.
 */
export default function CreateFirmForm({
    action,
    submitLabel,
}: {
    action: RouteFormDefinition<'post'>;
    submitLabel: string;
}) {
    return (
        <Form {...action} className="space-y-4">
            {({ errors, processing }) => (
                <>
                    <div className="grid gap-2">
                        <Label htmlFor="name">Firm name</Label>
                        <Input
                            id="name"
                            name="name"
                            required
                            autoComplete="organization"
                            placeholder="Acme Accountants"
                            autoFocus
                        />
                        <InputError message={errors.name} />
                    </div>

                    <Button disabled={processing}>{submitLabel}</Button>
                </>
            )}
        </Form>
    );
}
