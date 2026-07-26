<?php

namespace Database\Factories;

use App\Models\CrmConnection;
use Illuminate\Database\Eloquent\Factories\Factory;

abstract class PipedriveFactory extends Factory
{
    protected function connectionFor(array $attributes): CrmConnection
    {
        return CrmConnection::factory()->create(['company_id' => $attributes['company_id']]);
    }

    /**
     * @return array<string, mixed>
     */
    protected function synchronizedAttributes(): array
    {
        return [
            'pipedrive_id' => fake()->unique()->numberBetween(1, 2_000_000_000),
            'raw_payload' => ['source' => 'factory'],
            'synced_at' => now(),
        ];
    }
}
