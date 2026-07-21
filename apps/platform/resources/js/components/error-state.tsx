import { AlertCircleIcon } from 'lucide-react';
import type { ReactNode } from 'react';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { cn } from '@/lib/utils';

type ErrorStateVariant = 'alert' | 'inline';

type ErrorStateProps = {
    title?: string;
    description?: ReactNode;
    action?: ReactNode;
    variant?: ErrorStateVariant;
    className?: string;
};

/**
 * Section-level error feedback for failed loads, processing, or recoverable faults.
 * Use AlertError for form validation error lists.
 */
export default function ErrorState({
    title = 'Something went wrong',
    description,
    action,
    variant = 'alert',
    className,
}: ErrorStateProps) {
    if (variant === 'inline') {
        return (
            <div className={cn('space-y-3 text-sm', className)}>
                <p className="text-destructive">
                    <span className="font-medium">{title}</span>
                    {description ? (
                        <>
                            {': '}
                            {description}
                        </>
                    ) : null}
                </p>
                {action}
            </div>
        );
    }

    return (
        <Alert variant="destructive" className={className}>
            <AlertCircleIcon />
            <AlertTitle>{title}</AlertTitle>
            {description && <AlertDescription>{description}</AlertDescription>}
            {action && <div className="col-start-2 mt-2">{action}</div>}
        </Alert>
    );
}
