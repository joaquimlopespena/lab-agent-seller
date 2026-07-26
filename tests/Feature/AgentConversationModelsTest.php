<?php

use App\Models\AgentConversation;
use App\Models\AgentConversationMessage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Laravel\Ai\Models\Conversation;
use Laravel\Ai\Models\ConversationMessage;

uses(RefreshDatabase::class);

test('agent conversation models extend the sdk contract with domain relations and uuid keys', function () {
    $conversation = AgentConversation::factory()->create();
    $message = AgentConversationMessage::factory()->role('assistant')->create([
        'company_id' => $conversation->company_id,
        'conversation_id' => $conversation,
    ]);

    expect($conversation)->toBeInstanceOf(Conversation::class)
        ->and($message)->toBeInstanceOf(ConversationMessage::class)
        ->and(Str::isUuid($conversation->id))->toBeTrue()
        ->and(Str::isUuid($message->id))->toBeTrue()
        ->and($conversation->companyCustomer->company_id)->toBe($conversation->company_id)
        ->and($conversation->messages->first()->is($message))->toBeTrue()
        ->and($message->messageRole->slug)->toBe($message->role)
        ->and($message->attachments)->toBeArray();
});

test('compatible role cannot diverge from its canonical role', function () {
    AgentConversationMessage::factory()->create(['role' => 'assistant']);
})->throws(DomainException::class);
