import type { ReactNode } from 'react';
import { Button } from '@/components/ui/button';
import {
    Sheet,
    SheetContent,
    SheetHeader,
    SheetTitle,
    SheetTrigger,
} from '@/components/ui/sheet';
import { getQuestionnaireItemTypeDefinition } from '@/features/document-requests/questionnaire-item-type-registry';
import type { QuestionnaireItemType } from '@/features/document-requests/types';
import { useTranslation } from '@/hooks/use-translation';

/** Full-height, independently scrolling side pane for desktop. */
export function BuilderPane({ children }: { children: ReactNode }) {
    return (
        <div className="h-full min-h-0 overflow-y-auto overscroll-contain">
            {children}
        </div>
    );
}

/** Bottom sheet used below the desktop breakpoint for palette/settings. */
export function BuilderSheet({
    open,
    onOpenChange,
    label,
    children,
}: {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    label: string;
    children: ReactNode;
}) {
    return (
        <Sheet open={open} onOpenChange={onOpenChange}>
            <SheetTrigger asChild>
                <Button type="button" variant="outline">
                    {label}
                </Button>
            </SheetTrigger>
            <SheetContent
                side="bottom"
                className="max-h-[85dvh] overflow-y-auto"
            >
                <SheetHeader>
                    <SheetTitle>{label}</SheetTitle>
                </SheetHeader>
                <div className="px-1 pb-4">{children}</div>
            </SheetContent>
        </Sheet>
    );
}

export function PaletteOverlay({ type }: { type: QuestionnaireItemType }) {
    const { t } = useTranslation();
    const definition = getQuestionnaireItemTypeDefinition(type);
    const Icon = definition.icon;

    return (
        <div className="flex items-center gap-2 rounded-xl border border-primary bg-card px-3 py-2 shadow-md">
            <Icon className="size-4 text-primary" aria-hidden="true" />
            <span className="text-sm font-medium">{t(definition.label)}</span>
        </div>
    );
}
