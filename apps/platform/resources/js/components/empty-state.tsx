import { Inbox, type LucideIcon } from 'lucide-react';
import type { ReactNode } from 'react';
import { cn } from '@/lib/utils';

type EmptyStateVariant = 'inline' | 'compact' | 'panel';

type EmptyStateProps = {
    title: string;
    description?: string;
    icon?: LucideIcon;
    action?: ReactNode;
    variant?: EmptyStateVariant;
    bordered?: boolean;
    className?: string;
};

/**
 * Shared empty-data placeholder for lists, cards, and panels.
 */
export default function EmptyState({
    title,
    description,
    icon: Icon = Inbox,
    action,
    variant = 'inline',
    bordered = false,
    className,
}: EmptyStateProps) {
    if (variant === 'compact') {
        return (
            <div
                className={cn(
                    'px-3 py-8 text-center text-sm text-muted-foreground',
                    className,
                )}
            >
                <p className="font-medium text-foreground">{title}</p>
                {description && <p className="mt-1">{description}</p>}
                {action && <div className="mt-4">{action}</div>}
            </div>
        );
    }

    if (variant === 'panel') {
        return (
            <div
                className={cn(
                    'p-8 text-center',
                    bordered && 'rounded-lg border',
                    className,
                )}
            >
                <div className="mx-auto mb-4 flex size-14 items-center justify-center rounded-2xl bg-muted">
                    <Icon
                        className="size-7 text-muted-foreground"
                        aria-hidden
                    />
                </div>
                <p className="font-medium">{title}</p>
                {description && (
                    <p className="mt-1 text-sm text-muted-foreground">
                        {description}
                    </p>
                )}
                {action && <div className="mt-4">{action}</div>}
            </div>
        );
    }

    return (
        <div
            className={cn(
                'text-sm text-muted-foreground',
                bordered && 'rounded-md border px-4 py-3',
                className,
            )}
        >
            <p>{title}</p>
            {description && <p className="mt-1">{description}</p>}
            {action && <div className="mt-4">{action}</div>}
        </div>
    );
}
