<?php

namespace App\Models;

use Database\Factories\CustomerIdentityFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['email'])]
class CustomerIdentity extends Model
{
    /** @use HasFactory<CustomerIdentityFactory> */
    use HasFactory;

    public function companyCustomers(): HasMany
    {
        return $this->hasMany(CompanyCustomer::class);
    }

    public function magicLinks(): HasMany
    {
        return $this->hasMany(CustomerMagicLink::class);
    }

    public function sessions(): HasMany
    {
        return $this->hasMany(CustomerSession::class);
    }

    public function companies(): BelongsToMany
    {
        return $this->belongsToMany(Company::class, 'company_customers')->withTimestamps();
    }
}
