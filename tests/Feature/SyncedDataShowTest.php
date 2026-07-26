<?php

use App\Models\Company;
use App\Models\PipedriveActivity;
use App\Models\PipedriveCustomField;
use App\Models\PipedriveDeal;
use App\Models\PipedriveOrganization;
use App\Models\PipedrivePerson;
use App\Models\PipedrivePersonEmail;
use App\Models\PipedrivePipeline;
use App\Models\PipedriveProduct;
use App\Models\PipedriveStage;
use App\Models\PipedriveUser;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

it('authorizes details for every category and hides internal payloads', function (string $category, string $model) {
    $company = Company::factory()->create();
    $otherCompany = Company::factory()->create();
    $user = User::factory()->for($company)->create();
    $connection = activePipedriveConnection($company);
    $otherConnection = activePipedriveConnection($otherCompany);
    $owned = $model::factory()->create([
        'company_id' => $company,
        'crm_connection_id' => $connection,
        'raw_payload' => ['secret' => 'owned raw secret'],
    ]);
    $foreign = $model::factory()->create([
        'company_id' => $otherCompany,
        'crm_connection_id' => $otherConnection,
        'raw_payload' => ['secret' => 'foreign raw secret'],
    ]);

    $response = $this->actingAs($user)
        ->get(route('admin.synced-data.show', ['category' => $category, 'record' => $owned]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/synced-data/show')
            ->where('record.id', $owned->id)
            ->where('readOnly', true)
            ->missing('record.raw_payload')
            ->missing('record.company_id')
            ->missing('record.crm_connection_id')
        );

    expect($response->getContent())
        ->not->toContain('owned raw secret')
        ->not->toContain('access_token')
        ->not->toContain('refresh_token');

    $this->actingAs($user)
        ->get(route('admin.synced-data.show', ['category' => $category, 'record' => $foreign]))
        ->assertNotFound();
})->with([
    'pipelines' => ['pipelines', PipedrivePipeline::class],
    'stages' => ['stages', PipedriveStage::class],
    'custom fields' => ['custom-fields', PipedriveCustomField::class],
    'users' => ['users', PipedriveUser::class],
    'organizations' => ['organizations', PipedriveOrganization::class],
    'people' => ['people', PipedrivePerson::class],
    'deals' => ['deals', PipedriveDeal::class],
    'products' => ['products', PipedriveProduct::class],
    'activities' => ['activities', PipedriveActivity::class],
]);

it('shows useful person relations from the same tenant', function () {
    $company = Company::factory()->create();
    $user = User::factory()->for($company)->create();
    $connection = activePipedriveConnection($company);
    $organization = PipedriveOrganization::factory()->create([
        'company_id' => $company,
        'crm_connection_id' => $connection,
        'name' => 'Acme Tenant Local',
    ]);
    $owner = PipedriveUser::factory()->create([
        'company_id' => $company,
        'crm_connection_id' => $connection,
        'name' => 'Tenant Owner',
    ]);
    $person = PipedrivePerson::factory()->create([
        'company_id' => $company,
        'crm_connection_id' => $connection,
        'pipedrive_organization_id' => $organization,
        'owner_pipedrive_user_id' => $owner,
        'name' => 'Tenant Person',
    ]);
    PipedrivePersonEmail::factory()->primary()->create([
        'company_id' => $company,
        'pipedrive_person_id' => $person,
        'email' => 'person@example.test',
    ]);

    $this->actingAs($user)
        ->get(route('admin.synced-data.show', ['category' => 'people', 'record' => $person]))
        ->assertInertia(fn (Assert $page) => $page
            ->where('record.relations.organization.title', 'Acme Tenant Local')
            ->where('record.relations.owner.title', 'Tenant Owner')
            ->where('record.relations.emails.0.email', 'person@example.test')
            ->where('record.relations.emails.0.is_primary', true)
        );
});
