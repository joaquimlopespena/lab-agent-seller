<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\PipedriveOrganization;
use App\Models\PipedrivePerson;
use App\Models\PipedriveUser;

/** @extends PipedriveFactory<PipedrivePerson> */
class PipedrivePersonFactory extends PipedriveFactory
{
    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'crm_connection_id' => fn (array $attributes) => $this->connectionFor($attributes),
            'pipedrive_organization_id' => null,
            'owner_pipedrive_user_id' => null,
            ...$this->synchronizedAttributes(),
            'name' => fake()->name(),
            'is_active' => true,
        ];
    }

    public function withOrganization(): static
    {
        return $this->afterMaking(function (PipedrivePerson $person): void {
            $person->organization()->associate(PipedriveOrganization::factory()->create([
                'company_id' => $person->company_id,
                'crm_connection_id' => $person->crm_connection_id,
            ]));
        });
    }

    public function withOwner(): static
    {
        return $this->afterMaking(function (PipedrivePerson $person): void {
            $person->owner()->associate(PipedriveUser::factory()->create([
                'company_id' => $person->company_id,
                'crm_connection_id' => $person->crm_connection_id,
            ]));
        });
    }

    public function withoutOptionalRelations(): static
    {
        return $this->state(['pipedrive_organization_id' => null, 'owner_pipedrive_user_id' => null]);
    }
}
