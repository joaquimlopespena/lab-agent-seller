import { Head, Link } from '@inertiajs/react';
import {
    ArrowRightIcon,
    DatabaseIcon,
    MessagesSquareIcon,
    PlugIcon,
    RefreshCwIcon,
    UsersIcon,
} from 'lucide-react';
import { PageContainer } from '@/components/page-container';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardFooter,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { dashboard } from '@/routes';
import { index as conversationsIndex } from '@/routes/admin/conversations';
import { index as integrationsIndex } from '@/routes/admin/integrations';
import { index as syncedDataIndex } from '@/routes/admin/synced-data';
import type { CompanySummary, DashboardSummary } from '@/types';

type Props = {
    company: CompanySummary;
    summary: DashboardSummary;
};

const metricCards = [
    {
        key: 'sync_runs_count',
        label: 'Sincronizações',
        icon: RefreshCwIcon,
    },
    {
        key: 'synced_records_count',
        label: 'Dados sincronizados',
        icon: DatabaseIcon,
    },
    {
        key: 'customers_count',
        label: 'Clientes',
        icon: UsersIcon,
    },
    {
        key: 'conversations_count',
        label: 'Conversas',
        icon: MessagesSquareIcon,
    },
] as const;

export default function Dashboard({ company, summary }: Props) {
    return (
        <>
            <Head title="Dashboard" />
            <PageContainer
                title={company.name}
                description="Resumo do ambiente administrativo da sua empresa."
            >
                <div className="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                    {metricCards.map(({ key, label, icon: Icon }) => (
                        <Card key={key} className="gap-3">
                            <CardHeader className="flex-row items-center justify-between gap-3">
                                <CardDescription>{label}</CardDescription>
                                <Icon className="size-4 text-muted-foreground" />
                            </CardHeader>
                            <CardContent>
                                <p className="text-3xl font-semibold">
                                    {summary[key]}
                                </p>
                            </CardContent>
                        </Card>
                    ))}
                </div>

                <div className="grid gap-4 lg:grid-cols-3">
                    <Card>
                        <CardHeader>
                            <PlugIcon className="size-5 text-muted-foreground" />
                            <CardTitle>Conexão CRM</CardTitle>
                            <CardDescription>
                                {summary.crm_connection
                                    ? `Pipedrive: ${summary.crm_connection.status.name}`
                                    : 'Conecte o Pipedrive para iniciar a configuração.'}
                            </CardDescription>
                        </CardHeader>
                        <CardFooter>
                            <Button asChild variant="outline">
                                <Link href={integrationsIndex()}>
                                    Acessar conexão
                                    <ArrowRightIcon />
                                </Link>
                            </Button>
                        </CardFooter>
                    </Card>

                    <Card>
                        <CardHeader>
                            <DatabaseIcon className="size-5 text-muted-foreground" />
                            <CardTitle>Dados sincronizados</CardTitle>
                            <CardDescription>
                                Consulte os registros importados do CRM.
                            </CardDescription>
                        </CardHeader>
                        <CardFooter>
                            <Button asChild variant="outline">
                                <Link href={syncedDataIndex()}>
                                    Consultar dados
                                    <ArrowRightIcon />
                                </Link>
                            </Button>
                        </CardFooter>
                    </Card>

                    <Card>
                        <CardHeader>
                            <MessagesSquareIcon className="size-5 text-muted-foreground" />
                            <CardTitle>Conversas</CardTitle>
                            <CardDescription>
                                Acompanhe as conversas dos clientes da empresa.
                            </CardDescription>
                        </CardHeader>
                        <CardFooter>
                            <Button asChild variant="outline">
                                <Link href={conversationsIndex()}>
                                    Ver conversas
                                    <ArrowRightIcon />
                                </Link>
                            </Button>
                        </CardFooter>
                    </Card>
                </div>
            </PageContainer>
        </>
    );
}

Dashboard.layout = {
    breadcrumbs: [
        {
            title: 'Dashboard',
            href: dashboard(),
        },
    ],
};
