<?php

use App\Models\Company;
use App\Models\CrmConnection;
use App\Models\CrmConnectionStatus;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

it('shows a disconnected state without exposing a foreign tenant connection', function () {
    $company = Company::factory()->create();
    $otherCompany = Company::factory()->create();
    $administrator = User::factory()->for($company)->create();
    CrmConnection::factory()->for($otherCompany)->create([
        'access_token' => 'foreign-access',
        'refresh_token' => 'foreign-refresh',
    ]);

    $this->actingAs($administrator)
        ->get(route('admin.integrations.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/integrations/index')
            ->where('displayStatus', 'disconnected')
            ->where('connection', null)
            ->where('canConnect', true)
            ->where('canReconnect', false)
            ->missing('access_token')
            ->missing('refresh_token'));
});

it('provides safe connection props for every persisted status', function (
    string $status,
    bool $canConnect,
) {
    $administrator = User::factory()->create();
    $statusModel = CrmConnectionStatus::query()->firstOrCreate(
        ['slug' => $status],
        ['name' => ucfirst($status), 'is_active' => true],
    );
    CrmConnection::factory()->for($administrator->company)->for($statusModel, 'status')->create([
        'access_token' => 'top-secret',
        'refresh_token' => 'also-secret',
    ]);

    $this->actingAs($administrator)
        ->get(route('admin.integrations.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('displayStatus', $status)
            ->where('connection.status.slug', $status)
            ->where('canConnect', $canConnect)
            ->where('canReconnect', true)
            ->missing('connection.access_token')
            ->missing('connection.refresh_token'));
})->with([
    'pending' => [CrmConnectionStatus::Pending, false],
    'active' => [CrmConnectionStatus::Active, false],
    'failed' => [CrmConnectionStatus::Failed, true],
    'revoked' => [CrmConnectionStatus::Revoked, true],
    'expired' => [CrmConnectionStatus::Expired, true],
]);
