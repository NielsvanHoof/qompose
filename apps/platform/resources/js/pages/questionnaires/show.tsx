import { Form, Head, Link, setLayoutProps } from '@inertiajs/react';
import QuestionnaireTemplateController from '@/actions/App/Http/Controllers/Questionnaires/QuestionnaireTemplateController';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import TemplateItemEditor from '@/components/questionnaires/template-item-editor';
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
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import {
    show as showTemplate,
    index as templateIndex,
} from '@/routes/workspaces/templates';
import type { TemplateCategoryOption, TemplateDetail } from '@/types';

/**
 * View or edit a questionnaire template and its items.
 */
export default function ShowTemplate({
    template,
    categories,
    can_manage: canManage,
    can_copy: canCopy,
}: {
    template: TemplateDetail;
    categories: TemplateCategoryOption[];
    can_manage: boolean;
    can_copy: boolean;
}) {
    setLayoutProps({
        breadcrumbs: [
            {
                title: 'Templates',
                href: templateIndex(),
            },
            {
                title: template.name,
                href: showTemplate(template.id),
            },
        ],
    });

    return (
        <>
            <Head title={template.name} />

            <div className="mx-auto flex w-full max-w-5xl flex-col gap-6 p-4 md:p-8">
                <div className="flex flex-wrap items-start justify-between gap-4">
                    <div>
                        <div className="flex flex-wrap items-center gap-2">
                            <Heading title={template.name} />
                            <Badge variant="secondary">
                                {template.category_label}
                            </Badge>
                            {template.is_system && (
                                <Badge variant="outline">System</Badge>
                            )}
                        </div>
                        {template.description && (
                            <p className="mt-1 text-sm text-muted-foreground">
                                {template.description}
                            </p>
                        )}
                    </div>

                    <div className="flex flex-wrap gap-2">
                        {canCopy && (
                            <Form
                                {...QuestionnaireTemplateController.copy.form(
                                    template.id,
                                )}
                            >
                                {({ processing }) => (
                                    <Button
                                        type="submit"
                                        variant="secondary"
                                        disabled={processing}
                                    >
                                        Copy to my firm
                                    </Button>
                                )}
                            </Form>
                        )}
                        {canManage && (
                            <Form
                                {...QuestionnaireTemplateController.destroy.form(
                                    template.id,
                                )}
                            >
                                {({ processing }) => (
                                    <Button
                                        type="submit"
                                        variant="destructive"
                                        disabled={processing}
                                    >
                                        Delete
                                    </Button>
                                )}
                            </Form>
                        )}
                        <Button variant="outline" asChild>
                            <Link href={templateIndex()}>Back</Link>
                        </Button>
                    </div>
                </div>

                {canManage && (
                    <Card>
                        <CardHeader>
                            <CardTitle>Template details</CardTitle>
                            <CardDescription>
                                Update the name, category, and description.
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            <Form
                                {...QuestionnaireTemplateController.update.form(
                                    template.id,
                                )}
                                className="space-y-4"
                            >
                                {({ errors, processing }) => (
                                    <>
                                        <div className="grid gap-2">
                                            <Label htmlFor="name">Name</Label>
                                            <Input
                                                id="name"
                                                name="name"
                                                required
                                                defaultValue={template.name}
                                            />
                                            <InputError message={errors.name} />
                                        </div>
                                        <div className="grid gap-2">
                                            <Label htmlFor="category">
                                                Category
                                            </Label>
                                            <Select
                                                required
                                                defaultValue={template.category}
                                                name="category"
                                            >
                                                <SelectTrigger className="w-full">
                                                    <SelectValue />
                                                </SelectTrigger>
                                                <SelectContent className="bg-background">
                                                    {categories.map(
                                                        (category) => (
                                                            <SelectItem
                                                                key={
                                                                    category.value
                                                                }
                                                                value={
                                                                    category.value
                                                                }
                                                            >
                                                                {category.label}
                                                            </SelectItem>
                                                        ),
                                                    )}
                                                </SelectContent>
                                            </Select>
                                            <InputError
                                                message={errors.category}
                                            />
                                        </div>
                                        <div className="grid gap-2">
                                            <Label htmlFor="description">
                                                Description
                                            </Label>
                                            <textarea
                                                id="description"
                                                name="description"
                                                rows={3}
                                                defaultValue={
                                                    template.description ?? ''
                                                }
                                                className="rounded-md border border-input bg-background px-3 py-2 text-sm shadow-xs outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50"
                                            />
                                            <InputError
                                                message={errors.description}
                                            />
                                        </div>
                                        <Button disabled={processing}>
                                            Save details
                                        </Button>
                                    </>
                                )}
                            </Form>
                        </CardContent>
                    </Card>
                )}

                <Card>
                    <CardHeader>
                        <CardTitle>Questionnaire items</CardTitle>
                        <CardDescription>
                            {template.is_system
                                ? 'System templates are read-only. Copy to customise.'
                                : 'Add, edit, or remove items. Drag the grip to reorder.'}
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <TemplateItemEditor
                            templateId={template.id}
                            items={template.items}
                            canManage={canManage}
                        />
                    </CardContent>
                </Card>
            </div>
        </>
    );
}
