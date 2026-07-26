import { router } from '@inertiajs/react';
import { AlertCircleIcon, RefreshCwIcon } from 'lucide-react';
import { useEffect, useState } from 'react';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Skeleton } from '@/components/ui/skeleton';

type PageStatus = 'idle' | 'loading' | 'error';

export function useConversationPageStatus(): PageStatus {
    const [status, setStatus] = useState<PageStatus>('idle');

    useEffect(() => {
        const stopStart = router.on('start', () => setStatus('loading'));
        const stopNavigate = router.on('navigate', () => setStatus('idle'));
        const stopCancel = router.on('cancel', () => setStatus('idle'));
        const stopHttpException = router.on('httpException', (event) => {
            event.preventDefault();
            setStatus('error');
        });
        const stopNetworkError = router.on('networkError', (event) => {
            event.preventDefault();
            setStatus('error');
        });

        return () => {
            stopStart();
            stopNavigate();
            stopCancel();
            stopHttpException();
            stopNetworkError();
        };
    }, []);

    return status;
}

export function ConversationPageState({
    status,
    detail = false,
}: {
    status: Exclude<PageStatus, 'idle'>;
    detail?: boolean;
}) {
    if (status === 'error') {
        return (
            <Alert variant="destructive">
                <AlertCircleIcon aria-hidden />
                <AlertTitle>Não foi possível carregar as conversas</AlertTitle>
                <AlertDescription className="flex flex-col items-start gap-3">
                    <p>Verifique sua conexão e tente novamente.</p>
                    <Button
                        type="button"
                        variant="outline"
                        size="sm"
                        onClick={() => router.reload()}
                    >
                        <RefreshCwIcon aria-hidden className="size-4" />
                        Tentar novamente
                    </Button>
                </AlertDescription>
            </Alert>
        );
    }

    return (
        <Card aria-busy="true" aria-live="polite">
            <CardContent className="flex flex-col gap-4 p-4 md:p-6">
                <span className="sr-only">Carregando conversas</span>
                <Skeleton className="h-6 w-48" />
                <Skeleton className="h-4 w-72 max-w-full" />
                {Array.from({ length: detail ? 4 : 6 }, (_, index) => (
                    <Skeleton
                        key={index}
                        className={detail ? 'h-20 w-full' : 'h-14 w-full'}
                    />
                ))}
            </CardContent>
        </Card>
    );
}
