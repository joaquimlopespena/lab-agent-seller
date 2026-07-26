<?php

namespace Database\Factories;

use App\Models\CustomerIdentity;
use App\Models\CustomerMagicLink;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<CustomerMagicLink> */
class CustomerMagicLinkFactory extends Factory
{
    public function definition(): array
    {
        return [
            'customer_identity_id' => CustomerIdentity::factory(),
            'token_hash' => fake()->unique()->sha256(),
            'expires_at' => now()->addMinutes(15),
            'used_at' => null,
        ];
    }

    public function used(): static
    {
        return $this->state(['used_at' => now()]);
    }
}
