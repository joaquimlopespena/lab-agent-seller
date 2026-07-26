import { Head } from '@inertiajs/react';
import { PlugIcon } from 'lucide-react';
import { EmptyState } from '@/components/empty-state';
import { PageContainer } from '@/components/page-container';
import { index } from '@/routes/admin/integrations';

export default function IntegrationsIndex() {
    return (
        <>
            <Head title="Integrações" />
            <PageContainer
                title="Integrações"
                description="Gerencie as conexões de serviços da sua empresa."
            >
                <EmptyState
                    title="Nenhuma integração conectada"
                    description="A conexão com o Pipedrive estará disponível em uma próxima etapa do MVP."
                    icon={PlugIcon}
                />
            </PageContainer>
        </>
    );
}

IntegrationsIndex.layout = {
    breadcrumbs: [{ title: 'Integrações', href: index() }],
};
