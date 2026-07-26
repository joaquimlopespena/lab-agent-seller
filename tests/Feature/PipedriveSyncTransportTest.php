<?php

use App\Integrations\Pipedrive\HttpPipedriveDataClient;
use App\Integrations\Pipedrive\PipedriveDataClient;
use App\Integrations\Pipedrive\PipedrivePage;
use App\Integrations\Pipedrive\PipedriveTransportException;
use App\Models\CrmSyncStatus;
use App\Models\PipedrivePipeline;
use App\Services\PipedriveSynchronizationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schedule;
use Tests\FakePipedriveDataClient;

uses(RefreshDatabase::class);

it('consumes every page and imports each page transactionally', function () {
    seedSyncStatuses();
    $connection = activePipedriveConnection();
    $fake = (new FakePipedriveDataClient)->withPages('pipelines', [
        new PipedrivePage([['id' => 10, 'name' => 'First']], '1'),
        new PipedrivePage([['id' => 11, 'name' => 'Second']]),
    ]);
    app()->instance(PipedriveDataClient::class, $fake);

    $run = app(PipedriveSynchronizationService::class)->synchronize($connection->company, $connection);

    expect($run->status->slug)->toBe(CrmSyncStatus::Succeeded)
        ->and(PipedrivePipeline::where('company_id', $connection->company_id)->count())->toBe(2)
        ->and($fake->calls)->toContain(
            "{$connection->company_id}:pipelines:first",
            "{$connection->company_id}:pipelines:1",
        );
});

it('retries transient transport failures', function () {
    $connection = activePipedriveConnection();
    Http::preventStrayRequests();
    Http::fakeSequence()
        ->pushStatus(500)
        ->push(['data' => [['id' => 1]], 'additional_data' => ['pagination' => ['more_items_in_collection' => false]]]);

    $client = new HttpPipedriveDataClient(Http::getFacadeRoot(), [
        'api_endpoint' => 'https://example.test/v1',
        'sync_attempts' => 2,
        'sync_retry_delay_milliseconds' => 0,
    ]);

    expect($client->page($connection, 'pipelines')->items)->toHaveCount(1);
    Http::assertSentCount(2);
});

it('stops retrying transport failures after the configured limit', function () {
    $connection = activePipedriveConnection();
    Http::preventStrayRequests();
    Http::fakeSequence()
        ->pushStatus(503)
        ->pushStatus(503);
    $client = new HttpPipedriveDataClient(Http::getFacadeRoot(), [
        'api_endpoint' => 'https://example.test/v1',
        'sync_attempts' => 2,
        'sync_retry_delay_milliseconds' => 0,
    ]);

    expect(fn () => $client->page($connection, 'pipelines'))
        ->toThrow(PipedriveTransportException::class);
    Http::assertSentCount(2);
});

it('does not register recurring synchronization', function () {
    expect(collect(Schedule::events())->filter(
        fn ($event): bool => str_contains($event->command ?? '', 'pipedrive'),
    ))->toBeEmpty();
});
