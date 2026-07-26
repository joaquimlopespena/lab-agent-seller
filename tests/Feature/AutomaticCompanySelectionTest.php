<?php

use App\CustomerAuth\ConsumeCustomerMagicLink;
use App\Models\CompanyCustomer;
use App\Models\CustomerIdentity;
use App\Models\CustomerMagicLink;
use App\Models\CustomerSession;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;

uses(RefreshDatabase::class);

test('consuming a link automatically selects the only authorized company', function () {
    $identity = CustomerIdentity::factory()->create();
    $companyCustomer = CompanyCustomer::factory()
        ->for($identity, 'customerIdentity')
        ->create();
    $token = str()->random(64);
    CustomerMagicLink::factory()
        ->for($identity, 'customerIdentity')
        ->create(['token_hash' => hash('sha256', $token)]);

    $consumption = app(ConsumeCustomerMagicLink::class)->handle(
        $token,
        Request::create('/customer/access/'.$token),
    );

    expect($consumption->successful())->toBeTrue()
        ->and($consumption->session?->active_company_id)->toBe($companyCustomer->company_id);
});

test('opening selection automatically continues when a current single association remains', function () {
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

test('customer session never authenticates administrative or synchronized data routes', function () {
    $identity = CustomerIdentity::factory()->create();
    $companyCustomer = CompanyCustomer::factory()
        ->for($identity, 'customerIdentity')
        ->create();
    $token = str()->random(64);
    CustomerSession::factory()
        ->for($identity, 'customerIdentity')
        ->forActiveCompany($companyCustomer->company)
        ->create(['token_hash' => hash('sha256', $token)]);

    $this->withCookie('customer_session', $token)
        ->get(route('dashboard'))
        ->assertRedirect(route('login'));

    $this->withCookie('customer_session', $token)
        ->get(route('admin.synced-data.index'))
        ->assertRedirect(route('login'));
});
