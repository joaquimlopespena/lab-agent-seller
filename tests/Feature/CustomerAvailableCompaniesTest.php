<?php

use App\CustomerAuth\CustomerCompanyResolver;
use App\Models\Company;
use App\Models\CompanyCustomer;
use App\Models\CustomerIdentity;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('available companies reflect zero one and multiple current associations', function () {
    $identity = CustomerIdentity::factory()->create();
    $unrelatedCompany = Company::factory()->create(['name' => 'Empresa não vinculada']);
    $resolver = app(CustomerCompanyResolver::class);

    expect($resolver->availableCompanies($identity))->toBeEmpty();

    $second = CompanyCustomer::factory()
        ->for($identity, 'customerIdentity')
        ->for(Company::factory()->state(['name' => 'Beta']), 'company')
        ->create();

    expect($resolver->availableCompanies($identity)->pluck('id')->all())
        ->toBe([$second->company_id]);

    $first = CompanyCustomer::factory()
        ->for($identity, 'customerIdentity')
        ->for(Company::factory()->state(['name' => 'Alfa']), 'company')
        ->create();

    expect($resolver->availableCompanies($identity)->pluck('id')->all())
        ->toBe([$first->company_id, $second->company_id])
        ->not->toContain($unrelatedCompany->id);

    $first->delete();

    expect($resolver->availableCompanies($identity)->pluck('id')->all())
        ->toBe([$second->company_id]);
});

test('available company projection contains no crm or administrative data', function () {
    $identity = CustomerIdentity::factory()->create();
    CompanyCustomer::factory()->for($identity, 'customerIdentity')->create();

    $company = app(CustomerCompanyResolver::class)->availableCompanies($identity)->sole();

    expect($company->getAttributes())->toHaveKeys(['id', 'name'])
        ->and($company->getAttributes())->not->toHaveKeys([
            'email',
            'company_id',
            'raw_payload',
            'access_token',
        ])
        ->and($company->relationLoaded('administrator'))->toBeFalse()
        ->and($company->relationLoaded('crmConnection'))->toBeFalse();
});
