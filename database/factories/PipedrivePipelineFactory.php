<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\PipedrivePipeline;

/** @extends PipedriveFactory<PipedrivePipeline> */
class PipedrivePipelineFactory extends PipedriveFactory
{
    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'crm_connection_id' => fn (array $attributes) => $this->connectionFor($attributes),
            ...$this->synchronizedAttributes(),
            'name' => fake()->words(2, true),
            'display_order' => fake()->numberBetween(1, 20),
            'is_active' => true,
        ];
    }
}
