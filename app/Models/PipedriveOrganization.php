<?php

namespace App\Models;

use Database\Factories\PipedriveOrganizationFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['company_id', 'crm_connection_id', 'owner_pipedrive_user_id', 'pipedrive_id', 'name', 'address', 'is_active', 'raw_payload', 'synced_at'])]
class PipedriveOrganization extends PipedriveModel
{
    /** @use HasFactory<PipedriveOrganizationFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return ['is_active' => 'boolean', 'raw_payload' => 'array', 'synced_at' => 'datetime'];
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(PipedriveUser::class, 'owner_pipedrive_user_id');
    }

    public function people(): HasMany
    {
        return $this->hasMany(PipedrivePerson::class);
    }

    public function deals(): HasMany
    {
        return $this->hasMany(PipedriveDeal::class);
    }

    public function activities(): HasMany
    {
        return $this->hasMany(PipedriveActivity::class);
    }

    protected function tenantRelationships(): array
    {
        return [...parent::tenantRelationships(), 'owner'];
    }
}
