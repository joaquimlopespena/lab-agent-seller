<?php

use App\Models\CustomerMagicLink;
use App\Models\CustomerSession;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

test('request page is public and contains no tenant or credential props', function () {
    $this->get(route('customer.access.request'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('customer/access/request')
            ->missing('companies')
            ->missing('identity')
            ->missing('token')
            ->missing('hash'));
});

test('successful request feedback contains no associated company data', function () {
    $this->withSession(['customerAccess' => 'sent'])
        ->get(route('customer.access.request'))
        ->assertInertia(fn (Assert $page) => $page
            ->component('customer/access/request')
            ->where('customerAccess', 'sent')
            ->missing('companies')
            ->missing('identity'));
});

test('valid consumption redirects to protected confirmation without sensitive props', function () {
    $token = str()->random(64);
    CustomerMagicLink::factory()->create(['token_hash' => hash('sha256', $token)]);

    $this->get(route('customer.access.consume', ['token' => $token]))
        ->assertRedirect(route('customer.access.confirmation'));

    expect(CustomerSession::query()->count())->toBe(1);
});

test('invalid and expired pages expose only a safe reason', function (string $state) {
    $token = str()->random(64);

    if ($state === 'expired') {
        CustomerMagicLink::factory()->create([
            'token_hash' => hash('sha256', $token),
            'expires_at' => now()->subMinute(),
        ]);
    }

    $this->get(route('customer.access.consume', ['token' => $token]))
        ->assertInertia(fn (Assert $page) => $page
            ->component('customer/access/error')
            ->where('reason', $state)
            ->missing('companies')
            ->missing('identity')
            ->missing('token')
            ->missing('email'));
})->with(['invalid', 'expired']);
