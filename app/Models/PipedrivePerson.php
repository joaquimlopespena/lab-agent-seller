<?php

namespace App\Models;

use Database\Factories\PipedrivePersonFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['company_id', 'crm_connection_id', 'pipedrive_organization_id', 'owner_pipedrive_user_id', 'pipedrive_id', 'name', 'is_active', 'raw_payload', 'synced_at'])]
class PipedrivePerson extends PipedriveModel
{
    /** @use HasFactory<PipedrivePersonFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return ['is_active' => 'boolean', 'raw_payload' => 'array', 'synced_at' => 'datetime'];
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(PipedriveOrganization::class, 'pipedrive_organization_id');
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(PipedriveUser::class, 'owner_pipedrive_user_id');
    }

    public function emails(): HasMany
    {
        return $this->hasMany(PipedrivePersonEmail::class);
    }

    public function deals(): HasMany
    {
        return $this->hasMany(PipedriveDeal::class);
    }

    public function activities(): HasMany
    {
        return $this->hasMany(PipedriveActivity::class);
    }

    public function companyCustomers(): HasMany
    {
        return $this->hasMany(CompanyCustomer::class);
    }

    protected function tenantRelationships(): array
    {
        return [...parent::tenantRelationships(), 'organization', 'owner'];
    }
}
