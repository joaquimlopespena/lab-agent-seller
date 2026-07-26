import { Head } from '@inertiajs/react';
import { SendIcon } from 'lucide-react';
import { CustomerPageState } from '@/components/customer-page-state';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardFooter } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

export default function CustomerChatIndex() {
    return (
        <>
            <Head title="Atendimento" />

            <Card className="flex min-h-[32rem] flex-1 flex-col">
                <CardContent
                    className="flex flex-1 items-center justify-center p-4 sm:p-6"
                    aria-live="polite"
                >
                    <div className="w-full max-w-lg">
                        <CustomerPageState
                            state="empty"
                            title="Conversa ainda não iniciada"
                            description="Suas mensagens e as respostas do agente aparecerão neste espaço."
                        />
                    </div>
                </CardContent>
                <CardFooter className="border-t p-4">
                    <form className="flex w-full items-end gap-2">
                        <div className="grid flex-1 gap-2">
                            <Label htmlFor="message" className="sr-only">
                                Mensagem
                            </Label>
                            <Input
                                id="message"
                                name="message"
                                placeholder="Digite sua mensagem"
                                disabled
                            />
                        </div>
                        <Button
                            type="button"
                            size="icon"
                            disabled
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
