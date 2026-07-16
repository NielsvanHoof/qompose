import { Form, router } from '@inertiajs/react';
import { Trash2 } from 'lucide-react';
import QuestionnaireTemplateItemController from '@/actions/App/Http/Controllers/Questionnaires/QuestionnaireTemplateItemController';
import InputError from '@/components/input-error';
import SortableList from '@/components/sortable/sortable-list';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { useCurrentWorkspace } from '@/hooks/use-current-workspace';
import type { TemplateItem } from '@/types';

const ITEM_TYPES = [
    { value: 'file', label: 'File upload' },
    { value: 'text', label: 'Text answer' },
    { value: 'boolean', label: 'Yes / no' },
];

/**
 * Add, edit, reorder, and delete items on a firm-owned template.
 */
export default function TemplateItemEditor({
    templateId,
    items,
    canManage,
}: {
    templateId: number;
    items: TemplateItem[];
    canManage: boolean;
}) {
    const currentWorkspace = useCurrentWorkspace();

    const persistOrder = (orderedIds: number[]) => {
        router.post(
            QuestionnaireTemplateItemController.reorder.url({
                tenant: currentWorkspace,
                template: templateId,
            }),
            { item_ids: orderedIds },
            { preserveScroll: true },
        );
    };

    return (
        <div className="space-y-6">
            {items.length === 0 ? (
                <div className="rounded-md border px-4 py-3">
                    <p className="text-sm text-muted-foreground">
                        No items yet. Add your first question below.
                    </p>
                </div>
            ) : (
                <SortableList
                    items={items}
                    enabled={canManage}
                    onReorder={persistOrder}
                    className="divide-y rounded-md border"
                    renderItem={(item, { DragHandle }) => (
                        <div className="space-y-3 px-4 py-4">
                            <div className="flex flex-wrap items-center justify-between gap-2">
                                <div className="flex items-center gap-1">
                                    {canManage && <DragHandle />}
                                    <Badge variant="outline">{item.type}</Badge>
                                </div>
                                {canManage && (
                                    <Form
                                        {...QuestionnaireTemplateItemController.destroy.form(
                                            {
                                                tenant: currentWorkspace,
                                                template: templateId,
                                                item: item.id,
                                            },
                                        )}
                                    >
                                        {({ processing }) => (
                                            <Button
                                                type="submit"
                                                size="icon"
                                                variant="ghost"
                                                disabled={processing}
                                            >
                                                <Trash2 />
                                            </Button>
                                        )}
                                    </Form>
                                )}
                            </div>

                            {canManage ? (
                                <Form
                                    {...QuestionnaireTemplateItemController.update.form(
                                        {
                                            tenant: currentWorkspace,
                                            template: templateId,
                                            item: item.id,
                                        },
                                    )}
                                    className="grid gap-3"
                                >
                                    {({ errors, processing }) => (
                                        <>
                                            <ItemFields
                                                defaults={item}
                                                errors={errors}
                                            />
                                            <Button
                                                type="submit"
                                                size="sm"
                                                disabled={processing}
                                                className="w-fit"
                                            >
                                                Save item
                                            </Button>
                                        </>
                                    )}
                                </Form>
                            ) : (
                                <div>
                                    <p className="font-medium">{item.title}</p>
                                    {item.instructions && (
                                        <p className="mt-1 text-sm text-muted-foreground">
                                            {item.instructions}
                                        </p>
                                    )}
                                </div>
                            )}
                        </div>
                    )}
                />
            )}

            {canManage && (
                <Form
                    {...QuestionnaireTemplateItemController.store.form({
                        tenant: currentWorkspace,
                        template: templateId,
                    })}
                    resetOnSuccess
                    className="space-y-3 rounded-md border p-4"
                >
                    {({ errors, processing }) => (
                        <>
                            <p className="text-sm font-medium">Add item</p>
                            <ItemFields
                                defaults={{
                                    type: 'file',
                                    title: '',
                                    instructions: '',
                                }}
                                errors={errors}
                            />
                            <Button type="submit" disabled={processing}>
                                Add item
                            </Button>
                        </>
                    )}
                </Form>
            )}
        </div>
    );
}

function ItemFields({
    defaults,
    errors,
}: {
    defaults: {
        type: string;
        title: string;
        instructions: string | null;
    };
    errors: Partial<Record<string, string>>;
}) {
    return (
        <>
            <div className="grid gap-2">
                <Label>Type</Label>
                <Select required defaultValue={defaults.type} name="type">
                    <SelectTrigger className="w-full">
                        <SelectValue />
                    </SelectTrigger>
                    <SelectContent className="bg-background">
                        {ITEM_TYPES.map((type) => (
                            <SelectItem key={type.value} value={type.value}>
                                {type.label}
                            </SelectItem>
                        ))}
                    </SelectContent>
                </Select>
                <InputError message={errors.type} />
            </div>
            <div className="grid gap-2">
                <Label>Title</Label>
                <Input
                    name="title"
                    required
                    defaultValue={defaults.title}
                    placeholder="Kopie identiteitsbewijs"
                />
                <InputError message={errors.title} />
            </div>
            <div className="grid gap-2">
                <Label>Instructions (optional)</Label>
                <textarea
                    name="instructions"
                    rows={2}
                    defaultValue={defaults.instructions ?? ''}
                    className="rounded-md border border-input bg-background px-3 py-2 text-sm shadow-xs outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50"
                />
                <InputError message={errors.instructions} />
            </div>
        </>
    );
}
