<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;

uses(RefreshDatabase::class);

beforeEach(function () {
    config()->set('services.pipedrive', [
        'client_id' => 'client-id',
        'client_secret' => 'client-secret',
        'redirect_uri' => 'https://app.test/oauth/pipedrive/callback',
        'scopes' => ['deals:read', 'persons:read', 'organizations:read'],
        'authorization_endpoint' => 'https://oauth.pipedrive.test/authorize',
        'token_endpoint' => 'https://oauth.pipedrive.test/token',
        'state_ttl_minutes' => 10,
        'timeout_seconds' => 10,
        'connect_timeout_seconds' => 3,
    ]);
});

it('requires an authenticated administrator to start OAuth', function () {
    $this->get(route('oauth.pipedrive.start'))
        ->assertRedirect(route('login'));
});

it('returns to integrations with a safe error when OAuth is not configured', function () {
    config()->set('services.pipedrive.client_id', null);

    $this->actingAs(User::factory()->create())
        ->get(route('oauth.pipedrive.start'))
        ->assertRedirect(route('admin.integrations.index'))
        ->assertSessionHas('toast', [
            'type' => 'error',
            'message' => 'A integração com o Pipedrive ainda não foi configurada.',
        ]);
});

it('redirects with an opaque session and tenant bound state and read only scopes', function () {
    $administrator = User::factory()->create();

    $response = $this->actingAs($administrator)->get(route('oauth.pipedrive.start'));
    $location = $response->headers->get('Location');
    parse_str((string) parse_url($location, PHP_URL_QUERY), $query);
    $payload = json_decode(Crypt::decryptString($query['state']), true, flags: JSON_THROW_ON_ERROR);

    $response->assertRedirectContains('https://oauth.pipedrive.test/authorize');

    expect($query)
        ->client_id->toBe('client-id')
        ->redirect_uri->toBe('https://app.test/oauth/pipedrive/callback')
        ->scope->toBe('deals:read persons:read organizations:read')
        ->not->toHaveKeys(['client_secret'])
        ->and($query['state'])->not->toBe((string) $administrator->company_id)
        ->and($payload['company_id'])->toBe($administrator->company_id)
        ->and(session('pipedrive.oauth.company_id'))->toBe($administrator->company_id);
});
