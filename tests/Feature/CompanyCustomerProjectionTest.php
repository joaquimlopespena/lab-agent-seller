<?php

use App\Integrations\Pipedrive\PipedriveDataClient;
use App\Models\CompanyCustomer;
use App\Models\CustomerIdentity;
use App\Services\PipedriveSynchronizationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\FakePipedriveDataClient;

uses(RefreshDatabase::class);

it('shares only the global identity while keeping customer links tenant local and idempotent', function () {
    seedSyncStatuses();
    $first = activePipedriveConnection();
    $second = activePipedriveConnection();
    $fake = (new FakePipedriveDataClient)->with('persons', [[
        'id' => 1,
        'name' => 'Customer',
        'email' => [
            ['value' => 'shared@example.test', 'primary' => true],
            ['value' => 'other@example.test'],
        ],
    ]]);
    app()->instance(PipedriveDataClient::class, $fake);
    $service = app(PipedriveSynchronizationService::class);

    $service->synchronize($first->company, $first);
    $service->synchronize($second->company, $second);
    $service->synchronize($first->company, $first);

    expect(CustomerIdentity::where('email', 'shared@example.test')->count())->toBe(1)
        ->and(CustomerIdentity::count())->toBe(2)
        ->and(CompanyCustomer::count())->toBe(4);

    foreach (CompanyCustomer::with(['pipedrivePerson', 'pipedrivePersonEmail'])->get() as $customer) {
        expect($customer->pipedrivePerson->company_id)->toBe($customer->company_id)
            ->and($customer->pipedrivePersonEmail->company_id)->toBe($customer->company_id);
    }
});

it('rejects cross-tenant person and email references', function () {
    seedSyncStatuses();
    $first = activePipedriveConnection();
    $second = activePipedriveConnection();
    $fake = (new FakePipedriveDataClient)->with('persons', [[
        'id' => 1,
        'name' => 'Customer',
        'email' => ['customer@example.test'],
    ]]);
    app()->instance(PipedriveDataClient::class, $fake);
    $service = app(PipedriveSynchronizationService::class);
    $service->synchronize($first->company, $first);
    $service->synchronize($second->company, $second);

    $firstCustomer = CompanyCustomer::where('company_id', $first->company_id)->firstOrFail();
    $secondCustomer = CompanyCustomer::where('company_id', $second->company_id)->firstOrFail();

    expect(fn () => $firstCustomer->update([
        'pipedrive_person_id' => $secondCustomer->pipedrive_person_id,
        'pipedrive_person_email_id' => $secondCustomer->pipedrive_person_email_id,
    ]))->toThrow(DomainException::class);
});
