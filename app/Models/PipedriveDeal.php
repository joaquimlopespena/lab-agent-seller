<?php

namespace App\Models;

use Database\Factories\PipedriveDealFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['company_id', 'crm_connection_id', 'pipedrive_pipeline_id', 'pipedrive_stage_id', 'pipedrive_person_id', 'pipedrive_organization_id', 'owner_pipedrive_user_id', 'pipedrive_id', 'title', 'value', 'currency', 'raw_payload', 'synced_at'])]
class PipedriveDeal extends PipedriveModel
{
    /** @use HasFactory<PipedriveDealFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return ['value' => 'decimal:2', 'raw_payload' => 'array', 'synced_at' => 'datetime'];
    }

    /** @return BelongsTo<PipedrivePipeline, $this> */
    public function pipeline(): BelongsTo
    {
        return $this->belongsTo(PipedrivePipeline::class, 'pipedrive_pipeline_id');
    }

    /** @return BelongsTo<PipedriveStage, $this> */
    public function stage(): BelongsTo
    {
        return $this->belongsTo(PipedriveStage::class, 'pipedrive_stage_id');
    }

    /** @return BelongsTo<PipedrivePerson, $this> */
    public function person(): BelongsTo
    {
        return $this->belongsTo(PipedrivePerson::class, 'pipedrive_person_id');
    }

    /** @return BelongsTo<PipedriveOrganization, $this> */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(PipedriveOrganization::class, 'pipedrive_organization_id');
    }

    /** @return BelongsTo<PipedriveUser, $this> */
    public function owner(): BelongsTo
    {
        return $this->belongsTo(PipedriveUser::class, 'owner_pipedrive_user_id');
    }

    /** @return BelongsToMany<PipedriveProduct, $this, PipedriveDealProduct, 'pivot'> */
    public function products(): BelongsToMany
    {
        return $this->belongsToMany(PipedriveProduct::class, 'pipedrive_deal_products')
            ->using(PipedriveDealProduct::class)
            ->withPivot(['quantity', 'unit_price', 'discount_percentage'])
            ->withTimestamps();
    }

    /** @return HasMany<PipedriveActivity, $this> */
    public function activities(): HasMany
    {
        return $this->hasMany(PipedriveActivity::class);
    }

    /** @return list<string> */
    protected function tenantRelationships(): array
    {
        return [...parent::tenantRelationships(), 'pipeline', 'stage', 'person', 'organization', 'owner'];
    }
}
