<?php

use App\Models\AgentConversation;
use App\Models\Company;
use App\Models\CompanyCustomer;
use App\Models\CrmSyncRun;
use App\Models\PipedrivePipeline;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

it('summarizes only the authenticated administrator company', function () {
    $company = Company::factory()->create(['name' => 'Empresa Autorizada']);
    $otherCompany = Company::factory()->create(['name' => 'Empresa Vizinha']);
    $administrator = User::factory()->for($company)->create();

    foreach ([$company, $otherCompany] as $tenant) {
        $customer = CompanyCustomer::factory()->for($tenant)->create();
        $connection = $tenant->crmConnection()->firstOrFail();

        CrmSyncRun::factory()->for($tenant)->create([
            'crm_connection_id' => $connection->id,
        ]);
        PipedrivePipeline::factory()->for($tenant)->create([
            'crm_connection_id' => $connection->id,
        ]);
        AgentConversation::factory()->for($tenant)->create([
            'company_customer_id' => $customer->id,
        ]);
    }

    $this->actingAs($administrator)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('dashboard')
            ->where('company', [
                'id' => $company->id,
                'name' => 'Empresa Autorizada',
            ])
            ->where('summary.sync_runs_count', 1)
            ->where('summary.synced_records_count', 1)
            ->where('summary.customers_count', 1)
            ->where('summary.conversations_count', 1)
            ->where('summary.crm_connection.id', $company->crmConnection->id)
            ->missing('summary.crm_connection.access_token')
            ->missing('summary.crm_connection.refresh_token'));
});

it('shows an empty tenant-aware initial state', function () {
    $company = Company::factory()->create();
    $administrator = User::factory()->for($company)->create();

    $this->actingAs($administrator)
        ->get(route('dashboard'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('summary.crm_connection', null)
            ->where('summary.sync_runs_count', 0)
            ->where('summary.synced_records_count', 0)
            ->where('summary.customers_count', 0)
            ->where('summary.conversations_count', 0));
});
