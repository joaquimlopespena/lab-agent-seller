import { Head } from '@inertiajs/react';
import {
    AlertCircleIcon,
    CheckCircle2Icon,
    Clock3Icon,
    PlugIcon,
    RefreshCwIcon,
    ShieldCheckIcon,
} from 'lucide-react';
import { start } from '@/actions/App/Http/Controllers/PipedriveOAuthController';
import { PageContainer } from '@/components/page-container';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardFooter,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { index } from '@/routes/admin/integrations';
import type { PipedriveConnectionSummary } from '@/types';

type ConnectionStatus =
    'disconnected' | 'pending' | 'active' | 'failed' | 'revoked' | 'expired';

type Props = {
    connection: PipedriveConnectionSummary | null;
    displayStatus: ConnectionStatus;
    canConnect: boolean;
    canReconnect: boolean;
};

const statusContent: Record<
    ConnectionStatus,
    {
        label: string;
        description: string;
        variant: 'default' | 'secondary' | 'destructive' | 'outline';
    }
> = {
    disconnected: {
        label: 'Desconectado',
        description:
            'Autorize uma conta Pipedrive para liberar a sincronização de leitura.',
        variant: 'outline',
    },
    pending: {
        label: 'Pendente',
        description:
            'A autorização foi iniciada e ainda precisa ser concluída no Pipedrive.',
        variant: 'secondary',
    },
    active: {
        label: 'Ativo',
        description:
            'A conta está autorizada e pronta para sincronizações de leitura.',
        variant: 'default',
    },
    failed: {
        label: 'Falhou',
        description:
            'A última tentativa não foi concluída. Inicie uma nova autorização.',
        variant: 'destructive',
    },
    revoked: {
        label: 'Revogado',
        description:
            'O acesso foi revogado no Pipedrive e precisa ser autorizado novamente.',
        variant: 'destructive',
    },
    expired: {
        label: 'Expirado',
        description: 'A autorização expirou e precisa ser renovada.',
        variant: 'destructive',
    },
};

function formatDate(value: string | null): string {
    if (value === null) {
        return 'Ainda não autorizada';
    }

    return new Intl.DateTimeFormat('pt-BR', {
        dateStyle: 'medium',
        timeStyle: 'short',
    }).format(new Date(value));
}

export default function IntegrationsIndex({
    connection,
    displayStatus,
    canConnect,
    canReconnect,
}: Props) {
    const status = statusContent[displayStatus];
    const showAction = canConnect || canReconnect;

    return (
        <>
            <Head title="Integrações" />
            <PageContainer
                title="Integrações"
                description="Gerencie as conexões de serviços da sua empresa."
            >
                {connection?.last_error && (
                    <Alert variant="destructive">
                        <AlertCircleIcon />
                        <AlertTitle>
                            Não foi possível concluir a conexão
                        </AlertTitle>
                        <AlertDescription>
                            {connection.last_error}
                        </AlertDescription>
                    </Alert>
                )}

                <Card>
                    <CardHeader className="flex-row items-start justify-between gap-4">
                        <div className="flex flex-col gap-2">
                            <div className="flex items-center gap-2">
                                <PlugIcon className="size-5 text-muted-foreground" />
                                <CardTitle>Pipedrive</CardTitle>
                            </div>
                            <CardDescription>
                                {status.description}
                            </CardDescription>
                        </div>
                        <Badge variant={status.variant}>{status.label}</Badge>
                    </CardHeader>

                    <CardContent>
                        <div className="grid gap-4 rounded-lg border bg-muted/30 p-4 sm:grid-cols-2">
                            <div className="flex items-start gap-3">
                                <ShieldCheckIcon className="mt-0.5 size-4 text-muted-foreground" />
                                <div className="flex flex-col gap-1">
                                    <p className="text-sm font-medium">
                                        Acesso somente leitura
                                    </p>
                                    <p className="text-sm text-muted-foreground">
                                        Negócios, pessoas, organizações,
                                        atividades, usuários e produtos.
                                    </p>
                                </div>
                            </div>
                            <div className="flex items-start gap-3">
                                <Clock3Icon className="mt-0.5 size-4 text-muted-foreground" />
                                <div className="flex flex-col gap-1">
                                    <p className="text-sm font-medium">
                                        Última autorização
                                    </p>
                                    <p className="text-sm text-muted-foreground">
                                        {formatDate(
                                            connection?.authorized_at ?? null,
                                        )}
                                    </p>
                                </div>
                            </div>
                        </div>
                    </CardContent>

                    {showAction && (
                        <CardFooter>
                            <Button asChild>
                                <a href={start.url()}>
                                    {connection ? (
                                        <RefreshCwIcon />
                                    ) : (
                                        <CheckCircle2Icon />
                                    )}
                                    {connection
                                        ? 'Reconectar Pipedrive'
                                        : 'Conectar Pipedrive'}
                                </a>
                            </Button>
                        </CardFooter>
                    )}
                </Card>
            </PageContainer>
        </>
    );
}

IntegrationsIndex.layout = {
    breadcrumbs: [{ title: 'Integrações', href: index() }],
};
