import { router } from '@inertiajs/react';
import { AlertCircleIcon, RefreshCwIcon } from 'lucide-react';
import { useEffect, useState } from 'react';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Skeleton } from '@/components/ui/skeleton';

type SyncedDataPageStatus = 'idle' | 'loading' | 'error';

type SyncedDataPageStateProps = {
    status: Exclude<SyncedDataPageStatus, 'idle'>;
    variant: 'list' | 'detail';
};

export function useSyncedDataPageStatus(): SyncedDataPageStatus {
    const [status, setStatus] = useState<SyncedDataPageStatus>('idle');

    useEffect(() => {
        const removeStartListener = router.on('start', () => {
            setStatus('loading');
        });
        const removeNavigateListener = router.on('navigate', () => {
            setStatus('idle');
        });
        const removeCancelListener = router.on('cancel', () => {
            setStatus('idle');
        });
        const removeHttpExceptionListener = router.on(
            'httpException',
            (event) => {
                event.preventDefault();
                setStatus('error');
            },
        );
        const removeNetworkErrorListener = router.on(
            'networkError',
            (event) => {
                event.preventDefault();
                setStatus('error');
            },
        );

        return () => {
            removeStartListener();
            removeNavigateListener();
            removeCancelListener();
            removeHttpExceptionListener();
            removeNetworkErrorListener();
        };
    }, []);

    return status;
}

export function SyncedDataPageState({
    status,
    variant,
}: SyncedDataPageStateProps) {
    if (status === 'error') {
        return (
            <Alert variant="destructive">
                <AlertCircleIcon aria-hidden />
                <AlertTitle>Não foi possível carregar os dados</AlertTitle>
                <AlertDescription>
                    <p>
                        Verifique sua conexão e tente carregar esta página
                        novamente.
                    </p>
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

    const rows = variant === 'list' ? 5 : 4;

    return (
        <Card aria-busy="true" aria-live="polite">
            <CardContent className="flex flex-col gap-5 p-4 md:p-6">
                <span className="sr-only">Carregando dados sincronizados</span>
                <div className="flex items-center justify-between gap-4">
                    <div className="flex flex-1 flex-col gap-2">
                        <Skeleton className="h-5 w-2/5 max-w-48" />
                        <Skeleton className="h-4 w-3/5 max-w-80" />
                    </div>
                    {variant === 'list' && (
                        <Skeleton className="hidden h-9 w-52 sm:block" />
                    )}
                </div>
                <div className="grid gap-3">
                    {Array.from({ length: rows }, (_, index) => (
                        <Skeleton key={index} className="h-14 w-full" />
                    ))}
                </div>
            </CardContent>
        </Card>
    );
}
