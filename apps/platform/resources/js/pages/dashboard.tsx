import { Form, Head, Link, usePage } from '@inertiajs/react';
import { ArrowRight, Building2, Plus } from 'lucide-react';
import WorkspaceOnboardingController from '@/actions/App/Http/Controllers/WorkspaceOnboardingController';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
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
import { dashboard } from '@/routes';
import { index as dossierIndex } from '@/routes/workspaces/dossiers';

type Workspace = {
    name: string;
    slug: string;
};

type PageProps = {
    workspaces: Workspace[];
};

export default function Dashboard() {
    const { workspaces } = usePage<PageProps>().props;

    return (
        <>
            <Head title="Dashboard" />

            <div className="mx-auto flex w-full max-w-5xl flex-col gap-8 p-4 md:p-8">
                <Heading
                    title="Your workspaces"
                    description="Choose a workspace to collect and review client documents."
                />

                {workspaces.length === 0 ? (
                    <Card className="mx-auto w-full max-w-xl">
                        <CardHeader>
                            <Building2 className="size-8 text-muted-foreground" />
                            <CardTitle>Create your first workspace</CardTitle>
                            <CardDescription>
                                A workspace keeps your clients, dossiers, and
                                document requests separate.
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            <WorkspaceForm />
                        </CardContent>
                    </Card>
                ) : (
                    <div className="grid gap-6 lg:grid-cols-[1fr_20rem]">
                        <div className="grid gap-4 sm:grid-cols-2">
                            {workspaces.map((workspace) => (
                                <Link
                                    key={workspace.slug}
                                    href={dossierIndex(workspace)}
                                    className="group rounded-xl outline-none focus-visible:ring-2 focus-visible:ring-ring"
                                >
                                    <Card className="h-full transition-colors group-hover:bg-muted/50">
                                        <CardHeader>
                                            <CardTitle>
                                                {workspace.name}
                                            </CardTitle>
                                            <CardDescription>
                                                Open dossiers
                                            </CardDescription>
                                        </CardHeader>
                                        <CardContent>
                                            <span className="inline-flex items-center gap-2 text-sm font-medium">
                                                Open workspace
                                                <ArrowRight className="size-4" />
                                            </span>
                                        </CardContent>
                                    </Card>
                                </Link>
                            ))}
                        </div>

                        <Card className="h-fit">
                            <CardHeader>
                                <CardTitle>New workspace</CardTitle>
                                <CardDescription>
                                    Create another workspace when you need a
                                    separate client area.
                                </CardDescription>
                            </CardHeader>
                            <CardContent>
                                <WorkspaceForm compact />
                            </CardContent>
                        </Card>
                    </div>
                )}
            </div>
        </>
    );
}

function WorkspaceForm({ compact = false }: { compact?: boolean }) {
    return (
        <Form
            {...WorkspaceOnboardingController.store.form()}
            resetOnSuccess
            className="space-y-4"
        >
            {({ errors, processing }) => (
                <>
                    <div className="grid gap-2">
                        <Label htmlFor="name">Workspace name</Label>
                        <Input
                            id="name"
                            name="name"
                            required
                            autoComplete="organization"
                            placeholder="Acme Accountants"
                        />
                        <InputError message={errors.name} />
                    </div>

                    <Button
                        disabled={processing}
                        className={compact ? 'w-full' : undefined}
                    >
                        <Plus />
                        Create workspace
                    </Button>
                </>
            )}
        </Form>
    );
}

Dashboard.layout = {
    breadcrumbs: [
        {
            title: 'Dashboard',
            href: dashboard(),
        },
    ],
};
