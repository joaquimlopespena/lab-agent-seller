<?php

namespace App\Models\Concerns;

use App\Models\Company;
use DomainException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

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
     * @return list<string>
     */
    protected function tenantRelationships(): array
    {
        return [];
    }
}
