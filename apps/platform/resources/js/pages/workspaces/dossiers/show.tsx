import { Form, Head, Link } from '@inertiajs/react';
import DocumentRequestController from '@/actions/App/Http/Controllers/Workspace/DocumentRequestController';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { index as dossierIndex } from '@/routes/workspaces/dossiers';

type Tenant = {
    slug: string;
};

type DocumentRequest = {
    id: number;
    title: string;
    instructions: string | null;
    status: string;
};

type Dossier = {
    id: number;
    title: string;
    reference: string | null;
    status: string;
    client: {
        name: string;
        email: string;
    };
    document_requests: DocumentRequest[];
};

export default function ShowDossier({
    tenant,
    dossier,
}: {
    tenant: Tenant;
    dossier: Dossier;
}) {
    return (
        <>
            <Head title={dossier.title} />

            <div className="mx-auto flex w-full max-w-5xl flex-col gap-6 p-4 md:p-8">
                <div className="flex flex-wrap items-start justify-between gap-4">
                    <div>
                        <Link
                            href={dossierIndex(tenant)}
                            className="text-sm text-muted-foreground hover:text-foreground"
                        >
                            Dossiers
                        </Link>
                        <Heading
                            title={dossier.title}
                            description={`${dossier.client.name} · ${dossier.client.email}`}
                        />
                    </div>
                    <Badge variant="secondary">
                        {dossier.status.replace('_', ' ')}
                    </Badge>
                </div>

                <div className="grid gap-6 lg:grid-cols-[1fr_20rem]">
                    <Card>
                        <CardHeader>
                            <CardTitle>Document requests</CardTitle>
                            <CardDescription>
                                The client will see these in their secure portal.
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            {dossier.document_requests.length === 0 ? (
                                <p className="text-sm text-muted-foreground">
                                    No documents requested yet.
                                </p>
                            ) : (
                                <div className="divide-y rounded-md border">
                                    {dossier.document_requests.map(
                                        (documentRequest) => (
                                            <div
                                                key={documentRequest.id}
                                                className="flex flex-wrap items-start justify-between gap-3 px-4 py-3"
                                            >
                                                <div>
                                                    <p className="font-medium">
                                                        {documentRequest.title}
                                                    </p>
                                                    {documentRequest.instructions && (
                                                        <p className="mt-1 text-sm text-muted-foreground">
                                                            {
                                                                documentRequest.instructions
                                                            }
                                                        </p>
                                                    )}
                                                </div>
                                                <Badge variant="outline">
                                                    {documentRequest.status}
                                                </Badge>
                                            </div>
                                        ),
                                    )}
                                </div>
                            )}
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle>Add request</CardTitle>
                            <CardDescription>
                                Ask the client for one document at a time.
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            <Form
                                {...DocumentRequestController.store.form({
                                    tenant,
                                    dossier: dossier.id,
                                })}
                                resetOnSuccess
                                className="space-y-4"
                            >
                                {({ errors, processing }) => (
                                    <>
                                        <div className="grid gap-2">
                                            <Label htmlFor="title">
                                                Document name
                                            </Label>
                                            <Input
                                                id="title"
                                                name="title"
                                                required
                                                placeholder="Payslip January 2025"
                                            />
                                            <InputError
                                                message={errors.title}
                                            />
                                        </div>

                                        <div className="grid gap-2">
                                            <Label htmlFor="instructions">
                                                Instructions (optional)
                                            </Label>
                                            <textarea
                                                id="instructions"
                                                name="instructions"
                                                rows={4}
                                                className="border-input bg-background focus-visible:border-ring focus-visible:ring-ring/50 rounded-md border px-3 py-2 text-sm shadow-xs outline-none focus-visible:ring-[3px]"
                                                placeholder="Upload the PDF you received from your employer."
                                            />
                                            <InputError
                                                message={errors.instructions}
                                            />
                                        </div>

                                        <Button
                                            disabled={processing}
                                            className="w-full"
                                        >
                                            Add request
                                        </Button>
                                    </>
                                )}
                            </Form>
                        </CardContent>
                    </Card>
                </div>
            </div>
        </>
    );
}
