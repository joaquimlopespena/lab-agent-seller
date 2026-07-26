<?php

use App\Ai\Contracts\GeneratesAgentReplies;
use App\Models\AgentConversation;
use App\Models\AgentConversationMessage;
use App\Models\CompanyCustomer;
use Database\Seeders\LookupSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\FakeAgentReplyGenerator;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(LookupSeeder::class);
    $this->withCredentials();
    $this->app->instance(GeneratesAgentReplies::class, new FakeAgentReplyGenerator);
});

it('persists a tenant-consistent user message with its canonical role', function () {
    [$companyCustomer, , $token] = activeCustomerSession();

    $this->withCookie('customer_session', $token)
        ->postJson(route('customer.chat.messages.store'), ['content' => 'Preciso de ajuda'])
        ->assertCreated();

    $message = AgentConversationMessage::query()->where('role', 'user')->sole();

    expect($message->company_id)->toBe($companyCustomer->company_id)
        ->and($message->conversation->company_customer_id)->toBe($companyCustomer->id)
        ->and($message->messageRole->slug)->toBe('user')
        ->and($message->content)->toBe('Preciso de ajuda');
});

it('rejects invalid content without persisting anything', function (mixed $content) {
    [, , $token] = activeCustomerSession();

    $this->withCookie('customer_session', $token)
        ->postJson(route('customer.chat.messages.store'), ['content' => $content])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('content');

    expect(AgentConversationMessage::query()->count())->toBe(0);
})->with(['empty' => '', 'whitespace' => '   ', 'non-string' => [['message']]]);

it('ignores browser supplied conversation and customer ids across tenants', function () {
    [$activeCustomer, , $token] = activeCustomerSession();
    $foreignCustomer = CompanyCustomer::factory()->create();
    $foreignConversation = AgentConversation::factory()->create([
        'company_id' => $foreignCustomer->company_id,
        'company_customer_id' => $foreignCustomer->id,
    ]);

    $this->withCookie('customer_session', $token)
        ->postJson(route('customer.chat.messages.store'), [
            'content' => 'Mensagem segura',
            'conversation_id' => $foreignConversation->id,
            'company_customer_id' => $foreignCustomer->id,
        ])
        ->assertCreated();

    $message = AgentConversationMessage::query()->where('role', 'user')->sole();

    expect($message->company_id)->toBe($activeCustomer->company_id)
        ->and($message->conversation_id)->not->toBe($foreignConversation->id);
});
