<?php

namespace App\Services;

use App\Integrations\Pipedrive\PipedriveDataClient;
use App\Models\Company;
use App\Models\CompanyCustomer;
use App\Models\CrmConnection;
use App\Models\CrmConnectionStatus;
use App\Models\CrmSyncRun;
use App\Models\CrmSyncStatus;
use App\Models\CustomerIdentity;
use App\Models\PipedriveActivity;
use App\Models\PipedriveCustomField;
use App\Models\PipedriveDeal;
use App\Models\PipedriveOrganization;
use App\Models\PipedrivePerson;
use App\Models\PipedrivePersonEmail;
use App\Models\PipedrivePipeline;
use App\Models\PipedriveProduct;
use App\Models\PipedriveStage;
use App\Models\PipedriveUser;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use LogicException;
use Throwable;

final class PipedriveSynchronizationService
{
    public function __construct(private PipedriveDataClient $pipedrive) {}

    public function synchronize(Company $company, CrmConnection $connection): CrmSyncRun
    {
        $run = $this->startRun($company, $connection);

        try {
            $this->syncStructure($connection);
            $this->syncContacts($connection);
            $this->syncSales($connection);
            $this->projectCustomers($connection);

            $run->update([
                'status_id' => $this->statusId(CrmSyncStatus::Succeeded),
                'finished_at' => now(),
            ]);
        } catch (Throwable $exception) {
            report($exception);

            $run->update([
                'status_id' => $this->statusId(CrmSyncStatus::Failed),
                'finished_at' => now(),
                'error_message' => 'Não foi possível concluir a sincronização. Tente novamente.',
            ]);
        }

        return $run->fresh('status:id,name,slug');
    }

