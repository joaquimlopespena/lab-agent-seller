import { AlertCircleIcon, InboxIcon, LoaderCircleIcon } from 'lucide-react';
import type { LucideIcon } from 'lucide-react';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Card, CardContent } from '@/components/ui/card';
import { Skeleton } from '@/components/ui/skeleton';

type CustomerPageStateProps = {
    state: 'loading' | 'error' | 'empty';
    title?: string;
    description?: string;
};

const defaultContent: Record<
    CustomerPageStateProps['state'],
    { title: string; description: string; icon: LucideIcon }
> = {
    loading: {
        title: 'Carregando',
        description: 'Aguarde enquanto preparamos esta etapa.',
        icon: LoaderCircleIcon,
    },
    error: {
        title: 'Não foi possível continuar',
        description: 'Tente novamente em instantes.',
        icon: AlertCircleIcon,
    },
    empty: {
        title: 'Nada por aqui',
        description: 'Não há informações disponíveis neste momento.',
        icon: InboxIcon,
    },
};

export function CustomerPageState({
    state,
    title,
    description,
}: CustomerPageStateProps) {
    const content = defaultContent[state];
    const Icon = content.icon;

    if (state === 'error') {
        return (
            <Alert variant="destructive">
                <Icon aria-hidden="true" />
                <AlertTitle>{title ?? content.title}</AlertTitle>
                <AlertDescription>
                    {description ?? content.description}
                </AlertDescription>
            </Alert>
        );
    }

    if (state === 'loading') {
        return (
            <Card aria-busy="true" aria-live="polite">
                <CardContent className="flex flex-col gap-4 p-6">
                    <span className="sr-only">{title ?? content.title}</span>
                    <Skeleton className="h-5 w-2/5" />
                    <Skeleton className="h-4 w-full" />
                    <Skeleton className="h-4 w-3/4" />
                </CardContent>
            </Card>
        );
    }

    return (
        <Card>
            <CardContent className="flex min-h-48 flex-col items-center justify-center gap-3 p-6 text-center">
                <Icon
                    aria-hidden="true"
                    className="size-8 text-muted-foreground"
                />
                <div className="flex flex-col gap-1">
                    <h2 className="font-medium">{title ?? content.title}</h2>
                    <p className="text-sm text-muted-foreground">
                        {description ?? content.description}
                    </p>
                </div>
            </CardContent>
        </Card>
    );
}
