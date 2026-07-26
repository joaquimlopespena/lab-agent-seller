<?php

namespace Database\Factories;

use App\Models\AgentConversation;
use App\Models\Company;
use App\Models\CompanyCustomer;
use App\Models\ConversationStatus;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<AgentConversation> */
class AgentConversationFactory extends Factory
{
    public function definition(): array
    {
        return [
            'id' => (string) Str::uuid7(),
            'company_id' => Company::factory(),
            'company_customer_id' => fn (array $attributes) => CompanyCustomer::factory()->create([
                'company_id' => $attributes['company_id'],
            ]),
            'status_id' => ConversationStatus::factory(),
            'participant_type' => null,
            'participant_id' => null,
            'title' => fake()->sentence(4),
        ];
    }
}
