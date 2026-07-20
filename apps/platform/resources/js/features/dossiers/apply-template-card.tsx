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
import { inlineDossierActionOptions } from '@/lib/inline-dossier-action-options';

function formatTemplateLabel(template: ApplyTemplateOption): string {
    const suffix = template.is_system ? ' · System' : '';

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

    if (templates.length === 0) {
        return null;
    }

    return (
        <Card className="min-w-0">
            <CardHeader>
                <CardTitle>Apply template</CardTitle>
                <CardDescription>
                    Append a reusable pack. Existing items are kept.
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
                                    Template
                                </Label>
                                <Select
                                    required
                                    name="questionnaire_template_id"
                                    defaultValue=""
                                >
                                    <SelectTrigger className="w-full min-w-0">
                                        <SelectValue placeholder="Select a template…" />
                                    </SelectTrigger>
                                    <SelectContent className="bg-background">
                                        {templates.map((template) => (
                                            <SelectItem
                                                key={template.id}
                                                value={template.id.toString()}
                                                className="truncate"
                                                title={formatTemplateLabel(
                                                    template,
                                                )}
                                            >
                                                {formatTemplateLabel(template)}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                                <InputError
                                    message={errors.questionnaire_template_id}
                                />
                            </div>

                            <Button disabled={processing} className="w-full">
                                Apply template
                            </Button>
                        </>
                    )}
                </Form>
            </CardContent>
        </Card>
    );
}
