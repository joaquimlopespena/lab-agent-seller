import { Form, Head } from '@inertiajs/react';
import { CheckCircle2Icon, LoaderCircleIcon, MailIcon } from 'lucide-react';
import InputError from '@/components/input-error';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
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
import { store } from '@/actions/App/Http/Controllers/Customer/CustomerMagicLinkController';

type CustomerAccessRequestProps = {
    customerAccess?: 'sent' | 'not_associated' | null;
};

export default function CustomerAccessRequest({
    customerAccess,
}: CustomerAccessRequestProps) {
    return (
        <>
            <Head title="Acessar atendimento" />

            <div className="grid gap-6 md:grid-cols-[minmax(0,1fr)_18rem]">
                <Card>
                    <CardHeader>
                        <CardTitle>Receba seu link de acesso</CardTitle>
                        <CardDescription>
                            Use o e-mail associado ao seu cadastro de cliente.
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <Form action={store()} resetOnSuccess>
                            {({ errors, processing }) => (
                                <div className="flex flex-col gap-4">
                                    <div className="grid gap-2">
                                        <Label htmlFor="email">E-mail</Label>
                                        <Input
                                            id="email"
                                            name="email"
                                            type="email"
                                            placeholder="voce@exemplo.com"
                                            autoComplete="email"
                                            autoFocus
                                            required
                                            aria-describedby={
                                                errors.email
                                                    ? 'email-error'
                                                    : undefined
                                            }
                                            aria-invalid={Boolean(errors.email)}
                                        />
                                        <InputError
                                            id="email-error"
                                            message={errors.email}
                                        />
                                    </div>
                                    <Button type="submit" disabled={processing}>
                                        {processing && (
                                            <LoaderCircleIcon
                                                className="animate-spin"
                                                aria-hidden="true"
                                            />
                                        )}
                                        {processing
                                            ? 'Enviando...'
                                            : 'Enviar link de acesso'}
                                    </Button>
                                </div>
                            )}
                        </Form>
                    </CardContent>
                </Card>

                {customerAccess ? (
                    <Alert
                        variant={
                            customerAccess === 'sent'
                                ? 'default'
                                : 'destructive'
                        }
                        aria-live="polite"
                    >
                        {customerAccess === 'sent' ? (
                            <CheckCircle2Icon aria-hidden="true" />
                        ) : (
                            <MailIcon aria-hidden="true" />
                        )}
                        <AlertTitle>
                            {customerAccess === 'sent'
                                ? 'Confira seu e-mail'
                                : 'E-mail não associado'}
                        </AlertTitle>
                        <AlertDescription>
                            {customerAccess === 'sent'
                                ? 'Enviamos um link de uso único para continuar.'
                                : 'Não encontramos uma empresa associada a este e-mail.'}
                        </AlertDescription>
                    </Alert>
                ) : (
                    <Alert>
                        <MailIcon aria-hidden="true" />
                        <AlertTitle>Acesso seguro</AlertTitle>
                        <AlertDescription>
                            O link é temporário e pode ser usado somente uma
                            vez.
                        </AlertDescription>
                    </Alert>
                )}
            </div>
        </>
    );
}

CustomerAccessRequest.layout = {
    title: 'Acesse o atendimento',
    description:
        'Informe seu e-mail para continuar com segurança, sem acessar a área administrativa.',
};
