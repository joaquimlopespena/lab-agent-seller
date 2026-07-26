import { Head, Link } from '@inertiajs/react';
import {
    ArrowLeftIcon,
    BotIcon,
    MessageCircleIcon,
    UserIcon,
} from 'lucide-react';
import {
    ConversationPageState,
    useConversationPageStatus,
} from '@/components/conversation-page-state';
import { EmptyState } from '@/components/empty-state';
import { PageContainer } from '@/components/page-container';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { index } from '@/routes/admin/conversations';

type Message = {
    id: string;
    role: 'user' | 'assistant' | 'system';
    role_label: string;
    content: string;
    created_at: string | null;
    updated_at: string | null;
};

type Props = {
    conversation: {
        id: string;
        customer: { name: string; email: string };
        status: { name: string; slug: string };
        created_at: string | null;
        updated_at: string | null;
        messages: Message[];
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

export default function ConversationShow({ conversation }: Props) {
    const status = useConversationPageStatus();

    return (
        <>
            <Head title={`Conversa de ${conversation.customer.name}`} />
            <PageContainer
                title={conversation.customer.name}
                description={conversation.customer.email}
            >
                {status === 'idle' ? (
                    <>
                        <div>
                            <Button asChild variant="outline" size="sm">
                                <Link href={index()}>
                                    <ArrowLeftIcon
                                        aria-hidden
                                        className="size-4"
                                    />
                                    Voltar
                                </Link>
                            </Button>
                        </div>
                        <Card>
                            <CardHeader>
                                <div className="flex flex-wrap items-center justify-between gap-3">
                                    <CardTitle>Dados da conversa</CardTitle>
                                    <Badge variant="secondary">
                                        {conversation.status.name}
                                    </Badge>
                                </div>
                            </CardHeader>
                            <CardContent className="grid gap-4 text-sm sm:grid-cols-2">
                                <div>
                                    <p className="text-muted-foreground">
                                        Iniciada em
                                    </p>
                                    <p>{formatDate(conversation.created_at)}</p>
                                </div>
                                <div>
                                    <p className="text-muted-foreground">
                                        Atualizada em
                                    </p>
                                    <p>{formatDate(conversation.updated_at)}</p>
                                </div>
                            </CardContent>
                        </Card>

                        <Card>
                            <CardHeader>
                                <CardTitle>Histórico de mensagens</CardTitle>
                            </CardHeader>
                            <CardContent>
                                {conversation.messages.length === 0 ? (
                                    <EmptyState
                                        title="Nenhuma mensagem registrada"
                                        description="O histórico aparecerá quando o cliente iniciar a conversa."
                                        icon={MessageCircleIcon}
                                    />
                                ) : (
                                    <ol className="flex flex-col gap-4">
                                        {conversation.messages.map(
                                            (message) => {
                                                const fromCustomer =
                                                    message.role === 'user';
                                                const Icon = fromCustomer
                                                    ? UserIcon
                                                    : BotIcon;

                                                return (
                                                    <li
                                                        key={message.id}
                                                        className={`flex max-w-[90%] gap-3 rounded-2xl px-4 py-3 ${
                                                            fromCustomer
                                                                ? 'ml-auto bg-primary text-primary-foreground'
                                                                : 'bg-muted'
                                                        }`}
                                                    >
                                                        <Icon
                                                            aria-hidden
                                                            className="mt-0.5 size-4 shrink-0"
                                                        />
                                                        <div className="min-w-0">
                                                            <div className="flex flex-wrap items-center gap-2 text-xs opacity-75">
                                                                <span>
                                                                    {
                                                                        message.role_label
                                                                    }
                                                                </span>
                                                                <time>
                                                                    {formatDate(
                                                                        message.created_at,
                                                                    )}
                                                                </time>
                                                            </div>
                                                            <p className="mt-1 text-sm whitespace-pre-wrap">
                                                                {
                                                                    message.content
                                                                }
                                                            </p>
                                                        </div>
                                                    </li>
                                                );
                                            },
                                        )}
                                    </ol>
                                )}
                            </CardContent>
                        </Card>
                    </>
                ) : (
                    <ConversationPageState status={status} detail />
                )}
            </PageContainer>
        </>
    );
}

ConversationShow.layout = {
    breadcrumbs: [
        { title: 'Conversas', href: index() },
        { title: 'Histórico', href: '#' },
    ],
};
