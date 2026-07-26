<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\CrmConnection;
use App\Models\CrmConnectionStatus;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<CrmConnection> */
class CrmConnectionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'status_id' => CrmConnectionStatus::factory(),
            'provider' => 'pipedrive',
            'external_company_id' => (string) fake()->unique()->numberBetween(1, 1_000_000),
            'access_token' => fake()->sha256(),
            'refresh_token' => fake()->sha256(),
            'token_expires_at' => now()->addHour(),
            'authorized_at' => now(),
            'last_error' => null,
        ];
    }
}
