<?php

namespace App\CustomerAuth;

use App\Models\Company;
use App\Models\CompanyCustomer;
use App\Models\CustomerIdentity;
use App\Models\CustomerSession;
use Illuminate\Database\Eloquent\Collection;

class CustomerCompanyResolver
{
    /** @return Collection<int, Company> */
    public function availableCompanies(CustomerIdentity $customerIdentity): Collection
    {
        return Company::query()
            ->select(['companies.id', 'companies.name'])
            ->whereHas('customers', fn ($query) => $query->whereBelongsTo($customerIdentity))
            ->orderBy('name')
            ->orderBy('id')
            ->get();
    }

    public function companyCustomer(CustomerIdentity $customerIdentity, int $companyId): ?CompanyCustomer
    {
        return CompanyCustomer::query()
            ->whereBelongsTo($customerIdentity)
            ->where('company_id', $companyId)
            ->first();
    }

    public function automaticallySelectOnlyCompany(
        CustomerSession $customerSession,
        CustomerIdentity $customerIdentity,
    ): ?Company {
        $companies = $this->availableCompanies($customerIdentity);

        if ($companies->count() !== 1) {
            return null;
        }

        $company = $companies->sole();

        if ((int) $customerSession->active_company_id !== $company->id) {
            $customerSession->update(['active_company_id' => $company->id]);
        }

        return $company;
    }
}
