<?php

use App\Integrations\Pipedrive\PipedriveDataClient;
use App\Models\Company;
use App\Models\CrmSyncRun;
use App\Models\CrmSyncStatus;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\FakePipedriveDataClient;

uses(RefreshDatabase::class);

beforeEach(function () {
    seedSyncStatuses();
    app()->instance(PipedriveDataClient::class, new FakePipedriveDataClient);
});

it('requires an administrator and starts only the synchronization of their company', function () {
    $this->postJson(route('admin.integrations.pipedrive.synchronize'))
        ->assertUnauthorized();

    $company = Company::factory()->create();
    $user = User::factory()->create(['company_id' => $company]);
    $ownConnection = activePipedriveConnection($company);
    activePipedriveConnection();

    $response = $this->actingAs($user)->postJson(route('admin.integrations.pipedrive.synchronize'));

    $response->assertCreated()
        ->assertJsonPath('status', CrmSyncStatus::Succeeded);
    expect($ownConnection->syncRuns)->toHaveCount(1)
        ->and(CrmSyncRun::where('company_id', '!=', $company->id)->count())->toBe(0);
});

it('prevents concurrent execution for one connection', function () {
    $company = Company::factory()->create();
    $user = User::factory()->create(['company_id' => $company]);
    $connection = activePipedriveConnection($company);
    $running = CrmSyncStatus::where('slug', CrmSyncStatus::Running)->firstOrFail();
    CrmSyncRun::factory()->create([
        'company_id' => $company,
        'crm_connection_id' => $connection,
        'status_id' => $running,
        'finished_at' => null,
    ]);

    $this->actingAs($user)
        ->postJson(route('admin.integrations.pipedrive.synchronize'))
        ->assertConflict();

    expect($connection->syncRuns()->count())->toBe(1);
});

it('allows independent tenants to synchronize', function () {
    $firstCompany = Company::factory()->create();
    $secondCompany = Company::factory()->create();
    $firstUser = User::factory()->create(['company_id' => $firstCompany]);
    $secondUser = User::factory()->create(['company_id' => $secondCompany]);
    activePipedriveConnection($firstCompany);
    activePipedriveConnection($secondCompany);

    $this->actingAs($firstUser)->postJson(route('admin.integrations.pipedrive.synchronize'))->assertCreated();
    $this->actingAs($secondUser)->postJson(route('admin.integrations.pipedrive.synchronize'))->assertCreated();

    expect(CrmSyncRun::where('company_id', $firstCompany->id)->count())->toBe(1)
        ->and(CrmSyncRun::where('company_id', $secondCompany->id)->count())->toBe(1);
});
