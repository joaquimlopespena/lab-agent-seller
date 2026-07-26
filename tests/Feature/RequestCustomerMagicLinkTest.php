<?php

use App\Models\CompanyCustomer;
use App\Models\CustomerIdentity;
use App\Models\CustomerMagicLink;
use App\Notifications\CustomerMagicLinkNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;

uses(RefreshDatabase::class);

test('eligible customer in one or multiple companies receives one hashed magic link', function (int $companies) {
    Notification::fake();
    $identity = CustomerIdentity::factory()->create(['email' => 'customer@example.com']);
    CompanyCustomer::factory()->count($companies)->create(['customer_identity_id' => $identity]);

    $this->post(route('customer.access.store'), ['email' => '  CUSTOMER@EXAMPLE.COM '])
        ->assertRedirect(route('customer.access.request'))
        ->assertSessionHas('customerAccess', 'sent');

    $magicLink = CustomerMagicLink::query()->sole();

    expect($magicLink->customer_identity_id)->toBe($identity->id)
        ->and($magicLink->token_hash)->toHaveLength(64)
        ->and($magicLink->token_hash)->not->toContain('customer@example.com')
        ->and(now()->diffInMinutes($magicLink->expires_at))->toBeBetween(14.0, 15.0);

    Notification::assertSentOnDemand(CustomerMagicLinkNotification::class);
})->with(['one company' => 1, 'multiple companies' => 2]);

test('unassociated email creates no credential and sends no notification', function () {
    Notification::fake();

    $this->post(route('customer.access.store'), ['email' => 'unknown@example.com'])
        ->assertRedirect(route('customer.access.request'))
        ->assertSessionHas('customerAccess', 'not_associated');

    expect(CustomerMagicLink::query()->count())->toBe(0);
    Notification::assertNothingSent();
});

test('invalid email is rejected before lookup', function () {
    Notification::fake();

    $this->post(route('customer.access.store'), ['email' => 'not-an-email'])
        ->assertSessionHasErrors('email');

    expect(CustomerMagicLink::query()->count())->toBe(0);
    Notification::assertNothingSent();
});
