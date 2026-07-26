<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\PipedrivePerson;
use App\Models\PipedrivePersonEmail;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<PipedrivePersonEmail> */
class PipedrivePersonEmailFactory extends Factory
{
    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'pipedrive_person_id' => fn (array $attributes) => PipedrivePerson::factory()->create([
                'company_id' => $attributes['company_id'],
            ]),
            'email' => fake()->unique()->safeEmail(),
            'label' => 'work',
            'is_primary' => false,
        ];
    }

    public function primary(): static
    {
        return $this->state(['is_primary' => true]);
    }
}
