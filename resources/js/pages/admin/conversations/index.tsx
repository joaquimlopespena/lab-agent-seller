import { Head, Link, router } from '@inertiajs/react';
import { MessagesSquareIcon, SearchIcon } from 'lucide-react';
import { useState } from 'react';
import type { FormEvent } from 'react';
import {
    ConversationPageState,
    useConversationPageStatus,
} from '@/components/conversation-page-state';
import { EmptyState } from '@/components/empty-state';
import { PageContainer } from '@/components/page-container';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { index, show } from '@/routes/admin/conversations';

type Conversation = {
    id: string;
    customer: { name: string; email: string };
    status: { name: string; slug: string };
    created_at: string | null;
    updated_at: string | null;
};

type Props = {
    filters: { search: string };
    conversations: {
        data: Conversation[];
        current_page: number;
        last_page: number;
        per_page: number;
        total: number;
        from: number | null;
        to: number | null;
    };
    readOnly: true;
};

const formatDate = (value: string | null) =>
    value
        ? new Intl.DateTimeFormat('pt-BR', {
              dateStyle: 'short',
              timeStyle: 'short',
          }).format(new Date(value))
        : '—';

export default function ConversationIndex({ filters, conversations }: Props) {
    const [search, setSearch] = useState(filters.search);
    const status = useConversationPageStatus();

    function submit(event: FormEvent<HTMLFormElement>) {
        event.preventDefault();
        router.get(
            index(),
            { search: search || undefined },
            { preserveState: true, replace: true },
        );
    }

    const pageUrl = (page: number) =>
        index({ query: { search: filters.search || undefined, page } });

    return (
        <>
            <Head title="Conversas" />
            <PageContainer
                title="Conversas"
                description="Consulte os atendimentos realizados no seu tenant."
            >
                {status === 'idle' ? (
                    <Card>
                        <CardContent className="flex flex-col gap-5 p-4 md:p-6">
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
                                    placeholder="Buscar cliente ou e-mail"
                                    aria-label="Buscar cliente ou e-mail"
                                />
                                <Button type="submit" variant="secondary">
                                    <SearchIcon
                                        aria-hidden
                                        className="size-4"
                                    />
                                    Buscar
                                </Button>
                            </form>

                            {conversations.data.length === 0 ? (
                                <EmptyState
                                    title={
                                        filters.search
                                            ? 'Nenhuma conversa encontrada'
                                            : 'Nenhuma conversa iniciada'
                                    }
                                    description={
                                        filters.search
                                            ? 'Tente buscar por outro cliente ou e-mail.'
                                            : 'As conversas dos clientes desta empresa aparecerão aqui.'
                                    }
                                    icon={MessagesSquareIcon}
                                />
                            ) : (
                                <div className="grid gap-3">
                                    {conversations.data.map((conversation) => (
                                        <article
                                            key={conversation.id}
                                            className="flex flex-col gap-4 rounded-lg border p-4 sm:flex-row sm:items-center sm:justify-between"
                                        >
                                            <div className="min-w-0">
                                                <div className="flex flex-wrap items-center gap-2">
                                                    <h2 className="font-medium">
                                                        {
                                                            conversation
                                                                .customer.name
                                                        }
                                                    </h2>
                                                    <Badge variant="secondary">
                                                        {
                                                            conversation.status
                                                                .name
                                                        }
                                                    </Badge>
                                                </div>
                                                <p className="truncate text-sm text-muted-foreground">
                                                    {
                                                        conversation.customer
                                                            .email
                                                    }
                                                </p>
                                                <p className="mt-2 text-xs text-muted-foreground">
                                                    Criada em{' '}
                                                    {formatDate(
                                                        conversation.created_at,
                                                    )}{' '}
                                                    · Atualizada em{' '}
                                                    {formatDate(
                                                        conversation.updated_at,
                                                    )}
                                                </p>
                                            </div>
                                            <Button
                                                asChild
                                                size="sm"
                                                variant="outline"
                                            >
                                                <Link
                                                    href={show(conversation.id)}
                                                    prefetch
                                                >
                                                    Ver histórico
                                                </Link>
                                            </Button>
                                        </article>
                                    ))}
                                </div>
                            )}

                            {conversations.total > 0 && (
                                <div className="flex flex-col gap-3 text-sm sm:flex-row sm:items-center sm:justify-between">
                                    <p className="text-muted-foreground">
                                        {conversations.from}–{conversations.to}{' '}
                                        de {conversations.total} conversas
                                    </p>
                                    <div className="flex gap-2">
                                        <Button
                                            asChild
                                            size="sm"
                                            variant="outline"
                                            disabled={
                                                conversations.current_page === 1
                                            }
                                        >
                                            <Link
                                                href={pageUrl(
                                                    conversations.current_page -
                                                        1,
                                                )}
                                            >
                                                Anterior
                                            </Link>
                                        </Button>
                                        <Button
                                            asChild
                                            size="sm"
                                            variant="outline"
                                            disabled={
                                                conversations.current_page ===
                                                conversations.last_page
                                            }
                                        >
                                            <Link
                                                href={pageUrl(
                                                    conversations.current_page +
                                                        1,
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
                    <ConversationPageState status={status} />
                )}
            </PageContainer>
        </>
    );
}

ConversationIndex.layout = {
    breadcrumbs: [{ title: 'Conversas', href: index() }],
};
