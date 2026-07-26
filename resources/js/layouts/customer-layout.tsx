import { Link, usePage } from '@inertiajs/react';
import AppLogoIcon from '@/components/app-logo-icon';
import { home } from '@/routes';
import type { CustomerLayoutProps } from '@/types';

export default function CustomerLayout({
    children,
    title,
    description,
}: CustomerLayoutProps) {
    const { name } = usePage().props;

    return (
        <div className="flex min-h-svh flex-col bg-muted/30">
            <header className="border-b bg-background">
                <div className="mx-auto flex w-full max-w-4xl items-center px-4 py-4 sm:px-6">
                    <Link
                        href={home()}
                        className="flex items-center gap-3 rounded-md font-medium focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 focus-visible:outline-none"
                    >
                        <AppLogoIcon className="size-8 fill-current" />
                        <span>{name}</span>
                    </Link>
                </div>
            </header>

            <main className="mx-auto flex w-full max-w-4xl flex-1 flex-col px-4 py-8 sm:px-6 sm:py-12">
                {(title || description) && (
                    <div className="mb-6 flex flex-col gap-2">
                        {title && (
                            <h1 className="text-2xl font-semibold tracking-tight sm:text-3xl">
                                {title}
                            </h1>
                        )}
                        {description && (
                            <p className="max-w-2xl text-sm text-muted-foreground sm:text-base">
                                {description}
                            </p>
                        )}
                    </div>
                )}
                {children}
            </main>
        </div>
    );
}
