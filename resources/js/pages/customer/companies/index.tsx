import { Form, Head } from '@inertiajs/react';
import { Building2Icon, LoaderCircleIcon } from 'lucide-react';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { store } from '@/routes/customer/companies';
import type { CompanySummary } from '@/types';

type CustomerCompaniesIndexProps = {
    companies: CompanySummary[];
};

export default function CustomerCompaniesIndex({
    companies,
}: CustomerCompaniesIndexProps) {
    return (
        <>
            <Head title="Escolher empresa" />

            <Card>
                <CardHeader>
                    <div className="flex items-center gap-3">
                        <div className="flex size-10 items-center justify-center rounded-full bg-muted text-muted-foreground">
                            <Building2Icon
                                aria-hidden="true"
                                className="size-5"
                            />
                        </div>
                        <div className="grid gap-1">
                            <CardTitle>Empresas disponíveis</CardTitle>
                            <CardDescription>
                                A conversa ficará vinculada somente à empresa
                                escolhida.
                            </CardDescription>
                        </div>
                    </div>
                </CardHeader>
                <CardContent>
                    {companies.length === 0 ? (
                        <Alert>
                            <Building2Icon aria-hidden="true" />
                            <AlertTitle>Nenhuma empresa disponível</AlertTitle>
                            <AlertDescription>
                                Seu acesso não possui vínculos ativos no
                                momento.
                            </AlertDescription>
                        </Alert>
                    ) : (
                        <Form
                            {...store.form()}
                            disableWhileProcessing
                            className="grid gap-6 inert:pointer-events-none inert:opacity-70"
                        >
                            {({ errors, processing }) => (
                                <>
                                    <fieldset className="grid gap-3">
                                        <legend className="sr-only">
                                            Escolha uma empresa
                                        </legend>
                                        {companies.map((company) => (
                                            <label
                                                key={company.id}
                                                className="flex cursor-pointer items-center gap-4 rounded-lg border p-4 transition-colors hover:bg-muted/50 has-checked:border-primary has-checked:bg-primary/5"
                                            >
                                                <input
                                                    type="radio"
                                                    name="company_id"
                                                    value={company.id}
                                                    required
                                                    className="size-4 accent-primary"
                                                />
                                                <span className="grid gap-1">
                                                    <span className="font-medium">
                                                        {company.name}
                                                    </span>
                                                    <span className="text-xs text-muted-foreground">
                                                        Identificação #
                                                        {company.id}
                                                    </span>
                                                </span>
                                            </label>
                                        ))}
                                    </fieldset>

                                    {errors.company_id && (
                                        <Alert
                                            variant="destructive"
                                            aria-live="polite"
                                        >
                                            <AlertTitle>
                                                Não foi possível selecionar
                                            </AlertTitle>
                                            <AlertDescription>
                                                {errors.company_id}
                                            </AlertDescription>
                                        </Alert>
                                    )}

                                    <Button
                                        type="submit"
                                        disabled={processing}
                                        className="justify-self-start"
                                    >
                                        {processing && (
                                            <LoaderCircleIcon
                                                aria-hidden="true"
                                                className="animate-spin"
                                            />
                                        )}
                                        {processing
                                            ? 'Continuando...'
                                            : 'Continuar para a conversa'}
                                    </Button>
                                </>
                            )}
                        </Form>
                    )}
                </CardContent>
            </Card>
        </>
    );
}

CustomerCompaniesIndex.layout = {
    title: 'Com qual empresa deseja conversar?',
    description:
        'Selecione uma empresa associada ao seu acesso para definir o contexto da conversa.',
};
