<?php

use App\Models\AgentConversation;
use App\Models\AgentConversationMessage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

it('provides only authorized public detail props without internal payloads', function () {
    $user = User::factory()->create();
    $conversation = AgentConversation::factory()->create(['company_id' => $user->company_id]);
    AgentConversationMessage::factory()->role('assistant')->create([
        'company_id' => $user->company_id,
        'conversation_id' => $conversation,
        'content' => 'Resposta pública',
        'meta' => ['internal' => 'private prompt data'],
        'usage' => ['private' => 123],
        'tool_calls' => [['secret' => true]],
    ]);

    $response = $this->actingAs($user)->get(route('admin.conversations.show', $conversation));

    $response->assertOk()->assertInertia(fn (Assert $page) => $page
        ->component('admin/conversations/show')
        ->where('conversation.messages.0.content', 'Resposta pública')
        ->where('conversation.messages.0.role', 'assistant')
        ->has('conversation.messages.0.created_at')
        ->has('conversation.messages.0.updated_at')
        ->where('readOnly', true)
        ->missing('conversation.company_id')
        ->missing('conversation.company_customer_id')
        ->missing('conversation.messages.0.company_id')
        ->missing('conversation.messages.0.message_role_id')
        ->missing('conversation.messages.0.agent')
        ->missing('conversation.messages.0.meta')
        ->missing('conversation.messages.0.usage')
        ->missing('conversation.messages.0.tool_calls')
    );

    expect($response->getContent())
        ->not->toContain('private prompt data')
        ->not->toContain('tool_calls');
});

it('supports a conversation with no messages', function () {
    $user = User::factory()->create();
    $conversation = AgentConversation::factory()->create(['company_id' => $user->company_id]);

    $this->actingAs($user)
        ->get(route('admin.conversations.show', $conversation))
        ->assertInertia(fn (Assert $page) => $page->has('conversation.messages', 0));
});
