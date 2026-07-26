<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\PipedriveDeal;
use App\Models\PipedriveOrganization;
use App\Models\PipedrivePerson;
use App\Models\PipedrivePipeline;
use App\Models\PipedriveStage;
use App\Models\PipedriveUser;

/** @extends PipedriveFactory<PipedriveDeal> */
class PipedriveDealFactory extends PipedriveFactory
{
    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'crm_connection_id' => fn (array $attributes) => $this->connectionFor($attributes),
            'pipedrive_pipeline_id' => null,
            'pipedrive_stage_id' => null,
            'pipedrive_person_id' => null,
            'pipedrive_organization_id' => null,
            'owner_pipedrive_user_id' => null,
            ...$this->synchronizedAttributes(),
            'title' => fake()->sentence(3),
            'value' => fake()->randomFloat(2, 10, 100000),
            'currency' => 'BRL',
        ];
    }

    public function withSalesStructure(): static
    {
        return $this->afterMaking(function (PipedriveDeal $deal): void {
            $pipeline = PipedrivePipeline::factory()->create([
                'company_id' => $deal->company_id,
                'crm_connection_id' => $deal->crm_connection_id,
            ]);
            $stage = PipedriveStage::factory()->create([
                'company_id' => $deal->company_id,
                'crm_connection_id' => $deal->crm_connection_id,
                'pipedrive_pipeline_id' => $pipeline,
            ]);

            $deal->pipeline()->associate($pipeline);
            $deal->stage()->associate($stage);
        });
    }

    public function withContact(): static
    {
        return $this->afterMaking(function (PipedriveDeal $deal): void {
            $organization = PipedriveOrganization::factory()->create([
                'company_id' => $deal->company_id,
                'crm_connection_id' => $deal->crm_connection_id,
            ]);
            $person = PipedrivePerson::factory()->create([
                'company_id' => $deal->company_id,
                'crm_connection_id' => $deal->crm_connection_id,
                'pipedrive_organization_id' => $organization,
            ]);

            $deal->organization()->associate($organization);
            $deal->person()->associate($person);
        });
    }

    public function withOwner(): static
    {
        return $this->afterMaking(function (PipedriveDeal $deal): void {
            $deal->owner()->associate(PipedriveUser::factory()->create([
                'company_id' => $deal->company_id,
                'crm_connection_id' => $deal->crm_connection_id,
            ]));
        });
    }

    public function withoutOptionalRelations(): static
    {
        return $this->state([
            'pipedrive_pipeline_id' => null,
            'pipedrive_stage_id' => null,
            'pipedrive_person_id' => null,
            'pipedrive_organization_id' => null,
            'owner_pipedrive_user_id' => null,
        ]);
    }
}
