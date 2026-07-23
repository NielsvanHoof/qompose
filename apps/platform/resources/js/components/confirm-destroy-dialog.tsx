import { Form } from '@inertiajs/react';
import type { ComponentProps, ReactNode } from 'react';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import { useTranslation } from '@/hooks/use-translation';

type FormProps = ComponentProps<typeof Form>;

type ConfirmDestroyDialogProps = {
    title: string;
    description: string;
    confirmLabel: string;
    /** Trigger control (usually a Button). Wrapped with DialogTrigger asChild. */
    trigger: ReactNode;
    /** Wayfinder `Controller.destroy.form(...)` props (without children). */
    form: Omit<FormProps, 'children' | 'options'>;
    options?: FormProps['options'];
    confirmTestId?: string;
};

/**
 * Shared confirmation dialog for destructive Inertia Form actions.
 * Mirrors the archive-dossier / delete-user Dialog pattern.
 */
export default function ConfirmDestroyDialog({
    title,
    description,
    confirmLabel,
    trigger,
    form,
    options,
    confirmTestId,
}: ConfirmDestroyDialogProps) {
    const { t } = useTranslation();

    return (
        <Dialog>
            <DialogTrigger asChild>{trigger}</DialogTrigger>
            <DialogContent>
                <DialogTitle>{title}</DialogTitle>
                <DialogDescription>{description}</DialogDescription>

                <Form {...form} options={options}>
                    {({ processing }) => (
                        <DialogFooter className="gap-2">
                            <DialogClose asChild>
                                <Button type="button" variant="secondary">
                                    {t('Cancel')}
                                </Button>
                            </DialogClose>
                            <Button
                                type="submit"
                                variant="destructive"
                                disabled={processing}
                                data-test={confirmTestId}
                            >
                                {confirmLabel}
                            </Button>
                        </DialogFooter>
                    )}
                </Form>
            </DialogContent>
        </Dialog>
    );
}
