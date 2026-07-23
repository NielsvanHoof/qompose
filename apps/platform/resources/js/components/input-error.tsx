import { type HTMLAttributes, useId } from 'react';
import { cn } from '@/lib/utils';

/**
 * Validation error text. Announced to assistive tech when it appears.
 * Pass a stable `id` (or use the generated one) with the field’s
 * `aria-describedby` and set `aria-invalid` on the control.
 */
export default function InputError({
    message,
    id,
    className = '',
    ...props
}: HTMLAttributes<HTMLParagraphElement> & { message?: string }) {
    const generatedId = useId();
    const errorId = id ?? generatedId;

    if (!message) {
        return null;
    }

    return (
        <p
            id={errorId}
            role="alert"
            aria-live="polite"
            {...props}
            className={cn('text-sm text-red-600 dark:text-red-400', className)}
        >
            {message}
        </p>
    );
}

/**
 * ARIA props that connect a control to an InputError with a known id.
 */
export function fieldErrorAriaProps(
    errorId: string,
    message?: string,
): {
    'aria-invalid'?: true;
    'aria-describedby'?: string;
} {
    if (!message) {
        return {};
    }

    return {
        'aria-invalid': true,
        'aria-describedby': errorId,
    };
}
