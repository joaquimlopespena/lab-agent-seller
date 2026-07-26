<?php

use App\Conversations\OpenCustomerConversation;
use App\Models\AgentConversation;
use App\Models\AgentConversationMessage;
use Database\Seeders\LookupSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

beforeEach(fn () => $this->seed(LookupSeeder::class));

it('loads the persistent chat page with sanitized ordered initial props', function () {
    [$companyCustomer, , $token] = activeCustomerSession();
    $conversation = app(OpenCustomerConversation::class)->handle($companyCustomer);
    $assistant = AgentConversationMessage::factory()->role('assistant')->create([
        'company_id' => $companyCustomer->company_id,
        'conversation_id' => $conversation->id,
        'content' => 'Resposta visível',
    ]);

    $this->withCookie('customer_session', $token)
        ->get(route('customer.chat.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('customer/chat/index')
            ->where('conversation.id', $conversation->id)
            ->where('conversation.title', $conversation->title)
            ->has('messages', 1)
            ->where('messages.0.id', $assistant->id)
            ->where('messages.0.role', 'assistant')
            ->where('messages.0.content', 'Resposta visível')
            ->missing('messages.0.company_id')
            ->missing('messages.0.message_role_id')
            ->missing('messages.0.meta'));
});

it('does not honor browser conversation identifiers when loading the active chat', function () {
    [$companyCustomer, , $token] = activeCustomerSession();
    $activeConversation = app(OpenCustomerConversation::class)->handle($companyCustomer);
    $foreignConversation = AgentConversation::factory()->create();

    $this->withCookie('customer_session', $token)
        ->get(route('customer.chat.index', ['conversation' => $foreignConversation->id]))
        ->assertInertia(fn (Assert $page) => $page
            ->where('conversation.id', $activeConversation->id)
            ->where('messages', []));
});
