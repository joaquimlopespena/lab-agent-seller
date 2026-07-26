<?php

use App\Models\Company;
use App\Models\CompanyCustomer;
use App\Models\CustomerIdentity;
use App\Models\CustomerSession;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function customerSelectionContext(): array
{
    $identity = CustomerIdentity::factory()->create();
    $first = CompanyCustomer::factory()->for($identity, 'customerIdentity')->create();
    $second = CompanyCustomer::factory()->for($identity, 'customerIdentity')->create();
    $token = str()->random(64);
    $session = CustomerSession::factory()
        ->for($identity, 'customerIdentity')
        ->create(['token_hash' => hash('sha256', $token)]);

    return [$identity, $first, $second, $session, $token];
}

test('an authorized company can be selected and changed in a controlled request', function () {
    [, $first, $second, $session, $token] = customerSelectionContext();

    $this->withCookie('customer_session', $token)
        ->post(route('customer.companies.store'), ['company_id' => $first->company_id])
        ->assertRedirect(route('customer.chat.index'));

    expect($session->fresh()->active_company_id)->toBe($first->company_id);

    $this->withCookie('customer_session', $token)
        ->post(route('customer.companies.store'), ['company_id' => $second->company_id])
        ->assertRedirect(route('customer.chat.index'));

    expect($session->fresh()->active_company_id)->toBe($second->company_id);
});

test('an unassociated manipulated company id is refused without changing tenant', function () {
    [, $first, , $session, $token] = customerSelectionContext();
    $session->update(['active_company_id' => $first->company_id]);
    $unassociatedCompany = Company::factory()->create();

    $this->from(route('customer.companies.index'))
        ->withCookie('customer_session', $token)
        ->post(route('customer.companies.store'), ['company_id' => $unassociatedCompany->id])
        ->assertRedirect(route('customer.companies.index'))
        ->assertSessionHasErrors('company_id');

    expect($session->fresh()->active_company_id)->toBe($first->company_id);
});

test('a company unlinked before submission is refused without changing tenant', function () {
    [, $first, $removed, $session, $token] = customerSelectionContext();
    $session->update(['active_company_id' => $first->company_id]);
    $removedCompanyId = $removed->company_id;
    $removed->delete();

    $this->from(route('customer.companies.index'))
        ->withCookie('customer_session', $token)
        ->post(route('customer.companies.store'), ['company_id' => $removedCompanyId])
        ->assertRedirect(route('customer.companies.index'))
        ->assertSessionHasErrors('company_id');

    expect($session->fresh()->active_company_id)->toBe($first->company_id);
});

test('conversation cannot be opened before a company is selected', function () {
    [, , , , $token] = customerSelectionContext();

    $this->withCookie('customer_session', $token)
        ->get(route('customer.chat.index'))
        ->assertRedirect(route('customer.companies.index'));
});
