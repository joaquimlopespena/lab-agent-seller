<?php

use App\Models\Company;
use App\Models\CrmSyncRun;
use App\Models\CrmSyncStatus;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

it('shows ordered tenant-only synchronization history with sanitized errors and retry state', function () {
    seedSyncStatuses();
    $company = Company::factory()->create();
    $otherCompany = Company::factory()->create();
    $user = User::factory()->create(['company_id' => $company]);
    $connection = activePipedriveConnection($company);
    $otherConnection = activePipedriveConnection($otherCompany);
    $failed = CrmSyncStatus::where('slug', CrmSyncStatus::Failed)->firstOrFail();
    $succeeded = CrmSyncStatus::where('slug', CrmSyncStatus::Succeeded)->firstOrFail();

    CrmSyncRun::factory()->create([
        'company_id' => $company,
        'crm_connection_id' => $connection,
        'status_id' => $succeeded,
        'created_at' => now()->subMinute(),
        'finished_at' => now()->subMinute(),
    ]);
    $latest = CrmSyncRun::factory()->create([
        'company_id' => $company,
        'crm_connection_id' => $connection,
        'status_id' => $failed,
        'error_message' => 'Não foi possível concluir a sincronização. Tente novamente.',
        'created_at' => now(),
        'finished_at' => now(),
    ]);
    CrmSyncRun::factory()->create([
        'company_id' => $otherCompany,
        'crm_connection_id' => $otherConnection,
        'status_id' => $failed,
        'error_message' => 'private tenant error',
    ]);

    $this->actingAs($user)
        ->get(route('admin.integrations.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/integrations/index')
            ->where('canSynchronize', true)
            ->has('syncRuns', 2)
            ->where('syncRuns.0.id', $latest->id)
            ->where('syncRuns.0.status.slug', CrmSyncStatus::Failed)
            ->where('syncRuns.0.error_message', 'Não foi possível concluir a sincronização. Tente novamente.')
            ->missing('syncRuns.2')
        );
});

it('hides the retry action while the tenant connection is running', function () {
    seedSyncStatuses();
    $company = Company::factory()->create();
    $user = User::factory()->create(['company_id' => $company]);
    $connection = activePipedriveConnection($company);
    $running = CrmSyncStatus::where('slug', CrmSyncStatus::Running)->firstOrFail();
    CrmSyncRun::factory()->create([
        'company_id' => $company,
        'crm_connection_id' => $connection,
        'status_id' => $running,
    ]);

    $this->actingAs($user)
        ->get(route('admin.integrations.index'))
        ->assertInertia(fn (Assert $page) => $page->where('canSynchronize', false));
});
