<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\PipedriveUser;

/** @extends PipedriveFactory<PipedriveUser> */
class PipedriveUserFactory extends PipedriveFactory
{
    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'crm_connection_id' => fn (array $attributes) => $this->connectionFor($attributes),
            ...$this->synchronizedAttributes(),
            'name' => fake()->name(),
            'email' => fake()->safeEmail(),
            'is_active' => true,
        ];
    }
}
