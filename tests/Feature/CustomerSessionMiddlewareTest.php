<?php

use App\Models\CustomerMagicLink;
use App\Models\CustomerSession;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Symfony\Component\HttpFoundation\Cookie;

uses(RefreshDatabase::class);

test('valid customer cookie resolves customer guard independently of admin guard', function () {
    $token = str()->random(64);
    $customerSession = CustomerSession::factory()->create([
        'token_hash' => hash('sha256', $token),
    ]);
    $admin = User::factory()->create();

    $this->actingAs($admin)
        ->withCookie('customer_session', $token)
        ->get(route('customer.access.confirmation'))
        ->assertOk();

    expect(auth('web')->id())->toBe($admin->id)
        ->and(auth('customer')->id())->toBe($customerSession->customer_identity_id);
});

test('expired or revoked customer session is refused without affecting admin authentication', function (string $state) {
    $token = str()->random(64);
    $attributes = $state === 'expired'
        ? ['expires_at' => now()->subMinute()]
        : ['revoked_at' => now()];
    CustomerSession::factory()->create([
        ...$attributes,
        'token_hash' => hash('sha256', $token),
    ]);
    $admin = User::factory()->create();

    $this->actingAs($admin)
        ->withCookie('customer_session', $token)
        ->get(route('customer.access.confirmation'))
        ->assertRedirect(route('customer.access.request'))
        ->assertCookieExpired('customer_session');

    expect(auth('web')->id())->toBe($admin->id)
        ->and(auth('customer')->guest())->toBeTrue();
})->with(['expired', 'revoked']);

test('session cookie issued by magic link is secure http only and separate from admin session', function () {
    $token = str()->random(64);
    CustomerMagicLink::factory()->create([
        'token_hash' => hash('sha256', $token),
    ]);

    $response = $this->get(route('customer.access.consume', ['token' => $token]));
    $cookie = collect($response->headers->getCookies())
        ->firstWhere(fn (Cookie $cookie): bool => $cookie->getName() === 'customer_session');

    expect($cookie)->not->toBeNull()
        ->and($cookie->isSecure())->toBeTrue()
        ->and($cookie->isHttpOnly())->toBeTrue()
        ->and($cookie->getSameSite())->toBe('lax')
        ->and(collect($response->headers->getCookies())
            ->filter(fn (Cookie $responseCookie): bool => $responseCookie->getName() === 'customer_session'))
        ->toHaveCount(1);
});

test('customer authentication never grants administrative route access', function () {
    $token = str()->random(64);
    CustomerSession::factory()->create(['token_hash' => hash('sha256', $token)]);

    $this->withCookie('customer_session', $token)
        ->get(route('dashboard'))
        ->assertRedirect(route('login'));
});

test('customer session can be revoked without ending the admin session', function () {
    $token = str()->random(64);
    $customerSession = CustomerSession::factory()->create([
        'token_hash' => hash('sha256', $token),
    ]);
    $admin = User::factory()->create();

    $this->actingAs($admin)
        ->withCookie('customer_session', $token)
        ->delete(route('customer.session.destroy'))
        ->assertRedirect(route('customer.access.request'))
        ->assertCookieExpired('customer_session');

    expect($customerSession->fresh()->revoked_at)->not->toBeNull()
        ->and(auth('web')->id())->toBe($admin->id);
});
