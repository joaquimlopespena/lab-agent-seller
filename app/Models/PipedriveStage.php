<?php

namespace App\Models;

use Database\Factories\PipedriveStageFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['company_id', 'crm_connection_id', 'pipedrive_pipeline_id', 'pipedrive_id', 'name', 'display_order', 'is_active', 'raw_payload', 'synced_at'])]
class PipedriveStage extends PipedriveModel
{
    /** @use HasFactory<PipedriveStageFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return ['is_active' => 'boolean', 'raw_payload' => 'array', 'synced_at' => 'datetime'];
    }

    public function pipeline(): BelongsTo
    {
        return $this->belongsTo(PipedrivePipeline::class, 'pipedrive_pipeline_id');
    }

    public function deals(): HasMany
    {
        return $this->hasMany(PipedriveDeal::class);
    }

    protected function tenantRelationships(): array
    {
        return [...parent::tenantRelationships(), 'pipeline'];
    }
}
