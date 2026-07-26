<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Database\Factories\CrmConnectionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['company_id', 'status_id', 'provider', 'external_company_id', 'access_token', 'refresh_token', 'token_expires_at', 'authorized_at', 'last_error'])]
#[Hidden(['access_token', 'refresh_token'])]
class CrmConnection extends Model
{
    /** @use HasFactory<CrmConnectionFactory> */
    use BelongsToCompany, HasFactory;

    protected function casts(): array
    {
        return [
            'access_token' => 'encrypted',
            'refresh_token' => 'encrypted',
            'token_expires_at' => 'datetime',
            'authorized_at' => 'datetime',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function status(): BelongsTo
    {
        return $this->belongsTo(CrmConnectionStatus::class, 'status_id');
    }

    public function syncRuns(): HasMany
    {
        return $this->hasMany(CrmSyncRun::class);
    }

    public function pipelines(): HasMany
    {
        return $this->hasMany(PipedrivePipeline::class);
    }

    public function stages(): HasMany
    {
        return $this->hasMany(PipedriveStage::class);
    }

    public function customFields(): HasMany
    {
        return $this->hasMany(PipedriveCustomField::class);
    }

    public function pipedriveUsers(): HasMany
    {
        return $this->hasMany(PipedriveUser::class);
    }

    public function organizations(): HasMany
    {
        return $this->hasMany(PipedriveOrganization::class);
    }

    public function people(): HasMany
    {
        return $this->hasMany(PipedrivePerson::class);
    }

    public function deals(): HasMany
    {
        return $this->hasMany(PipedriveDeal::class);
    }

    public function products(): HasMany
    {
        return $this->hasMany(PipedriveProduct::class);
    }

    public function activities(): HasMany
    {
        return $this->hasMany(PipedriveActivity::class);
    }
}
