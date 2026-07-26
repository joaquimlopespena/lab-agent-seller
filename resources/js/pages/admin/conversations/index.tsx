import { Head } from '@inertiajs/react';
import { MessagesSquareIcon } from 'lucide-react';
import { EmptyState } from '@/components/empty-state';
import { PageContainer } from '@/components/page-container';
import { index } from '@/routes/admin/conversations';

export default function ConversationsIndex() {
    return (
        <>
            <Head title="Conversas" />
            <PageContainer
                title="Conversas"
                description="Acompanhe as conversas dos clientes da sua empresa."
            >
                <EmptyState
                    title="Nenhuma conversa"
                    description="As conversas iniciadas pelos seus clientes aparecerão aqui."
                    icon={MessagesSquareIcon}
                />
            </PageContainer>
        </>
    );
}

ConversationsIndex.layout = {
    breadcrumbs: [{ title: 'Conversas', href: index() }],
};
