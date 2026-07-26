import { Head, useHttp } from '@inertiajs/react';
import { BotIcon, RefreshCwIcon, SendIcon, UserIcon } from 'lucide-react';
import { useState } from 'react';
import type { FormEvent } from 'react';
import {
    retry,
    store,
} from '@/actions/App/Http/Controllers/Customer/CustomerConversationController';
import { CustomerPageState } from '@/components/customer-page-state';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardFooter } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import type { ConversationMessage, CustomerConversation } from '@/types/domain';

type ChatResponse = {
    status: 'replied';
    messages: ConversationMessage[];
};

type ChatFailureResponse = {
    user_message: ConversationMessage;
};

type CustomerChatIndexProps = {
    conversation: CustomerConversation;
    messages: ConversationMessage[];
};

export default function CustomerChatIndex({
    conversation,
    messages: initialMessages,
}: CustomerChatIndexProps) {
    const [messages, setMessages] =
        useState<ConversationMessage[]>(initialMessages);
    const [providerError, setProviderError] = useState(false);
    const sendRequest = useHttp<{ content: string }, ChatResponse>({
        content: '',
    });
    const retryRequest = useHttp<Record<string, never>, ChatResponse>({});

    const latestMessage = messages.at(-1);
    const canRetry =
        providerError &&
        latestMessage?.role === 'user' &&
        !sendRequest.processing &&
        !retryRequest.processing;

    async function submit(event: FormEvent<HTMLFormElement>) {
        event.preventDefault();

        if (sendRequest.processing || sendRequest.data.content.trim() === '') {
            return;
        }

        setProviderError(false);

        await sendRequest.submit(store(), {
            onSuccess: (response) => {
                setMessages((current) => [...current, ...response.messages]);
                sendRequest.reset('content');
            },
            onHttpException: (httpResponse) => {
                setProviderError(true);
                const response =
                    typeof httpResponse.data === 'string'
                        ? (JSON.parse(httpResponse.data) as ChatFailureResponse)
                        : (httpResponse.data as ChatFailureResponse);

                setMessages((current) =>
                    current.some(
                        (message) => message.id === response.user_message.id,
                    )
                        ? current
                        : [...current, response.user_message],
                );
            },
        });
    }

    async function retryLastMessage() {
        if (!canRetry || latestMessage === undefined) {
            return;
        }

        setProviderError(false);

        await retryRequest.submit(retry(latestMessage.id), {
            onSuccess: (response) => {
                setMessages((current) => [...current, ...response.messages]);
            },
            onHttpException: () => {
                setProviderError(true);
            },
        });
    }

    const isGenerating = sendRequest.processing || retryRequest.processing;

    return (
        <>
            <Head title="Atendimento" />

            <Card
                className="flex min-h-[32rem] flex-1 flex-col"
                aria-label={conversation.title}
            >
                <CardContent
                    className="flex flex-1 flex-col gap-4 overflow-y-auto p-4 sm:p-6"
                    aria-live="polite"
                >
                    {messages.length === 0 && !isGenerating ? (
                        <CustomerPageState
                            state="empty"
                            title="Conversa ainda não iniciada"
                            description="Suas mensagens e as respostas do agente aparecerão neste espaço."
                        />
                    ) : (
                        messages.map((message) => {
                            const fromCustomer = message.role === 'user';
                            const Icon = fromCustomer ? UserIcon : BotIcon;

                            return (
                                <article
                                    key={message.id}
                                    className={`flex max-w-[85%] gap-3 rounded-2xl px-4 py-3 text-sm ${
                                        fromCustomer
                                            ? 'ml-auto bg-primary text-primary-foreground'
                                            : 'bg-muted text-foreground'
                                    }`}
                                >
                                    <Icon
                                        className="mt-0.5 size-4 shrink-0"
                                        aria-hidden="true"
                                    />
                                    <p className="whitespace-pre-wrap">
                                        {message.content}
                                    </p>
                                </article>
                            );
                        })
                    )}

                    {isGenerating && (
                        <div
                            className="flex max-w-[85%] items-center gap-3 rounded-2xl bg-muted px-4 py-3 text-sm text-muted-foreground"
                            aria-busy="true"
                        >
                            <BotIcon className="size-4" aria-hidden="true" />
                            <span className="animate-pulse">
                                Preparando resposta…
                            </span>
                        </div>
                    )}

                    {providerError && (
                        <Alert variant="destructive">
                            <AlertTitle>Resposta indisponível</AlertTitle>
                            <AlertDescription className="flex flex-col items-start gap-3">
                                <span>
                                    Sua mensagem foi salva. Você pode tentar
                                    gerar a resposta novamente.
                                </span>
                                <Button
                                    type="button"
                                    variant="outline"
                                    size="sm"
                                    disabled={!canRetry}
                                    onClick={retryLastMessage}
                                >
                                    <RefreshCwIcon aria-hidden="true" />
                                    Tentar novamente
                                </Button>
                            </AlertDescription>
                        </Alert>
                    )}
                </CardContent>
                <CardFooter className="border-t p-4">
                    <form
                        className="flex w-full items-end gap-2"
                        onSubmit={submit}
                    >
                        <div className="grid flex-1 gap-2">
                            <Label htmlFor="message" className="sr-only">
                                Mensagem
                            </Label>
                            <Input
                                id="message"
                                name="message"
                                placeholder="Digite sua mensagem"
                                value={sendRequest.data.content}
                                maxLength={4000}
                                disabled={isGenerating}
                                onChange={(event) =>
                                    sendRequest.setData(
                                        'content',
                                        event.target.value,
                                    )
                                }
                            />
                            {sendRequest.errors.content && (
                                <p className="text-sm text-destructive">
                                    {sendRequest.errors.content}
                                </p>
                            )}
                        </div>
                        <Button
                            type="submit"
                            size="icon"
                            disabled={
                                isGenerating ||
                                sendRequest.data.content.trim() === ''
                            }
                            aria-label="Enviar mensagem"
                        >
                            <SendIcon aria-hidden="true" />
                        </Button>
                    </form>
                </CardFooter>
            </Card>
        </>
    );
}

CustomerChatIndex.layout = {
    title: 'Atendimento',
    description:
        'Converse com o agente no contexto da empresa selecionada, sem acesso a informações administrativas.',
};
