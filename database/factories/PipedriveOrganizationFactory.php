<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\PipedriveOrganization;
use App\Models\PipedriveUser;

/** @extends PipedriveFactory<PipedriveOrganization> */
class PipedriveOrganizationFactory extends PipedriveFactory
{
    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'crm_connection_id' => fn (array $attributes) => $this->connectionFor($attributes),
            'owner_pipedrive_user_id' => null,
            ...$this->synchronizedAttributes(),
            'name' => fake()->company(),
            'address' => fake()->address(),
            'is_active' => true,
        ];
    }

    public function withOwner(): static
    {
        return $this->afterMaking(function (PipedriveOrganization $organization): void {
            $organization->owner()->associate(PipedriveUser::factory()->create([
                'company_id' => $organization->company_id,
                'crm_connection_id' => $organization->crm_connection_id,
            ]));
        });
    }

    public function withoutOwner(): static
    {
        return $this->state(['owner_pipedrive_user_id' => null]);
    }
}
