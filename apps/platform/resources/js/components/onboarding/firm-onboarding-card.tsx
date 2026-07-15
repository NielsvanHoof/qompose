import { Form } from '@inertiajs/react';
import { Building2 } from 'lucide-react';
import Heading from '@/components/heading';
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
import { store } from '@/routes/onboarding/firm';

/**
 * First-run card to create the accounting firm.
 */
export default function FirmOnboardingCard() {
    return (
        <Card className="w-full">
            <CardHeader>
                <Building2 className="size-8 text-muted-foreground" />
                <CardTitle>Set up your firm</CardTitle>
                <CardDescription>
                    Start by adding the accounting firm that will manage client
                    dossiers.
                </CardDescription>
            </CardHeader>
            <CardContent>
                <Heading
                    title="What is your firm called?"
                    description="Next, you will add your first client and create a dossier."
                />

                <Form {...store.form()} className="space-y-4">
                    {({ errors, processing }) => (
                        <>
                            <div className="grid gap-2">
                                <Label htmlFor="name">Firm name</Label>
                                <Input
                                    id="name"
                                    name="name"
                                    required
                                    autoComplete="organization"
                                    placeholder="Acme Accountants"
                                    autoFocus
                                />
                                <InputError message={errors.name} />
                            </div>

                            <Button disabled={processing}>
                                Continue to your first client
                            </Button>
                        </>
                    )}
                </Form>
            </CardContent>
        </Card>
    );
}
