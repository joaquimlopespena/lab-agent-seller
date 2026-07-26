<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

abstract class PipedriveModel extends Model
{
    use BelongsToCompany;

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function crmConnection(): BelongsTo
    {
        return $this->belongsTo(CrmConnection::class);
    }

    protected function tenantRelationships(): array
    {
        return ['crmConnection'];
    }
}
