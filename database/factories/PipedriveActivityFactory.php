<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\PipedriveActivity;
use App\Models\PipedriveDeal;
use App\Models\PipedriveOrganization;
use App\Models\PipedrivePerson;
use App\Models\PipedriveUser;

/** @extends PipedriveFactory<PipedriveActivity> */
class PipedriveActivityFactory extends PipedriveFactory
{
    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'crm_connection_id' => fn (array $attributes) => $this->connectionFor($attributes),
            'pipedrive_deal_id' => null,
            'pipedrive_person_id' => null,
            'pipedrive_organization_id' => null,
            'owner_pipedrive_user_id' => null,
            ...$this->synchronizedAttributes(),
            'subject' => fake()->sentence(3),
            'due_at' => now()->addDay(),
            'is_done' => false,
        ];
    }

    public function withRelations(): static
    {
        return $this->afterMaking(function (PipedriveActivity $activity): void {
            $organization = PipedriveOrganization::factory()->create([
                'company_id' => $activity->company_id,
                'crm_connection_id' => $activity->crm_connection_id,
            ]);
            $person = PipedrivePerson::factory()->create([
                'company_id' => $activity->company_id,
                'crm_connection_id' => $activity->crm_connection_id,
                'pipedrive_organization_id' => $organization,
            ]);

            $activity->deal()->associate(PipedriveDeal::factory()->create([
                'company_id' => $activity->company_id,
                'crm_connection_id' => $activity->crm_connection_id,
                'pipedrive_person_id' => $person,
                'pipedrive_organization_id' => $organization,
            ]));
            $activity->person()->associate($person);
            $activity->organization()->associate($organization);
            $activity->owner()->associate(PipedriveUser::factory()->create([
                'company_id' => $activity->company_id,
                'crm_connection_id' => $activity->crm_connection_id,
            ]));
        });
    }

    public function withoutOptionalRelations(): static
    {
        return $this->state([
            'pipedrive_deal_id' => null,
            'pipedrive_person_id' => null,
            'pipedrive_organization_id' => null,
            'owner_pipedrive_user_id' => null,
        ]);
    }
}
