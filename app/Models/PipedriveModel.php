<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

abstract class PipedriveModel extends Model
{
    use BelongsToCompany;

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

    /** @return list<string> */
    protected function tenantRelationships(): array
    {
        return ['crmConnection'];
    }
}
