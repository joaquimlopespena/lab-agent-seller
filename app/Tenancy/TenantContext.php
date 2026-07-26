<?php

namespace App\Tenancy;

use App\Models\Company;
use App\Models\User;
use LogicException;

class TenantContext
{
    private ?Company $company = null;

    public function set(Company $company): void
    {
        $this->company = $company;
    }

    public function setForUser(User $user): void
    {
        $this->set($user->company()->firstOrFail());
    }

    public function forget(): void
    {
        $this->company = null;
    }

    public function current(): ?Company
    {
        return $this->company;
    }

    public function company(): Company
    {
        return $this->company ?? throw new LogicException('No authenticated company is available in the current request.');
    }

    public function id(): int
    {
        return (int) $this->company()->getKey();
    }
}
