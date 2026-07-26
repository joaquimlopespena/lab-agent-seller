<?php

use App\Ai\Contracts\GeneratesAgentReplies;
use App\Conversations\OpenCustomerConversation;
use App\Models\AgentConversationMessage;
use App\Models\CompanyCustomer;
use Database\Seeders\LookupSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\FakeAgentReplyGenerator;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(LookupSeeder::class);
    $this->withCredentials();
});

it('requires an authenticated customer session with a current tenant relationship', function () {
    $this->get(route('customer.chat.index'))
        ->assertRedirect(route('customer.access.request'));

    [$companyCustomer, $session, $token] = activeCustomerSession();
    $companyCustomer->delete();

    $this->withCookie('customer_session', $token)
        ->get(route('customer.chat.index'))
        ->assertRedirect(route('customer.companies.index'));

    expect($session->refresh()->active_company_id)->toBeNull();
});

it('returns ordered sanitized history and sends through the active tenant endpoint', function () {
    [$companyCustomer, , $token] = activeCustomerSession();
    $fake = new FakeAgentReplyGenerator('Tudo certo');
    $this->app->instance(GeneratesAgentReplies::class, $fake);

    $this->withCookie('customer_session', $token)
        ->postJson(route('customer.chat.messages.store'), ['content' => 'Olá'])
        ->assertCreated()
        ->assertJsonMissingPath('messages.0.company_id')
        ->assertJsonMissingPath('messages.0.message_role_id')
        ->assertJsonMissingPath('messages.0.meta');

    $this->withCookie('customer_session', $token)
        ->get(route('customer.chat.index'))
        ->assertOk();

    expect(AgentConversationMessage::query()->orderBy('created_at')->orderBy('id')->pluck('role')->all())
        ->toBe(['user', 'assistant'])
        ->and(AgentConversationMessage::query()->pluck('company_id')->unique()->all())
        ->toBe([$companyCustomer->company_id]);
});

it('returns a recoverable failure response from the endpoint', function () {
    [, , $token] = activeCustomerSession();
    $this->app->instance(
        GeneratesAgentReplies::class,
        new FakeAgentReplyGenerator(new RuntimeException('provider unavailable')),
    );

    $this->withCookie('customer_session', $token)
        ->postJson(route('customer.chat.messages.store'), ['content' => 'Olá'])
        ->assertServiceUnavailable()
        ->assertJson([
            'status' => 'provider_failed',
            'retryable' => true,
        ]);
});

it('refuses retrying a message from another tenant', function () {
    [, , $token] = activeCustomerSession();
    $foreignCustomer = CompanyCustomer::factory()->create();
    $conversation = app(OpenCustomerConversation::class)->handle($foreignCustomer);
    $foreignMessage = AgentConversationMessage::factory()->create([
        'company_id' => $foreignCustomer->company_id,
        'conversation_id' => $conversation->id,
    ]);

    $this->withCookie('customer_session', $token)
        ->postJson(route('customer.chat.messages.retry', ['message' => $foreignMessage->id]))
        ->assertNotFound();
});
