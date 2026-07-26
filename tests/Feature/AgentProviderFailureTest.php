<?php

use App\Ai\Contracts\GeneratesAgentReplies;
use App\Models\AgentConversationMessage;
use Database\Seeders\LookupSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\FakeAgentReplyGenerator;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(LookupSeeder::class);
    $this->withCredentials();
});

it('preserves the customer message without fabricating an assistant reply on provider failure', function () {
    [$companyCustomer, , $token] = activeCustomerSession();
    $fake = new FakeAgentReplyGenerator(new RuntimeException('timeout'));
    $this->app->instance(GeneratesAgentReplies::class, $fake);

    $response = $this->withCookie('customer_session', $token)
        ->postJson(route('customer.chat.messages.store'), ['content' => 'Mensagem preservada']);

    $response->assertServiceUnavailable()
        ->assertJsonPath('retryable', true)
        ->assertJsonPath('user_message.content', 'Mensagem preservada');

    expect(AgentConversationMessage::query()->count())->toBe(1)
        ->and(AgentConversationMessage::query()->sole()->role)->toBe('user')
        ->and(AgentConversationMessage::query()->sole()->company_id)->toBe($companyCustomer->company_id);
});

it('retries the original message in the same tenant without duplicating it', function () {
    [$companyCustomer, , $token] = activeCustomerSession();
    $fake = new FakeAgentReplyGenerator(new RuntimeException('timeout'), 'Resposta recuperada');
    $this->app->instance(GeneratesAgentReplies::class, $fake);

    $failure = $this->withCookie('customer_session', $token)
        ->postJson(route('customer.chat.messages.store'), ['content' => 'Tente esta']);
    $messageId = $failure->json('user_message.id');

    $this->withCookie('customer_session', $token)
        ->postJson(route('customer.chat.messages.retry', ['message' => $messageId]))
        ->assertOk()
        ->assertJsonPath('messages.0.content', 'Resposta recuperada');

    expect(AgentConversationMessage::query()->where('role', 'user')->count())->toBe(1)
        ->and(AgentConversationMessage::query()->where('role', 'assistant')->count())->toBe(1)
        ->and(AgentConversationMessage::query()->pluck('company_id')->unique()->all())
        ->toBe([$companyCustomer->company_id])
        ->and($fake->requests[1]->message)->toBe('Tente esta');
});
