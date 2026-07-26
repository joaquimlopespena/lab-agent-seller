<?php

namespace App\Models\Concerns;

use App\Models\Company;
use App\Tenancy\TenantContext;
use DomainException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;

trait BelongsToCompany
{
    protected static function bootBelongsToCompany(): void
    {
        static::saving(function (Model $model): void {
            foreach ($model->tenantRelationships() as $relationship) {
                $foreignKey = $model->{$relationship}()->getForeignKeyName();
                $relatedId = $model->getAttribute($foreignKey);

                if ($relatedId === null) {
                    continue;
                }

                $related = $model->{$relationship}()->getRelated()
                    ->newQuery()
                    ->find($relatedId);

                if ($related === null || (int) $related->getAttribute('company_id') !== (int) $model->getAttribute('company_id')) {
                    throw new DomainException("The {$relationship} reference must belong to the same company.");
                }
            }
        });
    }

    /**
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeForCompany(Builder $query, Company|int $company): Builder
    {
        return $query->where(
            $this->qualifyColumn('company_id'),
            $company instanceof Company ? $company->getKey() : $company,
        );
    }

    /**
     * @param  Model|Builder<static>|Relation<*, *, *>  $query
     * @return Builder<static>
     */
    public function resolveRouteBindingQuery($query, $value, $field = null): Builder
    {
        $tenantContext = app(TenantContext::class);

        if ($tenantContext->current() === null && request()->user() !== null) {
            $tenantContext->setForUser(request()->user());
        }

        return parent::resolveRouteBindingQuery($query, $value, $field)
            ->where($this->qualifyColumn('company_id'), $tenantContext->id());
    }

    /**
     * @return list<string>
     */
    protected function tenantRelationships(): array
    {
        return [];
    }
}
