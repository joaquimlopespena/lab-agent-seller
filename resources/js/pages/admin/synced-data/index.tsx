import { Head, Link, router } from '@inertiajs/react';
import { DatabaseIcon, SearchIcon } from 'lucide-react';
import { useState } from 'react';
import type { FormEvent } from 'react';
import { EmptyState } from '@/components/empty-state';
import { PageContainer } from '@/components/page-container';
import {
    SyncedDataPageState,
    useSyncedDataPageStatus,
} from '@/components/synced-data-page-state';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { index, show } from '@/routes/admin/synced-data';

type Category = {
    slug: string;
    label: string;
    description: string;
    count: number;
};

type RecordSummary = {
    id: number;
    external_id?: number;
    title: string;
    subtitle?: string;
    active?: boolean;
    synced_at?: string;
};

type Props = {
    categories: Category[];
    activeCategory: Omit<Category, 'count'>;
    filters: { search: string };
    records: {
        data: RecordSummary[];
        current_page: number;
        last_page: number;
        per_page: number;
        total: number;
        from: number | null;
        to: number | null;
    };
    readOnly: true;
};

export default function SyncedDataIndex({
    categories,
    activeCategory,
    filters,
    records,
}: Props) {
    const [search, setSearch] = useState(filters.search);
    const pageStatus = useSyncedDataPageStatus();

    function submit(event: FormEvent<HTMLFormElement>) {
        event.preventDefault();
        router.get(
            index({ category: activeCategory.slug }),
            { search: search || undefined },
            { preserveState: true, replace: true },
        );
    }

    const pageUrl = (page: number) =>
        index(
            { category: activeCategory.slug },
            { query: { search: filters.search || undefined, page } },
        );

    return (
        <>
            <Head title="Dados sincronizados" />
            <PageContainer
                title="Dados sincronizados"
                description="Consulte a representação local e somente leitura do Pipedrive."
            >
                <div className="grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
                    {categories.map((category) => (
                        <Link
                            key={category.slug}
                            href={index({ category: category.slug })}
                            preserveScroll
                            className="rounded-xl focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none"
                        >
                            <Card
                                className={
                                    category.slug === activeCategory.slug
                                        ? 'border-primary bg-primary/5'
                                        : 'transition-colors hover:bg-muted/50'
                                }
                            >
                                <CardContent className="flex items-start justify-between gap-3 p-4">
                                    <div className="min-w-0">
                                        <h2 className="font-medium">
                                            {category.label}
                                        </h2>
                                        <p className="line-clamp-2 text-sm text-muted-foreground">
                                            {category.description}
                                        </p>
                                    </div>
                                    <Badge variant="secondary">
                                        {category.count}
                                    </Badge>
                                </CardContent>
                            </Card>
                        </Link>
                    ))}
                </div>

                {pageStatus === 'idle' ? (
                    <Card>
                        <CardContent className="flex flex-col gap-5 p-4 md:p-6">
                            <div className="flex flex-col gap-3 md:flex-row md:items-end md:justify-between">
                                <div>
                                    <h2 className="text-lg font-semibold">
                                        {activeCategory.label}
                                    </h2>
                                    <p className="text-sm text-muted-foreground">
                                        {activeCategory.description}
                                    </p>
                                </div>
                                <form
                                    onSubmit={submit}
                                    className="flex w-full gap-2 md:max-w-md"
                                    role="search"
                                >
                                    <Input
                                        value={search}
                                        onChange={(event) =>
                                            setSearch(event.target.value)
                                        }
                                        maxLength={100}
                                        placeholder="Filtrar registros"
                                        aria-label="Filtrar registros"
                                    />
                                    <Button type="submit" variant="secondary">
                                        <SearchIcon
                                            aria-hidden
                                            className="size-4"
                                        />
                                        Filtrar
                                    </Button>
                                </form>
                            </div>

                            {records.data.length === 0 ? (
                                <EmptyState
                                    title={
                                        filters.search
                                            ? 'Nenhum resultado encontrado'
                                            : `Nenhum registro em ${activeCategory.label.toLowerCase()}`
                                    }
                                    description={
                                        filters.search
                                            ? 'Tente um termo de busca diferente.'
                                            : 'Esta categoria ficará disponível após uma sincronização que contenha esse tipo de dado.'
                                    }
                                    icon={DatabaseIcon}
                                />
                            ) : (
                                <div className="overflow-x-auto rounded-lg border">
                                    <table className="w-full min-w-160 text-sm">
                                        <thead className="bg-muted/50 text-left">
                                            <tr>
                                                <th className="px-4 py-3 font-medium">
                                                    Registro
                                                </th>
                                                <th className="px-4 py-3 font-medium">
                                                    ID Pipedrive
                                                </th>
                                                <th className="px-4 py-3 font-medium">
                                                    Estado
                                                </th>
                                                <th className="px-4 py-3 text-right font-medium">
                                                    Consulta
                                                </th>
                                            </tr>
                                        </thead>
                                        <tbody className="divide-y">
                                            {records.data.map((record) => (
                                                <tr key={record.id}>
                                                    <td className="px-4 py-3">
                                                        <p className="font-medium">
                                                            {record.title}
                                                        </p>
                                                        {record.subtitle && (
                                                            <p className="text-muted-foreground">
                                                                {
                                                                    record.subtitle
                                                                }
                                                            </p>
                                                        )}
                                                    </td>
                                                    <td className="px-4 py-3 text-muted-foreground">
                                                        {record.external_id ??
                                                            '—'}
                                                    </td>
                                                    <td className="px-4 py-3">
                                                        {record.active ===
                                                        undefined ? (
                                                            '—'
                                                        ) : (
                                                            <Badge
                                                                variant={
                                                                    record.active
                                                                        ? 'secondary'
                                                                        : 'outline'
                                                                }
                                                            >
                                                                {record.active
                                                                    ? 'Ativo'
                                                                    : 'Inativo'}
                                                            </Badge>
                                                        )}
                                                    </td>
                                                    <td className="px-4 py-3 text-right">
                                                        <Button
                                                            asChild
                                                            size="sm"
                                                            variant="ghost"
                                                        >
                                                            <Link
                                                                href={show({
                                                                    category:
                                                                        activeCategory.slug,
                                                                    record: record.id,
                                                                })}
                                                            >
                                                                Ver detalhes
                                                            </Link>
                                                        </Button>
                                                    </td>
                                                </tr>
                                            ))}
                                        </tbody>
                                    </table>
                                </div>
                            )}

                            {records.total > 0 && (
                                <div className="flex flex-col gap-3 text-sm sm:flex-row sm:items-center sm:justify-between">
                                    <p className="text-muted-foreground">
                                        {records.from}–{records.to} de{' '}
                                        {records.total} registros
                                    </p>
                                    <div className="flex gap-2">
                                        <Button
                                            asChild
                                            variant="outline"
                                            size="sm"
                                            disabled={
                                                records.current_page === 1
                                            }
                                        >
                                            <Link
                                                href={pageUrl(
                                                    records.current_page - 1,
                                                )}
                                            >
                                                Anterior
                                            </Link>
                                        </Button>
                                        <Button
                                            asChild
                                            variant="outline"
                                            size="sm"
                                            disabled={
                                                records.current_page ===
                                                records.last_page
                                            }
                                        >
                                            <Link
                                                href={pageUrl(
                                                    records.current_page + 1,
                                                )}
                                            >
                                                Próxima
                                            </Link>
                                        </Button>
                                    </div>
                                </div>
                            )}
                        </CardContent>
                    </Card>
                ) : (
                    <SyncedDataPageState status={pageStatus} variant="list" />
                )}
            </PageContainer>
        </>
    );
}

SyncedDataIndex.layout = {
    breadcrumbs: [{ title: 'Dados sincronizados', href: index() }],
};
