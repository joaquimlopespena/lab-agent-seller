<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;

uses(RefreshDatabase::class);

test('laravel ai conversation contract is extended with tenant constraints', function () {
    expect(column('agent_conversations', 'id')['type'])->toContain('character varying(36)')
        ->and(Schema::hasColumns('agent_conversations', [
            'company_id', 'company_customer_id', 'status_id', 'participant_type', 'participant_id', 'title',
        ]))->toBeTrue()
        ->and(uniqueIndexColumns('agent_conversations'))->toContain(['company_id', 'company_customer_id'])
        ->and(column('agent_conversation_messages', 'id')['type'])->toContain('character varying(36)')
        ->and(Schema::hasColumns('agent_conversation_messages', [
            'conversation_id', 'company_id', 'message_role_id', 'participant_type', 'participant_id',
            'agent', 'role', 'content', 'attachments', 'tool_calls', 'tool_results', 'usage', 'meta', 'approval_state',
        ]))->toBeTrue()
        ->and(indexColumns('agent_conversation_messages'))->toContain(
            ['conversation_id', 'participant_type', 'participant_id', 'updated_at'],
            ['participant_type', 'participant_id'],
            ['company_id', 'conversation_id'],
        );
});
