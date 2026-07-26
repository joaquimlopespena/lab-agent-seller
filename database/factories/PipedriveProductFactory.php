<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\PipedriveProduct;

/** @extends PipedriveFactory<PipedriveProduct> */
class PipedriveProductFactory extends PipedriveFactory
{
    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'crm_connection_id' => fn (array $attributes) => $this->connectionFor($attributes),
            ...$this->synchronizedAttributes(),
            'name' => fake()->words(2, true),
            'code' => fake()->unique()->bothify('PRD-####'),
            'unit_price' => fake()->randomFloat(2, 1, 10000),
            'currency' => 'BRL',
            'is_active' => true,
        ];
    }
}
