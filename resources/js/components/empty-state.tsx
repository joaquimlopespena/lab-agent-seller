import type { LucideIcon } from 'lucide-react';
import { Card, CardContent } from '@/components/ui/card';

type EmptyStateProps = {
    title: string;
    description: string;
    icon: LucideIcon;
};

export function EmptyState({
    title,
    description,
    icon: Icon,
}: EmptyStateProps) {
    return (
        <Card className="border-dashed">
            <CardContent className="flex min-h-64 flex-col items-center justify-center gap-4 p-6 text-center">
                <div className="flex size-12 items-center justify-center rounded-full bg-muted text-muted-foreground">
                    <Icon aria-hidden="true" className="size-6" />
                </div>
                <div className="flex max-w-md flex-col gap-1">
                    <h2 className="font-medium">{title}</h2>
                    <p className="text-sm text-muted-foreground">
                        {description}
                    </p>
                </div>
            </CardContent>
        </Card>
    );
}
