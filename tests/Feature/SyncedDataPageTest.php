<?php

use App\Models\Company;
use App\Models\CrmConnection;
use App\Models\PipedrivePipeline;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

it('provides only safe tenant-local navigation props', function () {
    $company = Company::factory()->create();
    $otherCompany = Company::factory()->create();
    $user = User::factory()->for($company)->create();
    $connection = activePipedriveConnection($company);
    $otherConnection = activePipedriveConnection($otherCompany);
    PipedrivePipeline::factory()->create([
        'company_id' => $company,
        'crm_connection_id' => $connection,
        'name' => 'Visible pipeline',
    ]);
    PipedrivePipeline::factory()->create([
        'company_id' => $otherCompany,
        'crm_connection_id' => $otherConnection,
        'name' => 'Hidden pipeline',
    ]);

    $response = $this->actingAs($user)
        ->get(route('admin.synced-data.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/synced-data/index')
            ->has('categories', 9)
            ->where('categories.0.count', 1)
            ->where('records.total', 1)
            ->where('readOnly', true)
            ->missing('connection')
            ->missing('tokens')
        );

    expect($response->getContent())
        ->toContain('Visible pipeline')
        ->not->toContain('Hidden pipeline')
        ->not->toContain(CrmConnection::findOrFail($connection->id)->access_token);
});

it('requires an authenticated and verified administrator', function () {
    $this->get(route('admin.synced-data.index'))->assertRedirect(route('login'));
});
