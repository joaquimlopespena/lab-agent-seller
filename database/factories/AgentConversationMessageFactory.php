<?php

namespace Database\Factories;

use App\Models\AgentConversation;
use App\Models\AgentConversationMessage;
use App\Models\Company;
use App\Models\MessageRole;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<AgentConversationMessage> */
class AgentConversationMessageFactory extends Factory
{
    public function definition(): array
    {
        return [
            'id' => (string) Str::uuid7(),
            'company_id' => Company::factory(),
            'conversation_id' => fn (array $attributes) => AgentConversation::factory()->create([
                'company_id' => $attributes['company_id'],
            ]),
            'message_role_id' => fn () => MessageRole::query()->firstOrCreate(
                ['slug' => 'user'],
                ['name' => 'User', 'is_active' => true],
            ),
            'participant_type' => null,
            'participant_id' => null,
            'agent' => 'sales-assistant',
            'role' => 'user',
            'content' => fake()->sentence(),
            'attachments' => [],
            'tool_calls' => [],
            'tool_results' => [],
            'usage' => [],
            'meta' => [],
            'approval_state' => null,
        ];
    }

    public function role(string $role): static
    {
        return $this->state([
            'message_role_id' => fn () => MessageRole::query()->firstOrCreate(
                ['slug' => $role],
                ['name' => ucfirst($role), 'is_active' => true],
            ),
            'role' => $role,
        ]);
    }
}
