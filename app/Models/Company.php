<?php

namespace App\Models;

use Database\Factories\CompanyFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * @property int $id
 * @property string $name
 * @property-read CrmConnection|null $crmConnection
 */
#[Fillable(['name'])]
class Company extends Model
{
    /** @use HasFactory<CompanyFactory> */
    use HasFactory;

    /** @return HasOne<User, $this> */
    public function administrator(): HasOne
    {
        return $this->hasOne(User::class);
    }

    /** @return HasOne<CrmConnection, $this> */
    public function crmConnection(): HasOne
    {
        return $this->hasOne(CrmConnection::class);
    }

    /** @return HasMany<CrmSyncRun, $this> */
    public function crmSyncRuns(): HasMany
    {
        return $this->hasMany(CrmSyncRun::class);
    }

    /** @return HasMany<CompanyCustomer, $this> */
    public function customers(): HasMany
    {
        return $this->hasMany(CompanyCustomer::class);
    }

    /** @return HasMany<AgentConversation, $this> */
    public function conversations(): HasMany
    {
        return $this->hasMany(AgentConversation::class);
    }

    /** @return BelongsToMany<CustomerIdentity, $this> */
    public function customerIdentities(): BelongsToMany
    {
        return $this->belongsToMany(CustomerIdentity::class, 'company_customers')->withTimestamps();
    }

    /** @return HasMany<CustomerSession, $this> */
    public function customerSessions(): HasMany
    {
        return $this->hasMany(CustomerSession::class, 'active_company_id');
    }

    /** @return HasMany<PipedrivePipeline, $this> */
    public function pipedrivePipelines(): HasMany
    {
        return $this->hasMany(PipedrivePipeline::class);
    }

    /** @return HasMany<PipedriveStage, $this> */
    public function pipedriveStages(): HasMany
    {
        return $this->hasMany(PipedriveStage::class);
    }

    /** @return HasMany<PipedriveCustomField, $this> */
    public function pipedriveCustomFields(): HasMany
    {
        return $this->hasMany(PipedriveCustomField::class);
    }

    /** @return HasMany<PipedriveUser, $this> */
    public function pipedriveUsers(): HasMany
    {
        return $this->hasMany(PipedriveUser::class);
    }

    /** @return HasMany<PipedriveOrganization, $this> */
    public function pipedriveOrganizations(): HasMany
    {
        return $this->hasMany(PipedriveOrganization::class);
    }

    /** @return HasMany<PipedrivePerson, $this> */
    public function pipedrivePeople(): HasMany
    {
        return $this->hasMany(PipedrivePerson::class);
    }

    /** @return HasMany<PipedrivePersonEmail, $this> */
    public function pipedrivePersonEmails(): HasMany
    {
        return $this->hasMany(PipedrivePersonEmail::class);
    }

    /** @return HasMany<PipedriveDeal, $this> */
    public function pipedriveDeals(): HasMany
    {
        return $this->hasMany(PipedriveDeal::class);
    }

    /** @return HasMany<PipedriveProduct, $this> */
    public function pipedriveProducts(): HasMany
    {
        return $this->hasMany(PipedriveProduct::class);
    }

    /** @return HasMany<PipedriveActivity, $this> */
    public function pipedriveActivities(): HasMany
    {
        return $this->hasMany(PipedriveActivity::class);
    }
}
