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

it('swaps the reply adapter without changing customer authentication tenancy or persistence', function () {
    [$companyCustomer, , $token] = activeCustomerSession();
    $fake = new FakeAgentReplyGenerator('Resposta substituível');
    $this->app->instance(GeneratesAgentReplies::class, $fake);

    $this->withCookie('customer_session', $token)
        ->postJson(route('customer.chat.messages.store'), ['content' => 'Olá'])
        ->assertCreated()
        ->assertJsonPath('messages.1.content', 'Resposta substituível');

    expect($fake->requests)->toHaveCount(1)
        ->and(AgentConversationMessage::query()->pluck('company_id')->unique()->all())
        ->toBe([$companyCustomer->company_id])
        ->and(AgentConversationMessage::query()->pluck('role')->all())
        ->toBe(['user', 'assistant']);
});
