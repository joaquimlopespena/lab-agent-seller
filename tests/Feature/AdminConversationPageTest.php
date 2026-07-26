<?php

use App\Models\AgentConversation;
use App\Models\Company;
use App\Models\CompanyCustomer;
use App\Models\CustomerIdentity;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

it('provides tenant-aware paginated props for the conversation page', function () {
    $identity = CustomerIdentity::factory()->create(['email' => 'same@example.test']);
    $company = Company::factory()->create();
    $otherCompany = Company::factory()->create();
    $user = User::factory()->for($company)->create();
    $owned = CompanyCustomer::factory()->create([
        'company_id' => $company,
        'customer_identity_id' => $identity,
    ]);
    $foreign = CompanyCustomer::factory()->create([
        'company_id' => $otherCompany,
        'customer_identity_id' => $identity,
    ]);
    AgentConversation::factory()->create(['company_id' => $company, 'company_customer_id' => $owned]);
    AgentConversation::factory()->create(['company_id' => $otherCompany, 'company_customer_id' => $foreign]);

    $response = $this->actingAs($user)->get(route('admin.conversations.index'));

    $response->assertOk()->assertInertia(fn (Assert $page) => $page
        ->component('admin/conversations/index')
        ->where('conversations.total', 1)
        ->has('conversations.data', 1)
        ->where('conversations.data.0.customer.email', 'same@example.test')
        ->where('conversations.current_page', 1)
        ->where('conversations.per_page', 15)
        ->where('readOnly', true)
        ->missing('conversations.data.0.company_id')
        ->missing('conversations.data.0.company_customer_id')
        ->missing('conversations.data.0.participant_type')
    );
});

it('requires an authenticated verified administrator for conversation pages', function () {
    $this->get(route('admin.conversations.index'))->assertRedirect(route('login'));
});
