<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Database\Factories\PipedrivePersonEmailFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['company_id', 'pipedrive_person_id', 'email', 'label', 'is_primary'])]
class PipedrivePersonEmail extends Model
{
    /** @use HasFactory<PipedrivePersonEmailFactory> */
    use BelongsToCompany, HasFactory;

    protected function casts(): array
    {
        return ['is_primary' => 'boolean'];
    }

    /** @return BelongsTo<Company, $this> */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /** @return BelongsTo<PipedrivePerson, $this> */
    public function person(): BelongsTo
    {
        return $this->belongsTo(PipedrivePerson::class, 'pipedrive_person_id');
    }

    /** @return HasMany<CompanyCustomer, $this> */
    public function companyCustomers(): HasMany
    {
        return $this->hasMany(CompanyCustomer::class);
    }

    /** @return list<string> */
    protected function tenantRelationships(): array
    {
        return ['person'];
    }
}
