<?php

use App\CustomerAuth\CustomerAuthConfiguration;

test('customer authentication configuration has the documented defaults', function () {
    $configuration = new CustomerAuthConfiguration(config('customer_auth'));

    expect($configuration->magicLinkTtlMinutes)->toBe(15)
        ->and($configuration->sessionTtlHours)->toBe(24);
});

test('customer authentication durations accept environment-style overrides', function () {
    $configuration = new CustomerAuthConfiguration([
        ...config('customer_auth'),
        'magic_link_ttl_minutes' => '30',
        'session_ttl_hours' => '12',
    ]);

    expect($configuration->magicLinkTtlMinutes)->toBe(30)
        ->and($configuration->sessionTtlHours)->toBe(12);
});

test('invalid customer authentication durations fail explicitly', function (mixed $value) {
    new CustomerAuthConfiguration([
        ...config('customer_auth'),
        'magic_link_ttl_minutes' => $value,
    ]);
})->with([0, -1, 'invalid', 1.5, null])->throws(InvalidArgumentException::class);
