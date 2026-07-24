import type { ReactNode } from 'react';
import { Button } from '@/components/ui/button';
import {
    Sheet,
    SheetContent,
    SheetHeader,
    SheetTitle,
    SheetTrigger,
} from '@/components/ui/sheet';
import { Spinner } from '@/components/ui/spinner';
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

/**
 * Form-slot placeholder at the drop index while create is in flight.
 * Announcements stay on the builder root live region — no duplicate aria-live here.
 */
export function BuilderInsertPlaceholder({ index }: { index: number }) {
    const { t } = useTranslation();

    return (
        <li
            className="flex list-none items-center gap-1.5 rounded-xl border border-dashed border-border/70 bg-card/70 px-2 py-1.5"
            aria-busy="true"
        >
            {/* Spacer matches the drag-handle column so the index lines up with real rows. */}
            <span className="size-8 shrink-0" aria-hidden="true" />
            <span className="w-5 shrink-0 font-data text-xs text-muted-foreground/50 tabular-nums">
                {index + 1}
            </span>
            <Spinner className="size-3.5 text-muted-foreground" aria-hidden />
            <span className="sr-only">{t('Adding component…')}</span>
        </li>
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
                className="max-h-[85dvh] overflow-y-auto overscroll-contain"
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
