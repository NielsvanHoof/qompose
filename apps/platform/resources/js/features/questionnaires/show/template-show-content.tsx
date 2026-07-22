import { Form, Link } from '@inertiajs/react';
import QuestionnaireTemplateController from '@/actions/App/Http/Controllers/Questionnaires/QuestionnaireTemplateController';
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
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import TemplateItemEditor from '@/features/questionnaires/show/template-item-editor';
import type {
    TemplateCategoryOption,
    TemplateDetail,
} from '@/features/questionnaires/types';
import { useCurrentWorkspace } from '@/hooks/use-current-workspace';
import { useTranslation } from '@/hooks/use-translation';
import { index as templateIndex } from '@/routes/workspaces/templates';

/**
 * Template detail body: header actions, editable details, and item editor.
 */
export default function TemplateShowContent({
    template,
    categories,
    canManage,
    canCopy,
}: {
    template: TemplateDetail;
    categories: TemplateCategoryOption[];
    canManage: boolean;
    canCopy: boolean;
}) {
    const currentWorkspace = useCurrentWorkspace();
    const { t } = useTranslation();

    return (
        <div className="mx-auto flex w-full max-w-5xl flex-col gap-6 p-4 md:p-8">
            <div className="flex flex-wrap items-start justify-between gap-4">
                <div>
                    <div className="flex flex-wrap items-center gap-2">
                        <Heading title={template.name} />
                        <Badge variant="secondary">
                            {template.category_label}
                        </Badge>
                        {template.is_system && (
                            <Badge variant="outline">{t('System')}</Badge>
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
                            {...QuestionnaireTemplateController.copy.form({
                                tenant: currentWorkspace,
                                template: template.id,
                            })}
                        >
                            {({ processing }) => (
                                <Button
                                    type="submit"
                                    variant="secondary"
                                    disabled={processing}
                                >
                                    {t('Copy to my firm')}
                                </Button>
                            )}
                        </Form>
                    )}
                    {canManage && (
                        <Form
                            {...QuestionnaireTemplateController.destroy.form({
                                tenant: currentWorkspace,
                                template: template.id,
                            })}
                        >
                            {({ processing }) => (
                                <Button
                                    type="submit"
                                    variant="destructive"
                                    disabled={processing}
                                >
                                    {t('Delete')}
                                </Button>
                            )}
                        </Form>
                    )}
                    <Button variant="outline" asChild>
                        <Link href={templateIndex(currentWorkspace)}>
                            {t('Back')}
                        </Link>
                    </Button>
                </div>
            </div>

            {canManage && (
                <Card>
                    <CardHeader>
                        <CardTitle>{t('Template details')}</CardTitle>
                        <CardDescription>
                            {t('Update the name, category, and description.')}
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <Form
                            {...QuestionnaireTemplateController.update.form({
                                tenant: currentWorkspace,
                                template: template.id,
                            })}
                            className="space-y-4"
                        >
                            {({ errors, processing }) => (
                                <>
                                    <div className="grid gap-2">
                                        <Label htmlFor="name">{t('Name')}</Label>
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
                                            {t('Category')}
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
                                                {categories.map((category) => (
                                                    <SelectItem
                                                        key={category.value}
                                                        value={category.value}
                                                    >
                                                        {category.label}
                                                    </SelectItem>
                                                ))}
                                            </SelectContent>
                                        </Select>
                                        <InputError message={errors.category} />
                                    </div>
                                    <div className="grid gap-2">
                                        <Label htmlFor="description">
                                            {t('Description')}
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
                                        {t('Save details')}
                                    </Button>
                                </>
                            )}
                        </Form>
                    </CardContent>
                </Card>
            )}

            <Card>
                <CardHeader>
                    <CardTitle>{t('Questionnaire items')}</CardTitle>
                    <CardDescription>
                        {template.is_system
                            ? t('System templates are read-only. Copy to customise.')
                            : t('Add, edit, or remove items. Drag the grip to reorder.')}
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
    );
}
