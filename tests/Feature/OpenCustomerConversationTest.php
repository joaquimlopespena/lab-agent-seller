<?php

use App\Conversations\OpenCustomerConversation;
use App\Models\AgentConversation;
use App\Models\CompanyCustomer;
use App\Models\CustomerIdentity;
use Database\Seeders\LookupSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

beforeEach(fn () => $this->seed(LookupSeeder::class));

it('creates and resumes one active uuid conversation for a company customer', function () {
    $companyCustomer = CompanyCustomer::factory()->create();
    $open = app(OpenCustomerConversation::class);

    $first = $open->handle($companyCustomer);
    $resumed = $open->handle($companyCustomer);

    expect($resumed->is($first))->toBeTrue()
        ->and(Str::isUuid($first->id))->toBeTrue()
        ->and($first->company_id)->toBe($companyCustomer->company_id)
        ->and($first->status->slug)->toBe('active')
        ->and($first->title)->not->toBeEmpty()
        ->and(AgentConversation::query()->count())->toBe(1);
});

it('preserves the same conversation across a new customer session and separates tenants', function () {
    $identity = CustomerIdentity::factory()->create();
    $firstCustomer = CompanyCustomer::factory()->for($identity, 'customerIdentity')->create();
    $secondCustomer = CompanyCustomer::factory()->for($identity, 'customerIdentity')->create();
    $open = app(OpenCustomerConversation::class);

    [, , $firstToken] = activeCustomerSession($firstCustomer);
    $this->withCookie('customer_session', $firstToken)
        ->get(route('customer.chat.index'))
        ->assertOk();
    $firstConversation = AgentConversation::query()->sole();

    [, , $renewedToken] = activeCustomerSession($firstCustomer);
    $this->withCookie('customer_session', $renewedToken)
        ->get(route('customer.chat.index'))
        ->assertOk();

    $secondConversation = $open->handle($secondCustomer);

    expect(AgentConversation::query()->where('company_id', $firstCustomer->company_id)->sole()->id)
        ->toBe($firstConversation->id)
        ->and($secondConversation->id)->not->toBe($firstConversation->id)
        ->and(AgentConversation::query()->count())->toBe(2);
});
