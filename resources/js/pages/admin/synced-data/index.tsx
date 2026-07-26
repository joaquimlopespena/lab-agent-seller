import { Head } from '@inertiajs/react';
import { DatabaseIcon } from 'lucide-react';
import { EmptyState } from '@/components/empty-state';
import { PageContainer } from '@/components/page-container';
import { index } from '@/routes/admin/synced-data';

export default function SyncedDataIndex() {
    return (
        <>
            <Head title="Dados sincronizados" />
            <PageContainer
                title="Dados sincronizados"
                description="Consulte os dados importados do CRM da sua empresa."
            >
                <EmptyState
                    title="Nenhum dado sincronizado"
                    description="Os dados do CRM aparecerão aqui após a primeira sincronização."
                    icon={DatabaseIcon}
                />
            </PageContainer>
        </>
    );
}

SyncedDataIndex.layout = {
    breadcrumbs: [{ title: 'Dados sincronizados', href: index() }],
};
