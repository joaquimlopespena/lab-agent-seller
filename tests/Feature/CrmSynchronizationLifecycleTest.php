<?php

use App\Integrations\Pipedrive\PipedriveDataClient;
use App\Models\Company;
use App\Models\CrmConnection;
use App\Models\CrmConnectionStatus;
use App\Models\CrmSyncRun;
use App\Models\CrmSyncStatus;
use App\Services\PipedriveSynchronizationService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\FakePipedriveDataClient;

uses(RefreshDatabase::class);

beforeEach(function () {
    seedSyncStatuses();
    $this->fake = new FakePipedriveDataClient;
    app()->instance(PipedriveDataClient::class, $this->fake);
});

it('records the complete successful lifecycle', function () {
    $connection = activePipedriveConnection();

    $run = app(PipedriveSynchronizationService::class)->synchronize($connection->company, $connection);

    expect($run->status->slug)->toBe(CrmSyncStatus::Succeeded)
        ->and($run->started_at)->not->toBeNull()
        ->and($run->finished_at)->not->toBeNull()
        ->and($run->error_message)->toBeNull();
});

it('transitions from pending to running while the connection lock transaction is held', function () {
    $connection = activePipedriveConnection();
    $runningStatusId = CrmSyncStatus::where('slug', CrmSyncStatus::Running)->valueOrFail('id');
    $transactionLevelBeforeSync = DB::transactionLevel();
    $transitions = [];

    CrmSyncRun::created(function (CrmSyncRun $run) use (&$transitions): void {
        $transitions[] = [
            'status' => $run->status->slug,
            'transaction_level' => DB::transactionLevel(),
        ];
    });

    CrmSyncRun::updated(function (CrmSyncRun $run) use (&$transitions, $runningStatusId): void {
        if ($run->wasChanged('status_id') && (int) $run->status_id === (int) $runningStatusId) {
            $transitions[] = [
                'status' => CrmSyncStatus::Running,
                'transaction_level' => DB::transactionLevel(),
            ];
        }
    });

    app(PipedriveSynchronizationService::class)->synchronize($connection->company, $connection);

    expect($transitions)->toBe([
        ['status' => CrmSyncStatus::Pending, 'transaction_level' => $transactionLevelBeforeSync + 1],
        ['status' => CrmSyncStatus::Running, 'transaction_level' => $transactionLevelBeforeSync + 1],
    ]);
});

it('records a sanitized failure and permits a retry', function () {
    $connection = activePipedriveConnection();
    $this->fake->failing('pipelines');

    $failed = app(PipedriveSynchronizationService::class)->synchronize($connection->company, $connection);

    expect($failed->status->slug)->toBe(CrmSyncStatus::Failed)
        ->and($failed->error_message)->not->toContain('secret')
        ->and($failed->finished_at)->not->toBeNull();

    app()->instance(PipedriveDataClient::class, new FakePipedriveDataClient);
    $retry = app(PipedriveSynchronizationService::class)->synchronize($connection->company, $connection);

    expect($retry->status->slug)->toBe(CrmSyncStatus::Succeeded)
        ->and($connection->syncRuns()->count())->toBe(2);
});

it('rejects inactive and cross-tenant connections without creating a run', function () {
    $connection = activePipedriveConnection();
    $otherCompany = Company::factory()->create();

    expect(fn () => app(PipedriveSynchronizationService::class)->synchronize($otherCompany, $connection))
        ->toThrow(AuthorizationException::class);

    $inactive = CrmConnectionStatus::factory()->create(['slug' => CrmConnectionStatus::Failed]);
    $connection->update(['status_id' => $inactive->getKey()]);

    expect(fn () => app(PipedriveSynchronizationService::class)->synchronize($connection->company, $connection))
        ->toThrow(LogicException::class)
        ->and(CrmConnection::find($connection->getKey())->syncRuns()->count())->toBe(0);
});
