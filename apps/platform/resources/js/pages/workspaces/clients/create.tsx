import { Form, Head, Link } from '@inertiajs/react';
import ClientController from '@/actions/App/Http/Controllers/Workspace/ClientController';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { index as clientIndex } from '@/routes/workspaces/clients';

type Tenant = {
    slug: string;
};

export default function CreateClient({ tenant }: { tenant: Tenant }) {
    return (
        <>
            <Head title="New client" />

            <div className="mx-auto w-full max-w-xl p-4 md:p-8">
                <Heading
                    title="New client"
                    description="Add the person or organisation that will provide documents."
                />

                <Form
                    {...ClientController.store.form(tenant)}
                    className="mt-6 space-y-6"
                >
                    {({ errors, processing }) => (
                        <>
                            <div className="grid gap-2">
                                <Label htmlFor="name">Name</Label>
                                <Input
                                    id="name"
                                    name="name"
                                    required
                                    autoComplete="name"
                                    placeholder="Jane Client"
                                />
                                <InputError message={errors.name} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="email">Email address</Label>
                                <Input
                                    id="email"
                                    name="email"
                                    type="email"
                                    required
                                    autoComplete="email"
                                    placeholder="jane@example.com"
                                />
                                <InputError message={errors.email} />
                            </div>

                            <div className="flex items-center gap-3">
                                <Button disabled={processing}>
                                    Create client
                                </Button>
                                <Button variant="ghost" asChild>
                                    <Link href={clientIndex()}>Cancel</Link>
                                </Button>
                            </div>
                        </>
                    )}
                </Form>
            </div>
        </>
    );
}
