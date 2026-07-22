import { Form, Link } from '@inertiajs/react';
import QuestionnaireTemplateController from '@/actions/App/Http/Controllers/Questionnaires/QuestionnaireTemplateController';
import EmptyState from '@/components/empty-state';
import IndexPagination from '@/components/index-query/index-pagination';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
} from '@/components/ui/card';
import type { TemplateSummary } from '@/features/questionnaires/types';
import { useCurrentWorkspace } from '@/hooks/use-current-workspace';
import { show as showTemplate } from '@/routes/workspaces/templates';
import type { Paginated } from '@/types/pagination';

/**
 * One paginated bucket on the template library (system or firm-owned).
 * Title lives on the parent tab — this section only shows a short description.
 */
export default function QuestionnaireTemplatesSection({
    description,
    templates,
    canManage,
    empty,
}: {
    description: string;
    templates: Paginated<TemplateSummary>;
    canManage: boolean;
    empty: string;
}) {
    const currentWorkspace = useCurrentWorkspace();
    const rows = templates.data;

    return (
        <div className="flex flex-col gap-2">
            <Card>
                <CardHeader>
                    <CardDescription>
                        {description}
                        {templates.total > 0 && (
                            <>
                                {' '}
                                · {templates.total}{' '}
                                {templates.total === 1
                                    ? 'template'
                                    : 'templates'}
                            </>
                        )}
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    {rows.length === 0 ? (
                        <EmptyState title={empty} />
                    ) : (
                        <div className="divide-y rounded-md border">
                            {rows.map((template) => (
                                <div
                                    key={template.id}
                                    className="flex flex-wrap items-center justify-between gap-3 px-4 py-3"
                                >
                                    <div className="min-w-0">
                                        <div className="flex flex-wrap items-center gap-2">
                                            <Link
                                                href={showTemplate({
                                                    tenant: currentWorkspace,
                                                    template: template.id,
                                                })}
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
                                        <Button
                                            variant="outline"
                                            size="sm"
                                            asChild
                                        >
                                            <Link
                                                href={showTemplate({
                                                    tenant: currentWorkspace,
                                                    template: template.id,
                                                })}
                                            >
                                                {template.is_system
                                                    ? 'View'
                                                    : 'Edit'}
                                            </Link>
                                        </Button>
                                        {canManage && (
                                            <Form
                                                {...QuestionnaireTemplateController.copy.form(
                                                    {
                                                        tenant: currentWorkspace,
                                                        template: template.id,
                                                    },
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
            <IndexPagination paginator={templates} />
        </div>
    );
}
