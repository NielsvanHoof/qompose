import { Form } from '@inertiajs/react';
import { ChevronDown } from 'lucide-react';
import DocumentRequestController from '@/actions/App/Http/Controllers/Dossiers/DocumentRequestController';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import {
    Collapsible,
    CollapsibleContent,
    CollapsibleTrigger,
} from '@/components/ui/collapsible';
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

/**
 * Append a reusable questionnaire pack onto the current dossier form.
 */
export default function QuestionnaireBuilderTemplates({
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
        <Collapsible className="border-t border-border/60 pt-3">
            <CollapsibleTrigger asChild>
                <Button
                    type="button"
                    variant="ghost"
                    size="sm"
                    // Allow the helper line to wrap in the narrow palette column.
                    className="group h-auto w-full justify-between gap-2 px-0 py-1 text-left whitespace-normal"
                >
                    <span className="min-w-0 flex-1">
                        <span className="block text-sm font-semibold">
                            {t('Templates')}
                        </span>
                        <span className="block text-xs font-normal text-pretty text-muted-foreground">
                            {t(
                                'Append a reusable pack. Existing items are kept.',
                            )}
                        </span>
                    </span>
                    <ChevronDown
                        className="size-4 shrink-0 transition-transform group-data-[state=open]:rotate-180"
                        aria-hidden="true"
                    />
                </Button>
            </CollapsibleTrigger>
            <CollapsibleContent className="pt-3">
                <Form
                    {...DocumentRequestController.applyTemplate.form({
                        tenant: currentWorkspace,
                        dossier: dossierId,
                    })}
                    options={inlineDossierActionOptions}
                    className="space-y-3"
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
                                    {/* Match trigger width and align start so long labels
                                        don't spill past the narrow palette column. */}
                                    <SelectContent
                                        align="start"
                                        className="w-[var(--radix-select-trigger-width)] max-w-[var(--radix-select-trigger-width)] bg-background"
                                    >
                                        {templates.map((template) => {
                                            const label = `${template.name} · ${template.category_label} (${template.items_count})${template.is_system ? ` · ${t('System')}` : ''}`;

                                            return (
                                                <SelectItem
                                                    key={template.id}
                                                    value={template.id.toString()}
                                                    className="items-start whitespace-normal"
                                                    title={label}
                                                >
                                                    <span className="text-left leading-snug text-pretty">
                                                        {label}
                                                    </span>
                                                </SelectItem>
                                            );
                                        })}
                                    </SelectContent>
                                </Select>
                                <InputError
                                    message={errors.questionnaire_template_id}
                                />
                            </div>
                            <Button
                                disabled={processing}
                                className="w-full"
                                size="sm"
                            >
                                {t('Apply template')}
                            </Button>
                        </>
                    )}
                </Form>
            </CollapsibleContent>
        </Collapsible>
    );
}
