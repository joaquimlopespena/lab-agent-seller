<?php

use App\Models\AgentConversation;
use App\Models\Company;
use App\Models\CompanyCustomer;
use App\Models\CrmConnection;
use App\Models\CrmSyncRun;
use App\Models\PipedrivePipeline;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;

uses(RefreshDatabase::class);

beforeEach(function () {
    Route::middleware(['web', 'auth'])->group(function () {
        Route::get('/_test/connections/{crmConnection}', function (CrmConnection $crmConnection) {
            Gate::authorize('view', $crmConnection);

            return response()->json(['id' => $crmConnection->id]);
        });
        Route::patch('/_test/connections/{crmConnection}', function (CrmConnection $crmConnection) {
            Gate::authorize('update', $crmConnection);
            $crmConnection->update(['provider' => 'updated-provider']);

            return response()->noContent();
        });
        Route::get('/_test/sync-runs/{crmSyncRun}', fn (CrmSyncRun $crmSyncRun) => $crmSyncRun);
        Route::get('/_test/synced-data/{pipedrivePipeline}', fn (PipedrivePipeline $pipedrivePipeline) => $pipedrivePipeline);
        Route::get('/_test/customers/{companyCustomer}', fn (CompanyCustomer $companyCustomer) => $companyCustomer);
        Route::get('/_test/conversations/{agentConversation}', fn (AgentConversation $agentConversation) => $agentConversation);
    });
});

it('allows policies only for resources owned by the administrator company', function (
    Closure $ownedResource,
    Closure $foreignResource,
) {
    $company = Company::factory()->create();
    $otherCompany = Company::factory()->create();
    $administrator = User::factory()->for($company)->create();
    $resource = $ownedResource($company);
    $otherResource = $foreignResource($otherCompany);

    expect($administrator->can('view', $resource))->toBeTrue()
        ->and($administrator->can('update', $resource))->toBeTrue()
        ->and($administrator->can('view', $otherResource))->toBeFalse()
        ->and($administrator->can('update', $otherResource))->toBeFalse();
})->with([
    'CRM connections' => [
        fn (Company $company) => CrmConnection::factory()->for($company)->create(),
        fn (Company $company) => CrmConnection::factory()->for($company)->create(),
    ],
    'sync runs' => [
        fn (Company $company) => CrmSyncRun::factory()->for($company)->create(),
        fn (Company $company) => CrmSyncRun::factory()->for($company)->create(),
    ],
    'synced data' => [
        fn (Company $company) => PipedrivePipeline::factory()->for($company)->create(),
        fn (Company $company) => PipedrivePipeline::factory()->for($company)->create(),
    ],
    'customers' => [
        fn (Company $company) => CompanyCustomer::factory()->for($company)->create(),
        fn (Company $company) => CompanyCustomer::factory()->for($company)->create(),
    ],
    'conversations' => [
        fn (Company $company) => AgentConversation::factory()->for($company)->create(),
        fn (Company $company) => AgentConversation::factory()->for($company)->create(),
    ],
]);

it('returns not found when route identifiers cross the tenant boundary', function (
    string $path,
    Closure $resourceFactory,
) {
    $company = Company::factory()->create();
    $otherCompany = Company::factory()->create();
    $administrator = User::factory()->for($company)->create();
    $ownedResource = $resourceFactory($company);
    $foreignResource = $resourceFactory($otherCompany);

    $this->actingAs($administrator)
        ->get("/_test/{$path}/{$ownedResource->getRouteKey()}")
        ->assertOk();

    $this->actingAs($administrator)
        ->get("/_test/{$path}/{$foreignResource->getRouteKey()}")
        ->assertNotFound();
})->with([
    'CRM connections' => ['connections', fn (Company $company) => CrmConnection::factory()->for($company)->create()],
    'sync runs' => ['sync-runs', fn (Company $company) => CrmSyncRun::factory()->for($company)->create()],
    'synced data' => ['synced-data', fn (Company $company) => PipedrivePipeline::factory()->for($company)->create()],
    'customers' => ['customers', fn (Company $company) => CompanyCustomer::factory()->for($company)->create()],
    'conversations' => ['conversations', fn (Company $company) => AgentConversation::factory()->for($company)->create()],
]);

it('prevents mutation through a foreign route identifier without changing either tenant', function () {
    $company = Company::factory()->create();
    $otherCompany = Company::factory()->create();
    $administrator = User::factory()->for($company)->create();
    $ownedConnection = CrmConnection::factory()->for($company)->create();
    $foreignConnection = CrmConnection::factory()->for($otherCompany)->create();

    $this->actingAs($administrator)
        ->patch("/_test/connections/{$ownedConnection->id}")
        ->assertNoContent();

    $this->actingAs($administrator)
        ->patch("/_test/connections/{$foreignConnection->id}")
        ->assertNotFound();

    expect($ownedConnection->fresh()->provider)->toBe('updated-provider')
        ->and($foreignConnection->fresh()->provider)->toBe('pipedrive');
});
