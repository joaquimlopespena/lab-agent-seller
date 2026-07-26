import { Head, Link } from '@inertiajs/react';
import { ArrowLeftIcon, LockKeyholeIcon } from 'lucide-react';
import { PageContainer } from '@/components/page-container';
import {
    SyncedDataPageState,
    useSyncedDataPageStatus,
} from '@/components/synced-data-page-state';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { index } from '@/routes/admin/synced-data';

type RelationItem = {
    id: number;
    title?: string;
    email?: string;
    label?: string;
    is_primary?: boolean;
    pivot?: Record<string, string | number | null>;
};

type Props = {
    category: { slug: string; label: string };
    record: {
        id: number;
        external_id?: number;
        title: string;
        subtitle?: string;
        active?: boolean;
        fields: Record<string, string | number | boolean | null>;
        relations: Record<string, RelationItem | RelationItem[]>;
    };
    readOnly: true;
};

const labels: Record<string, string> = {
    display_order: 'Ordem',
    is_active: 'Ativo',
    synced_at: 'Sincronizado em',
    pipedrive_key: 'Chave Pipedrive',
    entity_type: 'Entidade',
    field_type: 'Tipo',
    email: 'E-mail',
    address: 'Endereço',
    value: 'Valor',
    currency: 'Moeda',
    code: 'Código',
    unit_price: 'Preço unitário',
    due_at: 'Vencimento',
    is_done: 'Concluída',
    stages: 'Etapas',
    pipeline: 'Pipeline',
    owner: 'Responsável',
    people: 'Pessoas',
    organization: 'Organização',
    emails: 'E-mails',
    stage: 'Etapa',
    person: 'Pessoa',
    products: 'Produtos',
    deals: 'Negócios',
    deal: 'Negócio',
};

function display(value: string | number | boolean | null) {
    if (value === null || value === '') {
        return '—';
    }

    if (typeof value === 'boolean') {
        return value ? 'Sim' : 'Não';
    }

    return String(value);
}

export default function SyncedDataShow({ category, record }: Props) {
    const pageStatus = useSyncedDataPageStatus();

    return (
        <>
            <Head title={`${record.title} — ${category.label}`} />
            <PageContainer
                title={record.title}
                description={`Detalhes de ${category.label.toLowerCase()} importados do Pipedrive.`}
            >
                <div className="flex flex-wrap items-center justify-between gap-3">
                    <Button asChild variant="outline" size="sm">
                        <Link href={index({ category: category.slug })}>
                            <ArrowLeftIcon aria-hidden className="size-4" />
                            Voltar à lista
                        </Link>
                    </Button>
                    <Badge variant="outline">
                        <LockKeyholeIcon aria-hidden className="size-3" />
                        Somente leitura
                    </Badge>
                </div>

                {pageStatus === 'idle' ? (
                    <div className="grid gap-4 lg:grid-cols-2">
                        <Card>
                            <CardHeader>
                                <CardTitle>Informações</CardTitle>
                            </CardHeader>
                            <CardContent>
                                <dl className="grid gap-4 sm:grid-cols-2">
                                    <div>
                                        <dt className="text-sm text-muted-foreground">
                                            ID Pipedrive
                                        </dt>
                                        <dd className="font-medium">
                                            {record.external_id ?? '—'}
                                        </dd>
                                    </div>
                                    {Object.entries(record.fields).map(
                                        ([key, value]) => (
                                            <div key={key}>
                                                <dt className="text-sm text-muted-foreground">
                                                    {labels[key] ?? key}
                                                </dt>
                                                <dd className="font-medium wrap-break-word">
                                                    {display(value)}
                                                </dd>
                                            </div>
                                        ),
                                    )}
                                </dl>
                            </CardContent>
                        </Card>

                        <Card>
                            <CardHeader>
                                <CardTitle>Relações</CardTitle>
                            </CardHeader>
                            <CardContent className="flex flex-col gap-5">
                                {Object.keys(record.relations).length === 0 ? (
                                    <p className="text-sm text-muted-foreground">
                                        Nenhuma relação disponível para este
                                        tipo.
                                    </p>
                                ) : (
                                    Object.entries(record.relations).map(
                                        ([name, related]) => {
                                            const items = Array.isArray(related)
                                                ? related
                                                : related
                                                  ? [related]
                                                  : [];

                                            return (
                                                <section
                                                    key={name}
                                                    className="flex flex-col gap-2"
                                                >
                                                    <h3 className="text-sm font-medium">
                                                        {labels[name] ?? name}
                                                    </h3>
                                                    {items.length === 0 ? (
                                                        <p className="text-sm text-muted-foreground">
                                                            Não informado
                                                        </p>
                                                    ) : (
                                                        <div className="grid gap-2">
                                                            {items.map(
                                                                (item) => (
                                                                    <div
                                                                        key={
                                                                            item.id
                                                                        }
                                                                        className="rounded-lg border p-3 text-sm"
                                                                    >
                                                                        <p className="font-medium">
                                                                            {item.title ??
                                                                                item.email ??
                                                                                `#${item.id}`}
                                                                        </p>
                                                                        {item.email &&
                                                                            item.title !==
                                                                                item.email && (
                                                                                <p className="text-muted-foreground">
                                                                                    {
                                                                                        item.email
                                                                                    }
                                                                                </p>
                                                                            )}
                                                                        {item.label && (
                                                                            <Badge
                                                                                variant="secondary"
                                                                                className="mt-2"
                                                                            >
                                                                                {
                                                                                    item.label
                                                                                }
                                                                            </Badge>
                                                                        )}
                                                                    </div>
                                                                ),
                                                            )}
                                                        </div>
                                                    )}
                                                </section>
                                            );
                                        },
                                    )
                                )}
                            </CardContent>
                        </Card>
                    </div>
                ) : (
                    <SyncedDataPageState status={pageStatus} variant="detail" />
                )}
            </PageContainer>
        </>
    );
}

SyncedDataShow.layout = {
    breadcrumbs: [{ title: 'Dados sincronizados', href: index() }],
};
