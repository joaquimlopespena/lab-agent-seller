<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\PipedrivePipeline;
use App\Models\PipedriveStage;

/** @extends PipedriveFactory<PipedriveStage> */
class PipedriveStageFactory extends PipedriveFactory
{
    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'crm_connection_id' => fn (array $attributes) => $this->connectionFor($attributes),
            'pipedrive_pipeline_id' => fn (array $attributes) => PipedrivePipeline::factory()->create([
                'company_id' => $attributes['company_id'],
                'crm_connection_id' => $attributes['crm_connection_id'],
            ]),
            ...$this->synchronizedAttributes(),
            'name' => fake()->words(2, true),
            'display_order' => fake()->numberBetween(1, 20),
            'is_active' => true,
        ];
    }
}
