import { Head, Link } from '@inertiajs/react';
import { CheckCircle2Icon } from 'lucide-react';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import { index } from '@/routes/customer/companies';

export default function CustomerAccessConfirmation() {
    return (
        <>
            <Head title="Acesso confirmado" />

            <Alert aria-live="polite">
                <CheckCircle2Icon aria-hidden="true" />
                <AlertTitle>Acesso confirmado</AlertTitle>
                <AlertDescription>
                    Seu link foi validado e a sessão temporária está ativa.
                </AlertDescription>
            </Alert>

            <Button asChild className="mt-6 self-start">
                <Link href={index()}>Continuar</Link>
            </Button>
        </>
    );
}

CustomerAccessConfirmation.layout = {
    title: 'Tudo certo',
    description: 'Você já pode continuar para o atendimento.',
};
