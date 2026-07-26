<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Database\Factories\CrmConnectionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $company_id
 * @property int $status_id
 * @property string $provider
 * @property Carbon|null $authorized_at
 * @property Carbon|null $token_expires_at
 * @property string|null $last_error
 * @property-read CrmConnectionStatus $status
 * @property-read Collection<int, CrmSyncRun> $syncRuns
 */
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

    /** @return BelongsTo<Company, $this> */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /** @return BelongsTo<CrmConnectionStatus, $this> */
    public function status(): BelongsTo
    {
        return $this->belongsTo(CrmConnectionStatus::class, 'status_id');
    }

    /** @return HasMany<CrmSyncRun, $this> */
    public function syncRuns(): HasMany
    {
        return $this->hasMany(CrmSyncRun::class);
    }

    /** @return HasMany<PipedrivePipeline, $this> */
    public function pipelines(): HasMany
    {
        return $this->hasMany(PipedrivePipeline::class);
    }

    /** @return HasMany<PipedriveStage, $this> */
    public function stages(): HasMany
    {
        return $this->hasMany(PipedriveStage::class);
    }

    /** @return HasMany<PipedriveCustomField, $this> */
    public function customFields(): HasMany
    {
        return $this->hasMany(PipedriveCustomField::class);
    }

    /** @return HasMany<PipedriveUser, $this> */
    public function pipedriveUsers(): HasMany
    {
        return $this->hasMany(PipedriveUser::class);
    }

    /** @return HasMany<PipedriveOrganization, $this> */
    public function organizations(): HasMany
    {
        return $this->hasMany(PipedriveOrganization::class);
    }

    /** @return HasMany<PipedrivePerson, $this> */
    public function people(): HasMany
    {
        return $this->hasMany(PipedrivePerson::class);
    }

    /** @return HasMany<PipedriveDeal, $this> */
    public function deals(): HasMany
    {
        return $this->hasMany(PipedriveDeal::class);
    }

    /** @return HasMany<PipedriveProduct, $this> */
    public function products(): HasMany
    {
        return $this->hasMany(PipedriveProduct::class);
    }

    /** @return HasMany<PipedriveActivity, $this> */
    public function activities(): HasMany
    {
        return $this->hasMany(PipedriveActivity::class);
    }
}
