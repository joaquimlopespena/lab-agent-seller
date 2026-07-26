<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Database\Factories\CrmSyncRunFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['company_id', 'crm_connection_id', 'status_id', 'started_at', 'finished_at', 'error_message'])]
class CrmSyncRun extends Model
{
    /** @use HasFactory<CrmSyncRunFactory> */
    use BelongsToCompany, HasFactory;

    protected function casts(): array
    {
        return ['started_at' => 'datetime', 'finished_at' => 'datetime'];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function crmConnection(): BelongsTo
    {
        return $this->belongsTo(CrmConnection::class);
    }

    public function status(): BelongsTo
    {
        return $this->belongsTo(CrmSyncStatus::class, 'status_id');
    }

    protected function tenantRelationships(): array
    {
        return ['crmConnection'];
    }
}
