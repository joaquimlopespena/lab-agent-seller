import type { ReactNode } from 'react';

type PageContainerProps = {
    title: string;
    description: string;
    children: ReactNode;
};

export function PageContainer({
    title,
    description,
    children,
}: PageContainerProps) {
    return (
        <div className="flex h-full flex-1 flex-col gap-6 p-4 md:p-6">
            <div className="flex flex-col gap-1">
                <h1 className="text-2xl font-semibold tracking-tight">
                    {title}
                </h1>
                <p className="text-sm text-muted-foreground">{description}</p>
            </div>
            {children}
        </div>
    );
}
