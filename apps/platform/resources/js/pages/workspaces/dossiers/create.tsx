import { Form, Head, Link } from '@inertiajs/react';
import DossierController from '@/actions/App/Http/Controllers/DossierController';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { index as clientIndex } from '@/routes/workspaces/clients';
import { index as dossierIndex } from '@/routes/workspaces/dossiers';

type Client = {
    id: number;
    name: string;
    email: string;
};

export default function CreateDossier({ clients }: { clients: Client[] }) {
    return (
        <>
            <Head title="New dossier" />

            <div className="mx-auto w-full max-w-xl p-4 md:p-8">
                <Heading
                    title="New dossier"
                    description="Create a document collection for an existing client."
                />

                {clients.length === 0 ? (
                    <div className="mt-6 rounded-lg border p-6">
                        <p className="text-sm text-muted-foreground">
                            Create a client before creating a dossier.
                        </p>
                        <Button className="mt-4" asChild>
                            <Link href={clientIndex()}>
                                Go to clients
                            </Link>
                        </Button>
                    </div>
                ) : (
                    <Form
                        {...DossierController.store.form()}
                        className="mt-6 space-y-6"
                    >
                        {({ errors, processing }) => (
                            <>
                                <div className="grid gap-2">
                                    <Label htmlFor="client_id">Client</Label>
                                    <select
                                        id="client_id"
                                        name="client_id"
                                        required
                                        defaultValue=""
                                        className="border-input bg-background focus-visible:border-ring focus-visible:ring-ring/50 h-9 rounded-md border px-3 text-sm shadow-xs outline-none focus-visible:ring-[3px]"
                                    >
                                        <option value="" disabled>
                                            Select a client
                                        </option>
                                        {clients.map((client) => (
                                            <option
                                                key={client.id}
                                                value={client.id}
                                            >
                                                {client.name} ({client.email})
                                            </option>
                                        ))}
                                    </select>
                                    <InputError message={errors.client_id} />
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="title">Dossier name</Label>
                                    <Input
                                        id="title"
                                        name="title"
                                        required
                                        placeholder="2025 payroll documents"
                                    />
                                    <InputError message={errors.title} />
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="reference">
                                        Reference (optional)
                                    </Label>
                                    <Input
                                        id="reference"
                                        name="reference"
                                        placeholder="PAY-2025-001"
                                    />
                                    <InputError message={errors.reference} />
                                </div>

                                <div className="flex items-center gap-3">
                                    <Button disabled={processing}>
                                        Create dossier
                                    </Button>
                                    <Button variant="ghost" asChild>
                                        <Link href={dossierIndex()}>
                                            Cancel
                                        </Link>
                                    </Button>
                                </div>
                            </>
                        )}
                    </Form>
                )}
            </div>
        </>
    );
}
