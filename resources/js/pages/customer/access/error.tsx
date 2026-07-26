import { Head, Link } from '@inertiajs/react';
import { AlertCircleIcon } from 'lucide-react';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import { request } from '@/routes/customer/access';

type CustomerAccessErrorProps = {
    reason: 'invalid' | 'expired';
};

export default function CustomerAccessError({
    reason,
}: CustomerAccessErrorProps) {
    const expired = reason === 'expired';

    return (
        <>
            <Head title={expired ? 'Link expirado' : 'Link inválido'} />

            <Alert variant="destructive" aria-live="assertive">
                <AlertCircleIcon aria-hidden="true" />
                <AlertTitle>
                    {expired ? 'Este link expirou' : 'Este link não é válido'}
                </AlertTitle>
                <AlertDescription>
                    Solicite um novo link para tentar novamente.
                </AlertDescription>
            </Alert>

            <Button asChild variant="outline" className="mt-6 self-start">
                <Link href={request()}>Solicitar novo link</Link>
            </Button>
        </>
    );
}

CustomerAccessError.layout = {
    title: 'Não foi possível autenticar',
    description:
        'Por segurança, nenhuma informação de cliente ou empresa é exibida.',
};
