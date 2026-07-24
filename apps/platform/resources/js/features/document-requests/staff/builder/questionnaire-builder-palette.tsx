import { useDraggable } from '@dnd-kit/core';
import { type LucideIcon, Plus, Search } from 'lucide-react';
import { type ComponentType, useMemo, useState } from 'react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { getQuestionnaireItemTypeDefinitions } from '@/features/document-requests/questionnaire-item-type-registry';
import QuestionnaireBuilderTemplates from '@/features/document-requests/staff/builder/questionnaire-builder-templates';
import type { QuestionnaireItemType } from '@/features/document-requests/types';
import type { ApplyTemplateOption } from '@/features/questionnaires/types';
import { useTranslation } from '@/hooks/use-translation';
import { cn } from '@/lib/utils';

/**
 * Compact searchable palette of registered components plus optional templates.
 */
export default function QuestionnaireBuilderPalette({
    dossierId,
    templates,
    canEdit,
    onAdd,
}: {
    dossierId: number;
    templates: ApplyTemplateOption[];
    canEdit: boolean;
    onAdd: (type: QuestionnaireItemType) => void;
}) {
    const { t } = useTranslation();
    const [query, setQuery] = useState('');
    const definitions = getQuestionnaireItemTypeDefinitions(t);

    const filtered = useMemo(() => {
        const normalized = query.trim().toLocaleLowerCase();

        if (normalized === '') {
            return definitions;
        }

        return definitions.filter((definition) => {
            const haystack =
                `${definition.label} ${definition.description}`.toLocaleLowerCase();

            return haystack.includes(normalized);
        });
    }, [definitions, query]);

    return (
        <aside className="space-y-4" aria-label={t('Components')}>
            <div className="space-y-2">
                <div className="space-y-1">
                    <h3 className="text-sm font-semibold tracking-tight">
                        {t('Components')}
                    </h3>
                    <p className="text-xs text-muted-foreground">
                        {t('Drag onto the form or use Add.')}
                    </p>
                </div>

                <div className="relative">
                    <Search
                        className="pointer-events-none absolute top-1/2 left-2.5 size-3.5 -translate-y-1/2 text-muted-foreground"
                        aria-hidden="true"
                    />
                    <Input
                        type="search"
                        value={query}
                        onChange={(event) => setQuery(event.target.value)}
                        placeholder={t('Search components…')}
                        aria-label={t('Search components…')}
                        className="h-8 pl-8 text-sm"
                        autoComplete="off"
                    />
                </div>
            </div>

            {filtered.length === 0 ? (
                <p className="text-xs text-muted-foreground">
                    {t('No matching components.')}
                </p>
            ) : (
                <ul className="space-y-1.5">
                    {filtered.map((definition) => (
                        <li key={definition.value}>
                            <PaletteItem
                                type={definition.value}
                                label={definition.label}
                                description={definition.description}
                                icon={definition.icon}
                                disabled={!canEdit}
                                onAdd={() => onAdd(definition.value)}
                            />
                        </li>
                    ))}
                </ul>
            )}

            {canEdit ? (
                <QuestionnaireBuilderTemplates
                    dossierId={dossierId}
                    templates={templates}
                />
            ) : null}
        </aside>
    );
}

function PaletteItem({
    type,
    label,
    description,
    icon: Icon,
    disabled,
    onAdd,
}: {
    type: QuestionnaireItemType;
    label: string;
    description: string;
    icon:
        | LucideIcon
        | ComponentType<{ className?: string; 'aria-hidden'?: boolean }>;
    disabled: boolean;
    onAdd: () => void;
}) {
    const { t } = useTranslation();
    const { attributes, listeners, setNodeRef, isDragging } = useDraggable({
        id: `palette-${type}`,
        data: { source: 'palette', type },
        disabled,
    });

    return (
        <div
            ref={setNodeRef}
            className={cn(
                'rounded-lg border border-border/70 bg-card p-2 shadow-xs',
                isDragging && 'opacity-40',
                disabled && 'opacity-60',
            )}
        >
            <div className="flex items-center gap-1.5">
                <button
                    type="button"
                    className={cn(
                        'flex min-w-0 flex-1 touch-none items-center gap-2 rounded-md px-1 py-1 text-left focus-visible:ring-[3px] focus-visible:ring-ring/50 focus-visible:outline-none',
                        !disabled && 'cursor-grab active:cursor-grabbing',
                    )}
                    aria-label={t('Drag :component', { component: label })}
                    title={description}
                    disabled={disabled}
                    {...listeners}
                    {...attributes}
                >
                    <span className="flex size-7 shrink-0 items-center justify-center rounded-md bg-secondary text-primary">
                        <Icon className="size-3.5" aria-hidden={true} />
                    </span>
                    <span className="min-w-0 truncate text-sm font-medium">
                        {label}
                    </span>
                </button>
                <Button
                    type="button"
                    size="icon"
                    variant="outline"
                    className="size-8 shrink-0"
                    disabled={disabled}
                    aria-label={t('Add :component', { component: label })}
                    onClick={onAdd}
                >
                    <Plus aria-hidden="true" />
                </Button>
            </div>
        </div>
    );
}
