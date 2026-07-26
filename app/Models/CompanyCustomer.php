<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Database\Factories\CompanyCustomerFactory;
use DomainException;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * @property int $id
 * @property int $company_id
 * @property int $customer_identity_id
 * @property int $pipedrive_person_id
 * @property int $pipedrive_person_email_id
 * @property-read CustomerIdentity $customerIdentity
 * @property-read PipedrivePerson $pipedrivePerson
 */
#[Fillable(['company_id', 'customer_identity_id', 'pipedrive_person_id', 'pipedrive_person_email_id'])]
class CompanyCustomer extends Model
{
    /** @use HasFactory<CompanyCustomerFactory> */
    use BelongsToCompany, HasFactory;

    protected static function booted(): void
    {
        static::saving(function (self $companyCustomer): void {
            $email = $companyCustomer->pipedrivePersonEmail()->first();

            if ($email !== null && (int) $email->pipedrive_person_id !== (int) $companyCustomer->pipedrive_person_id) {
                throw new DomainException('The Pipedrive email must belong to the selected person.');
            }
        });
    }

    /** @return BelongsTo<Company, $this> */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /** @return BelongsTo<CustomerIdentity, $this> */
    public function customerIdentity(): BelongsTo
    {
        return $this->belongsTo(CustomerIdentity::class);
    }

    /** @return BelongsTo<PipedrivePerson, $this> */
    public function pipedrivePerson(): BelongsTo
    {
        return $this->belongsTo(PipedrivePerson::class);
    }

    /** @return BelongsTo<PipedrivePersonEmail, $this> */
    public function pipedrivePersonEmail(): BelongsTo
    {
        return $this->belongsTo(PipedrivePersonEmail::class);
    }

    /** @return HasOne<AgentConversation, $this> */
    public function conversation(): HasOne
    {
        return $this->hasOne(AgentConversation::class);
    }

    /** @return list<string> */
    protected function tenantRelationships(): array
    {
        return ['pipedrivePerson', 'pipedrivePersonEmail'];
    }
}
