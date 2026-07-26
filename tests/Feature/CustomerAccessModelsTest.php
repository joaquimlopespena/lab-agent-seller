<?php

use App\Models\Company;
use App\Models\CompanyCustomer;
use App\Models\CustomerIdentity;
use App\Models\CustomerMagicLink;
use App\Models\CustomerSession;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('customer identity links companies access records and hides hashes', function () {
    $identity = CustomerIdentity::factory()->create();
    $firstLink = CompanyCustomer::factory()->create(['customer_identity_id' => $identity]);
    $secondLink = CompanyCustomer::factory()->create(['customer_identity_id' => $identity]);
    $magicLink = CustomerMagicLink::factory()->used()->create(['customer_identity_id' => $identity]);
    $session = CustomerSession::factory()->forActiveCompany($firstLink->company)->revoked()->create([
        'customer_identity_id' => $identity,
    ]);

    expect($identity->companies)->toHaveCount(2)
        ->and($identity->companyCustomers)->toHaveCount(2)
        ->and($firstLink->company->customerIdentities->contains($identity))->toBeTrue()
        ->and($magicLink->expires_at)->toBeInstanceOf(DateTimeInterface::class)
        ->and($magicLink->used_at)->toBeInstanceOf(DateTimeInterface::class)
        ->and($session->activeCompany)->toBeInstanceOf(Company::class)
        ->and($session->last_activity_at)->toBeInstanceOf(DateTimeInterface::class)
        ->and($session->revoked_at)->toBeInstanceOf(DateTimeInterface::class)
        ->and($magicLink->toArray())->not->toHaveKey('token_hash')
        ->and($session->toArray())->not->toHaveKey('token_hash');
});
