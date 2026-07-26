<?php

namespace App\CustomerAuth;

use App\Notifications\CustomerMagicLinkNotification;
use DateTimeInterface;
use Illuminate\Support\Facades\Notification;

final class LaravelNotificationMagicLinkSender implements CustomerMagicLinkSender
{
    public function send(string $email, string $token, DateTimeInterface $expiresAt): void
    {
        Notification::route('mail', $email)
            ->notify(new CustomerMagicLinkNotification($token, $expiresAt));
    }
}
