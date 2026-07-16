import { Form } from '@inertiajs/react';
import DocumentRequestController from '@/actions/App/Http/Controllers/Dossiers/DocumentRequestController';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
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

/**
 * Sidebar form to add one questionnaire item to a dossier.
 */
export default function AddDocumentRequestCard({
    dossierId,
}: {
    dossierId: number;
}) {
    const currentWorkspace = useCurrentWorkspace();

    return (
        <Card>
            <CardHeader>
                <CardTitle>Add request</CardTitle>
                <CardDescription>
                    Ask for a file, a text answer, or a yes/no confirmation.
                </CardDescription>
            </CardHeader>
            <CardContent>
                <Form
                    {...DocumentRequestController.store.form({
                        tenant: currentWorkspace,
                        dossier: dossierId,
                    })}
                    resetOnSuccess
                    className="space-y-4"
                >
                    {({ errors, processing }) => (
                        <>
                            <div className="grid gap-2">
                                <Label htmlFor="type">Type</Label>
                                <Select
                                    required
                                    defaultValue="file"
                                    name="type"
                                >
                                    <SelectTrigger className="w-full">
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent className="bg-background">
                                        <SelectItem value="file">
                                            File upload
                                        </SelectItem>
                                        <SelectItem value="text">
                                            Text answer
                                        </SelectItem>
                                        <SelectItem value="boolean">
                                            Yes / no
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                                <InputError message={errors.type} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="title">Title</Label>
                                <Input
                                    id="title"
                                    name="title"
                                    required
                                    placeholder="Payslip January 2025"
                                />
                                <InputError message={errors.title} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="instructions">
                                    Instructions (optional)
                                </Label>
                                <textarea
                                    id="instructions"
                                    name="instructions"
                                    rows={4}
                                    className="rounded-md border border-input bg-background px-3 py-2 text-sm shadow-xs outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50"
                                    placeholder="Upload the PDF you received from your employer."
                                />
                                <InputError message={errors.instructions} />
                            </div>

                            <Button disabled={processing} className="w-full">
                                Add request
                            </Button>
                        </>
                    )}
                </Form>
            </CardContent>
        </Card>
    );
}
