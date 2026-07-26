<?php

namespace App\Models;

use Database\Factories\PipedrivePipelineFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['company_id', 'crm_connection_id', 'pipedrive_id', 'name', 'display_order', 'is_active', 'raw_payload', 'synced_at'])]
class PipedrivePipeline extends PipedriveModel
{
    /** @use HasFactory<PipedrivePipelineFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return ['is_active' => 'boolean', 'raw_payload' => 'array', 'synced_at' => 'datetime'];
    }

    /** @return HasMany<PipedriveStage, $this> */
    public function stages(): HasMany
    {
        return $this->hasMany(PipedriveStage::class);
    }

    /** @return HasMany<PipedriveDeal, $this> */
    public function deals(): HasMany
    {
        return $this->hasMany(PipedriveDeal::class);
    }
}
