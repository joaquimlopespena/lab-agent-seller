<?php

namespace App\Models;

use Database\Factories\CompanyFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable(['name'])]
class Company extends Model
{
    /** @use HasFactory<CompanyFactory> */
    use HasFactory;

    public function administrator(): HasOne
    {
        return $this->hasOne(User::class);
    }

    public function crmConnection(): HasOne
    {
        return $this->hasOne(CrmConnection::class);
    }

    public function crmSyncRuns(): HasMany
    {
        return $this->hasMany(CrmSyncRun::class);
    }

    public function customers(): HasMany
    {
        return $this->hasMany(CompanyCustomer::class);
    }

    public function conversations(): HasMany
    {
        return $this->hasMany(AgentConversation::class);
    }

    public function customerIdentities(): BelongsToMany
    {
        return $this->belongsToMany(CustomerIdentity::class, 'company_customers')->withTimestamps();
    }

    public function customerSessions(): HasMany
    {
        return $this->hasMany(CustomerSession::class, 'active_company_id');
    }

    public function pipedrivePipelines(): HasMany
    {
        return $this->hasMany(PipedrivePipeline::class);
    }

    public function pipedriveStages(): HasMany
    {
        return $this->hasMany(PipedriveStage::class);
    }

    public function pipedriveCustomFields(): HasMany
    {
        return $this->hasMany(PipedriveCustomField::class);
    }

    public function pipedriveUsers(): HasMany
    {
        return $this->hasMany(PipedriveUser::class);
    }

    public function pipedriveOrganizations(): HasMany
    {
        return $this->hasMany(PipedriveOrganization::class);
    }

    public function pipedrivePeople(): HasMany
    {
        return $this->hasMany(PipedrivePerson::class);
    }

    public function pipedrivePersonEmails(): HasMany
    {
        return $this->hasMany(PipedrivePersonEmail::class);
    }

    public function pipedriveDeals(): HasMany
    {
        return $this->hasMany(PipedriveDeal::class);
    }

    public function pipedriveProducts(): HasMany
    {
        return $this->hasMany(PipedriveProduct::class);
    }

    public function pipedriveActivities(): HasMany
    {
        return $this->hasMany(PipedriveActivity::class);
    }
}
