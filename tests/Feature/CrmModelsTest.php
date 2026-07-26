<?php

use App\Models\CrmConnection;
use App\Models\CrmSyncRun;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

test('crm models expose relationships casts and encrypted hidden tokens', function () {
    $connection = CrmConnection::factory()->create([
        'access_token' => 'access-secret',
        'refresh_token' => 'refresh-secret',
    ]);
    $run = CrmSyncRun::factory()->create([
        'company_id' => $connection->company_id,
        'crm_connection_id' => $connection,
    ]);
    $storedToken = DB::table('crm_connections')->where('id', $connection->id)->value('access_token');

    expect($connection->company->is($run->company))->toBeTrue()
        ->and($connection->status->connections->contains($connection))->toBeTrue()
        ->and($run->crmConnection->is($connection))->toBeTrue()
        ->and($run->status->syncRuns->contains($run))->toBeTrue()
        ->and($connection->access_token)->toBe('access-secret')
        ->and($storedToken)->not->toBe('access-secret')
        ->and($connection->token_expires_at)->toBeInstanceOf(DateTimeInterface::class)
        ->and($run->started_at)->toBeInstanceOf(DateTimeInterface::class)
        ->and($connection->toArray())->not->toHaveKeys(['access_token', 'refresh_token']);
});
