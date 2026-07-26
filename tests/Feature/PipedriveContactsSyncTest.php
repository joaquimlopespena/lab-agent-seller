<?php

use App\Integrations\Pipedrive\PipedriveDataClient;
use App\Models\PipedriveOrganization;
use App\Models\PipedrivePerson;
use App\Models\PipedrivePersonEmail;
use App\Services\PipedriveSynchronizationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\FakePipedriveDataClient;

uses(RefreshDatabase::class);

it('syncs optional contact relations and replaces normalized email collections', function () {
    seedSyncStatuses();
    $connection = activePipedriveConnection();
    $fake = (new FakePipedriveDataClient)
        ->with('users', [['id' => 9, 'name' => 'Owner']])
        ->with('organizations', [['id' => 8, 'name' => 'Acme', 'owner_id' => 9]])
        ->with('persons', [[
            'id' => 7,
            'name' => 'Customer',
            'org_id' => ['value' => 8],
            'owner_id' => ['id' => 9],
            'email' => [
                ['value' => ' FIRST@Example.Test ', 'label' => 'work', 'primary' => true],
                ['value' => 'second@example.test', 'label' => 'home'],
                ['value' => 'first@example.test'],
            ],
        ], [
            'id' => 6,
            'name' => 'Without relations',
            'org_id' => 404,
            'email' => [],
        ]]);
    app()->instance(PipedriveDataClient::class, $fake);
    $service = app(PipedriveSynchronizationService::class);

    $service->synchronize($connection->company, $connection);
    $person = PipedrivePerson::where('company_id', $connection->company_id)->where('pipedrive_id', 7)->firstOrFail();

    expect($person->organization)->toBeInstanceOf(PipedriveOrganization::class)
        ->and($person->owner)->not->toBeNull()
        ->and($person->emails()->pluck('email')->all())->toEqualCanonicalizing([
            'first@example.test',
            'second@example.test',
        ])
        ->and(PipedrivePerson::where('pipedrive_id', 6)->firstOrFail()->organization)->toBeNull();

    $fake->with('persons', [[
        'id' => 7,
        'name' => 'Renamed',
        'email' => [['value' => 'third@example.test', 'primary' => true]],
    ]]);
    $service->synchronize($connection->company, $connection);

    expect(PipedrivePersonEmail::where('pipedrive_person_id', $person->id)->pluck('email')->all())
        ->toBe(['third@example.test'])
        ->and(PipedrivePerson::where('company_id', $connection->company_id)->where('pipedrive_id', 7)->count())->toBe(1);
});

it('never reuses contact relations from another tenant', function () {
    seedSyncStatuses();
    $first = activePipedriveConnection();
    $second = activePipedriveConnection();
    $fake = (new FakePipedriveDataClient)
        ->with('organizations', [['id' => 8, 'name' => 'Shared external id']])
        ->with('persons', [['id' => 7, 'name' => 'Person', 'org_id' => 8, 'email' => ['same@example.test']]]);
    app()->instance(PipedriveDataClient::class, $fake);
    $service = app(PipedriveSynchronizationService::class);

    $service->synchronize($first->company, $first);
    $service->synchronize($second->company, $second);

    expect(PipedrivePerson::where('pipedrive_id', 7)->get()->pluck('organization.company_id')->all())
        ->toEqualCanonicalizing([$first->company_id, $second->company_id]);
});
