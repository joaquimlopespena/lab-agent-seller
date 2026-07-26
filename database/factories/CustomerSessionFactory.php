<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\CustomerIdentity;
use App\Models\CustomerSession;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<CustomerSession> */
class CustomerSessionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'id' => (string) Str::uuid7(),
            'customer_identity_id' => CustomerIdentity::factory(),
            'active_company_id' => null,
            'token_hash' => fake()->unique()->sha256(),
            'ip_address' => fake()->ipv4(),
            'user_agent' => fake()->userAgent(),
            'expires_at' => now()->addDay(),
            'last_activity_at' => now(),
            'revoked_at' => null,
        ];
    }

    public function forActiveCompany(?Company $company = null): static
    {
        return $this->state(['active_company_id' => $company ?? Company::factory()]);
    }

    public function revoked(): static
    {
        return $this->state(['revoked_at' => now()]);
    }
}
