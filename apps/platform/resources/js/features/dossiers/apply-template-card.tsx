import { Form } from '@inertiajs/react';
import DocumentRequestController from '@/actions/App/Http/Controllers/Dossiers/DocumentRequestController';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import type { ApplyTemplateOption } from '@/features/questionnaires/types';
import { useCurrentWorkspace } from '@/hooks/use-current-workspace';
import { useTranslation } from '@/hooks/use-translation';
import { inlineDossierActionOptions } from '@/lib/inline-dossier-action-options';

function formatTemplateLabel(
    template: ApplyTemplateOption,
    t: (key: string) => string,
): string {
    // Template name and category come from the DB — do not translate those.
    const suffix = template.is_system ? ` · ${t('System')}` : '';

    return `${template.name} · ${template.category_label} (${template.items_count})${suffix}`;
}

/**
 * Append a system or firm template onto the current dossier checklist.
 */
export default function ApplyTemplateCard({
    dossierId,
    templates,
}: {
    dossierId: number;
    templates: ApplyTemplateOption[];
}) {
    const currentWorkspace = useCurrentWorkspace();
    const { t } = useTranslation();

    if (templates.length === 0) {
        return null;
    }

    return (
        <Card className="min-w-0">
            <CardHeader>
                <CardTitle>{t('Apply template')}</CardTitle>
                <CardDescription>
                    {t('Append a reusable pack. Existing items are kept.')}
                </CardDescription>
            </CardHeader>
            <CardContent>
                <Form
                    {...DocumentRequestController.applyTemplate.form({
                        tenant: currentWorkspace,
                        dossier: dossierId,
                    })}
                    options={inlineDossierActionOptions}
                    className="space-y-4"
                >
                    {({ errors, processing }) => (
                        <>
                            <div className="grid min-w-0 gap-2">
                                <Label htmlFor="questionnaire_template_id">
                                    {t('Template')}
                                </Label>
                                <Select
                                    required
                                    name="questionnaire_template_id"
                                    defaultValue=""
                                >
                                    <SelectTrigger className="w-full min-w-0">
                                        <SelectValue
                                            placeholder={t(
                                                'Select a template…',
                                            )}
                                        />
                                    </SelectTrigger>
                                    <SelectContent className="bg-background">
                                        {templates.map((template) => (
                                            <SelectItem
                                                key={template.id}
                                                value={template.id.toString()}
                                                className="truncate"
                                                title={formatTemplateLabel(
                                                    template,
                                                    t,
                                                )}
                                            >
                                                {formatTemplateLabel(
                                                    template,
                                                    t,
                                                )}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                                <InputError
                                    message={errors.questionnaire_template_id}
                                />
                            </div>

                            <Button disabled={processing} className="w-full">
                                {t('Apply template')}
                            </Button>
                        </>
                    )}
                </Form>
            </CardContent>
        </Card>
    );
}
