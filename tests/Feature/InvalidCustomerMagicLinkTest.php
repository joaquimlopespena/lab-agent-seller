<?php

use App\Models\Company;
use App\Models\CustomerMagicLink;
use App\Models\CustomerSession;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

test('missing or tampered magic links are rejected without side effects', function () {
    $token = str()->random(64);

    $this->get(route('customer.access.consume', ['token' => $token]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('customer/access/error')
            ->where('reason', 'invalid')
            ->missing('identity')
            ->missing('companies')
            ->missing('token'));

    expect(CustomerSession::query()->count())->toBe(0);
});

test('expired and used magic links are rejected without session or active tenant', function (string $state) {
    $token = str()->random(64);
    $attributes = $state === 'expired'
        ? ['expires_at' => now()->subMinute()]
        : ['used_at' => now()];
    CustomerMagicLink::factory()->create([
        ...$attributes,
        'token_hash' => hash('sha256', $token),
    ]);

    $this->get(route('customer.access.consume', ['token' => $token]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('customer/access/error')
            ->where('reason', $state === 'expired' ? 'expired' : 'invalid')
            ->missing('identity')
            ->missing('companies'));

    expect(CustomerSession::query()->count())->toBe(0)
        ->and(CustomerSession::query()->whereNotNull('active_company_id')->exists())->toBeFalse()
        ->and(Company::query()->count())->toBe(0);
})->with(['expired', 'used']);
