<?php

use App\Integrations\Pipedrive\PipedriveDataClient;
use App\Models\PipedriveActivity;
use App\Models\PipedriveDeal;
use App\Models\PipedriveProduct;
use App\Services\PipedriveSynchronizationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\FakePipedriveDataClient;

uses(RefreshDatabase::class);

it('syncs complete and partial sales relations with an idempotent product pivot', function () {
    seedSyncStatuses();
    $connection = activePipedriveConnection();
    $fake = (new FakePipedriveDataClient)
        ->with('pipelines', [['id' => 1, 'name' => 'Sales']])
        ->with('stages', [['id' => 2, 'pipeline_id' => 1, 'name' => 'New']])
        ->with('users', [['id' => 3, 'name' => 'Owner']])
        ->with('organizations', [['id' => 4, 'name' => 'Acme']])
        ->with('persons', [['id' => 5, 'name' => 'Person', 'org_id' => 4, 'email' => []]])
        ->with('deals', [[
            'id' => 6,
            'title' => 'Complete',
            'pipeline_id' => 1,
            'stage_id' => 2,
            'person_id' => 5,
            'org_id' => 4,
            'user_id' => 3,
            'value' => 120.50,
            'currency' => 'brl',
        ], [
            'id' => 7,
            'title' => 'Partial',
            'pipeline_id' => 404,
        ]])
        ->with('products', [['id' => 8, 'name' => 'Plan', 'prices' => [['price' => 50, 'currency' => 'BRL']]]])
        ->with('deals/6/products', [[
            'product_id' => 8,
            'quantity' => 2,
            'item_price' => 45,
            'discount_percentage' => 10,
        ]])
        ->with('activities', [[
            'id' => 9,
            'subject' => 'Follow up',
            'deal_id' => 6,
            'person_id' => 5,
            'org_id' => 4,
            'user_id' => 3,
            'done' => true,
            'due_date' => '2026-07-27',
            'due_time' => '09:30',
        ]]);
    app()->instance(PipedriveDataClient::class, $fake);
    $service = app(PipedriveSynchronizationService::class);
    $service->synchronize($connection->company, $connection);

    $deal = PipedriveDeal::where('company_id', $connection->company_id)->where('pipedrive_id', 6)->firstOrFail();
    $partial = PipedriveDeal::where('company_id', $connection->company_id)->where('pipedrive_id', 7)->firstOrFail();
    $activity = PipedriveActivity::where('company_id', $connection->company_id)->sole();

    expect($deal->pipeline)->not->toBeNull()
        ->and($deal->stage)->not->toBeNull()
        ->and($deal->person)->not->toBeNull()
        ->and($partial->pipeline)->toBeNull()
        ->and($activity->deal->is($deal))->toBeTrue()
        ->and($activity->is_done)->toBeTrue()
        ->and($deal->products)->toHaveCount(1)
        ->and($deal->products->first()->pivot->quantity)->toBe('2.0000')
        ->and($deal->products->first()->pivot->discount_percentage)->toBe('10.00');

    $fake->with('deals/6/products', [[
        'product_id' => 8,
        'quantity' => 3,
        'item_price' => 40,
        'discount' => 20,
    ]]);
    $service->synchronize($connection->company, $connection);

    $pivot = $deal->fresh()->products()->firstOrFail()->pivot;
    expect(PipedriveProduct::where('company_id', $connection->company_id)->count())->toBe(1)
        ->and($deal->products()->count())->toBe(1)
        ->and($pivot->quantity)->toBe('3.0000')
        ->and($pivot->discount_percentage)->toBe('20.00');
});

it('does not resolve sales references across tenants', function () {
    seedSyncStatuses();
    $first = activePipedriveConnection();
    $second = activePipedriveConnection();
    $fake = (new FakePipedriveDataClient)
        ->with('pipelines', [['id' => 1, 'name' => 'Pipeline']])
        ->with('deals', [['id' => 2, 'title' => 'Deal', 'pipeline_id' => 1]]);
    app()->instance(PipedriveDataClient::class, $fake);
    $service = app(PipedriveSynchronizationService::class);

    $service->synchronize($first->company, $first);
    $service->synchronize($second->company, $second);

    foreach (PipedriveDeal::with('pipeline')->get() as $deal) {
        expect($deal->pipeline->company_id)->toBe($deal->company_id);
    }
});
