<?php

use App\Models\CompanyCustomer;
use App\Models\CustomerIdentity;
use App\Models\CustomerMagicLink;
use App\Notifications\CustomerMagicLinkNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Notification;

uses(RefreshDatabase::class);

test('notification targets the normalized recipient with a valid expiring URL', function () {
    Notification::fake();
    $identity = CustomerIdentity::factory()->create(['email' => 'client@example.com']);
    CompanyCustomer::factory()->create(['customer_identity_id' => $identity]);

    $this->post(route('customer.access.store'), ['email' => 'CLIENT@example.com']);

    Notification::assertSentOnDemand(
        CustomerMagicLinkNotification::class,
        function (CustomerMagicLinkNotification $notification, array $channels, object $notifiable): bool {
            $mail = $notification->toMail($notifiable);
            $token = str($mail->actionUrl)->afterLast('/')->toString();
            $storedLink = CustomerMagicLink::query()->sole();

            expect(Arr::wrap($notifiable->routes['mail']))->toBe(['client@example.com'])
                ->and($channels)->toContain('mail')
                ->and($mail->actionUrl)->toBe(route('customer.access.consume', ['token' => $token]))
                ->and(hash('sha256', $token))->toBe($storedLink->token_hash)
                ->and($notification->expiresAt->getTimestamp())->toBe($storedLink->expires_at->getTimestamp())
                ->and($storedLink->token_hash)->not->toBe($token);

            return true;
        },
    );
});
