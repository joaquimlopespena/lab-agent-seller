<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\PipedriveCustomField;

/** @extends PipedriveFactory<PipedriveCustomField> */
class PipedriveCustomFieldFactory extends PipedriveFactory
{
    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'crm_connection_id' => fn (array $attributes) => $this->connectionFor($attributes),
            ...$this->synchronizedAttributes(),
            'pipedrive_key' => fake()->unique()->bothify('field_####????'),
            'entity_type' => fake()->randomElement(['deal', 'person', 'organization']),
            'field_type' => fake()->randomElement(['varchar', 'double', 'enum']),
            'name' => fake()->words(2, true),
            'is_active' => true,
            'options_payload' => [['id' => 1, 'label' => 'Option']],
        ];
    }
}
