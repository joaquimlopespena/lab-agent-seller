<?php

namespace App\Support;

use App\Models\PipedriveActivity;
use App\Models\PipedriveCustomField;
use App\Models\PipedriveDeal;
use App\Models\PipedriveModel;
use App\Models\PipedriveOrganization;
use App\Models\PipedrivePerson;
use App\Models\PipedrivePipeline;
use App\Models\PipedriveProduct;
use App\Models\PipedriveStage;
use App\Models\PipedriveUser;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class SyncedDataCatalog
{
    /**
     * @return array<string, array{label: string, description: string, model: class-string<PipedriveModel>, search: list<string>, relations: list<string>}>
     */
    public function categories(): array
    {
        return [
            'pipelines' => ['label' => 'Pipelines', 'description' => 'Funis e suas etapas comerciais.', 'model' => PipedrivePipeline::class, 'search' => ['name'], 'relations' => ['stages:id,pipedrive_pipeline_id,name,display_order,is_active']],
            'stages' => ['label' => 'Etapas', 'description' => 'Etapas dos pipelines importados.', 'model' => PipedriveStage::class, 'search' => ['name'], 'relations' => ['pipeline:id,company_id,name']],
            'custom-fields' => ['label' => 'Campos', 'description' => 'Campos personalizados disponíveis no CRM.', 'model' => PipedriveCustomField::class, 'search' => ['name', 'pipedrive_key', 'entity_type', 'field_type'], 'relations' => []],
            'users' => ['label' => 'Usuários', 'description' => 'Usuários responsáveis no Pipedrive.', 'model' => PipedriveUser::class, 'search' => ['name', 'email'], 'relations' => []],
            'organizations' => ['label' => 'Organizações', 'description' => 'Empresas e organizações importadas.', 'model' => PipedriveOrganization::class, 'search' => ['name', 'address'], 'relations' => ['owner:id,company_id,name,email', 'people:id,company_id,pipedrive_organization_id,name']],
            'people' => ['label' => 'Pessoas', 'description' => 'Contatos e todos os seus e-mails.', 'model' => PipedrivePerson::class, 'search' => ['name'], 'relations' => ['organization:id,company_id,name', 'owner:id,company_id,name,email', 'emails:id,company_id,pipedrive_person_id,email,label,is_primary']],
            'deals' => ['label' => 'Negócios', 'description' => 'Oportunidades e produtos relacionados.', 'model' => PipedriveDeal::class, 'search' => ['title', 'currency'], 'relations' => ['pipeline:id,company_id,name', 'stage:id,company_id,name', 'person:id,company_id,name', 'organization:id,company_id,name', 'owner:id,company_id,name,email', 'products:id,company_id,name,code,unit_price,currency']],
            'products' => ['label' => 'Produtos', 'description' => 'Produtos disponíveis e negócios relacionados.', 'model' => PipedriveProduct::class, 'search' => ['name', 'code', 'currency'], 'relations' => ['deals:id,company_id,title,value,currency']],
            'activities' => ['label' => 'Atividades', 'description' => 'Compromissos e tarefas comerciais.', 'model' => PipedriveActivity::class, 'search' => ['subject'], 'relations' => ['deal:id,company_id,title', 'person:id,company_id,name', 'organization:id,company_id,name', 'owner:id,company_id,name,email']],
        ];
    }

    /**
     * @return array{label: string, description: string, model: class-string<PipedriveModel>, search: list<string>, relations: list<string>}
     */
    public function category(string $category): array
    {
        return $this->categories()[$category] ?? abort(404);
    }

    /**
     * @param  Builder<PipedriveModel>  $query
     * @param  list<string>  $columns
     */
    public function applySearch(Builder $query, string $search, array $columns): void
    {
        if ($search === '') {
            return;
        }

        $query->where(function (Builder $query) use ($columns, $search): void {
            foreach ($columns as $column) {
                $query->orWhere($column, 'ilike', '%'.$search.'%');
            }
        });
    }

    /**
     * @return array<string, mixed>
     */
    public function summary(PipedriveModel $record, string $category): array
    {
        return array_filter([
            'id' => $record->getKey(),
            'external_id' => $record->getAttribute('pipedrive_id'),
            'title' => $this->title($record),
            'subtitle' => $this->subtitle($record, $category),
            'active' => $record->getAttribute('is_active'),
            'synced_at' => $record->getAttribute('synced_at')?->toISOString(),
        ], static fn (mixed $value): bool => $value !== null);
    }

    /**
     * @return array<string, mixed>
     */
    public function detail(PipedriveModel $record, string $category): array
    {
        return [
            ...$this->summary($record, $category),
            'fields' => $this->fields($record, $category),
            'relations' => $this->relations($record, $category),
        ];
    }

    private function title(PipedriveModel $record): string
    {
        return (string) ($record->getAttribute('name')
            ?? $record->getAttribute('title')
            ?? $record->getAttribute('subject')
            ?? $record->getAttribute('pipedrive_key'));
    }

    private function subtitle(PipedriveModel $record, string $category): ?string
    {
        return match ($category) {
            'custom-fields' => implode(' · ', array_filter([$record->getAttribute('entity_type'), $record->getAttribute('field_type')])),
            'users' => $record->getAttribute('email'),
            'organizations' => $record->getAttribute('address'),
            'people' => $record->relationLoaded('emails') ? $record->getRelation('emails')->first()?->email : null,
            'deals' => implode(' ', array_filter([$record->getAttribute('currency'), $record->getAttribute('value')])),
            'products' => $record->getAttribute('code'),
            'activities' => $record->getAttribute('due_at')?->toISOString(),
            default => null,
        };
    }

    /**
     * @return array<string, scalar|null>
     */
    private function fields(PipedriveModel $record, string $category): array
    {
        $columns = match ($category) {
            'pipelines', 'stages' => ['display_order', 'is_active', 'synced_at'],
            'custom-fields' => ['pipedrive_key', 'entity_type', 'field_type', 'is_active', 'synced_at'],
            'users' => ['email', 'is_active', 'synced_at'],
            'organizations' => ['address', 'is_active', 'synced_at'],
            'people' => ['is_active', 'synced_at'],
            'deals' => ['value', 'currency', 'synced_at'],
            'products' => ['code', 'unit_price', 'currency', 'is_active', 'synced_at'],
            'activities' => ['due_at', 'is_done', 'synced_at'],
            default => [],
        };

        return collect($columns)->mapWithKeys(function (string $column) use ($record): array {
            $value = $record->getAttribute($column);

            return [$column => $value instanceof DateTimeInterface ? $value->format(DATE_ATOM) : $value];
        })->all();
    }

    /**
     * @return array<string, mixed>
     */
    private function relations(PipedriveModel $record, string $category): array
    {
        $names = match ($category) {
            'pipelines' => ['stages'],
            'stages' => ['pipeline'],
            'organizations' => ['owner', 'people'],
            'people' => ['organization', 'owner', 'emails'],
            'deals' => ['pipeline', 'stage', 'person', 'organization', 'owner', 'products'],
            'products' => ['deals'],
            'activities' => ['deal', 'person', 'organization', 'owner'],
            default => [],
        };

        return collect($names)->mapWithKeys(function (string $name) use ($record): array {
            $value = $record->getRelation($name);

            if ($value instanceof Model) {
                return [$name => $this->relationItem($value)];
            }

            return [$name => $value?->map(fn (Model $item): array => $this->relationItem($item))->values()->all() ?? []];
        })->all();
    }

    /**
     * @return array<string, mixed>
     */
    private function relationItem(Model $record): array
    {
        $item = [
            'id' => $record->getKey(),
            'title' => $record->getAttribute('name') ?? $record->getAttribute('title') ?? $record->getAttribute('email'),
            'email' => $record->getAttribute('email'),
            'label' => $record->getAttribute('label'),
            'is_primary' => $record->getAttribute('is_primary'),
        ];

        if ($record->getAttribute('pivot') !== null) {
            $item['pivot'] = $record->getAttribute('pivot')->only(['quantity', 'unit_price', 'discount_percentage']);
        }

        return array_filter($item, static fn (mixed $value): bool => $value !== null);
    }
}
