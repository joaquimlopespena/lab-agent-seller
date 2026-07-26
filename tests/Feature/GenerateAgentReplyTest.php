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

it('provides ordered history and persists the assistant reply after the customer message', function () {
    [, , $token] = activeCustomerSession();
    $fake = new FakeAgentReplyGenerator('Primeira resposta', 'Segunda resposta');
    $this->app->instance(GeneratesAgentReplies::class, $fake);

    $this->withCookie('customer_session', $token)
        ->postJson(route('customer.chat.messages.store'), ['content' => 'Primeira pergunta'])
        ->assertCreated();
    $this->withCookie('customer_session', $token)
        ->postJson(route('customer.chat.messages.store'), ['content' => 'Segunda pergunta'])
        ->assertCreated()
        ->assertJsonPath('messages.1.role', 'assistant')
        ->assertJsonPath('messages.1.content', 'Segunda resposta');

    expect($fake->requests)->toHaveCount(2)
        ->and($fake->requests[0]->history)->toBe([])
        ->and($fake->requests[0]->message)->toBe('Primeira pergunta')
        ->and($fake->requests[1]->history)->toBe([
            ['role' => 'user', 'content' => 'Primeira pergunta'],
            ['role' => 'assistant', 'content' => 'Primeira resposta'],
        ])
        ->and(AgentConversationMessage::query()
            ->orderBy('created_at')
            ->orderBy('id')
            ->pluck('role')
            ->all())
        ->toBe(['user', 'assistant', 'user', 'assistant'])
        ->and(AgentConversationMessage::query()->where('role', 'assistant')->get()
            ->every(fn ($message) => $message->messageRole->slug === 'assistant'))
        ->toBeTrue();
});
