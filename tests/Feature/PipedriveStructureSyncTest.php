<?php

use App\Integrations\Pipedrive\PipedriveDataClient;
use App\Models\PipedriveCustomField;
use App\Models\PipedrivePipeline;
use App\Models\PipedriveStage;
use App\Models\PipedriveUser;
use App\Services\PipedriveSynchronizationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\FakePipedriveDataClient;

uses(RefreshDatabase::class);

it('upserts structure idempotently and isolates colliding external ids', function () {
    seedSyncStatuses();
    $firstConnection = activePipedriveConnection();
    $secondConnection = activePipedriveConnection();
    $fake = (new FakePipedriveDataClient)
        ->with('pipelines', [['id' => 1, 'name' => 'Sales', 'order_nr' => 1]])
        ->with('stages', [['id' => 2, 'pipeline_id' => 1, 'name' => 'New']])
        ->with('dealFields', [[
            'id' => 3,
            'key' => 'source',
            'name' => 'Source',
            'field_type' => 'varchar',
            'options' => [['id' => 1, 'label' => 'Web']],
        ]])
        ->with('users', [['id' => 4, 'name' => 'Owner', 'email' => 'owner@example.test']]);
    app()->instance(PipedriveDataClient::class, $fake);
    $service = app(PipedriveSynchronizationService::class);

    $service->synchronize($firstConnection->company, $firstConnection);
    $service->synchronize($secondConnection->company, $secondConnection);

    $fake->with('pipelines', [['id' => 1, 'name' => 'Updated', 'active_flag' => false]])
        ->with('stages', [['id' => 2, 'pipeline_id' => 1, 'name' => 'Qualified']]);
    $service->synchronize($firstConnection->company, $firstConnection);

    $pipeline = PipedrivePipeline::where('company_id', $firstConnection->company_id)->sole();
    $stage = PipedriveStage::where('company_id', $firstConnection->company_id)->sole();

    expect(PipedrivePipeline::where('pipedrive_id', 1)->count())->toBe(2)
        ->and(PipedriveStage::where('pipedrive_id', 2)->count())->toBe(2)
        ->and(PipedriveCustomField::where('company_id', $firstConnection->company_id)->count())->toBe(1)
        ->and(PipedriveUser::where('company_id', $firstConnection->company_id)->count())->toBe(1)
        ->and($pipeline->name)->toBe('Updated')
        ->and($pipeline->is_active)->toBeFalse()
        ->and($stage->pipeline->is($pipeline))->toBeTrue()
        ->and($stage->name)->toBe('Qualified');
});
