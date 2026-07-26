<?php

namespace App\Models;

use Database\Factories\PipedriveUserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['company_id', 'crm_connection_id', 'pipedrive_id', 'name', 'email', 'is_active', 'raw_payload', 'synced_at'])]
class PipedriveUser extends PipedriveModel
{
    /** @use HasFactory<PipedriveUserFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return ['is_active' => 'boolean', 'raw_payload' => 'array', 'synced_at' => 'datetime'];
    }

    /** @return HasMany<PipedriveOrganization, $this> */
    public function organizations(): HasMany
    {
        return $this->hasMany(PipedriveOrganization::class, 'owner_pipedrive_user_id');
    }

    /** @return HasMany<PipedrivePerson, $this> */
    public function people(): HasMany
    {
        return $this->hasMany(PipedrivePerson::class, 'owner_pipedrive_user_id');
    }

    /** @return HasMany<PipedriveDeal, $this> */
    public function deals(): HasMany
    {
        return $this->hasMany(PipedriveDeal::class, 'owner_pipedrive_user_id');
    }

    /** @return HasMany<PipedriveActivity, $this> */
    public function activities(): HasMany
    {
        return $this->hasMany(PipedriveActivity::class, 'owner_pipedrive_user_id');
    }
}
