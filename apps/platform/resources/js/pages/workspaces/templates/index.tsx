import { Form, Head, Link } from '@inertiajs/react';
import { Plus } from 'lucide-react';
import QuestionnaireTemplateController from '@/actions/App/Http/Controllers/Workspace/QuestionnaireTemplateController';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import type { TemplateSummary } from '@/types';
import {
    create as createTemplate,
    index as templateIndex,
    show as showTemplate,
} from '@/routes/workspaces/templates';

/**
 * Template library — system packs plus firm-owned copies.
 */
export default function TemplateIndex({
    system_templates: systemTemplates,
    firm_templates: firmTemplates,
    can_manage: canManage,
}: {
    system_templates: TemplateSummary[];
    firm_templates: TemplateSummary[];
    can_manage: boolean;
}) {
    return (
        <>
            <Head title="Templates" />

            <div className="mx-auto flex w-full max-w-5xl flex-col gap-6 p-4 md:p-8">
                <div className="flex flex-wrap items-start justify-between gap-4">
                    <div>
                        <h1 className="text-2xl font-semibold tracking-tight">
                            Templates
                        </h1>
                        <p className="mt-1 text-sm text-muted-foreground">
                            Reusable KYC, jaarrekening, fiscale and PBC
                            questionnaires. Copy a system pack to customise it
                            for your firm.
                        </p>
                    </div>

                    {canManage && (
                        <Button asChild>
                            <Link href={createTemplate()}>
                                <Plus />
                                New template
                            </Link>
                        </Button>
                    )}
                </div>

                <TemplateSection
                    title="System templates"
                    description="Read-only starter packs. Copy one to edit for your firm."
                    templates={systemTemplates}
                    canManage={canManage}
                    empty="No system templates seeded yet."
                />

                <TemplateSection
                    title="My templates"
                    description="Firm-owned templates you can edit and apply to dossiers."
                    templates={firmTemplates}
                    canManage={canManage}
                    empty="No firm templates yet. Copy a system template or create one."
                />
            </div>
        </>
    );
}

function TemplateSection({
    title,
    description,
    templates,
    canManage,
    empty,
}: {
    title: string;
    description: string;
    templates: TemplateSummary[];
    canManage: boolean;
    empty: string;
}) {
    return (
        <Card>
            <CardHeader>
                <CardTitle>{title}</CardTitle>
                <CardDescription>{description}</CardDescription>
            </CardHeader>
            <CardContent>
                {templates.length === 0 ? (
                    <p className="text-sm text-muted-foreground">{empty}</p>
                ) : (
                    <div className="divide-y rounded-md border">
                        {templates.map((template) => (
                            <div
                                key={template.id}
                                className="flex flex-wrap items-center justify-between gap-3 px-4 py-3"
                            >
                                <div className="min-w-0">
                                    <div className="flex flex-wrap items-center gap-2">
                                        <Link
                                            href={showTemplate(template.id)}
                                            className="font-medium hover:underline"
                                        >
                                            {template.name}
                                        </Link>
                                        <Badge variant="secondary">
                                            {template.category_label}
                                        </Badge>
                                    </div>
                                    {template.description && (
                                        <p className="mt-1 text-sm text-muted-foreground">
                                            {template.description}
                                        </p>
                                    )}
                                    <p className="mt-1 text-xs text-muted-foreground">
                                        {template.items_count} items
                                    </p>
                                </div>

                                <div className="flex flex-wrap gap-2">
                                    <Button variant="outline" size="sm" asChild>
                                        <Link href={showTemplate(template.id)}>
                                            {template.is_system
                                                ? 'View'
                                                : 'Edit'}
                                        </Link>
                                    </Button>
                                    {canManage && (
                                        <Form
                                            {...QuestionnaireTemplateController.copy.form(
                                                template.id,
                                            )}
                                        >
                                            {({ processing }) => (
                                                <Button
                                                    type="submit"
                                                    size="sm"
                                                    variant="secondary"
                                                    disabled={processing}
                                                >
                                                    Copy to my firm
                                                </Button>
                                            )}
                                        </Form>
                                    )}
                                </div>
                            </div>
                        ))}
                    </div>
                )}
            </CardContent>
        </Card>
    );
}

TemplateIndex.layout = {
    breadcrumbs: [
        {
            title: 'Templates',
            href: templateIndex(),
        },
    ],
};
