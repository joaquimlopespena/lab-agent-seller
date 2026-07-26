<?php

namespace App\Models;

use Database\Factories\CustomerIdentityFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;

#[Fillable(['email'])]
class CustomerIdentity extends Authenticatable
{
    /** @use HasFactory<CustomerIdentityFactory> */
    use HasFactory;

    /** @return HasMany<CompanyCustomer, $this> */
    public function companyCustomers(): HasMany
    {
        return $this->hasMany(CompanyCustomer::class);
    }

    /** @return HasMany<CustomerMagicLink, $this> */
    public function magicLinks(): HasMany
    {
        return $this->hasMany(CustomerMagicLink::class);
    }

    /** @return HasMany<CustomerSession, $this> */
    public function sessions(): HasMany
    {
        return $this->hasMany(CustomerSession::class);
    }

    /** @return BelongsToMany<Company, $this> */
    public function companies(): BelongsToMany
    {
        return $this->belongsToMany(Company::class, 'company_customers')->withTimestamps();
    }
}
