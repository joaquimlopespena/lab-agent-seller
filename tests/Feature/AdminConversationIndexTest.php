<?php

use App\Models\AgentConversation;
use App\Models\Company;
use App\Models\CompanyCustomer;
use App\Models\CustomerIdentity;
use App\Models\PipedrivePerson;
use App\Models\PipedrivePersonEmail;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

it('lists and searches only conversations from the authenticated company', function () {
    $company = Company::factory()->create();
    $otherCompany = Company::factory()->create();
    $user = User::factory()->for($company)->create();
    $owned = CompanyCustomer::factory()->create(['company_id' => $company]);
    $foreign = CompanyCustomer::factory()->create(['company_id' => $otherCompany]);
    $owned->pipedrivePerson->update(['name' => 'Cliente Visível']);
    $foreign->pipedrivePerson->update(['name' => 'Segredo Externo']);
    $ownedConversation = AgentConversation::factory()->create([
        'company_id' => $company,
        'company_customer_id' => $owned,
    ]);
    AgentConversation::factory()->create([
        'company_id' => $otherCompany,
        'company_customer_id' => $foreign,
    ]);

    $response = $this->actingAs($user)->get(route('admin.conversations.index', [
        'search' => 'Cliente',
        'company_id' => $otherCompany->id,
    ]));

    $response->assertOk()->assertInertia(fn (Assert $page) => $page
        ->component('admin/conversations/index')
        ->where('filters.search', 'Cliente')
        ->where('conversations.total', 1)
        ->where('conversations.data.0.id', $ownedConversation->id)
        ->where('conversations.data.0.customer.name', 'Cliente Visível')
        ->has('conversations.data.0.customer.email')
        ->has('conversations.data.0.status.slug')
        ->has('conversations.data.0.created_at')
        ->has('conversations.data.0.updated_at')
        ->where('readOnly', true)
        ->missing('company_id')
    );

    expect($response->getContent())->not->toContain('Segredo Externo');
});

it('keeps shared email identities separate between companies', function () {
    $identity = CustomerIdentity::factory()->create(['email' => 'shared@example.test']);
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
    $owned->pipedrivePerson->update(['name' => 'Pessoa do Tenant A']);
    $foreign->pipedrivePerson->update(['name' => 'Pessoa do Tenant B']);
    AgentConversation::factory()->create(['company_id' => $company, 'company_customer_id' => $owned]);
    AgentConversation::factory()->create(['company_id' => $otherCompany, 'company_customer_id' => $foreign]);

    $response = $this->actingAs($user)->get(route('admin.conversations.index', ['search' => 'shared']));

    $response->assertInertia(fn (Assert $page) => $page
        ->where('conversations.total', 1)
        ->where('conversations.data.0.customer.name', 'Pessoa do Tenant A')
        ->where('conversations.data.0.customer.email', 'shared@example.test')
    );
    expect($response->getContent())->not->toContain('Pessoa do Tenant B');
});

it('paginates tenant conversations and returns an empty state', function () {
    $company = Company::factory()->create();
    $user = User::factory()->for($company)->create();

    $this->actingAs($user)->get(route('admin.conversations.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('conversations.total', 0)
            ->has('conversations.data', 0)
        );

    $connection = activePipedriveConnection($company);

    foreach (range(1, 16) as $index) {
        $identity = CustomerIdentity::factory()->create();
        $person = PipedrivePerson::factory()->create([
            'company_id' => $company,
            'crm_connection_id' => $connection,
        ]);
        $email = PipedrivePersonEmail::factory()->create([
            'company_id' => $company,
            'pipedrive_person_id' => $person,
            'email' => $identity->email,
        ]);
        $customer = CompanyCustomer::factory()->create([
            'company_id' => $company,
            'customer_identity_id' => $identity,
            'pipedrive_person_id' => $person,
            'pipedrive_person_email_id' => $email,
        ]);
        AgentConversation::factory()->create([
            'company_id' => $company,
            'company_customer_id' => $customer,
            'title' => "Conversa {$index}",
        ]);
    }

    $this->actingAs($user)->get(route('admin.conversations.index', ['page' => 2]))
        ->assertInertia(fn (Assert $page) => $page
            ->where('conversations.total', 16)
            ->where('conversations.current_page', 2)
            ->where('conversations.last_page', 2)
            ->has('conversations.data', 1)
        );
});

it('rejects an excessive conversation search', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('admin.conversations.index', ['search' => str_repeat('a', 101)]))
        ->assertUnprocessable();
});
