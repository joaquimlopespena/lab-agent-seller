<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Database\Factories\CrmSyncRunFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property Carbon|null $started_at
 * @property Carbon|null $finished_at
 * @property string|null $error_message
 * @property Carbon $created_at
 * @property-read CrmSyncStatus $status
 */
#[Fillable(['company_id', 'crm_connection_id', 'status_id', 'started_at', 'finished_at', 'error_message'])]
class CrmSyncRun extends Model
{
    /** @use HasFactory<CrmSyncRunFactory> */
    use BelongsToCompany, HasFactory;

    protected function casts(): array
    {
        return ['started_at' => 'datetime', 'finished_at' => 'datetime'];
    }

    /** @return BelongsTo<Company, $this> */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /** @return BelongsTo<CrmConnection, $this> */
    public function crmConnection(): BelongsTo
    {
        return $this->belongsTo(CrmConnection::class);
    }

    /** @return BelongsTo<CrmSyncStatus, $this> */
    public function status(): BelongsTo
    {
        return $this->belongsTo(CrmSyncStatus::class, 'status_id');
    }

    /** @return list<string> */
    protected function tenantRelationships(): array
    {
        return ['crmConnection'];
    }
}
