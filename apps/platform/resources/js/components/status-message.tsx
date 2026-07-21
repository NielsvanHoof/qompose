import type { ReactNode } from 'react';
import { cn } from '@/lib/utils';

type StatusMessageProps = {
    children: ReactNode;
    className?: string;
    variant?: 'success';
};

/**
 * Inline feedback banner for success states, using theme semantic tokens.
 */
export default function StatusMessage({
    children,
    className,
    variant = 'success',
}: StatusMessageProps) {
    return (
        <div
            role="status"
            className={cn(
                variant === 'success' &&
                    'rounded-md border border-success-border bg-success-muted px-4 py-3 text-sm font-medium text-success-foreground',
                className,
            )}
        >
            {children}
        </div>
    );
}
