<?php

use App\CustomerAuth\ConsumeCustomerMagicLink;
use App\CustomerAuth\CustomerAuthConfiguration;
use App\Models\CustomerMagicLink;
use App\Models\CustomerSession;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;

uses(RefreshDatabase::class);

test('valid magic link is consumed atomically into a configured customer session', function () {
    config()->set('customer_auth.session_ttl_hours', 8);
    app()->forgetInstance(CustomerAuthConfiguration::class);
    $token = str()->random(64);
    $magicLink = CustomerMagicLink::factory()->create([
        'token_hash' => hash('sha256', $token),
    ]);
    $request = Request::create('/customer/access/'.$token, 'GET', server: [
        'REMOTE_ADDR' => '203.0.113.10',
        'HTTP_USER_AGENT' => 'Customer browser',
    ]);

    $result = app(ConsumeCustomerMagicLink::class)->handle($token, $request);

    expect($result->successful())->toBeTrue()
        ->and($result->session)->toBeInstanceOf(CustomerSession::class)
        ->and($result->session->customer_identity_id)->toBe($magicLink->customer_identity_id)
        ->and($result->session->active_company_id)->toBeNull()
        ->and($result->session->ip_address)->toBe('203.0.113.10')
        ->and($result->session->user_agent)->toBe('Customer browser')
        ->and(now()->diffInHours($result->session->expires_at))->toBeBetween(7.0, 8.0)
        ->and($result->session->token_hash)->toBe(hash('sha256', $result->token));

    expect($magicLink->fresh()->used_at)->not->toBeNull();
});

test('repeated or logically concurrent consumption creates only one session', function () {
    $token = str()->random(64);
    CustomerMagicLink::factory()->create(['token_hash' => hash('sha256', $token)]);
    $request = Request::create('/customer/access/'.$token);
    $consumer = app(ConsumeCustomerMagicLink::class);

    $first = $consumer->handle($token, $request);
    $second = $consumer->handle($token, $request);

    expect($first->successful())->toBeTrue()
        ->and($second->successful())->toBeFalse()
        ->and($second->failure)->toBe('used')
        ->and(CustomerSession::query()->count())->toBe(1);
});