    private function startRun(Company $company, CrmConnection $connection): CrmSyncRun
    {
        return DB::transaction(function () use ($company, $connection): CrmSyncRun {
            $lockedConnection = CrmConnection::query()
                ->with('status:id,slug')
                ->whereKey($connection->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if ((int) $lockedConnection->company_id !== (int) $company->getKey()) {
                throw new AuthorizationException;
            }

            if ($lockedConnection->status->slug !== CrmConnectionStatus::Active) {
                throw new LogicException('A conexão Pipedrive precisa estar ativa.');
            }

            $runningStatusId = $this->statusId(CrmSyncStatus::Running);

            if ($lockedConnection->syncRuns()->where('status_id', $runningStatusId)->exists()) {
                throw new LogicException('Já existe uma sincronização em andamento para esta conexão.');
            }

            $run = $lockedConnection->syncRuns()->create([
                'company_id' => $company->getKey(),
                'status_id' => $this->statusId(CrmSyncStatus::Pending),
            ]);

            $run->update([
                'status_id' => $runningStatusId,
                'started_at' => now(),
                'finished_at' => null,
                'error_message' => null,
            ]);

            return $run;
        });
    }

    private function syncStructure(CrmConnection $connection): void
    {
        $this->consume($connection, 'pipelines', function (array $item) use ($connection): void {
            PipedrivePipeline::query()->updateOrCreate(
                ['company_id' => $connection->company_id, 'pipedrive_id' => $this->requiredId($item)],
                [
                    'crm_connection_id' => $connection->getKey(),
                    'name' => $this->string($item, 'name', 'Pipeline'),
                    'display_order' => $this->nullableInteger($item['order_nr'] ?? $item['display_order'] ?? null),
                    'is_active' => $this->isActive($item),
                    'raw_payload' => $item,
                    'synced_at' => now(),
                ],
            );
        });

        $this->consume($connection, 'stages', function (array $item) use ($connection): void {
            $pipeline = $this->findByExternalId(PipedrivePipeline::class, $connection, $this->externalId($item['pipeline_id'] ?? null));

            if ($pipeline === null) {
                return;
            }

            PipedriveStage::query()->updateOrCreate(
                ['company_id' => $connection->company_id, 'pipedrive_id' => $this->requiredId($item)],
                [
                    'crm_connection_id' => $connection->getKey(),
                    'pipedrive_pipeline_id' => $pipeline->getKey(),
                    'name' => $this->string($item, 'name', 'Etapa'),
                    'display_order' => $this->nullableInteger($item['order_nr'] ?? $item['display_order'] ?? null),
                    'is_active' => $this->isActive($item),
                    'raw_payload' => $item,
                    'synced_at' => now(),
                ],
            );
        });

        foreach ([
            'dealFields' => 'deal',
            'personFields' => 'person',
            'organizationFields' => 'organization',
            'productFields' => 'product',
            'activityFields' => 'activity',
        ] as $resource => $entityType) {
            $this->consume($connection, $resource, function (array $item) use ($connection, $entityType): void {
                $key = $this->string($item, 'key', (string) ($item['id'] ?? ''));

                if ($key === '') {
                    return;
                }

                PipedriveCustomField::query()->updateOrCreate(
                    [
                        'company_id' => $connection->company_id,
                        'entity_type' => $entityType,
                        'pipedrive_key' => $key,
                    ],
                    [
                        'crm_connection_id' => $connection->getKey(),
                        'pipedrive_id' => $this->externalId($item['id'] ?? null),
                        'field_type' => $this->string($item, 'field_type', 'unknown'),
                        'name' => $this->string($item, 'name', $key),
                        'is_active' => $this->isActive($item),
                        'options_payload' => is_array($item['options'] ?? null) ? $item['options'] : null,
                        'raw_payload' => $item,
                        'synced_at' => now(),
                    ],
                );
            });
        }

        $this->consume($connection, 'users', function (array $item) use ($connection): void {
            PipedriveUser::query()->updateOrCreate(
                ['company_id' => $connection->company_id, 'pipedrive_id' => $this->requiredId($item)],
                [
                    'crm_connection_id' => $connection->getKey(),
                    'name' => $this->string($item, 'name', 'Usuário'),
                    'email' => $this->nullableString($item['email'] ?? null),
                    'is_active' => $this->isActive($item),
                    'raw_payload' => $item,
                    'synced_at' => now(),
                ],
            );
        });
    }

    private function syncContacts(CrmConnection $connection): void
    {
        $this->consume($connection, 'organizations', function (array $item) use ($connection): void {
            $owner = $this->findByExternalId(PipedriveUser::class, $connection, $this->externalId($item['owner_id'] ?? null));

            PipedriveOrganization::query()->updateOrCreate(
                ['company_id' => $connection->company_id, 'pipedrive_id' => $this->requiredId($item)],
                [
                    'crm_connection_id' => $connection->getKey(),
                    'owner_pipedrive_user_id' => $owner?->getKey(),
                    'name' => $this->string($item, 'name', 'Organização'),
                    'address' => $this->nullableString($item['address'] ?? null),
                    'is_active' => $this->isActive($item),
                    'raw_payload' => $item,
                    'synced_at' => now(),
                ],
            );
        });

        $this->consume($connection, 'persons', function (array $item) use ($connection): void {
            $organization = $this->findByExternalId(PipedriveOrganization::class, $connection, $this->externalId($item['org_id'] ?? null));
            $owner = $this->findByExternalId(PipedriveUser::class, $connection, $this->externalId($item['owner_id'] ?? null));

            $person = PipedrivePerson::query()->updateOrCreate(
                ['company_id' => $connection->company_id, 'pipedrive_id' => $this->requiredId($item)],
                [
                    'crm_connection_id' => $connection->getKey(),
                    'pipedrive_organization_id' => $organization?->getKey(),
                    'owner_pipedrive_user_id' => $owner?->getKey(),
                    'name' => $this->string($item, 'name', 'Pessoa'),
                    'is_active' => $this->isActive($item),
                    'raw_payload' => $item,
                    'synced_at' => now(),
                ],
            );

            $emails = $this->normalizedEmails($item['email'] ?? []);

            foreach ($emails as $email) {
                PipedrivePersonEmail::query()->updateOrCreate(
                    ['pipedrive_person_id' => $person->getKey(), 'email' => $email['email']],
                    [
                        'company_id' => $connection->company_id,
                        'label' => $email['label'],
                        'is_primary' => $email['is_primary'],
                    ],
                );
            }

            $staleEmailIds = $person->emails()
                ->whereNotIn('email', Arr::pluck($emails, 'email'))
                ->pluck('id');

            if ($staleEmailIds->isNotEmpty()) {
                CompanyCustomer::query()
                    ->where('company_id', $connection->company_id)
                    ->whereIn('pipedrive_person_email_id', $staleEmailIds)
                    ->delete();

                PipedrivePersonEmail::query()->whereKey($staleEmailIds)->delete();
            }
        });
    }

    private function syncSales(CrmConnection $connection): void
    {
        $this->consume($connection, 'deals', function (array $item) use ($connection): void {
            PipedriveDeal::query()->updateOrCreate(
                ['company_id' => $connection->company_id, 'pipedrive_id' => $this->requiredId($item)],
                [
                    'crm_connection_id' => $connection->getKey(),
                    'pipedrive_pipeline_id' => $this->findByExternalId(PipedrivePipeline::class, $connection, $this->externalId($item['pipeline_id'] ?? null))?->getKey(),
                    'pipedrive_stage_id' => $this->findByExternalId(PipedriveStage::class, $connection, $this->externalId($item['stage_id'] ?? null))?->getKey(),
                    'pipedrive_person_id' => $this->findByExternalId(PipedrivePerson::class, $connection, $this->externalId($item['person_id'] ?? null))?->getKey(),
                    'pipedrive_organization_id' => $this->findByExternalId(PipedriveOrganization::class, $connection, $this->externalId($item['org_id'] ?? null))?->getKey(),
                    'owner_pipedrive_user_id' => $this->findByExternalId(PipedriveUser::class, $connection, $this->externalId($item['user_id'] ?? $item['owner_id'] ?? null))?->getKey(),
                    'title' => $this->string($item, 'title', 'Negócio'),
                    'value' => $this->nullableDecimal($item['value'] ?? null),
                    'currency' => $this->currency($item['currency'] ?? null),
                    'raw_payload' => $item,
                    'synced_at' => now(),
                ],
            );
        });

        $this->consume($connection, 'products', function (array $item) use ($connection): void {
            PipedriveProduct::query()->updateOrCreate(
                ['company_id' => $connection->company_id, 'pipedrive_id' => $this->requiredId($item)],
                [
                    'crm_connection_id' => $connection->getKey(),
                    'name' => $this->string($item, 'name', 'Produto'),
                    'code' => $this->nullableString($item['code'] ?? null),
                    'unit_price' => $this->nullableDecimal($item['unit_price'] ?? $item['prices'][0]['price'] ?? null),
                    'currency' => $this->currency($item['currency'] ?? $item['prices'][0]['currency'] ?? null),
                    'is_active' => $this->isActive($item),
                    'raw_payload' => $item,
                    'synced_at' => now(),
                ],
            );
        });

        PipedriveDeal::query()
            ->where('company_id', $connection->company_id)
            ->where('crm_connection_id', $connection->getKey())
            ->each(function (PipedriveDeal $deal) use ($connection): void {
                $products = [];

                $this->consume($connection, "deals/{$deal->pipedrive_id}/products", function (array $item) use ($connection, &$products): void {
                    $product = $this->findByExternalId(PipedriveProduct::class, $connection, $this->externalId($item['product_id'] ?? $item['id'] ?? null));

                    if ($product !== null) {
                        $products[$product->getKey()] = [
                            'quantity' => $this->nullableDecimal($item['quantity'] ?? null) ?? 1,
                            'unit_price' => $this->nullableDecimal($item['item_price'] ?? $item['unit_price'] ?? null),
                            'discount_percentage' => $this->nullableDecimal($item['discount_percentage'] ?? $item['discount'] ?? null),
                        ];
                    }
                });

                $deal->products()->sync($products);
            });

        $this->consume($connection, 'activities', function (array $item) use ($connection): void {
            PipedriveActivity::query()->updateOrCreate(
                ['company_id' => $connection->company_id, 'pipedrive_id' => $this->requiredId($item)],
                [
                    'crm_connection_id' => $connection->getKey(),
                    'pipedrive_deal_id' => $this->findByExternalId(PipedriveDeal::class, $connection, $this->externalId($item['deal_id'] ?? null))?->getKey(),
                    'pipedrive_person_id' => $this->findByExternalId(PipedrivePerson::class, $connection, $this->externalId($item['person_id'] ?? null))?->getKey(),
                    'pipedrive_organization_id' => $this->findByExternalId(PipedriveOrganization::class, $connection, $this->externalId($item['org_id'] ?? null))?->getKey(),
                    'owner_pipedrive_user_id' => $this->findByExternalId(PipedriveUser::class, $connection, $this->externalId($item['user_id'] ?? $item['owner_id'] ?? null))?->getKey(),
                    'subject' => $this->string($item, 'subject', 'Atividade'),
                    'due_at' => $this->dueAt($item),
                    'is_done' => filter_var($item['done'] ?? false, FILTER_VALIDATE_BOOL),
                    'raw_payload' => $item,
                    'synced_at' => now(),
                ],
            );
        });
    }

    private function projectCustomers(CrmConnection $connection): void
    {
        PipedrivePersonEmail::query()
            ->with('person:id,company_id')
            ->where('company_id', $connection->company_id)
            ->each(function (PipedrivePersonEmail $personEmail) use ($connection): void {
                if ((int) $personEmail->person->company_id !== (int) $connection->company_id) {
                    return;
                }

                $identity = CustomerIdentity::query()->firstOrCreate(['email' => $personEmail->email]);

                CompanyCustomer::query()->updateOrCreate(
                    [
                        'company_id' => $connection->company_id,
                        'customer_identity_id' => $identity->getKey(),
                    ],
                    [
                        'pipedrive_person_id' => $personEmail->pipedrive_person_id,
                        'pipedrive_person_email_id' => $personEmail->getKey(),
                    ],
                );
            });
    }

    /**
     * @param  callable(array<string, mixed>): void  $import
     */
    private function consume(CrmConnection $connection, string $resource, callable $import): void
    {
        $cursor = null;

        do {
            $page = $this->pipedrive->page($connection, $resource, $cursor);

            DB::transaction(function () use ($page, $import): void {
                foreach ($page->items as $item) {
                    $import($item);
                }
            });

            $cursor = $page->nextCursor;
        } while ($cursor !== null);
    }

    private function statusId(string $slug): int
    {
        return (int) CrmSyncStatus::query()->where('slug', $slug)->valueOrFail('id');
    }

    /**
     * @param  class-string<Model>  $model
     */
    private function findByExternalId(string $model, CrmConnection $connection, ?int $externalId): ?Model
    {
        if ($externalId === null) {
            return null;
        }

        return $model::query()
            ->where('company_id', $connection->company_id)
            ->where('crm_connection_id', $connection->getKey())
            ->where('pipedrive_id', $externalId)
            ->first();
    }

    /**
     * @param  array<string, mixed>  $item
     */
    private function requiredId(array $item): int
    {
        $id = $this->externalId($item['id'] ?? null);

        if ($id === null) {
            throw new LogicException('O Pipedrive retornou um registro sem identificador.');
        }

        return $id;
    }

    private function externalId(mixed $value): ?int
    {
        if (is_array($value)) {
            $value = $value['value'] ?? $value['id'] ?? null;
        }

        return is_numeric($value) ? (int) $value : null;
    }

    /**
     * @param  array<string, mixed>  $item
     */
    private function string(array $item, string $key, string $fallback): string
    {
        return $this->nullableString($item[$key] ?? null) ?? $fallback;
    }

    private function nullableString(mixed $value): ?string
    {
        if (is_array($value)) {
            $value = $value['value'] ?? $value['name'] ?? $value['address'] ?? null;
        }

        if (! is_scalar($value)) {
            return null;
        }

        $normalized = trim((string) $value);

        return $normalized === '' ? null : $normalized;
    }

    private function nullableInteger(mixed $value): ?int
    {
        return is_numeric($value) ? (int) $value : null;
    }

    private function nullableDecimal(mixed $value): int|float|string|null
    {
        return is_numeric($value) ? $value : null;
    }

    private function currency(mixed $value): ?string
    {
        $currency = $this->nullableString($value);

        return $currency === null ? null : Str::upper(Str::limit($currency, 3, ''));
    }

    /**
     * @param  array<string, mixed>  $item
     */
    private function isActive(array $item): bool
    {
        if (array_key_exists('active_flag', $item)) {
            return filter_var($item['active_flag'], FILTER_VALIDATE_BOOL);
        }

        return ! in_array($item['status'] ?? null, ['deleted', 'inactive'], true);
    }

    /**
     * @return list<array{email: string, label: ?string, is_primary: bool}>
     */
    private function normalizedEmails(mixed $values): array
    {
        $values = is_array($values) ? $values : [$values];
        $emails = [];

        foreach ($values as $index => $value) {
            $email = is_array($value) ? ($value['value'] ?? null) : $value;

            if (! is_string($email)) {
                continue;
            }

            $email = Str::lower(trim($email));

            if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
                continue;
            }

            $emails[$email] = [
                'email' => $email,
                'label' => is_array($value) ? $this->nullableString($value['label'] ?? null) : null,
                'is_primary' => is_array($value)
                    ? filter_var($value['primary'] ?? false, FILTER_VALIDATE_BOOL)
                    : $index === 0,
            ];
        }

        return array_values($emails);
    }

    /**
     * @param  array<string, mixed>  $item
     */
    private function dueAt(array $item): ?string
    {
        $date = $this->nullableString($item['due_date'] ?? null);

        if ($date === null) {
            return null;
        }

        $time = $this->nullableString($item['due_time'] ?? null) ?? '00:00:00';

        return "{$date} {$time}";
    }
}
