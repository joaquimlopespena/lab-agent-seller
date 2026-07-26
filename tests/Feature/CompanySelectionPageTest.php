<?php

use App\Models\Company;
use App\Models\CompanyCustomer;
use App\Models\CustomerIdentity;
use App\Models\CustomerSession;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

test('selection page lists only safe summaries for authorized companies', function () {
    $identity = CustomerIdentity::factory()->create();
    $beta = CompanyCustomer::factory()
        ->for($identity, 'customerIdentity')
        ->for(Company::factory()->state(['name' => 'Beta']), 'company')
        ->create();
    $alfa = CompanyCustomer::factory()
        ->for($identity, 'customerIdentity')
        ->for(Company::factory()->state(['name' => 'Alfa']), 'company')
        ->create();
    $unrelated = Company::factory()->create(['name' => 'Oculta']);
    $token = str()->random(64);
    CustomerSession::factory()
        ->for($identity, 'customerIdentity')
        ->create(['token_hash' => hash('sha256', $token)]);

    $this->withCookie('customer_session', $token)
        ->get(route('customer.companies.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('customer/companies/index')
            ->where('companies', [
                ['id' => $alfa->company_id, 'name' => 'Alfa'],
                ['id' => $beta->company_id, 'name' => 'Beta'],
            ])
            ->missing('identity')
            ->missing('customer')
            ->missing('administrator')
            ->missing('crm_connection')
            ->missing('raw_payload')
            ->missing('access_token'));

    expect($unrelated->name)->toBe('Oculta');
});

test('selection page redirects directly to chat for one authorized company', function () {
    $identity = CustomerIdentity::factory()->create();
    $companyCustomer = CompanyCustomer::factory()
        ->for($identity, 'customerIdentity')
        ->create();
    $token = str()->random(64);
    $session = CustomerSession::factory()
        ->for($identity, 'customerIdentity')
        ->create(['token_hash' => hash('sha256', $token)]);

    $this->withCookie('customer_session', $token)
        ->get(route('customer.companies.index'))
        ->assertRedirect(route('customer.chat.index'));

    expect($session->fresh()->active_company_id)->toBe($companyCustomer->company_id);
});

test('selection page exposes an empty safe list when there are no associations', function () {
    $identity = CustomerIdentity::factory()->create();
    $token = str()->random(64);
    CustomerSession::factory()
        ->for($identity, 'customerIdentity')
        ->create(['token_hash' => hash('sha256', $token)]);

    $this->withCookie('customer_session', $token)
        ->get(route('customer.companies.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('customer/companies/index')
            ->where('companies', [])
            ->missing('identity')
            ->missing('email'));
});
