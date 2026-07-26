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

it('lists every synchronized category only for the authenticated tenant', function (string $category, string $model, string $attribute) {
    $company = Company::factory()->create();
    $otherCompany = Company::factory()->create();
    $user = User::factory()->for($company)->create();
    $connection = activePipedriveConnection($company);
    $otherConnection = activePipedriveConnection($otherCompany);
    $owned = $model::factory()->create([
        'company_id' => $company,
        'crm_connection_id' => $connection,
        'pipedrive_id' => 777,
        $attribute => 'Owned searchable record',
    ]);
    $model::factory()->create([
        'company_id' => $otherCompany,
        'crm_connection_id' => $otherConnection,
        'pipedrive_id' => 777,
        $attribute => 'Foreign private record',
    ]);

    if ($category === 'people') {
        PipedrivePersonEmail::factory()->create([
            'company_id' => $company,
            'pipedrive_person_id' => $owned,
            'email' => 'owned@example.test',
        ]);
    }

    $this->actingAs($user)
        ->get(route('admin.synced-data.index', ['category' => $category]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/synced-data/index')
            ->where('activeCategory.slug', $category)
            ->where('records.total', 1)
            ->where('records.data.0.id', $owned->id)
            ->where('records.data.0.external_id', 777)
            ->missing('records.data.1')
        );
})->with([
    'pipelines' => ['pipelines', PipedrivePipeline::class, 'name'],
    'stages' => ['stages', PipedriveStage::class, 'name'],
    'custom fields' => ['custom-fields', PipedriveCustomField::class, 'name'],
    'users' => ['users', PipedriveUser::class, 'name'],
    'organizations' => ['organizations', PipedriveOrganization::class, 'name'],
    'people and emails' => ['people', PipedrivePerson::class, 'name'],
    'deals' => ['deals', PipedriveDeal::class, 'title'],
    'products' => ['products', PipedriveProduct::class, 'name'],
    'activities' => ['activities', PipedriveActivity::class, 'subject'],
]);

it('filters and paginates without accepting a company id override', function () {
    $company = Company::factory()->create();
    $otherCompany = Company::factory()->create();
    $user = User::factory()->for($company)->create();
    $connection = activePipedriveConnection($company);
    $otherConnection = activePipedriveConnection($otherCompany);

    PipedrivePipeline::factory()->count(16)->sequence(
        fn ($sequence): array => [
            'company_id' => $company,
            'crm_connection_id' => $connection,
            'name' => "Matching pipeline {$sequence->index}",
        ],
    )->create();
    PipedrivePipeline::factory()->create([
        'company_id' => $company,
        'crm_connection_id' => $connection,
        'name' => 'Unrelated',
    ]);
    PipedrivePipeline::factory()->create([
        'company_id' => $otherCompany,
        'crm_connection_id' => $otherConnection,
        'name' => 'Matching foreign secret',
    ]);

    $this->actingAs($user)
        ->get(route('admin.synced-data.index', [
            'category' => 'pipelines',
            'search' => 'Matching',
            'page' => 2,
            'company_id' => $otherCompany->id,
        ]))
        ->assertInertia(fn (Assert $page) => $page
            ->where('filters.search', 'Matching')
            ->where('records.total', 16)
            ->where('records.current_page', 2)
            ->has('records.data', 1)
            ->where('readOnly', true)
            ->missing('company_id')
        );
});

it('returns a clear empty result for a category without records', function () {
    $company = Company::factory()->create();
    $user = User::factory()->for($company)->create();

    $this->actingAs($user)
        ->get(route('admin.synced-data.index', ['category' => 'activities']))
        ->assertInertia(fn (Assert $page) => $page
            ->where('activeCategory.slug', 'activities')
            ->where('records.total', 0)
            ->has('records.data', 0)
        );
});

it('rejects unknown categories and excessive filters', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('admin.synced-data.index', ['category' => 'unknown']))
        ->assertNotFound();

    $this->actingAs($user)
        ->get(route('admin.synced-data.index', ['category' => 'people', 'search' => str_repeat('a', 101)]))
        ->assertUnprocessable();
});
