import { Head } from '@inertiajs/react';
import { Building2Icon } from 'lucide-react';
import { CustomerPageState } from '@/components/customer-page-state';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';

export default function CustomerCompaniesIndex() {
    return (
        <>
            <Head title="Escolher empresa" />

            <Card>
                <CardHeader>
                    <div className="flex items-center gap-3">
                        <div className="flex size-10 items-center justify-center rounded-full bg-muted text-muted-foreground">
                            <Building2Icon
                                aria-hidden="true"
                                className="size-5"
                            />
                        </div>
                        <div className="grid gap-1">
                            <CardTitle>Empresas disponíveis</CardTitle>
                            <CardDescription>
                                A conversa ficará vinculada somente à empresa
                                escolhida.
                            </CardDescription>
                        </div>
                    </div>
                </CardHeader>
                <CardContent>
                    <CustomerPageState
                        state="empty"
                        title="Nenhuma empresa disponível"
                        description="As empresas associadas ao seu e-mail aparecerão aqui após a autenticação."
                    />
                </CardContent>
            </Card>
        </>
    );
}

CustomerCompaniesIndex.layout = {
    title: 'Com qual empresa deseja conversar?',
    description:
        'Selecione uma empresa associada ao seu acesso para definir o contexto da conversa.',
};
