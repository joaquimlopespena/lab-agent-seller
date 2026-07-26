import { Form, Head } from '@inertiajs/react';
import {
    AlertCircleIcon,
    CheckCircle2Icon,
    Clock3Icon,
    PlugIcon,
    RefreshCwIcon,
    ShieldCheckIcon,
} from 'lucide-react';
import { default as synchronize } from '@/actions/App/Http/Controllers/Admin/StartPipedriveSyncController';
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
import type { CrmSyncRunSummary, PipedriveConnectionSummary } from '@/types';

type ConnectionStatus =
    'disconnected' | 'pending' | 'active' | 'failed' | 'revoked' | 'expired';

type Props = {
    connection: PipedriveConnectionSummary | null;
    displayStatus: ConnectionStatus;
    canConnect: boolean;
    canReconnect: boolean;
    canSynchronize: boolean;
    syncRuns: CrmSyncRunSummary[];
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

const syncStatusContent: Record<
    string,
    {
        label: string;
        variant: 'default' | 'secondary' | 'destructive' | 'outline';
    }
> = {
    pending: { label: 'Pendente', variant: 'outline' },
    running: { label: 'Em andamento', variant: 'secondary' },
    succeeded: { label: 'Concluída', variant: 'default' },
    failed: { label: 'Falhou', variant: 'destructive' },
};

export default function IntegrationsIndex({
    connection,
    displayStatus,
    canConnect,
    canReconnect,
    canSynchronize,
    syncRuns,
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

                {connection && (
                    <Card>
                        <CardHeader className="flex-row items-start justify-between gap-4">
                            <div className="flex flex-col gap-2">
                                <CardTitle>Sincronizações</CardTitle>
                                <CardDescription>
                                    Histórico das importações manuais do
                                    Pipedrive.
                                </CardDescription>
                            </div>
                            {canSynchronize && (
                                <Form {...synchronize.form()}>
                                    {({ processing }) => (
                                        <Button
                                            type="submit"
                                            disabled={processing}
                                        >
                                            <RefreshCwIcon
                                                className={
                                                    processing
                                                        ? 'animate-spin'
                                                        : undefined
                                                }
                                            />
                                            {syncRuns.some(
                                                (run) =>
                                                    run.status.slug ===
                                                    'failed',
                                            )
                                                ? 'Tentar novamente'
                                                : 'Sincronizar agora'}
                                        </Button>
                                    )}
                                </Form>
                            )}
                        </CardHeader>
                        <CardContent>
                            {syncRuns.length === 0 ? (
                                <div className="rounded-lg border border-dashed p-6 text-center text-sm text-muted-foreground">
                                    Nenhuma sincronização foi executada.
                                </div>
                            ) : (
                                <div className="overflow-x-auto rounded-lg border">
                                    <table className="w-full text-sm">
                                        <thead className="bg-muted/50 text-left">
                                            <tr>
                                                <th className="px-4 py-3 font-medium">
                                                    Execução
                                                </th>
                                                <th className="px-4 py-3 font-medium">
                                                    Estado
                                                </th>
                                                <th className="px-4 py-3 font-medium">
                                                    Início
                                                </th>
                                                <th className="px-4 py-3 font-medium">
                                                    Término
                                                </th>
                                                <th className="px-4 py-3 font-medium">
                                                    Resultado
                                                </th>
                                            </tr>
                                        </thead>
                                        <tbody className="divide-y">
                                            {syncRuns.map((run) => {
                                                const runStatus =
                                                    syncStatusContent[
                                                        run.status.slug
                                                    ] ?? {
                                                        label: run.status.name,
                                                        variant:
                                                            'outline' as const,
                                                    };

                                                return (
                                                    <tr key={run.id}>
                                                        <td className="px-4 py-3 font-medium">
                                                            #{run.id}
                                                        </td>
                                                        <td className="px-4 py-3">
                                                            <Badge
                                                                variant={
                                                                    runStatus.variant
                                                                }
                                                            >
                                                                {
                                                                    runStatus.label
                                                                }
                                                            </Badge>
                                                        </td>
                                                        <td className="px-4 py-3 text-muted-foreground">
                                                            {formatDate(
                                                                run.started_at,
                                                            )}
                                                        </td>
                                                        <td className="px-4 py-3 text-muted-foreground">
                                                            {formatDate(
                                                                run.finished_at,
                                                            )}
                                                        </td>
                                                        <td className="max-w-sm px-4 py-3 text-muted-foreground">
                                                            {run.error_message ??
                                                                'Sem erros registrados'}
                                                        </td>
                                                    </tr>
                                                );
                                            })}
                                        </tbody>
                                    </table>
                                </div>
                            )}
                        </CardContent>
                    </Card>
                )}
            </PageContainer>
        </>
    );
}

IntegrationsIndex.layout = {
    breadcrumbs: [{ title: 'Integrações', href: index() }],
};
