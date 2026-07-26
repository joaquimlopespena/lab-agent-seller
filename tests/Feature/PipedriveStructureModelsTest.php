<?php

use App\Models\Company;
use App\Models\PipedriveCustomField;
use App\Models\PipedrivePipeline;
use App\Models\PipedriveStage;
use App\Models\PipedriveUser;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('pipedrive structure relations and casts stay inside each tenant', function () {
    $pipeline = PipedrivePipeline::factory()->create();
    $stage = PipedriveStage::factory()->create([
        'company_id' => $pipeline->company_id,
        'crm_connection_id' => $pipeline->crm_connection_id,
        'pipedrive_pipeline_id' => $pipeline,
    ]);
    $field = PipedriveCustomField::factory()->create();
    $pipedriveUser = PipedriveUser::factory()->create();
    $otherCompany = Company::factory()->create();

    expect($pipeline->stages->first()->is($stage))->toBeTrue()
        ->and($stage->pipeline->is($pipeline))->toBeTrue()
        ->and($pipeline->company->is($pipeline->crmConnection->company))->toBeTrue()
        ->and($field->options_payload)->toBeArray()
        ->and($field->raw_payload)->toBeArray()
        ->and($field->is_active)->toBeBool()
        ->and($pipedriveUser->synced_at)->toBeInstanceOf(DateTimeInterface::class)
        ->and(PipedrivePipeline::forCompany($otherCompany)->count())->toBe(0);
});
