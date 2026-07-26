<?php

use App\Models\AgentConversation;
use App\Models\AgentConversationMessage;
use App\Models\Company;
use App\Models\CompanyCustomer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

it('shows an ordered read-only conversation history', function () {
    $company = Company::factory()->create();
    $user = User::factory()->for($company)->create();
    $customer = CompanyCustomer::factory()->create(['company_id' => $company]);
    $customer->pipedrivePerson->update(['name' => 'Cliente Histórico']);
    $conversation = AgentConversation::factory()->create([
        'company_id' => $company,
        'company_customer_id' => $customer,
    ]);
    $later = AgentConversationMessage::factory()->role('assistant')->create([
        'company_id' => $company,
        'conversation_id' => $conversation,
        'content' => 'Segunda mensagem',
        'created_at' => now()->addMinute(),
    ]);
    $earlier = AgentConversationMessage::factory()->role('user')->create([
        'company_id' => $company,
        'conversation_id' => $conversation,
        'content' => 'Primeira mensagem',
        'created_at' => now(),
    ]);

    $this->actingAs($user)
        ->get(route('admin.conversations.show', $conversation))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/conversations/show')
            ->where('conversation.id', $conversation->id)
            ->where('conversation.customer.name', 'Cliente Histórico')
            ->has('conversation.customer.email')
            ->has('conversation.status')
            ->has('conversation.created_at')
            ->has('conversation.updated_at')
            ->has('conversation.messages', 2)
            ->where('conversation.messages.0.id', $earlier->id)
            ->where('conversation.messages.0.role', 'user')
            ->where('conversation.messages.0.content', 'Primeira mensagem')
            ->where('conversation.messages.1.id', $later->id)
            ->where('conversation.messages.1.role', 'assistant')
            ->where('readOnly', true)
        );
});

it('refuses a conversation UUID from another tenant without exposing content', function () {
    $company = Company::factory()->create();
    $otherCompany = Company::factory()->create();
    $user = User::factory()->for($company)->create();
    $conversation = AgentConversation::factory()->create(['company_id' => $otherCompany]);
    AgentConversationMessage::factory()->create([
        'company_id' => $otherCompany,
        'conversation_id' => $conversation,
        'content' => 'segredo cross tenant',
    ]);

    $response = $this->actingAs($user)
        ->get(route('admin.conversations.show', $conversation))
        ->assertNotFound();

    expect($response->getContent())->not->toContain('segredo cross tenant');
});

it('exposes no administrative mutation routes', function () {
    $user = User::factory()->create();
    $conversation = AgentConversation::factory()->create(['company_id' => $user->company_id]);

    $this->actingAs($user)->post(route('admin.conversations.show', $conversation))->assertMethodNotAllowed();
    $this->actingAs($user)->put(route('admin.conversations.show', $conversation))->assertMethodNotAllowed();
    $this->actingAs($user)->delete(route('admin.conversations.show', $conversation))->assertMethodNotAllowed();
});
