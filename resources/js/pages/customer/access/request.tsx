import { Head } from '@inertiajs/react';
import { MailIcon } from 'lucide-react';
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

export default function CustomerAccessRequest() {
    return (
        <>
            <Head title="Acessar atendimento" />

            <div className="grid gap-6 md:grid-cols-[minmax(0,1fr)_18rem]">
                <Card>
                    <CardHeader>
                        <CardTitle>Receba seu link de acesso</CardTitle>
                        <CardDescription>
                            Use o e-mail cadastrado por uma das empresas que
                            você atende.
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <form className="flex flex-col gap-4">
                            <div className="grid gap-2">
                                <Label htmlFor="email">E-mail</Label>
                                <Input
                                    id="email"
                                    name="email"
                                    type="email"
                                    placeholder="voce@exemplo.com"
                                    autoComplete="email"
                                    disabled
                                />
                            </div>
                            <Button type="button" disabled>
                                Enviar link de acesso
                            </Button>
                        </form>
                    </CardContent>
                </Card>

                <Alert>
                    <MailIcon aria-hidden="true" />
                    <AlertTitle>Acesso seguro</AlertTitle>
                    <AlertDescription>
                        O envio do link será habilitado quando a autenticação de
                        clientes estiver disponível.
                    </AlertDescription>
                </Alert>
            </div>
        </>
    );
}

CustomerAccessRequest.layout = {
    title: 'Acesse o atendimento',
    description:
        'Informe seu e-mail para continuar com segurança, sem acessar a área administrativa.',
};
